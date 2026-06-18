(() => {
    'use strict';

    const i18n = window.__marocPcI18n || {};
    const urls = i18n.urls || {};
    const text = (key, fallback) => i18n[key] || fallback;
    const url = (key, fallback) => urls[key] || fallback;
    const mobileAccountQuery = window.matchMedia('(max-width: 760px), (pointer: coarse)');

    function openRoleModal() {
        const modal = document.getElementById('roleModal');
        if (!modal) return false;
        modal.style.display = 'flex';
        return true;
    }

    function closeGuestMenus(exceptWrapper = null) {
        document.querySelectorAll('.account-access-wrapper.is-open').forEach((wrapper) => {
            if (wrapper === exceptWrapper) return;
            wrapper.classList.remove('is-open');
            const trigger = wrapper.querySelector('.account-access-trigger');
            const menu = wrapper.querySelector('.account-access-menu');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
            if (menu) menu.hidden = true;
        });
    }

    function toggleGuestMenu(wrapper, forceOpen = null) {
        const trigger = wrapper.querySelector('.account-access-trigger');
        const menu = wrapper.querySelector('.account-access-menu');
        if (!trigger || !menu) return;

        const shouldOpen = forceOpen ?? !wrapper.classList.contains('is-open');
        closeGuestMenus(wrapper);
        wrapper.classList.toggle('is-open', shouldOpen);
        trigger.setAttribute('aria-expanded', String(shouldOpen));
        menu.hidden = !shouldOpen;
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.account-access-trigger');
        if (trigger) {
            const wrapper = trigger.closest('.account-access-wrapper');
            if (!wrapper) return;

            event.preventDefault();
            event.stopPropagation();

            if (mobileAccountQuery.matches && openRoleModal()) {
                closeGuestMenus();
                return;
            }

            toggleGuestMenu(wrapper);
            return;
        }

        const expandedButton = event.target.closest('[data-open-role-modal]');
        if (expandedButton) {
            event.preventDefault();
            closeGuestMenus();
            openRoleModal();
            return;
        }

        if (!event.target.closest('.account-access-wrapper')) {
            closeGuestMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeGuestMenus();
    });

    function renderGuestAccess(userWrapper) {
        userWrapper.innerHTML = `
            <div class="user-menu-wrapper account-access-wrapper">
                <button type="button" class="cart-icon account-access-trigger" aria-label="${text('account', 'Account')}" aria-haspopup="menu" aria-expanded="false" aria-controls="accountAccessMenu">
                    <i class="fas fa-user"></i>
                </button>
                <div class="user-dropdown account-access-menu" id="accountAccessMenu" role="menu" hidden>
                    <span class="user-name">${text('accountAccess', 'Account access')}</span>
                    <a href="${url('login', 'login.php')}" role="menuitem">
                        <i class="fas fa-user"></i>
                        <span><strong>${text('customerLogin', 'Customer login')}</strong><small>${text('customerLoginHint', 'Orders, wishlist, purchases')}</small></span>
                    </a>
                    <a href="${url('signup', 'signup.php')}" role="menuitem">
                        <i class="fas fa-user-plus"></i>
                        <span><strong>${text('createAccount', 'Create account')}</strong><small>${text('createAccountHint', 'New Maroc PC profile')}</small></span>
                    </a>
                    <span class="account-access-divider" aria-hidden="true"></span>
                    <a href="${url('adminLogin', 'adminlogin.php')}" role="menuitem" class="admin-access-link">
                        <i class="fas fa-shield-alt"></i>
                        <span><strong>${text('adminAccess', 'Admin access')}</strong><small>${text('adminAccessHint', 'Inventory and order tools')}</small></span>
                    </a>
                    <button type="button" class="account-access-expanded" data-open-role-modal role="menuitem">
                        <i class="fas fa-layer-group"></i>
                        <span>${text('moreSignInOptions', 'More sign-in options')}</span>
                    </button>
                </div>
            </div>
        `;
    }

    function updateNav(auth) {
        const userWrapper = document.getElementById('userNav') || document.querySelector('.cart-wrapper:has(a[aria-label="Account"])');
        if (!userWrapper) return;

        if (auth?.loggedIn) {
            userWrapper.innerHTML = `
                <div class="user-menu-wrapper">
                    <a href="${url('account', 'account.php')}" class="cart-icon" aria-label="${text('account', 'Account')}">
                        <i class="fas fa-user-check"></i>
                    </a>
                    <div class="user-dropdown">
                        <span class="user-name">${auth.user || text('account', 'Account')}</span>
                        <a href="${url('account', 'account.php')}"><i class="fas fa-user"></i> ${text('myAccount', 'My Account')}</a>
                        <a href="${url('orders', 'account.php?tab=orders')}"><i class="fas fa-box"></i> ${text('myOrders', 'My Orders')}</a>
                        <a href="${url('logout', 'logout.php')}"><i class="fas fa-sign-out-alt"></i> ${text('logout', 'Logout')}</a>
                    </div>
                </div>
            `;
        } else {
            renderGuestAccess(userWrapper);
        }
    }

    function armSessionExitLogout(auth) {
        if (!auth?.loggedIn || auth?.rememberMe || window.__marocPcExitLogoutArmed) return;
        window.__marocPcExitLogoutArmed = true;

        let keepSession = false;
        const sameOrigin = (url) => {
            try {
                const target = new URL(url, window.location.href);
                return target.origin === window.location.origin;
            } catch (_) {
                return false;
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (link && sameOrigin(link.href)) keepSession = true;
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            keepSession = sameOrigin(form.action || window.location.href);
        }, true);

        document.addEventListener('keydown', (event) => {
            const key = event.key.toLowerCase();
            if (key === 'f5' || ((event.ctrlKey || event.metaKey) && key === 'r')) {
                keepSession = true;
            }
        }, true);

        if ('navigation' in window) {
            window.navigation.addEventListener('navigate', (event) => {
                if (event.navigationType === 'reload') keepSession = true;
            });
        }

        window.addEventListener('pagehide', (event) => {
            if (event.persisted || keepSession) return;

            try { localStorage.removeItem('wishlist'); } catch (_) {}
            const body = new Blob(['exit=1'], { type: 'application/x-www-form-urlencoded' });
            if (navigator.sendBeacon) {
                navigator.sendBeacon('api/end-session.php', body);
                return;
            }

            fetch('api/end-session.php', {
                method: 'POST',
                body,
                credentials: 'same-origin',
                keepalive: true,
            }).catch(() => {});
        });
    }

    fetch('auth-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(auth => {
            updateNav(auth);
            armSessionExitLogout(auth);
        })
        .catch(() => {});
})();
