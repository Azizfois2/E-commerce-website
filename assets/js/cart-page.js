(function () {
    "use strict";

    //declaration des variables
    const els = {
        cartItemsList: document.getElementById('cartItemsList'),
        emptyCart: document.getElementById('emptyCart'),
        cartContent: document.getElementById('cartContent'),
        cartItemsCount: document.getElementById('cartItemsCount'),
        subtotal: document.getElementById('subtotal'),
        shipping: document.getElementById('shipping'),
        tax: document.getElementById('tax'),
        total: document.getElementById('total'),
        discountRow: document.getElementById('discountRow'),
        discount: document.getElementById('discount'),
        promoCode: document.getElementById('promoCode'),
        applyPromo: document.getElementById('applyPromo'),
        clearCart: document.getElementById('clearCart'),
        checkoutBtn: document.getElementById('checkoutBtn'),
        recentlyViewed: document.getElementById('recentlyViewed'),
        recentlyViewedGrid: document.getElementById('recentlyViewedGrid'),
        cartCount: document.getElementById('cartCount')
    };

    // TTC (hhh)
    const TAX_RATE = 0.0825;

    // Nous sommes des marocains
    function formatMAD(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MAD';
    }

    const FREE_SHIPPING_THRESHOLD = 1000;
    const SHIPPING_COST = 100;

    // Matgoulha li tachi wahed hhh
    let activePromo = null;
    const PROMO_CODES = {
        'SAVE10': { type: 'percent', value: 0.10, label: '10% off' },
        'SAVE20': { type: 'percent', value: 0.20, label: '20% off' },
        'TECH50': { type: 'fixed', value: 50.00, label: '50 MAD off' },
        'FREESHIP': { type: 'shipping', value: 0, label: 'Free shipping' }
    };


    function renderCartItems() {
        if (!els.cartItemsList) return;

        const items = (typeof Cart !== 'undefined' && Cart.items) ? Cart.items : [];

        if (items.length === 0) {
            showEmptyState();
            return;
        }

        showCartContent();
        els.cartItemsList.innerHTML = items.map(item => createCartItemHTML(item)).join('');
        attachItemListeners();
    }

    function createCartItemHTML(item) {
        const itemTotal = (item.price * item.quantity).toFixed(2);
        const image = item.image || 'images/placeholder-product.jpg';

        return `
            <div class="cart-item" data-id="${item.id}">
                <div class="cart-col-product">
                    <img src="${image}" alt="${item.name}" class="cart-item-image" loading="lazy" onerror="this.src='images/products/generic-laptop.png'">
                    <div class="cart-item-details">
                        <h4 class="cart-item-name">${item.name}</h4>
                        <p class="cart-item-category">${item.category || 'Maroc PC'}</p>
                        <p class="cart-item-stock ${item.inStock !== false ? 'in-stock' : 'out-of-stock'}">
                            <i class="fas ${item.inStock !== false ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                            ${item.inStock !== false ? 'In Stock' : 'Out of Stock'}
                        </p>
                    </div>
                </div>
                <div class="cart-col-price">
                    <span class="cart-item-price">${formatMAD(item.price)}</span>
                </div>
                <div class="cart-col-quantity">
                    <div class="quantity-controls">
                        <button class="qty-btn qty-minus" data-id="${item.id}" aria-label="Decrease quantity">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="qty-input" value="${item.quantity}" 
                               min="1" max="99" data-id="${item.id}" aria-label="Quantity">
                        <button class="qty-btn qty-plus" data-id="${item.id}" aria-label="Increase quantity">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="cart-col-total">
                    <span class="cart-item-total">${formatMAD(parseFloat(itemTotal))}</span>
                </div>
                <div class="cart-col-action">
                    <button class="remove-btn" data-id="${item.id}" aria-label="Remove item">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function attachItemListeners() {

        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const item = Cart.items.find(i => i.id === id);
                if (item && item.quantity > 1) {
                    Cart.updateQuantity(id, item.quantity - 1);
                    refreshCart();
                } else if (item && item.quantity === 1) {
                    Cart.remove(id);
                    refreshCart();
                }
            });
        });


        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const item = Cart.items.find(i => i.id === id);
                if (item) {
                    Cart.updateQuantity(id, item.quantity + 1);
                    refreshCart();
                }
            });
        });


        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', () => {
                const id = parseInt(input.dataset.id);
                const value = parseInt(input.value);
                if (value > 0) {
                    Cart.updateQuantity(id, value);
                } else {
                    Cart.remove(id);
                }
                refreshCart();
            });

            input.addEventListener('input', () => {
                if (input.value < 1) input.value = 1;
                if (input.value > 99) input.value = 99;
            });
        });


        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const item = Cart.items.find(i => i.id === id);

                const cartItem = btn.closest('.cart-item');
                if (cartItem) {
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(-20px)';

                    setTimeout(() => {
                        Cart.remove(id);
                        refreshCart();
                        if (item) {
                            Cart.showToast(`${item.name} removed from cart`, 'info');
                        }
                    }, 300);
                } else {
                    Cart.remove(id);
                    refreshCart();
                }
            });
        });
    }


    function showEmptyState() {
        if (els.emptyCart) els.emptyCart.style.display = 'flex';
        if (els.cartContent) els.cartContent.style.display = 'none';
        if (els.recentlyViewed) els.recentlyViewed.style.display = 'block';
        loadRecentlyViewed();
    }

    function showCartContent() {
        if (els.emptyCart) els.emptyCart.style.display = 'none';
        if (els.cartContent) els.cartContent.style.display = 'grid';
        if (els.recentlyViewed) els.recentlyViewed.style.display = 'block';
    }


    function updateTotals() {
        const items = (typeof Cart !== 'undefined' && Cart.items) ? Cart.items : [];
        const count = items.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

        let shipping = 0;
        if (subtotal > 0 && subtotal < FREE_SHIPPING_THRESHOLD) {
            shipping = SHIPPING_COST;
        }
        if (activePromo && activePromo.type === 'shipping') {
            shipping = 0;
        }

        let discount = 0;
        if (activePromo) {
            if (activePromo.type === 'percent') {
                discount = subtotal * activePromo.value;
            } else if (activePromo.type === 'fixed') {
                discount = Math.min(activePromo.value, subtotal);
            }
        }

        const taxableAmount = Math.max(0, subtotal - discount);
        const tax = taxableAmount * TAX_RATE;
        const total = taxableAmount + tax + shipping;


        if (els.cartItemsCount) els.cartItemsCount.textContent = count;
        if (els.subtotal) els.subtotal.textContent = formatMAD(subtotal);

        if (els.shipping) {
            if (shipping === 0 && subtotal > 0) {
                els.shipping.innerHTML = '<span class="free-shipping">FREE</span>';
            } else if (subtotal === 0) {
                els.shipping.textContent = 'Calculated at checkout';
            } else {
                els.shipping.textContent = formatMAD(shipping);
            }
        }

        if (els.tax) els.tax.textContent = formatMAD(tax);
        if (els.total) els.total.textContent = formatMAD(total);

        if (els.discountRow && els.discount) {
            if (discount > 0) {
                els.discountRow.style.display = 'flex';
                els.discount.textContent = `-${formatMAD(discount)}`;
            } else {
                els.discountRow.style.display = 'none';
            }
        }

        if (typeof Cart !== 'undefined' && Cart.updateUI) {
            Cart.updateUI();
        }

        if (els.checkoutBtn) {
            els.checkoutBtn.classList.toggle('disabled', count === 0);
            els.checkoutBtn.style.pointerEvents = count === 0 ? 'none' : 'auto';
            els.checkoutBtn.style.opacity = count === 0 ? '0.5' : '1';
        }

        // ── Installment widget in cart summary ──────────────────
        const installContainer = document.getElementById('cartInstallmentWidget');
        if (installContainer && typeof Installment !== 'undefined' && total > 0) {
            installContainer.innerHTML = Installment.widget(total, 'cartInstallCalc');
            Installment.bind('cartInstallCalc', total);
        } else if (installContainer) {
            installContainer.innerHTML = '';
        }
        
        renderCompleteBuildUpsell(items);
    }

    function renderCompleteBuildUpsell(items) {
        const upsellContainer = document.getElementById('completeBuildUpsell');
        if (!upsellContainer) return;

        // Extract categories currently in the cart
        const cartCategories = new Set(items.map(item => item.category?.toLowerCase()));
        
        // Don't show upsell if cart is empty or has non-pc parts only (like just accessories)
        const coreComponents = ['cpu', 'gpu', 'motherboard', 'ram', 'psu', 'case', 'storage'];
        const hasCore = Array.from(cartCategories).some(cat => coreComponents.includes(cat));
        
        if (!hasCore) {
            upsellContainer.style.display = 'none';
            return;
        }

        let missing = null;
        let message = '';
        let suggestionCat = '';

        // Prioritized logic for "Complete My Build"
        if (cartCategories.has('cpu') && !cartCategories.has('motherboard')) {
            missing = 'motherboard';
            message = 'You have a CPU but no motherboard.';
            suggestionCat = 'motherboard';
        } else if (cartCategories.has('motherboard') && !cartCategories.has('cpu')) {
            missing = 'CPU';
            message = 'You have a motherboard but no processor.';
            suggestionCat = 'cpu';
        } else if ((cartCategories.has('cpu') || cartCategories.has('motherboard')) && !cartCategories.has('ram')) {
            missing = 'RAM';
            message = "Don't forget the memory (RAM) for your system.";
            suggestionCat = 'ram';
        } else if (cartCategories.has('cpu') && cartCategories.has('motherboard') && !cartCategories.has('storage')) {
            missing = 'storage';
            message = 'Your system needs storage to boot.';
            suggestionCat = 'storage';
        } else if (cartCategories.has('cpu') && cartCategories.has('motherboard') && cartCategories.has('ram') && !cartCategories.has('psu')) {
            missing = 'power supply';
            message = 'Power up your components with a reliable PSU.';
            suggestionCat = 'psu';
        } else if (cartCategories.has('cpu') && cartCategories.has('motherboard') && cartCategories.has('ram') && cartCategories.has('psu') && !cartCategories.has('case')) {
            missing = 'case';
            message = 'Give your components a home.';
            suggestionCat = 'case';
        }

        if (missing) {
            // Find a cheap/popular product in the missing category to show a starting price
            let startingPriceStr = '';
            if (typeof products !== 'undefined' && Array.isArray(products)) {
                 const catProducts = products.filter(p => p.category?.toLowerCase() === suggestionCat && p.inStock);
                 if (catProducts.length > 0) {
                     catProducts.sort((a,b) => a.price - b.price);
                     startingPriceStr = ` from <strong>${formatMAD(catProducts[0].price)}</strong>`;
                 }
            }

            upsellContainer.innerHTML = `
                <div class="upsell-banner" style="background: rgba(0, 245, 212, 0.05); border: 1px solid var(--cyan); border-radius: 12px; padding: 16px; margin: 24px 0; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(0, 245, 212, 0.1); color: var(--cyan); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 4px; color: var(--cyan); font-family: var(--font-mono); text-transform: uppercase; font-size: 0.85rem;">Complete My Build</h4>
                            <p style="margin: 0; color: var(--text);">${message} Add a compatible ${missing}${startingPriceStr}.</p>
                        </div>
                    </div>
                    <a href="products.html?category=${suggestionCat}" class="btn btn-outline" style="border-color: var(--cyan); color: var(--cyan); white-space: nowrap;">
                        Browse ${missing.charAt(0).toUpperCase() + missing.slice(1)} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            `;
            upsellContainer.style.display = 'block';
        } else {
            upsellContainer.style.display = 'none';
        }
    }

    function initPromoCode() {
        if (!els.applyPromo || !els.promoCode) return;

        els.applyPromo.addEventListener('click', applyPromo);
        els.promoCode.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') applyPromo();
        });
    }

    function applyPromo() {
        const code = els.promoCode.value.trim().toUpperCase();
        if (!code) {
            Cart.showToast('Please enter a promo code', 'error');
            return;
        }

        if (PROMO_CODES[code]) {
            activePromo = PROMO_CODES[code];
            Cart.showToast(`Promo applied: ${activePromo.label}`, 'success');
            els.promoCode.value = '';
            els.promoCode.placeholder = `Active: ${activePromo.label}`;
            els.promoCode.parentElement.classList.add('promo-active');
        } else {
            Cart.showToast('Invalid promo code', 'error');
            activePromo = null;
            els.promoCode.parentElement.classList.remove('promo-active');
        }
        updateTotals();
    }


    function initClearCart() {
        if (!els.clearCart) return;

        els.clearCart.addEventListener('click', () => {
            if (Cart.items.length === 0) {
                Cart.showToast('Cart is already empty', 'info');
                return;
            }

            if (confirm('Are you sure you want to clear your cart?')) {
                Cart.clear();
                activePromo = null;
                if (els.promoCode) {
                    els.promoCode.placeholder = 'Promo Code';
                    els.promoCode.parentElement.classList.remove('promo-active');
                }
                refreshCart();
                Cart.showToast('Cart cleared', 'info');
            }
        });
    }


    function loadRecentlyViewed() {
        if (!els.recentlyViewedGrid) return;

        try {
            const viewed = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
            if (viewed.length === 0) {
                if (els.recentlyViewed) els.recentlyViewed.style.display = 'none';
                return;
            }

            const cartIds = new Set((Cart.items || []).map(i => i.id));
            const filtered = viewed.filter(p => !cartIds.has(p.id)).slice(0, 4);

            if (filtered.length === 0) {
                if (els.recentlyViewed) els.recentlyViewed.style.display = 'none';
                return;
            }

            els.recentlyViewedGrid.innerHTML = filtered.map(product => `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${product.image || 'images/placeholder.jpg'}" alt="${product.name}" loading="lazy">
                    </div>
                    <div class="product-info">
                        <h3>${product.name}</h3>
                        <p class="product-price">${formatMAD(product.price)}</p>
                        <button class="btn btn-primary add-to-cart-btn" data-id="${product.id}">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.dataset.id);
                    const product = filtered.find(p => p.id === id);
                    if (product && typeof Cart !== 'undefined') {
                        Cart.add(product);
                        refreshCart();
                    }
                });
            });
        } catch (e) {
            console.warn('Could not load recently viewed:', e);
        }
    }

    function refreshCart() {
        renderCartItems();
        updateTotals();
    }


    function initSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.querySelector('.search-btn');

        if (!searchInput) return;

        const performSearch = () => {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `products.html?search=${encodeURIComponent(query)}`;
            }
        };

        if (searchBtn) searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
    }


    function checkPriceLockStatus() {
        const banner = document.getElementById('priceLockBanner');
        const timer = document.getElementById('priceLockTimer');
        if (!banner || !timer) return;

        fetch('api/cart-sync.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.has_locked && data.is_active) {
                    banner.style.display = 'flex';
                    
                    // Sync active locked prices back to cart page UI
                    if (Array.isArray(data.cart)) {
                        let updated = false;
                        data.cart.forEach(li => {
                            const localItem = Cart.items.find(item => item.id === li.id);
                            if (localItem && localItem.price !== li.price) {
                                localItem.price = li.price;
                                updated = true;
                            }
                        });
                        if (updated) {
                            refreshCart();
                        }
                    }

                    let secondsLeft = data.seconds_remaining;
                    const updateLockText = () => {
                        const hrs = Math.floor(secondsLeft / 3600);
                        const mins = Math.floor((secondsLeft % 3600) / 60);
                        const secs = secondsLeft % 60;
                        timer.innerText = `${String(hrs).padStart(2, '0')}h ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
                    };
                    updateLockText();

                    const interval = setInterval(() => {
                        if (secondsLeft <= 0) {
                            clearInterval(interval);
                            banner.style.display = 'none';
                            return;
                        }
                        secondsLeft--;
                        updateLockText();
                    }, 1000);
                } else {
                    banner.style.display = 'none';
                }
            })
            .catch(() => {});
    }


    function init() {
        if (typeof Cart === 'undefined') {
            console.error('Cart module not loaded. Make sure cart.js is loaded before cart-page.js');
            return;
        }

        refreshCart();
        if (els.checkoutBtn) {
            els.checkoutBtn.addEventListener('click', (e) => {

                if (Cart.items.length === 0) {
                    e.preventDefault();
                }
            });
        }
        initPromoCode();
        initClearCart();
        initSearch();
        checkPriceLockStatus();


        window.addEventListener('storage', (e) => {
            if (e.key === 'cart') {
                try {
                    const newItems = JSON.parse(e.newValue || '[]');
                    Cart.items = Array.isArray(newItems) ? newItems : [];
                    refreshCart();
                } catch (err) {
                    console.warn('Failed to sync cart from storage:', err);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.refreshCartPage = refreshCart;
})();
