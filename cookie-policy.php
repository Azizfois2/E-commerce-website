<?php
require_once 'config.php';
require_once 'support-page-template.php';

renderSupportPage([
    'title' => 'Cookie Policy',
    'eyebrow' => 'Cookies',
    'summary' => 'How Maroc PC uses cookies, browser storage, sessions, cart storage, theme preferences, translation preferences, and remember-me login behavior.',
    'cards' => [
        ['icon' => 'fa-lock', 'title' => 'Essential cookies', 'text' => 'Needed for login sessions, CSRF protection, and secure checkout/account behavior.'],
        ['icon' => 'fa-cart-shopping', 'title' => 'Cart storage', 'text' => 'Cart and recently viewed items can use browser storage so shopping continues between pages.'],
        ['icon' => 'fa-palette', 'title' => 'Preferences', 'text' => 'Theme, language, and UI preferences may be saved locally in your browser.'],
    ],
    'sections' => [
        [
            'title' => 'What Cookies Are',
            'paragraphs' => [
                'Cookies are small pieces of data stored by your browser. Maroc PC also uses local storage for some front-end features like cart, theme, wishlist UI state, and recently viewed products.',
            ],
        ],
        [
            'title' => 'Essential Cookies and Sessions',
            'items' => [
                'PHP session cookie: keeps your login, CSRF token, and secure account state active while browsing.',
                'Remember-me session behavior: when selected, your session can persist longer than a normal browser session.',
                'CSRF token state: protects forms such as login, reset password, checkout, and account actions.',
            ],
        ],
        [
            'title' => 'Local Storage and Preferences',
            'items' => [
                'Cart items and quantities may be stored locally before checkout.',
                'Theme selection can be stored so dark/light preference stays consistent.',
                'Wishlist, compare, product filters, recently viewed products, and checkout UI choices may use browser storage.',
                'Translation or language preferences may be stored by the translation tool.',
            ],
        ],
        [
            'title' => 'Analytics and Debugging',
            'paragraphs' => [
                'The current local project mainly uses first-party behavior for store functionality. If analytics, advertising pixels, or external tracking tools are added later, this policy should be updated to list them clearly.',
            ],
        ],
        [
            'title' => 'How to Control Cookies',
            'items' => [
                'Use your browser settings to block or delete cookies and site data.',
                'Deleting cookies may log you out and clear session-based security data.',
                'Clearing local storage may remove cart contents, preferences, saved comparison choices, or recently viewed items from the browser.',
                'For account data stored server-side, contact support@marocpc.com or use account controls where available.',
            ],
        ],
    ],
]);
