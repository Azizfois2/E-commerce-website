<?php
declare(strict_types=1);
require_once 'bootstrap.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="compareTitleTag">Laptop Comparison | Maroc PC</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Mono&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <?= i18n_preference_assets() ?>
    <style>
        body { background: var(--page-bg); color: var(--text); font-family: 'Syne', sans-serif; }
        .compare-page { max-width: 1200px; margin: 110px auto 60px; padding: 0 20px; }
        .compare-page h1 {
            font-family: 'Orbitron', sans-serif; font-size: 2rem; text-align: center;
            background: linear-gradient(90deg, var(--white) 30%, var(--cyan) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .compare-grid {
            display: grid;
            grid-template-columns: 180px repeat(var(--cols, 2), 1fr);
            gap: 0;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: var(--glass-bg, rgba(18,18,24,0.8));
            backdrop-filter: blur(12px);
        }
        .compare-cell {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        .compare-cell.label {
            font-weight: 700;
            color: var(--muted);
            font-family: 'Space Mono', monospace;
            font-size: 0.75rem;
            text-transform: uppercase;
            background: var(--page-bg-2);
        }
        .compare-cell.best { color: var(--cyan); font-weight: 700; }
        .compare-header {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 20px 16px; border-bottom: 1px solid var(--border);
            background: var(--page-bg-2);
        }
        .compare-header img {
            width: 120px; height: 100px; object-fit: contain; border-radius: 8px;
        }
        .compare-header h3 {
            font-size: 0.9rem; margin: 0; text-align: center; font-weight: 700;
        }
        .compare-header .price {
            font-family: 'Orbitron', sans-serif; font-size: 1.1rem; font-weight: 900; color: var(--cyan);
        }
        .compare-empty {
            grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: var(--muted);
        }
        .compare-empty i { font-size: 2rem; margin-bottom: 12px; display: block; }
        .ai-badge-cmp {
            display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px;
            border-radius: 12px; font-size: 0.65rem; font-weight: 700;
            background: linear-gradient(135deg, var(--cyan), #00d4b8); color: #000;
        }
        .ai-badge-cmp.workstation { background: linear-gradient(135deg, #ff6b35, #f7931e); color: #fff; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px; color: var(--cyan);
            text-decoration: none; font-weight: 700; margin-bottom: 20px; font-size: 0.9rem;
        }
        .back-link:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .compare-grid { grid-template-columns: 100px repeat(var(--cols, 2), 1fr); font-size: 0.75rem; }
            .compare-header img { width: 80px; height: 70px; }
            .compare-page h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>
            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.components'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.pc_build_wizard'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link active"><?php i18n_e('nav.laptop_finder'); ?></a>
            </nav>
            <div class="nav-spacer" aria-hidden="true"></div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i><i class="fas fa-moon icon-moon"></i>
            </button>
            <?= i18n_language_switcher('nav-translate') ?>
            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="Cart">
                    <i class="fas fa-shopping-cart"></i><span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <div class="compare-page">
        <a href="laptop-finder.php" class="back-link"><i class="fas fa-arrow-left"></i> <span id="backToFinderText"></span></a>
        <h1><i class="fas fa-balance-scale"></i> <span id="compareTitleText"></span></h1>
        <div id="compareContent"></div>
    </div>

    <script src="assets/js/currency.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/laptop_data.js"></script>
    <?= i18n_language_switcher_assets() ?>
    <script>
    window.__i18n = {
        compareTitle: '<?= addslashes(i18n_t('laptop_finder_page.compare_title', [], 'Laptop Comparison')) ?>',
        compareSelectAtLeast: '<?= addslashes(i18n_t('laptop_finder_page.compare_select_at_least', [], 'Select at least 2 laptops to compare')) ?>',
        compareGoBackTo: '<?= addslashes(i18n_t('laptop_finder_page.compare_go_back_to', [], 'Go back to the')) ?>',
        compareBrand: '<?= addslashes(i18n_t('laptop_finder_page.compare_brand', [], 'Brand')) ?>',
        compareScreen: '<?= addslashes(i18n_t('laptop_finder_page.compare_screen', [], 'Screen')) ?>',
        compareBattery: '<?= addslashes(i18n_t('laptop_finder_page.compare_battery', [], 'Battery')) ?>',
        compareWeight: '<?= addslashes(i18n_t('laptop_finder_page.compare_weight', [], 'Weight')) ?>',
        compareAiNpu: '<?= addslashes(i18n_t('laptop_finder_page.compare_ai_npu', [], 'AI NPU')) ?>',
        compareTotalAiTops: '<?= addslashes(i18n_t('laptop_finder_page.compare_total_ai_tops', [], 'Total AI TOPS')) ?>',
        compareCopilotPlus: '<?= addslashes(i18n_t('laptop_finder_page.compare_copilot_plus', [], 'Copilot+')) ?>',
        compareCatalogPerformance: '<?= addslashes(i18n_t('laptop_finder_page.compare_catalog_performance', [], 'Catalog Performance')) ?>',
        comparePortabilityFacts: '<?= addslashes(i18n_t('laptop_finder_page.compare_portability_facts', [], 'Portability Facts')) ?>',
        compareScreenFacts: '<?= addslashes(i18n_t('laptop_finder_page.compare_screen_facts', [], 'Screen Facts')) ?>',
        compareAiNpuFacts: '<?= addslashes(i18n_t('laptop_finder_page.compare_ai_npu_facts', [], 'AI NPU Facts')) ?>',
        compareValuePrice: '<?= addslashes(i18n_t('laptop_finder_page.compare_value_price', [], 'Value / Price')) ?>',
        compareFormFactor: '<?= addslashes(i18n_t('laptop_finder_page.compare_form_factor', [], 'Form Factor')) ?>',
        compareDimensions: '<?= addslashes(i18n_t('laptop_finder_page.compare_dimensions', [], 'Dimensions')) ?>',
        compareMaxDisplays: '<?= addslashes(i18n_t('laptop_finder_page.compare_max_displays', [], 'Max Displays')) ?>',
        compareStock: '<?= addslashes(i18n_t('laptop_finder_page.compare_stock', [], 'Stock')) ?>',
        compareInStock: '<?= addslashes(i18n_t('laptop_finder_page.compare_in_stock', [], 'In Stock')) ?>',
        compareOutOfStock: '<?= addslashes(i18n_t('laptop_finder_page.compare_out_of_stock', [], 'Out of Stock')) ?>',
        comparePrice: '<?= addslashes(i18n_t('laptop_finder_page.compare_price', [], 'Price')) ?>',
        compareSelect: '<?= addslashes(i18n_t('laptop_finder_page.compare_select', [], 'Select')) ?>',
        compareNone: '<?= addslashes(i18n_t('laptop_finder_page.compare_none', [], 'None')) ?>',
        compareYes: '<?= addslashes(i18n_t('laptop_finder_page.compare_yes', [], 'Yes')) ?>',
        compareNo: '<?= addslashes(i18n_t('laptop_finder_page.compare_no', [], 'No')) ?>',
        compareWorkstation: '<?= addslashes(i18n_t('laptop_finder_page.compare_workstation', [], 'Workstation')) ?>',
        compareAiReady: '<?= addslashes(i18n_t('laptop_finder_page.compare_ai_ready', [], 'AI-Ready')) ?>',
        backToFinder: '<?= addslashes(i18n_t('laptop_finder_page.back_to_finder', [], 'Back to laptop finder')) ?>',
    };
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const ids = (params.get('ids') || '').split(',').map(Number).filter(n => n > 0);

        if (typeof laptops === 'undefined' || ids.length < 2) {
            document.getElementById('compareContent').innerHTML = `
                <div class="compare-grid" style="--cols:1;">
                    <div class="compare-empty">
                        <i class="fas fa-balance-scale"></i>
                        <h3 id="selectAtLeastMsg"></h3>
                        <p id="goBackMsg"></p>
                    </div>
                </div>`;
            return;
        }

        const selected = ids.map(id => laptops.find(l => l.id === id)).filter(Boolean).slice(0, 3);
        const cols = selected.length;
        const fmt = v => window.formatMAD ? window.formatMAD(v) : Number(v || 0).toLocaleString() + ' DH';
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));

        // Find best values for highlighting
        const bestPrice = Math.min(...selected.map(l => l.price));
        const bestBattery = Math.max(...selected.map(l => l.batteryWh || 0));
        const bestWeight = Math.min(...selected.map(l => l.weightKg || 99));
        const bestTops = Math.max(...selected.map(l => l.npuTops || 0));
        const bestDisplays = Math.max(...selected.map(l => l.maxDisplays || 0));
        const bestCatalogPerf = Math.max(...selected.map(l => l.scores?.performance || 0));
        const bestCatalogPort = Math.max(...selected.map(l => l.scores?.portability || 0));
        const bestCatalogScreen = Math.max(...selected.map(l => l.scores?.screen || 0));
        const bestCatalogAi = Math.max(...selected.map(l => l.scores?.ai || 0));
        const bestCatalogValue = Math.max(...selected.map(l => l.scores?.value || 0));

        let html = `<div class="compare-grid" style="--cols:${cols};">`;

        // Header row
        html += `<div class="compare-cell label"></div>`;
        selected.forEach(l => {
            const aiLabel = l.aiTier === 'workstation' ? (window.__i18n?.compareWorkstation || 'Workstation') : (l.isCopilotPlus ? (window.__i18n?.compareCopilotPlus || 'Copilot+') : (Number(l.npuTops || 0) > 0 ? (window.__i18n?.compareAiReady || 'AI-Ready') : ''));
            const aiBadge = aiLabel
                ? `<div class="ai-badge-cmp ${l.aiTier === 'workstation' ? 'workstation' : ''}"><i class="fas fa-brain"></i> ${aiLabel} ${l.npuTops || 0}T</div>`
                : '';
            html += `<div class="compare-header">
                <img src="${esc(l.image)}" alt="${esc(l.name)}" onerror="this.src='images/products/generic-laptop.png'">
                <h3 class="notranslate" translate="no">${esc(l.name)}</h3>
                ${aiBadge}
                <div class="price">${fmt(l.price)}</div>
            </div>`;
        });

        // Comparison rows
        const rows = [
            { label: window.__i18n?.compareBrand || 'Brand', vals: selected.map(l => l.brand), best: null },
            { label: 'CPU', vals: selected.map(l => l.specs?.CPU || '-'), best: null },
            { label: 'RAM', vals: selected.map(l => l.specs?.RAM || '-'), best: null },
            { label: 'Storage', vals: selected.map(l => l.specs?.Storage || '-'), best: null },
            { label: 'GPU', vals: selected.map(l => l.specs?.GPU || '-'), best: null },
            { label: window.__i18n?.compareScreen || 'Screen', vals: selected.map(l => l.screenSize ? `${l.screenSize}" ${l.screenQuality}` : '-'), best: null },
            { label: window.__i18n?.compareBattery || 'Battery', vals: selected.map(l => l.batteryWh ? `${l.batteryWh} Wh` : '-'), bestIdx: bestBattery > 0 ? selected.map(l => l.batteryWh || 0).indexOf(bestBattery) : -1 },
            { label: window.__i18n?.compareWeight || 'Weight', vals: selected.map(l => l.weightKg ? `${l.weightKg} kg` : '-'), bestIdx: bestWeight < 99 ? selected.map(l => l.weightKg || 99).indexOf(bestWeight) : -1 },
            { label: window.__i18n?.compareAiNpu || 'AI NPU', vals: selected.map(l => l.npuModel ? `${l.npuModel} (${l.npuTops} TOPS)` : (window.__i18n?.compareNone || 'None')), bestIdx: bestTops > 0 ? selected.map(l => l.npuTops || 0).indexOf(bestTops) : -1 },
            { label: window.__i18n?.compareTotalAiTops || 'Total AI TOPS', vals: selected.map(l => l.specs?.['Total AI TOPS'] || '-'), best: null },
            { label: window.__i18n?.compareCopilotPlus || 'Copilot+', vals: selected.map(l => l.isCopilotPlus ? (window.__i18n?.compareYes || 'Yes') : (window.__i18n?.compareNo || 'No')), best: null },
            { label: window.__i18n?.compareCatalogPerformance || 'Catalog Performance', vals: selected.map(l => (l.scores?.performance || 0).toFixed(1)), bestIdx: selected.map(l => l.scores?.performance || 0).indexOf(bestCatalogPerf) },
            { label: window.__i18n?.comparePortabilityFacts || 'Portability Facts', vals: selected.map(l => (l.scores?.portability || 0).toFixed(1)), bestIdx: selected.map(l => l.scores?.portability || 0).indexOf(bestCatalogPort) },
            { label: window.__i18n?.compareScreenFacts || 'Screen Facts', vals: selected.map(l => (l.scores?.screen || 0).toFixed(1)), bestIdx: selected.map(l => l.scores?.screen || 0).indexOf(bestCatalogScreen) },
            { label: window.__i18n?.compareAiNpuFacts || 'AI NPU Facts', vals: selected.map(l => (l.scores?.ai || 0).toFixed(1)), bestIdx: selected.map(l => l.scores?.ai || 0).indexOf(bestCatalogAi) },
            { label: window.__i18n?.compareValuePrice || 'Value / Price', vals: selected.map(l => (l.scores?.value || 0).toFixed(1)), bestIdx: selected.map(l => l.scores?.value || 0).indexOf(bestCatalogValue) },
            { label: window.__i18n?.compareFormFactor || 'Form Factor', vals: selected.map(l => l.formFactor || l.category || 'laptop'), best: null },
            { label: window.__i18n?.compareDimensions || 'Dimensions', vals: selected.map(l => l.dimensions || '-'), best: null },
            { label: window.__i18n?.compareMaxDisplays || 'Max Displays', vals: selected.map(l => l.maxDisplays ? `${l.maxDisplays}` : '-'), bestIdx: bestDisplays > 0 ? selected.map(l => l.maxDisplays || 0).indexOf(bestDisplays) : -1 },
            { label: window.__i18n?.compareStock || 'Stock', vals: selected.map(l => l.inStock ? `${window.__i18n?.compareInStock || 'In Stock'} (${l.stockQuantity})` : (window.__i18n?.compareOutOfStock || 'Out of Stock')), best: null },
        ];

        rows.forEach(row => {
            html += `<div class="compare-cell label">${row.label}</div>`;
            row.vals.forEach((val, i) => {
                const isBest = row.bestIdx === i;
                html += `<div class="compare-cell${isBest ? ' best' : ''}">${esc(val)}${isBest ? ' <i class="fas fa-trophy" style="margin-left:6px;font-size:0.7rem;"></i>' : ''}</div>`;
            });
        });

        // Price row
        html += `<div class="compare-cell label" style="font-weight:900;">${window.__i18n?.comparePrice || 'Price'}</div>`;
        selected.forEach(l => {
            const isBest = l.price === bestPrice;
            html += `<div class="compare-cell${isBest ? ' best' : ''}" style="font-family:'Orbitron',sans-serif;font-weight:900;font-size:1rem;">${fmt(l.price)}${isBest ? ' <i class="fas fa-tag" style="margin-left:6px;font-size:0.7rem;"></i>' : ''}</div>`;
        });

        html += `</div>`;

        // Action buttons
        html += `<div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;justify-content:center;">`;
        selected.forEach(l => {
            html += `<button onclick="window.location.href='laptop-finder.php'" style="background:var(--cyan);color:#000;border:none;padding:12px 24px;border-radius:10px;font-weight:700;cursor:pointer;">
                <i class="fas fa-cart-plus"></i> ${window.__i18n?.compareSelect || 'Select'} ${esc(l.brand)}
            </button>`;
        });
        html += `</div>`;

        document.getElementById('compareContent').innerHTML = html;
    });


    // Set i18n text content on page load
    (function() {
        var t = function(key, fallback) { return (window.__i18n && window.__i18n[key]) ? window.__i18n[key] : fallback; };
        var el = function(id) { return document.getElementById(id); };
        
        if (el('backToFinderText')) el('backToFinderText').textContent = t('backToFinder', 'Back to laptop finder');
        if (el('compareTitleText')) el('compareTitleText').textContent = t('compareTitle', 'Laptop Comparison');
        if (el('compareTitleTag')) el('compareTitleTag').textContent = t('compareTitle', 'Laptop Comparison') + ' | Maroc PC';
        if (el('selectAtLeastMsg')) el('selectAtLeastMsg').textContent = t('compareSelectAtLeast', 'Select at least 2 laptops to compare');
        if (el('goBackMsg')) {
            var goBack = t('compareGoBackTo', 'Go back to the');
            var finderName = t('backToFinder', 'Laptop Finder');
            el('goBackMsg').innerHTML = goBack + ' <a href="laptop-finder.php" style="color:var(--cyan);">' + finderName + '</a>';
        }
    })();

    </script>
</body>
</html>
