<?php
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maroc PC Tools Cockpit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <style>
        :root {
            --tool-bg: #05070a;
            --tool-panel: #0a0d12;
            --tool-panel-2: #111722;
            --tool-input: #080b10;
            --tool-border: rgba(120, 255, 236, 0.16);
            --tool-text: #eef0f4;
            --tool-muted: #9aa5b5;
            --tool-cyan: #00f5d4;
            --tool-green: #00e676;
            --tool-red: #ff4444;
            --tool-amber: #ffcf4d;
            --tool-orange: #ff6b35;
            --font-mono: 'JetBrains Mono', monospace;
        }
        [data-theme="light"] {
            --tool-bg: #eef2f6;
            --tool-panel: #f8fafc;
            --tool-panel-2: #e7edf4;
            --tool-input: #fdfefe;
            --tool-border: rgba(0, 122, 110, 0.22);
            --tool-text: #101827;
            --tool-muted: #526174;
            --tool-cyan: #007a6e;
            --tool-green: #008a4f;
            --tool-red: #c62828;
            --tool-amber: #9a6500;
            --tool-orange: #d95f0a;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--tool-bg); color: var(--tool-text); font-family: 'Syne', system-ui, sans-serif; }
        .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            min-height: 76px;
            border-bottom: 1px solid rgba(120, 255, 236, 0.12);
            background: color-mix(in srgb, var(--tool-bg) 94%, transparent);
            backdrop-filter: blur(16px);
        }
        .nav-container {
            width: min(1200px, calc(100% - 32px));
            min-height: 76px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .logo { display: inline-flex; align-items: center; text-decoration: none; }
        .nav-logo { width: 54px; height: 54px; object-fit: contain; display: block; }
        .nav {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        .nav-link {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            padding: 0 11px;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--tool-muted);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 800;
        }
        .nav-link:hover,
        .nav-link.active {
            border-color: var(--tool-border);
            background: rgba(0, 245, 212, 0.07);
            color: var(--tool-cyan);
        }
        .nav-spacer { flex: 1; min-width: 12px; }
        .theme-toggle {
            width: 44px;
            height: 44px;
            border: 1px solid var(--tool-border);
            border-radius: 10px;
            background: var(--tool-panel-2);
            color: var(--tool-text);
            display: inline-grid;
            place-items: center;
            cursor: pointer;
        }
        .theme-toggle .icon-moon { display: none; }
        .tools-shell { padding: 118px 0 72px; }
        .tools-hero { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr); gap: 24px; align-items: end; margin-bottom: 28px; }
        .tools-kicker { display: inline-flex; gap: 8px; align-items: center; color: var(--tool-cyan); font-family: var(--font-mono); font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        .tools-hero h1 { margin: 12px 0 10px; font-family: 'Orbitron', sans-serif; font-size: 2.4rem; line-height: 1.08; }
        .tools-hero p { max-width: 68ch; margin: 0; color: var(--tool-muted); line-height: 1.65; }
        .tools-status { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 14px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-panel); }
        .tools-status div { padding: 10px; border-radius: 8px; background: var(--tool-panel-2); }
        .tools-status span { display: block; color: var(--tool-muted); font-size: 0.68rem; font-family: var(--font-mono); text-transform: uppercase; }
        .tools-status strong { display: block; margin-top: 4px; color: var(--tool-text); font-family: var(--font-mono); font-size: 0.95rem; }
        .tool-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 22px; }
        .tool-nav { position: sticky; top: 92px; align-self: start; display: grid; gap: 8px; padding: 12px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-panel); }
        .tool-nav button { min-height: 42px; border: 1px solid transparent; border-radius: 8px; background: transparent; color: var(--tool-muted); display: flex; gap: 10px; align-items: center; padding: 0 12px; cursor: pointer; font-weight: 800; text-align: left; }
        .tool-nav button:hover, .tool-nav button.active { border-color: var(--tool-border); background: rgba(0, 245, 212, 0.06); color: var(--tool-cyan); }
        .tool-panel { display: none; border: 1px solid var(--tool-border); border-radius: 12px; background: var(--tool-panel); overflow: hidden; }
        .tool-panel.active { display: block; }
        .tool-head { padding: 20px; border-bottom: 1px solid var(--tool-border); display: flex; justify-content: space-between; gap: 16px; align-items: start; }
        .tool-head h2 { margin: 0; font-size: 1.2rem; font-family: 'Orbitron', sans-serif; }
        .tool-head p { max-width: 70ch; margin: 6px 0 0; color: var(--tool-muted); line-height: 1.55; }
        .tool-body { padding: 20px; display: grid; gap: 18px; }
        .tool-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .tool-form.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tool-field { display: grid; gap: 6px; }
        .tool-field span { color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.68rem; font-weight: 900; text-transform: uppercase; }
        .tool-field input, .tool-field select, .tool-field textarea { min-height: 42px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-input); color: var(--tool-text); padding: 0 11px; font-family: 'Syne', sans-serif; }
        .tool-field textarea { min-height: 86px; padding-top: 10px; resize: vertical; }
        .tool-field input:focus, .tool-field select:focus, .tool-field textarea:focus { outline: none; border-color: var(--tool-cyan); box-shadow: 0 0 0 3px rgba(0,245,212,0.1); }
        .tool-action { width: fit-content; min-height: 42px; padding: 0 16px; border: 1px solid var(--tool-cyan); border-radius: 8px; background: var(--tool-cyan); color: #03110f; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .tool-action.secondary { background: transparent; color: var(--tool-cyan); }
        .tool-result { min-height: 110px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-input); padding: 16px; color: var(--tool-muted); }
        .result-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .readout { border: 1px solid var(--tool-border); border-radius: 8px; padding: 12px; background: var(--tool-panel-2); }
        .readout span { display: block; color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.66rem; text-transform: uppercase; }
        .readout strong { display: block; margin-top: 5px; color: var(--tool-text); font-size: 1rem; }
        .readout.good strong { color: var(--tool-green); }
        .readout.warn strong { color: var(--tool-amber); }
        .readout.bad strong { color: var(--tool-red); }
        .product-row { display: grid; grid-template-columns: 56px 1fr auto; gap: 12px; align-items: center; padding: 10px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-panel-2); }
        .product-row + .product-row { margin-top: 8px; }
        .product-row img { width: 56px; height: 46px; object-fit: contain; border-radius: 6px; background: var(--tool-input); }
        .product-row strong { display: block; color: var(--tool-text); }
        .product-row span { color: var(--tool-muted); font-size: 0.8rem; }
        .product-row em { color: var(--tool-cyan); font-family: var(--font-mono); font-style: normal; font-weight: 900; white-space: nowrap; }
        .status-line { display: flex; gap: 8px; align-items: center; margin-top: 10px; font-family: var(--font-mono); font-size: 0.75rem; }
        .status-line.good { color: var(--tool-green); }
        .status-line.warn { color: var(--tool-amber); }
        .status-line.bad { color: var(--tool-red); }
        .request-stack { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .request-card { border: 1px solid var(--tool-border); border-radius: 10px; padding: 14px; background: var(--tool-input); display: grid; gap: 10px; }
        [data-theme="light"] .tool-action { color: #eefaf8; }
        [data-theme="light"] .tool-action.secondary { color: var(--tool-cyan); background: transparent; }
        [data-theme="light"] .theme-toggle { background: var(--tool-panel); }
        .request-card h3 { margin: 0; font-size: 0.95rem; }
        .radar-list { display: grid; gap: 8px; }
        .radar-item { display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 10px 12px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-panel-2); }
        .radar-item strong { color: var(--tool-text); }
        .radar-item span { color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.72rem; }
        @media (max-width: 920px) {
            .nav-container { align-items: flex-start; padding: 12px 0; }
            .nav { gap: 2px; }
            .nav-link { min-height: 34px; font-size: 0.78rem; padding: 0 8px; }
            .tools-shell { padding-top: 148px; }
            .tools-hero, .tool-grid, .tool-form, .tool-form.two, .request-stack { grid-template-columns: 1fr; }
            .tool-nav { position: static; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .result-grid, .tools-status { grid-template-columns: 1fr; }
        }
        /* --- Builder Tools Dropdown --- */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }
        .nav-dropdown .dropdown-toggle {
            background: transparent;
            border: 1px solid transparent;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 800;
            color: var(--tool-muted);
            min-height: 38px;
            padding: 0 11px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .nav-dropdown:hover .dropdown-toggle,
        .nav-dropdown .dropdown-toggle:hover,
        .nav-dropdown .dropdown-toggle.active {
            border-color: var(--tool-border);
            background: rgba(0, 245, 212, 0.07);
            color: var(--tool-cyan);
        }
        .nav-dropdown .dropdown-menu {
            display: block;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 220px;
            background: var(--tool-panel);
            border: 1px solid var(--tool-border);
            border-radius: 8px;
            padding: 8px 0;
            z-index: 1000;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .nav-dropdown:hover .dropdown-menu,
        .nav-dropdown:focus-within .dropdown-menu {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
        }
        .nav-dropdown .dropdown-item {
            display: block;
            padding: 10px 18px;
            color: var(--tool-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 800;
            transition: all 0.2s ease;
        }
        .nav-dropdown .dropdown-item:hover {
            color: var(--tool-cyan);
            background: var(--tool-panel-2);
        }
        @media (max-width: 920px) {
            .nav-dropdown { display: none; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="index.html" class="logo"><img src="logo.png" alt="Maroc PC Logo" class="nav-logo"></a>
            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Components</a>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle active" aria-haspopup="true" aria-expanded="false">
                        Builder Tools <span class="chevron">▾</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="builder.php" class="dropdown-item">PC Build Wizard</a>
                        <a href="builder.php?tab=gaming-finder" class="dropdown-item">Gaming PC Finder</a>
                        <a href="laptop-finder.php" class="dropdown-item">Laptop Finder</a>
                        <a href="builder.php?tab=psu-calculator" class="dropdown-item">Power Supply Calculator</a>
                        <a href="builder.php?tab=memory-finder" class="dropdown-item">Memory Finder</a>
                        <a href="tools.php" class="dropdown-item">Tools Cockpit</a>
                    </div>
                </div>
                <a href="index.html#deals" class="nav-link">Deals</a>
                <a href="index.html#contact" class="nav-link">Contact</a>
            </nav>
            <div class="nav-spacer"></div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme"><i class="fas fa-sun icon-sun"></i><i class="fas fa-moon icon-moon"></i></button>
        </div>
    </header>

    <main class="tools-shell">
        <div class="container">
            <section class="tools-hero">
                <div>
                    <span class="tools-kicker"><i class="fas fa-gauge-high"></i> Diagnostic tools</span>
                    <h1>Maroc PC Tools Cockpit</h1>
                    <p>Compatibility, upgrades, pricing, pairings, future-proofing, availability, and request workflows in one task-focused console.</p>
                </div>
                <aside class="tools-status" id="toolsStatus">
                    <div><span>Catalog</span><strong id="statusProducts">--</strong></div>
                    <div><span>In stock</span><strong id="statusStock">--</strong></div>
                    <div><span>Alerts</span><strong id="statusAlerts">READY</strong></div>
                </aside>
            </section>

            <div class="tool-grid">
                <nav class="tool-nav" aria-label="Tool sections">
                    <button class="active" data-tool="compat"><i class="fas fa-puzzle-piece"></i> Compatibility</button>
                    <button data-tool="upgrade"><i class="fas fa-arrow-up-right-dots"></i> Upgrade Advisor</button>
                    <button data-tool="deal"><i class="fas fa-scale-balanced"></i> Deal Scanner</button>
                    <button data-tool="bench"><i class="fas fa-chart-line"></i> Benchmarks</button>
                    <button data-tool="pair"><i class="fas fa-link"></i> CPU/GPU Pairing</button>
                    <button data-tool="future"><i class="fas fa-shield-halved"></i> Future Proof</button>
                    <button data-tool="radar"><i class="fas fa-satellite-dish"></i> Availability Radar</button>
                    <button data-tool="student"><i class="fas fa-graduation-cap"></i> Student/Gift Bundle</button>
                    <button data-tool="requests"><i class="fas fa-inbox"></i> Request Console</button>
                </nav>

                <section class="tool-panel active" id="tool-compat">
                    <div class="tool-head"><div><h2>Standalone Compatibility Checker</h2><p>Check socket, memory generation, PSU headroom, storage type, and basic thermal coverage.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span>CPU</span><select id="compatCpu"></select></label>
                            <label class="tool-field"><span>Motherboard</span><select id="compatBoard"></select></label>
                            <label class="tool-field"><span>RAM</span><select id="compatRam"></select></label>
                            <label class="tool-field"><span>GPU</span><select id="compatGpu"></select></label>
                            <label class="tool-field"><span>PSU</span><select id="compatPsu"></select></label>
                            <label class="tool-field"><span>Cooling</span><select id="compatCooling"></select></label>
                        </div>
                        <button class="tool-action" data-run="compat"><i class="fas fa-play"></i> Run check</button>
                        <div class="tool-result" id="compatResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-upgrade">
                    <div class="tool-head"><div><h2>Upgrade My Current PC</h2><p>Enter your current hardware and budget; the tool recommends the most impactful catalog upgrade.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span>Current CPU</span><input id="upgradeCpu" placeholder="i5-13600K, Ryzen 5 5600..."></label>
                            <label class="tool-field"><span>Current GPU</span><input id="upgradeGpu" placeholder="GTX 1060, RTX 4060..."></label>
                            <label class="tool-field"><span>Budget MAD</span><input id="upgradeBudget" type="number" value="3500"></label>
                        </div>
                        <button class="tool-action" data-run="upgrade"><i class="fas fa-magnifying-glass"></i> Find upgrade</button>
                        <div class="tool-result" id="upgradeResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-deal">
                    <div class="tool-head"><div><h2>Is This a Good Deal?</h2><p>Compare a listing price against the local Maroc PC catalog and submit a price-match request if needed.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span>Component name</span><input id="dealName" placeholder="RTX 4070 Ti, Ryzen 7..."></label>
                            <label class="tool-field"><span>Seen price MAD</span><input id="dealPrice" type="number" value="3000"></label>
                            <label class="tool-field"><span>Listing URL</span><input id="dealUrl" placeholder="https://..."></label>
                        </div>
                        <button class="tool-action" data-run="deal"><i class="fas fa-scale-balanced"></i> Scan deal</button>
                        <div class="tool-result" id="dealResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-bench">
                    <div class="tool-head"><div><h2>CPU / GPU Benchmark Database</h2><p>Catalog-based estimates for common games and productivity signals. This is an internal estimator, not scraped third-party data.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form two">
                            <label class="tool-field"><span>CPU</span><select id="benchCpu"></select></label>
                            <label class="tool-field"><span>GPU</span><select id="benchGpu"></select></label>
                        </div>
                        <button class="tool-action" data-run="bench"><i class="fas fa-chart-simple"></i> Estimate</button>
                        <div class="tool-result" id="benchResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-pair">
                    <div class="tool-head"><div><h2>CPU / GPU Pairing Recommender</h2><p>Pick either side of the pairing and get budget, balanced, and no-compromise matches.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form two">
                            <label class="tool-field"><span>Known part</span><select id="pairPart"></select></label>
                            <label class="tool-field"><span>Pairing direction</span><select id="pairDirection"><option value="auto">Auto detect</option><option value="cpu">Recommend CPU</option><option value="gpu">Recommend GPU</option></select></label>
                        </div>
                        <button class="tool-action" data-run="pair"><i class="fas fa-link"></i> Recommend pairings</button>
                        <div class="tool-result" id="pairResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-future">
                    <div class="tool-head"><div><h2>Is My PC Future-Proof?</h2><p>Score PCIe, VRAM, DDR generation, socket longevity, PSU headroom, and upgrade runway.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span>CPU</span><select id="futureCpu"></select></label>
                            <label class="tool-field"><span>GPU</span><select id="futureGpu"></select></label>
                            <label class="tool-field"><span>Horizon</span><select id="futureYears"><option>2 years</option><option selected>3 years</option><option>5 years</option></select></label>
                            <label class="tool-field"><span>Motherboard</span><select id="futureBoard"></select></label>
                            <label class="tool-field"><span>RAM</span><select id="futureRam"></select></label>
                            <label class="tool-field"><span>PSU</span><select id="futurePsu"></select></label>
                        </div>
                        <button class="tool-action" data-run="future"><i class="fas fa-shield"></i> Score build</button>
                        <div class="tool-result" id="futureResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-radar">
                    <div class="tool-head"><div><h2>Component Availability Radar</h2><p>Transparent stock pressure based on the XAMPP products table.</p></div><button class="tool-action secondary" data-run="radar"><i class="fas fa-rotate"></i> Refresh</button></div>
                    <div class="tool-body"><div class="tool-result radar-list" id="radarResult"></div></div>
                </section>

                <section class="tool-panel" id="tool-student">
                    <div class="tool-head"><div><h2>Student / Gift Bundle Builder</h2><p>Budget-first bundle generator for school, gaming, creative, or general-use gifts.</p></div></div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span>Budget MAD</span><input id="bundleBudget" type="number" value="8000"></label>
                            <label class="tool-field"><span>Use case</span><select id="bundleUse"><option value="student">Engineering student</option><option value="general">General studies</option><option value="design">Design student</option><option value="gaming">Gaming gift</option></select></label>
                            <label class="tool-field"><span>Mode</span><select id="bundleMode"><option value="student">Student</option><option value="gift">Build for a friend</option></select></label>
                        </div>
                        <button class="tool-action" data-run="bundle"><i class="fas fa-wand-magic-sparkles"></i> Generate bundle</button>
                        <div class="tool-result" id="bundleResult"></div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-requests">
                    <div class="tool-head"><div><h2>Request Console</h2><p>Customer-facing forms connected to admin/database queues.</p></div></div>
                    <div class="tool-body">
                        <div class="request-stack">
                            <form class="request-card" data-request="community"><h3>Community Build Showcase</h3><label class="tool-field"><span>Build name</span><input name="build_name" required></label><label class="tool-field"><span>Caption</span><textarea name="caption"></textarea></label><label class="tool-field"><span>Image URL</span><input name="image_url"></label><button class="tool-action" type="submit">Submit build</button><div class="status-line"></div></form>
                            <form class="request-card" data-request="trade"><h3>Instant Trade-in Valuation</h3><label class="tool-field"><span>Hardware type</span><select name="hardware_type"><option>gpu</option><option>cpu</option><option>ram</option><option>storage</option><option>motherboard</option><option>laptop</option></select></label><label class="tool-field"><span>Hardware name</span><input name="hardware_name" required></label><label class="tool-field"><span>Condition</span><select name="condition_grade"><option>excellent</option><option>good</option><option>fair</option><option>parts</option></select></label><button class="tool-action" type="submit">Estimate</button><div class="status-line"></div></form>
                            <form class="request-card" data-request="receipt"><h3>Bank Transfer Receipt</h3><label class="tool-field"><span>Bank</span><select name="bank_name"><option>CIH</option><option>Attijariwafa</option><option>BMCE</option><option>Cash Plus</option><option>Wafacash</option></select></label><label class="tool-field"><span>Amount</span><input name="amount" type="number" required></label><label class="tool-field"><span>Reference</span><input name="transfer_reference"></label><button class="tool-action" type="submit">Log receipt</button><div class="status-line"></div></form>
                            <div class="request-card"><h3>Maroc PC Points Referral</h3><button class="tool-action" type="button" id="referralBtn">Generate referral link</button><div class="tool-result" id="referralResult">Sign in first if you want the code attached to your account.</div></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script>
        const catalog = typeof products !== 'undefined' && Array.isArray(products) ? products : [];
        const stockState = {};
        const $ = id => document.getElementById(id);
        const value = id => $(id)?.value || '';
        const setHtml = (id, html) => { const node = $(id); if (node) node.innerHTML = html; };
        const setText = (id, text) => { const node = $(id); if (node) node.textContent = text; };
        const byCat = cat => catalog.filter(p => p.category === cat);
        const money = n => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' MAD';
        const img = p => p?.image || `images/products/placeholder-${p?.category || 'storage'}.svg`;
        const specText = p => Object.values(p?.specs || {}).join(' ');
        const num = v => Number(String(v || '').match(/\d+/)?.[0] || 0);
        const scoreCpu = p => /9800X3D/i.test(p?.name) ? 98 : /7800X3D/i.test(p?.name) ? 94 : /9950|7950|285K/i.test(p?.name) ? 91 : /14900|265K/i.test(p?.name) ? 88 : /9700|7700/i.test(p?.name) ? 82 : /14600|9600/i.test(p?.name) ? 78 : /Xeon/i.test(p?.name) ? 48 : 62;
        const scoreGpu = p => /5090/i.test(p?.name) ? 115 : /5080/i.test(p?.name) ? 104 : /4090/i.test(p?.name) ? 100 : /5070 Ti|4080/i.test(p?.name) ? 92 : /7900 XTX/i.test(p?.name) ? 90 : /4070 Ti/i.test(p?.name) ? 82 : /5070/i.test(p?.name) ? 80 : /7800 XT/i.test(p?.name) ? 74 : /5060/i.test(p?.name) ? 68 : /580/i.test(p?.name) ? 42 : /550/i.test(p?.name) ? 26 : 58;
        const itemScore = p => p?.category === 'cpu' ? scoreCpu(p) : scoreGpu(p);
        const isLegacyCpu = p => /Xeon|E5-|X99|i5-7|i5-8|i7-7|i7-8/i.test(`${p?.name || ''} ${specText(p)}`);
        const REAL_GPU_BASELINES = [
            {
                match: /RTX 5080/i,
                source: 'NanoReview RTX 5080 game table',
                url: 'https://nanoreview.net/en/gpu/geforce-rtx-5080',
                average: { '1080p High': 239, '1080p Ultra': 203, '1440p Ultra': 164, '4K Ultra': 97 },
                games: [
                    ['Forza Horizon 5', 259, 185, 176, 141],
                    ['The Witcher 3', 274, 237, 170, 89],
                    ['Counter-Strike 2', 314, 237, 175, 89],
                    ['Far Cry 6', 193, 176, 160, 96],
                    ['Hogwarts Legacy', 183, 154, 120, 70],
                    ['Call of Duty: MWIII', 249, 241, 193, 132],
                    ['Ghost of Tsushima', 171, 137, 127, 83],
                    ['Cyberpunk 2077', 216, 190, 136, 63],
                    ['Shadow of the Tomb Raider', 293, 270, 216, 113],
                ],
            },
            {
                match: /RTX 4090/i,
                source: "Tom's Hardware GPU hierarchy",
                url: 'https://www.tomshardware.com/reviews/gpu-hierarchy,4388.html',
                average: { '1080p High': 196, '1080p Ultra': 150, '1440p Ultra': 127, '4K Ultra': 85 },
                games: [],
            },
            {
                match: /RX 7900 XTX/i,
                source: "Tom's Hardware GPU hierarchy",
                url: 'https://www.tomshardware.com/reviews/gpu-hierarchy,4388.html',
                average: { '1080p High': 174, '1080p Ultra': 125, '1440p Ultra': 103, '4K Ultra': 64 },
                games: [],
            },
        ];
        const gpuBaseline = gpu => REAL_GPU_BASELINES.find(row => row.match.test(gpu?.name || ''));
        const productRow = p => `<div class="product-row"><img src="${img(p)}" onerror="this.src='logo.png'" alt=""><div><strong>${p.name}</strong><span>${p.brand} · ${p.category}</span></div><em>${money(p.price)}</em></div>`;
        const selectHtml = (items, label = 'Select') => `<option value="">${label}</option>${items.map(p => `<option value="${p.id}">${p.name} - ${money(p.price)}</option>`).join('')}`;
        const productById = id => catalog.find(p => Number(p.id) === Number(id));
        const memoryType = p => /DDR5/i.test(specText(p)) ? 'DDR5' : /DDR4/i.test(specText(p)) ? 'DDR4' : '';
        const socket = p => String(p?.specs?.Socket || '').toUpperCase();
        const watts = p => num(p?.specs?.TDP || p?.specs?.Wattage || p?.specs?.Power) || ({ cpu: 125, gpu: 300, motherboard: 60, ram: 12, storage: 10, cooling: 15, psu: 0 }[p?.category] || 0);

        function bindToolNavigation() {
        document.querySelectorAll('.tool-nav button').forEach(btn => btn.addEventListener('click', () => {
            document.querySelectorAll('.tool-nav button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tool-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`tool-${btn.dataset.tool}`)?.classList.add('active');
        }));
        }

        function fillSelects() {
            const sets = {
                Cpu: byCat('cpu'), Gpu: byCat('gpu'), Board: byCat('motherboard'), Ram: byCat('ram'), Psu: byCat('psu'), Cooling: byCat('cooling')
            };
            ['compat','bench','future'].forEach(prefix => {
                Object.entries(sets).forEach(([key, items]) => {
                    const el = document.getElementById(prefix + key);
                    if (el) el.innerHTML = selectHtml(items, `Select ${key}`);
                });
            });
            setHtml('pairPart', selectHtml([...byCat('cpu'), ...byCat('gpu')], 'Select CPU or GPU'));
            setText('statusProducts', catalog.length);
            setText('statusStock', catalog.filter(p => p.inStock).length);
        }

        async function loadStock() {
            try {
                const res = await fetch('api/products-stock.php');
                const data = await res.json();
                Object.assign(stockState, data.stock || {});
                setText('statusStock', Object.values(stockState).filter(s => s.in_stock).length);
                renderRadar();
            } catch (e) {
                renderRadar();
            }
        }

        function renderCompat() {
            const cpu = productById(value('compatCpu')), board = productById(value('compatBoard')), ram = productById(value('compatRam')), gpu = productById(value('compatGpu')), psu = productById(value('compatPsu')), cooling = productById(value('compatCooling'));
            const load = [cpu, board, ram, gpu, cooling].filter(Boolean).reduce((s,p) => s + watts(p), 0);
            const psuWatts = watts(psu);
            const checks = [];
            checks.push({ ok: cpu && board ? socket(cpu) === socket(board) : null, label: cpu && board ? `Socket: ${socket(cpu) || 'unknown'} vs ${socket(board) || 'unknown'}` : 'Select CPU and motherboard' });
            checks.push({ ok: board && ram ? !memoryType(board) || !memoryType(ram) || memoryType(board) === memoryType(ram) : null, label: board && ram ? `Memory: ${memoryType(board) || 'unknown'} / ${memoryType(ram) || 'unknown'}` : 'Select board and RAM' });
            checks.push({ ok: psu ? psuWatts >= Math.ceil(load * 1.25) : null, label: psu ? `PSU: ${psuWatts}W for ~${load}W load` : 'Select PSU' });
            checks.push({ ok: cpu && cooling ? watts(cooling) >= Math.min(250, watts(cpu)) : null, label: cpu && cooling ? `Cooling load: CPU ~${watts(cpu)}W` : 'Select CPU and cooling' });
            const bad = checks.filter(c => c.ok === false).length;
            setHtml('compatResult', `<div class="result-grid">${checks.map(c => `<div class="readout ${c.ok === false ? 'bad' : c.ok ? 'good' : 'warn'}"><span>${c.ok === false ? 'Fail' : c.ok ? 'Pass' : 'Waiting'}</span><strong>${c.label}</strong></div>`).join('')}</div><div class="status-line ${bad ? 'bad' : 'good'}"><i class="fas ${bad ? 'fa-circle-xmark' : 'fa-circle-check'}"></i>${bad ? 'Compatibility issues found.' : 'No blocking compatibility issue detected.'}</div>`);
        }

        function renderUpgrade() {
            const budget = Number(value('upgradeBudget') || 0);
            const gpuWeak = /gtx|rx 5|rx 4|1050|1060|1650|1660|580|550/i.test(value('upgradeGpu'));
            const cpuWeak = /i3|i5-7|i5-8|ryzen 3|xeon/i.test(value('upgradeCpu'));
            const category = gpuWeak || !cpuWeak ? 'gpu' : 'cpu';
            const options = byCat(category).filter(p => p.inStock && p.price <= budget).sort((a,b) => (category === 'gpu' ? scoreGpu(b)-scoreGpu(a) : scoreCpu(b)-scoreCpu(a)) || b.price-a.price).slice(0,3);
            setHtml('upgradeResult', options.length ? `<div class="status-line good"><i class="fas fa-arrow-up"></i>Biggest upgrade lane: ${category.toUpperCase()}</div>${options.map(productRow).join('')}` : `<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>No in-stock ${category.toUpperCase()} upgrade found under ${money(budget)}.</div>`);
        }

        function renderDeal() {
            const q = value('dealName').toLowerCase().trim();
            const seen = Number(value('dealPrice') || 0);
            const match = catalog.filter(p => (`${p.name} ${p.brand}`).toLowerCase().includes(q) || q.split(/\s+/).some(token => token.length > 2 && p.name.toLowerCase().includes(token))).sort((a,b) => Math.abs(a.price-seen)-Math.abs(b.price-seen))[0];
            if (!match) { setHtml('dealResult', '<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>No close catalog match found.</div>'); return; }
            const delta = Math.round(((seen - match.price) / match.price) * 100);
            const tone = delta <= -12 ? 'good' : delta >= 8 ? 'bad' : 'warn';
            const label = tone === 'good' ? 'Good deal' : tone === 'bad' ? 'Overpriced' : 'Fair price';
            setHtml('dealResult', `<div class="readout ${tone}"><span>${label}</span><strong>${Math.abs(delta)}% ${delta < 0 ? 'below' : 'above'} Maroc PC catalog reference</strong></div>${productRow(match)}<button class="tool-action secondary" id="dealPriceMatch"><i class="fas fa-paper-plane"></i> Send price-match request</button>`);
            $('dealPriceMatch').onclick = () => postRequest({ action: 'price_match', product_id: match.id, product_name: match.name, competitor_url: value('dealUrl'), competitor_price: seen }, $('dealResult'));
        }

        function renderBench() {
            const cpu = productById(value('benchCpu')), gpu = productById(value('benchGpu'));
            if (!cpu || !gpu) { setHtml('benchResult', 'Select CPU and GPU.'); return; }
            const cpuScore = scoreCpu(cpu);
            const gpuScore = scoreGpu(gpu);
            const ratio = cpuScore / Math.max(1, gpuScore);
            const real = gpuBaseline(gpu);
            const cpuCap = res => cpuScore * ({ '1080p': 1.18, '1440p': 1.36, '4K': 1.62 }[res] || 1.3);
            const fallbackGpu = res => gpuScore * ({ '1080p': 1.72, '1440p': 1.25, '4K': 0.72 }[res] || 1.2);
            const rawFor = res => {
                if (!real) return fallbackGpu(res);
                if (res === '1080p') return real.average['1080p Ultra'] || real.average['1080p High'];
                if (res === '1440p') return real.average['1440p Ultra'];
                return real.average['4K Ultra'];
            };
            const adjusted = (raw, res, cpuDemand = 1) => {
                const cap = cpuCap(res) / cpuDemand;
                const severePenalty = ratio < 0.62 ? 0.78 : ratio < 0.74 ? 0.86 : ratio < 0.86 ? 0.94 : 1;
                return Math.max(20, Math.round(Math.min(raw, cap) * severePenalty));
            };
            const games = real?.games?.length
                ? real.games.map(([name, high1080, ultra1080, ultra1440, ultra4k]) => [name, ultra1440, ultra1080, ultra4k, name.includes('Counter') ? 1.42 : name.includes('Call of Duty') ? 1.22 : name.includes('Cyberpunk') ? 1.06 : 1])
                : [
                    ['Cyberpunk 2077', fallbackGpu('1440p') * .86, fallbackGpu('1080p') * .86, fallbackGpu('4K') * .86, 1.06],
                    ['Warzone', fallbackGpu('1440p') * 1.05, fallbackGpu('1080p') * 1.05, fallbackGpu('4K') * 1.05, 1.22],
                    ['Valorant', fallbackGpu('1440p') * 1.55, fallbackGpu('1080p') * 1.55, fallbackGpu('4K') * 1.55, 1.48],
                    ['Forza Horizon 5', fallbackGpu('1440p') * 1.08, fallbackGpu('1080p') * 1.08, fallbackGpu('4K') * 1.08, .92],
                ];
            const warning = ratio < 0.72
                ? `<div class="status-line bad"><i class="fas fa-triangle-exclamation"></i>Severe CPU bottleneck: ${cpu.name} cannot feed ${gpu.name} in CPU-bound games.</div>`
                : ratio < 0.86
                    ? `<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>Moderate CPU bottleneck detected at lower resolutions.</div>`
                    : `<div class="status-line good"><i class="fas fa-circle-check"></i>CPU/GPU pairing is within a healthy estimator range.</div>`;
            const source = real
                ? `<div class="status-line"><i class="fas fa-database"></i>Sourced GPU baseline: <a href="${real.url}" target="_blank" rel="noopener">${real.source}</a>. CPU-adjusted for this pairing.</div>`
                : `<div class="status-line warn"><i class="fas fa-database"></i>No sourced GPU row for this card yet. Showing estimator output only.</div>`;
            setHtml('benchResult', `${source}${warning}<div class="result-grid">${[
                ['1080p', rawFor('1080p')],
                ['1440p', rawFor('1440p')],
                ['4K', rawFor('4K')]
            ].map(([res, raw]) => `<div class="readout"><span>${res} AVG</span><strong>${adjusted(raw, res)} FPS</strong></div>`).join('')}</div>${games.map(([g, raw1440, raw1080, raw4k, cpuDemand]) => `<div class="radar-item"><strong>${g}</strong><span>1080p: ${adjusted(raw1080, '1080p', cpuDemand)} FPS · 1440p: ${adjusted(raw1440, '1440p', cpuDemand)} FPS · 4K: ${adjusted(raw4k, '4K', cpuDemand)} FPS</span></div>`).join('')}`);
        }

        function renderPair() {
            const part = productById(value('pairPart'));
            if (!part) { setHtml('pairResult', 'Select a known CPU or GPU.'); return; }
            const direction = value('pairDirection');
            let targetCat = direction === 'auto' ? (part.category === 'cpu' ? 'gpu' : 'cpu') : direction;
            if (targetCat === part.category) targetCat = part.category === 'cpu' ? 'gpu' : 'cpu';

            const score = itemScore(part);
            const minScore = score >= 100 ? 74 : score >= 88 ? 68 : score >= 74 ? 56 : 0;
            const maxGap = score >= 90 ? 34 : 42;
            const candidates = byCat(targetCat)
                .filter(p => p.inStock)
                .filter(p => targetCat !== 'cpu' || !isLegacyCpu(p) || score < 70)
                .map(p => ({ p, score: itemScore(p), gap: Math.abs(itemScore(p) - score) }))
                .filter(row => row.score >= minScore && row.gap <= maxGap)
                .sort((a,b) => a.gap - b.gap || a.p.price - b.p.price);

            const budgetCeiling = targetCat === 'cpu' ? (score >= 100 ? 3800 : 2800) : (score >= 88 ? 8000 : 5000);
            const balancedFloor = targetCat === 'cpu' ? (score >= 100 ? 78 : 70) : (score >= 88 ? 78 : 64);
            const budget = candidates.filter(row => row.p.price <= budgetCeiling).sort((a,b) => b.score - a.score || a.p.price - b.p.price)[0] || candidates[0];
            const balanced = candidates.filter(row => row.score >= balancedFloor).sort((a,b) => a.gap - b.gap || a.p.price - b.p.price)[0] || candidates[0];
            const noCompromise = candidates.slice().sort((a,b) => b.score - a.score || b.p.price - a.p.price)[0];
            const tiers = [['Budget', budget], ['Balanced', balanced], ['No compromise', noCompromise]];
            const note = `<div class="status-line ${candidates.length ? 'good' : 'warn'}"><i class="fas ${candidates.length ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i>Known ${part.category.toUpperCase()}: recommending ${targetCat.toUpperCase()} matches only.</div>`;
            setHtml('pairResult', note + (candidates.length ? tiers.filter((x, index, arr) => x[1] && arr.findIndex(y => y[1]?.p.id === x[1].p.id) === index).map(([label, row]) => `<div class="readout"><span>${label}</span><strong>${row.gap <= 10 ? 'Balanced match' : 'Practical match'}</strong></div>${productRow(row.p)}`).join('') : 'No sensible in-stock pairing found.'));
        }

        function renderFuture() {
            const cpu = productById(value('futureCpu')), gpu = productById(value('futureGpu')), board = productById(value('futureBoard')), ram = productById(value('futureRam')), psu = productById(value('futurePsu'));
            const years = num(value('futureYears')) || 3;
            const vram = num(gpu?.specs?.VRAM);
            let score = 35;
            if (/AM5|LGA 1851/i.test(socket(cpu) + socket(board))) score += 18;
            if (memoryType(ram) === 'DDR5' || memoryType(board) === 'DDR5') score += 17;
            if (vram >= 16) score += 18; else if (vram >= 12) score += 12; else score += 5;
            if (/PCIe 5/i.test(specText(board))) score += 12;
            if (watts(psu) >= 850) score += 10;
            score = Math.max(0, Math.min(100, score - Math.max(0, years - 3) * 7));
            setHtml('futureResult', `<div class="readout ${score >= 78 ? 'good' : score >= 58 ? 'warn' : 'bad'}"><span>${value('futureYears')} longevity</span><strong>${score}/100</strong></div><div class="status-line ${score >= 78 ? 'good' : score >= 58 ? 'warn' : 'bad'}"><i class="fas fa-shield"></i>${score >= 78 ? 'Strong upgrade runway.' : score >= 58 ? 'Usable, with one weak point.' : 'Likely to feel constrained.'}</div>`);
        }

        function renderRadar() {
            const rows = catalog.map(p => ({ p, s: stockState[String(p.id)] || { in_stock: p.inStock, quantity: p.inStock ? 10 : 0, tone: p.inStock ? 'good' : 'out' } }))
                .filter(row => !row.s.in_stock || row.s.quantity <= 10)
                .sort((a,b) => a.s.quantity - b.s.quantity).slice(0,20);
            setHtml('radarResult', rows.map(({p,s}) => `<div class="radar-item"><strong>${p.name}</strong><span>${!s.in_stock ? 'OUT OF STOCK' : `STOCK: ${s.quantity}`}</span></div>`).join('') || 'No stock pressure detected.');
        }

        function renderBundle() {
            const budget = Number(value('bundleBudget') || 0);
            const use = value('bundleUse');
            const weights = use === 'gaming' ? { cpu:.2,gpu:.42,ram:.1,storage:.1,monitor:.18 } : use === 'design' ? { cpu:.27,gpu:.25,ram:.16,storage:.16,monitor:.16 } : { cpu:.24,gpu:.18,ram:.14,storage:.14,monitor:.3 };
            const picks = Object.entries(weights).map(([cat,w]) => byCat(cat).filter(p => p.inStock && p.price <= budget*w*1.35).sort((a,b)=>b.rating-a.rating || b.price-a.price)[0]).filter(Boolean);
            setHtml('bundleResult', `<div class="status-line good"><i class="fas fa-gift"></i>${value('bundleMode') === 'gift' ? 'Gift build' : 'Student bundle'} around ${money(picks.reduce((s,p)=>s+p.price,0))}</div>${picks.map(productRow).join('')}`);
        }

        async function postRequest(payload, host) {
            const res = await fetch('api/feature-requests.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            const target = host.querySelector?.('.status-line') || host;
            target.innerHTML = `<i class="fas ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${data.message || 'Done'}`;
            target.className = `status-line ${data.success ? 'good' : 'bad'}`;
            if (data.estimated_value) target.innerHTML += ` Estimated: ${money(data.estimated_value)}.`;
        }

        document.querySelectorAll('[data-run]').forEach(btn => btn.addEventListener('click', () => ({ compat: renderCompat, upgrade: renderUpgrade, deal: renderDeal, bench: renderBench, pair: renderPair, future: renderFuture, radar: loadStock, bundle: renderBundle }[btn.dataset.run]?.())));
        document.querySelectorAll('[data-request]').forEach(form => form.addEventListener('submit', e => {
            e.preventDefault();
            const fd = new FormData(form);
            const type = form.dataset.request;
            const action = type === 'community' ? 'community_build' : type === 'trade' ? 'trade_in' : 'receipt';
            postRequest({ action, ...Object.fromEntries(fd.entries()), components: [] }, form.querySelector('.status-line'));
        }));
        document.getElementById('referralBtn').addEventListener('click', async () => {
            const res = await fetch('api/feature-requests.php?action=referral');
            const data = await res.json();
            setHtml('referralResult', data.success ? `<strong>${data.code}</strong><br><span>${data.url}</span><br><span>${data.bonus_points} points for both accounts after first purchase.</span>` : data.message);
        });

        bindToolNavigation();
        fillSelects();
        loadStock();
        renderRadar();
    </script>
</body>
</html>
