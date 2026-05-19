/**
 * flash-sales.js — Flash Sale Frontend System
 * 
 * Fetches active flash sales from the API and:
 * - Updates the deals section countdown with the nearest ending sale
 * - Renders flash sale product cards in the deals section
 * - Adds "FLASH SALE" badges on product cards if they have an active sale
 */
(() => {
    'use strict';

    const FLASH_API = 'api/flash-sales.php';

    function formatMAD(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MAD';
    }

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    /**
     * Animate a flip-card element
     */
    function animateFlip(el) {
        if (!el) return;
        el.classList.add('flip-animate');
        setTimeout(() => el.classList.remove('flip-animate'), 400);
    }

    /**
     * Start a real countdown timer to a specific end date
     */
    function startCountdown(endDate) {
        const dEl = document.getElementById('days');
        const hEl = document.getElementById('hours');
        const mEl = document.getElementById('minutes');
        const sEl = document.getElementById('seconds');

        if (!dEl || !hEl || !mEl || !sEl) return;

        function update() {
            const now = new Date();
            const diff = Math.max(0, endDate - now);

            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);

            if (dEl.textContent !== pad(d)) { dEl.textContent = pad(d); animateFlip(dEl.parentElement); }
            if (hEl.textContent !== pad(h)) { hEl.textContent = pad(h); animateFlip(hEl.parentElement); }
            if (mEl.textContent !== pad(m)) { mEl.textContent = pad(m); animateFlip(mEl.parentElement); }
            if (sEl) { sEl.textContent = pad(s); animateFlip(sEl.parentElement); }

            if (diff <= 0) {
                clearInterval(timer);
                const badge = document.querySelector('.deals-badge');
                if (badge) badge.textContent = 'Sale Ended';
            }
        }

        update();
        const timer = setInterval(update, 1000);
        return timer;
    }

    /**
     * Render flash sale product cards in the deals section
     */
    function renderFlashSaleCards(sales) {
        const dealsSection = document.getElementById('deals');
        if (!dealsSection || sales.length === 0) return;

        // Find or create the flash products container
        let container = document.getElementById('flashSaleProducts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'flashSaleProducts';
            container.className = 'flash-sale-grid';
            // Insert after the timer
            const timer = dealsSection.querySelector('.deals-timer');
            const shopBtn = dealsSection.querySelector('a.btn');
            if (shopBtn) {
                dealsSection.insertBefore(container, shopBtn);
            } else if (timer) {
                timer.after(container);
            } else {
                dealsSection.appendChild(container);
            }
        }

        container.innerHTML = sales.slice(0, 4).map(sale => {
            const remaining = sale.remaining;
            const stockWarning = remaining !== null && remaining <= 10
                ? `<div class="flash-stock-warning"><i class="fas fa-fire"></i> Only ${remaining} left at this price!</div>`
                : '';

            const inStock = parseInt(sale.stock_quantity) > 0;
            const actionBtn = inStock
                ? `<button class="flash-sale-btn" data-product-id="${sale.product_id}" data-price="${sale.sale_price}" data-name="${sale.product_name}" data-image="${sale.product_image}">
                    <i class="fas fa-bolt"></i> Grab Deal
                   </button>`
                : `<button class="flash-sale-btn notify-restock-btn" style="background: var(--page-bg-2); color: var(--text); border-color: var(--border);" data-product-id="${sale.product_id}" data-name="${sale.product_name}">
                    <i class="fas fa-bell"></i> Notify Me
                   </button>`;

            return `
                <div class="flash-sale-card">
                    <div class="flash-sale-img">
                        <img src="${sale.product_image}" alt="${sale.product_name}" loading="lazy">
                        <span class="flash-sale-badge">-${sale.discount_pct}%</span>
                    </div>
                    <div class="flash-sale-info">
                        <h4>${sale.product_name}</h4>
                        <div class="flash-sale-prices">
                            <span class="flash-sale-price">${formatMAD(sale.sale_price)}</span>
                            <span class="flash-sale-old">${formatMAD(sale.original_price)}</span>
                        </div>
                        ${stockWarning}
                        ${actionBtn}
                    </div>
                </div>
            `;
        }).join('');

        // Bind add-to-cart buttons
        container.querySelectorAll('.flash-sale-btn:not(.notify-restock-btn)').forEach(btn => {
            btn.addEventListener('click', () => {
                if (typeof Cart !== 'undefined') {
                    Cart.add({
                        id: parseInt(btn.dataset.productId),
                        name: btn.dataset.name,
                        price: parseFloat(btn.dataset.price),
                        image: btn.dataset.image,
                        quantity: 1
                    });
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    btn.classList.add('added');
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-bolt"></i> Grab Deal';
                        btn.classList.remove('added');
                    }, 2000);
                }
            });
        });

        // Bind notify me buttons
        container.querySelectorAll('.notify-restock-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.productId;
                const name = btn.dataset.name;
                const email = prompt(`Notify me when "${name}" is back in stock:\n\nPlease enter your email address:`);
                if (email && email.trim() !== '') {
                    fetch('api/restock-notify.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: id, email: email.trim() })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast !== 'undefined') showToast(data.message);
                            else alert(data.message);
                        } else {
                            if (typeof showToast !== 'undefined') showToast(data.error || 'Failed to subscribe', 'error');
                            else alert(data.error || 'Failed to subscribe');
                        }
                    })
                    .catch(() => {
                        if (typeof showToast !== 'undefined') showToast('Network error', 'error');
                        else alert('Network error');
                    });
                }
            });
        });
    }

    /**
     * Update the deals section header with the nearest-ending sale info
     */
    function updateDealsHeader(sales) {
        if (sales.length === 0) return;

        const nearestSale = sales[0]; // Already sorted by ends_at ASC
        const maxDiscount = Math.max(...sales.map(s => parseInt(s.discount_pct)));

        const titleEl = document.getElementById('titre');
        if (titleEl) {
            titleEl.textContent = `Up to ${maxDiscount}% Off — Flash Sale!`;
        }

        const badge = document.querySelector('.deals-badge');
        if (badge) {
            badge.innerHTML = `<i class="fas fa-bolt"></i> Flash Sale Live`;
        }

        const badgeLarge = document.querySelector('.deal-badge-large');
        if (badgeLarge) {
            badgeLarge.textContent = `-${maxDiscount}%`;
        }

        // Start real countdown to the nearest ending sale
        const endDate = new Date(nearestSale.ends_at);
        startCountdown(endDate);
    }

    /**
     * Mark product cards on products.html with flash sale badges
     */
    function markProductCards(sales) {
        if (typeof ProductsPage === 'undefined') return;

        // Store flash sales data globally so prod.js can access it
        window._flashSales = {};
        sales.forEach(sale => {
            window._flashSales[sale.product_id] = sale;
        });
    }

    /**
     * Initialize flash sales system
     */
    async function init() {
        try {
            const res = await fetch(FLASH_API);
            const data = await res.json();

            if (!data.success || !data.sales || data.sales.length === 0) {
                // No active flash sales — use the default fake countdown
                return;
            }

            // Stop the fallback timer from index.html
            window._flashSalesLoaded = true;
            if (window._fallbackTimer) {
                clearInterval(window._fallbackTimer);
            }

            updateDealsHeader(data.sales);
            renderFlashSaleCards(data.sales);
            markProductCards(data.sales);

        } catch (e) {
            // API not available — keep the static deals section as-is
            console.debug('Flash sales API not available:', e.message);
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for external use
    window.FlashSales = { init };
})();
