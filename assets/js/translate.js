const languages = [
    { code: 'en', flag: '🇬🇧', name: 'English' },
    { code: 'fr', flag: '🇫🇷', name: 'Français' },
    { code: 'ar', flag: '🇲🇦', name: 'العربية' },
    { code: 'es', flag: '🇪🇸', name: 'Español' }
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
    gtScript.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    var s = document.getElementsByTagName('script')[0];
    if (s && s.parentNode) {
        s.parentNode.insertBefore(gtScript, s);
    } else {
        document.head.appendChild(gtScript);
    }
})();

function initCustomTranslateUI() {
    const parent = document.getElementById('google_translate_element');
    if (!parent) return;


    const container = document.createElement('div');
    container.className = 'custom-translate-container';

    const btn = document.createElement('button');
    btn.className = 'custom-translate-btn';
    btn.setAttribute('aria-label', 'Select Language');


    let currentLang = 'en';
    const match = document.cookie.match(/(?:^| )googtrans=([^;]+)/);
    if (match) {
        const parts = match[1].split('/');
        currentLang = parts[2] || 'en';
    }
    const currentLangObj = languages.find(l => l.code === currentLang) || languages[0];
    btn.innerHTML = currentLangObj.flag;

    const dropdown = document.createElement('div');
    dropdown.className = 'custom-translate-dropdown';

    languages.forEach(lang => {
        const opt = document.createElement('div');
        opt.className = 'custom-translate-option';
        opt.innerHTML = `<span class="flag-icon">${lang.flag}</span> <span class="lang-name">${lang.name}</span>`;
        opt.onclick = () => {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = lang.code;
                select.dispatchEvent(new Event('change'));
            }
            btn.innerHTML = lang.flag;
            dropdown.classList.remove('show');
        };
        dropdown.appendChild(opt);
    });

    btn.onclick = (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    };

    document.addEventListener('click', () => {
        dropdown.classList.remove('show');
    });

    container.appendChild(btn);
    container.appendChild(dropdown);


    parent.parentNode.insertBefore(container, parent.nextSibling);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initCustomTranslateUI, 500));
} else {
    setTimeout(initCustomTranslateUI, 500);
}

