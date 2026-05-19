<?php
declare(strict_types=1);
require_once 'bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Laptop | Maroc PC Curated Ecosystem</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Mono&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Base App Stylesheets -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/products.css">
    <link rel="stylesheet" href="assets/css/installment-compare.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    
    <!-- Curated Finder Styles -->
    <style>
        :root {
            --cyan-glow: rgba(0, 245, 212, 0.15);
            --orange-glow: rgba(255, 107, 53, 0.15);
            --glass-bg: rgba(18, 18, 24, 0.8);
            --card-border-active: var(--cyan);
        }

        [data-theme="light"] {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --cyan-glow: rgba(0, 168, 143, 0.12);
            --orange-glow: rgba(224, 90, 32, 0.12);
        }

        body {
            background-color: var(--page-bg);
            color: var(--text);
            font-family: 'Syne', sans-serif;
            overflow-x: hidden;
        }

        .finder-container {
            max-width: 1400px;
            margin: 110px auto 40px;
            padding: 0 24px;
        }

        .finder-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .finder-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, var(--white) 30%, var(--cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .finder-header p {
            font-size: 1.15rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* The Golden Grid */
        .finder-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 32px;
            align-items: start;
        }

        /* Glass Cockpit Cockpit (Filters Panel) */
        .cockpit-panel {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--cyan) transparent;
        }
        .cockpit-panel::-webkit-scrollbar {
            width: 4px;
        }
        .cockpit-panel::-webkit-scrollbar-thumb {
            background: var(--cyan);
            border-radius: 2px;
        }

        .cockpit-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        .cockpit-title i {
            color: var(--cyan);
        }

        .filter-section {
            margin-bottom: 28px;
        }

        .filter-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 12px;
            display: block;
            font-family: 'Space Mono', monospace;
        }

        /* Card Selectors */
        .selector-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .selector-group.full-width {
            grid-template-columns: 1fr;
        }

        .selector-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .selector-card:hover {
            border-color: var(--cyan);
            background: var(--card-bg-hover);
            transform: translateY(-2px);
        }

        .selector-card.active {
            border-color: var(--cyan);
            background: transparent;
            box-shadow: none;
            color: var(--cyan-dim);
        }

        .selector-card i {
            font-size: 1.4rem;
            margin-bottom: 6px;
            display: block;
            color: var(--muted);
            transition: color 0.25s;
        }

        .selector-card.active i {
            color: var(--cyan-dim);
        }

        .selector-card span {
            font-size: 0.85rem;
            font-weight: 700;
            display: block;
        }

        /* Budget Range Slider styling */
        .budget-slider-container {
            padding: 10px 0;
        }

        .budget-slider {
            width: 100%;
            -webkit-appearance: none;
            height: 6px;
            border-radius: 3px;
            background: var(--border);
            outline: none;
            margin: 12px 0;
        }

        .budget-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--cyan);
            cursor: pointer;
            box-shadow: 0 0 8px var(--cyan);
            transition: transform 0.1s;
        }

        .budget-slider::-webkit-slider-thumb:hover {
            transform: scale(1.25);
        }

        .budget-values {
            display: flex;
            justify-content: space-between;
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .budget-current {
            color: var(--cyan);
            font-weight: 700;
            font-size: 1rem;
        }

        /* Results Panel */
        .results-panel {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        #laptopsContainer {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
            font-family: 'Space Mono', monospace;
        }

        .results-count {
            font-size: 1rem;
            color: var(--muted);
        }

        .results-count span {
            color: var(--cyan);
            font-weight: 700;
        }

        /* Outcome Laptop Card */
        .laptop-card {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: grid;
            grid-template-columns: 240px 1fr 280px;
            gap: 28px;
            align-items: center;
            backdrop-filter: blur(12px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .laptop-card:hover {
            border-color: var(--cyan);
            box-shadow: 0 8px 30px rgba(0, 245, 212, 0.08);
            transform: translateY(-3px);
        }

        /* Custom badge overlay on high match card */
        .match-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--cyan);
            color: var(--page-bg);
            font-family: 'Space Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0 10px var(--cyan-glow);
            z-index: 2;
        }

        .laptop-image-container {
            width: 100%;
            height: 180px;
            display: grid;
            place-items: center;
            background: var(--page-bg-2);
            border-radius: 12px;
            padding: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .laptop-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }

        .laptop-card:hover .laptop-image-container img {
            transform: scale(1.06);
        }

        .laptop-details h3 {
            font-size: 1.45rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--white);
        }

        .laptop-brand {
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
        }

        .laptop-specs-mini {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .spec-item {
            font-size: 0.85rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .spec-item i {
            color: var(--text);
            width: 14px;
        }

        /* Outcome Metric Bars */
        .metric-container {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }

        .metric-bar-group {
            display: grid;
            grid-template-columns: 120px 1fr 40px;
            align-items: center;
            gap: 12px;
        }

        .metric-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            font-family: 'Space Mono', monospace;
        }

        .metric-track {
            height: 6px;
            background: var(--page-bg-2);
            border-radius: 3px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .metric-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease-out;
        }

        .metric-fill.performance { background: var(--orange); }
        .metric-fill.portability { background: var(--cyan); }
        .metric-fill.screen { background: var(--diagnostic-purple); }
        .metric-fill.value { background: #2ec4b6; }

        .metric-val {
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: right;
        }

        /* Checkout Upsell & Action Panel */
        .action-panel {
            text-align: right;
            border-left: 1px solid var(--border);
            padding-left: 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .price-box {
            margin-bottom: 20px;
        }

        .laptop-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--white);
        }

        .laptop-old-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            color: var(--muted);
            text-decoration: line-through;
            margin-top: 4px;
        }

        /* Aggressive Upsell Checkbox */
        .upsell-box {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: left;
            transition: all 0.25s;
            cursor: pointer;
            user-select: none;
        }

        .upsell-box:hover {
            border-color: var(--orange);
            background: var(--orange-glow);
        }

        .upsell-box.active {
            border-color: var(--orange);
            background: rgba(255, 107, 53, 0.12);
        }

        .upsell-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 6px;
        }

        .upsell-header i {
            color: var(--orange);
        }

        .upsell-body {
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.4;
        }

        .upsell-price {
            color: var(--orange);
            font-weight: 700;
            font-family: 'Space Mono', monospace;
        }

        .btn-select {
            background: var(--cyan);
            color: var(--page-bg);
            border: none;
            border-radius: 6px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.95rem;
            padding: 14px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            transition: all 0.25s;
        }

        .btn-select:hover {
            background: var(--white);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
            transform: scale(1.02);
        }

        .btn-quickview {
            background: transparent;
            color: var(--cyan);
            border: 1px solid var(--cyan);
            border-radius: 6px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-bottom: 12px;
            transition: all 0.25s;
        }

        .btn-quickview:hover {
            background: var(--cyan-glow);
            color: var(--white);
            border-color: var(--white);
            transform: scale(1.02);
        }

        .laptop-image-container {
            position: relative;
            cursor: pointer;
        }
        
        .laptop-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 245, 212, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
        }
        
        .laptop-image-container:hover .laptop-image-overlay {
            opacity: 1;
        }
        
        .laptop-image-overlay span {
            background: var(--page-bg);
            color: var(--cyan);
            border: 1px solid var(--cyan);
            padding: 6px 12px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .empty-match-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .empty-match-state i {
            font-size: 3rem;
            color: var(--orange);
            margin-bottom: 16px;
        }

        .empty-match-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--white);
        }

        .empty-match-state p {
            color: var(--muted);
            margin-bottom: 24px;
        }

        .alternative-headline {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--orange);
            margin-top: 32px;
            margin-bottom: 16px;
            text-align: center;
            position: relative;
        }

        .alternative-headline::before,
        .alternative-headline::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 80px;
            height: 1px;
            background: var(--border);
        }

        .alternative-headline::before { left: 20%; }
        .alternative-headline::after { right: 20%; }

        @media (max-width: 1024px) {
            .finder-grid {
                grid-template-columns: 1fr;
            }
            .cockpit-panel {
                position: static;
            }
            .laptop-card {
                grid-template-columns: 1fr;
            }
            .action-panel {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--border);
                padding-top: 20px;
            }
        }
    </style>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>

    <!-- Shared Header Navigation -->
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>

            <a href="index.html" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>

            <!-- Split Navigation -->
            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Components</a>
                <a href="builder.php" class="nav-link">PC Build Wizard</a>
                <a href="laptop-finder.php" class="nav-link active">Find Your Laptop</a>
            </nav>

            <div class="nav-spacer" aria-hidden="true"></div>

            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div id="google_translate_element" class="nav-translate"></div>

            <div class="cart-wrapper" id="userNav">
                <a href="login.html" class="cart-icon" aria-label="Account">
                    <i class="fas fa-user"></i>
                </a>
            </div>

            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <div class="finder-container">
        
        <!-- Header Section -->
        <section class="finder-header">
            <h1>Advanced+ Laptop Finder</h1>
            <p>Outcome-Oriented Curation. Tell us what your laptop needs to <strong>accomplish</strong>, and let our curator map the perfect machine.</p>
        </section>

        <!-- Main Workspace Grid -->
        <div class="finder-grid">
            
            <!-- Cockpit (Filters Sidebar) -->
            <aside class="cockpit-panel">
                <div class="cockpit-title">
                    <i class="fas fa-sliders"></i>
                    <span>Golden Filters</span>
                </div>

                <!-- 1. Primary Use -->
                <div class="filter-section">
                    <span class="filter-label">1. Primary Outcome Target</span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="usage" data-val="gaming">
                            <i class="fas fa-gamepad"></i>
                            <span>Gaming</span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="business">
                            <i class="fas fa-briefcase"></i>
                            <span>Business</span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="student">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Student</span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="creative">
                            <i class="fas fa-palette"></i>
                            <span>Creative</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Portability -->
                <div class="filter-section">
                    <span class="filter-label">2. Portability Preference</span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="portability" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span>Any Class</span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="ultralight">
                            <i class="fas fa-feather-pointed"></i>
                            <span>Ultralight</span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="standard">
                            <i class="fas fa-laptop"></i>
                            <span>Standard</span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="desktop_replacement">
                            <i class="fas fa-desktop"></i>
                            <span>Heavy Power</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Screen Quality -->
                <div class="filter-section">
                    <span class="filter-label">3. Screen Excellence</span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="screen" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span>Any Quality</span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="oled">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <span>OLED Color</span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="high_refresh">
                            <i class="fas fa-bolt"></i>
                            <span>High Refresh</span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="standard">
                            <i class="fas fa-tv"></i>
                            <span>Standard IPS</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Graphics Tier -->
                <div class="filter-section">
                    <span class="filter-label">4. Graphics Core GPU</span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="gpu" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span>Any GPU</span>
                        </div>
                        <div class="selector-card" data-filter="gpu" data-val="dedicated">
                            <i class="fas fa-server"></i>
                            <span>Dedicated RTX</span>
                        </div>
                        <div class="selector-card" data-filter="gpu" data-val="integrated">
                            <i class="fas fa-microchip"></i>
                            <span>Integrated</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Budget Slider -->
                <div class="filter-section">
                    <span class="filter-label">5. Maximum Budget</span>
                    <div class="budget-slider-container">
                        <input type="range" min="7000" max="45000" step="1000" value="45000" class="budget-slider" id="budgetRange">
                        <div class="budget-values">
                            <span>7k MAD</span>
                            <span class="budget-current" id="budgetCurrent">45,000 MAD</span>
                            <span>45k+ MAD</span>
                        </div>
                    </div>
                </div>

            </aside>

            <!-- Results Output -->
            <main class="results-panel">
                
                <div class="results-header">
                    <span class="results-count">Matches Located: <span id="matchCount">0</span></span>
                    <span>Maroc PC Quality Checked</span>
                </div>

                <!-- Laptops Cards Container -->
                <div id="laptopsContainer"></div>

            </main>

        </div>

    </div>

    <!-- Quick View Modal -->
    <div class="modal" id="quickViewModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close" id="modalCloseBtn"><i class="fas fa-times"></i></button>
            <div class="modal-body" id="quickViewContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Toast elements -->
    <div id="toast" class="toast">
        <span id="toastMessage"></span>
    </div>

    <!-- Standard footer scripts -->
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/installment.js"></script>
    <script src="assets/js/reviews.js"></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    
    <!-- Outcome database -->
    <script src="assets/js/laptop_data.js"></script>

    <!-- Curator Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Selected filter state
            const state = {
                usage: 'gaming',
                portability: 'any',
                screen: 'any',
                gpu: 'any',
                budget: 45000
            };

            const els = {
                budgetSlider: document.getElementById('budgetRange'),
                budgetCurrent: document.getElementById('budgetCurrent'),
                container: document.getElementById('laptopsContainer'),
                matchCount: document.getElementById('matchCount')
            };

            // Bind selectors
            document.querySelectorAll('.selector-card').forEach(card => {
                card.addEventListener('click', () => {
                    const filterName = card.getAttribute('data-filter');
                    const value = card.getAttribute('data-val');

                    // Deactivate siblings
                    card.parentElement.querySelectorAll('.selector-card').forEach(sibling => {
                        sibling.classList.remove('active');
                    });

                    card.classList.add('active');
                    state[filterName] = value;
                    render();
                });
            });

            // Bind slider
            els.budgetSlider.addEventListener('input', (e) => {
                const val = parseInt(e.target.value, 10);
                state.budget = val;
                els.budgetCurrent.textContent = val >= 45000 ? 'No Limit' : val.toLocaleString() + ' MAD';
                render();
            });

            function getSampleSpecs(specs) {
                if (!specs) return '';
                return Object.entries(specs).slice(0, 4).map(([key, val]) => `
                    <div class="spec-item">
                        <i class="fas fa-chevron-right"></i>
                        <strong>${key}:</strong> <span class="notranslate" translate="no">${val}</span>
                    </div>
                `).join('');
            }

            function getBadgeText(laptop) {
                if (laptop.usageCategory === 'gaming' && laptop.scores.performance >= 9) {
                    return 'Desktop Replacement Power';
                }
                if (laptop.portabilityTier === 'ultralight') {
                    return 'Ultimate Road Warrior';
                }
                if (laptop.usageCategory === 'student' && laptop.scores.value >= 9) {
                    return 'Highest Value Student';
                }
                return 'Outcome Verified';
            }

            function render() {
                // Ensure laptops is defined
                if (typeof laptops === 'undefined') {
                    els.container.innerHTML = '<p class="text-center">Laptop database is not loaded.</p>';
                    return;
                }

                // Filter items
                let matches = laptops.filter(laptop => {
                    // Check budget
                    if (state.budget < 45000 && laptop.price > state.budget) {
                        return false;
                    }
                    // Check primary usage
                    if (laptop.usageCategory !== state.usage) {
                        return false;
                    }
                    // Check Portability
                    if (state.portability !== 'any' && laptop.portabilityTier !== state.portability) {
                        return false;
                    }
                    // Check Screen
                    if (state.screen !== 'any' && laptop.screenQuality !== state.screen) {
                        return false;
                    }
                    // Check GPU
                    if (state.gpu !== 'any' && laptop.gpuTier !== state.gpu) {
                        return false;
                    }
                    return true;
                });

                let showingAlternatives = false;

                // Alternate search: if no exact matches found, find closest outcome matches
                if (matches.length === 0) {
                    showingAlternatives = true;
                    // Sort all laptops by how close they match usage and price
                    matches = laptops
                        .filter(l => state.budget >= 45000 || l.price <= state.budget + 3000) // soft budget check
                        .map(l => {
                            // Calculate match score
                            let score = 0;
                            if (l.usageCategory === state.usage) score += 5;
                            if (state.portability === 'any' || l.portabilityTier === state.portability) score += 2;
                            if (state.screen === 'any' || l.screenQuality === state.screen) score += 2;
                            if (state.gpu === 'any' || l.gpuTier === state.gpu) score += 2;
                            
                            // price closeness
                            const priceDiff = Math.abs(l.price - state.budget);
                            score += Math.max(0, 3 - (priceDiff / 5000));

                            return { laptop: l, matchScore: score };
                        })
                        .sort((a, b) => b.matchScore - a.matchScore)
                        .slice(0, 3)
                        .map(item => item.laptop);
                }

                els.matchCount.textContent = showingAlternatives ? '0 (Showing closest alternatives)' : matches.length.toString();

                if (matches.length === 0) {
                    els.container.innerHTML = `
                        <div class="empty-match-state">
                            <i class="fas fa-triangle-exclamation"></i>
                            <h3>No suitable laptops found</h3>
                            <p>Try expanding your maximum budget or clearing some filter constraints.</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                if (showingAlternatives) {
                    html += `<div class="alternative-headline"><i class="fas fa-compass"></i> Surfacing Closest Alternatives</div>`;
                }

                matches.forEach(laptop => {
                    const priceFormatted = laptop.price.toLocaleString() + ' MAD';
                    const oldPriceHtml = laptop.oldPrice 
                        ? `<div class="laptop-old-price">${laptop.oldPrice.toLocaleString()} MAD</div>` 
                        : '';

                    html += `
                        <div class="laptop-card" data-laptop-id="${laptop.id}">
                            <div class="match-badge">${getBadgeText(laptop)}</div>
                            <div class="laptop-image-container" onclick="openLaptopModal(${laptop.id})">
                                <img src="${laptop.image}" alt="${laptop.name}" onerror="this.src='images/products/generic-laptop.png'">
                                <div class="laptop-image-overlay">
                                    <span><i class="fas fa-eye"></i> Quick View</span>
                                </div>
                            </div>
                            
                            <div class="laptop-details">
                                <span class="laptop-brand notranslate" translate="no">${laptop.brand}</span>
                                <h3 class="notranslate" translate="no" style="cursor: pointer;" onclick="openLaptopModal(${laptop.id})">${laptop.name}</h3>
                                
                                <div class="laptop-specs-mini">
                                    ${getSampleSpecs(laptop.specs)}
                                </div>

                                <!-- Outcome Metrics -->
                                <div class="metric-container">
                                    <div class="metric-bar-group">
                                        <span class="metric-label">Gaming/AI</span>
                                        <div class="metric-track">
                                            <div class="metric-fill performance" style="width: ${laptop.scores.performance * 10}%"></div>
                                        </div>
                                        <span class="metric-val">${laptop.scores.performance}</span>
                                    </div>
                                    <div class="metric-bar-group">
                                        <span class="metric-label">Portability</span>
                                        <div class="metric-track">
                                            <div class="metric-fill portability" style="width: ${laptop.scores.portability * 10}%"></div>
                                        </div>
                                        <span class="metric-val">${laptop.scores.portability}</span>
                                    </div>
                                    <div class="metric-bar-group">
                                        <span class="metric-label">Premium Screen</span>
                                        <div class="metric-track">
                                            <div class="metric-fill screen" style="width: ${laptop.scores.screen * 10}%"></div>
                                        </div>
                                        <span class="metric-val">${laptop.scores.screen}</span>
                                    </div>
                                    <div class="metric-bar-group">
                                        <span class="metric-label">Value Ratio</span>
                                        <div class="metric-track">
                                            <div class="metric-fill value" style="width: ${laptop.scores.value * 10}%"></div>
                                        </div>
                                        <span class="metric-val">${laptop.scores.value}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="action-panel">
                                <div class="price-box">
                                    <div class="laptop-price">${priceFormatted}</div>
                                    ${oldPriceHtml}
                                </div>

                                <!-- Optimization Pack Upsell -->
                                <div class="upsell-box" onclick="toggleUpsell(this, ${laptop.id})">
                                    <div class="upsell-header">
                                        <input type="checkbox" class="upsell-checkbox" id="upsell-${laptop.id}" style="pointer-events: none;">
                                        <i class="fas fa-fire"></i>
                                        <span>Maroc Optimization Pack</span>
                                    </div>
                                    <div class="upsell-body">
                                        Clean Windows install, thermal repaste, zero bloatware (+<span class="upsell-price">499 MAD</span>).
                                    </div>
                                </div>

                                <button class="btn-quickview" onclick="openLaptopModal(${laptop.id})">
                                    <i class="fas fa-eye"></i>
                                    <span>Quick View</span>
                                </button>

                                <button class="btn-select" onclick="buyLaptop(${laptop.id})">
                                    <i class="fas fa-cart-plus"></i>
                                    <span>Select Laptop</span>
                                </button>
                            </div>
                        </div>
                    `;
                });

                els.container.innerHTML = html;
            }

            // Expose render globally for upsell checkbox clicks
            window.toggleUpsell = (box, laptopId) => {
                const chk = document.getElementById('upsell-' + laptopId);
                if (!chk) return;
                chk.checked = !chk.checked;
                if (chk.checked) {
                    box.classList.add('active');
                } else {
                    box.classList.remove('active');
                }
            };

            window.buyLaptop = (laptopId) => {
                const laptop = laptops.find(l => l.id === laptopId);
                if (!laptop) return;

                const cart = window.Cart;
                if (!cart) {
                    console.error("Cart system is missing.");
                    return;
                }

                // Format laptop as product item
                const productItem = {
                    id: 'laptop-' + laptop.id,
                    name: laptop.name,
                    brand: laptop.brand,
                    category: 'Laptops',
                    price: laptop.price,
                    image: laptop.image,
                    inStock: true
                };

                // Add laptop to cart
                cart.add(productItem);

                // Add optimization pack if selected
                const chk = document.getElementById('upsell-' + laptopId);
                if (chk && chk.checked) {
                    const packItem = {
                        id: 'laptop-opt-pack',
                        name: 'Laptop Optimization Pack (Clean Setup, Repaste, Support)',
                        brand: 'Maroc PC Services',
                        category: 'Services',
                        price: 499.00,
                        image: 'images/products/placeholder-service.svg',
                        inStock: true
                    };
                    cart.add(packItem);
                }
            };

            window.openLaptopModal = (laptopId) => {
                const laptop = laptops.find(l => l.id === laptopId);
                if (!laptop) return;

                const modal = document.getElementById('quickViewModal');
                const content = document.getElementById('quickViewContent');
                if (!modal || !content) return;

                const discount = laptop.oldPrice
                    ? Math.round(((laptop.oldPrice - laptop.price) / laptop.oldPrice) * 100)
                    : 0;

                const getSpecsHtml = (specs) => {
                    if (!specs) return '';
                    return Object.entries(specs).map(([key, val]) => `
                        <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                            <div class="spec-key" style="color: var(--muted); font-weight: 700;">${key}</div>
                            <div class="spec-val notranslate" translate="no" style="color: var(--white); font-weight: 500;">${val}</div>
                        </div>
                    `).join('');
                };

                content.innerHTML = `
                    <div class="modal-image" style="background: var(--page-bg-2); border-radius: 12px; padding: 20px; display: grid; place-items: center; border: 1px solid var(--border);">
                        <img src="${laptop.image}" alt="${laptop.name}" onerror="this.src='images/products/generic-laptop.png'" style="max-width: 100%; max-height: 400px; object-fit: contain;">
                    </div>
                    <div class="modal-details" style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="product-category" style="font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--cyan); text-transform: uppercase; letter-spacing: 1px;">Laptops · <span class="notranslate" translate="no">${laptop.brand}</span></div>
                        <h2 class="notranslate" translate="no" style="font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 900; color: var(--white);">${laptop.name}</h2>
                        
                        <div class="product-price-row" style="display: flex; align-items: baseline; gap: 12px;">
                            <span class="product-price" style="font-family: 'Orbitron', sans-serif; font-size: 2.2rem; font-weight: 900; color: var(--white);">${laptop.price.toLocaleString()} MAD</span>
                            ${laptop.oldPrice ? `<span class="product-old-price" style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; color: var(--muted); text-decoration: line-through;">${laptop.oldPrice.toLocaleString()} MAD</span>` : ''}
                            ${discount > 0 ? `<span class="product-discount" style="background: var(--orange); color: var(--page-bg); font-weight: 900; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-family: 'Orbitron', sans-serif;">-${discount}%</span>` : ''}
                        </div>

                        <!-- Outcome Ratings inside Modal -->
                        <div class="metric-container" style="background: var(--page-bg-3); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin: 10px 0; display: flex; flex-direction: column; gap: 12px;">
                            <h4 style="font-family: 'Orbitron', sans-serif; font-size: 0.95rem; text-transform: uppercase; color: var(--white); margin: 0 0 4px 0; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">Outcome Ratings</h4>
                            
                            <div class="metric-bar-group" style="display: grid; grid-template-columns: 120px 1fr 40px; align-items: center; gap: 12px;">
                                <span class="metric-label" style="font-size: 0.72rem; text-transform: uppercase; color: var(--muted); font-family: 'Space Mono', monospace;">Gaming/AI</span>
                                <div class="metric-track" style="height: 6px; background: var(--page-bg-2); border-radius: 3px; overflow: hidden; border: 1px solid var(--border);">
                                    <div class="metric-fill" style="height: 100%; background: var(--orange); width: ${laptop.scores.performance * 10}%; transition: width 1s ease-out;"></div>
                                </div>
                                <span class="metric-val" style="font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--white); text-align: right; font-weight: 700;">${laptop.scores.performance}</span>
                            </div>
                            
                            <div class="metric-bar-group" style="display: grid; grid-template-columns: 120px 1fr 40px; align-items: center; gap: 12px;">
                                <span class="metric-label" style="font-size: 0.72rem; text-transform: uppercase; color: var(--muted); font-family: 'Space Mono', monospace;">Portability</span>
                                <div class="metric-track" style="height: 6px; background: var(--page-bg-2); border-radius: 3px; overflow: hidden; border: 1px solid var(--border);">
                                    <div class="metric-fill" style="height: 100%; background: var(--cyan); width: ${laptop.scores.portability * 10}%; transition: width 1s ease-out;"></div>
                                </div>
                                <span class="metric-val" style="font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--white); text-align: right; font-weight: 700;">${laptop.scores.portability}</span>
                            </div>
                            
                            <div class="metric-bar-group" style="display: grid; grid-template-columns: 120px 1fr 40px; align-items: center; gap: 12px;">
                                <span class="metric-label" style="font-size: 0.72rem; text-transform: uppercase; color: var(--muted); font-family: 'Space Mono', monospace;">Premium Screen</span>
                                <div class="metric-track" style="height: 6px; background: var(--page-bg-2); border-radius: 3px; overflow: hidden; border: 1px solid var(--border);">
                                    <div class="metric-fill" style="height: 100%; background: var(--diagnostic-purple); width: ${laptop.scores.screen * 10}%; transition: width 1s ease-out;"></div>
                                </div>
                                <span class="metric-val" style="font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--white); text-align: right; font-weight: 700;">${laptop.scores.screen}</span>
                            </div>
                            
                            <div class="metric-bar-group" style="display: grid; grid-template-columns: 120px 1fr 40px; align-items: center; gap: 12px;">
                                <span class="metric-label" style="font-size: 0.72rem; text-transform: uppercase; color: var(--muted); font-family: 'Space Mono', monospace;">Value Ratio</span>
                                <div class="metric-track" style="height: 6px; background: var(--page-bg-2); border-radius: 3px; overflow: hidden; border: 1px solid var(--border);">
                                    <div class="metric-fill" style="height: 100%; background: #2ec4b6; width: ${laptop.scores.value * 10}%; transition: width 1s ease-out;"></div>
                                </div>
                                <span class="metric-val" style="font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--white); text-align: right; font-weight: 700;">${laptop.scores.value}</span>
                            </div>
                        </div>

                        <p class="description" style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; margin: 0;">Outcome-verified lifestyle portable workstation curation. Extensively vetted for performance stability, hardware craftsmanship, thermal design, and power efficiency.</p>
                        
                        <div class="trust-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 10px 0;">
                            <div class="trust-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--page-bg-3); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.8rem; font-weight: 700;">
                                <i class="fas fa-shield-halved" style="color: var(--cyan); width: 18px; text-align: center;"></i>
                                <span>1 Year Maroc PC Warranty</span>
                            </div>
                            <div class="trust-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--page-bg-3); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.8rem; font-weight: 700;">
                                <i class="fab fa-whatsapp" style="color: var(--cyan); width: 18px; text-align: center;"></i>
                                <span>WhatsApp expert advise</span>
                            </div>
                            <div class="trust-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--page-bg-3); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.8rem; font-weight: 700;">
                                <i class="fas fa-truck-fast" style="color: var(--cyan); width: 18px; text-align: center;"></i>
                                <span>Free Express Delivery</span>
                            </div>
                            <div class="trust-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--page-bg-3); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.8rem; font-weight: 700;">
                                <i class="fas fa-arrows-spin" style="color: var(--cyan); width: 18px; text-align: center;"></i>
                                <span>7-day return guarantee</span>
                            </div>
                        </div>

                        <div class="specs" style="background: var(--page-bg-2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 10px;">
                            <h4 style="font-family: 'Orbitron', sans-serif; font-size: 0.95rem; text-transform: uppercase; color: var(--white); margin: 0 0 10px 0; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">Specifications</h4>
                            ${getSpecsHtml(laptop.specs)}
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">Screen Size</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${laptop.screenSize}"</div>
                            </div>
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">Battery Capacity</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${laptop.batteryWh} Wh</div>
                            </div>
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">Physical Weight</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${laptop.weightKg} kg</div>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary add-to-cart-btn" data-id="${laptop.id}" style="margin-top: 24px; width: 100%; font-family: 'Orbitron', sans-serif; height: 50px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; text-transform: uppercase;">
                            <i class="fas fa-cart-plus"></i> Select & Add to Cart
                        </button>
                        
                        ${typeof Installment !== 'undefined' ? Installment.widget(laptop.price, 'modalInstallment') : ''}
                    </div>
                `;

                // Add to cart event listener inside modal
                content.querySelector('.add-to-cart-btn').addEventListener('click', (e) => {
                    const id = parseInt(e.currentTarget.dataset.id);
                    buyLaptop(id);
                    closeLaptopModal();
                });

                // Bind installment calculation if present
                if (typeof Installment !== 'undefined') {
                    Installment.bind('modalInstallment', laptop.price);
                }

                // Load reviews dynamically - shift ID by 100000 to keep laptop reviews cleanly isolated
                if (typeof Reviews !== 'undefined') {
                    Reviews.loadForProduct(100000 + laptop.id);
                }

                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };

            window.closeLaptopModal = () => {
                const modal = document.getElementById('quickViewModal');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            };

            // Bind modal close buttons
            const closeBtn = document.getElementById('modalCloseBtn');
            const overlay = document.querySelector('.modal-overlay');
            if (closeBtn) closeBtn.addEventListener('click', closeLaptopModal);
            if (overlay) overlay.addEventListener('click', closeLaptopModal);

            // Sidebar open / close
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (hamburgerBtn && sidebar && sidebarClose && sidebarOverlay) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });

                function closeSidebar() {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }

                sidebarClose.addEventListener('click', closeSidebar);
                sidebarOverlay.addEventListener('click', closeSidebar);

                document.querySelectorAll('.sidebar-toggle-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const parent = btn.closest('.sidebar-dropdown');
                        const isOpen = parent.classList.contains('open');
                        document.querySelectorAll('.sidebar-dropdown.open').forEach(d => d.classList.remove('open'));
                        if (!isOpen) parent.classList.add('open');
                    });
                });
            }

            // Init rendering
            render();
        });
    </script>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Panel -->
    <nav class="sidebar" id="sidebar" aria-label="Mobile navigation">
        <div class="sidebar-header">
            <a href="index.html" class="sidebar-logo-link">
                <i class="fas fa-microchip"></i> Maroc PC
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-search">
            <input type="text" placeholder="Search components..." aria-label="Search products" />
            <button aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <li><a href="index.html" class="sidebar-link"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="products.html" class="sidebar-link"><i class="fas fa-box"></i> Products</a></li>
            <li class="sidebar-dropdown open">
                <button class="sidebar-link sidebar-toggle-btn active" aria-expanded="true">
                    <i class="fas fa-tools"></i>
                    Builder Tools
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="builder.php" class="sidebar-sublink">PC Build Wizard</a></li>
                    <li><a href="builder.php?tab=gaming-finder" class="sidebar-sublink">Gaming PC Finder</a></li>
                    <li><a href="laptop-finder.php" class="sidebar-sublink active">Laptop Finder</a></li>
                    <li><a href="builder.php?tab=psu-calculator" class="sidebar-sublink">Power Supply Calculator</a></li>
                    <li><a href="builder.php?tab=memory-finder" class="sidebar-sublink">Memory Finder</a></li>
                </ul>
            </li>
            <li><a href="index.html#deals" class="sidebar-link"><i class="fas fa-bolt"></i> Deals</a></li>
            <li>
                <a href="cart.html" class="sidebar-link">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <span class="sidebar-cart-badge" id="sidebarCartCount">0</span>
                </a>
            </li>
            <li><a href="index.html#contact" class="sidebar-link"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>
    </nav>
</body>
</html>
