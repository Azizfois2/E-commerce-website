(() => {
    "use strict";

    const TAX_RATE = 0.0825;
    const COD_FEE = 30;
    const FREE_SHIPPING_THRESHOLD = 1000;
    const SHIPPING_PRICES = { standard: 100, express: 200, overnight: 400, free: 0, pickup: 0 };

    // Test card numbers for simulated payment
    const TEST_CARDS = {
        '4000000000000002': { decline: true, reason: 'Card declined. Please use a different card.' },
        '4000000000009995': { decline: true, reason: 'Insufficient funds. Please try another card.' },
        '4000000000000069': { decline: true, reason: 'Card expired. Please update your card details.' },
    };

    const els = {
        orderItems: document.getElementById('orderItems'),
        orderSubtotal: document.getElementById('orderSubtotal'),
        orderShipping: document.getElementById('orderShipping'),
        orderTax: document.getElementById('orderTax'),
        orderTotal: document.getElementById('orderTotal'),
        shippingForm: document.getElementById('shippingForm'),
        shippingInputs: document.querySelectorAll('input[name="shipping"]'),
        paymentMethodInputs: document.querySelectorAll('input[name="paymentMethod"]'),
        paymentForms: {
            'credit-card': document.getElementById('creditCardForm'),
            paypal: document.getElementById('paypalForm'),
            bitcoin: document.getElementById('bitcoinForm'),
            'apple-pay': document.getElementById('applePayForm'),
            'google-pay': document.getElementById('googlePayForm'),
            'nfc-biometric': document.getElementById('nfcBiometricForm'),
            cod: document.getElementById('codForm')
        },
        sameAsShipping: document.getElementById('sameAsShipping'),
        billingAddressForm: document.getElementById('billingAddressForm'),
        authInputs: document.querySelectorAll('input[name="auth"]'),
        loginCheckout: document.getElementById('loginCheckout'),
        loyaltySection: document.getElementById('loyaltySection'),
        userPointsBalance: document.getElementById('userPointsBalance'),
        redeemPointsInput: document.getElementById('redeemPointsInput'),
        applyPointsBtn: document.getElementById('applyPointsBtn'),
        pointsMessage: document.getElementById('pointsMessage'),
        checkoutPromoCode: document.getElementById('checkoutPromoCode'),
        applyCheckoutPromoBtn: document.getElementById('applyCheckoutPromoBtn'),
        checkoutPromoMessage: document.getElementById('checkoutPromoMessage'),
        orderDiscountRow: document.getElementById('orderDiscountRow'),
        orderDiscount: document.getElementById('orderDiscount'),
        // Processing overlay
        paymentProcessing: document.getElementById('paymentProcessing'),
        processingTitle: document.getElementById('processingTitle'),
        processingSubtitle: document.getElementById('processingSubtitle'),
        processingAmount: document.getElementById('processingAmount'),
        stepVerify: document.getElementById('stepVerify'),
        stepAuth: document.getElementById('stepAuth'),
        stepConfirm: document.getElementById('stepConfirm'),
        checkoutUpsells: document.getElementById('checkoutUpsells'),
        checkoutUpsellList: document.getElementById('checkoutUpsellList'),
    };

    let pointsRedeemed = 0;
    let userMaxPoints = 0;
    let activePromo = null;
    let paypalRendered = false;

    // Stripe SDK Real Integration Instances
    let stripeInstance = null;
    let stripeElements = null;
    let stripePaymentElement = null;
    let stripeClientSecret = null;
    let nfcAuthorized = false;
    let biometricAuthorized = false;

    // ── Helpers ───────────────────────────────────────────────
    function getCartItems() {
        if (typeof Cart !== 'undefined' && Array.isArray(Cart.items)) return Cart.items;
        try { return JSON.parse(localStorage.getItem('cart') || '[]'); } catch { return []; }
    }

    function formatMoney(v) {
        return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    }

    function generateTxnId() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let id = 'TXN-';
        for (let i = 0; i < 12; i++) id += chars.charAt(Math.floor(Math.random() * chars.length));
        return id;
    }

    function selectedShippingMethod() {
        return document.querySelector('input[name="shipping"]:checked')?.value || 'standard';
    }

    function generatePickupVerificationCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        const bytes = new Uint8Array(8);
        if (window.crypto?.getRandomValues) {
            window.crypto.getRandomValues(bytes);
        } else {
            for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
        }
        const token = Array.from(bytes, byte => chars[byte % chars.length]).join('');
        return `PICKUP-${token.slice(0, 4)}-${token.slice(4)}`;
    }

    function getPickupVerificationCode() {
        if (!window.currentPickupVerificationCode) {
            window.currentPickupVerificationCode = generatePickupVerificationCode();
        }
        return window.currentPickupVerificationCode;
    }

    function formatPickupAddress(store) {
        if (!store) return '';
        return [
            'Store Pickup',
            store.name ? `Store: ${store.name}` : '',
            store.address ? `Address: ${store.address}` : '',
            store.hours ? `Hours: ${store.hours}` : '',
            store.phone ? `Phone: ${store.phone}` : ''
        ].filter(Boolean).join('\n');
    }

    function luhnCheck(cardNo) {
        let s = 0, alt = false;
        for (let i = cardNo.length - 1; i >= 0; i--) {
            let d = parseInt(cardNo.charAt(i), 10);
            if (alt) d *= 2;
            s += Math.floor(d / 10) + d % 10;
            alt = !alt;
        }
        return s % 10 === 0;
    }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    // ── Loyalty ──────────────────────────────────────────────
    async function loadLoyalty() {
        if (!els.loyaltySection) return;
        try {
            const res = await fetch('api/loyalty.php?action=balance');
            const data = await res.json();
            if (data.success && data.balance > 0) {
                userMaxPoints = data.balance;
                els.userPointsBalance.textContent = data.balance;
                els.loyaltySection.style.display = 'block';
            }
        } catch (e) {}
    }

    function initLoyalty() {
        if (!els.applyPointsBtn) return;
        els.applyPointsBtn.addEventListener('click', () => {
            const pts = parseInt(els.redeemPointsInput.value) || 0;
            if (pts < 0) return;
            if (pts > userMaxPoints) {
                els.pointsMessage.textContent = `You only have ${userMaxPoints} points.`;
                els.pointsMessage.style.color = 'var(--red)';
                return;
            }
            if (pts > 0 && pts < 100) {
                els.pointsMessage.textContent = 'Minimum 100 points required.';
                els.pointsMessage.style.color = 'var(--red)';
                return;
            }
            pointsRedeemed = pts;
            els.pointsMessage.textContent = pts > 0 ? `Applied ${pts} points!` : '';
            els.pointsMessage.style.color = 'var(--green)';
            syncCheckout();
        });
    }

    function promoDiscount(subtotal, shipping) {
        if (!activePromo) return 0;
        if (activePromo.type === 'percent') return subtotal * (activePromo.value / 100);
        if (activePromo.type === 'fixed') return Math.min(activePromo.value, subtotal);
        if (activePromo.type === 'shipping') return shipping;
        return 0;
    }

    function initPromoCode() {
        if (!els.applyCheckoutPromoBtn || !els.checkoutPromoCode) return;
        els.applyCheckoutPromoBtn.addEventListener('click', async () => {
            const code = els.checkoutPromoCode.value.trim().toUpperCase();
            const subtotal = getCartItems().reduce((s, i) => s + (i.price * i.quantity), 0);
            if (!code) {
                if (els.checkoutPromoMessage) {
                    els.checkoutPromoMessage.textContent = 'Enter a promo code.';
                    els.checkoutPromoMessage.style.color = 'var(--red)';
                }
                return;
            }
            try {
                const res = await fetch('api/coupon-validate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ code, subtotal })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Invalid promo code');
                activePromo = data;
                if (els.checkoutPromoMessage) {
                    els.checkoutPromoMessage.textContent = `Applied: ${data.label}`;
                    els.checkoutPromoMessage.style.color = 'var(--green)';
                }
            } catch (e) {
                activePromo = null;
                if (els.checkoutPromoMessage) {
                    els.checkoutPromoMessage.textContent = e.message;
                    els.checkoutPromoMessage.style.color = 'var(--red)';
                }
            }
            syncCheckout();
        });
    }

    // ── Card Preview ─────────────────────────────────────────
    function initCardPreview() {
        const numInput = document.getElementById('cardNumber');
        const holderInput = document.getElementById('cardHolder');
        const expInput = document.getElementById('expiryDate');

        if (numInput) {
            numInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '');
                e.target.value = val.match(/.{1,4}/g)?.join(' ') || '';
                document.getElementById('previewCardNumber').textContent = e.target.value || '•••• •••• •••• ••••';
            });
        }
        if (holderInput) {
            holderInput.addEventListener('input', (e) => {
                document.getElementById('previewCardHolder').textContent = e.target.value.toUpperCase() || 'YOUR NAME';
            });
        }
        if (expInput) {
            expInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length > 2) val = val.substring(0, 2) + '/' + val.substring(2, 4);
                e.target.value = val;
                document.getElementById('previewCardExpiry').textContent = val || 'MM/YY';
            });
        }
    }

    // ── Render ────────────────────────────────────────────────
    function selectedPaymentMethod() {
        return document.querySelector('input[name="paymentMethod"]:checked')?.value || 'credit-card';
    }

    function selectedShippingPrice(subtotal) {
        const type = selectedShippingMethod();
        const base = SHIPPING_PRICES[type] ?? SHIPPING_PRICES.standard;
        return (subtotal >= FREE_SHIPPING_THRESHOLD && type === 'standard') ? 0 : base;
    }

    function renderItems(items) {
        if (!els.orderItems) return;
        if (!items.length) { els.orderItems.innerHTML = '<p class="text-center">Your cart is empty.</p>'; return; }
        els.orderItems.innerHTML = items.map(i => `
            <div class="order-item">
                <div class="order-item-main">
                    <span class="order-item-name">${i.name}</span>
                    <span class="order-item-qty">x${i.quantity}</span>
                </div>
                <span class="order-item-price">${formatMoney(i.price * i.quantity)}</span>
            </div>`).join('');
    }

    function getCatalogProducts() {
        if (typeof products !== 'undefined' && Array.isArray(products)) return products;
        if (Array.isArray(window.products)) return window.products;
        return [];
    }

    function getProductById(id) {
        return getCatalogProducts().find(product => String(product.id) === String(id)) || null;
    }

    function cartHasCategory(items, category) {
        return items.some(item => item.category === category);
    }

    function cartHasAccessory(items, productId) {
        return items.some(item => String(item.id) === String(productId));
    }

    function productSpecText(item) {
        const specs = item && item.specs ? item.specs : {};
        return Object.values(specs).join(' ').toLowerCase();
    }

    function selectUpsells(items) {
        if (!getCatalogProducts().length || !items.length) return [];

        const suggestions = [];
        const addSuggestion = (productId, reason, priority) => {
            const product = getProductById(productId);
            if (!product || !product.inStock || cartHasAccessory(items, product.id)) return;
            suggestions.push({ product, reason, priority });
        };

        const hasCpu = cartHasCategory(items, 'cpu');
        const hasGpu = cartHasCategory(items, 'gpu');
        const hasCooling = cartHasCategory(items, 'cooling');
        const hasStorage = cartHasCategory(items, 'storage');
        const hasCase = cartHasCategory(items, 'case');
        const hasSataStorage = items.some(item => item.category === 'storage' && productSpecText(item).includes('sata'));
        const hasNvmeStorage = items.some(item => item.category === 'storage' && productSpecText(item).includes('nvme'));
        const hasHighPowerGpu = items.some(item => item.category === 'gpu' && /rtx 40|rtx 50|rx 7/i.test(item.name || ''));

        if (hasCpu || hasCooling) {
            addSuggestion(602, 'Fresh thermal paste for CPU cooler mounting.', 10);
        }
        if (hasSataStorage) {
            addSuggestion(604, 'SATA SSDs and HDDs often need a separate data cable.', 20);
        }
        if (hasGpu || hasCase) {
            addSuggestion(605, 'Keeps GPU and front-panel cables tidy for cleaner airflow.', 30);
        }
        if (hasCpu) {
            addSuggestion(606, 'A simple safety pick for first-time assembly.', 40);
        }
        if (hasHighPowerGpu) {
            addSuggestion(607, 'Useful when an older PSU cable set needs GPU connector flexibility.', 50);
        }
        if (hasNvmeStorage) {
            addSuggestion(608, 'Helps NVMe drives hold speed during long gaming or copy sessions.', 60);
        }
        if (hasGpu && !hasCooling) {
            addSuggestion(609, 'Extra intake or exhaust fan for GPU-heavy builds.', 70);
        }
        if (!hasStorage && (hasCpu || hasGpu)) {
            addSuggestion(604, 'Handy spare cable for later storage upgrades.', 80);
        }

        return suggestions
            .sort((a, b) => a.priority - b.priority)
            .slice(0, 3);
    }

    function renderUpsells(items) {
        if (!els.checkoutUpsells || !els.checkoutUpsellList) return;

        const suggestions = selectUpsells(items);
        if (!suggestions.length) {
            els.checkoutUpsells.style.display = 'none';
            els.checkoutUpsellList.innerHTML = '';
            return;
        }

        els.checkoutUpsellList.innerHTML = suggestions.map(({ product, reason }) => `
            <div class="checkout-upsell-item">
                <div class="checkout-upsell-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="checkout-upsell-copy">
                    <strong>${product.name}</strong>
                    <span>${reason}</span>
                    <em>${formatMoney(product.price)}</em>
                </div>
                <button type="button" class="checkout-upsell-add" data-id="${product.id}" aria-label="Add ${product.name}">
                    <i class="fas fa-cart-plus"></i>
                </button>
            </div>
        `).join('');

        els.checkoutUpsellList.querySelectorAll('.checkout-upsell-add').forEach(btn => {
            btn.addEventListener('click', () => {
                const product = getProductById(btn.dataset.id);
                if (!product) return;
                if (typeof Cart !== 'undefined' && typeof Cart.add === 'function') {
                    Cart.add(product);
                } else {
                    const current = getCartItems();
                    current.push({ ...product, quantity: 1 });
                    localStorage.setItem('cart', JSON.stringify(current));
                }
                syncCheckout();
            });
        });

        els.checkoutUpsells.style.display = 'block';
    }

    function computeTotals(items) {
        const subtotal = items.reduce((s, i) => s + (i.price * i.quantity), 0);
        const shipping = subtotal > 0 ? selectedShippingPrice(subtotal) : 0;
        const tax = subtotal * TAX_RATE;
        const discount = (pointsRedeemed / 10) + promoDiscount(subtotal, shipping);
        const codFee = selectedPaymentMethod() === 'cod' ? COD_FEE : 0;
        const total = Math.max(0, subtotal + shipping + tax + codFee - discount);
        return { subtotal, shipping, tax, discount, codFee, total };
    }

    function renderTotals(items) {
        const t = computeTotals(items);
        if (els.orderSubtotal) els.orderSubtotal.textContent = formatMoney(t.subtotal);
        if (els.orderShipping) els.orderShipping.textContent = t.subtotal > 0 ? (t.shipping === 0 ? 'FREE' : formatMoney(t.shipping)) : '0.00 MAD';
        if (els.orderTax) els.orderTax.textContent = formatMoney(t.tax);

        if (els.orderDiscountRow) {
            if (t.discount > 0) {
                els.orderDiscountRow.style.display = 'flex';
                els.orderDiscount.textContent = '-' + formatMoney(t.discount);
            } else {
                els.orderDiscountRow.style.display = 'none';
            }
        }

        // COD fee row
        let codRow = document.getElementById('orderCodFeeRow');
        if (t.codFee > 0) {
            if (!codRow) {
                codRow = document.createElement('div');
                codRow.className = 'total-row';
                codRow.id = 'orderCodFeeRow';
                codRow.innerHTML = `<span>COD Fee</span><span id="orderCodFee">${formatMoney(t.codFee)}</span>`;
                const grandRow = document.querySelector('.total-row.grand-total');
                if (grandRow) grandRow.parentNode.insertBefore(codRow, grandRow);
            } else {
                codRow.style.display = 'flex';
                document.getElementById('orderCodFee').textContent = formatMoney(t.codFee);
            }
        } else if (codRow) {
            codRow.style.display = 'none';
        }

        const codNotice = document.getElementById('codDepositNotice');
        if (codNotice) {
            if (t.total > 8000 && selectedPaymentMethod() === 'cod') {
                codNotice.style.display = 'block';
            } else {
                codNotice.style.display = 'none';
            }
        }

        if (els.orderTotal) els.orderTotal.textContent = formatMoney(t.total);
    }

    let stripeInitTimeout = null;
    function syncCheckout() {
        const items = getCartItems();
        renderItems(items);
        renderUpsells(items);
        renderTotals(items);

        if (window.STRIPE_PUBLISHABLE_KEY && window.STRIPE_PUBLISHABLE_KEY !== '') {
            clearTimeout(stripeInitTimeout);
            stripeInitTimeout = setTimeout(() => {
                initRealStripeElements();
            }, 400);
        }
    }

    // ── Payment Forms ────────────────────────────────────────
    function initPaymentForms() {
        if (!els.paymentMethodInputs.length) return;
        const update = () => {
            const selected = selectedPaymentMethod();
            Object.keys(els.paymentForms).forEach(key => {
                const form = els.paymentForms[key];
                if (form) form.classList.toggle('hidden', key !== selected);
            });
            els.paymentMethodInputs.forEach(input => {
                const label = input.closest('.payment-method-option');
                if (label) label.classList.toggle('active', input.checked);
            });
            syncCheckout(); // recalc for COD fee
            if (selected === 'paypal') initPayPalButtons();
        };
        els.paymentMethodInputs.forEach(input => input.addEventListener('change', update));
        update();
    }

    function initBillingToggle() {
        if (!els.sameAsShipping || !els.billingAddressForm) return;
        const update = () => els.billingAddressForm.classList.toggle('hidden', els.sameAsShipping.checked);
        els.sameAsShipping.addEventListener('change', update);
        update();
    }

    function initAuthSwitch() {
        if (!els.authInputs.length || !els.loginCheckout) return;
        els.authInputs.forEach(input => {
            input.addEventListener('change', () => {
                if (els.loginCheckout.checked) window.location.href = 'login.php?next=checkout.php';
            });
        });
    }

    function initShippingUpdates() {
        const update = () => {
            const method = selectedShippingMethod();
            els.shippingInputs.forEach(input => {
                const label = input.closest('.shipping-option');
                if (label) label.classList.toggle('active', input.checked);
            });
            const mapContainer = document.getElementById('pickupMapContainer');
            if (mapContainer) mapContainer.style.display = method === 'pickup' ? 'block' : 'none';
            if (method !== 'pickup') {
                window.selectedPickupStore = null;
                window.currentPickupVerificationCode = '';
                document.querySelectorAll('.pickup-node.selected').forEach(node => node.classList.remove('selected'));
            }
            syncCheckout();
        };
        els.shippingInputs.forEach(input => input.addEventListener('change', update));
        update();
    }

    function initCryptoUpdates() {
        const inputs = document.querySelectorAll('input[name="crypto"]');
        if (!inputs.length) return;
        const update = () => inputs.forEach(input => {
            const label = input.closest('.crypto-option');
            if (label) label.classList.toggle('active', input.checked);
        });
        inputs.forEach(input => input.addEventListener('change', update));
        update();
    }

    function initCountryStateLogic() {
        [
            { country: document.getElementById('country'), state: document.getElementById('state') },
            { country: document.getElementById('billingCountry'), state: document.getElementById('billingState') }
        ].forEach(pair => {
            if (!pair.country || !pair.state) return;
            const update = () => {
                const label = pair.state.closest('.form-group')?.querySelector('label');
                pair.state.required = pair.country.value === 'US';
                if (label) {
                    label.textContent = pair.state.required
                        ? label.textContent.replace(/ \*$/, '') + ' *'
                        : label.textContent.replace(/ \*$/, '');
                }
            };
            pair.country.addEventListener('change', update);
            update();
        });
    }

    // ── Payment Processing Overlay ───────────────────────────
    function showProcessing(amount) {
        els.processingAmount.textContent = formatMoney(amount);
        els.processingTitle.textContent = 'Processing Payment';
        els.processingSubtitle.textContent = 'Please do not close this window';
        [els.stepVerify, els.stepAuth, els.stepConfirm].forEach(s => {
            s.className = 'processing-step';
        });
        els.stepVerify.classList.add('active');
        els.paymentProcessing.classList.add('active');
    }

    function hideProcessing() {
        els.paymentProcessing.classList.remove('active');
    }

    async function animateStep(stepEl, nextStepEl, delayMs) {
        await sleep(delayMs);
        stepEl.classList.remove('active');
        stepEl.classList.add('done');
        if (nextStepEl) nextStepEl.classList.add('active');
    }

    async function animateFailure(stepEl, reason) {
        await sleep(800);
        stepEl.classList.remove('active');
        stepEl.classList.add('failed');
        els.processingTitle.textContent = 'Payment Failed';
        els.processingSubtitle.textContent = reason;
        els.paymentProcessing.querySelector('.processing-spinner').style.display = 'none';
        els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-times-circle processing-card-icon';
        els.paymentProcessing.querySelector('.processing-card-icon').style.color = 'var(--red)';
        els.paymentProcessing.querySelector('.processing-card-icon').style.animation = 'none';
        await sleep(2500);
        // Reset icon state
        els.paymentProcessing.querySelector('.processing-spinner').style.display = '';
        els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-credit-card processing-card-icon';
        els.paymentProcessing.querySelector('.processing-card-icon').style.color = '';
        els.paymentProcessing.querySelector('.processing-card-icon').style.animation = '';
        hideProcessing();
    }

    // ── Simulated Card Payment ───────────────────────────────
    async function simulateCardPayment(total) {
        const cardNum = document.getElementById('cardNumber')?.value.replace(/\s/g, '') || '';
        const testCard = TEST_CARDS[cardNum];

        showProcessing(total);

        // Step 1: Verify
        await animateStep(els.stepVerify, els.stepAuth, 1200);

        // Check for test decline
        if (testCard?.decline) {
            await animateFailure(els.stepAuth, testCard.reason);
            return { success: false, reason: testCard.reason };
        }

        // Step 2: Authorize
        await animateStep(els.stepAuth, els.stepConfirm, 1500);

        // Step 3: Confirm
        await animateStep(els.stepConfirm, null, 1000);

        els.processingTitle.textContent = 'Payment Approved!';
        els.processingSubtitle.textContent = 'Finalizing your order...';
        els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-check-circle processing-card-icon';
        els.paymentProcessing.querySelector('.processing-card-icon').style.color = 'var(--green)';
        els.paymentProcessing.querySelector('.processing-spinner').style.borderTopColor = 'var(--green)';

        await sleep(800);

        const txnId = generateTxnId();
        hideProcessing();

        // Reset icon
        els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-credit-card processing-card-icon';
        els.paymentProcessing.querySelector('.processing-card-icon').style.color = '';
        els.paymentProcessing.querySelector('.processing-spinner').style.borderTopColor = '';

        return { success: true, transactionId: txnId };
    }

    // ── Simulated Crypto Payment ─────────────────────────────
    async function simulateCryptoPayment(total) {
        return new Promise((resolve) => {
            const modal = document.getElementById('cryptoModal');
            if (!modal) return resolve({ success: true, transactionId: generateTxnId() });

            // Detect selected crypto
            const cryptoSelect = document.querySelector('input[name="crypto"]:checked')?.value || 'btc';
            const tickers = { 'btc': 'BTC', 'eth': 'ETH', 'usdt': 'USDT' };
            const rates = { 'btc': 650000, 'eth': 35000, 'usdt': 10 }; // Approx MAD rates
            const ticker = tickers[cryptoSelect] || 'BTC';
            const rate = rates[cryptoSelect] || rates.btc;
            
            const cryptoTotal = (total / rate).toFixed(6);
            document.getElementById('cryptoAmount').textContent = `${cryptoTotal} ${ticker}`;
            
            // Random mock addresses
            const addrs = {
                'btc': 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
                'eth': '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
                'usdt': 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t' // TRC20 mock
            };
            const addr = addrs[cryptoSelect] || addrs.btc;
            document.getElementById('cryptoAddress').value = addr;
            
            // Update QR
            const qrUri = cryptoSelect === 'btc' ? `bitcoin:${addr}?amount=${cryptoTotal}` : addr;
            document.getElementById('cryptoQrCode').src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrUri)}`;

            modal.classList.add('active');

            const confirmBtn = document.getElementById('confirmCryptoPaymentBtn');
            const closeBtn = document.getElementById('closeCryptoModal');
            const copyBtn = document.getElementById('copyCryptoBtn');

            const handleCopy = () => {
                navigator.clipboard.writeText(addr);
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy"></i>', 2000);
            };

            const handleClose = () => {
                cleanup();
                resolve({ success: false });
            };

            const handleConfirm = async () => {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying network...';
                
                await sleep(2500); // Simulate network check
                
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Payment Detected';
                confirmBtn.style.background = 'var(--green)';
                confirmBtn.style.borderColor = 'var(--green)';
                
                await sleep(1000); // Let user see success
                
                cleanup();
                resolve({ success: true, transactionId: 'CRYPTO-' + generateTxnId() });
            };

            const cleanup = () => {
                modal.classList.remove('active');
                confirmBtn.removeEventListener('click', handleConfirm);
                closeBtn.removeEventListener('click', handleClose);
                copyBtn.removeEventListener('click', handleCopy);
                
                // Reset button state for next time
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'I Have Sent The Payment';
                confirmBtn.style.background = '';
                confirmBtn.style.borderColor = '';
            };

            confirmBtn.addEventListener('click', handleConfirm);
            closeBtn.addEventListener('click', handleClose);
            copyBtn.addEventListener('click', handleCopy);
        });
    }

    // ── Build Address ────────────────────────────────────────
    function buildAddress() {
        if (selectedShippingMethod() === 'pickup') {
            return formatPickupAddress(window.selectedPickupStore);
        }

        const fn = document.getElementById('firstName')?.value.trim() || '';
        const ln = document.getElementById('lastName')?.value.trim() || '';
        const email = document.getElementById('email')?.value.trim() || '';
        const phone = document.getElementById('phone')?.value.trim() || '';
        const addr = document.getElementById('address')?.value.trim() || '';
        const addr2 = document.getElementById('address2')?.value.trim() || '';
        const city = document.getElementById('city')?.value.trim() || '';
        const stateSelect = document.getElementById('state');
        const countrySelect = document.getElementById('country');
        const state = stateSelect?.selectedOptions?.[0]?.textContent.trim() || stateSelect?.value || '';
        const zip = document.getElementById('zip')?.value.trim() || '';
        const country = countrySelect?.selectedOptions?.[0]?.textContent.trim() || countrySelect?.value || '';
        const nameLine = `${fn} ${ln}`.trim();
        const contactLines = [
            nameLine,
            email ? `Email: ${email}` : '',
            phone ? `Phone: ${phone}` : ''
        ].filter(Boolean);
        const addressLines = [addr, addr2, `${city}, ${state} ${zip}`.trim(), country].filter(Boolean);
        return [...contactLines, ...addressLines].join('\n');
    }

    // ── Validate Shipping Form ───────────────────────────────
    function validateShipping() {
        const isPickup = selectedShippingMethod() === 'pickup';
        const required = isPickup
            ? ['firstName', 'lastName', 'email', 'phone']
            : ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'zip'];
        for (const id of required) {
            const el = document.getElementById(id);
            if (!el || !el.value.trim()) {
                el?.focus();
                alert(isPickup ? 'Please fill in your pickup contact details.' : 'Please fill in all required shipping fields.');
                return false;
            }
        }
        if (isPickup && !window.selectedPickupStore) {
            document.getElementById('pickupMapContainer')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            alert('Please select a pickup location before placing your order.');
            return false;
        }
        return true;
    }

    // ── Validate Card ────────────────────────────────────────
    function validateCard() {
        const cardInput = document.getElementById('cardNumber')?.value.replace(/\s/g, '') || '';
        if (!cardInput || !/^\d{13,19}$/.test(cardInput) || !luhnCheck(cardInput)) {
            alert('Invalid credit card number. Please check your details.');
            return false;
        }
        const cvvInput = document.getElementById('cvv')?.value;
        if (!cvvInput || !/^\d{3,4}$/.test(cvvInput)) {
            alert('Invalid CVV.');
            return false;
        }
        const expiryInput = document.getElementById('expiryDate')?.value;
        if (!expiryInput || !/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiryInput)) {
            alert('Invalid expiry date. Use MM/YY format.');
            return false;
        }
        const [month, year] = expiryInput.split('/');
        const expiry = new Date(`20${year}`, parseInt(month) - 1);
        const now = new Date(); now.setDate(1); now.setHours(0, 0, 0, 0);
        if (expiry < now) { alert('Credit card has expired.'); return false; }
        return true;
    }

    // ── Place Order (common backend call) ────────────────────
    async function placeOrder(paymentMethod, transactionId, paypalOrderId) {
        const items = getCartItems();
        const shippingMethod = selectedShippingMethod();
        const pickupStore = shippingMethod === 'pickup' ? window.selectedPickupStore : null;
        const pickupVerificationCode = pickupStore ? getPickupVerificationCode() : '';
        const shippingAddress = buildAddress();
        const billingSame = document.getElementById('sameAsShipping')?.checked;
        const billingAddress = billingSame ? shippingAddress : '';
        const t = computeTotals(items);

        const body = {
            items,
            shippingMethod,
            paymentMethod,
            shippingAddress,
            billingAddress,
            total: parseFloat(t.total.toFixed(2)),
            points_redeemed: pointsRedeemed,
            promo_code: activePromo?.code || '',
            notes: document.getElementById('orderNotes')?.value.trim() || '',
            transaction_id: transactionId || null,
            paypal_order_id: paypalOrderId || null,
            pickup_store: pickupStore,
            pickup_verification_code: pickupVerificationCode,
            // Extra info to save
            save_info: document.getElementById('saveInfo')?.checked || false,
            newsletter: document.getElementById('newsletterSignup')?.checked || false,
            firstName: document.getElementById('firstName')?.value.trim() || '',
            lastName: document.getElementById('lastName')?.value.trim() || '',
            email: document.getElementById('email')?.value.trim() || '',
            phone: document.getElementById('phone')?.value.trim() || '',
            save_card: document.getElementById('saveCard')?.checked || false
        };

        const res = await fetch('api/place-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        });
        return res.json();
    }

    // ── Show Confirmation ────────────────────────────────────
    function showConfirmation(orderId, transactionId, paymentMethod, total, ticketItems = getCartItems(), pickupVerificationCode = '') {
        const shippingMethod = selectedShippingMethod();
        const isPickup = shippingMethod === 'pickup';

        const methodLabels = {
            'credit-card': 'Credit / Debit Card',
            'paypal': 'PayPal',
            'bitcoin': 'Cryptocurrency',
            'apple-pay': 'Apple Pay',
            'google-pay': 'Google Pay',
            'nfc-biometric': 'NFC & Biometrics',
            'cod': 'Cash on Delivery'
        };

        document.getElementById('orderNumber').textContent = '#' + String(orderId).padStart(6, '0');
        document.getElementById('transactionId').textContent = transactionId || '—';
        document.getElementById('confirmPaymentMethod').textContent = methodLabels[paymentMethod] || paymentMethod;
        document.getElementById('confirmAmount').textContent = formatMoney(total);
        const pickupCodeRow = document.getElementById('pickupCodeRow');
        const confirmPickupCode = document.getElementById('confirmPickupCode');
        if (pickupCodeRow && confirmPickupCode) {
            pickupCodeRow.style.display = isPickup ? 'flex' : 'none';
            confirmPickupCode.textContent = isPickup ? pickupVerificationCode : '—';
        }
        const confirmationMessage = document.getElementById('confirmationMessage');
        if (confirmationMessage) {
            confirmationMessage.textContent = isPickup
                ? 'Your pickup order has been placed. Download or print your ticket and bring a valid ID to the selected store.'
                : 'Thank you for your purchase. Your order has been placed successfully.';
        }
        document.getElementById('confirmationModal').classList.add('active');

        const ticketButton = document.getElementById('downloadPickupTicketBtn');
        if (ticketButton) {
            ticketButton.style.display = isPickup ? 'inline-flex' : 'none';
            ticketButton.onclick = isPickup ? () => generatePickupTicket(orderId, total, ticketItems, pickupVerificationCode) : null;
        }

        try { localStorage.removeItem('cart'); } catch (e) {}
        if (typeof Cart !== 'undefined') Cart.items = [];
    }

    function generatePickupTicket(orderId, total, ticketItems = getCartItems(), pickupVerificationCode = '') {
        if (!window.selectedPickupStore) {
            console.warn('No pickup store selected.');
            return;
        }

        const store = window.selectedPickupStore;
        const verifyCode = pickupVerificationCode || getPickupVerificationCode();

        // Populate Template
        document.getElementById('ticketOrderId').textContent = '#' + String(orderId).padStart(6, '0');
        const firstName = document.getElementById('firstName')?.value || 'Customer';
        const lastName = document.getElementById('lastName')?.value || '';
        document.getElementById('ticketCustomerName').textContent = `${firstName} ${lastName}`.trim();
        document.getElementById('ticketTotal').textContent = formatMoney(total);
        document.getElementById('ticketVerifyCode').textContent = verifyCode;
        
        document.getElementById('ticketStoreName').textContent = store.name;
        document.getElementById('ticketStoreAddress').textContent = store.address;
        document.getElementById('ticketStoreHours').textContent = 'Hours: ' + store.hours;
        
        const d = new Date();
        document.getElementById('ticketDate').textContent = d.toLocaleString();

        // Populate Items
        const itemsList = document.getElementById('ticketItemsList');
        itemsList.innerHTML = '';
        ticketItems.forEach(item => {
            const li = document.createElement('li');
            li.style.borderBottom = '1px dashed rgba(255,255,255,0.1)';
            li.style.padding = '8px 0';
            li.style.display = 'flex';
            li.style.justifyContent = 'space-between';
            li.innerHTML = `<span>${item.quantity}x ${item.name}</span> <span style="color:#00f5d4;">${formatMoney(item.price * item.quantity)}</span>`;
            itemsList.appendChild(li);
        });

        // Check if html2pdf is available
        if (typeof html2pdf === 'undefined') {
            console.error('html2pdf library not loaded');
            downloadPickupTicketHtmlFallback(document.getElementById('pickupTicketTemplate'), verifyCode);
            return;
        }

        // Trigger html2pdf with improved settings
        const element = document.getElementById('pickupTicketTemplate');
        if (!element) {
            console.error('Ticket template element not found');
            alert('Unable to generate PDF ticket. Please contact support.');
            return;
        }

        const opt = {
            margin:       [0.5, 0.5, 0.5, 0.5],
            filename:     `MarocPC_Pickup_${verifyCode}.pdf`,
            image:        { type: 'jpeg', quality: 0.95 },
            html2canvas:  { 
                scale: 2, 
                useCORS: true, 
                logging: false,
                backgroundColor: '#0f172a',
                letterRendering: true,
                allowTaint: false
            },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };

        // Clone and prepare element for rendering
        const renderNode = element.cloneNode(true);
        renderNode.id = 'pickupTicketTemplateRender';
        renderNode.style.cssText = `
            display: block !important;
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            z-index: 99999 !important;
            pointer-events: none !important;
            opacity: 1 !important;
            visibility: visible !important;
        `;
        document.body.appendChild(renderNode);

        // Wait for fonts and images to load
        setTimeout(() => {
            html2pdf()
                .set(opt)
                .from(renderNode)
                .save()
                .then(() => {
                    console.log('PDF generated successfully');
                    renderNode.remove();
                })
                .catch((error) => {
                    console.error('PDF generation error:', error);
                    renderNode.remove();
                    downloadPickupTicketHtmlFallback(element, verifyCode);
                });
        }, 500);
    }

    function waitForTicketAssets(element) {
        const images = Array.from(element.querySelectorAll('img'));
        if (!images.length) return Promise.resolve();

        return Promise.all(images.map(img => {
            if (img.complete && img.naturalWidth > 0) return Promise.resolve();
            return new Promise(resolve => {
                img.addEventListener('load', resolve, { once: true });
                img.addEventListener('error', resolve, { once: true });
                setTimeout(resolve, 1200);
            });
        }));
    }

    function downloadPickupTicketHtmlFallback(element, verifyCode) {
        if (!element) {
            alert('Pickup ticket download failed. Please copy the pickup code from this confirmation screen.');
            return;
        }

        const html = `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maroc PC Pickup Ticket - ${verifyCode}</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&family=Space+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: start center;
            background: #0f172a;
            padding: 24px;
            font-family: 'Space Mono', monospace;
        }
        .ticket-actions {
            width: 800px;
            max-width: 100%;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-bottom: 12px;
        }
        button {
            border: 1px solid #00f5d4;
            border-radius: 8px;
            background: #00f5d4;
            color: #0f172a;
            font: 700 14px system-ui, sans-serif;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover {
            background: #00d4b8;
            transform: translateY(-1px);
        }
        button.secondary {
            background: transparent;
            color: #00f5d4;
        }
        button.secondary:hover {
            background: rgba(0, 245, 212, 0.1);
        }
        @media print {
            body { display: block; padding: 0; background: #0f172a; }
            .ticket-actions { display: none !important; }
        }
        @media (max-width: 850px) {
            .ticket-actions { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="ticket-actions">
        <button class="secondary" onclick="window.close()">Close</button>
        <button onclick="window.print()"><i class="fas fa-print"></i> Print or Save as PDF</button>
    </div>
    ${element.outerHTML}
    <script>
        // Auto-print dialog on load (optional)
        // window.addEventListener('load', () => setTimeout(() => window.print(), 500));
    </script>
</body>
</html>`;

        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `MarocPC_Pickup_${verifyCode}.html`;
        a.click();
        URL.revokeObjectURL(url);

        // Also open in new window for immediate printing
        const printWindow = window.open('', '_blank');
        if (printWindow) {
            printWindow.document.write(html);
            printWindow.document.close();
        }
    }
        const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `MarocPC_Pickup_${verifyCode || 'Ticket'}.html`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 3000);
        alert('PDF generation failed, so an HTML pickup ticket was downloaded instead. Open it and use Print or Save as PDF.');
    }

    // ── Init Place Order (Credit Card, COD, Bitcoin, etc.) ──
    function initPlaceOrder() {
        if (!els.shippingForm) return;
        els.shippingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const items = getCartItems();
            if (!items.length) { alert('Your cart is empty.'); return; }

            const terms = document.getElementById('termsAgree');
            if (terms && !terms.checked) { alert('Please agree to the Terms and Conditions.'); return; }
            if (!validateShipping()) return;

            const paymentMethod = selectedPaymentMethod();

            // PayPal is handled by its own SDK buttons, not the form submit
            if (paymentMethod === 'paypal') {
                alert('Please use the PayPal button above to complete your payment.');
                return;
            }

            // COD Deposit validation
            if (paymentMethod === 'cod') {
                const t = computeTotals(items);
                if (t.total > 8000) {
                    const agree = document.getElementById('codDepositAgree');
                    if (agree && !agree.checked) {
                        alert('You must agree to the Security Deposit for high-value Cash on Delivery orders.');
                        return;
                    }
                }
            }

            // Card validation
            if (paymentMethod === 'credit-card') {
                if (!window.STRIPE_PUBLISHABLE_KEY || window.STRIPE_PUBLISHABLE_KEY === '') {
                    if (!validateCard()) return;
                }
            }

            // NFC / Biometric validation
            if (paymentMethod === 'nfc-biometric') {
                if (!nfcAuthorized && !biometricAuthorized) {
                    alert('Please perform a contactless NFC scan or biometric authorization first!');
                    return;
                }
            }

            const t = computeTotals(items);
            const btn = document.getElementById('placeOrderBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                let transactionId = null;

                if (paymentMethod === 'credit-card') {
                    if (window.STRIPE_PUBLISHABLE_KEY && window.STRIPE_PUBLISHABLE_KEY !== '') {
                        const result = await processRealStripePayment(t.total);
                        if (!result.success) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
                            return;
                        }
                        transactionId = result.transactionId;
                    } else {
                        // Simulated card processing
                        const result = await simulateCardPayment(t.total);
                        if (!result.success) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
                            return;
                        }
                        transactionId = result.transactionId;
                    }
                } else if (paymentMethod === 'bitcoin') {
                    // Simulated Crypto processing
                    const result = await simulateCryptoPayment(t.total);
                    if (!result.success) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
                        return;
                    }
                    transactionId = result.transactionId;
                } else if (paymentMethod === 'cod') {
                    transactionId = 'COD-' + Date.now();
                } else if (paymentMethod === 'nfc-biometric') {
                    transactionId = (biometricAuthorized ? 'BIOMETRIC-' : 'NFC-') + generateTxnId();
                } else {
                    // Apple Pay, Google Pay — simulated
                    transactionId = generateTxnId();
                }

                const data = await placeOrder(paymentMethod, transactionId, null);
                if (data.success) {
                    showConfirmation(data.orderId, transactionId, paymentMethod, t.total, items, window.currentPickupVerificationCode || '');
                } else {
                    alert(data.error || 'Order failed. Please try again.');
                }
            } catch (err) {
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
            }
        });
    }

    // ── PayPal SDK ───────────────────────────────────────────
    function initPayPalButtons() {
        if (paypalRendered) return;
        if (typeof paypal === 'undefined') {
            console.warn('PayPal SDK not loaded');
            return;
        }

        const container = document.getElementById('paypal-button-container');
        if (!container) return;

        paypalRendered = true;

        paypal.Buttons({
            style: {
                layout: 'vertical',
                color: 'gold',
                shape: 'rect',
                label: 'paypal',
                height: 45
            },

            createOrder: function(data, actions) {
                const items = getCartItems();
                if (!items.length) {
                    alert('Your cart is empty.');
                    return;
                }
                if (!validateShipping()) return;

                const terms = document.getElementById('termsAgree');
                if (terms && !terms.checked) {
                    alert('Please agree to the Terms and Conditions.');
                    return;
                }

                const t = computeTotals(items);
                // PayPal uses USD — convert MAD to USD approx (1 USD ≈ 10 MAD)
                const usdTotal = (t.total / 10).toFixed(2);

                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            currency_code: 'USD',
                            value: usdTotal
                        },
                        description: 'Maroc PC Order'
                    }]
                });
            },

            onApprove: async function(data, actions) {
                try {
                    const details = await actions.order.capture();
                    const items = getCartItems();
                    const t = computeTotals(items);
                    const txnId = details.id || generateTxnId();

                    const orderData = await placeOrder('paypal', txnId, details.id);

                    if (orderData.success) {
                        showConfirmation(orderData.orderId, txnId, 'paypal', t.total, items, window.currentPickupVerificationCode || '');
                    } else {
                        alert(orderData.error || 'Order failed after PayPal payment.');
                    }
                } catch (err) {
                    console.error('PayPal capture error:', err);
                    alert('PayPal payment failed. Please try again.');
                }
            },

            onCancel: function() {
                // User cancelled PayPal - do nothing
            },

            onError: function(err) {
                console.error('PayPal error:', err);
                alert('PayPal encountered an error. Please try again or use a different payment method.');
            }
        }).render('#paypal-button-container');
    }

    // ── Stripe Apple & Google Pay Simulation ──────────────────
    function initStripeDigitalWallets() {
        const appleBtn = document.getElementById('applePayBtn');
        const googleBtn = document.getElementById('googlePayBtn');

        const processWalletPayment = async (methodName) => {
            const items = getCartItems();
            if (!items.length) { alert('Your cart is empty.'); return; }
            if (!validateShipping()) return;
            const terms = document.getElementById('termsAgree');
            if (terms && !terms.checked) { alert('Please agree to the Terms and Conditions.'); return; }

            const t = computeTotals(items);
            
            // Trigger realistic Apple / Google Pay prompt sheet
            showProcessing(t.total);
            els.processingTitle.textContent = `Connecting ${methodName}...`;
            els.processingSubtitle.textContent = 'Please confirm Touch ID / Face ID / Wallet passcode';
            
            await sleep(1500); // Simulate biometric authorization
            
            els.processingTitle.textContent = 'Stripe Verification';
            els.processingSubtitle.textContent = 'Decrypting payment token securely...';
            els.stepVerify.className = 'processing-step done';
            els.stepAuth.className = 'processing-step active';
            
            await sleep(1500); // Simulate Stripe Gateway authorization
            
            els.stepAuth.className = 'processing-step done';
            els.stepConfirm.className = 'processing-step active';
            
            await sleep(1000); // Confirm order placement
            
            els.stepConfirm.className = 'processing-step done';
            els.processingTitle.textContent = 'Payment Approved!';
            els.processingSubtitle.textContent = 'Saving order to your account...';
            
            // Checkmark animation
            els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-check-circle processing-card-icon';
            els.paymentProcessing.querySelector('.processing-card-icon').style.color = 'var(--green)';
            
            await sleep(1000);
            
            const transactionId = 'STRIPE-' + generateTxnId();
            try {
                const data = await placeOrder(methodName === 'Apple Pay' ? 'apple-pay' : 'google-pay', transactionId, null);
                hideProcessing();
                // Reset card icon classes
                els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-credit-card processing-card-icon';
                els.paymentProcessing.querySelector('.processing-card-icon').style.color = '';
                
                if (data.success) {
                    showConfirmation(data.orderId, transactionId, methodName === 'Apple Pay' ? 'apple-pay' : 'google-pay', t.total, items, window.currentPickupVerificationCode || '');
                } else {
                    alert(data.error || 'Order placement failed. Please try again.');
                }
            } catch (err) {
                hideProcessing();
                alert('Network error placing order.');
            }
        };

        if (appleBtn) {
            appleBtn.addEventListener('click', () => processWalletPayment('Apple Pay'));
        }
        if (googleBtn) {
            googleBtn.addEventListener('click', () => processWalletPayment('Google Pay'));
        }
    }

    // ── Confirmation Actions ─────────────────────────────────
    function initConfirmationActions() {
        const trackBtn = document.getElementById('trackOrderBtn');
        if (trackBtn) {
            trackBtn.addEventListener('click', () => {
                window.location.href = 'account.php?tab=orders';
            });
        }
        const closeBtn = document.getElementById('closeConfirmationBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                document.getElementById('confirmationModal')?.classList.remove('active');
            });
        }
    }

    // ── Real Stripe Payments ──────────────────────────────────
    function getStripeAppearance() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || localStorage.getItem('theme') === 'dark' || !localStorage.getItem('theme');
        return isDark ? {
            theme: 'night',
            variables: {
                colorPrimary: '#00e5c8',
                colorBackground: '#0d1018',
                colorText: '#edf0f7',
                colorDanger: '#ff3d5a',
                fontFamily: 'Syne, system-ui, sans-serif',
                borderRadius: '12px'
            }
        } : {
            theme: 'flat',
            variables: {
                colorPrimary: '#00bfa5',
                colorBackground: '#ffffff',
                colorText: '#0f172a',
                colorDanger: '#ef4444',
                fontFamily: 'Syne, system-ui, sans-serif',
                borderRadius: '12px'
            }
        };
    }

    function playScannerBeep(frequency = 880, duration = 150) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration / 1000);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start();
            oscillator.stop(audioCtx.currentTime + duration / 1000);
        } catch (e) {
            console.warn('Audio Context not allowed or supported by browser:', e);
        }
    }

    function initNfcBiometricSimulation() {
        const biometricBtn = document.getElementById('biometricScanBtn');
        const nfcBtn = document.getElementById('nfcScanBtn');
        const status = document.getElementById('nfcStatusReadout');
        const laser = document.getElementById('biometricLaser');

        if (!biometricBtn || !nfcBtn || !status) return;

        biometricBtn.addEventListener('click', () => {
            if (biometricBtn.classList.contains('waiting') || nfcBtn.classList.contains('waiting')) return;
            
            // Start simulation
            biometricBtn.classList.add('waiting');
            if (laser) laser.classList.add('active');
            status.className = 'nfc-status-readout scanning';
            status.textContent = 'STATUS: SCANNING BIOMETRIC SIGNATURE (FACE ID / TOUCH ID)...';
            
            // Audio click/beep
            playScannerBeep(600, 80);

            setTimeout(() => {
                biometricBtn.classList.remove('waiting');
                if (laser) laser.classList.remove('active');
                
                biometricAuthorized = true;
                nfcAuthorized = false;
                
                status.className = 'nfc-status-readout success-biometric';
                status.innerHTML = 'STATUS: BIOMETRIC AUTHENTICATED! READY TO CHOOSE PLACE ORDER. ✓';
                
                // Immersive success chime!
                playScannerBeep(1200, 180);
                setTimeout(() => playScannerBeep(1800, 250), 100);
            }, 2000);
        });

        nfcBtn.addEventListener('click', () => {
            if (biometricBtn.classList.contains('waiting') || nfcBtn.classList.contains('waiting')) return;
            
            nfcBtn.classList.add('waiting');
            status.className = 'nfc-status-readout scanning';
            status.textContent = 'STATUS: POLLING FOR CONTACTLESS NFC ANTENNA SIGNAL...';
            
            playScannerBeep(600, 80);

            setTimeout(() => {
                nfcBtn.classList.remove('waiting');
                
                nfcAuthorized = true;
                biometricAuthorized = false;
                
                status.className = 'nfc-status-readout success-nfc';
                status.innerHTML = 'STATUS: NFC CONNECTION STABLE! READY TO CHOOSE PLACE ORDER. ✓';
                
                playScannerBeep(988, 180);
                setTimeout(() => playScannerBeep(1318, 250), 100);
            }, 2000);
        });
    }

    function initThemeObserver() {
        const observer = new MutationObserver(() => {
            if (stripeElements) {
                stripeElements.update({ appearance: getStripeAppearance() });
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    async function initRealStripeElements() {
        if (!window.STRIPE_PUBLISHABLE_KEY || typeof Stripe === 'undefined') return;

        const stripeLoading = document.getElementById('stripe-loading');
        const paymentElementDiv = document.getElementById('payment-element');
        if (!paymentElementDiv) return;

        const items = getCartItems();
        if (!items.length) return;

        const subtotal = items.reduce((s, i) => s + (i.price * i.quantity), 0);
        const shippingMethod = document.querySelector('input[name="shipping"]:checked')?.value || 'standard';

        try {
            if (stripePaymentElement) {
                stripePaymentElement.destroy();
                stripePaymentElement = null;
            }

            const res = await fetch('api/create-payment-intent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    items,
                    shippingMethod,
                    points_redeemed: pointsRedeemed,
                    promo_code: activePromo?.code || ''
                })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                console.error('Stripe PaymentIntent creation failed:', data.error);
                if (stripeLoading) stripeLoading.innerHTML = `<span style="color: var(--red); font-size: 0.8rem; text-align: center; display: block;">Error: ${data.error || 'Failed to initialize payment gateway.'}</span>`;
                return;
            }

            stripeClientSecret = data.clientSecret;
            stripeInstance = Stripe(window.STRIPE_PUBLISHABLE_KEY);
            stripeElements = stripeInstance.elements({
                clientSecret: stripeClientSecret,
                appearance: getStripeAppearance()
            });

            stripePaymentElement = stripeElements.create('payment');
            stripePaymentElement.mount('#payment-element');

            stripePaymentElement.on('ready', () => {
                if (stripeLoading) stripeLoading.style.display = 'none';
            });

        } catch (e) {
            console.error('Stripe initialization error:', e);
            if (stripeLoading) stripeLoading.innerHTML = `<span style="color: var(--red); font-size: 0.8rem; text-align: center; display: block;">Stripe is offline. Please try again.</span>`;
        }
    }

    async function processRealStripePayment(total) {
        showProcessing(total);

        // Step 1: Verify
        await animateStep(els.stepVerify, els.stepAuth, 1000);

        try {
            const { error, paymentIntent } = await stripeInstance.confirmPayment({
                elements: stripeElements,
                confirmParams: {
                    return_url: window.location.origin + window.location.pathname + '?stripe_success=1'
                },
                redirect: 'if_required'
            });

            if (error) {
                await animateFailure(els.stepAuth, error.message);
                return { success: false, reason: error.message };
            }

            if (paymentIntent && paymentIntent.status === 'succeeded') {
                // Step 2: Authorize
                await animateStep(els.stepAuth, els.stepConfirm, 1000);

                // Step 3: Confirm
                await animateStep(els.stepConfirm, null, 1000);

                els.processingTitle.textContent = 'Payment Approved!';
                els.processingSubtitle.textContent = 'Finalizing your order...';
                els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-check-circle processing-card-icon';
                els.paymentProcessing.querySelector('.processing-card-icon').style.color = 'var(--green)';
                els.paymentProcessing.querySelector('.processing-spinner').style.borderTopColor = 'var(--green)';

                await sleep(800);
                hideProcessing();

                // Reset icon
                els.paymentProcessing.querySelector('.processing-card-icon').className = 'fas fa-credit-card processing-card-icon';
                els.paymentProcessing.querySelector('.processing-card-icon').style.color = '';
                els.paymentProcessing.querySelector('.processing-spinner').style.borderTopColor = '';

                return { success: true, transactionId: paymentIntent.id };
            } else {
                await animateFailure(els.stepAuth, 'Payment requires verification or was not captured.');
                return { success: false, reason: 'Payment incomplete.' };
            }
        } catch (e) {
            await animateFailure(els.stepAuth, e.message || 'Payment Gateway Communication Error');
            return { success: false, reason: 'Network error.' };
        }
    }
    // ── Map Pickup Logic ─────────────────────────────────────
    function initPickupMap() {
        const nodes = document.querySelectorAll('.pickup-node');
        if (!nodes.length) return;

        const detailsEl = document.getElementById('pickupDetails');
        const locations = {
            'tangier':    { name: 'Tangier Terminal',       address: '32 Rue de la Liberté, Iberia',      hours: '10:00 – 22:00', phone: '+212 539-112233' },
            'rabat':      { name: 'Rabat Showroom',         address: 'Avenue Fal Ould Oumeir, Agdal',     hours: '09:00 – 19:00', phone: '+212 537-654321' },
            'casablanca': { name: 'Casablanca HQ',          address: '123 Boulevard Zerktouni, Maarif',   hours: '09:00 – 20:00', phone: '+212 522-123456' },
            'fes':        { name: 'Fes Terminal',            address: '12 Rue Atlas, Ville Nouvelle',      hours: '09:30 – 19:30', phone: '+212 535-778899' },
            'marrakech':  { name: 'Marrakech Terminal',      address: '45 Avenue Mohammed V, Guéliz',      hours: '10:00 – 21:00', phone: '+212 524-987654' },
            'agadir':     { name: 'Agadir Terminal',         address: 'Av. Hassan II, Talborjt',           hours: '09:30 – 20:30', phone: '+212 528-445566' },
            'oujda':      { name: 'Oujda East Hub',          address: '8 Boulevard Derfoufi, Oujda',       hours: '09:00 – 18:30', phone: '+212 536-223344' },
            'laayoune':   { name: 'Laâyoune South Terminal', address: 'Avenue de la Marche Verte, Laâyoune', hours: '10:00 – 20:00', phone: '+212 528-990011' },
            'dakhla':     { name: 'Dakhla Sahara Point',     address: 'Boulevard Mohammed V, Dakhla',      hours: '10:00 – 19:00', phone: '+212 528-776655' }
        };

        nodes.forEach(node => {
            node.addEventListener('click', () => {
                // Deselect all
                nodes.forEach(n => n.classList.remove('selected'));
                // Select this
                node.classList.add('selected');

                const city = node.dataset.city;
                const loc = locations[city] || locations['casablanca'];
                
                window.selectedPickupStore = loc; // Store globally for PDF ticket
                window.currentPickupVerificationCode = '';

                detailsEl.innerHTML = `
                    <h4 style="margin:0 0 14px; color:var(--cyan); font-family:'Orbitron', sans-serif; font-size:0.95rem; letter-spacing:0.5px;">
                        <i class="fas fa-store"></i> ${loc.name}
                    </h4>
                    <div style="margin-bottom:10px; font-size:0.88rem; color:var(--text); display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-map-marker-alt" style="color:var(--cyan); width:16px; text-align:center;"></i> ${loc.address}
                    </div>
                    <div style="margin-bottom:10px; font-size:0.88rem; color:var(--text); display:flex; align-items:center; gap:10px;">
                        <i class="far fa-clock" style="color:var(--cyan); width:16px; text-align:center;"></i> ${loc.hours}
                    </div>
                    <div style="margin-bottom:16px; font-size:0.88rem; color:var(--text); display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-phone" style="color:var(--cyan); width:16px; text-align:center;"></i> ${loc.phone}
                    </div>
                    <div style="padding:10px 14px; background:rgba(0,245,212,0.06); border:1px solid rgba(0,245,212,0.15); border-radius:8px; font-size:0.82rem; color:var(--muted); margin-bottom:16px;">
                        <i class="fas fa-info-circle" style="color:var(--cyan);"></i> Present your order confirmation code at the counter. Valid ID required.
                    </div>
                    <button type="button" class="button button-primary" style="width:100%; padding:12px; border-radius:8px; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem;"
                        onclick="this.innerHTML='<i class=\\'fas fa-check\\'></i> ${loc.name} Selected'; this.style.background='rgba(0,245,212,0.15)'; this.style.color='var(--cyan)'; this.style.border='1px solid var(--cyan)'; this.disabled=true;">
                        <i class="fas fa-check-circle"></i> Select This Store
                    </button>
                `;
            });
        });
    }

    // ── Init ─────────────────────────────────────────────────
    function init() {
        fetch('auth-status.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(auth => {
                if (!auth?.loggedIn) {
                    window.location.href = 'signup.php?next=checkout.php';
                    return;
                }

                syncCheckout();
                loadLoyalty();
                initLoyalty();
                initPromoCode();
                initShippingUpdates();
                initPaymentForms();
                initBillingToggle();
                initAuthSwitch();
                initPlaceOrder();
                initCardPreview();
                initCryptoUpdates();
                initCountryStateLogic();
                initStripeDigitalWallets();
                initConfirmationActions();
                initNfcBiometricSimulation();
                initThemeObserver();
                initPickupMap();
                initRealStripeElements();
            })
            .catch(() => {
                window.location.href = 'signup.php?next=checkout.php';
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
