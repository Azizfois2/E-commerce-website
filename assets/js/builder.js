/**
 * builder.js — Interactive PC Builder / Configurator
 * Step-by-step wizard with compatibility engine & wattage calculator
 */
const PCBuilder = (() => {
    const lookupBuilderPhrase = (key) => {
        if (!key) return undefined;
        const phraseMap = window.__marocPcPhraseMap;
        const i18n = window.__marocPcI18n;
        if (phraseMap?.[key]) return phraseMap[key];
        if (i18n?.[key]) return i18n[key];
        if (key.endsWith('.')) {
            const trimmed = key.slice(0, -1);
            if (phraseMap?.[trimmed]) return phraseMap[trimmed];
            if (i18n?.[trimmed]) return i18n[trimmed];
        }
        return undefined;
    };
    const builderText = (key, fallback) => lookupBuilderPhrase(key) ?? fallback ?? key;
    const builderTemplate = (key, fallback, params = {}) => {
        let value = builderText(key, fallback);
        Object.entries(params).forEach(([name, replacement]) => {
            value = value.replaceAll(`{${name}}`, replacement);
        });
        return value;
    };
    const builderLabel = label => builderText(label, label);
    const translateSpec = (text) => {
        let str = String(text || '');
        if (window.__marocPcPhraseMap) {
            ['threads', 'thread', 'GHz', 'MHz', 'MB', 'GB', 'TB', 'W'].forEach(unit => {
                const translated = window.__marocPcPhraseMap[unit];
                if (translated) {
                    str = str.replace(new RegExp(`\\b${unit}\\b`, 'g'), translated);
                }
            });
            const wTranslated = window.__marocPcPhraseMap['W'];
            if (wTranslated) {
                str = str.replace(/(\d)W\b/g, `$1 ${wTranslated}`);
            }
        }
        return str;
    };
    const translateBuilderValue = value => {
        if (!value || typeof value !== 'string') return value;
        const trimmed = value.trim();
        const map = window.__marocPcPhraseMap || {};
        if (trimmed && map[trimmed] && map[trimmed] !== trimmed) {
            return value.replace(trimmed, map[trimmed]);
        }
        return value;
    };
    const translateBuilderAttributes = element => {
        if (!Object.keys(window.__marocPcPhraseMap || {}).length || !element?.matches || element.closest('.notranslate,[translate="no"]')) return;
        ['placeholder', 'title', 'aria-label', 'data-ai-prompt'].forEach(attribute => {
            if (!element.hasAttribute(attribute)) return;
            const current = element.getAttribute(attribute);
            const translated = translateBuilderValue(current);
            if (translated !== current) element.setAttribute(attribute, translated);
        });
    };
    const shouldSkipTranslation = node => {
        const parent = node.parentElement;
        return !parent
            || parent.closest('.notranslate,[translate="no"],.cc-name,.cc-brand,.cc-spec-tag,.cb-card-title,.cb-card-desc,.cb-card-author,.cb-card-date,input,textarea,select,option,script,style');
    };
    const translateBuilderCopy = root => {
        if (!window.__marocPcPhraseMap || !root) return;
        const scope = root.nodeType === Node.TEXT_NODE ? root.parentElement : root;
        if (!scope || scope.closest?.('.notranslate,[translate="no"]')) return;

        if (root.nodeType === Node.TEXT_NODE) {
            if (shouldSkipTranslation(root) || !root.nodeValue.trim()) return;
            const translated = translateBuilderValue(root.nodeValue);
            if (translated !== root.nodeValue) root.nodeValue = translated;
            return;
        }

        if (root.nodeType === Node.ELEMENT_NODE) {
            translateBuilderAttributes(root);
            root.querySelectorAll('[placeholder],[title],[aria-label],[data-ai-prompt]').forEach(translateBuilderAttributes);
        }

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return shouldSkipTranslation(node) || !node.nodeValue.trim()
                    ? NodeFilter.FILTER_REJECT
                    : NodeFilter.FILTER_ACCEPT;
            }
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => translateBuilderCopy(node));
    };
    const initBuilderPhraseObserver = () => {
        if (!window.__marocPcPhraseMap || document.body.dataset.builderI18nReady === '1') return;
        document.body.dataset.builderI18nReady = '1';
        translateBuilderCopy(document.body);
        new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE || node.nodeType === Node.TEXT_NODE) {
                        translateBuilderCopy(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    };

    // ── Component Steps ──────────────────────────────────────
    const STEPS = [
        { key: 'cpu', label: 'CPU', icon: 'fa-microchip', category: 'cpu' },
        { key: 'motherboard', label: 'Motherboard', icon: 'fa-diagram-project', category: 'motherboard' },
        { key: 'gpu', label: 'GPU', icon: 'fa-gamepad', category: 'gpu' },
        { key: 'ram', label: 'RAM', icon: 'fa-memory', category: 'ram' },
        { key: 'storage', label: 'Storage', icon: 'fa-hdd', category: 'storage' },
        { key: 'psu', label: 'PSU', icon: 'fa-bolt', category: 'psu' },
        { key: 'cooling', label: 'Cooling', icon: 'fa-fan', category: 'cooling' },
        { key: 'monitor', label: 'Monitor', icon: 'fa-display', category: 'monitor' },
        { key: 'accessories', label: 'Accessories', icon: 'fa-toolbox', category: 'accessories', optional: true },
        { key: 'keyboard', label: 'Keyboard', icon: 'fa-keyboard', category: 'keyboard', optional: true },
        { key: 'mouse', label: 'Mouse', icon: 'fa-computer-mouse', category: 'mouse', optional: true },
    ];

    // ── PC Case Slot Layout Map ────────────────────────────────
    // Mimics a front-view ATX case interior. Each slot is positioned
    // via CSS grid-row / grid-column inline styles.
    const CASE_SLOT_LAYOUT = {
        cooling:     { row: 1, col: '1 / 3', size: 'sm', zone: 'TOP'     },
        cpu:         { row: 2, col: '1 / 2', size: 'md', zone: 'CPU'     },
        ram:         { row: 2, col: '2 / 3', size: 'md', zone: 'DIMM'    },
        motherboard: { row: 3, col: '1 / 3', size: 'lg', zone: 'ATX'     },
        gpu:         { row: 4, col: '1 / 3', size: 'lg', zone: 'PCIE'    },
        storage:     { row: 5, col: '1 / 2', size: 'sm', zone: 'BAY'     },
        monitor:     { row: 5, col: '2 / 3', size: 'sm', zone: 'IO'      },
        psu:         { row: 6, col: '1 / 3', size: 'md', zone: 'SHROUD'  },
        accessories: { row: 7, col: '1 / 3', size: 'sm', zone: 'EXTERN'  },
        keyboard:    { row: 8, col: '1 / 3', size: 'sm', zone: 'EXTERN'  },
        mouse:       { row: 9, col: '1 / 3', size: 'sm', zone: 'EXTERN'  }
    };

    // ── Component SVG Illustrations ────────────────────────────
    // Technical hardware illustrations: schematic style, not flat, not photorealistic.
    // All use currentColor so they inherit theme colors via CSS.
    function getComponentSVG(key) {
        const svgs = {
            cpu: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- CPU die: top-down with IHS and pin grid -->
                <rect x="8" y="8" width="64" height="64" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <rect x="18" y="18" width="44" height="44" rx="2" stroke="currentColor" stroke-width="2"/>
                <rect x="24" y="24" width="32" height="32" rx="1" fill="currentColor" opacity="0.12"/>
                <text x="40" y="43" text-anchor="middle" font-family="monospace" font-size="7" font-weight="700" fill="currentColor">CPU</text>
                <!-- Pin rows -->
                <g stroke="currentColor" stroke-width="0.8" opacity="0.5">
                    <line x1="12" y1="14" x2="12" y2="66"/><line x1="16" y1="14" x2="16" y2="66"/>
                    <line x1="68" y1="14" x2="68" y2="66"/><line x1="64" y1="14" x2="64" y2="66"/>
                    <line x1="14" y1="12" x2="66" y2="12"/><line x1="14" y1="16" x2="66" y2="16"/>
                    <line x1="14" y1="68" x2="66" y2="68"/><line x1="14" y1="64" x2="66" y2="64"/>
                </g>
                <!-- Corner notch -->
                <circle cx="14" cy="14" r="2" fill="currentColor" opacity="0.3"/>
            </svg>`,

            ram: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- DDR5 RAM stick: heat spreader + edge contacts -->
                <rect x="6" y="22" width="68" height="36" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M6 28h68" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <path d="M6 52h68" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <!-- Heat spreader fins -->
                <g stroke="currentColor" stroke-width="0.6" opacity="0.35">
                    <line x1="12" y1="28" x2="12" y2="52"/><line x1="20" y1="28" x2="20" y2="52"/>
                    <line x1="28" y1="28" x2="28" y2="52"/><line x1="36" y1="28" x2="36" y2="52"/>
                    <line x1="44" y1="28" x2="44" y2="52"/><line x1="52" y1="28" x2="52" y2="52"/>
                    <line x1="60" y1="28" x2="60" y2="52"/><line x1="68" y1="28" x2="68" y2="52"/>
                </g>
                <!-- Label -->
                <text x="40" y="44" text-anchor="middle" font-family="monospace" font-size="6" font-weight="700" fill="currentColor">DDR5</text>
                <!-- Edge contacts (gold fingers) -->
                <g fill="currentColor" opacity="0.4">
                    <rect x="8" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="14" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="20" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="26" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="32" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="38" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="44" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="50" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="56" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="62" y="58" width="4" height="8" rx="0.5"/>
                    <rect x="68" y="58" width="4" height="8" rx="0.5"/>
                </g>
                <!-- Key notch -->
                <rect x="34" y="58" width="12" height="8" fill="var(--page-bg, #040c0a)"/>
            </svg>`,

            gpu: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- GPU: top-down with dual fans + shroud -->
                <rect x="4" y="16" width="72" height="48" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <!-- Shroud outline -->
                <path d="M4 20h72M4 60h72" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <!-- Fan 1 -->
                <circle cx="24" cy="40" r="14" stroke="currentColor" stroke-width="1.2"/>
                <circle cx="24" cy="40" r="4" fill="currentColor" opacity="0.25"/>
                <g stroke="currentColor" stroke-width="0.8" opacity="0.5">
                    <path d="M24 26 Q28 33 24 40 Q20 33 24 26Z"/>
                    <path d="M38 40 Q31 44 24 40 Q31 36 38 40Z"/>
                    <path d="M24 54 Q20 47 24 40 Q28 47 24 54Z"/>
                    <path d="M10 40 Q17 36 24 40 Q17 44 10 40Z"/>
                </g>
                <!-- Fan 2 -->
                <circle cx="56" cy="40" r="14" stroke="currentColor" stroke-width="1.2"/>
                <circle cx="56" cy="40" r="4" fill="currentColor" opacity="0.25"/>
                <g stroke="currentColor" stroke-width="0.8" opacity="0.5">
                    <path d="M56 26 Q60 33 56 40 Q52 33 56 26Z"/>
                    <path d="M70 40 Q63 44 56 40 Q63 36 70 40Z"/>
                    <path d="M56 54 Q52 47 56 40 Q60 47 56 54Z"/>
                    <path d="M42 40 Q49 36 56 40 Q49 44 42 40Z"/>
                </g>
                <!-- Heat pipes -->
                <g stroke="currentColor" stroke-width="1" opacity="0.3">
                    <path d="M16 62 L16 66 M24 62 L24 66 M32 62 L32 66"/>
                    <path d="M48 62 L48 66 M56 62 L56 66 M64 62 L64 66"/>
                </g>
            </svg>`,

            storage: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- NVMe M.2 SSD stick -->
                <rect x="8" y="28" width="64" height="24" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <!-- Controller chip -->
                <rect x="14" y="32" width="16" height="16" rx="1" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <text x="22" y="42" text-anchor="middle" font-family="monospace" font-size="5" fill="currentColor" opacity="0.7">CTRL</text>
                <!-- NAND chips -->
                <rect x="34" y="33" width="10" height="14" rx="0.5" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <rect x="48" y="33" width="10" height="14" rx="0.5" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <rect x="62" y="33" width="8" height="14" rx="0.5" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <!-- M.2 connector notch -->
                <rect x="8" y="28" width="6" height="24" fill="currentColor" opacity="0.15"/>
                <path d="M8 40 L14 40" stroke="currentColor" stroke-width="0.8"/>
                <!-- Label -->
                <text x="52" y="46" text-anchor="middle" font-family="monospace" font-size="5" font-weight="700" fill="currentColor" opacity="0.6">NVMe</text>
            </svg>`,

            motherboard: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- ATX Motherboard PCB -->
                <rect x="6" y="6" width="68" height="68" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <!-- CPU socket -->
                <rect x="14" y="12" width="22" height="22" rx="1" stroke="currentColor" stroke-width="1.2"/>
                <text x="25" y="26" text-anchor="middle" font-family="monospace" font-size="5" fill="currentColor" opacity="0.7">CPU</text>
                <!-- RAM slots -->
                <g stroke="currentColor" stroke-width="0.8" opacity="0.5">
                    <rect x="42" y="10" width="3" height="28" rx="0.5"/>
                    <rect x="47" y="10" width="3" height="28" rx="0.5"/>
                    <rect x="52" y="10" width="3" height="28" rx="0.5"/>
                    <rect x="57" y="10" width="3" height="28" rx="0.5"/>
                </g>
                <!-- PCIe slots -->
                <rect x="10" y="40" width="44" height="4" rx="0.5" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <rect x="10" y="48" width="44" height="4" rx="0.5" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <rect x="10" y="56" width="30" height="4" rx="0.5" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <!-- Chipset heatsink -->
                <rect x="56" y="44" width="14" height="14" rx="1" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                <g stroke="currentColor" stroke-width="0.5" opacity="0.3">
                    <line x1="58" y1="46" x2="68" y2="46"/><line x1="58" y1="49" x2="68" y2="49"/>
                    <line x1="58" y1="52" x2="68" y2="52"/><line x1="58" y1="55" x2="68" y2="55"/>
                </g>
                <!-- I/O shield area -->
                <g fill="currentColor" opacity="0.2">
                    <rect x="8" y="8" width="4" height="8" rx="0.5"/>
                    <rect x="8" y="18" width="4" height="6" rx="0.5"/>
                    <rect x="8" y="26" width="4" height="6" rx="0.5"/>
                </g>
                <!-- SATA ports -->
                <g stroke="currentColor" stroke-width="0.8" opacity="0.4">
                    <rect x="62" y="62" width="8" height="3" rx="0.5"/>
                    <rect x="62" y="67" width="8" height="3" rx="0.5"/>
                </g>
            </svg>`,

            psu: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- PSU: box with intake fan + cables -->
                <rect x="8" y="14" width="64" height="52" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <!-- Fan grille (honeycomb pattern) -->
                <circle cx="40" cy="40" r="18" stroke="currentColor" stroke-width="1"/>
                <circle cx="40" cy="40" r="12" stroke="currentColor" stroke-width="0.6" opacity="0.4"/>
                <circle cx="40" cy="40" r="6" stroke="currentColor" stroke-width="0.6" opacity="0.3"/>
                <g stroke="currentColor" stroke-width="0.5" opacity="0.3">
                    <line x1="22" y1="40" x2="58" y2="40"/>
                    <line x1="40" y1="22" x2="40" y2="58"/>
                    <line x1="27" y1="27" x2="53" y2="53"/>
                    <line x1="53" y1="27" x2="27" y2="53"/>
                </g>
                <!-- Power switch -->
                <rect x="60" y="18" width="8" height="5" rx="1" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <line x1="64" y1="19" x2="64" y2="22" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <!-- Label -->
                <text x="40" y="44" text-anchor="middle" font-family="monospace" font-size="6" font-weight="700" fill="currentColor" opacity="0.6">PSU</text>
                <!-- Cable outputs -->
                <g fill="currentColor" opacity="0.25">
                    <rect x="12" y="66" width="6" height="3" rx="0.5"/>
                    <rect x="22" y="66" width="6" height="3" rx="0.5"/>
                    <rect x="32" y="66" width="6" height="3" rx="0.5"/>
                    <rect x="42" y="66" width="6" height="3" rx="0.5"/>
                    <rect x="52" y="66" width="6" height="3" rx="0.5"/>
                    <rect x="62" y="66" width="6" height="3" rx="0.5"/>
                </g>
            </svg>`,

            cooling: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- CPU Cooler: fan top-view with blades -->
                <circle cx="40" cy="40" r="32" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="40" cy="40" r="28" stroke="currentColor" stroke-width="0.8" opacity="0.3"/>
                <!-- Fan hub -->
                <circle cx="40" cy="40" r="8" fill="currentColor" opacity="0.2"/>
                <circle cx="40" cy="40" r="4" stroke="currentColor" stroke-width="1"/>
                <!-- Fan blades -->
                <g stroke="currentColor" stroke-width="1.2" fill="currentColor" opacity="0.15">
                    <path d="M40 12 Q50 26 40 40 Q30 26 40 12Z"/>
                    <path d="M68 40 Q54 50 40 40 Q54 30 68 40Z"/>
                    <path d="M40 68 Q30 54 40 40 Q50 54 40 68Z"/>
                    <path d="M12 40 Q26 30 40 40 Q26 50 12 40Z"/>
                    <path d="M57 18 Q56 34 40 40 Q38 24 57 18Z"/>
                    <path d="M62 57 Q46 56 40 40 Q56 38 62 57Z"/>
                    <path d="M23 62 Q24 46 40 40 Q42 56 23 62Z"/>
                    <path d="M18 23 Q34 24 40 40 Q24 42 18 23Z"/>
                </g>
                <!-- Mounting screws -->
                <circle cx="14" cy="14" r="2" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <circle cx="66" cy="14" r="2" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <circle cx="14" cy="66" r="2" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <circle cx="66" cy="66" r="2" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
            </svg>`,

            monitor: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Monitor: front view with bezel + stand -->
                <!-- Screen bezel -->
                <rect x="6" y="10" width="68" height="44" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <!-- Screen area -->
                <rect x="10" y="14" width="60" height="36" rx="1" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <!-- Screen reflection -->
                <path d="M12 16 L40 16 L12 30Z" fill="currentColor" opacity="0.06"/>
                <!-- Stand neck -->
                <rect x="34" y="54" width="12" height="10" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <!-- Stand base -->
                <path d="M22 64 L58 64 L54 70 L26 70Z" stroke="currentColor" stroke-width="1.2" fill="currentColor" opacity="0.1"/>
                <!-- Power LED -->
                <circle cx="40" cy="52" r="1.5" fill="currentColor" opacity="0.4"/>
                <!-- Brand label -->
                <text x="40" y="36" text-anchor="middle" font-family="monospace" font-size="5" fill="currentColor" opacity="0.3">DISPLAY</text>
            </svg>`,

            keyboard: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Keyboard: top-down layout -->
                <rect x="4" y="24" width="72" height="32" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <!-- Key rows -->
                <g stroke="currentColor" stroke-width="0.6" opacity="0.5">
                    <!-- Top row (function keys) -->
                    <rect x="8" y="28" width="4" height="4" rx="0.5"/><rect x="14" y="28" width="4" height="4" rx="0.5"/>
                    <rect x="20" y="28" width="4" height="4" rx="0.5"/><rect x="26" y="28" width="4" height="4" rx="0.5"/>
                    <rect x="32" y="28" width="4" height="4" rx="0.5"/><rect x="38" y="28" width="4" height="4" rx="0.5"/>
                    <rect x="44" y="28" width="4" height="4" rx="0.5"/><rect x="50" y="28" width="4" height="4" rx="0.5"/>
                    <rect x="56" y="28" width="4" height="4" rx="0.5"/><rect x="62" y="28" width="4" height="4" rx="0.5"/>
                    <rect x="68" y="28" width="4" height="4" rx="0.5"/>
                    <!-- Number row -->
                    <rect x="8" y="34" width="4" height="4" rx="0.5"/><rect x="14" y="34" width="4" height="4" rx="0.5"/>
                    <rect x="20" y="34" width="4" height="4" rx="0.5"/><rect x="26" y="34" width="4" height="4" rx="0.5"/>
                    <rect x="32" y="34" width="4" height="4" rx="0.5"/><rect x="38" y="34" width="4" height="4" rx="0.5"/>
                    <rect x="44" y="34" width="4" height="4" rx="0.5"/><rect x="50" y="34" width="4" height="4" rx="0.5"/>
                    <rect x="56" y="34" width="4" height="4" rx="0.5"/><rect x="62" y="34" width="4" height="4" rx="0.5"/>
                    <rect x="68" y="34" width="4" height="4" rx="0.5"/>
                    <!-- QWERTY row -->
                    <rect x="10" y="40" width="4" height="4" rx="0.5"/><rect x="16" y="40" width="4" height="4" rx="0.5"/>
                    <rect x="22" y="40" width="4" height="4" rx="0.5"/><rect x="28" y="40" width="4" height="4" rx="0.5"/>
                    <rect x="34" y="40" width="4" height="4" rx="0.5"/><rect x="40" y="40" width="4" height="4" rx="0.5"/>
                    <rect x="46" y="40" width="4" height="4" rx="0.5"/><rect x="52" y="40" width="4" height="4" rx="0.5"/>
                    <rect x="58" y="40" width="4" height="4" rx="0.5"/><rect x="64" y="40" width="4" height="4" rx="0.5"/>
                    <!-- Space bar row -->
                    <rect x="8" y="46" width="6" height="4" rx="0.5"/>
                    <rect x="20" y="46" width="28" height="4" rx="0.5"/>
                    <rect x="54" y="46" width="6" height="4" rx="0.5"/>
                    <rect x="62" y="46" width="6" height="4" rx="0.5"/>
                </g>
                <!-- Wrist rest hint -->
                <path d="M6 56 L74 56" stroke="currentColor" stroke-width="0.5" opacity="0.2"/>
            </svg>`,

            mouse: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Mouse: side profile view -->
                <path d="M16 44 Q16 20 40 16 Q64 20 64 44 Q64 60 52 64 L28 64 Q16 60 16 44Z" stroke="currentColor" stroke-width="1.5"/>
                <!-- Left button -->
                <path d="M20 44 Q20 28 40 24 L40 44Z" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <!-- Right button -->
                <path d="M60 44 Q60 28 40 24 L40 44Z" stroke="currentColor" stroke-width="0.8" opacity="0.5"/>
                <!-- Scroll wheel -->
                <ellipse cx="40" cy="32" rx="3" ry="5" stroke="currentColor" stroke-width="1"/>
                <line x1="40" y1="28" x2="40" y2="36" stroke="currentColor" stroke-width="0.6" opacity="0.5"/>
                <!-- DPI button -->
                <circle cx="40" cy="42" r="2" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <!-- Side buttons -->
                <rect x="18" y="38" width="4" height="8" rx="1" stroke="currentColor" stroke-width="0.8" opacity="0.4"/>
                <!-- Cable -->
                <path d="M40 16 Q40 10 44 8" stroke="currentColor" stroke-width="1" opacity="0.4" fill="none"/>
                <!-- Logo area -->
                <circle cx="40" cy="52" r="4" stroke="currentColor" stroke-width="0.6" opacity="0.3"/>
                <!-- Base pad -->
                <path d="M22 64 L58 64" stroke="currentColor" stroke-width="1.2" opacity="0.4"/>
            </svg>`,

            accessories: `<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Accessories: generic toolbox/peripherals -->
                <rect x="10" y="20" width="60" height="40" rx="3" stroke="currentColor" stroke-width="1.5"/>
                <path d="M10 30 L70 30" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                <rect x="20" y="14" width="40" height="6" rx="1" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                <!-- Tools inside -->
                <g stroke="currentColor" stroke-width="1" opacity="0.5">
                    <line x1="24" y1="38" x2="24" y2="52"/>
                    <circle cx="24" cy="36" r="3"/>
                    <rect x="34" y="36" width="3" height="18" rx="0.5"/>
                    <rect x="42" y="36" width="3" height="18" rx="0.5"/>
                    <path d="M52 36 L56 36 L56 54 L52 54Z"/>
                </g>
                <text x="40" y="58" text-anchor="middle" font-family="monospace" font-size="5" fill="currentColor" opacity="0.4">TOOLS</text>
            </svg>`
        };
        return svgs[key] || svgs.accessories;
    }

    const DEFAULT_WATTAGE = {
        cpu: 125, motherboard: 50, gpu: 300, ram: 10, storage: 10, psu: 0, cooling: 15, monitor: 0, accessories: 0, keyboard: 2, mouse: 2,
    };

    const BUILD_SERVICES = {
        assembly: { id: 'service-assembly', name: 'Professional PC Assembly', price: 299, icon: 'fa-screwdriver-wrench' },
        bios: { id: 'service-bios', name: 'BIOS Update', price: 99, icon: 'fa-microchip' },
        stress: { id: 'service-stress', name: 'Stress Test Report', price: 149, icon: 'fa-gauge-high' },
        windows: { id: 'service-windows', name: 'Windows Install', price: 199, icon: 'fa-window-maximize' },
        bazzite: { id: 'service-bazzite', name: 'Bazzite + Proton++ Install', price: 249, icon: 'fa-linux' },
    };

    let PRESETS = [
        { 
            key: 'esports', 
            label: 'Base Build', 
            labelKey: 'Base Build',
            useCase: 'gaming', 
            budget: 12500, 
            descKey: 'base_build_desc',
            description: 'A solid entry level setup featuring an AMD or Intel processor, ideal for everyday computing tasks, light multitasking, and casual gaming.',
            image: 'https://images.unsplash.com/photo-1587202376732-8309058b70b4?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'aaa1440', 
            label: 'Advanced Build', 
            labelKey: 'Advanced Build',
            useCase: 'gaming', 
            budget: 18000, 
            descKey: 'advanced_build_desc',
            description: 'A versatile mid range build powered by high-performance components, designed for seamless multitasking, gaming at higher settings, and content creation.',
            image: 'https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'creator', 
            label: 'Power Build', 
            labelKey: 'Power Build',
            useCase: 'editing', 
            budget: 26000, 
            descKey: 'power_build_desc',
            description: 'A high performance system optimized for demanding workloads like 4K gaming, video editing, and advanced simulations, offering top-tier speed and efficiency.',
            image: 'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'legacy', 
            label: 'Legacy Enthusiast', 
            labelKey: 'Legacy Enthusiast',
            useCase: 'legacy', 
            budget: 4200, 
            descKey: 'legacy_build_desc',
            description: 'A specialized build using used server-grade components and legacy X99 architecture. Recommended for enthusiasts comfortable with tinkering.',
            image: 'https://images.unsplash.com/photo-1555680202-c86f0e12f086?q=80&w=400&auto=format&fit=crop'
        }
    ];

    const FINDER_GAMES = [
        { id: 'cyberpunk', name: 'Cyberpunk 2077', icon: 'fa-robot', demand: 1.12, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1091500/header.jpg' },
        { id: 'rdr2', name: 'Red Dead Redemption 2', icon: 'fa-hat-cowboy', demand: 1.02, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1174180/header.jpg' },
        { id: 'warzone', name: 'Warzone', icon: 'fa-person-rifle', demand: 0.98, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1938090/header.jpg' },
        { id: 'wukong', name: 'Black Myth: Wukong', icon: 'fa-dragon', demand: 1.14, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/2358720/header.jpg' },
        { id: 'bg3', name: "Baldur's Gate 3", icon: 'fa-dice-d20', demand: 0.86, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1086940/header.jpg' },
        { id: 'starfield', name: 'Starfield', icon: 'fa-shuttle-space', demand: 1.08, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1716740/header.jpg' },
        { id: 'valorant', name: 'Valorant', icon: 'fa-crosshairs', demand: 0.62, image: 'https://upload.wikimedia.org/wikipedia/en/b/ba/Valorant_cover.jpg' },
        { id: 'forza5', name: 'Forza Horizon 5', icon: 'fa-flag-checkered', demand: 0.92, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1551360/header.jpg' },
        { id: 'fortnite', name: 'Fortnite', icon: 'fa-bolt', demand: 0.78, image: 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Fortnite_at_E3_2018_%2842781993231%29.jpg/960px-Fortnite_at_E3_2018_%2842781993231%29.jpg' },
        { id: 'gta5', name: 'GTA V', icon: 'fa-car', demand: 0.74, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/271590/header.jpg' },
        { id: 'helldivers2', name: 'Helldivers 2', icon: 'fa-skull', demand: 0.94, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/553850/header.jpg' },
        { id: 'eldenring', name: 'Elden Ring', icon: 'fa-ring', demand: 0.88, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1245620/header.jpg' },
        { id: 'fc25', name: 'EA SPORTS FC 25', icon: 'fa-futbol', demand: 0.70, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/2669320/header.jpg' },
        { id: 'minecraft', name: 'Minecraft', icon: 'fa-cube', demand: 0.40, image: 'https://upload.wikimedia.org/wikipedia/en/5/51/Minecraft_cover.png' },
        { id: 'rocketleague', name: 'Rocket League', icon: 'fa-car-burst', demand: 0.50, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/252950/header.jpg' },
        { id: 'pragmata', name: 'PRAGMATA', icon: 'fa-user-astronaut', demand: 1.08, image: '', note: 'Capcom sci-fi action adventure' },
        { id: 're4', name: 'Resident Evil 4', icon: 'fa-skull-crossbones', demand: 0.82, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/2050650/header.jpg' },
        { id: 'persona5', name: 'Persona 5 Royal', icon: 'fa-masks-theater', demand: 0.45, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1687950/header.jpg' },
        { id: 'efootball', name: 'eFootball 2025', icon: 'fa-futbol', demand: 0.68, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1665460/header.jpg' },
    ];

    const FINDER_RECOMMENDATION = {
        resolution: { '1080p': 0, '1440p': 3600, '4K': 9000 },
        fps: { 60: 0, 120: 2600, 165: 4500 },
        baseBudget: 9800,
        minBudget: 8000,
        maxBudget: 32000,
    };

    const GAME_ART_FALLBACKS = {
        cyberpunk: ['#101826', '#00f5d4', '#7c4dff'],
        rdr2: ['#20120d', '#ff6b00', '#8f1d1d'],
        warzone: ['#101614', '#7ee081', '#2a4038'],
        wukong: ['#1a120d', '#f4b860', '#7c2d12'],
        bg3: ['#181128', '#c084fc', '#f97316'],
        starfield: ['#0b1420', '#8ecae6', '#e5e7eb'],
        valorant: ['#201017', '#ff4655', '#00f5d4'],
        forza5: ['#111827', '#22d3ee', '#f59e0b'],
        fortnite: ['#111232', '#7c4dff', '#00f5d4'],
        gta5: ['#132018', '#22c55e', '#f97316'],
        helldivers2: ['#151515', '#facc15', '#ef4444'],
        eldenring: ['#1b1710', '#d4af37', '#8b5a2b'],
        fc25: ['#102018', '#00f5d4', '#ffffff'],
        minecraft: ['#10200f', '#4ade80', '#8b5a2b'],
        rocketleague: ['#101728', '#38bdf8', '#f97316'],
        pragmata: ['#08111f', '#00f5d4', '#c7d2fe'],
        re4: ['#1c1714', '#a3a3a3', '#dc2626'],
        persona5: ['#1a0508', '#ef233c', '#f8fafc'],
        efootball: ['#0f1b12', '#00f5d4', '#facc15'],
    };

    // ── State ────────────────────────────────────────────────
    let currentStep = 0;
    let selectedComponents = {};
    let selectedServices = {};
    let allProducts = [];
    let buildName = 'My Build';
    let useCase = 'gaming';
    let activePreset = 'aaa1440';
    let targetBudget = 18000;
    let selectedPlatform = 'intel';
    let builderPath = '';
    let bottleneckResolution = '1440p';
    let componentFilters = {
        query: '',
        sort: 'recommended',
        stockOnly: true,
    };
    let finderState = {
        games: ['cyberpunk', 'gta5'],
        resolution: '1440p',
        targetFps: 120,
        budget: 18000,
    };

    let activePriceTier = 'all';
    let lastRenderedStep = null;
    let wizardState = {
        currentStep: 1,
        useCase: 'gaming',
        budget: 12000,
        theme: 'performance'
    };

    function productImage(product) {
        return product.image || `images/products/placeholder-${product.category || 'storage'}.svg`;
    }

    // ── Init ─────────────────────────────────────────────────
    async function init() {
        // Load products from global data.js
        if (typeof products !== 'undefined') {
            allProducts = products;
        }

        // Fetch dynamic presets
        try {
            const res = await fetch('api/custom-builds.php');
            const data = await res.json();
            if (data.success && data.presets && data.presets.length > 0) {
                PRESETS = data.presets.map((p, index) => {
                    const fallbackImages = [
                        'https://images.unsplash.com/photo-1587202376732-8309058b70b4?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1555680202-c86f0e12f086?q=80&w=400&auto=format&fit=crop'
                    ];
                    return {
                        key: 'preset_' + p.id,
                        label: p.name,
                        useCase: p.target_category,
                        budget: parseFloat(p.base_price),
                        description: p.description || 'Custom preset curated by our experts.',
                        image: fallbackImages[index % fallbackImages.length],
                        productsData: p.products_json
                    };
                });
            }
        } catch (e) {
            console.error('Failed to load dynamic presets', e);
        }

        // Check for shared build in URL
        const params = new URLSearchParams(window.location.search);
        const shareCode = params.get('build');
        if (shareCode) {
            loadSharedBuild(shareCode);
        }

        renderWizardSteps();
        renderUseCaseBar();
        renderGamingFinder();
        renderBuilderToolPanels();
        renderCurrentStep();
        updateSummary();
        bindServiceOptions();

        // Initialize FPS Estimator if available
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.init();
            syncFinderEstimator();
        }

        // Load preference from localStorage
        const savedMode = localStorage.getItem('workspaceMode') || 'focus';
        setWorkspaceMode(savedMode);
        initStickyDockObserver();
        initBuilderPhraseObserver();
    }

    // ── Render Wizard Steps ──────────────────────────────────
    function renderWizardSteps() {
        const container = document.getElementById('wizardSteps');
        if (!container) return;

        container.innerHTML = STEPS.map((step, i) => {
            let cls = 'wizard-step';
            if (i === currentStep) cls += ' active';
            if (selectedComponents[step.key]) cls += ' completed';
            return `<button class="${cls}" data-step="${i}">
                <i class="fas ${step.icon}"></i>
                ${builderText(step.label, step.label)}
                ${step.optional ? `<small>${builderText('Optional', 'Optional')}</small>` : ''}
            </button>`;
        }).join('');

        container.querySelectorAll('.wizard-step').forEach(btn => {
            btn.addEventListener('click', () => {
                currentStep = parseInt(btn.dataset.step);
                renderWizardSteps();
                renderCurrentStep();
            });
        });
    }

    // ── Use Case Bar ─────────────────────────────────────────
    function renderUseCaseBar() {
        const container = document.getElementById('useCaseBar');
        if (!container) return;

        container.innerHTML = `
            <div class="preset-grid">
                ${PRESETS.map(c => `
                    <div class="preset-card ${c.key === activePreset ? 'active' : ''}" data-case="${c.key}">
                        <div class="preset-image">
                            <img src="${c.image}" alt="${c.label}" onerror="this.src='logo.png'">
                        </div>
                        <div class="preset-content">
                            <h3>${builderText(c.labelKey, c.label)}</h3>
                            <p>${builderText(c.description, c.description)}</p>
                            <div class="preset-footer">
                                <span class="preset-budget">${builderTemplate('Target: {amount}', 'Target: {amount}', {amount: formatMAD(c.budget)})}</span>
                                <button class="btn-start-build" 
                                    data-case="${c.key}" 
                                    data-use-case="${c.useCase}" 
                                    data-budget="${c.budget}">
                                    ${builderTemplate('START WITH {label}', 'START WITH {label}', {label: builderText(c.labelKey, c.label)})}
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        container.querySelectorAll('.btn-start-build').forEach(btn => {
            btn.addEventListener('click', () => {
                const selectedKey = btn.dataset.case;
                if (selectedKey === 'legacy') {
                    showLegacyWarningModal(() => {
                        applyPreset(selectedKey, btn);
                    });
                } else {
                    applyPreset(selectedKey, btn);
                }

            });
        });
    }

    function renderActivePresetBanner(preset) {
        const banner = document.getElementById('activePresetBanner');
        if (!banner) return;

        if (!preset) {
            banner.style.display = 'none';
            return;
        }

        banner.innerHTML = `
            <div class="apb-content">
                <div class="apb-left">
                    <span class="apb-badge"><i class="fas fa-microchip"></i> ${builderText('Preset Loaded', 'Preset Loaded')}</span>
                    <div class="apb-info">
                        <h3 class="apb-title">${builderText(preset.labelKey || preset.label, preset.label)}</h3>
                        <span class="apb-budget-info">${builderTemplate('Target Budget: {amount}', 'Target Budget: {amount}', {amount: formatMAD(preset.budget)})}</span>
                    </div>
                </div>
                <div class="apb-right">
                    <button class="btn-change-preset" onclick="PCBuilder.showPresetSelector()"><i class="fas fa-sync-alt"></i> ${builderText('Change Preset', 'Change Preset')}</button>
                </div>
            </div>
        `;
        banner.style.display = 'block';
    }

    function showPresetSelector() {
        const workspace = document.getElementById('pcBuilderWorkspace');
        if (workspace) {
            workspace.classList.add('show-preset-grid');
        }
        document.getElementById('useCaseBar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function applyPreset(selectedKey, btn) {
        activePreset = selectedKey;
        useCase = btn.dataset.useCase || 'general';
        targetBudget = parseInt(btn.dataset.budget, 10) || targetBudget;

        const preset = PRESETS.find(p => p.key === selectedKey);
        if (preset) {
            renderActivePresetBanner(preset);
        }
        
        const workspace = document.getElementById('pcBuilderWorkspace');
        if (workspace) {
            workspace.classList.remove('show-preset-grid');
        }
        
        const container = document.getElementById('useCaseBar');
        if (container) {
            container.querySelectorAll('.preset-card').forEach(card => card.classList.remove('active'));
            const card = btn.closest('.preset-card');
            if (card) card.classList.add('active');
        }
        
        autoBuild(btn.dataset.useCase || useCase, targetBudget);

        // Transition UI to Focus Mode (Review & Customize)
        setWorkspaceMode('focus');
        
        if (selectedKey === 'legacy') {
            selectedServices['bios'] = BUILD_SERVICES['bios'];
            selectedServices['stress'] = BUILD_SERVICES['stress'];
            
            const biosCb = document.querySelector('.service-checkbox[value="bios"]');
            if (biosCb) biosCb.checked = true;
            
            const stressCb = document.querySelector('.service-checkbox[value="stress"]');
            if (stressCb) stressCb.checked = true;
            
            updateSummary();
            showToast(builderText('BIOS Update & Stress Test auto-recommended for legacy hardware.', 'BIOS Update & Stress Test auto-recommended for legacy hardware.'), 'warn');
        }
        
        // Scroll to wizard steps
        document.getElementById('wizardSteps')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showLegacyWarningModal(onConfirm) {
        let modal = document.getElementById('legacyWarningModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'legacyWarningModal';
            modal.className = 'custom-modal-backdrop';
            modal.innerHTML = `
                <div class="custom-modal-box">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <h3>Legacy Hardware Warning</h3>
                    <p>This build uses used server-grade components from 2016 (X99 platform). These parts lack modern features, have higher failure rates, and are NOT recommended for first-time builders.</p>
                    <p>You are choosing a <strong>3-month limited warranty</strong> over a standard 2-year modern warranty. Are you a technical enthusiast prepared to troubleshoot this hardware?</p>
                    <div class="modal-actions">
                        <button class="btn-cancel" id="btnLegacyCancel">I want a modern PC</button>
                        <button class="btn-proceed" id="btnLegacyProceed">I accept the risks</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Use a short timeout to allow DOM to register the element before animating
        setTimeout(() => modal.classList.add('show'), 10);
        
        modal.querySelector('#btnLegacyCancel').onclick = () => {
            modal.classList.remove('show');
            const baseBuildBtn = document.querySelector('.btn-start-build[data-case="esports"]');
            if (baseBuildBtn) {
                applyPreset('esports', baseBuildBtn);
                setTimeout(() => {
                    showToast(builderText('Welcome to Reliability: Base Build selected with full 2-year warranty.', 'Welcome to Reliability: Base Build selected with full 2-year warranty.'), 'success');
                }, 500);
            }
        };
        
        modal.querySelector('#btnLegacyProceed').onclick = () => {
            modal.classList.remove('show');
            onConfirm();
        };
    }

    function gameArtFallback(game) {
        const colors = GAME_ART_FALLBACKS[game.id] || ['#10131f', '#00f5d4', '#7c4dff'];
        return `radial-gradient(circle at 72% 18%, ${colors[1]}66, transparent 34%), linear-gradient(135deg, ${colors[0]}, ${colors[2]}88)`;
    }

    function gameArtStyle(game) {
        const fallback = gameArtFallback(game);
        const image = String(game.image || '').replace(/['"\\()]/g, '');
        return image ? `${fallback}, url('${image}')` : fallback;
    }

    function renderGamingFinder() {
        const gamesContainer = document.getElementById('finderGames');
        const budgetInput = document.getElementById('finderBudget');
        if (!gamesContainer || !budgetInput) return;

        gamesContainer.innerHTML = FINDER_GAMES.map(game => `
            <button class="gf-game ${finderState.games.includes(game.id) ? 'active' : ''}" data-game="${escapeHTML(game.id)}" type="button" aria-pressed="${finderState.games.includes(game.id) ? 'true' : 'false'}" style="--game-art:${gameArtStyle(game)}">
                <span class="gf-game-shade"></span>
                <span class="gf-game-content">
                    <i class="fas ${game.icon}"></i>
                    <span>${escapeHTML(game.name)}</span>
                    ${game.note ? `<small>${escapeHTML(game.note)}</small>` : ''}
                </span>
            </button>
        `).join('');

        gamesContainer.querySelectorAll('.gf-game').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.game;
                if (finderState.games.includes(id)) {
                    if (finderState.games.length === 1) {
                        showToast(builderText('Keep at least one game selected.', 'Keep at least one game selected.'), 'error');
                        return;
                    }
                    finderState.games = finderState.games.filter(gameId => gameId !== id);
                } else if (finderState.games.length < 4) {
                    finderState.games = [...finderState.games, id];
                } else {
                    showToast(builderText('Pick up to 4 games.', 'Pick up to 4 games.'), 'error');
                    return;
                }
                btn.classList.toggle('active', finderState.games.includes(id));
                btn.setAttribute('aria-pressed', finderState.games.includes(id) ? 'true' : 'false');
                syncFinderEstimator();
                updateFinderPreview();
            });
        });

        bindFinderSegment('finderResolution', 'resolution', 'resolution');
        bindFinderSegment('finderFps', 'targetFps', 'fps');

        budgetInput.value = finderState.budget;
        budgetInput.addEventListener('input', () => {
            finderState.budget = parseInt(budgetInput.value, 10) || targetBudget;
            updateFinderPreview();
        });

        updateFinderPreview();
    }

    function bindFinderSegment(containerId, stateKey, dataKey) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('button').forEach(btn => {
            const rawValue = btn.dataset[dataKey];
            const value = stateKey === 'targetFps' ? parseInt(rawValue, 10) : rawValue;
            btn.classList.toggle('active', finderState[stateKey] === value);
            btn.addEventListener('click', () => {
                finderState[stateKey] = value;
                container.querySelectorAll('button').forEach(button => button.classList.remove('active'));
                btn.classList.add('active');
                syncFinderEstimator();
                updateFinderPreview();
            });
        });
    }

    function getFinderDemand() {
        const selectedGames = FINDER_GAMES.filter(game => finderState.games.includes(game.id));
        if (!selectedGames.length) return 0.82;
        return Math.max(...selectedGames.map(game => game.demand));
    }

    function getFinderRecommendedBudget() {
        const demand = getFinderDemand();
        const demandCost = Math.max(0, (demand - 0.72) * 7000);
        const recommended = FINDER_RECOMMENDATION.baseBudget
            + (FINDER_RECOMMENDATION.resolution[finderState.resolution] || 0)
            + (FINDER_RECOMMENDATION.fps[finderState.targetFps] || 0)
            + demandCost;

        return Math.min(
            FINDER_RECOMMENDATION.maxBudget,
            Math.max(FINDER_RECOMMENDATION.minBudget, Math.round(recommended / 500) * 500)
        );
    }

    function getFinderTier(recommendedBudget) {
        if (finderState.budget >= recommendedBudget + 3500) {
            return { label: builderText('Performance headroom', 'Performance headroom'), tone: 'great', icon: 'fa-circle-check' };
        }
        if (finderState.budget >= recommendedBudget) {
            return { label: builderText('Good match', 'Good match'), tone: 'good', icon: 'fa-circle-check' };
        }
        if (finderState.budget >= recommendedBudget * 0.82) {
            return { label: builderText('Balanced with settings tweaks', 'Balanced with settings tweaks'), tone: 'warn', icon: 'fa-gauge-high' };
        }
        return { label: builderText('Budget is tight', 'Budget is tight'), tone: 'tight', icon: 'fa-triangle-exclamation' };
    }

    function getFinderEstimatedFps() {
        const gpu = selectedComponents.gpu;
        const cpu = selectedComponents.cpu;
        if (!gpu || typeof FPS_DATA === 'undefined') return null;

        const selectedGameIds = finderState.games.length
            ? finderState.games
            : FINDER_GAMES.slice(0, 3).map(game => game.id);
        const gpuBenchmarks = FPS_DATA.benchmarks[String(gpu.id)] || getFallbackGpuBenchmark(gpu);
        const cpuMultiplier = FPS_DATA.cpuTiers?.[String(cpu?.id)] || FPS_DATA.cpuTiers?.default || 0.85;

        const fpsValues = selectedGameIds.map(gameId => {
            const fallback = getFallbackGameFps(gpu, finderState.resolution);
            const base = gpuBenchmarks?.[gameId]?.[finderState.resolution] || fallback;
            return Math.max(25, Math.round(base * cpuMultiplier));
        });

        if (!fpsValues.length) return null;
        const average = Math.round(fpsValues.reduce((sum, fps) => sum + fps, 0) / fpsValues.length);
        return {
            average,
            low: Math.min(...fpsValues),
            meetsTarget: average >= finderState.targetFps,
        };
    }

    function getFallbackGpuBenchmark(gpu) {
        const price = Number(gpu?.price || 0);
        const tdp = extractWattage(gpu || {}, 'gpu');
        let scale = 0.75;
        if (price >= 14000 || tdp >= 400) scale = 1.18;
        else if (price >= 11000 || tdp >= 315) scale = 1.04;
        else if (price >= 7600 || tdp >= 280) scale = 0.88;
        else if (price >= 5400 || tdp >= 240) scale = 0.72;

        const base = {
            cyberpunk: { '1080p': 128, '1440p': 92, '4K': 55 },
            rdr2: { '1080p': 148, '1440p': 112, '4K': 72 },
            warzone: { '1080p': 154, '1440p': 118, '4K': 74 },
            fortnite: { '1080p': 205, '1440p': 168, '4K': 108 },
            valorant: { '1080p': 310, '1440p': 260, '4K': 185 },
            gta5: { '1080p': 185, '1440p': 165, '4K': 95 },
            wukong: { '1080p': 104, '1440p': 78, '4K': 46 },
            bg3: { '1080p': 168, '1440p': 126, '4K': 78 },
            starfield: { '1080p': 116, '1440p': 84, '4K': 52 },
            forza5: { '1080p': 172, '1440p': 132, '4K': 84 },
            helldivers2: { '1080p': 142, '1440p': 106, '4K': 66 },
            eldenring: { '1080p': 144, '1440p': 112, '4K': 72 },
            fc25: { '1080p': 210, '1440p': 168, '4K': 112 },
            minecraft: { '1080p': 260, '1440p': 220, '4K': 150 },
            rocketleague: { '1080p': 290, '1440p': 240, '4K': 165 },
            pragmata: { '1080p': 112, '1440p': 82, '4K': 50 },
            re4: { '1080p': 150, '1440p': 112, '4K': 72 },
            persona5: { '1080p': 240, '1440p': 200, '4K': 140 },
            efootball: { '1080p': 220, '1440p': 178, '4K': 118 },
        };

        return Object.fromEntries(Object.entries(base).map(([game, values]) => [
            game,
            Object.fromEntries(Object.entries(values).map(([resolution, fps]) => [resolution, Math.round(fps * scale)]))
        ]));
    }

    function getFallbackGameFps(gpu, resolution) {
        const price = Number(gpu?.price || 0);
        const resolutionPenalty = resolution === '4K' ? 0.48 : resolution === '1440p' ? 0.72 : 1;
        return Math.round(Math.max(45, price / 65) * resolutionPenalty);
    }

    function updateFinderPreview() {
        const result = document.getElementById('finderResult');
        const budgetValue = document.getElementById('finderBudgetValue');
        const status = document.getElementById('finderStatus');
        if (!result) return;

        const recommendedBudget = getFinderRecommendedBudget();
        const tier = getFinderTier(recommendedBudget);
        const fps = getFinderEstimatedFps();
        const selectedGameNames = FINDER_GAMES
            .filter(game => finderState.games.includes(game.id))
            .map(game => game.name);

        if (budgetValue) budgetValue.textContent = formatMAD(finderState.budget);
        if (status) status.textContent = `${finderState.resolution} / ${finderState.targetFps} FPS`;

        const budgetDelta = finderState.budget - recommendedBudget;
        const budgetText = budgetDelta >= 0
            ? `${formatMAD(budgetDelta)} ${builderText('above target', 'above target')}`
            : `${formatMAD(Math.abs(budgetDelta))} ${builderText('below target', 'below target')}`;

        result.innerHTML = `
            <div class="gf-result-top">
                <span class="gf-pill ${tier.tone}"><i class="fas ${tier.icon}"></i> ${tier.label}</span>
                <strong>${formatMAD(recommendedBudget)}</strong>
            </div>
            <div class="gf-result-title">${selectedGameNames.length ? selectedGameNames.join(' + ') : builderText('Gaming profile', 'Gaming profile')}</div>
            <div class="gf-metrics">
                <div>
                    <span>${builderText('Budget fit', 'Budget fit')}</span>
                    <strong>${budgetText}</strong>
                </div>
                <div>
                    <span>${builderText('FPS estimate', 'FPS estimate')}</span>
                    <strong>${fps ? `${fps.average} ${builderText('FPS avg', 'FPS avg')}` : builderText('Build pending', 'Build pending')}</strong>
                </div>
                <div>
                    <span>${builderText('1% low', '1% low')}</span>
                    <strong>${fps ? `${fps.low} FPS` : builderText('Select build', 'Select build')}</strong>
                </div>
            </div>
            <div class="gf-note ${fps && !fps.meetsTarget ? 'warn' : ''}">
                ${fps
                    ? (fps.meetsTarget
                        ? builderTemplate('The current build is on pace for {resolution} at {fps} FPS.', 'The current build is on pace for {resolution} at {fps} FPS.', { resolution: finderState.resolution, fps: finderState.targetFps })
                        : builderTemplate('The current build may need a stronger GPU or lower settings for {fps} FPS.', 'The current build may need a stronger GPU or lower settings for {fps} FPS.', { fps: finderState.targetFps }))
                    : builderText('Run the finder to auto-select compatible parts from your catalog.', 'Run the finder to auto-select compatible parts from your catalog.')}
            </div>
        `;
    }

    function syncFinderEstimator() {
        if (typeof FPSEstimator !== 'undefined') {
            if (FPSEstimator.setResolution) FPSEstimator.setResolution(finderState.resolution);
            if (FPSEstimator.setGames) FPSEstimator.setGames(finderState.games);
        }
    }

    function applyGamingFinder() {
        useCase = 'gaming';
        activePreset = 'finder';
        targetBudget = finderState.budget;

        const recommendedBudget = getFinderRecommendedBudget();
        const buildBudget = Math.max(finderState.budget, Math.round(recommendedBudget * 0.9 / 500) * 500);
        autoBuild('gaming', Math.min(FINDER_RECOMMENDATION.maxBudget, buildBudget), false);

        // Transition UI to Focus Mode (Review & Customize)
        setWorkspaceMode('focus');

        buildName = `${finderState.resolution} Gaming Build`;
        const nameInput = document.getElementById('buildNameInput');
        if (nameInput) nameInput.value = buildName;

        syncFinderEstimator();
        updateFinderPreview();
        showToast(builderTemplate('Finder matched a {resolution} gaming build.', 'Finder matched a {resolution} gaming build.', {resolution: finderState.resolution}), 'success');

        // Automatically switch back to PC Builder and open the workspace
        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function resetGamingFinder() {
        finderState = {
            games: ['cyberpunk', 'gta5'],
            resolution: '1440p',
            targetFps: 120,
            budget: 18000,
        };
        renderGamingFinder();
        syncFinderEstimator();
        showToast(builderText('Finder reset.', 'Finder reset.'), 'success');
    }

    function renderBuilderToolPanels() {
        populateProductSelect('psuCpuSelect', 'cpu', builderText('Select CPU', 'Select CPU'));
        populateProductSelect('psuGpuSelect', 'gpu', builderText('Select GPU', 'Select GPU'));
        populateProductSelect('memoryMotherboardSelect', 'motherboard', builderText('Auto / not sure', 'Auto / not sure'));

        [
            'psuCpuSelect', 'psuGpuSelect', 'psuMotherboardSelect', 'psuRamSelect',
            'psuSsdCount', 'psuHddCount', 'psuFanCount', 'psuHeadroomSelect'
        ].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', updatePowerSupplyCalculator);
        });

        ['memoryPlatformSelect', 'memoryMotherboardSelect', 'memoryUseSelect', 'memoryCapacitySelect'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', updateMemoryFinder);
        });

        updatePowerSupplyCalculator();
        updateMemoryFinder();
    }

    function populateProductSelect(selectId, category, placeholder) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const options = allProducts
            .filter(product => product.category === category && product.inStock)
            .sort((a, b) => a.price - b.price)
            .map(product => `<option value="${product.id}">${product.name}</option>`)
            .join('');
        select.innerHTML = `<option value="">${placeholder}</option>${options}`;
    }

    function selectedProductFrom(selectId) {
        const value = parseInt(document.getElementById(selectId)?.value || '', 10);
        return value ? allProducts.find(product => product.id === value) || null : null;
    }

    async function updatePowerSupplyCalculator() {
        const result = document.getElementById('psuCalculatorResult');
        if (!result) return;
        const cpu = selectedProductFrom('psuCpuSelect');
        const gpu = selectedProductFrom('psuGpuSelect');
        const motherboardW = parseInt(document.getElementById('psuMotherboardSelect')?.value || '0', 10);
        const ramW = parseInt(document.getElementById('psuRamSelect')?.value || '0', 10);
        const ssdCount = clampNumber(document.getElementById('psuSsdCount')?.value, 0, 8);
        const hddCount = clampNumber(document.getElementById('psuHddCount')?.value, 0, 8);
        const fanCount = clampNumber(document.getElementById('psuFanCount')?.value, 0, 12);
        const headroom = parseFloat(document.getElementById('psuHeadroomSelect')?.value || '1.25');
        
        let componentLoad = motherboardW + ramW + (ssdCount * 8) + (hddCount * 12) + (fanCount * 4);
        let recommended = 0;
        let clearanceIssues = [];

        // Fetch precise wattage and clearance from backend engine
        const productIds = [];
        if (cpu) productIds.push(cpu.id);
        if (gpu) productIds.push(gpu.id);
        if (selectedComponents.motherboard) productIds.push(selectedComponents.motherboard.id);
        if (selectedComponents.cooling) productIds.push(selectedComponents.cooling.id);
        if (selectedComponents.case) productIds.push(selectedComponents.case.id);
        
        if (productIds.length > 0) {
            try {
                const res = await fetch('api/builder-logic.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'validate', products: productIds })
                });
                const data = await res.json();
                
                if (data.success && data.wattage && data.wattage.total_tdp > 0) {
                    componentLoad += data.wattage.total_tdp;
                    const backendRecommended = data.wattage.recommended_psu || 0;
                    recommended = Math.max(backendRecommended, getNearestPsuTier(componentLoad * headroom));
                    clearanceIssues = data.clearance_issues || [];
                } else {
                    // DB not populated yet or validation failed silently -> fallback to client math
                    componentLoad += (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0);
                    recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
                }
            } catch (e) {
                console.error("Backend validation failed", e);
                // Fallback to client math
                componentLoad += (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0);
                recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
            }
        } else {
            recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
        }

        /* ORIGINAL CODE:
        const componentLoad = (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0) + motherboardW + ramW + (ssdCount * 8) + (hddCount * 12) + (fanCount * 4);
        const recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
        */

        const amps = recommended ? Math.ceil(recommended / 12) : 0;
        const matchingPsus = recommended ? allProducts
            .filter(product => product.category === 'psu' && product.inStock && extractWattage(product, 'psu') >= recommended)
            .sort((a, b) => a.price - b.price || (b.rating || 0) - (a.rating || 0))
            .slice(0, 3) : [];

        let clearanceHtml = '';
        if (clearanceIssues.length > 0) {
            clearanceHtml = `<div class="tool-note" style="color:var(--danger); border-left:3px solid var(--danger); padding-left:10px;">
                <strong><i class="fas fa-exclamation-triangle"></i> ${builderText('Compatibility Issues Detected:', 'Compatibility Issues Detected:')}</strong><br>
                ${clearanceIssues.join('<br>')}
            </div>`;
        }

        result.innerHTML = `
            <div class="tool-result-top">
                <span class="gf-pill ${recommended ? 'good' : 'warn'}"><i class="fas fa-bolt"></i> ${builderText('Suggested PSU', 'Suggested PSU')}</span>
                <strong>${recommended ? `${recommended}${window.__marocPcPhraseMap?.['W'] || 'W'}+` : builderText('Select parts', 'Select parts')}</strong>
            </div>
            ${clearanceHtml}
            <div class="tool-meter"><span style="width:${recommended ? Math.min(100, (componentLoad / recommended) * 100) : 0}%"></span></div>
            <div class="tool-metrics">
                <div><span>${builderText('Estimated load', 'Estimated load')}</span><strong>${componentLoad}${window.__marocPcPhraseMap?.['W'] || 'W'}</strong></div>
                <div><span>${builderText('12V rail target', '12V rail target')}</span><strong>${amps ? `${amps}A+` : '-'}</strong></div>
                <div><span>${builderText('Headroom', 'Headroom')}</span><strong>${Math.round((headroom - 1) * 100)}%</strong></div>
            </div>
            <div class="tool-suggestions">
                <h4>${builderText('Matching PSUs', 'Matching PSUs')}</h4>
                ${matchingPsus.length ? matchingPsus.map(product => `
                    <div class="tool-product-card">
                        <button class="tool-product" onclick="PCBuilder.applyPsuChoice(${product.id})" title="${builderText('Select as PSU for current build', 'Select as PSU for current build')}">
                            <img src="${productImage(product)}" alt="${product.name}">
                            <span><strong>${product.name}</strong><em>${formatMAD(product.price)} - ${extractWattage(product, 'psu')}W</em></span>
                        </button>
                        <button class="tool-product-cart-btn" onclick="PCBuilder.addSingleToCart(${product.id})" title="${builderText('addToCart', 'Add to Cart')}" aria-label="${builderText('addToCart', 'Add to Cart')}">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                `).join('') : `<p>${builderText('Select CPU/GPU details to see compatible PSUs.', 'Select CPU/GPU details to see compatible PSUs.')}</p>`}
            </div>
        `;
    }

    function useCurrentBuildForPsu() {
        setSelectValue('psuCpuSelect', selectedComponents.cpu?.id || '');
        setSelectValue('psuGpuSelect', selectedComponents.gpu?.id || '');
        const boardFactor = String(selectedComponents.motherboard?.specs?.['Form Factor'] || '').toUpperCase();
        const boardWatts = boardFactor.includes('MINI') ? '45' : boardFactor.includes('MICRO') ? '55' : boardFactor.includes('E-ATX') ? '80' : selectedComponents.motherboard ? '65' : '0';
        setSelectValue('psuMotherboardSelect', boardWatts);
        const ramCapacity = getCapacityGB(selectedComponents.ram);
        const ramType = getMemoryType(selectedComponents.ram);
        const ramWatts = ramCapacity >= 128 ? '28' : ramCapacity >= 64 ? '18' : ramType === 'DDR5' ? '12' : ramCapacity ? '10' : '0';
        setSelectValue('psuRamSelect', ramWatts);
        setInputValue('psuSsdCount', selectedComponents.storage ? 1 : 0);
        updatePowerSupplyCalculator();
        showToast(builderText('Power calculator synced with your current build.', 'Power calculator synced with your current build.'), 'success');
    }

    function addSingleToCart(productId) {
        const product = allProducts.find(item => item.id === productId);
        if (!product) return;
        if (typeof Cart !== 'undefined' && Cart.add) {
            Cart.add(product);
            showToast(builderTemplate('cartAddedTemplate', '{name} added to cart!', { name: product.name }), 'success');
        } else {
            showToast(builderText('cartCouldNotAdd', 'This product could not be added. Please refresh and try again.'), 'error');
        }
    }

    function applyPsuChoice(productId) {
        const product = allProducts.find(item => item.id === productId && item.category === 'psu');
        if (!product) return;
        selectedComponents.psu = product;
        currentStep = STEPS.findIndex(step => step.key === 'psu');
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(builderTemplate('{name} added as your PSU.', '{name} added as your PSU.', {name: product.name}), 'success');

        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function updateMemoryFinder() {
        const result = document.getElementById('memoryFinderResult');
        if (!result) return;
        const platform = document.getElementById('memoryPlatformSelect')?.value || '';
        const board = selectedProductFrom('memoryMotherboardSelect');
        const workload = document.getElementById('memoryUseSelect')?.value || 'gaming';
        const minCapacity = parseInt(document.getElementById('memoryCapacitySelect')?.value || '0', 10);
        const requiredType = getRequiredMemoryType(platform, board);
        const workloadCapacity = workload === 'creator' ? 64 : workload === 'streaming' ? 32 : workload === 'office' ? 16 : 32;
        const capacityTarget = Math.max(minCapacity, workloadCapacity);
        const matches = allProducts
            .filter(product => product.category === 'ram' && product.inStock)
            .filter(product => !requiredType || getMemoryType(product) === requiredType)
            .filter(product => !capacityTarget || getCapacityGB(product) >= capacityTarget)
            .sort((a, b) => {
                const aCapacity = getCapacityGB(a);
                const bCapacity = getCapacityGB(b);
                if (aCapacity !== bCapacity) return aCapacity - bCapacity;
                return a.price - b.price || (b.rating || 0) - (a.rating || 0);
            })
            .slice(0, 4);

        result.innerHTML = `
            <div class="tool-result-top">
                <span class="gf-pill ${requiredType ? 'good' : 'warn'}"><i class="fas fa-memory"></i> ${requiredType || builderText('DDR4 / DDR5', 'DDR4 / DDR5')}</span>
                <strong>${capacityTarget || 16}GB+</strong>
            </div>
            <div class="tool-note">${requiredType ? builderTemplate('{type} memory is recommended for this platform.', '{type} memory is recommended for this platform.', {type: requiredType}) : builderText('Select a platform or motherboard to narrow compatibility.', 'Select a platform or motherboard to narrow compatibility.')}</div>
            <div class="tool-suggestions memory-picks">
                <h4>${builderText('Compatible RAM', 'Compatible RAM')}</h4>
                ${matches.length ? matches.map(product => `
                    <div class="tool-product-card">
                        <button class="tool-product" onclick="PCBuilder.applyMemoryChoice(${product.id})" title="${builderText('Select as RAM for current build', 'Select as RAM for current build')}">
                            <img src="${productImage(product)}" alt="${product.name}">
                            <span><strong>${product.name}</strong><em>${getCapacityGB(product)}GB - ${getMemoryType(product) || builderText('Memory', 'Memory')} - ${formatMAD(product.price)}</em></span>
                        </button>
                        <button class="tool-product-cart-btn" onclick="PCBuilder.addSingleToCart(${product.id})" title="${builderText('addToCart', 'Add to Cart')}" aria-label="${builderText('addToCart', 'Add to Cart')}">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                `).join('') : `<p>${builderText('No RAM in catalog matches those filters yet.', 'No RAM in catalog matches those filters yet.')}</p>`}
            </div>
        `;
    }

    function useCurrentBuildForMemory() {
        const cpuSocket = getSocket(selectedComponents.cpu);
        setSelectValue('memoryPlatformSelect', cpuSocket.includes('AM5') ? 'AM5' : cpuSocket.includes('LGA 1700') ? 'LGA 1700' : '');
        setSelectValue('memoryMotherboardSelect', selectedComponents.motherboard?.id || '');
        updateMemoryFinder();
        showToast(builderText('Memory finder synced with your current build.', 'Memory finder synced with your current build.'), 'success');
    }

    function applyMemoryChoice(productId) {
        const product = allProducts.find(item => item.id === productId && item.category === 'ram');
        if (!product) return;
        selectedComponents.ram = product;
        currentStep = STEPS.findIndex(step => step.key === 'ram');
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(builderTemplate('{name} added as your RAM.', '{name} added as your RAM.', {name: product.name}), 'success');

        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function getRequiredMemoryType(platform, board) {
        const boardMemory = getMemoryType(board);
        if (boardMemory) return boardMemory;
        if (platform === 'AM5') return 'DDR5';
        return '';
    }

    function getCapacityGB(product) {
        const text = String(product?.specs?.Capacity || product?.name || '');
        const match = text.match(/(\d+)\s*GB/i);
        return match ? parseInt(match[1], 10) : 0;
    }

    function getNearestPsuTier(watts) {
        const tiers = [450, 550, 650, 750, 850, 1000, 1200, 1500];
        return tiers.find(tier => tier >= watts) || Math.ceil(watts / 100) * 100;
    }

    function clampNumber(value, min, max) {
        const parsed = parseInt(value || min, 10);
        return Math.min(max, Math.max(min, Number.isFinite(parsed) ? parsed : min));
    }

    function setSelectValue(id, value) {
        const input = document.getElementById(id);
        if (input) input.value = String(value);
    }

    function setInputValue(id, value) {
        const input = document.getElementById(id);
        if (input) input.value = String(value);
    }

    function getStepBudgetWeight(stepKey) {
        const weights = {
            cpu: useCase === 'budget' ? 0.12 : useCase === 'editing' ? 0.28 : 0.22,
            motherboard: useCase === 'budget' ? 0.17 : 0.12,
            gpu: useCase === 'budget' ? 0.32 : useCase === 'office' ? 0.18 : useCase === 'editing' ? 0.30 : 0.42,
            ram: useCase === 'budget' ? 0.11 : useCase === 'editing' || useCase === 'streaming' ? 0.14 : 0.10,
            storage: useCase === 'budget' ? 0.09 : 0.10,
            psu: useCase === 'budget' ? 0.12 : 0.09,
            cooling: useCase === 'budget' ? 0.04 : 0.07,
            monitor: 0.20, // Baseline weight relative to the PC budget
        };
        return weights[stepKey] || 0.12;
    }

    function getProductSearchText(product) {
        return [
            product.name,
            product.brand,
            product.category,
            ...Object.values(product.specs || {}),
        ].join(' ').toLowerCase();
    }

    function getRecommendedScore(product, stepKey, target) {
        const compat = checkCompatibility(stepKey, product);
        const selectedBoost = selectedComponents[stepKey]?.id === product.id ? 5000 : 0;
        const stockBoost = product.inStock ? 2500 : -2500;
        const compatBoost = compat.compatible ? 2500 : -4000;
        const priceFit = -Math.abs(Number(product.price || 0) - target) / 10;
        const ratingBoost = Number(product.rating || 0) * 350;
        return selectedBoost + stockBoost + compatBoost + ratingBoost + priceFit;
    }

    function getFilteredProducts(products, stepKey) {
        const query = componentFilters.query.trim().toLowerCase();
        const target = targetBudget * getStepBudgetWeight(stepKey);
        
        let filtered = products;

        // Apply Price-Tier Filter Pill
        if (activePriceTier === 'budget') {
            filtered = filtered.filter(p => p.price <= target * 0.85);
        } else if (activePriceTier === 'sweet') {
            filtered = filtered.filter(p => p.price >= target * 0.85 && p.price <= target * 1.15);
        } else if (activePriceTier === 'performance') {
            filtered = filtered.filter(p => p.price > target * 1.15);
        }

        return filtered
            .filter(product => !componentFilters.stockOnly || product.inStock)
            .filter(product => !query || getProductSearchText(product).includes(query))
            .sort((a, b) => {
                if (componentFilters.sort === 'price-asc') return a.price - b.price;
                if (componentFilters.sort === 'price-desc') return b.price - a.price;
                if (componentFilters.sort === 'wattage') return extractWattage(a, stepKey) - extractWattage(b, stepKey);
                return getRecommendedScore(b, stepKey, target) - getRecommendedScore(a, stepKey, target);
            });
    }

    function getComponentFilterLabel(totalCount, visibleCount, stepLabel) {
        const translatedLabel = builderText(stepLabel, stepLabel);
        if (componentFilters.query) {
            return builderTemplate('{visible} of {total} {label} matches', '{visible} of {total} {label} matches', {
                visible: visibleCount,
                total: totalCount,
                label: translatedLabel
            });
        }
        return builderTemplate('{count} {label} options', '{count} {label} options', {
            count: visibleCount,
            label: translatedLabel
        });
    }

    // ── Render Current Step Components ────────────────────────
    function renderCurrentStep() {
        const panel = document.getElementById('componentPanel');
        if (!panel) return;

        const step = STEPS[currentStep];
        const allForStep = allProducts.filter(p => p.category === step.category);
        const stepTarget = targetBudget * getStepBudgetWeight(step.key);

        // Reset price tier pill filter if active step has changed
        if (lastRenderedStep !== currentStep) {
            activePriceTier = 'all';
            lastRenderedStep = currentStep;
        }

        const filtered = getFilteredProducts(allForStep, step.key);
        const visibleLabel = getComponentFilterLabel(allForStep.length, filtered.length, step.label);

        let panelHTML = `
            <div class="component-panel-head">
                <div>
                    <h2><i class="fas ${step.icon}"></i> ${builderTemplate('Select {label}', 'Select {label}', {label: builderText(step.label, step.label)})} ${step.optional ? `<small style="color:var(--muted);font-family:'Space Mono',monospace;font-size:0.72rem;text-transform:uppercase;">${builderText('Optional', 'Optional')}</small>` : ''}</h2>
                    <p class="panel-desc">${builderTemplate('Choose a {label} for your build.', 'Choose a {label} for your build.', {label: builderText(step.label, step.label).toLowerCase()})} ${builderText(getStepHint(step.key), getStepHint(step.key))}</p>
                </div>
                <div class="step-budget-chip">
                    <span>${builderText('Target', 'Target')}</span>
                    <strong>${formatMAD(stepTarget)}</strong>
                </div>
            </div>

            <!-- Dynamic Price-Tier Pills -->
            <div class="price-tier-pills">
                <button class="tier-pill ${activePriceTier === 'all' ? 'active' : ''}" onclick="PCBuilder.selectPriceTier('all')">
                    ${builderText('All', 'All')}
                </button>
                <button class="tier-pill ${activePriceTier === 'budget' ? 'active' : ''}" onclick="PCBuilder.selectPriceTier('budget')">
                    ${builderText('Budget Fit', 'Budget Fit')} (&le; 85%)
                </button>
                <button class="tier-pill ${activePriceTier === 'sweet' ? 'active' : ''}" onclick="PCBuilder.selectPriceTier('sweet')">
                    ${builderText('Sweet Spot', 'Sweet Spot')} (85% - 115%)
                </button>
                <button class="tier-pill ${activePriceTier === 'performance' ? 'active' : ''}" onclick="PCBuilder.selectPriceTier('performance')">
                    ${builderText('Performance Peak', 'Performance Peak')} (&gt; 115%)
                </button>
            </div>

            <div class="component-toolbar">
                <label class="component-search">
                    <i class="fas fa-search"></i>
                    <input type="search" id="componentSearchInput" placeholder="${builderTemplate('Search {label}...', 'Search {label}...', {label: builderText(step.label, step.label).toLowerCase()})}" value="${escapeHTML(componentFilters.query)}">
                </label>
                <label class="component-select">
                    <span>${builderText('Sort', 'Sort')}</span>
                    <select id="componentSortSelect">
                        <option value="recommended" ${componentFilters.sort === 'recommended' ? 'selected' : ''}>${builderText('Recommended', 'Recommended')}</option>
                        <option value="price-asc" ${componentFilters.sort === 'price-asc' ? 'selected' : ''}>${builderText('Price: low to high', 'Price: low to high')}</option>
                        <option value="price-desc" ${componentFilters.sort === 'price-desc' ? 'selected' : ''}>${builderText('Price: high to low', 'Price: high to low')}</option>
                        <option value="wattage" ${componentFilters.sort === 'wattage' ? 'selected' : ''}>${builderText('Lowest wattage', 'Lowest wattage')}</option>
                    </select>
                </label>
                <label class="stock-toggle">
                    <input type="checkbox" id="stockOnlyToggle" ${componentFilters.stockOnly ? 'checked' : ''}>
                    <span>${builderText('In stock only', 'In stock only')}</span>
                </label>
                <span class="component-count">${visibleLabel}</span>
            </div>
        `;

        if (allForStep.length === 0) {
            panelHTML += `
                <div class="empty-step">
                    <i class="fas ${step.icon}"></i>
                    <p>${builderTemplate('No {label} products available in catalog.', 'No {label} products available in catalog.', {label: builderText(step.label, step.label)})}</p>
                </div>
            `;
        } else if (filtered.length === 0) {
            panelHTML += `
                <div class="empty-step">
                    <i class="fas fa-filter-circle-xmark"></i>
                    <p>${builderTemplate('No {label} products match the current filters.', 'No {label} products match the current filters.', {label: builderText(step.label, step.label)})}</p>
                    <button class="btn-build btn-save-build" id="clearComponentFiltersBtn" type="button">${builderText('Clear filters', 'Clear filters')}</button>
                </div>
            `;
        } else {
            panelHTML += '<div class="component-grid">';
            filtered.forEach(product => {
                const isSelected = selectedComponents[step.key]?.id === product.id;
                const compat = checkCompatibility(step.key, product);
                let cls = 'component-card';
                if (isSelected) cls += ' selected';
                if (!compat.compatible) cls += ' incompatible';

                const wattage = extractWattage(product, step.key);
                const specTags = Object.entries(product.specs || {}).slice(0, 3).map(([k, v]) =>
                    `<span class="cc-spec-tag" dir="auto">${translateSpec(v)}</span>`
                ).join('');
                const recommended = compat.compatible && product.inStock && Math.abs(product.price - stepTarget) <= stepTarget * 0.35;

                panelHTML += `
                    <div class="${cls}" data-product-id="${product.id}" role="button" tabindex="${compat.compatible && product.inStock ? '0' : '-1'}" aria-pressed="${isSelected}">
                        ${recommended ? `<div class="cc-badge">${builderText('Recommended fit', 'Recommended fit')}</div>` : ''}
                        <img src="${productImage(product)}" alt="${product.name}" class="cc-image" onerror="this.src='images/products/placeholder-storage.svg'">
                        <div class="cc-brand">${product.brand || ''}</div>
                        <div class="cc-name" dir="ltr">${product.name}</div>
                        <div class="cc-specs">${specTags}</div>
                        ${!product.inStock ? `<div class="cc-out-of-stock"><i class="fas fa-ban"></i> ${builderText('Out of stock', 'Out of stock')}</div>` : ''}
                        <div class="cc-price">${formatMAD(product.price)}</div>
                        ${wattage > 0 ? `<div class="cc-wattage" dir="auto"><i class="fas fa-bolt"></i> ~${wattage} ${window.__marocPcPhraseMap?.['W'] || 'W'}</div>` : ''}
                        ${!compat.compatible ? `<div class="cc-compat-warn"><i class="fas fa-exclamation-triangle"></i> ${builderText(compat.reason, compat.reason)}</div>` : ''}
                        <span class="cc-action">${isSelected ? builderText('Selected', 'Selected') : compat.compatible && product.inStock ? builderText('Select part', 'Select part') : builderText('Unavailable', 'Unavailable')}</span>
                    </div>
                `;
            });
            panelHTML += '</div>';
        }

        panelHTML += `
            <div class="step-nav-actions">
                <button class="btn-build btn-save-build" id="prevStepBtn" ${currentStep === 0 ? 'disabled style="opacity:0.3"' : ''}>
                    <i class="fas fa-arrow-left"></i> ${builderText('Previous', 'Previous')}
                </button>
                <button class="btn-build btn-add-all" id="nextStepBtn" ${currentStep === STEPS.length - 1 ? 'disabled style="opacity:0.3"' : ''}>
                    ${builderText('Next', 'Next')} <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        `;

        panel.innerHTML = panelHTML;

        const searchInput = document.getElementById('componentSearchInput');
        const sortSelect = document.getElementById('componentSortSelect');
        const stockToggle = document.getElementById('stockOnlyToggle');
        const clearFiltersBtn = document.getElementById('clearComponentFiltersBtn');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                componentFilters.query = searchInput.value;
                renderCurrentStep();
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                componentFilters.sort = sortSelect.value;
                renderCurrentStep();
            });
        }
        if (stockToggle) {
            stockToggle.addEventListener('change', () => {
                componentFilters.stockOnly = stockToggle.checked;
                renderCurrentStep();
            });
        }
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                componentFilters = { query: '', sort: 'recommended', stockOnly: true };
                renderCurrentStep();
            });
        }

        function handleProductSelect(card) {
            const prodId = parseInt(card.dataset.productId);
            const product = allProducts.find(p => p.id === prodId);
            if (!product || !product.inStock) return;

            const compat = checkCompatibility(step.key, product);
            if (!compat.compatible) return;

            if (selectedComponents[step.key]?.id === prodId) {
                delete selectedComponents[step.key];
                showToast(builderTemplate('{label} removed.', '{label} removed.', {label: builderText(step.label, step.label)}), 'success');
            } else {
                selectedComponents[step.key] = product;
                showToast(builderTemplate('{name} selected.', '{name} selected.', {name: product.name}), 'success');
            }

            renderWizardSteps();
            renderCurrentStep();
            updateSummary();
        }

        panel.querySelectorAll('.component-card').forEach(card => {
            card.addEventListener('click', () => handleProductSelect(card));
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    handleProductSelect(card);
                }
            });
        });

        const prevBtn = document.getElementById('prevStepBtn');
        const nextBtn = document.getElementById('nextStepBtn');
        if (prevBtn) prevBtn.addEventListener('click', () => { if (currentStep > 0) { currentStep--; renderWizardSteps(); renderCurrentStep(); updateBuildGuide(); } });
        if (nextBtn) nextBtn.addEventListener('click', () => { if (currentStep < STEPS.length - 1) { currentStep++; renderWizardSteps(); renderCurrentStep(); updateBuildGuide(); } });

        updateBuildGuide();
    }

    // ── Step Hints ───────────────────────────────────────────
    function getStepHint(key) {
        const hints = {
            cpu: 'The brain of your PC. Choose based on your workload.',
            motherboard: 'Match the board socket and memory type to your CPU and RAM.',
            gpu: 'Critical for gaming and creative work.',
            ram: 'More RAM means better multitasking.',
            storage: 'Fast NVMe SSDs for quick boot and load times.',
            psu: 'Ensure enough wattage for all components.',
            cooling: 'Keep your system cool and quiet.',
            monitor: 'The window to your PC. Match resolution and refresh rate to your GPU.',
            accessories: 'Optional add-ons for assembly, cable routing, and finishing touches.',
            keyboard: 'Optional keyboard add-on; pick one only if you want it in the quote.',
            mouse: 'Optional mouse add-on; pick one only if you want it in the quote.',
        };
        return hints[key] || '';
    }

    // ── Compatibility Check ──────────────────────────────────
    function checkCompatibility(stepKey, product) {
        const result = { compatible: true, reason: '' };

        // Basic out-of-stock check
        if (!product.inStock) {
            return { compatible: false, reason: 'Out of stock' };
        }

        if (stepKey === 'cpu' && selectedComponents['motherboard']) {
            const cpuSocket = getSocket(product);
            const boardSocket = getSocket(selectedComponents['motherboard']);
            if (cpuSocket && boardSocket && cpuSocket !== boardSocket) {
                return { compatible: false, reason: builderTemplate('Requires {socket}', 'Requires {socket}', {socket: boardSocket}) };
            }
        }

        if (stepKey === 'motherboard') {
            const boardSocket = getSocket(product);
            const boardMemory = getMemoryType(product);

            if (selectedComponents['cpu']) {
                const cpuSocket = getSocket(selectedComponents['cpu']);
                if (cpuSocket && boardSocket && cpuSocket !== boardSocket) {
                    return { compatible: false, reason: builderTemplate('{socket} CPU needs {socket} board', '{socket} CPU needs {socket} board', {socket: cpuSocket}) };
                }
            }

            if (selectedComponents['ram']) {
                const ramType = getMemoryType(selectedComponents['ram']);
                if (ramType && boardMemory && ramType !== boardMemory) {
                    return { compatible: false, reason: `${boardMemory} board needs ${boardMemory} RAM` };
                }
            }
        }

        // CPU + RAM socket compatibility (simplified)
        if (stepKey === 'cpu' && selectedComponents['ram']) {
            const cpuSocket = product.specs?.Socket || '';
            const ramSpeed = selectedComponents['ram'].specs?.Speed || '';
            if (cpuSocket.includes('AM5') && ramSpeed.includes('DDR4')) {
                return { compatible: false, reason: 'AM5 requires DDR5' };
            }
        }

        if (stepKey === 'ram' && selectedComponents['cpu']) {
            const cpuSocket = selectedComponents['cpu'].specs?.Socket || '';
            const ramSpeed = product.specs?.Speed || '';
            // AM5 requires DDR5, LGA 1700 supports DDR4/DDR5
            if (cpuSocket.includes('AM5') && ramSpeed.includes('DDR4')) {
                return { compatible: false, reason: 'AM5 requires DDR5' };
            }
        }

        if (stepKey === 'ram' && selectedComponents['motherboard']) {
            const boardMemory = getMemoryType(selectedComponents['motherboard']);
            const ramType = getMemoryType(product);
            if (ramType && boardMemory && ramType !== boardMemory) {
                return { compatible: false, reason: `${boardMemory} motherboard requires ${boardMemory} RAM` };
            }
        }

        // PSU wattage check
        if (stepKey === 'psu') {
            const totalWattage = calculateTotalWattage(true); // exclude PSU
            const psuWattage = extractWattageFromSpec(product.specs?.Wattage || '');
            if (psuWattage > 0 && psuWattage < totalWattage * 1.1) {
                return { compatible: false, reason: `Need ${Math.ceil(totalWattage * 1.2)}W+ PSU` };
            }
        }

        if (stepKey === 'cooling' && selectedComponents['cpu']) {
            const cpuTdp = extractWattage(selectedComponents['cpu'], 'cpu');
            const coolerTdp = extractWattageFromSpec(product.specs?.['Max TDP'] || '');
            if (coolerTdp > 0 && coolerTdp < cpuTdp) {
                return { compatible: false, reason: `Need ${cpuTdp}W+ cooling` };
            }
        }

        return result;
    }

    // ── Wattage Extraction ───────────────────────────────────
    function getSocket(product) {
        return String(product?.specs?.Socket || '').trim().toUpperCase();
    }

    function getMemoryType(product) {
        const specs = product?.specs || {};
        const memoryText = `${specs.Memory || ''} ${specs.Speed || ''}`.toUpperCase();
        if (memoryText.includes('DDR5')) return 'DDR5';
        if (memoryText.includes('DDR4')) return 'DDR4';
        return '';
    }

    function extractWattage(product, stepKey) {
        // Try extracting from specs
        const specs = product.specs || {};
        const tdp = specs.TDP || specs.Wattage || '';
        const val = extractWattageFromSpec(tdp);
        return val || DEFAULT_WATTAGE[stepKey] || 0;
    }

    function extractWattageFromSpec(specStr) {
        if (!specStr) return 0;
        const match = String(specStr).replace(/[,\s]/g, '').match(/(\d+)\s*W/i);
        return match ? parseInt(match[1]) : 0;
    }

    // ── Calculate Total Wattage ──────────────────────────────
    function calculateTotalWattage(excludePSU = false) {
        let total = 0;
        for (const [key, product] of Object.entries(selectedComponents)) {
            if (excludePSU && key === 'psu') continue;
            total += extractWattage(product, key);
        }
        return total;
    }

    function calculateServiceTotal() {
        return Object.keys(selectedServices).reduce((sum, key) => {
            return sum + (BUILD_SERVICES[key]?.price || 0);
        }, 0);
    }

    function bindServiceOptions() {
        document.querySelectorAll('.service-checkbox').forEach(input => {
            input.addEventListener('change', () => {
                const service = BUILD_SERVICES[input.value];
                if (!service) return;
                if (input.checked) selectedServices[input.value] = service;
                else delete selectedServices[input.value];
                updateSummary();
            });
        });
    }

    function chooseBuilderPath(path, notify = true) {
        builderPath = path === 'china' ? 'china' : path === 'prebuilt' ? 'prebuilt' : 'custom';
        const workspace = document.getElementById('pcBuilderWorkspace');
        const startChoice = document.getElementById('buildStartChoice');
        if (workspace) {
            workspace.classList.remove('is-hidden', 'path-prebuilt', 'path-custom', 'path-china');
            workspace.classList.add(`path-${builderPath}`);
        }
        if (startChoice) startChoice.classList.add('is-minimized');

        if (builderPath === 'china') {
            selectedPlatform = 'x99';
            useCase = 'budget';
            activePreset = 'cnultra';
            targetBudget = 4200;
            document.querySelectorAll('.ps-card').forEach(card => {
                card.classList.toggle('active', card.dataset.platform === 'x99');
            });
            renderUseCaseBar();
            autoBuild('budget', targetBudget, false);
        }

        const target = builderPath === 'prebuilt'
            ? document.getElementById('useCaseBar')
            : builderPath === 'china'
                ? document.getElementById('buildGuideBar')
            : document.querySelector('.platform-selector');
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        updateBuildGuide();
        if (notify) {
            const message = builderPath === 'china'
                ? builderText('Ultra cheap CN value build prepared.', 'Ultra cheap CN value build prepared.')
                : builderPath === 'prebuilt'
                    ? builderText('Choose a recommended build to start.', 'Choose a recommended build to start.')
                    : builderText('Custom builder unlocked.', 'Custom builder unlocked.');
            showToast(message, 'success');
        }
    }

    function getComponentsTotal() {
        return Object.values(selectedComponents).reduce((sum, product) => sum + Number(product.price || 0), 0);
    }

    function getBuildTotal() {
        return getComponentsTotal() + calculateServiceTotal();
    }

    function getSelectedCount() {
        return STEPS.filter(step => selectedComponents[step.key]).length;
    }

    function getNextMissingStep() {
        return STEPS.find(step => !step.optional && !selectedComponents[step.key]) || null;
    }

    function getRequiredSteps() {
        return STEPS.filter(step => !step.optional);
    }

    function getRequiredSelectedCount() {
        return getRequiredSteps().filter(step => selectedComponents[step.key]).length;
    }

    function getStepIndex(key) {
        return STEPS.findIndex(step => step.key === key);
    }

    function jumpToStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= STEPS.length) return;
        currentStep = stepIndex;
        renderWizardSteps();
        renderCurrentStep();
        document.getElementById('componentPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function updateBuildGuide() {
        const guide = document.getElementById('buildGuideBar');
        const dock = document.getElementById('mobileBuildDock');
        const selectedCount = getSelectedCount();
        const requiredSteps = getRequiredSteps();
        const requiredSelected = getRequiredSelectedCount();
        const progress = Math.round((requiredSelected / Math.max(1, requiredSteps.length)) * 100);
        const nextMissing = getNextMissingStep();
        const nextLabel = nextMissing ? builderText(nextMissing.label, nextMissing.label) : builderText('Review build', 'Review build');
        const current = STEPS[currentStep];
        const total = getBuildTotal();
        const budgetDelta = targetBudget - total;
        const budgetText = budgetDelta >= 0
            ? `${formatMAD(budgetDelta)} ${builderText('under target', 'under target')}`
            : `${formatMAD(Math.abs(budgetDelta))} ${builderText('over target', 'over target')}`;

        if (guide) {
            guide.innerHTML = `
                <div class="bgb-progress" aria-label="Build completion ${progress}%">
                    <span style="width:${progress}%"></span>
                </div>
                <div class="bgb-copy">
                    <span class="gf-kicker"><i class="fas fa-route"></i> ${builderTemplate('Step X of Y', 'Step {current} of {total}', {current: currentStep + 1, total: STEPS.length})}</span>
                    <strong>${selectedCount ? builderTemplate('Parts selected', '{count} parts selected', {count: selectedCount}) : builderText('Start with your first part', 'Start with your first part')}</strong>
                    <p>${nextMissing ? builderTemplate('Next best step', 'Next best step: choose {part}.', {part: nextLabel}) : builderText('All required steps filled', 'All required steps are filled. Review compatibility and add services if needed.')}</p>
                </div>
                <div class="bgb-stats">
                    <div><span>${builderText('Total', 'Total')}</span><strong>${formatMAD(total)}</strong></div>
                    <div><span>${builderText('Budget', 'Budget')}</span><strong class="${budgetDelta < 0 ? 'over' : ''}">${budgetText}</strong></div>
                    <div><span>${builderText('Current', 'Current')}</span><strong>${builderText(current.label, current.label)}</strong></div>
                </div>
                <button class="guide-action" type="button" data-jump-step="${nextMissing ? getStepIndex(nextMissing.key) : currentStep}">
                    ${nextMissing ? builderTemplate('Choose part', 'Choose {part}', {part: nextLabel}) : builderText('Review Summary', 'Review Summary')} <i class="fas fa-arrow-right"></i>
                </button>
            `;

            guide.querySelector('.guide-action')?.addEventListener('click', () => {
                if (nextMissing) jumpToStep(getStepIndex(nextMissing.key));
                else focusSummary();
            });
        }

        if (dock) {
            dock.innerHTML = `
                <span><i class="fas fa-list-check"></i> ${requiredSelected}/${requiredSteps.length} ${builderText('required parts', 'required parts')}</span>
                <strong>${formatMAD(total)}</strong>
                <em>${nextMissing ? builderTemplate('Next part', 'Next: {part}', {part: nextLabel}) : builderText('Ready to review', 'Ready to review')}</em>
            `;
        }
    }

    function focusSummary() {
        document.querySelector('.build-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function getAssistantContext() {
        const selected = {};
        STEPS.forEach(step => {
            const product = selectedComponents[step.key];
            selected[step.key] = product ? {
                id: product.id,
                name: product.name,
                brand: product.brand,
                category: product.category,
                price: Number(product.price || 0),
                wattage: extractWattage(product, step.key),
                specs: product.specs || {},
            } : null;
        });

        const missing = STEPS.filter(step => !step.optional && !selectedComponents[step.key]).map(step => step.label);
        return {
            page: 'builder',
            builderPath,
            platform: selectedPlatform,
            useCase,
            activePreset,
            targetBudget,
            selected,
            missing,
            selectedCount: getSelectedCount(),
            totalPrice: getBuildTotal(),
            totalWattage: calculateTotalWattage(true),
            recommendedPsu: getPSURecommendation(calculateTotalWattage(true)),
            services: Object.values(selectedServices).map(service => ({
                id: service.id,
                name: service.name,
                price: service.price,
            })),
        };
    }

    function applyAssistantProducts(products = []) {
        if (!Array.isArray(products) || !products.length) return false;
        let applied = 0;

        products.forEach(productLike => {
            const product = allProducts.find(item => Number(item.id) === Number(productLike.id));
            if (!product || !product.inStock) return;
            const step = STEPS.find(item => item.category === product.category || item.key === product.category);
            if (!step) return;
            const compat = checkCompatibility(step.key, product);
            if (!compat.compatible) return;
            selectedComponents[step.key] = product;
            applied++;
        });

        if (!applied) {
            showToast(builderText('No compatible AI picks could be applied.', 'No compatible AI picks could be applied.'), 'error');
            return false;
        }

        chooseBuilderPath('custom', false);
        currentStep = Math.max(0, STEPS.findIndex(step => !selectedComponents[step.key]));
        if (currentStep < 0) currentStep = 0;
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(builderTemplate('Applied {count} AI pick{plural} to your build.', 'Applied {count} AI pick{plural} to your build.', {count: applied, plural: applied > 1 ? 's' : ''}), 'success');
        return true;
    }

    function getCompatibilityReport() {
        const report = [];
        const cpu = selectedComponents.cpu;
        const ram = selectedComponents.ram;
        const psu = selectedComponents.psu;
        const cooling = selectedComponents.cooling;
        const gpu = selectedComponents.gpu;
        const motherboard = selectedComponents.motherboard;
        const totalWatt = calculateTotalWattage(true);
        const recommendedPsu = getPSURecommendation(totalWatt);

        if (cpu && motherboard) {
            const cpuSocket = getSocket(cpu);
            const boardSocket = getSocket(motherboard);
            report.push({
                status: cpuSocket && boardSocket && cpuSocket !== boardSocket ? 'bad' : 'ok',
                title: builderText('CPU / motherboard', 'CPU / motherboard'),
                text: cpuSocket && boardSocket && cpuSocket !== boardSocket
                    ? builderTemplate('{cpu} needs {cpuSocket}, but {motherboard} is {boardSocket}.', '{cpu} needs {cpuSocket}, but {motherboard} is {boardSocket}.', { cpu: cpu.name, cpuSocket, motherboard: motherboard.name, boardSocket })
                    : cpuSocket
                        ? builderTemplate('{motherboard} matches {socket}.', '{motherboard} matches {socket}.', { motherboard: motherboard.name, socket: cpuSocket })
                        : builderTemplate('{motherboard} matches the selected CPU socket.', '{motherboard} matches the selected CPU socket.', { motherboard: motherboard.name })
            });
        } else {
            report.push({ status: 'warn', title: builderText('CPU / motherboard', 'CPU / motherboard'), text: builderText('Select a CPU and motherboard to validate socket support.', 'Select a CPU and motherboard to validate socket support.') });
        }

        if (cpu && ram) {
            const cpuSocket = cpu.specs?.Socket || '';
            const ramSpeed = ram.specs?.Speed || '';
            const needsDdr5 = cpuSocket.includes('AM5');
            report.push({
                status: needsDdr5 && ramSpeed.includes('DDR4') ? 'bad' : 'ok',
                title: builderText('CPU / RAM match', 'CPU / RAM match'),
                text: needsDdr5 && ramSpeed.includes('DDR4')
                    ? builderText('AM5 CPUs require DDR5 memory.', 'AM5 CPUs require DDR5 memory.')
                    : cpuSocket && ramSpeed
                        ? builderTemplate('{cpuSocket} works with {ramSpeed}.', '{cpuSocket} works with {ramSpeed}.', { cpuSocket, ramSpeed })
                        : builderText('The selected CPU works with selected memory.', 'The selected CPU works with selected memory.')
            });
        } else {
            report.push({ status: 'warn', title: builderText('CPU / RAM match', 'CPU / RAM match'), text: builderText('Select a CPU and RAM kit to validate memory type.', 'Select a CPU and RAM kit to validate memory type.') });
        }

        if (motherboard && ram) {
            const boardMemory = getMemoryType(motherboard);
            const ramType = getMemoryType(ram);
            report.push({
                status: boardMemory && ramType && boardMemory !== ramType ? 'bad' : 'ok',
                title: builderText('Motherboard / RAM', 'Motherboard / RAM'),
                text: boardMemory && ramType && boardMemory !== ramType
                    ? builderTemplate('{motherboard} uses {boardMemory}, but {ram} is {ramType}.', '{motherboard} uses {boardMemory}, but {ram} is {ramType}.', { motherboard: motherboard.name, boardMemory, ram: ram.name, ramType })
                    : ramType
                        ? builderTemplate('{motherboard} supports {ramType}.', '{motherboard} supports {ramType}.', { motherboard: motherboard.name, ramType })
                        : builderTemplate('{motherboard} supports the selected memory kit.', '{motherboard} supports the selected memory kit.', { motherboard: motherboard.name })
            });
        } else {
            report.push({ status: 'warn', title: builderText('Motherboard / RAM', 'Motherboard / RAM'), text: builderText('Select a motherboard and RAM kit to validate memory type.', 'Select a motherboard and RAM kit to validate memory type.') });
        }

        if (cpu && cooling) {
            const cpuTdp = extractWattage(cpu, 'cpu');
            const coolerTdp = extractWattageFromSpec(cooling.specs?.['Max TDP'] || '');
            report.push({
                status: coolerTdp && coolerTdp < cpuTdp ? 'bad' : 'ok',
                title: builderText('Cooling headroom', 'Cooling headroom'),
                text: coolerTdp && coolerTdp < cpuTdp
                    ? builderTemplate('Cooler is rated below the CPU load. Pick {watts}W+ cooling.', 'Cooler is rated below the CPU load. Pick {watts}W+ cooling.', { watts: cpuTdp })
                    : builderTemplate('{cooling} has enough thermal headroom.', '{cooling} has enough thermal headroom.', { cooling: cooling.name })
            });
        } else {
            report.push({ status: 'warn', title: builderText('Cooling headroom', 'Cooling headroom'), text: builderText('Select CPU and cooling to check thermal headroom.', 'Select CPU and cooling to check thermal headroom.') });
        }

        if (psu) {
            const psuWattage = extractWattage(psu, 'psu');
            report.push({
                status: psuWattage < recommendedPsu ? 'bad' : 'ok',
                title: builderText('PSU sizing', 'PSU sizing'),
                text: psuWattage < recommendedPsu
                    ? builderTemplate('Current load suggests a {watts}W+ PSU.', 'Current load suggests a {watts}W+ PSU.', { watts: recommendedPsu })
                    : builderTemplate('{psuWatts}W PSU covers the estimated {loadWatts}W load.', '{psuWatts}W PSU covers the estimated {loadWatts}W load.', { psuWatts: psuWattage, loadWatts: totalWatt })
            });
        } else {
            report.push({ status: 'warn', title: builderText('PSU sizing', 'PSU sizing'), text: builderTemplate('Recommended PSU: {watts}W+.', 'Recommended PSU: {watts}W+.', { watts: recommendedPsu }) });
        }

        if (gpu && psu) {
            report.push({ status: 'ok', title: builderText('GPU power planning', 'GPU power planning'), text: builderTemplate('{gpu} is included in the {watts}W load estimate.', '{gpu} is included in the {watts}W load estimate.', { gpu: gpu.name, watts: totalWatt }) });
        }

        if (cpu && gpu && String(cpu.brand).toLowerCase() === 'amd' && String(gpu.brand).toLowerCase() === 'amd') {
            report.push({
                status: 'ok',
                title: builderText('[SAM ENABLED] Hardware Synergy', '[SAM ENABLED] Hardware Synergy'),
                text: builderText('Smart Access Memory is supported with this AMD CPU + AMD GPU combination, boosting gaming performance.', 'Smart Access Memory is supported with this AMD CPU + AMD GPU combination, boosting gaming performance.')
            });
        }

        const hasCaseSelection = STEPS.some(step => step.key === 'case')
            || allProducts.some(product => product.category === 'case' && product.inStock);
        const selectedCase = selectedComponents.case;

        if (selectedCase) {
            report.push({
                status: 'ok',
                title: builderText('Case clearance', 'Case clearance'),
                text: builderTemplate('{caseName} is included for final GPU and cooler clearance review.', '{caseName} is included for final GPU and cooler clearance review.', { caseName: selectedCase.name })
            });
        } else {
            report.push({
                status: hasCaseSelection ? 'warn' : 'ok',
                title: builderText('Case clearance', 'Case clearance'),
                text: hasCaseSelection
                    ? builderText('Select a case to validate GPU length, motherboard size, and radiator fit.', 'Select a case to validate GPU length, motherboard size, and radiator fit.')
                    : builderText('No case step is required for this preset; enclosure clearance is handled during final build review.', 'No case step is required for this preset; enclosure clearance is handled during final build review.')
            });
        }

        return report;
    }

    function updateCompatibilityPanel() {
        const panel = document.getElementById('compatibilityPanel');
        if (!panel) return;

        const report = getCompatibilityReport();
        const icon = { ok: 'fa-circle-check', warn: 'fa-triangle-exclamation', bad: 'fa-circle-xmark' };
        panel.innerHTML = `
            <h4><i class="fas fa-shield-halved"></i> ${builderText('Compatibility Check', 'Compatibility Check')}</h4>
            <div class="compat-list">
                ${report.map(item => `
                    <div class="compat-item ${item.status}">
                        <i class="fas ${icon[item.status]}"></i>
                        <span><strong>${item.title}</strong>${item.text}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // ── PSU Recommendation ───────────────────────────────────
    function getArchitectureIPC(arch) {
        if (!arch) return 1.0;
        const a = arch.toLowerCase();
        if (a.includes('zen 5')) return 1.35;
        if (a.includes('zen 4')) return 1.20;
        if (a.includes('arrow lake')) return 1.30;
        if (a.includes('raptor lake')) return 1.25;
        if (a.includes('alder lake')) return 1.15;
        if (a.includes('zen 3')) return 1.05;
        if (a.includes('broadwell')) return 0.75;
        return 1.0;
    }

    function inferCpuScore(cpu) {
        if (!cpu || !cpu.specs) return 50;
        
        // Extract threads
        let threads = 8;
        const coresStr = String(cpu.specs.Cores || '');
        const threadMatch = coresStr.match(/(\d+)\s*threads?/i);
        if (threadMatch) {
            threads = parseInt(threadMatch[1]);
        } else {
            const coreMatch = coresStr.match(/^(\d+)/);
            if (coreMatch) threads = parseInt(coreMatch[1]) * 2;
        }

        // Extract clock
        const clockStr = String(cpu.specs['Boost Clock'] || '');
        const clockMatch = clockStr.match(/(\d+(?:\.\d+)?)/);
        const clock = clockMatch ? parseFloat(clockMatch[1]) : 4.0;

        // Extract cache
        const cacheStr = String(cpu.specs['L3 Cache'] || '');
        const cacheMatch = cacheStr.match(/(\d+)\s*MB/i);
        let cache = cacheMatch ? parseInt(cacheMatch[1]) : 16;
        if (cacheStr.toLowerCase().includes('3d v-cache')) cache *= 1.5; // X3D bonus

        const ipc = getArchitectureIPC(cpu.specs.Architecture);

        // Score formula based on real specs
        // (Base threads + clock speed multiplier) * IPC * Cache bonus
        const threadScore = Math.min(threads, 16) * 1.5 + Math.max(0, threads - 16) * 0.5;
        const rawScore = (threadScore * clock * ipc) + (cache * 0.2);
        
        // Normalize to ~0-120 scale
        return Math.max(20, Math.min(130, rawScore * 0.85));
    }

    function inferGpuScore(gpu) {
        if (!gpu || !gpu.specs) return 50;
        
        // Extract cores
        let cores = 1000;
        const coreStr = String(gpu.specs['CUDA Cores'] || gpu.specs['Stream Processors'] || '');
        const coreMatch = coreStr.replace(/[^\d.]/g, '');
        if (coreMatch) cores = parseInt(coreMatch);

        // Extract clock
        const clockStr = String(gpu.specs['Boost Clock'] || '');
        const clockMatch = clockStr.match(/(\d+(?:\.\d+)?)/);
        let clock = clockMatch ? parseFloat(clockMatch[1]) : 2.0;
        if (clock > 100) clock = clock / 1000; // normalize to GHz if in MHz

        // Extract VRAM
        const vramStr = String(gpu.specs.VRAM || '');
        const vramMatch = vramStr.match(/(\d+)\s*GB/i);
        const vram = vramMatch ? parseInt(vramMatch[1]) : 8;

        // TFLOPs calculation = Cores * 2 * Clock GHz / 1000
        // Note: AMD and NVIDIA cores aren't 1:1, AMD RDNA3 needs an IPC multiplier vs Ada Lovelace
        let tflops = (cores * 2 * clock) / 1000;
        
        const arch = String(gpu.specs.Architecture || '').toLowerCase();
        if (arch.includes('rdna 3')) tflops *= 1.25; // RDNA 3 stream processors dual issue
        else if (arch.includes('rdna 2')) tflops *= 1.0;
        else if (arch.includes('polaris')) tflops *= 0.6; // older arch penalty

        // Add VRAM bonus
        const vramBonus = vram >= 16 ? 10 : (vram >= 12 ? 5 : (vram <= 8 ? -5 : 0));

        // Normalize score based on TFLOPs (e.g. RTX 4090 ~ 82 TFLOPs -> ~120 score)
        return Math.max(20, Math.min(130, (tflops * 1.3) + vramBonus + 15));
    }

    function calculateBottleneck(cpu, gpu, resolution) {
        if (!cpu || !gpu) return null;
        const cpuScore = inferCpuScore(cpu);
        const gpuScore = inferGpuScore(gpu);
        const gpuPressure = { '1080p': 0.78, '1440p': 0.94, '4K': 1.15 }[resolution] || 0.94;
        const ratio = cpuScore / Math.max(1, gpuScore * gpuPressure);

        if (ratio < 0.88) {
            const percentage = Math.round((1 - ratio) * 100);
            return {
                type: 'cpu',
                label: builderText('CPU bottleneck', 'CPU bottleneck'),
                percentage,
                score: Math.max(0, 100 - percentage * 2),
                color: 'var(--diagnostic-red)',
                text: builderTemplate('{cpu} may hold back {gpu} by about {percentage}% at {resolution}.', '{cpu} may hold back {gpu} by about {percentage}% at {resolution}.', { cpu: cpu.name, gpu: gpu.name, percentage, resolution })
            };
        }

        if (ratio > 1.16) {
            const percentage = Math.round(Math.min(35, (ratio - 1) * 70));
            return {
                type: 'gpu',
                label: builderText('GPU bottleneck', 'GPU bottleneck'),
                percentage,
                score: Math.max(0, 100 - percentage * 1.6),
                color: '#4da3ff',
                text: builderTemplate('{gpu} is the limiting part by about {percentage}% at {resolution}.', '{gpu} is the limiting part by about {percentage}% at {resolution}.', { gpu: gpu.name, percentage, resolution })
            };
        }

        return {
            type: 'balanced',
            label: builderText('Balanced', 'Balanced'),
            percentage: Math.round(Math.abs(1 - ratio) * 100),
            score: 96,
            color: 'var(--diagnostic-green)',
            text: builderTemplate('{cpu} and {gpu} are well matched at {resolution}.', '{cpu} and {gpu} are well matched at {resolution}.', { cpu: cpu.name, gpu: gpu.name, resolution })
        };
    }

    function bottleneckTips(result) {
        if (!result) return builderText('Select a CPU and GPU to calculate balance at 1080p, 1440p, and 4K.', 'Select a CPU and GPU to calculate balance at 1080p, 1440p, and 4K.');
        if (result.type === 'cpu') {
            return builderText('Upgrade the CPU for high-refresh gaming, or save money by choosing a less powerful GPU.', 'Upgrade the CPU for high-refresh gaming, or save money by choosing a less powerful GPU.');
        }
        if (result.type === 'gpu') {
            return builderText('Upgrade the GPU for this CPU, or keep the current GPU if the goal is budget efficiency.', 'Upgrade the GPU for this CPU, or keep the current GPU if the goal is budget efficiency.');
        }
        return builderText('This pairing is healthy. Spend the next upgrade budget on cooling, storage, or monitor quality.', 'This pairing is healthy. Spend the next upgrade budget on cooling, storage, or monitor quality.');
    }

    function updateBottleneckPanel() {
        const panel = document.getElementById('bottleneckPanel');
        if (!panel) return;

        const cpu = selectedComponents.cpu;
        const gpu = selectedComponents.gpu;
        const result = calculateBottleneck(cpu, gpu, bottleneckResolution);

        if (!cpu || !gpu) {
            panel.innerHTML = `
                <h4><i class="fas fa-gauge-high"></i> ${builderText('Bottleneck Analyzer', 'Bottleneck Analyzer')}</h4>
                <div class="bottleneck-empty">
                    <span>${builderText('CPU + GPU required', 'CPU + GPU required')}</span>
                    <small>${builderText('Select both parts for real-time balance analysis.', 'Select both parts for real-time balance analysis.')}</small>
                </div>
            `;
            return;
        }

        const score = Math.round(result.score);
        panel.innerHTML = `
            <h4><i class="fas fa-gauge-high"></i> ${builderText('Bottleneck Analyzer', 'Bottleneck Analyzer')}</h4>
            <div class="bottleneck-tabs" role="tablist" aria-label="${builderText('Resolution', 'Resolution')}">
                ${['1080p', '1440p', '4K'].map(res => `
                    <button type="button" class="${res === bottleneckResolution ? 'active' : ''}" data-bottleneck-res="${res}">${res}</button>
                `).join('')}
            </div>
            <div class="bottleneck-meter">
                <div class="bottleneck-meter-head">
                    <strong>${result.label}</strong>
                    <span>${score}/100</span>
                </div>
                <div class="bottleneck-bar" aria-label="${builderTemplate('System balance score {score}', 'System balance score {score}', { score })}">
                    <span style="width:${score}%; background:${result.color};"></span>
                </div>
            </div>
            <p class="bottleneck-message">${result.text}</p>
            <p class="bottleneck-tip"><i class="fas fa-lightbulb"></i> ${bottleneckTips(result)}</p>
        `;

        panel.querySelectorAll('[data-bottleneck-res]').forEach(btn => {
            btn.addEventListener('click', () => {
                bottleneckResolution = btn.dataset.bottleneckRes || '1440p';
                updateBottleneckPanel();
            });
        });
    }



    function getSelectedProductsByBudget(stepKey, maxPrice) {
        const step = STEPS.find(item => item.key === stepKey);
        if (!step) return [];
        return allProducts
            .filter(product => product.category === step.category && product.inStock && Number(product.price || 0) <= maxPrice)
            .filter(product => checkCompatibility(stepKey, product).compatible)
            .sort((a, b) => b.rating - a.rating || b.price - a.price);
    }

    function getHealthScore() {
        const report = getCompatibilityReport();
        const bad = report.filter(item => item.status === 'bad').length;
        const warn = report.filter(item => item.status === 'warn').length;
        const totalWatt = calculateTotalWattage(true);
        const psuWatt = selectedComponents.psu ? extractWattage(selectedComponents.psu, 'psu') : 0;
        const psuHeadroom = psuWatt ? Math.max(0, Math.min(100, Math.round(((psuWatt - totalWatt) / Math.max(1, psuWatt)) * 100))) : 0;
        const balanceResult = calculateBottleneck(selectedComponents.cpu, selectedComponents.gpu, '1440p');
        const balance = balanceResult ? Math.round(balanceResult.score) : 55;
        const storageText = `${selectedComponents.storage?.name || ''} ${Object.values(selectedComponents.storage?.specs || {}).join(' ')}`.toLowerCase();
        const storage = selectedComponents.storage ? (storageText.includes('nvme') || storageText.includes('m.2') ? 92 : 68) : 45;
        const memoryType = getMemoryType(selectedComponents.ram) || getMemoryType(selectedComponents.motherboard);
        const vramStr = String(selectedComponents.gpu?.specs?.VRAM || '');
        const vramMatch = vramStr.replace(',', '.').match(/(\d+(?:\.\d+)?)/);
        const vram = vramMatch ? parseFloat(vramMatch[1]) : 8;
        const future = Math.round((
            (memoryType === 'DDR5' ? 30 : 16) +
            (vram >= 16 ? 25 : vram >= 12 ? 18 : 10) +
            (psuHeadroom >= 30 ? 20 : psuHeadroom >= 18 ? 14 : 8) +
            (selectedComponents.motherboard && getSocket(selectedComponents.motherboard).includes('AM5') ? 25 : 16)
        ));
        const thermals = selectedComponents.cooling ? Math.max(45, Math.min(100, 70 + psuHeadroom - warn * 4)) : 42;
        const value = Math.max(30, Math.min(100, Math.round(96 - Math.max(0, getBuildTotal() - targetBudget) / 180 - bad * 18)));
        const overall = Math.max(0, Math.min(100, Math.round((balance + thermals + storage + future + value) / 5 - bad * 12 - warn * 3)));
        return {
            overall,
            metrics: [
                { label: 'Balance', value: balance },
                { label: 'Thermals', value: Math.round(thermals) },
                { label: 'Storage', value: Math.round(storage) },
                { label: 'Future', value: Math.round(future) },
                { label: 'Value', value: Math.round(value) },
            ],
            psuHeadroom,
        };
    }

    function getNoiseEstimate() {
        let db = 25; // Base case noise floor with idle fans
        let desc = 'Whisper quiet';

        if (selectedComponents.cooling) {
            const coolerName = String(selectedComponents.cooling.name).toLowerCase();
            if (coolerName.includes('water') || coolerName.includes('liquid') || coolerName.includes('aio')) {
                db += 5; // Pump noise + radiator fans
            } else if (coolerName.includes('noctua') || coolerName.includes('be quiet')) {
                db += 3; // Premium air coolers
            } else {
                db += 8; // Standard air coolers
            }
        } else if (selectedComponents.cpu) {
            db += 12; // Assuming stock cooler
        }

        if (selectedComponents.gpu) {
            const gpuName = String(selectedComponents.gpu.name).toLowerCase();
            const gpuWatts = extractWattage(selectedComponents.gpu, 'gpu');
            if (gpuWatts > 300) {
                db += 15; // High end GPUs get loud
            } else if (gpuWatts > 200) {
                db += 10;
            } else {
                db += 6;
            }
            if (gpuName.includes('blower') || gpuName.includes('turbo')) db += 8;
        }

        if (db > 45) desc = builderText('Loud under load', 'Loud under load');
        else if (db > 35) desc = builderText('Audible hum', 'Audible hum');
        else desc = builderText('Quiet under load', 'Quiet under load');

        return { db, desc };
    }

    function updateHealthPanel() {
        const panel = document.getElementById('healthPanel');
        if (!panel) return;
        const health = getHealthScore();
        const noise = getNoiseEstimate();
        const selectedCount = getSelectedCount();
        panel.innerHTML = `
            <h4><i class="fas fa-heart-pulse"></i> ${builderText('Build Health Score', 'Build Health Score')}</h4>
            <div class="health-score-line">
                <strong>${selectedCount ? health.overall : '--'}</strong>
                <span>/100</span>
                <em>${selectedCount ? builderText('diagnostic confidence', 'diagnostic confidence') : builderText('select parts to score', 'select parts to score')}</em>
            </div>
            <div class="health-metrics">
                ${health.metrics.map(item => `
                    <div>
                        <span>${builderLabel(item.label)}</span>
                        <b>${selectedCount ? item.value : '--'}</b>
                        <i style="width:${selectedCount ? item.value : 0}%"></i>
                    </div>
                `).join('')}
            </div>
            <div class="psu-overhead-gauge" style="margin-bottom: 8px;">
                <span>${builderText('PSU overhead', 'PSU overhead')}</span>
                <strong>${selectedComponents.psu ? `${health.psuHeadroom}%` : builderText('Pick PSU', 'Pick PSU')}</strong>
            </div>
            <div class="psu-overhead-gauge">
                <span><i class="fas fa-volume-low"></i> ${builderText('Noise estimate', 'Noise estimate')}</span>
                <strong>${selectedCount ? `${noise.db} dB <span style="font-size: 0.65rem; color: var(--muted); font-weight: normal; margin-left: 4px;">(${noise.desc})</span>` : '--'}</strong>
            </div>
            <button type="button" class="health-action" id="downgradeBudgetBtn">
                <i class="fas fa-arrow-trend-down"></i> ${builderText('Downgrade to budget', 'Downgrade to budget')}
            </button>
        `;

        panel.querySelector('#downgradeBudgetBtn')?.addEventListener('click', downgradeToBudget);
    }

    function getSmartChecklistItems() {
        const items = [];
        const selectedKeys = Object.keys(selectedComponents);
        STEPS.forEach(step => {
            if (!step.optional && !selectedComponents[step.key]) {
                items.push({ tone: 'warn', text: builderTemplate('{part} is missing.', '{part} is missing.', { part: builderLabel(step.label) }) });
            }
        });

        const storageName = String(selectedComponents.storage?.name || '').toLowerCase();
        if (storageName.includes('sata')) items.push({ tone: 'warn', text: builderText('SATA storage selected, add a SATA data cable if your motherboard bundle is limited.', 'SATA storage selected, add a SATA data cable if your motherboard bundle is limited.') });
        if (selectedComponents.cpu && !selectedComponents.cooling) items.push({ tone: 'bad', text: builderText('CPU selected without cooling.', 'CPU selected without cooling.') });
        if (selectedComponents.cpu) items.push({ tone: 'ok', text: builderText('Thermal paste should be in the cart or included with the cooler.', 'Thermal paste should be in the cart or included with the cooler.') });
        if (selectedComponents.gpu || selectedComponents.psu) items.push({ tone: 'ok', text: builderText('Confirm GPU power cable count before assembly.', 'Confirm GPU power cable count before assembly.') });
        if (selectedComponents.motherboard && !String(selectedComponents.motherboard.name || '').toLowerCase().includes('wifi')) {
            items.push({ tone: 'warn', text: builderText('No Wi-Fi signal detected in motherboard name. Add a Wi-Fi card if needed.', 'No Wi-Fi signal detected in motherboard name. Add a Wi-Fi card if needed.') });
        }
        if (getRequiredSelectedCount() >= Math.max(1, getRequiredSteps().length - 1)) items.push({ tone: 'ok', text: builderText('Core build is nearly complete.', 'Core build is nearly complete.') });
        return items.slice(0, 8);
    }

    function updateSmartChecklistPanel() {
        const panel = document.getElementById('smartChecklistPanel');
        if (!panel) return;
        const items = getSmartChecklistItems();
        panel.innerHTML = `
            <h4><i class="fas fa-clipboard-check"></i> ${builderText('Oops I Forgot', 'Oops I Forgot')}</h4>
            <div class="smart-checklist">
                ${items.map(item => `
                    <div class="${item.tone}">
                        <i class="fas ${item.tone === 'ok' ? 'fa-circle-check' : item.tone === 'bad' ? 'fa-circle-xmark' : 'fa-triangle-exclamation'}"></i>
                        <span>${item.text}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function getAssemblyGuideSteps() {
        const steps = [];
        if (selectedComponents.motherboard) steps.push(builderText('Bench-test motherboard, CPU, one RAM stick, and PSU before mounting.', 'Bench-test motherboard, CPU, one RAM stick, and PSU before mounting.'));
        if (selectedComponents.cpu) steps.push(builderText('Install CPU and check socket orientation before locking the retention arm.', 'Install CPU and check socket orientation before locking the retention arm.'));
        if (selectedComponents.storage && String(selectedComponents.storage.name || '').toLowerCase().includes('m.2')) {
            steps.push(builderText('Install M.2 SSD before the board goes into the case; add a heatsink if the slot has no shield.', 'Install M.2 SSD before the board goes into the case; add a heatsink if the slot has no shield.'));
        }
        if (selectedComponents.cooling && /nh-d15|dark rock|tower/i.test(selectedComponents.cooling.name || '')) {
            steps.push(builderText('Mount the tower cooler before final cable routing because RAM clearance gets tight.', 'Mount the tower cooler before final cable routing because RAM clearance gets tight.'));
        }
        if (selectedComponents.gpu) steps.push(builderText('Install GPU after front-panel and PSU cables are routed.', 'Install GPU after front-panel and PSU cables are routed.'));
        if (selectedComponents.psu) steps.push(builderText('Leave at least 25 percent PSU headroom for transient GPU spikes and future upgrades.', 'Leave at least 25 percent PSU headroom for transient GPU spikes and future upgrades.'));
        if (selectedServices.stress) steps.push(builderText('Run memory, CPU, and GPU stress tests before packing.', 'Run memory, CPU, and GPU stress tests before packing.'));
        if (!steps.length) steps.push(builderText('Select parts to generate a component-specific assembly path.', 'Select parts to generate a component-specific assembly path.'));
        return steps.slice(0, 6);
    }

    function updateAssemblyGuidePanel() {
        const panel = document.getElementById('assemblyGuidePanel');
        if (!panel) return;
        panel.innerHTML = `
            <h4><i class="fas fa-timeline"></i> ${builderText('Assembly Timeline', 'Assembly Timeline')}</h4>
            <ol class="assembly-steps">
                ${getAssemblyGuideSteps().map(item => `<li>${item}</li>`).join('')}
            </ol>
        `;
    }

    function downgradeToBudget() {
        if (Object.keys(selectedComponents).length === 0) {
            autoBuild('budget', Math.max(4500, Math.round(targetBudget * 0.75)), true);
            return;
        }

        let changed = 0;
        ['gpu', 'cpu', 'motherboard', 'cooling', 'psu'].forEach(key => {
            const current = selectedComponents[key];
            if (!current) return;
            const maxPrice = Number(current.price || 0) * 0.82;
            const options = getSelectedProductsByBudget(key, maxPrice);
            if (options[0] && options[0].id !== current.id) {
                selectedComponents[key] = options[0];
                changed++;
            }
        });

        if (!changed) {
            showToast(builderText('No cheaper compatible swaps found in stock.', 'No cheaper compatible swaps found in stock.'), 'error');
            return;
        }
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(builderTemplate('Applied {count} budget-focused swap{plural}.', 'Applied {count} budget-focused swap{plural}.', {count: changed, plural: changed > 1 ? 's' : ''}), 'success');
    }

    function getPSURecommendation(totalWattage) {
        const recommended = Math.ceil(totalWattage * 1.25 / 50) * 50; // Round up to nearest 50W with 25% headroom
        const tiers = [450, 550, 650, 750, 850, 1000, 1200];
        for (const t of tiers) {
            if (t >= recommended) return t;
        }
        return recommended;
    }

    // ── Update Summary Sidebar ───────────────────────────────
    function updateSummary() {
        const summaryItems = document.getElementById('summaryItems');
        const wattageValue = document.getElementById('wattageValue');
        const wattageFill = document.getElementById('wattageFill');
        const wattageRec = document.getElementById('wattageRec');
        const totalPrice = document.getElementById('totalPrice');
        const addAllBtn = document.getElementById('addAllBtn');

        if (!summaryItems) return;

        let total = 0;
        let itemCount = 0;
        const serviceTotal = calculateServiceTotal();

        const caseSlots = document.getElementById('caseSlots');
        if (caseSlots) {
            caseSlots.innerHTML = STEPS.map(step => {
            const comp = selectedComponents[step.key];
            if (comp) {
                total += comp.price;
                itemCount++;
            }
            const slot = CASE_SLOT_LAYOUT[step.key] || { row: 'auto', col: '1 / 3', size: 'sm', zone: 'EXTERN' };
            const filled = comp ? 'is-filled' : 'is-empty';
            const localizedLabel = (window.__marocPcPhraseMap && window.__marocPcPhraseMap[step.label]) || step.label;
            const emptyText = (window.__marocPcPhraseMap && window.__marocPcPhraseMap['Not Installed']) || 'Not Installed';
            const svgArt = getComponentSVG(step.key);
            return `
                <button type="button"
                        class="case-slot slot-${step.key} ${filled}"
                        data-summary-step="${step.key}"
                        aria-label="${localizedLabel}: ${comp ? comp.name : emptyText}">
                    <div class="slot-svg">${svgArt}</div>
                    <div class="slot-content">
                        <div class="slot-header">
                            <span class="slot-badge">${slot.zone}</span>
                            <span class="slot-label">${localizedLabel}</span>
                        </div>
                        ${comp ? `
                            <div class="slot-model">${comp.name}</div>
                            <div class="slot-price-row">
                                <span class="slot-price">${formatMAD(comp.price)}</span>
                                <span class="slot-remove" data-key="${step.key}" role="button" tabindex="0" title="${builderText('Remove', 'Remove')}">
                                    <i class="fas fa-times"></i>
                                </span>
                            </div>
                        ` : `<div class="slot-empty-text">${emptyText}</div>`}
                    </div>
                </button>`;
        }).join('');
        } // end if (caseSlots)

        // Services strip (below the case interior)
        const servicesStrip = document.getElementById('caseServicesStrip');
        if (servicesStrip) {
            servicesStrip.innerHTML = Object.values(selectedServices).map(service => `
                <span class="case-service-chip">
                    <i class="fas ${service.icon}"></i>${service.name}
                    <em>${formatMAD(service.price)}</em>
                </span>`).join('');
        }

        // Remove button handlers
        summaryItems.querySelectorAll('.slot-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                delete selectedComponents[btn.dataset.key];
                renderWizardSteps();
                renderCurrentStep();
                updateSummary();
            });
        });

        summaryItems.querySelectorAll('[data-summary-step]').forEach(item => {
            const navigate = () => jumpToStep(getStepIndex(item.dataset.summaryStep));
            item.addEventListener('click', (event) => {
                if (event.target.closest('.slot-remove')) return;
                navigate();
            });
            item.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    navigate();
                }
            });
        });

        const finalTotal = total + serviceTotal;

        // Total price
        if (totalPrice) totalPrice.textContent = formatMAD(finalTotal);

        // Wattage meter
        const totalWatt = calculateTotalWattage(true);
        const psuWatt = selectedComponents['psu'] ? extractWattage(selectedComponents['psu'], 'psu') : 0;
        const psuCapacity = psuWatt || 850; // Default PSU for display
        const pct = Math.min(100, (totalWatt / psuCapacity) * 100);

        if (wattageValue) wattageValue.textContent = `${totalWatt}W / ${psuWatt ? psuWatt + 'W' : '???'}`;
        if (wattageFill) {
            wattageFill.style.width = pct + '%';
            wattageFill.className = 'wattage-fill' + (pct > 90 ? ' danger' : pct > 75 ? ' warn' : '');
        }
        if (wattageRec) {
            const rec = getPSURecommendation(totalWatt);
            wattageRec.innerHTML = builderText('Recommended PSU:', 'Recommended PSU:') + ' <strong>' + rec + 'W+</strong>';
        }

        // Add all to cart button
        if (addAllBtn) addAllBtn.disabled = itemCount === 0;

        // Update Sticky Bottom Dock details
        const stickyTotalPrice = document.getElementById('stickyTotalPrice');
        const stickyPartsCount = document.getElementById('stickyPartsCount');
        const stickyWattage = document.getElementById('stickyWattage');
        const stickyBuildName = document.getElementById('stickyBuildName');

        if (stickyTotalPrice) stickyTotalPrice.textContent = formatMAD(finalTotal);
        if (stickyPartsCount) stickyPartsCount.textContent = `${itemCount} ${itemCount !== 1 ? builderText('parts', 'parts') : builderText('part', 'part')}`;
        if (stickyWattage) {
            stickyWattage.textContent = `${totalWatt}W / ${psuWatt ? psuWatt + 'W' : '???'}`;
        }
        if (stickyBuildName) stickyBuildName.textContent = buildName || builderText('My Build', 'My Build');

        // Update workspace classes for empty vs populated build state
        const workspace = document.getElementById('pcBuilderWorkspace');
        if (workspace) {
            if (itemCount > 0) {
                workspace.classList.remove('build-empty');
                workspace.classList.add('build-populated');
            } else {
                workspace.classList.remove('build-populated');
                workspace.classList.add('build-empty');
                
                // Hide active preset banner if build is completely cleared
                const banner = document.getElementById('activePresetBanner');
                if (banner) banner.style.display = 'none';
            }

            // In Focus Mode, auto-adjust accordion collapse state based on items selection
            if (workspace.classList.contains('workspace-mode-focus')) {
                adjustFocusAccordions();
            }
        }

        updateCompatibilityPanel();
        updateBottleneckPanel();
        updateHealthPanel();
        updateSmartChecklistPanel();
        updateAssemblyGuidePanel();
        updateBuildGuide();

        // Update FPS Estimator if available
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.update();
        }
        updateFinderPreview();
    }

    // ── Add All to Cart ──────────────────────────────────────
    function addAllToCart() {
        const items = Object.values(selectedComponents);
        const services = Object.values(selectedServices);
        if (items.length === 0 && services.length === 0) return;

        items.forEach(product => {
            if (typeof Cart !== 'undefined' && Cart.add) {
                Cart.add(product);
            }
        });

        services.forEach(service => {
            if (typeof Cart !== 'undefined' && Cart.add) {
                Cart.add({
                    id: service.id,
                    name: service.name,
                    brand: 'Maroc PC',
                    category: 'service',
                    price: service.price,
                    image: 'logo.png',
                    inStock: true,
                    specs: { Type: 'Build service' }
                });
            }
        });

        showToast(builderTemplate('Added {itemsCount} components{servicesText} to cart!', 'Added {itemsCount} components{servicesText} to cart!', {
            itemsCount: items.length,
            servicesText: services.length ? builderTemplate(' and {count} services', ' and {count} services', {count: services.length}) : ''
        }), 'success');
    }

    // ── Save Build ───────────────────────────────────────────
    async function saveBuild() {
        const components = {};
        for (const [key, prod] of Object.entries(selectedComponents)) {
            components[key] = { id: prod.id, name: prod.name, price: prod.price, brand: prod.brand };
        }
        const services = {};
        for (const [key, service] of Object.entries(selectedServices)) {
            services[key] = { id: service.id, name: service.name, price: service.price };
        }

        if (Object.keys(components).length === 0) {
            showToast(builderText('Select at least one component first.', 'Select at least one component first.'), 'error');
            return;
        }

        const nameInput = document.getElementById('buildNameInput');
        if (nameInput) buildName = nameInput.value.trim() || builderText('My Build', 'My Build');

        try {
            const res = await fetch('api/builder-save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save',
                    build_name: buildName,
                    use_case: useCase,
                    components: { ...components, services },
                    total_price: Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal(),
                    total_wattage: calculateTotalWattage(true),
                })
            });
            const data = await res.json();

            if (data.success) {
                showShareModal(data.share_code);
            } else {
                showToast(data.message || builderText('Failed to save build.', 'Failed to save build.'), 'error');
            }
        } catch (e) {
            showToast(builderText('Network error. Try again.', 'Network error. Try again.'), 'error');
        }
    }

    // ── Load Shared Build ────────────────────────────────────
    async function loadSharedBuild(code) {
        try {
            const res = await fetch(`api/builder-save.php?code=${encodeURIComponent(code)}`);
            const data = await res.json();

            if (data.success && data.build) {
                const build = data.build;
                buildName = build.build_name || builderText('Shared Build', 'Shared Build');
                useCase = build.use_case || 'general';
                activePreset = PRESETS.find(p => p.useCase === useCase)?.key || 'aaa1440';

                // Map saved component IDs back to products
                const comps = build.components || {};
                for (const [key, comp] of Object.entries(comps)) {
                    if (key === 'services') {
                        selectedServices = {};
                        Object.keys(comp || {}).forEach(serviceKey => {
                            if (BUILD_SERVICES[serviceKey]) selectedServices[serviceKey] = BUILD_SERVICES[serviceKey];
                        });
                        continue;
                    }
                    const product = allProducts.find(p => p.id === comp.id);
                    if (product) {
                        selectedComponents[key] = product;
                    }
                }

                document.querySelectorAll('.service-checkbox').forEach(input => {
                    input.checked = Boolean(selectedServices[input.value]);
                });

                const nameInput = document.getElementById('buildNameInput');
                if (nameInput) nameInput.value = buildName;

                chooseBuilderPath('custom', false);
                renderWizardSteps();
                renderUseCaseBar();
                renderCurrentStep();
                updateSummary();
                showToast(builderTemplate('Loaded build: "{buildName}"', 'Loaded build: "{buildName}"', {buildName}), 'success');
            }
        } catch (e) {
            console.error('Failed to load shared build:', e);
        }
    }

    // ── Auto Build ───────────────────────────────────────────
    function autoBuild(presetUseCase = null, budget = null, notify = true) {
        if (presetUseCase === null) {
            openOnboardingWizard();
            return;
        }

        if (budget === null) {
            budget = targetBudget;
        }

        selectedComponents = {};

        const budgetWeights = {
            cpu: presetUseCase === 'budget' ? 0.12 : presetUseCase === 'editing' ? 0.28 : 0.22,
            motherboard: presetUseCase === 'budget' ? 0.17 : 0.12,
            gpu: presetUseCase === 'budget' ? 0.32 : presetUseCase === 'office' ? 0.18 : presetUseCase === 'editing' ? 0.30 : 0.42,
            ram: presetUseCase === 'budget' ? 0.11 : presetUseCase === 'editing' || presetUseCase === 'streaming' ? 0.14 : 0.10,
            storage: presetUseCase === 'budget' ? 0.09 : 0.10,
            psu: presetUseCase === 'budget' ? 0.12 : 0.09,
            cooling: presetUseCase === 'budget' ? 0.04 : 0.07,
            accessories: 0.05,
        };

        STEPS.filter(step => !step.optional).forEach(step => {
            const categoryBudget = budget * (budgetWeights[step.key] || 0.12);
            const options = allProducts
                .filter(p => p.category === step.category && p.inStock)
                .filter(p => checkCompatibility(step.key, p).compatible)
                .filter(p => {
                    if (step.key !== 'cpu' || !selectedPlatform) return true;
                    if (selectedPlatform === 'intel') return String(p.brand).toLowerCase().includes('intel');
                    if (selectedPlatform === 'amd') return String(p.brand).toLowerCase().includes('amd');
                    return true;
                })
                .sort((a, b) => {
                    const aOver = a.price > categoryBudget ? 1 : 0;
                    const bOver = b.price > categoryBudget ? 1 : 0;
                    if (aOver !== bOver) return aOver - bOver;
                    const aScore = (a.rating || 0) * 1000 - Math.abs(categoryBudget - a.price) / 20;
                    const bScore = (b.rating || 0) * 1000 - Math.abs(categoryBudget - b.price) / 20;
                    return bScore - aScore;
                });

            if (options.length > 0) {
                selectedComponents[step.key] = options[0];
            }
        });

        const recommended = getPSURecommendation(calculateTotalWattage(true));
        const psuOptions = allProducts
            .filter(p => p.category === 'psu' && p.inStock && extractWattage(p, 'psu') >= recommended)
            .sort((a, b) => a.price - b.price || b.rating - a.rating);
        if (psuOptions[0]) selectedComponents.psu = psuOptions[0];

        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        if (notify) showToast(builderTemplate('Auto-built {useCase} PC around {budget}.', 'Auto-built {useCase} PC around {budget}.', {useCase: presetUseCase, budget: formatMAD(budget)}), 'success');

        // Update FPS Estimator
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.update();
        }

        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    // ── Share Modal ──────────────────────────────────────────
    function showShareModal(shareCode) {
        const backdrop = document.getElementById('shareModalBackdrop');
        const urlInput = document.getElementById('shareUrlInput');
        if (!backdrop) return;

        const shareUrl = `${window.location.origin}${window.location.pathname}?build=${shareCode}`;
        if (urlInput) urlInput.value = shareUrl;

        // Generate real branded QR code on the fly (neon cyan foreground on custom dark backdrop)
        const qrBox = document.querySelector('.share-qr-box');
        if (qrBox) {
            qrBox.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(shareUrl)}&color=00f5d4&bgcolor=1a1d24" alt="Build QR Code" style="width: 100%; height: auto; display: block; border-radius: 12px; border: 1px solid rgba(0,245,212,0.15);" />`;
        }

        backdrop.classList.add('is-open');
    }

    function closeShareModal() {
        const backdrop = document.getElementById('shareModalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('is-open');
            // Revert to placeholder when closed for next invocation
            const qrBox = document.querySelector('.share-qr-box');
            if (qrBox) {
                qrBox.innerHTML = `
                    <div class="qr-placeholder">
                        <i class="fas fa-qrcode"></i>
                        <span>${builderText('QR CODE', 'QR CODE')}</span>
                    </div>
                `;
            }
        }
    }

    function copyShareUrl() {
        const input = document.getElementById('shareUrlInput');
        if (!input) return;
        navigator.clipboard.writeText(input.value).then(() => {
            showToast(builderText('Build URL copied!', 'Build URL copied!'), 'success');
        });
    }

    function buildQuoteText() {
        const lines = [`${buildName || 'My Build'} - Maroc PC`];
        Object.entries(selectedComponents).forEach(([key, product]) => {
            const step = STEPS.find(s => s.key === key);
            lines.push(`${step?.label || key}: ${product.name} - ${formatMAD(product.price)}`);
        });
        Object.values(selectedServices).forEach(service => {
            lines.push(`Service: ${service.name} - ${formatMAD(service.price)}`);
        });
        lines.push(`Estimated power draw: ${calculateTotalWattage(true)}W`);
        lines.push(`Total: ${formatMAD(Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal())}`);
        return lines.join('\n');
    }

    function shareWhatsApp() {
        if (Object.keys(selectedComponents).length === 0) {
            showToast(builderText('Select components before sharing.', 'Select components before sharing.'), 'error');
            return;
        }
        const text = encodeURIComponent(`${buildQuoteText()}\n\nCan you confirm availability and compatibility?`);
        window.open(`https://wa.me/212618821949?text=${text}`, '_blank', 'noopener');
    }

    function exportQuote() {
        if (Object.keys(selectedComponents).length === 0) {
            showToast(builderText('Select components before exporting.', 'Select components before exporting.'), 'error');
            return;
        }
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            showToast(builderText('Popup blocked. Allow popups to export the quote.', 'Popup blocked. Allow popups to export the quote.'), 'error');
            return;
        }
        const html = `
            <html>
            <head>
                <title>${buildName || builderText('Maroc PC Build Quote', 'Maroc PC Build Quote')}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 32px; color: #111; }
                    h1 { margin: 0 0 8px; }
                    .muted { color: #666; margin-bottom: 24px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
                    th { background: #f4f6f8; }
                    .total { font-size: 20px; font-weight: 700; text-align: right; }
                </style>
            </head>
            <body>
                <h1>${buildName || builderText('Maroc PC Build Quote', 'Maroc PC Build Quote')}</h1>
                <div class="muted">${builderText('Generated', 'Generated')} ${new Date().toLocaleString()} - ${builderText('Estimated wattage', 'Estimated wattage')} ${calculateTotalWattage(true)}W</div>
                <table>
                    <thead><tr><th>${builderText('Type', 'Type')}</th><th>${builderText('Item', 'Item')}</th><th>${builderText('Price', 'Price')}</th></tr></thead>
                    <tbody>
                        ${Object.entries(selectedComponents).map(([key, product]) => {
                            const step = STEPS.find(s => s.key === key);
                            return `<tr><td>${step?.label || key}</td><td>${product.name}</td><td>${formatMAD(product.price)}</td></tr>`;
                        }).join('')}
                        ${Object.values(selectedServices).map(service => `<tr><td>${builderText('Service', 'Service')}</td><td>${service.name}</td><td>${formatMAD(service.price)}</td></tr>`).join('')}
                    </tbody>
                </table>
                <div class="total">${builderText('Total:', 'Total:')} ${formatMAD(Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal())}</div>
                <p class="muted">${builderText('Prices and stock are estimates until confirmed by Maroc PC.', 'Prices and stock are estimates until confirmed by Maroc PC.')}</p>
            </body>
            </html>
        `;
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
printWindow.print();
    }

    // ── Toast Helper ─────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) { alert(message); return; }

        const icon = toast.querySelector('i');
        if (icon) {
            icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        }
        toast.style.borderColor = type === 'success' ? '#00f5d4' : '#ff3d5a';
        toastMsg.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Format ───────────────────────────────────────────────
    function formatMAD(n) {
        if (typeof window !== 'undefined' && typeof window.formatMAD === 'function') {
            return window.formatMAD(n);
        }
        const lang = (document.documentElement.lang || 'en').slice(0, 2);
        const currency = lang === 'ar' ? 'د.م.' : 'DH';
        return Number(n).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currency;
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    function setPlatform(platform) {
        selectedPlatform = platform;
        document.querySelectorAll('.ps-card').forEach(card => {
            card.classList.toggle('active', card.dataset.platform === platform);
        });
        showToast(builderTemplate('Building {platform} Combo...', 'Building {platform} Combo...', {platform: platform.toUpperCase()}), 'success');
        
        chooseBuilderPath('custom', false);
        autoBuild(useCase, targetBudget, false);
    }

    function shareFB() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
    }

    function shareWA() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(builderText('Check out my PC build: ', 'Check out my PC build: ') + url)}`, '_blank');
    }

    function shareTW() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(builderText('Check out my PC build: ', 'Check out my PC build: ') + url)}`, '_blank');
    }

    // ── Focus/Expert Workspace Mode ──────────────────────────
    function setWorkspaceMode(mode) {
        const workspace = document.getElementById('pcBuilderWorkspace');
        const checkbox = document.getElementById('workspaceModeToggle');
        const labelFocus = document.getElementById('modeLabelFocus');
        const labelExpert = document.getElementById('modeLabelExpert');
        const description = document.getElementById('modeDescription');

        if (!workspace) return;

        localStorage.setItem('workspaceMode', mode);

        if (mode === 'expert') {
            workspace.classList.remove('workspace-mode-focus');
            workspace.classList.add('workspace-mode-expert');
            if (checkbox) checkbox.checked = true;
            if (labelFocus) labelFocus.classList.remove('active');
            if (labelExpert) labelExpert.classList.add('active');
            if (description) description.textContent = builderText('Expert Technical Mode: full specs, socket checks, and raw gauges.', 'Expert Technical Mode: full specs, socket checks, and raw gauges.');
            
            // Expand all details sidebars in Expert mode
            document.querySelectorAll('.sidebar-accordion').forEach(acc => {
                acc.setAttribute('open', 'true');
            });
        } else {
            workspace.classList.remove('workspace-mode-expert');
            workspace.classList.add('workspace-mode-focus');
            if (checkbox) checkbox.checked = false;
            if (labelFocus) labelFocus.classList.add('active');
            if (labelExpert) labelExpert.classList.remove('active');
            if (description) description.textContent = builderText('Guided step-by-step assistant tailored for your build path.', 'Guided step-by-step assistant tailored for your build path.');

            adjustFocusAccordions();
        }
    }

    function toggleWorkspaceMode() {
        const checkbox = document.getElementById('workspaceModeToggle');
        if (checkbox) {
            const mode = checkbox.checked ? 'expert' : 'focus';
            setWorkspaceMode(mode);
        }
    }

    function adjustFocusAccordions() {
        const workspace = document.getElementById('pcBuilderWorkspace');
        if (!workspace || !workspace.classList.contains('workspace-mode-focus')) return;

        const itemCount = Object.keys(selectedComponents).length;

        const summaryAcc = document.getElementById('accordion-summary');
        const wattageAcc = document.getElementById('accordion-wattage');
        const diagnosticsAcc = document.getElementById('accordion-diagnostics');
        const servicesAcc = document.getElementById('accordion-services');

        if (itemCount === 0) {
            if (summaryAcc) summaryAcc.setAttribute('open', 'true');
            if (wattageAcc) wattageAcc.removeAttribute('open');
            if (diagnosticsAcc) diagnosticsAcc.removeAttribute('open');
            if (servicesAcc) servicesAcc.setAttribute('open', 'true');
        } else {
            if (summaryAcc) summaryAcc.setAttribute('open', 'true');
            if (wattageAcc) wattageAcc.removeAttribute('open');
            if (diagnosticsAcc) diagnosticsAcc.removeAttribute('open');
            if (servicesAcc) servicesAcc.removeAttribute('open');
        }
    }

    // ── Sticky Dock Observer ──────────────────────────────────
    function initStickyDockObserver() {
        const buildActions = document.querySelector('.build-actions');
        const stickyDock = document.getElementById('stickyBuildDock');
        if (!buildActions || !stickyDock) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) {
                    stickyDock.classList.add('visible');
                } else {
                    stickyDock.classList.remove('visible');
                }
            });
        }, {
            root: null,
            threshold: 0
        });

        observer.observe(buildActions);
    }

    // ── Price-Tier Pills ─────────────────────────────────────
    function selectPriceTier(tier) {
        activePriceTier = tier;
        renderCurrentStep();
    }

    // ── Onboarding Questionnaire Wizard ──────────────────────
    function openOnboardingWizard() {
        const modal = document.getElementById('onboardingWizardModal');
        if (!modal) return;

        wizardState.currentStep = 1;
        wizardState.useCase = 'gaming';
        wizardState.budget = 12000;
        wizardState.theme = 'performance';

        // Reset visual cards state
        document.querySelectorAll('.wizard-option-card').forEach(card => card.classList.remove('active'));

        const range = document.getElementById('wizardBudgetRange');
        if (range) range.value = 12000;
        const display = document.getElementById('wizardBudgetValue');
        if (display) display.textContent = formatMAD(12000);

        showWizardStep(1);
        modal.style.display = 'flex';
    }

    function closeOnboardingWizard() {
        const modal = document.getElementById('onboardingWizardModal');
        if (modal) modal.style.display = 'none';
    }

    function showWizardStep(stepNum) {
        wizardState.currentStep = stepNum;

        const progressFill = document.getElementById('wizardProgressFill');
        if (progressFill) {
            progressFill.style.width = (stepNum * 25) + '%';
        }

        document.querySelectorAll('.wizard-modal-step').forEach(el => {
            const step = parseInt(el.dataset.step);
            if (step === stepNum) {
                el.style.display = 'block';
                el.classList.add('active');
            } else {
                el.style.display = 'none';
                el.classList.remove('active');
            }
        });

        if (stepNum === 4) {
            triggerWizardBuild();
        }
    }

    function selectWizardOption(key, value) {
        wizardState[key] = value;

        const activeStepEl = document.querySelector(`.wizard-modal-step[data-step="${wizardState.currentStep}"]`);
        if (activeStepEl) {
            activeStepEl.querySelectorAll('.wizard-option-card').forEach(card => {
                const onclickStr = card.getAttribute('onclick') || '';
                if (onclickStr.includes(`'${value}'`)) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            });
        }

        if (wizardState.currentStep === 1) {
            setTimeout(() => nextWizardStep(), 350);
        } else if (wizardState.currentStep === 3) {
            setTimeout(() => nextWizardStep(), 350);
        }
    }

    // ── Update Wizard Budget ──────────────────────────────────
    function updateWizardBudget(val) {
        wizardState.budget = parseInt(val, 10);
        const display = document.getElementById('wizardBudgetValue');
        if (display) display.textContent = formatMAD(wizardState.budget);
    }

    function prevWizardStep() {
        if (wizardState.currentStep > 1) {
            showWizardStep(wizardState.currentStep - 1);
        }
    }

    function nextWizardStep() {
        if (wizardState.currentStep < 4) {
            showWizardStep(wizardState.currentStep + 1);
        }
    }

    function triggerWizardBuild() {
        setTimeout(() => {
            closeOnboardingWizard();

            if (wizardState.theme === 'rgb') {
                selectedServices['assembly'] = BUILD_SERVICES['assembly'];
                const assemblyCb = document.querySelector('.service-checkbox[value="assembly"]');
                if (assemblyCb) assemblyCb.checked = true;
            }

            autoBuild(wizardState.useCase, wizardState.budget, true);
            setWorkspaceMode('focus');

            const workspace = document.getElementById('pcBuilderWorkspace');
            if (workspace) {
                workspace.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1500);
    }

    // ── Public API ───────────────────────────────────────────
    return {
        init,
        addAllToCart,
        addSingleToCart,
        saveBuild,
        autoBuild,
        chooseBuilderPath,
        shareWhatsApp, // Correct mapping to itemized whatsapp quote share
        shareFB,
        shareWA,
        shareTW,
        setPlatform,
        exportQuote,
        applyGamingFinder,
        resetGamingFinder,
        useCurrentBuildForPsu,
        applyPsuChoice,
        useCurrentBuildForMemory,
        applyMemoryChoice,
        closeShareModal,
        copyShareUrl,
        focusSummary,
        getAssistantContext,
        applyAssistantProducts,
        getSelected: () => selectedComponents,

        // Premium Revamped UI/UX Expositions
        setWorkspaceMode,
        toggleWorkspaceMode,
        showPresetSelector,
        selectPriceTier,
        openOnboardingWizard,
        closeOnboardingWizard,
        selectWizardOption,
        updateWizardBudget,
        prevWizardStep,
        nextWizardStep
    };
})();

document.addEventListener('DOMContentLoaded', PCBuilder.init);
