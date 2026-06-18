(function () {
    'use strict';

    function currentPage() {
        return window.location.pathname.split('/').pop() || 'index.php';
    }

    function initStoreSidebar() {
        const hamburger = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const closeBtn = document.getElementById('sidebarClose');
        const overlay = document.getElementById('sidebarOverlay');

        if (!hamburger || !sidebar || !closeBtn || !overlay || sidebar.dataset.storeSidebarReady === 'true') {
            return;
        }

        sidebar.dataset.storeSidebarReady = 'true';
        let previouslyFocused = null;

        function syncCartBadge() {
            const pageCount = document.getElementById('cartCount');
            const sidebarCount = document.getElementById('sidebarCartCount');
            if (!pageCount || !sidebarCount) return;

            const count = pageCount.textContent.trim() || '0';
            sidebarCount.textContent = count;
            sidebarCount.style.display = parseInt(count, 10) > 0 ? 'flex' : 'none';
        }

        function openSidebar() {
            previouslyFocused = document.activeElement;
            sidebar.classList.add('open');
            overlay.classList.add('active');
            sidebar.setAttribute('aria-hidden', 'false');
            overlay.setAttribute('aria-hidden', 'false');
            hamburger.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            syncCartBadge();

            const firstFocusable = sidebar.querySelector('button, a, input');
            if (firstFocusable) firstFocusable.focus();
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            sidebar.setAttribute('aria-hidden', 'true');
            overlay.setAttribute('aria-hidden', 'true');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }
            previouslyFocused = null;
        }

        function trapFocus(event) {
            if (!sidebar.classList.contains('open') || event.key !== 'Tab') return;

            const focusable = Array.from(
                sidebar.querySelectorAll('a, button, input, [tabindex]:not([tabindex="-1"])')
            ).filter((element) => !element.disabled && element.offsetParent !== null);

            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        hamburger.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('.sidebar-toggle-btn').forEach((button) => {
            button.addEventListener('click', () => {
                const dropdown = button.closest('.sidebar-dropdown');
                if (!dropdown) return;

                const wasOpen = dropdown.classList.contains('open');
                sidebar.querySelectorAll('.sidebar-dropdown.open').forEach((openDropdown) => {
                    if (openDropdown === dropdown) return;
                    openDropdown.classList.remove('open');
                    openDropdown.querySelector('.sidebar-toggle-btn')?.setAttribute('aria-expanded', 'false');
                });

                dropdown.classList.toggle('open', !wasOpen);
                button.setAttribute('aria-expanded', String(!wasOpen));
            });
        });

        sidebar.querySelectorAll('a.sidebar-link, a.sidebar-sublink').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });

        const searchInput = sidebar.querySelector('.sidebar-search input');
        const searchButton = sidebar.querySelector('.sidebar-search button');

        if (searchInput && searchButton && currentPage() !== 'products.php') {
            const executeSearch = () => {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = 'products.php?search=' + encodeURIComponent(query);
                }
            };

            searchButton.addEventListener('click', executeSearch);
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    executeSearch();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
            trapFocus(event);
        });

        const cartCount = document.getElementById('cartCount');
        if (cartCount && typeof MutationObserver !== 'undefined') {
            new MutationObserver(syncCartBadge).observe(cartCount, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        }

        syncCartBadge();
        window.StoreSidebar = { open: openSidebar, close: closeSidebar };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStoreSidebar);
    } else {
        initStoreSidebar();
    }
})();
