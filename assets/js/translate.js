const languages = [
    { code: 'en', label: 'EN', name: 'English' },
    { code: 'fr', label: 'FR', name: 'Français' },
    { code: 'ar', label: 'AR', name: 'العربية' },
    { code: 'es', label: 'ES', name: 'Español' }
];

function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,fr,ar,es',
        autoDisplay: false
    }, 'google_translate_element');
}

(function () {
    var gtScript = document.createElement('script');
    gtScript.type = 'text/javascript';
    gtScript.async = true;
    gtScript.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    var s = document.getElementsByTagName('script')[0];
    if (s && s.parentNode) {
        s.parentNode.insertBefore(gtScript, s);
    } else {
        document.head.appendChild(gtScript);
    }
})();

function initCustomTranslateUI() {
    const parent = document.getElementById('google_translate_element');
    if (!parent || document.querySelector('.custom-translate-container')) return;

    injectTranslateStyles();
    const placementStyle = parent.getAttribute('style') || '';

    parent.setAttribute('aria-hidden', 'true');
    parent.style.position = 'absolute';
    parent.style.width = '1px';
    parent.style.height = '1px';
    parent.style.opacity = '0';
    parent.style.overflow = 'hidden';
    parent.style.pointerEvents = 'none';

    const container = document.createElement('div');
    container.className = 'custom-translate-container';
    if (placementStyle) {
        container.setAttribute('style', placementStyle);
    }

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'custom-translate-btn notranslate';
    btn.setAttribute('aria-label', 'Select language');
    btn.setAttribute('aria-expanded', 'false');

    let currentLang = 'en';
    const match = document.cookie.match(/(?:^| )googtrans=([^;]+)/);
    if (match) {
        const parts = match[1].split('/');
        currentLang = parts[2] || 'en';
    }

    const currentLangObj = languages.find(l => l.code === currentLang) || languages[0];
    btn.textContent = currentLangObj.label;

    const dropdown = document.createElement('div');
    dropdown.className = 'custom-translate-dropdown';
    dropdown.hidden = true;

    languages.forEach(lang => {
        const opt = document.createElement('button');
        opt.type = 'button';
        opt.className = 'custom-translate-option notranslate';
        opt.innerHTML = `<span class="flag-icon">${lang.label}</span><span class="lang-name">${lang.name}</span>`;
        opt.onclick = () => {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = lang.code;
                select.dispatchEvent(new Event('change'));
            }

            // Sync with backend i18n
            document.cookie = 'marocpc_lang=' + encodeURIComponent(lang.code) + '; max-age=31536000; path=/; samesite=lax';
            try { localStorage.setItem('marocpc_lang', lang.code); } catch (e) {}

            btn.textContent = lang.label;
            closeDropdown();
        };
        dropdown.appendChild(opt);
    });

    function closeDropdown() {
        dropdown.classList.remove('show');
        dropdown.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.onclick = (e) => {
        e.stopPropagation();
        const willOpen = dropdown.hidden;
        dropdown.hidden = !willOpen;
        dropdown.classList.toggle('show', willOpen);
        btn.setAttribute('aria-expanded', String(willOpen));
    };

    dropdown.onclick = (e) => e.stopPropagation();
    document.addEventListener('click', closeDropdown);

    container.appendChild(btn);
    container.appendChild(dropdown);
    parent.parentNode.insertBefore(container, parent);
}

function injectTranslateStyles() {
    if (document.getElementById('custom-translate-styles')) return;

    const style = document.createElement('style');
    style.id = 'custom-translate-styles';
    style.textContent = `
        .custom-translate-container {
            position: relative;
            z-index: 300;
            display: inline-flex;
            align-items: center;
            font-family: "Syne", "Inter", system-ui, sans-serif;
        }

        .custom-translate-btn {
            min-width: 44px;
            height: 34px;
            padding: 0 12px;
            border: 1px solid var(--border-cyan, rgba(0, 245, 212, 0.28));
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(0, 245, 212, 0.1), rgba(255, 107, 53, 0.05));
            color: var(--cyan, #00f5d4);
            font: 800 0.72rem/1 "Space Mono", "JetBrains Mono", monospace;
            letter-spacing: 0.08em;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.06);
            transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .custom-translate-btn:hover,
        .custom-translate-btn:focus-visible,
        .custom-translate-container:focus-within .custom-translate-btn {
            border-color: var(--cyan, #00f5d4);
            background: rgba(0, 245, 212, 0.14);
            color: var(--white, #eef0f4);
            outline: none;
            transform: translateY(-1px);
        }

        .custom-translate-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 168px;
            padding: 6px;
            border: 1px solid var(--border-cyan, rgba(0, 245, 212, 0.18));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.38);
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .custom-translate-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .custom-translate-dropdown[hidden] {
            display: block;
            visibility: hidden;
        }

        .custom-translate-option {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 10px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--text, #b0b8c8);
            font: 700 0.8rem/1.2 "Syne", "Inter", system-ui, sans-serif;
            text-align: left;
            cursor: pointer;
            transition: background 0.16s ease, color 0.16s ease;
        }

        .custom-translate-option:hover,
        .custom-translate-option:focus-visible {
            background: rgba(0, 245, 212, 0.09);
            color: var(--white, #eef0f4);
            outline: none;
        }

        .custom-translate-option .flag-icon {
            min-width: 28px;
            color: var(--cyan, #00f5d4);
            font-family: "Space Mono", "JetBrains Mono", monospace;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
        }

        [data-theme="light"] .custom-translate-btn,
        body.light-mode .custom-translate-btn {
            border-color: rgba(0, 122, 110, 0.22);
            background: linear-gradient(135deg, rgba(0, 122, 110, 0.08), rgba(198, 93, 36, 0.06));
            color: var(--industrial-teal, #007a6e);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }

        [data-theme="light"] .custom-translate-btn:hover,
        [data-theme="light"] .custom-translate-container:focus-within .custom-translate-btn,
        body.light-mode .custom-translate-btn:hover,
        body.light-mode .custom-translate-container:focus-within .custom-translate-btn {
            border-color: var(--industrial-teal, #007a6e);
            background: rgba(0, 122, 110, 0.11);
            color: var(--industrial-ink, #182029);
        }

        [data-theme="light"] .custom-translate-dropdown,
        body.light-mode .custom-translate-dropdown {
            border-color: rgba(0, 122, 110, 0.16);
            background: var(--industrial-surface, #f6f7f4);
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.14);
        }

        [data-theme="light"] .custom-translate-option,
        body.light-mode .custom-translate-option {
            color: var(--industrial-muted, #53606c);
        }

        [data-theme="light"] .custom-translate-option:hover,
        [data-theme="light"] .custom-translate-option:focus-visible,
        body.light-mode .custom-translate-option:hover,
        body.light-mode .custom-translate-option:focus-visible {
            background: rgba(0, 122, 110, 0.08);
            color: var(--industrial-ink, #182029);
        }

        .skiptranslate iframe,
        body > .skiptranslate {
            display: none !important;
        }

        body {
            top: 0 !important;
        }

        @media (max-width: 760px) {
            .custom-translate-btn {
                height: 32px;
                min-width: 42px;
                padding: 0 10px;
            }

            .custom-translate-dropdown {
                right: auto;
                left: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

function safeInitCustomTranslateUI() {
    try {
        initCustomTranslateUI();
    } catch (e) {
        console.error('Maroc PC: Error initializing custom translation UI:', e);
    }
}

function waitForGoogleTranslate() {
    let attempts = 0;
    const maxAttempts = 40; // 40 * 250ms = 10 seconds max wait

    const checkInterval = setInterval(() => {
        const combo = document.querySelector('.goog-te-combo');
        if (combo) {
            clearInterval(checkInterval);
            safeInitCustomTranslateUI();
        } else {
            attempts++;
            if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                // Fallback in case the combo never appears (e.g. adblocker blocking GT)
                safeInitCustomTranslateUI();
            }
        }
    }, 250);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForGoogleTranslate);
} else {
    waitForGoogleTranslate();
}
