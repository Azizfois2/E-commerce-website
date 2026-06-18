(() => {
    'use strict';

    const mobileQuery = window.matchMedia('(max-width: 768px)');
    let dock = null;

    function calibrateMobileLogo() {
        const header = document.querySelector('.myDIV');
        const logo = header?.querySelector('.logo');
        const menu = header?.querySelector('.mobile-menu-btn, .hamburger-btn');
        const rightItems = header
            ? Array.from(header.children).filter((child) => {
                if (child === logo || child === menu) return false;
                if (child.matches?.('.nav, .nav-dropdown, .search-box, .nav-spacer, .nav-translate, .custom-translate-container')) return false;
                const style = window.getComputedStyle(child);
                return style.display !== 'none' && style.visibility !== 'hidden' && child.getBoundingClientRect().width > 0;
            })
            : [];

        if (!header || !logo || !menu || !mobileQuery.matches || rightItems.length === 0) {
            header?.style.removeProperty('--mobile-logo-x');
            return;
        }

        const headerBox = header.getBoundingClientRect();
        const menuBox = menu.getBoundingClientRect();
        const firstRight = rightItems.reduce((leftmost, item) => {
            const box = item.getBoundingClientRect();
            return box.left < leftmost.left ? box : leftmost;
        }, rightItems[0].getBoundingClientRect());

        const gapLeft = menuBox.right + 10;
        const gapRight = firstRight.left - 10;
        const logoCenter = gapRight > gapLeft
            ? gapLeft + ((gapRight - gapLeft) / 2)
            : headerBox.left + (headerBox.width / 2);

        header.style.setProperty('--mobile-logo-x', `${logoCenter - headerBox.left}px`);
    }

    function dockLabel(key, fallback) {
        return window.__marocPcI18n?.[key] || fallback;
    }

    function getCartUrl() {
        return document.querySelector('.myDIV .cart-wrapper a[href*="cart"]')?.href || 'cart.php';
    }

    function getAccountTrigger() {
        return document.querySelector('#userNav .cart-icon, #userNav a, #userNav button');
    }

    function syncDockState() {
        if (!dock) return;

        const count = (document.getElementById('cartCount')?.textContent || '0').trim();
        const badge = dock.querySelector('[data-mobile-dock-badge]');
        if (badge) {
            badge.textContent = count;
            badge.hidden = !parseInt(count, 10);
        }

        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        const themeIcon = dock.querySelector('[data-mobile-dock-theme-icon]');
        if (themeIcon) {
            themeIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }

    function openDockSearch() {
        if (window.StoreSidebar?.open) {
            window.StoreSidebar.open();
            requestAnimationFrame(() => {
                const search = document.querySelector('#sidebar .sidebar-search input');
                if (search) search.focus();
            });
            return;
        }

        window.location.href = 'products.php';
    }

    function toggleThemeFromDock() {
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.click();
            return;
        }

        const html = document.documentElement;
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        try {
            localStorage.setItem('theme', next);
        } catch (_) {}
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next } }));
    }

    function buildDock() {
        if (dock || document.querySelector('.mobile-action-dock')) {
            dock = document.querySelector('.mobile-action-dock');
            syncDockState();
            return;
        }

        dock = document.createElement('nav');
        dock.className = 'mobile-action-dock';
        dock.setAttribute('aria-label', dockLabel('mobileDock', 'Mobile quick actions'));
        dock.innerHTML = `
            <button type="button" class="mobile-dock-btn" data-mobile-dock-action="menu" aria-label="${dockLabel('menu', 'Menu')}">
                <i class="fas fa-bars"></i>
            </button>
            <button type="button" class="mobile-dock-btn" data-mobile-dock-action="search" aria-label="${dockLabel('search', 'Search')}">
                <i class="fas fa-magnifying-glass"></i>
            </button>
            <button type="button" class="mobile-dock-btn mobile-dock-btn-primary" data-mobile-dock-action="theme" aria-label="${dockLabel('toggleTheme', 'Toggle theme')}">
                <i class="fas fa-sun" data-mobile-dock-theme-icon></i>
            </button>
            <button type="button" class="mobile-dock-btn" data-mobile-dock-action="account" aria-label="${dockLabel('account', 'Account')}">
                <i class="fas fa-user"></i>
            </button>
            <button type="button" class="mobile-dock-btn" data-mobile-dock-action="cart" aria-label="${dockLabel('cart', 'Cart')}">
                <i class="fas fa-cart-shopping"></i>
                <span class="mobile-dock-badge" data-mobile-dock-badge hidden>0</span>
            </button>
        `;

        dock.addEventListener('click', (event) => {
            const button = event.target.closest('[data-mobile-dock-action]');
            if (!button) return;

            const action = button.getAttribute('data-mobile-dock-action');
            if (action === 'menu') {
                window.StoreSidebar?.open ? window.StoreSidebar.open() : document.getElementById('hamburgerBtn')?.click();
            } else if (action === 'search') {
                openDockSearch();
            } else if (action === 'theme') {
                toggleThemeFromDock();
            } else if (action === 'account') {
                getAccountTrigger()?.click();
            } else if (action === 'cart') {
                window.location.href = getCartUrl();
            }
        });

        document.body.appendChild(dock);
        document.body.classList.add('has-mobile-action-dock');
        syncDockState();
    }

    const schedule = () => requestAnimationFrame(calibrateMobileLogo);

    window.addEventListener('resize', schedule, { passive: true });
    window.addEventListener('orientationchange', schedule, { passive: true });
    document.addEventListener('DOMContentLoaded', schedule);
    window.addEventListener('load', schedule);
    document.addEventListener('themeChanged', syncDockState);

    const observer = new MutationObserver(schedule);
    document.addEventListener('DOMContentLoaded', () => {
        const header = document.querySelector('.myDIV');
        if (header) {
            observer.observe(header, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'hidden', 'aria-expanded'],
            });
        }
        buildDock();

        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            new MutationObserver(syncDockState).observe(cartCount, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        }

        new MutationObserver(syncDockState).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme'],
        });
        schedule();
    });
})();
