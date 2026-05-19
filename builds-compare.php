<?php
require_once 'config.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php?next=builds-compare.php');
    exit();
}

$pdo = db();
$clientId = (int) $_SESSION['client_id'];
$stmt = $pdo->prepare("
    SELECT id, share_code, build_name, use_case, components, total_price, total_wattage, created_at
    FROM saved_builds
    WHERE client_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->execute([$clientId]);
$builds = $stmt->fetchAll();

foreach ($builds as &$build) {
    $build['components'] = json_decode((string) $build['components'], true) ?: [];
    $build['total_price'] = (float) $build['total_price'];
    $build['total_wattage'] = (int) $build['total_wattage'];
}
unset($build);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Comparison - Maroc PC</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <style>
        .compare-shell {
            min-height: 100vh;
            padding: 110px 20px 70px;
            background: var(--page-bg, #050505);
            color: var(--text, #eef0f4);
        }
        .compare-inner {
            width: min(1180px, 100%);
            margin: 0 auto;
        }
        .compare-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }
        .compare-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--cyan, #00f5d4);
            font-family: var(--font-mono, monospace);
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .compare-head h1 {
            margin: 8px 0 6px;
            font-size: 1.75rem;
            line-height: 1.15;
        }
        .compare-head p {
            max-width: 68ch;
            margin: 0;
            color: var(--muted, #b0b8c8);
            line-height: 1.55;
        }
        .compare-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .compare-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid var(--border, rgba(255,255,255,0.12));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
            color: var(--text, #eef0f4);
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
        }
        .compare-btn.primary {
            border-color: var(--cyan, #00f5d4);
            background: var(--cyan, #00f5d4);
            color: #06100f;
        }
        .compare-picker {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .compare-picker label {
            display: grid;
            gap: 7px;
        }
        .compare-picker span {
            color: var(--muted, #b0b8c8);
            font-family: var(--font-mono, monospace);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .compare-picker select {
            min-width: 0;
            height: 44px;
            border: 1px solid var(--border, rgba(255,255,255,0.12));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
            color: var(--text, #eef0f4);
            padding: 0 12px;
            font: inherit;
        }
        .compare-readout {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border, rgba(255,255,255,0.12));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
            color: var(--muted, #b0b8c8);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .compare-readout strong {
            color: var(--text, #eef0f4);
        }
        .compare-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border, rgba(255,255,255,0.12));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
        }
        .compare-table {
            width: 100%;
            min-width: 780px;
            border-collapse: collapse;
        }
        .compare-table th,
        .compare-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border, rgba(255,255,255,0.12));
            text-align: left;
            vertical-align: top;
        }
        .compare-table th {
            color: var(--text, #eef0f4);
            font-size: 0.88rem;
        }
        .compare-table td:first-child {
            width: 180px;
            color: var(--muted, #b0b8c8);
            font-family: var(--font-mono, monospace);
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .metric-good { color: var(--diagnostic-green, #00e676); font-weight: 900; }
        .metric-warn { color: #ffcf4d; font-weight: 900; }
        .metric-bad { color: var(--diagnostic-red, #ff4444); font-weight: 900; }
        .component-cell strong {
            display: block;
            color: var(--text, #eef0f4);
            font-size: 0.86rem;
            margin-bottom: 3px;
        }
        .component-cell small {
            color: var(--muted, #b0b8c8);
        }
        .empty-state {
            padding: 26px;
            border: 1px solid var(--border, rgba(255,255,255,0.12));
            border-radius: 8px;
            background: var(--page-bg-2, #0a0b0e);
        }
        @media (max-width: 760px) {
            .compare-head {
                align-items: stretch;
                flex-direction: column;
            }
            .compare-picker {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.html" class="logo"><i class="fas fa-microchip"></i><span>Maroc PC</span></a>
                <nav class="nav">
                    <a href="products.html" class="nav-link">Components</a>
                    <a href="builder.php" class="nav-link">PC Builder</a>
                    <a href="builds-compare.php" class="nav-link active">Compare Builds</a>
                    <a href="account.php" class="nav-link">Account</a>
                </nav>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-sun icon-sun"></i>
                    <i class="fas fa-moon icon-moon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="compare-shell">
        <div class="compare-inner">
            <div class="compare-head">
                <div>
                    <span class="compare-kicker"><i class="fas fa-code-compare"></i> Build Diff View</span>
                    <h1>Compare Saved Builds</h1>
                    <p>Select two or three saved builds and inspect the exact price, wattage, component, and balance differences.</p>
                </div>
                <div class="compare-actions">
                    <a class="compare-btn" href="builder.php"><i class="fas fa-screwdriver-wrench"></i> Builder</a>
                    <a class="compare-btn primary" href="account.php?tab=builds"><i class="fas fa-folder-open"></i> Saved Builds</a>
                </div>
            </div>

            <?php if (count($builds) < 2): ?>
                <div class="empty-state">
                    <strong>Save at least two builds to unlock comparison.</strong>
                    <p>Create alternatives in the builder, save them, then come back here to compare price and performance tradeoffs.</p>
                </div>
            <?php else: ?>
                <div class="compare-picker">
                    <label><span>Build A</span><select id="buildA"></select></label>
                    <label><span>Build B</span><select id="buildB"></select></label>
                    <label><span>Build C optional</span><select id="buildC"></select></label>
                </div>
                <div class="compare-readout" id="compareReadout"></div>
                <div class="compare-table-wrap">
                    <table class="compare-table">
                        <thead id="compareHead"></thead>
                        <tbody id="compareBody"></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const savedBuilds = <?= json_encode($builds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        function formatMAD(value) {
            return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
        }

        function componentFor(build, key) {
            const item = build.components && build.components[key];
            return item && typeof item === 'object' ? item : null;
        }

        function buildScore(build) {
            const price = Number(build.total_price || 0);
            const watt = Number(build.total_wattage || 0);
            const cpu = componentFor(build, 'cpu');
            const gpu = componentFor(build, 'gpu');
            let score = 55;
            if (cpu) score += 10;
            if (gpu) score += 15;
            if (componentFor(build, 'ram')) score += 8;
            if (componentFor(build, 'storage')) score += 6;
            if (componentFor(build, 'psu')) score += watt > 0 ? 6 : 3;
            if (price > 0 && watt > 0 && price / Math.max(watt, 1) < 18) score += 4;
            return Math.max(0, Math.min(100, Math.round(score)));
        }

        function tierFor(build) {
            const price = Number(build.total_price || 0);
            if (price >= 22000) return 'Extreme';
            if (price >= 14000) return 'High';
            if (price >= 8000) return 'Balanced';
            return 'Budget';
        }

        function selectBuilds() {
            const ids = ['buildA', 'buildB', 'buildC']
                .map(id => document.getElementById(id)?.value)
                .filter(Boolean);
            return ids.map(id => savedBuilds.find(build => String(build.id) === String(id))).filter(Boolean);
        }

        function optionLabel(build) {
            return `${build.build_name || 'Saved Build'} (${formatMAD(build.total_price)})`;
        }

        function initSelectors() {
            const options = savedBuilds.map(build => `<option value="${build.id}">${optionLabel(build)}</option>`).join('');
            document.getElementById('buildA').innerHTML = options;
            document.getElementById('buildB').innerHTML = options;
            document.getElementById('buildC').innerHTML = '<option value="">None</option>' + options;
            if (savedBuilds[1]) document.getElementById('buildB').value = savedBuilds[1].id;
            ['buildA', 'buildB', 'buildC'].forEach(id => document.getElementById(id)?.addEventListener('change', renderCompare));
        }

        function row(label, builds, render) {
            return `<tr><td>${label}</td>${builds.map(render).join('')}</tr>`;
        }

        function renderComponent(build, key) {
            const item = componentFor(build, key);
            if (!item) return '<td class="component-cell"><small>Not selected</small></td>';
            return `<td class="component-cell"><strong>${item.name}</strong><small>${item.brand || ''} ${item.price ? ' · ' + formatMAD(item.price) : ''}</small></td>`;
        }

        function renderCompare() {
            const builds = selectBuilds();
            const head = document.getElementById('compareHead');
            const body = document.getElementById('compareBody');
            const readout = document.getElementById('compareReadout');
            if (!head || !body || !readout) return;

            head.innerHTML = `<tr><th>Feature</th>${builds.map(build => `<th>${build.build_name || 'Saved Build'}<br><small>${build.use_case || 'general'} · ${build.share_code}</small></th>`).join('')}</tr>`;

            const prices = builds.map(build => Number(build.total_price || 0));
            const bestPrice = Math.min(...prices);
            const scores = builds.map(buildScore);
            const bestScore = Math.max(...scores);
            const fpsProxy = builds.map(build => Math.round(buildScore(build) * 1.65));
            const bestFps = Math.max(...fpsProxy);

            body.innerHTML = [
                row('Total Price', builds, build => `<td class="${Number(build.total_price) === bestPrice ? 'metric-good' : 'metric-warn'}">${formatMAD(build.total_price)}</td>`),
                row('Estimated Wattage', builds, build => `<td>${build.total_wattage || 0}W</td>`),
                row('Performance Tier', builds, build => `<td>${tierFor(build)}</td>`),
                row('Balance Score', builds, build => `<td class="${buildScore(build) === bestScore ? 'metric-good' : ''}">${buildScore(build)}/100</td>`),
                row('Estimated FPS 1080p', builds, build => {
                    const fps = Math.round(buildScore(build) * 1.65);
                    return `<td class="${fps === bestFps ? 'metric-good' : ''}">${fps} FPS</td>`;
                }),
                row('CPU', builds, build => renderComponent(build, 'cpu')),
                row('GPU', builds, build => renderComponent(build, 'gpu')),
                row('Motherboard', builds, build => renderComponent(build, 'motherboard')),
                row('RAM', builds, build => renderComponent(build, 'ram')),
                row('Storage', builds, build => renderComponent(build, 'storage')),
                row('Cooling', builds, build => renderComponent(build, 'cooling')),
                row('PSU', builds, build => renderComponent(build, 'psu'))
            ].join('');

            const cheapest = builds[prices.indexOf(bestPrice)];
            const strongest = builds[scores.indexOf(bestScore)];
            const delta = Math.max(...prices) - bestPrice;
            readout.innerHTML = `<strong>${cheapest.build_name}</strong> is the lowest-cost option. <strong>${strongest.build_name}</strong> has the highest balance score. Price spread: <strong>${formatMAD(delta)}</strong>.`;
        }

        if (savedBuilds.length >= 2) {
            initSelectors();
            renderCompare();
        }
    </script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
</body>
</html>
