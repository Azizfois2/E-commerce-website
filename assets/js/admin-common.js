// Admin Common Features: Command Palette, Keyboard Shortcuts, Lazy Chart.js, Auto-refresh
(function() {
    'use strict';

    function armAdminSessionExitExpiry() {
        if (window.__marocPcAdminExitExpiryArmed) return;
        window.__marocPcAdminExitExpiryArmed = true;

        let keepSession = false;
        const sameOrigin = (url) => {
            try {
                return new URL(url, window.location.href).origin === window.location.origin;
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
            if (form instanceof HTMLFormElement) {
                keepSession = sameOrigin(form.action || window.location.href);
            }
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

            const body = new Blob(['admin=1'], { type: 'application/x-www-form-urlencoded' });
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

    armAdminSessionExitExpiry();

    // ═══════════════════════════════════════════
    // 1. COMMAND PALETTE
    // ═══════════════════════════════════════════
    const adminRoutes = [
        { key: 'dashboard', label: 'Dashboard', url: 'dashboard.php', icon: 'fa-shield-halved' },
        { key: 'products', label: 'Components', url: 'admin-products.php', icon: 'fa-box' },
        { key: 'laptops', label: 'Laptops', url: 'admin-laptops.php', icon: 'fa-laptop' },
        { key: 'stock', label: 'Stock', url: 'admin-stock.php', icon: 'fa-chart-simple' },
        { key: 'procurement', label: 'Procurement', url: 'admin-procurement.php', icon: 'fa-truck-ramp-box' },
        { key: 'orders', label: 'Orders', url: 'admin-orders.php', icon: 'fa-receipt' },
        { key: 'diagnostics', label: 'Diagnostics', url: 'admin-diagnostics.php', icon: 'fa-screwdriver-wrench' },
        { key: 'customers', label: 'Customers', url: 'admin-customers.php', icon: 'fa-users' },
        { key: 'feedback', label: 'Feedback', url: 'admin-feedback.php', icon: 'fa-comment-dots' },
        { key: 'analytics', label: 'Analytics', url: 'admin-analytics.php', icon: 'fa-chart-pie' },
        { key: 'visitors', label: 'Visitors', url: 'admin-visitors.php', icon: 'fa-eye' },
        { key: 'marketing', label: 'Marketing', url: 'admin-marketing.php', icon: 'fa-bullhorn' },
        { key: 'coupons', label: 'Coupons', url: 'admin-coupons.php', icon: 'fa-ticket' },
        { key: 'reviews', label: 'Reviews', url: 'admin-reviews.php', icon: 'fa-star' },
        { key: 'requests', label: 'Requests', url: 'admin-requests.php', icon: 'fa-inbox' },
        { key: 'chatbot', label: 'Chatbot Logs', url: 'admin-chatbot-feedback.php', icon: 'fa-robot' },
        { key: 'store', label: 'Storefront', url: 'index.php', icon: 'fa-store' },
    ];

    function buildCommandPalette() {
        if (document.getElementById('adminCommandPalette')) return;

        const overlay = document.createElement('div');
        overlay.id = 'adminCommandPalette';
        overlay.className = 'cmd-palette-overlay';
        overlay.innerHTML = `
            <div class="cmd-palette-modal">
                <div class="cmd-palette-input-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search pages... (e.g. orders, stock)" autocomplete="off">
                </div>
                <ul class="cmd-palette-list"></ul>
                <div class="cmd-palette-footer">
                    <span><kbd>Enter</kbd> to open</span>
                    <span><kbd>Esc</kbd> to close</span>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        const input = overlay.querySelector('input');
        const list = overlay.querySelector('.cmd-palette-list');

        function renderItems(filter = '') {
            const term = filter.toLowerCase().trim();
            const items = term
                ? adminRoutes.filter(r => r.label.toLowerCase().includes(term) || r.key.includes(term))
                : adminRoutes;
            list.innerHTML = items.map((r, i) => `
                <li class="cmd-palette-item ${i === 0 ? 'is-active' : ''}" data-url="${r.url}">
                    <i class="fas ${r.icon}"></i>
                    <span>${highlightMatch(r.label, term)}</span>
                    <kbd class="cmd-palette-shortcut">${i < 9 ? 'Ctrl+' + (i + 1) : ''}</kbd>
                </li>
            `).join('');
            if (items.length === 0) {
                list.innerHTML = '<li class="cmd-palette-empty">No pages found</li>';
            }
        }

        function highlightMatch(text, term) {
            if (!term) return text;
            const idx = text.toLowerCase().indexOf(term);
            if (idx === -1) return text;
            return text.slice(0, idx) + '<mark>' + text.slice(idx, idx + term.length) + '</mark>' + text.slice(idx + term.length);
        }

        function openActive() {
            const active = list.querySelector('.is-active');
            if (active) window.location.href = active.dataset.url;
        }

        input.addEventListener('input', () => renderItems(input.value));

        input.addEventListener('keydown', (e) => {
            const items = list.querySelectorAll('.cmd-palette-item');
            const active = list.querySelector('.is-active');
            let idx = Array.from(items).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length === 0) return;
                items[idx]?.classList.remove('is-active');
                idx = (idx + 1) % items.length;
                items[idx].classList.add('is-active');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length === 0) return;
                items[idx]?.classList.remove('is-active');
                idx = (idx - 1 + items.length) % items.length;
                items[idx].classList.add('is-active');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                openActive();
            }
        });

        list.addEventListener('click', (e) => {
            const item = e.target.closest('.cmd-palette-item');
            if (item) window.location.href = item.dataset.url;
        });

        list.addEventListener('mouseover', (e) => {
            const item = e.target.closest('.cmd-palette-item');
            if (item) {
                list.querySelectorAll('.is-active').forEach(el => el.classList.remove('is-active'));
                item.classList.add('is-active');
            }
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) hidePalette();
        });

        renderItems();
    }

    function showPalette() {
        buildCommandPalette();
        const overlay = document.getElementById('adminCommandPalette');
        overlay.classList.add('is-visible');
        const input = overlay.querySelector('input');
        input.value = '';
        input.focus();
        overlay.querySelector('.cmd-palette-list').querySelectorAll('.cmd-palette-item').forEach((el, i) => {
            el.classList.toggle('is-active', i === 0);
        });
    }

    function hidePalette() {
        const overlay = document.getElementById('adminCommandPalette');
        if (overlay) overlay.classList.remove('is-visible');
    }

    document.addEventListener('keydown', (e) => {
        const isK = e.key === 'k' || e.key === 'K';
        if ((e.metaKey || e.ctrlKey) && isK) {
            e.preventDefault();
            const overlay = document.getElementById('adminCommandPalette');
            if (overlay && overlay.classList.contains('is-visible')) hidePalette();
            else showPalette();
        }
        if (e.key === 'Escape') hidePalette();
    });

    // ═══════════════════════════════════════════
    // 2. KEYBOARD SHORTCUTS MODAL
    // ═══════════════════════════════════════════
    function getShortcuts() {
        const i18nShortcuts = window.adminI18n?.shortcuts || {};
        return [
            { keys: ['Ctrl', 'K'], desc: i18nShortcuts.open_command_palette || 'Open command palette' },
            { keys: ['Shift', '?'], desc: i18nShortcuts.show_keyboard_shortcuts || 'Show keyboard shortcuts' },
            { keys: ['Ctrl', '1'], desc: i18nShortcuts.go_to_dashboard || 'Go to Dashboard' },
            { keys: ['Ctrl', '2'], desc: i18nShortcuts.go_to_orders || 'Go to Orders' },
            { keys: ['Ctrl', '3'], desc: i18nShortcuts.go_to_stock || 'Go to Stock' },
            { keys: ['Ctrl', '4'], desc: i18nShortcuts.go_to_customers || 'Go to Customers' },
            { keys: ['Ctrl', '5'], desc: i18nShortcuts.go_to_analytics || 'Go to Analytics' },
            { keys: ['Ctrl', 'D'], desc: i18nShortcuts.toggle_dark_light || 'Toggle dark / light mode' },
        ];
    }

    function buildShortcutsModal() {
        if (document.getElementById('adminShortcutsModal')) return;

        const i18nShortcuts = window.adminI18n?.shortcuts || {};
        const shortcuts = getShortcuts();
        
        const modal = document.createElement('div');
        modal.id = 'adminShortcutsModal';
        modal.className = 'shortcuts-modal-overlay';
        modal.innerHTML = `
            <div class="shortcuts-modal">
                <div class="shortcuts-header">
                    <h3>${i18nShortcuts.keyboard_shortcuts || 'Keyboard Shortcuts'}</h3>
                    <button class="shortcuts-close" aria-label="${i18nShortcuts.close || 'Close'}"><i class="fas fa-times"></i></button>
                </div>
                <div class="shortcuts-body">
                    ${shortcuts.map(s => `
                        <div class="shortcut-row">
                            <div class="shortcut-keys">${s.keys.map(k => `<kbd>${k}</kbd>`).join(' + ')}</div>
                            <div class="shortcut-desc">${s.desc}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('.shortcuts-close').addEventListener('click', hideShortcuts);
        modal.addEventListener('click', (e) => { if (e.target === modal) hideShortcuts(); });
    }

    function showShortcuts() {
        buildShortcutsModal();
        document.getElementById('adminShortcutsModal').classList.add('is-visible');
    }

    function hideShortcuts() {
        const modal = document.getElementById('adminShortcutsModal');
        if (modal) modal.classList.remove('is-visible');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === '?' && e.shiftKey && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const active = document.activeElement;
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
            e.preventDefault();
            showShortcuts();
        }
        if (e.key === 'Escape') hideShortcuts();

        // Quick nav shortcuts (Ctrl+1..5)
        if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey) {
            const num = parseInt(e.key, 10);
            if (num >= 1 && num <= 5) {
                e.preventDefault();
                const map = ['dashboard.php', 'admin-orders.php', 'admin-stock.php', 'admin-customers.php', 'admin-analytics.php'];
                window.location.href = map[num - 1];
            }
        }
    });

    // ═══════════════════════════════════════════
    // 3. LAZY LOAD CHART.JS
    // ═══════════════════════════════════════════
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    window.loadChartJs = function() {
        return loadScript('https://cdn.jsdelivr.net/npm/chart.js');
    };

    // Auto-load Chart.js if chart canvases exist
    if (document.querySelector('canvas[id$="Chart"]')) {
        window.loadChartJs();
    }

    function warmAdminPage(url) {
        if (!url || warmAdminPage.seen.has(url)) return;
        if (navigator.connection?.saveData) return;
        warmAdminPage.seen.add(url);

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        document.head.appendChild(link);
    }

    warmAdminPage.seen = new Set();

    document.addEventListener('mouseover', (event) => {
        const link = event.target.closest('a[href$=".php"]');
        if (!link || !link.href.startsWith(window.location.origin)) return;
        const path = new URL(link.href).pathname.split('/').pop();
        if (path && (path.startsWith('admin-') || path === 'dashboard.php')) {
            warmAdminPage(link.href);
        }
    }, { passive: true });

    // ═══════════════════════════════════════════
    // 4. AUTO-REFRESH DASHBOARD STATS (60s)
    // ═══════════════════════════════════════════
    if (document.querySelector('.dashboard-shell')) {
        setInterval(() => {
            fetch('api/admin-stats.php', { cache: 'no-store' })
                .then(r => {
                    if (r.status === 401) {
                        window.location.href = 'adminlogin.php?session_expired=1';
                        return null;
                    }
                    return r.ok ? r.json() : null;
                })
                .then(data => {
                    if (!data) return;
                    // Update sidebar badge
                    const badge = document.querySelector('.sidebar-badge');
                    if (badge && data.pending_orders !== undefined) {
                        badge.textContent = data.pending_orders;
                        badge.style.display = data.pending_orders > 0 ? '' : 'none';
                    }
                    // Dispatch event for dashboard-specific handlers
                    document.dispatchEvent(new CustomEvent('adminStatsRefreshed', { detail: data }));
                })
                .catch(() => {});
        }, 60000);
    }
})();
