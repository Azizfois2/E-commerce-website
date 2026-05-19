const Cart = {
    debugLog(payload) {
        const endpoint = 'http://127.0.0.1:7242/ingest/3ef74137-7336-41af-9a23-1526acbc2e88';
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).catch(() => {
            fetch(endpoint, {
                method: 'POST',
                mode: 'no-cors',
                body: JSON.stringify(payload)
            }).catch(() => {});
        });
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
            // #region agent log
            this.debugLog({runId:'pre-fix',hypothesisId:'H1',location:'js/cart.js:save',message:'Cart persisted to localStorage',data:{key:'cart',itemCount:this.items.length,totalQty:this.items.reduce((s,i)=>s+i.quantity,0)},timestamp:Date.now()});
            // #endregion
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

    add(product) {
        // #region agent log
        this.debugLog({runId:'pre-fix',hypothesisId:'H5',location:'js/cart.js:add',message:'Attempting to add product',data:{id:product?.id,name:product?.name,inStock:product?.inStock},timestamp:Date.now()});
        // #endregion
        // Guard: block out-of-stock items
        if (!product.inStock) {
            this.showToast(`${product.name} is not available yet.`, 'error');
            return;
        }

        const existing = this.items.find(item => item.id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            this.items.push({ ...product, quantity: 1 });
        }

        this.save();
        this.showToast(`${product.name} added to cart!`, 'success');
    },

    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
        this.save();
    },

    updateQuantity(id, quantity) {
        // #region agent log
        this.debugLog({runId:'pre-fix',hypothesisId:'H5',location:'js/cart.js:updateQuantity',message:'Quantity update requested',data:{id,requestedQuantity:quantity},timestamp:Date.now()});
        // #endregion
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

    // Formatted total string e.g. "$1,234.56"
    getFormattedTotal() {
        return this.getTotal().toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    },

    updateUI() {
        const countEl = document.getElementById('cartCount');
        if (!countEl) return;

        const count = this.getCount();
        countEl.textContent = count;
        // #region agent log
        this.debugLog({runId:'pre-fix',hypothesisId:'H1',location:'js/cart.js:updateUI',message:'Cart badge updated',data:{count,path:window.location.pathname},timestamp:Date.now()});
        // #endregion

        // Hide badge when empty, show when not
        countEl.style.display = count === 0 ? 'none' : '';

        // Bounce animation — requires `transition: transform 0.2s ease` on #cartCount in CSS
        countEl.style.transform = 'scale(1.3)';
        setTimeout(() => (countEl.style.transform = 'scale(1)'), 200);
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