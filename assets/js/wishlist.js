const Wishlist = {
    items: new Set(),
    isLoggedIn: false, // We'll infer this from if we can sync

    async init() {
        // Load from local
        let local = [];
        try {
            local = JSON.parse(localStorage.getItem('wishlist') || '[]');
            if (!Array.isArray(local)) local = [];
        } catch (e) {
            local = [];
        }
        this.items = new Set(local.map(Number));

        // Try to sync with server
        try {
            const res = await fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'sync', localWishlist: local.map(Number) })
            });
            const data = await res.json();
            if (data.success && data.wishlist) {
                this.isLoggedIn = true;
                this.items = new Set(data.wishlist.map(Number));
                this.saveLocal();
            }
        } catch (e) {
            console.error('Failed to sync wishlist:', e);
        }

        this.updateBadges();
    },

    saveLocal() {
        localStorage.setItem('wishlist', JSON.stringify([...this.items]));
        this.updateBadges();
    },

    async toggle(productId) {
        productId = parseInt(productId);
        
        if (this.items.has(productId)) {
            this.items.delete(productId);
        } else {
            this.items.add(productId);
        }
        
        // Save optimistically
        this.saveLocal();

        if (this.isLoggedIn) {
            try {
                const res = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    this.items = new Set(data.wishlist.map(Number));
                    this.saveLocal();
                }
            } catch (e) {
                console.error('Toggle wishlist failed:', e);
            }
        }
        
        return this.items.has(productId);
    },

    has(productId) {
        return this.items.has(parseInt(productId));
    },

    updateBadges() {
        // Find all wishlist buttons and update their state based on this.items
        document.querySelectorAll('.product-wishlist').forEach(btn => {
            const id = parseInt(btn.dataset.id);
            if (this.has(id)) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-heart"></i>';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="far fa-heart"></i>';
            }
        });
    },

    async setAlert(productId, targetPrice) {
        if (!this.isLoggedIn) {
            alert('Please login to set price alerts.');
            return false;
        }

        try {
            const res = await fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_alert', product_id: productId, target_price: targetPrice })
            });
            const data = await res.json();
            if (data.success) {
                return true;
            } else {
                alert(data.error || 'Failed to set alert.');
                return false;
            }
        } catch (e) {
            console.error('Set alert failed:', e);
            return false;
        }
    }
};

// Expose globally
window.Wishlist = Wishlist;

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    Wishlist.init();
});
