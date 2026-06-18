const Wishlist = {
    items: new Set(),
    isLoggedIn: false,
    loginUrl() {
        return `login.php?next=${encodeURIComponent(window.location.pathname + window.location.search)}`;
    },

    async init() {
        this.items = new Set();
        try { localStorage.removeItem('wishlist'); } catch (e) {}

        try {
            const res = await fetch('api/wishlist.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (res.ok && data.success && data.wishlist) {
                this.isLoggedIn = true;
                this.items = new Set(data.wishlist.map(Number));
            } else {
                this.isLoggedIn = false;
                this.items = new Set();
            }
        } catch (e) {
            this.isLoggedIn = false;
            this.items = new Set();
            console.error('Failed to load wishlist:', e);
        }

        this.updateBadges();
    },

    render() {
        this.updateBadges();
    },

    async toggle(productId) {
        productId = parseInt(productId);

        if (!this.isLoggedIn) {
            window.location.href = this.loginUrl();
            return null;
        }

        const wasActive = this.items.has(productId);
        if (wasActive) this.items.delete(productId);
        else this.items.add(productId);
        this.render();

        try {
            const res = await fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'toggle', product_id: productId })
            });
            const data = await res.json();
            if (res.status === 401) {
                this.isLoggedIn = false;
                this.items = new Set();
                this.render();
                window.location.href = this.loginUrl();
                return null;
            }
            if (data.success) {
                this.items = new Set(data.wishlist.map(Number));
                this.render();
            } else {
                if (wasActive) this.items.add(productId);
                else this.items.delete(productId);
                this.render();
            }
        } catch (e) {
            if (wasActive) this.items.add(productId);
            else this.items.delete(productId);
            this.render();
            console.error('Toggle wishlist failed:', e);
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
            btn.classList.toggle('requires-login', !this.isLoggedIn);
            btn.title = this.isLoggedIn ? '' : 'Login to use wishlist';
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
            window.location.href = this.loginUrl();
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
