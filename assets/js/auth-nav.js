(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        const accountLink = event.target.closest('a[aria-label="Account"]');
        if (!accountLink) return;

        const href = accountLink.getAttribute('href') || '';
        if (href.indexOf('login.php') === -1 && href.indexOf('login.html') === -1) {
            event.stopPropagation();
        }
    }, true);

    function updateNav(auth) {
        const userWrapper = document.querySelector('.cart-wrapper:has(a[aria-label="Account"])');
        if (!userWrapper) return;

        if (auth?.loggedIn) {
            userWrapper.innerHTML = `
                <div class="user-menu-wrapper">
                    <a href="account.php" class="cart-icon" aria-label="Account">
                        <i class="fas fa-user-check"></i>
                    </a>
                    <div class="user-dropdown">
                        <span class="user-name">${auth.user || 'Account'}</span>
                        <a href="account.php"><i class="fas fa-user"></i> My Account</a>
                        <a href="account.php?tab=orders"><i class="fas fa-box"></i> My Orders</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            `;
        } else {
            const link = userWrapper.querySelector('a[aria-label="Account"]');
            if (link) {
                const currentHref = link.getAttribute('href') || 'login.php';
                if (!/^login\.(php|html)$/i.test(currentHref)) {
                    link.href = 'login.php';
                }
                link.innerHTML = '<i class="fas fa-user"></i>';
                link.onclick = (e) => {
                    const modal = document.getElementById('roleModal');
                    if (modal) {
                        e.preventDefault();
                        modal.style.display = 'flex';
                    }
                };
            }
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
