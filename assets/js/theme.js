// theme.js

// Inject global styling for the toggle on pages missing index.css
(function injectThemeStyles() {
    if (document.getElementById('themeToggleStyles')) return;
    const style = document.createElement('style');
    style.id = 'themeToggleStyles';
    style.textContent = `
    .theme-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: var(--card-bg, #1a1a1a);
        border: 1px solid var(--border, #333);
        border-radius: 12px;
        cursor: pointer;
        color: var(--text, inherit);
        font-size: 18px;
        transition: all 0.3s ease;
        flex-shrink: 0;
        z-index: 100;
    }
    .theme-toggle:hover {
        background: var(--cyan, #00f0ff);
        color: #000;
        border-color: var(--cyan, #00f0ff);
    }
    [data-theme="dark"] .theme-toggle { color: #fff; }
    [data-theme="dark"] .theme-toggle .icon-sun  { display: inline-block; }
    [data-theme="dark"] .theme-toggle .icon-moon { display: none; }
    [data-theme="light"] .theme-toggle { color: #000; }
    [data-theme="light"] .theme-toggle .icon-sun  { display: none; }
    [data-theme="light"] .theme-toggle .icon-moon { display: inline-block; }
    `;
    document.head.appendChild(style);
})();

(function initPwaShell() {
    if (!document.querySelector('link[rel="manifest"]')) {
        const manifest = document.createElement('link');
        manifest.rel = 'manifest';
        manifest.href = 'manifest.json';
        document.head.appendChild(manifest);
    }

    let themeMeta = document.querySelector('meta[name="theme-color"]');
    if (!themeMeta) {
        themeMeta = document.createElement('meta');
        themeMeta.name = 'theme-color';
        document.head.appendChild(themeMeta);
    }
    themeMeta.content = '#00f5d4';

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js').catch((error) => {
                console.warn('Service worker registration failed:', error);
            });
        });
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    // ── Session Footprint Sync ─────────────────────────────────────
    if (document.cookie.includes('plant_footprint=1')) {
        localStorage.setItem('has_active_session', '1');
        document.cookie = 'plant_footprint=; Max-Age=0; path=/';
    }

    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            const html = document.documentElement;
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    }
});

window.addEventListener('storage', (e) => {
    if (e.key === 'theme') {
        const theme = e.newValue || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
    }
});

// Global Toast Curation System
function showToast(message, type = 'success') {
    let toast = document.getElementById('toast');
    let toastMsg = document.getElementById('toastMessage');
    
    if (!toast) {
        toast = document.createElement('output');
        toast.id = 'toast';
        toast.className = 'toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        
        toastMsg = document.createElement('span');
        toastMsg.id = 'toastMessage';
        toast.appendChild(toastMsg);
        
        document.body.appendChild(toast);
        
        // Inject toast styles if missing
        if (!document.getElementById('toastGlobalStyles')) {
            const style = document.createElement('style');
            style.id = 'toastGlobalStyles';
            style.textContent = `
                .toast {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    background: rgba(18, 18, 18, 0.95);
                    border: 1px solid var(--border, #333);
                    padding: 14px 24px;
                    border-radius: 12px;
                    color: var(--text, #fff);
                    font-family: 'Space Mono', monospace;
                    font-size: 0.9rem;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
                    transform: translateY(100px);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    pointer-events: none;
                }
                .toast.show {
                    transform: translateY(0);
                    opacity: 1;
                    pointer-events: auto;
                }
                .toast.success {
                    border-color: var(--cyan, #00f5d4);
                    box-shadow: 0 0 20px rgba(0, 245, 212, 0.15);
                }
                .toast.error {
                    border-color: #ff3d5a;
                    box-shadow: 0 0 20px rgba(255, 61, 90, 0.15);
                }
                .toast.info {
                    border-color: var(--orange, #ff6b35);
                    box-shadow: 0 0 20px rgba(255, 107, 53, 0.15);
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Setup FontAwesome Icon
    let icon = toast.querySelector('i');
    if (!icon) {
        icon = document.createElement('i');
        toast.insertBefore(icon, toastMsg);
    }
    
    if (type === 'success') {
        icon.className = 'fas fa-check-circle';
        icon.style.color = 'var(--cyan, #00f5d4)';
    } else if (type === 'error') {
        icon.className = 'fas fa-exclamation-circle';
        icon.style.color = '#ff3d5a';
    } else {
        icon.className = 'fas fa-info-circle';
        icon.style.color = 'var(--orange, #ff6b35)';
    }
    
    // Set text and class to trigger CSS animation
    toast.className = \`toast show \${type}\`;
    toastMsg.textContent = message;
    
    clearTimeout(window.globalToastTimer);
    window.globalToastTimer = setTimeout(() => {
        toast.classList.remove('show');
    }, 3500);
}
window.showToast = showToast;

