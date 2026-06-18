const marocPcCartI18n = window.__marocPcI18n || {};
const cartText = (key, fallback) => marocPcCartI18n[key] || fallback;
const cartTemplate = (key, fallback, params = {}) => {
    let value = cartText(key, fallback);
    Object.entries(params).forEach(([name, replacement]) => {
        value = value.replaceAll(`{${name}}`, replacement);
    });
    return value;
};

const Cart = {
    debugLog(payload) {
        // Debug hook intentionally left inert for production pages.
    },
    // Safe localStorage read — won't crash in private mode or on corrupted data
    items: (() => {
        try {
            return JSON.parse(localStorage.getItem('cart') || '[]');
        } catch {
            return [];
        }
    })(),

    save() {
        try {
            localStorage.setItem('cart', JSON.stringify(this.items));
        } catch (e) {
            console.warn('Cart could not be saved to localStorage:', e);
        }
        this.updateUI();

        // Sync with backend to lock price for 48 hours (if logged in)
        fetch('api/cart-sync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart: this.items })
        }).catch(() => {});
    },

    add(product, quantity = 1) {
        if (!product || typeof product.id === 'undefined') {
            this.showToast(cartText('cartCouldNotAdd', 'This product could not be added. Please refresh and try again.'), 'error');
            return false;
        }

        const parsedQuantity = Math.max(1, Number.parseInt(quantity, 10) || 1);
        const inStock = product.inStock ?? product.in_stock ?? true;

        if (inStock === false) {
            this.showToast(cartTemplate('cartNotAvailableTemplate', '{name} is not available yet.', { name: product.name }), 'error');
            return false;
        }

        const cartProduct = { ...product, inStock };

        const existing = this.items.find(item => item.id === cartProduct.id);
        if (existing) {
            existing.quantity += parsedQuantity;
        } else {
            this.items.push({ ...cartProduct, quantity: parsedQuantity });
        }

        this.save();
        this.showToast(cartTemplate('cartAddedTemplate', '{name} added to cart!', { name: cartProduct.name }), 'success');
        return true;
    },

    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
        this.save();
    },

    updateQuantity(id, quantity) {
        const item = this.items.find(item => item.id === id);
        if (!item) return;

        // Treat 0 or below as a remove signal instead of flooring at 1
        if (quantity <= 0) {
            this.remove(id);
        } else {
            item.quantity = quantity;
            this.save();
        }
    },

    // Empty the cart — needed after checkout
    clear() {
        this.items = [];
        this.save();
    },

    getCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    getTotal() {
        return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
    },

    // Formatted total string e.g. "1,234.56 DH" or "1,234.56 درهم"
    // formatMAD is now provided globally by currency.js
    getFormattedTotal() {
        return window.formatMAD ? window.formatMAD(this.getTotal()) : this.getTotal().toFixed(2) + ' DH';
    },

    updateUI() {
        const count = this.getCount();

        const countEl = document.getElementById('cartCount');
        if (countEl) {
            countEl.textContent = count;
            countEl.style.display = count === 0 ? 'none' : '';
            // Bounce animation — requires `transition: transform 0.2s ease` on #cartCount in CSS
            countEl.style.transform = 'scale(1.3)';
            setTimeout(() => (countEl.style.transform = 'scale(1)'), 200);
        }

        const sidebarCountEl = document.getElementById('sidebarCartCount');
        if (sidebarCountEl) {
            sidebarCountEl.textContent = count;
            sidebarCountEl.style.display = count === 0 ? 'none' : '';
        }
    },

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) return;

        // Support success/error styling via CSS class
        toast.className = `toast show ${type}`;
        toastMsg.textContent = message;

        // Clear any existing timer so rapid calls don't stack
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },
};

document.addEventListener('DOMContentLoaded', () => {
    Cart.updateUI();
});

// Expose Cart globally so that products.js and inline handlers can access it
window.Cart = Cart;

function resolveAddToCartButton(event) {
    const direct = event.target?.closest?.('.add-to-cart-btn, .add-to-cart-btn-modal, .detail-add-to-cart');
    if (direct) return direct;

    if (typeof document.elementsFromPoint !== 'function' || typeof event.clientX !== 'number') {
        return null;
    }

    return document
        .elementsFromPoint(event.clientX, event.clientY)
        .map(el => el.closest?.('.add-to-cart-btn, .add-to-cart-btn-modal, .detail-add-to-cart'))
        .find(Boolean) || null;
}

function resolveNotifyRestockButton(event) {
    const selector = '.notify-restock-btn, .detail-notify-restock';
    const direct = event.target?.closest?.(selector);
    if (direct) return direct;

    if (typeof document.elementsFromPoint !== 'function' || typeof event.clientX !== 'number') {
        return null;
    }

    return document
        .elementsFromPoint(event.clientX, event.clientY)
        .map(el => el.closest?.(selector))
        .find(Boolean) || null;
}

function findCartProduct(button) {
    const id = Number.parseInt(button.dataset.id || button.closest('.product-card')?.dataset.id || '', 10);
    if (!Number.isFinite(id)) return null;

    const source = Array.isArray(window.products) ? window.products : [];
    const product = source.find(item => Number(item.id) === id);
    if (product) return product;

    const card = button.closest('.product-card');
    if (!card) return null;

    const priceText = card.querySelector('.product-price')?.textContent || '0';
    const price = Number.parseFloat(priceText.replace(/[^\d.]/g, '')) || 0;

    return {
        id,
        name: card.querySelector('.product-card-name')?.textContent?.trim() || 'Product',
        category: card.dataset.category || '',
        price,
        image: card.querySelector('.product-img')?.getAttribute('src') || '',
        inStock: true
    };
}

function handleGlobalAddToCart(event) {
    if (event.type === 'pointerup' && event.pointerType === 'mouse' && event.button !== 0) return;

    const button = resolveAddToCartButton(event);
    if (!button || button.disabled) return;

    const now = Date.now();
    const lastAction = Number(button.dataset.cartActionAt || 0);
    if (now - lastAction < 700) return;
    button.dataset.cartActionAt = String(now);

    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }

    const product = findCartProduct(button);
    const quantityInput = button.closest('[data-quantity-scope]')?.querySelector('[data-quantity-input]');
    const quantity = quantityInput ? Number.parseInt(quantityInput.value || '1', 10) : 1;
    if (!Cart.add(product, quantity)) return;

    button.classList.add('added');
    button.innerHTML = `<i class="fas fa-check"></i> ${cartText('cartAdded', 'Added!')}`;
    window.setTimeout(() => {
        button.classList.remove('added');
        button.innerHTML = `<i class="fas fa-cart-plus"></i> ${cartText('addToCart', 'Add to Cart')}`;
    }, 1500);
}

function getNotifyProductMeta(button) {
    const card = button.closest('.product-card');
    const id = button.dataset.id || button.dataset.productId || card?.dataset.id || '';
    const name = button.dataset.name || card?.querySelector('.product-card-name')?.textContent?.trim() || 'this product';
    return { id, name };
}

function getRestockToast() {
    injectRestockToastStyles();

    let toast = document.getElementById('restockToast');
    if (toast) return toast;

    toast = document.createElement('form');
    toast.id = 'restockToast';
    toast.className = 'restock-toast';
    toast.noValidate = true;
    toast.innerHTML = `
        <div class="restock-toast-icon"><i class="fas fa-bell"></i></div>
        <div class="restock-toast-body">
            <strong>${cartText('restockTitle', 'Restock signal armed')}</strong>
            <span class="restock-toast-copy">${cartText('restockCopy', 'Drop your email and we will ping you when it returns.')}</span>
            <label class="restock-toast-field">
                <span>${cartText('restockEmailAddress', 'Email address')}</span>
                <input type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
            </label>
        </div>
        <button type="submit" class="restock-toast-submit">${cartText('notifyMe', 'Notify me')}</button>
        <button type="button" class="restock-toast-close" aria-label="${cartText('closeRestock', 'Close restock notification')}"><i class="fas fa-times"></i></button>
    `;
    document.body.appendChild(toast);

    toast.querySelector('.restock-toast-close')?.addEventListener('click', () => {
        toast.classList.remove('show');
    });

    return toast;
}

function injectRestockToastStyles() {
    if (document.getElementById('restock-toast-fallback-styles')) return;

    const style = document.createElement('style');
    style.id = 'restock-toast-fallback-styles';
    style.textContent = `
        .restock-toast{position:fixed;right:18px;bottom:24px;z-index:12000;display:grid;grid-template-columns:auto minmax(0,1fr) auto auto;align-items:center;gap:12px;width:min(560px,calc(100vw - 28px));padding:14px;border:1px solid rgba(0,245,212,.28);border-radius:10px;background:rgba(5,9,14,.94);color:#eef0f4;box-shadow:0 20px 56px rgba(0,0,0,.42),0 0 0 1px rgba(0,245,212,.08),inset 0 1px 0 rgba(255,255,255,.06);transform:translateY(24px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,transform .2s ease,visibility .2s ease}
        .restock-toast.show{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(0)}
        .restock-toast-icon{width:38px;height:38px;display:grid;place-items:center;border:1px solid rgba(0,245,212,.24);border-radius:8px;color:var(--cyan,#00f5d4);background:rgba(0,245,212,.08)}
        .restock-toast-body{min-width:0;display:grid;gap:5px}.restock-toast-body strong{overflow:hidden;color:var(--white,#eef0f4);font:800 .84rem/1.2 Syne,sans-serif;text-overflow:ellipsis;white-space:nowrap}.restock-toast-copy{color:var(--text,#b0b8c8);font-size:.78rem;line-height:1.35}
        .restock-toast-field{display:grid;gap:4px}.restock-toast-field span{color:var(--muted,#5a6170);font:700 .58rem/1 "Space Mono",monospace;letter-spacing:.12em;text-transform:uppercase}.restock-toast-field input{width:100%;height:34px;padding:0 10px;border:1px solid rgba(255,255,255,.12);border-radius:7px;background:rgba(255,255,255,.05);color:var(--white,#eef0f4);font:700 .82rem/1 Syne,sans-serif;outline:none}.restock-toast-field input:focus{border-color:rgba(0,245,212,.55);box-shadow:0 0 0 3px rgba(0,245,212,.1)}
        .restock-toast-submit,.restock-toast-close{height:36px;border-radius:7px;cursor:pointer;font-family:"Space Mono",monospace;font-weight:800}.restock-toast-submit{padding:0 14px;border:1px solid rgba(0,245,212,.6);background:var(--cyan,#00f5d4);color:#02110f;letter-spacing:.06em;text-transform:uppercase}.restock-toast-submit:disabled{cursor:wait;opacity:.7}.restock-toast-close{width:36px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:var(--text,#b0b8c8)}
        [data-theme="light"] .restock-toast{border-color:rgba(0,122,110,.22);background:rgba(248,249,251,.96);color:#182029;box-shadow:0 20px 48px rgba(15,23,42,.16)}[data-theme="light"] .restock-toast-body strong{color:#182029}[data-theme="light"] .restock-toast-field input{border-color:rgba(15,23,42,.12);background:#fff;color:#182029}
        @media(max-width:620px){.restock-toast{left:14px;right:14px;bottom:14px;grid-template-columns:auto minmax(0,1fr) auto}.restock-toast-submit{grid-column:2/4;width:100%}}
    `;
    document.head.appendChild(style);
}

function restockResponseMessage(data, productName) {
    if (data?.code === 'already_subscribed') {
        return cartTemplate('restockAlreadySubscribedTemplate', 'You are already subscribed to restock alerts for {name}.', { name: productName });
    }

    return cartTemplate('restockSetTemplate', 'Restock alert set for {name}.', { name: productName });
}

function restockErrorMessage(data) {
    if (data?.code === 'invalid_restock_request') {
        return cartText('validEmail', 'Enter a valid email address.');
    }
    if (data?.code === 'restock_database_error') {
        return cartText('couldNotSaveRestock', 'Could not save restock alert.');
    }

    return data?.error || data?.message || cartText('couldNotSaveRestock', 'Could not save restock alert.');
}

function openRestockToast(meta) {
    const toast = getRestockToast();
    const input = toast.querySelector('input[name="email"]');
    const title = toast.querySelector('strong');
    const copy = toast.querySelector('.restock-toast-copy');
    const submit = toast.querySelector('.restock-toast-submit');

    toast.dataset.productId = meta.id;
    toast.dataset.productName = meta.name;
    title.textContent = cartTemplate('notifyMeProductTemplate', 'Notify me: {name}', { name: meta.name });
    copy.textContent = cartText('restockAvailableCopy', 'Enter your email and we will send a restock alert as soon as it is available.');
    submit.disabled = false;
    submit.textContent = cartText('notifyMe', 'Notify me');

    toast.onsubmit = async (event) => {
        event.preventDefault();
        const email = input.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Cart.showToast(cartText('validEmail', 'Enter a valid email address.'), 'error');
            input.focus();
            return;
        }

        submit.disabled = true;
        submit.textContent = cartText('saving', 'Saving...');

        try {
            const response = await fetch('api/restock-notify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: toast.dataset.productId,
                    email
                })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                throw new Error(restockErrorMessage(data));
            }

            localStorage.setItem('restock_notify_email', email);
            toast.classList.remove('show');
            Cart.showToast(restockResponseMessage(data, toast.dataset.productName), 'success');
        } catch (error) {
            submit.disabled = false;
            submit.textContent = cartText('notifyMe', 'Notify me');
            Cart.showToast(error.message || cartText('networkError', 'Network error. Please try again.'), 'error');
        }
    };

    input.value = localStorage.getItem('restock_notify_email') || '';
    toast.classList.add('show');
    window.setTimeout(() => input.focus(), 80);
}

function handleGlobalNotifyRestock(event) {
    if (event.type === 'pointerup' && event.pointerType === 'mouse' && event.button !== 0) return;

    const button = resolveNotifyRestockButton(event);
    if (!button || button.disabled) return;

    const now = Date.now();
    const lastAction = Number(button.dataset.notifyActionAt || 0);
    if (now - lastAction < 700) return;
    button.dataset.notifyActionAt = String(now);

    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }

    openRestockToast(getNotifyProductMeta(button));
}

document.addEventListener('pointerup', handleGlobalAddToCart, true);
document.addEventListener('click', handleGlobalAddToCart, true);
document.addEventListener('pointerup', handleGlobalNotifyRestock, true);
document.addEventListener('click', handleGlobalNotifyRestock, true);
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    handleGlobalAddToCart(event);
    handleGlobalNotifyRestock(event);
}, true);
