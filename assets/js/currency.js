/**
 * Currency Formatting with i18n Support
 * Detects locale from HTML lang attribute and formats currency accordingly
 */

(function() {
    'use strict';

    // Detect current locale from HTML lang attribute
    function getCurrentLocale() {
        const htmlLang = document.documentElement.getAttribute('lang');
        if (htmlLang) {
            // Extract just the language code (e.g., 'ar' from 'ar-MA' or 'ar')
            const locale = htmlLang.toLowerCase().split('-')[0];
            return locale;
        }
        // Fallback: check URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        if (langParam) {
            return langParam.toLowerCase().split('-')[0];
        }
        return 'en';
    }

    // Currency symbol/code translations
    const currencyTranslations = {
        'ar': 'درهم',  // Arabic: Dirham
        'fr': 'DH',    // French: DH
        'es': 'DH',    // Spanish: DH
        'en': 'DH'     // English: DH
    };

    /**
     * Format a number as Moroccan Dirham with locale-aware currency symbol
     * @param {number} value - The amount to format
     * @param {object} options - Formatting options
     * @returns {string} Formatted currency string
     */
    window.formatMAD = function(value, options = {}) {
        const locale = getCurrentLocale();
        const currencyCode = currencyTranslations[locale] || 'DH';
        
        const defaults = {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            showSymbol: true
        };
        
        const opts = { ...defaults, ...options };
        
        const formatted = Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: opts.minimumFractionDigits,
            maximumFractionDigits: opts.maximumFractionDigits
        });
        
        if (!opts.showSymbol) {
            return formatted;
        }
        
        // For Arabic, currency comes after the number
        // For other languages, keep currency after
        return `${formatted} ${currencyCode}`;
    };

    /**
     * Format currency in short form (K, M notation)
     * @param {number} value - The amount to format
     * @returns {string} Formatted currency string
     */
    window.formatMADShort = function(value) {
        const locale = getCurrentLocale();
        const currencyCode = currencyTranslations[locale] || 'DH';
        
        const num = Number(value || 0);
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M ' + currencyCode;
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K ' + currencyCode;
        }
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currencyCode;
    };

    // Legacy alias for backwards compatibility
    window.formatMoney = window.formatMAD;
    window.formatMADLocal = window.formatMAD;

    // Log for debugging
    const currentLocale = getCurrentLocale();
    const currentCurrency = currencyTranslations[currentLocale];
    console.log('Currency formatter loaded');
    console.log('  - HTML lang attribute:', document.documentElement.getAttribute('lang'));
    console.log('  - Detected locale:', currentLocale);
    console.log('  - Currency symbol:', currentCurrency);
    console.log('  - Test format:', window.formatMAD(95.92));

})();