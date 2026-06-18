<?php
require_once 'admin-helpers.php';

adminRequireAuth();

// Helper for translated date month names
function adminTranslateDate(string $format, ?int $timestamp = null): string {
    $ts = $timestamp ?? time();
    $enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $trMonths = [
        adminPhrase('Jan'), adminPhrase('Feb'), adminPhrase('Mar'),
        adminPhrase('Apr'), adminPhrase('May'), adminPhrase('Jun'),
        adminPhrase('Jul'), adminPhrase('Aug'), adminPhrase('Sep'),
        adminPhrase('Oct'), adminPhrase('Nov'), adminPhrase('Dec'),
    ];
    return str_replace($enMonths, $trMonths, date($format, $ts));
}

$pdo = db();
adminEnsureAdminSuiteTables($pdo);
$archiveRun = adminVisitorArchiveOldLogs($pdo);
$visitorPolicy = [
    'archive_enabled' => (string) adminSetting($pdo, 'visitor_archive_enabled', '1') === '1',
    'archive_after_days' => (int) adminSetting($pdo, 'visitor_archive_after_days', '30'),
    'archive_prune_days' => (int) adminSetting($pdo, 'visitor_archive_prune_days', '365'),
    'archive_interval_minutes' => (int) adminSetting($pdo, 'visitor_archive_interval_minutes', '60'),
    'live_max_rows' => (int) adminSetting($pdo, 'visitor_live_max_rows', '50000'),
    'block_vpn' => (string) adminSetting($pdo, 'visitor_block_vpn', '0') === '1',
    'block_proxy' => (string) adminSetting($pdo, 'visitor_block_proxy', '0') === '1',
    'block_tor' => (string) adminSetting($pdo, 'visitor_block_tor', '0') === '1',
    'block_hosting' => (string) adminSetting($pdo, 'visitor_block_hosting', '0') === '1',
];

// ── KPI Statistics ─────────────────────────────────────────
$stats = [
    'visitors_today' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_logs WHERE DATE(visited_at) = CURDATE()'),
    'visitors_week' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_logs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
    'visitors_month' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_logs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
    'unique_today' => (int) adminFetchValue($pdo, 'SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE DATE(visited_at) = CURDATE()'),
    'unique_week' => (int) adminFetchValue($pdo, 'SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
    'blocked_ips' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM ip_blocks'),
    'blocked_countries' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM country_blocks'),
    'top_country' => adminFetchValue($pdo, 'SELECT country FROM visitor_logs WHERE country IS NOT NULL GROUP BY country ORDER BY COUNT(*) DESC LIMIT 1') ?: 'N/A',
    'top_browser' => adminFetchValue($pdo, 'SELECT browser FROM visitor_logs GROUP BY browser ORDER BY COUNT(*) DESC LIMIT 1') ?: 'N/A',
    'bot_visits_today' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_logs WHERE is_bot = 1 AND DATE(visited_at) = CURDATE()'),
    'archived_visits' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_log_archive'),
    'flagged_networks' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM visitor_logs WHERE is_proxy = 1 OR is_vpn = 1 OR is_tor = 1 OR is_hosting = 1'),
];

// ── Chart Data ─────────────────────────────────────────────
$visitorsTimeline = adminFetchAll($pdo, "
    SELECT DATE(visited_at) AS day, COUNT(*) AS visits, COUNT(DISTINCT ip_address) AS unique_visitors
    FROM visitor_logs
    WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(visited_at)
    ORDER BY day ASC
");
$visitorsByDay = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $visitorsByDay[$day] = ['day' => $day, 'visits' => 0, 'unique_visitors' => 0];
}
foreach ($visitorsTimeline as $row) {
    $visitorsByDay[(string) $row['day']] = [
        'day' => (string) $row['day'],
        'visits' => (int) $row['visits'],
        'unique_visitors' => (int) $row['unique_visitors'],
    ];
}
$timelineData = array_values($visitorsByDay);

$countryData = adminFetchAll($pdo, '
    SELECT country, country_code, COUNT(*) AS visits, COUNT(DISTINCT ip_address) AS unique_visitors
    FROM visitor_logs
    WHERE country IS NOT NULL AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY country, country_code
    ORDER BY visits DESC
    LIMIT 10
');

$browserData = adminFetchAll($pdo, '
    SELECT browser, COUNT(*) AS visits
    FROM visitor_logs
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY browser
    ORDER BY visits DESC
    LIMIT 8
');

$deviceData = adminFetchAll($pdo, '
    SELECT device_type, COUNT(*) AS visits
    FROM visitor_logs
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY device_type
    ORDER BY visits DESC
');

$topPages = adminFetchAll($pdo, '
    SELECT page_url, COUNT(*) AS visits, COUNT(DISTINCT ip_address) AS unique_visitors
    FROM visitor_logs
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY page_url
    ORDER BY visits DESC
    LIMIT 10
');

$hourlyData = adminFetchAll($pdo, '
    SELECT HOUR(visited_at) AS hour, COUNT(*) AS visits
    FROM visitor_logs
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(visited_at)
    ORDER BY hour ASC
');

// ── Visitor Log (paginated) ────────────────────────────────
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filters
$filterCountry = trim((string) ($_GET['country'] ?? ''));
$filterBrowser = trim((string) ($_GET['browser'] ?? ''));
$filterDevice = trim((string) ($_GET['device'] ?? ''));
$filterDateFrom = trim((string) ($_GET['from'] ?? ''));
$filterDateTo = trim((string) ($_GET['to'] ?? ''));

$where = [];
$params = [];
if ($filterCountry !== '') { $where[] = 'country_code = ?'; $params[] = $filterCountry; }
if ($filterBrowser !== '') { $where[] = 'browser = ?'; $params[] = $filterBrowser; }
if ($filterDevice !== '') { $where[] = 'device_type = ?'; $params[] = $filterDevice; }
if ($filterDateFrom !== '') { $where[] = 'DATE(visited_at) >= ?'; $params[] = $filterDateFrom; }
if ($filterDateTo !== '') { $where[] = 'DATE(visited_at) <= ?'; $params[] = $filterDateTo; }

$whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

$totalVisitors = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM visitor_logs {$whereSql}", $params);
$totalPages = max(1, (int) ceil($totalVisitors / $perPage));

$visitorLogs = adminFetchAll($pdo, "
    SELECT * FROM visitor_logs {$whereSql}
    ORDER BY visited_at DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Unique values for filter dropdowns
$uniqueCountries = adminFetchAll($pdo, 'SELECT DISTINCT country_code, country FROM visitor_logs WHERE country IS NOT NULL ORDER BY country ASC');
$uniqueBrowsers = adminFetchAll($pdo, 'SELECT DISTINCT browser FROM visitor_logs ORDER BY browser ASC');

// ── Block Lists ────────────────────────────────────────────
$ipBlocks = adminFetchAll($pdo, 'SELECT * FROM ip_blocks ORDER BY created_at DESC');
$countryBlocks = adminFetchAll($pdo, 'SELECT * FROM country_blocks ORDER BY created_at DESC');

adminPageStart('Visitors', 'visitors');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Traffic Intelligence')) ?></span>
        <h1><i class="fas fa-eye"></i> <?= adminH(adminPhrase('Visitor Analytics')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Track, analyze, and protect. Monitor visitor traffic, browser fingerprints, and block threats.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><i class="fas fa-shield-halved"></i> <?= adminH(adminPhrase('Dashboard')) ?></a>
        <a class="button button-light" href="admin-analytics.php"><i class="fas fa-chart-pie"></i> <?= adminH(adminPhrase('Analytics')) ?></a>
    </div>
</section>

<!-- KPI Cards -->
<section class="stats-section">
    <div class="stats-section-header">
        <h2><i class="fas fa-signal"></i> <?= adminH(adminPhrase('Traffic Overview')) ?></h2>
        <span class="stats-period"><?= adminH(adminPhrase('Real-time visitor metrics')) ?></span>
    </div>
    <div class="stats-grid primary-stats">
        <article class="stat-card stat-card-featured" data-stat="visitors">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <strong class="stat-value"><?= number_format($stats['visitors_today']) ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Visits Today')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-info"><?= adminH(adminPhrase('{count} unique', ['count' => $stats['unique_today']])) ?></span>
                    <span class="stat-subtext"><?= adminH(adminPhrase('{count} this week', ['count' => $stats['visitors_week']])) ?></span>
                </div>
            </div>
        </article>
        <article class="stat-card" data-stat="monthly">
            <div class="stat-icon stat-icon-neutral"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-content">
                <strong class="stat-value"><?= number_format($stats['visitors_month']) ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Visits This Month')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-success"><?= adminH(adminPhrase('{count} unique IPs', ['count' => $stats['unique_week']])) ?></span>
                </div>
            </div>
        </article>
        <article class="stat-card" data-stat="blocked">
            <div class="stat-icon stat-icon-neutral"><i class="fas fa-ban"></i></div>
            <div class="stat-content">
                <strong class="stat-value"><?= $stats['blocked_ips'] + $stats['blocked_countries'] ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Active Blocks')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-danger"><?= adminH(adminPhrase('{count} IPs', ['count' => $stats['blocked_ips']])) ?></span>
                    <span class="stat-subtext"><?= adminH(adminPhrase('{count} countries', ['count' => $stats['blocked_countries']])) ?></span>
                </div>
            </div>
        </article>
        <article class="stat-card" data-stat="top">
            <div class="stat-icon stat-icon-neutral"><i class="fas fa-globe"></i></div>
            <div class="stat-content">
                <strong class="stat-value stat-value-truncate"><?= adminH($stats['top_country']) ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Top Country')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-neutral"><?= adminH(adminPhrase('Top browser: {browser}', ['browser' => $stats['top_browser']])) ?></span>
                </div>
            </div>
        </article>
        <article class="stat-card" data-stat="archive">
            <div class="stat-icon stat-icon-neutral"><i class="fas fa-box-archive"></i></div>
            <div class="stat-content">
                <strong class="stat-value"><?= number_format($stats['archived_visits']) ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Archived Visits')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-neutral"><?= adminH(adminPhrase('after {count} days', ['count' => $visitorPolicy['archive_after_days']])) ?></span>
                </div>
            </div>
        </article>
        <article class="stat-card" data-stat="network">
            <div class="stat-icon stat-icon-neutral"><i class="fas fa-network-wired"></i></div>
            <div class="stat-content">
                <strong class="stat-value"><?= number_format($stats['flagged_networks']) ?></strong>
                <span class="stat-label"><?= adminH(adminPhrase('Flagged Networks')) ?></span>
                <div class="stat-meta">
                    <span class="stat-badge badge-info"><?= adminH(adminPhrase('VPN / proxy / Tor / hosting')) ?></span>
                </div>
            </div>
        </article>
    </div>
</section>

<!-- Archive + Network Policy -->
<section class="table-card visitor-policy-card">
    <div class="card-head">
        <div>
            <h2><i class="fas fa-shield-virus"></i> <?= adminH(adminPhrase('Visitor Archive & Network Policy')) ?></h2>
            <p class="card-copy"><?= adminH(adminPhrase('Archive old visitor rows automatically and optionally block privacy networks. VPN, proxy, Tor, and hosting blocks are off until you enable them.')) ?></p>
            <?php if (!empty($archiveRun['message'])): ?>
                <p class="card-copy" style="margin-top:6px;"><?= adminH($archiveRun['message']) ?></p>
            <?php endif; ?>
        </div>
        <button type="button" id="runVisitorArchive" class="button button-light button-small">
            <i class="fas fa-box-archive"></i> <?= adminH(adminPhrase('Run Archive Now')) ?>
        </button>
    </div>
    <form id="visitorPolicyForm" class="visitor-policy-grid">
        <label class="policy-toggle">
            <input type="checkbox" id="archiveEnabled" <?= $visitorPolicy['archive_enabled'] ? 'checked' : '' ?>>
            <span>
                <strong><?= adminH(adminPhrase('Auto archive visitor logs')) ?></strong>
                <small><?= adminH(adminPhrase('Moves old rows out of the live visitor table.')) ?></small>
            </span>
        </label>
        <label>
            <span><?= adminH(adminPhrase('Archive live rows after days')) ?></span>
            <input type="number" id="archiveAfterDays" min="1" max="365" value="<?= (int) $visitorPolicy['archive_after_days'] ?>">
        </label>
        <label>
            <span><?= adminH(adminPhrase('Delete archive rows after days')) ?></span>
            <input type="number" id="archivePruneDays" min="2" max="3650" value="<?= (int) $visitorPolicy['archive_prune_days'] ?>">
        </label>
        <label>
            <span><?= adminH(adminPhrase('Archive check interval minutes')) ?></span>
            <input type="number" id="archiveIntervalMinutes" min="5" max="1440" value="<?= (int) $visitorPolicy['archive_interval_minutes'] ?>">
        </label>
        <label>
            <span><?= adminH(adminPhrase('Max live visitor rows')) ?></span>
            <input type="number" id="liveMaxRows" min="1000" max="1000000" step="1000" value="<?= (int) $visitorPolicy['live_max_rows'] ?>">
        </label>
        <label class="policy-toggle policy-danger">
            <input type="checkbox" id="blockVpn" <?= $visitorPolicy['block_vpn'] ? 'checked' : '' ?>>
            <span><strong><?= adminH(adminPhrase('Block VPN networks')) ?></strong><small><?= adminH(adminPhrase('Disabled by default. Uses detected VPN flags and ISP hints.')) ?></small></span>
        </label>
        <label class="policy-toggle policy-danger">
            <input type="checkbox" id="blockProxy" <?= $visitorPolicy['block_proxy'] ? 'checked' : '' ?>>
            <span><strong><?= adminH(adminPhrase('Block proxy networks')) ?></strong><small><?= adminH(adminPhrase('Disabled by default. Uses proxy flags and ISP hints.')) ?></small></span>
        </label>
        <label class="policy-toggle policy-danger">
            <input type="checkbox" id="blockTor" <?= $visitorPolicy['block_tor'] ? 'checked' : '' ?>>
            <span><strong><?= adminH(adminPhrase('Block Tor network')) ?></strong><small><?= adminH(adminPhrase('Disabled by default. Add Tor exit CIDRs to visitor_network_blocks for stronger matching.')) ?></small></span>
        </label>
        <label class="policy-toggle policy-danger">
            <input type="checkbox" id="blockHosting" <?= $visitorPolicy['block_hosting'] ? 'checked' : '' ?>>
            <span><strong><?= adminH(adminPhrase('Block hosting/datacenter networks')) ?></strong><small><?= adminH(adminPhrase('Disabled by default. Can block cloud servers and some legitimate crawlers.')) ?></small></span>
        </label>
        <div class="policy-actions">
            <button type="submit" class="button button-light"><i class="fas fa-save"></i> <?= adminH(adminPhrase('Save Visitor Policy')) ?></button>
        </div>
    </form>
</section>

<!-- Charts -->
<section class="table-card chart-section">
    <div class="card-head">
        <div>
            <h2><i class="fas fa-chart-line"></i> <?= adminH(adminPhrase('Traffic Charts')) ?></h2>
            <p class="card-copy"><?= adminH(adminPhrase('Visual breakdowns of visitor traffic patterns.')) ?></p>
        </div>
    </div>
    <div class="chart-grid">
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Visitors Over Time (14 Days)')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="timelineChart"></canvas></div>
        </div>
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Top Countries')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="countryChart"></canvas></div>
        </div>
    </div>
    <div class="chart-grid" style="margin-top: 24px;">
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Browser Distribution')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="browserChart"></canvas></div>
        </div>
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Device Types')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="deviceChart"></canvas></div>
        </div>
    </div>
    <div class="chart-grid" style="margin-top: 24px;">
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Top Pages Visited')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="pagesChart"></canvas></div>
        </div>
        <div class="chart-cell">
            <h3><?= adminH(adminPhrase('Hourly Traffic (Last 30 Days)')) ?></h3>
            <div class="chart-wrap-tall"><canvas id="hourlyChart"></canvas></div>
        </div>
    </div>
</section>

<!-- Filters + Visitor Log Table -->
<section class="table-card">
    <div class="card-head">
        <h2><i class="fas fa-list"></i> <?= adminH(adminPhrase('Visitor Log')) ?> <small style="opacity:0.6">(<?= number_format($totalVisitors) ?>)</small></h2>
    </div>
    <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid var(--border);">
        <select name="country" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <option value=""><?= adminH(adminPhrase('All Countries')) ?></option>
            <?php foreach ($uniqueCountries as $c): ?>
                <option value="<?= adminH($c['country_code']) ?>" <?= $filterCountry === $c['country_code'] ? 'selected' : '' ?>><?= adminH($c['country'] ?: $c['country_code']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="browser" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <option value=""><?= adminH(adminPhrase('All Browsers')) ?></option>
            <?php foreach ($uniqueBrowsers as $b): ?>
                <option value="<?= adminH($b['browser']) ?>" <?= $filterBrowser === $b['browser'] ? 'selected' : '' ?>><?= adminH($b['browser']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="device" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <option value=""><?= adminH(adminPhrase('All Devices')) ?></option>
            <option value="desktop" <?= $filterDevice === 'desktop' ? 'selected' : '' ?>><?= adminH(adminPhrase('Desktop')) ?></option>
            <option value="mobile" <?= $filterDevice === 'mobile' ? 'selected' : '' ?>><?= adminH(adminPhrase('Mobile')) ?></option>
            <option value="tablet" <?= $filterDevice === 'tablet' ? 'selected' : '' ?>><?= adminH(adminPhrase('Tablet')) ?></option>
        </select>
        <input type="date" name="from" value="<?= adminH($filterDateFrom) ?>" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
        <input type="date" name="to" value="<?= adminH($filterDateTo) ?>" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
        <button type="submit" class="button button-light button-small"><i class="fas fa-filter"></i> <?= adminH(adminPhrase('Filter')) ?></button>
        <a href="admin-visitors.php" class="button button-light button-small"><?= adminH(adminPhrase('Reset')) ?></a>
    </form>

    <div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th><?= adminH(adminPhrase('IP')) ?></th>
                <th><?= adminH(adminPhrase('Country')) ?></th>
                <th><?= adminH(adminPhrase('Browser')) ?></th>
                <th><?= adminH(adminPhrase('OS')) ?></th>
                <th><?= adminH(adminPhrase('Device')) ?></th>
                <th><?= adminH(adminPhrase('Page')) ?></th>
                <th><?= adminH(adminPhrase('Fingerprint')) ?></th>
                <th><?= adminH(adminPhrase('ISP')) ?></th>
                <th><?= adminH(adminPhrase('Network')) ?></th>
                <th><?= adminH(adminPhrase('Time')) ?></th>
                <th><?= adminH(adminPhrase('Actions')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($visitorLogs === []): ?>
                <tr><td colspan="11"><?= adminH(adminPhrase('No visitor data yet. Traffic will appear as visitors browse your store.')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($visitorLogs as $v): ?>
                <tr>
                    <td><code style="font-size:0.75rem;"><?= adminH($v['ip_address']) ?></code></td>
                    <td>
                        <?php if ($v['country']): ?>
                            <span title="<?= adminH($v['city'] ?: '') ?>"><?= adminH($v['country']) ?></span>
                            <?php if ($v['country_code']): ?>
                                <small style="opacity:0.6">(<?= adminH($v['country_code']) ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="opacity:0.4">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= adminH($v['browser']) ?> <small style="opacity:0.5"><?= adminH($v['browser_version'] ?? '') ?></small></td>
                    <td><?= adminH($v['os']) ?> <small style="opacity:0.5"><?= adminH($v['os_version'] ?? '') ?></small></td>
                    <td><span class="status-badge <?= $v['device_type'] === 'mobile' ? 'is-warn' : ($v['device_type'] === 'tablet' ? 'is-info' : '') ?>"><?= adminH(adminPhrase(ucfirst(strtolower((string)$v['device_type'])))) ?></span></td>
                    <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= adminH($v['page_url']) ?>"><?= adminH($v['page_url']) ?></td>
                    <td><code style="font-size:0.7rem;"><?= adminH($v['fingerprint_hash'] ? substr($v['fingerprint_hash'], 0, 12) . '...' : '—') ?></code></td>
                    <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.75rem;"><?= adminH($v['isp'] ?: '—') ?></td>
                    <td style="font-size:0.72rem;">
                        <?php $flags = array_filter(array_map('trim', explode(',', (string) ($v['network_flags'] ?? '')))); ?>
                        <?php if ($flags === []): ?>
                            <span style="opacity:0.45">—</span>
                        <?php else: ?>
                            <?php foreach ($flags as $flag): ?>
                                <span class="status-badge is-warn" style="margin:1px;"><?= adminH(strtoupper($flag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap; font-size:0.75rem;"><?= adminH(adminTranslateDate('M j, H:i', strtotime($v['visited_at']))) ?></td>
                    <td>
                        <button class="button button-light button-small block-ip-btn" data-ip="<?= adminH($v['ip_address']) ?>" title="<?= adminH(adminPhrase('Block this IP')) ?>">
                            <i class="fas fa-ban" style="color:#ff3d5a;"></i>
                        </button>
                        <?php if (!empty($v['country_code'])): ?>
                            <button class="button button-light button-small block-country-row-btn"
                                data-country-code="<?= adminH($v['country_code']) ?>"
                                data-country-name="<?= adminH($v['country'] ?: $v['country_code']) ?>"
                                title="<?= adminH(adminPhrase('Block this country')) ?>">
                                <i class="fas fa-globe-americas" style="color:#ff6b35;"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex; gap:8px; justify-content:center; padding:16px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&country=<?= urlencode($filterCountry) ?>&browser=<?= urlencode($filterBrowser) ?>&device=<?= urlencode($filterDevice) ?>&from=<?= urlencode($filterDateFrom) ?>&to=<?= urlencode($filterDateTo) ?>" class="button button-light button-small">&laquo; <?= adminH(adminPhrase('Prev')) ?></a>
        <?php endif; ?>
        <span style="padding:8px 12px; font-size:0.85rem; color:var(--muted);"><?= adminH(adminPhrase('Page {page} of {total}', ['page' => $page, 'total' => $totalPages])) ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&country=<?= urlencode($filterCountry) ?>&browser=<?= urlencode($filterBrowser) ?>&device=<?= urlencode($filterDevice) ?>&from=<?= urlencode($filterDateFrom) ?>&to=<?= urlencode($filterDateTo) ?>" class="button button-light button-small"><?= adminH(adminPhrase('Next')) ?> &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Block Management -->
<section class="analytics-grid-wide">
    <!-- IP Blocks -->
    <article class="table-card">
        <div class="card-head">
            <h2><i class="fas fa-ban" style="color:#ff3d5a;"></i> <?= adminH(adminPhrase('IP Blocks')) ?></h2>
        </div>
        <form id="ipBlockForm" style="display:flex; gap:8px; flex-wrap:wrap; padding:12px 0; border-bottom:1px solid var(--border); margin-bottom:12px;">
            <input type="text" id="blockIp" placeholder="<?= adminH(adminPhrase('IP Address (e.g. 1.2.3.4)')) ?>" required style="flex:1; min-width:140px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <input type="text" id="blockIpReason" placeholder="<?= adminH(adminPhrase('Reason (optional)')) ?>" style="flex:1; min-width:140px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <input type="datetime-local" id="blockIpExpiry" style="padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <button type="submit" class="button button-light button-small"><i class="fas fa-plus"></i> <?= adminH(adminPhrase('Block')) ?></button>
        </form>
        <table>
            <thead><tr><th><?= adminH(adminPhrase('IP')) ?></th><th><?= adminH(adminPhrase('Reason')) ?></th><th><?= adminH(adminPhrase('Expires')) ?></th><th><?= adminH(adminPhrase('Created')) ?></th><th></th></tr></thead>
            <tbody id="ipBlocksBody">
                <?php if ($ipBlocks === []): ?>
                    <tr><td colspan="5" style="opacity:0.5;"><?= adminH(adminPhrase('No IP blocks configured.')) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($ipBlocks as $b): ?>
                    <tr data-block-id="<?= (int) $b['id'] ?>">
                        <td><code><?= adminH($b['ip_address']) ?></code></td>
                        <td style="font-size:0.8rem;"><?= adminH($b['reason'] ?: '—') ?></td>
                        <td style="font-size:0.75rem;"><?= $b['expires_at'] ? adminH(adminTranslateDate('M j, Y H:i', strtotime($b['expires_at']))) : adminH(adminPhrase('Never')) ?></td>
                        <td style="font-size:0.75rem;"><?= adminH(adminTranslateDate('M j, Y', strtotime($b['created_at']))) ?></td>
                        <td><button class="button button-light button-small unblock-ip-btn" data-id="<?= (int) $b['id'] ?>"><i class="fas fa-times" style="color:#ff3d5a;"></i></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <!-- Country Blocks -->
    <article class="table-card">
        <div class="card-head">
            <div>
                <h2><i class="fas fa-globe" style="color:#ff6b35;"></i> <?= adminH(adminPhrase('Country Blocks')) ?></h2>
                <p class="card-copy"><?= adminH(adminPhrase('Country blocks apply to future storefront visits after GeoIP lookup. Local/private IPs cannot be matched to a country.')) ?></p>
                <p class="card-copy" style="margin-top:6px;"><?= adminH(adminPhrase('To block Israel for testing, enter country code IL and country name Israel, then submit Block Country.')) ?></p>
            </div>
        </div>
        <form id="countryBlockForm" style="display:flex; gap:8px; flex-wrap:wrap; padding:12px 0; border-bottom:1px solid var(--border); margin-bottom:12px;">
            <select id="blockCountryPreset" style="min-width:180px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
                <option value=""><?= adminH(adminPhrase('Choose seen country...')) ?></option>
                <?php foreach ($uniqueCountries as $c): ?>
                    <?php if (!empty($c['country_code'])): ?>
                        <option value="<?= adminH($c['country_code'] . '|' . ($c['country'] ?: $c['country_code'])) ?>"><?= adminH(($c['country'] ?: $c['country_code']) . ' (' . $c['country_code'] . ')') ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <input type="text" id="blockCountryCode" placeholder="<?= adminH(adminPhrase('Country Code (e.g. CN)')) ?>" required maxlength="2" style="width:80px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem; text-transform:uppercase;">
            <input type="text" id="blockCountryName" placeholder="<?= adminH(adminPhrase('Country Name')) ?>" required style="flex:1; min-width:120px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <input type="text" id="blockCountryReason" placeholder="<?= adminH(adminPhrase('Reason (optional)')) ?>" style="flex:1; min-width:120px; padding:8px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border); color:var(--text); font-size:0.8rem;">
            <button type="button" id="fillIsraelBlock" class="button button-light button-small"><i class="fas fa-vial"></i> <?= adminH(adminPhrase('Fill Israel Test')) ?></button>
            <button type="submit" class="button button-light button-small"><i class="fas fa-plus"></i> <?= adminH(adminPhrase('Block Country')) ?></button>
        </form>
        <table>
            <thead><tr><th><?= adminH(adminPhrase('Country')) ?></th><th><?= adminH(adminPhrase('Code')) ?></th><th><?= adminH(adminPhrase('Reason')) ?></th><th><?= adminH(adminPhrase('Created')) ?></th><th></th></tr></thead>
            <tbody id="countryBlocksBody">
                <?php if ($countryBlocks === []): ?>
                    <tr><td colspan="5" style="opacity:0.5;"><?= adminH(adminPhrase('No country blocks configured.')) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($countryBlocks as $cb): ?>
                    <tr data-block-id="<?= (int) $cb['id'] ?>">
                        <td><?= adminH($cb['country_name']) ?></td>
                        <td><code><?= adminH($cb['country_code']) ?></code></td>
                        <td style="font-size:0.8rem;"><?= adminH($cb['reason'] ?: '—') ?></td>
                        <td style="font-size:0.75rem;"><?= adminH(adminTranslateDate('M j, Y', strtotime($cb['created_at']))) ?></td>
                        <td><button class="button button-light button-small unblock-country-btn" data-id="<?= (int) $cb['id'] ?>"><i class="fas fa-times" style="color:#ff6b35;"></i></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>

<style>
.visitor-policy-card {
    margin-bottom: 24px;
    overflow: hidden;
}
.visitor-policy-card .card-head {
    align-items: flex-start;
    gap: 18px;
}
.visitor-policy-card .card-head > div {
    min-width: 0;
}
.visitor-policy-card .card-copy {
    max-width: 76ch;
}
.visitor-policy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    padding: 20px;
}
.visitor-policy-grid label {
    display: flex;
    flex-direction: column;
    gap: 7px;
    color: var(--text);
    font-size: 0.82rem;
    min-width: 0;
}
.visitor-policy-grid input[type="number"] {
    width: 100%;
    min-height: 38px;
    padding: 8px 10px;
    border-radius: 8px;
    background: var(--input-bg);
    border: 1px solid var(--border);
    color: var(--text);
}
.policy-toggle {
    position: static;
    width: auto;
    height: auto;
    flex-direction: row !important;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: rgba(255,255,255,0.02);
}
.policy-toggle input {
    margin-top: 3px;
    flex: 0 0 auto;
    width: 16px;
    height: 16px;
    accent-color: var(--cyan);
}
.policy-toggle span {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
.policy-toggle strong,
.policy-toggle small {
    overflow-wrap: anywhere;
}
.policy-toggle small {
    color: var(--muted);
    line-height: 1.45;
}
.policy-danger {
    border-color: rgba(255, 107, 53, 0.24);
}
.policy-actions {
    display: flex;
    align-items: end;
}
html[dir="rtl"] .visitor-policy-card,
html[dir="rtl"] .visitor-policy-card input,
html[dir="rtl"] .visitor-policy-card button {
    text-align: right;
}
@media (max-width: 720px) {
    .visitor-policy-card .card-head {
        flex-direction: column;
    }
    .visitor-policy-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }
    .visitor-policy-grid .button {
        width: 100%;
    }
}
</style>

<script>
(function() {
    // ── Translations ────────────────────────────────
    // Device type labels for chart
    window.deviceTypeLabels = {
        desktop: <?= json_encode(adminPhrase('Desktop')) ?>,
        mobile: <?= json_encode(adminPhrase('Mobile')) ?>,
        tablet: <?= json_encode(adminPhrase('Tablet')) ?>,
        unknown: <?= json_encode(adminPhrase('Unknown')) ?>,
    };
    // Browser labels for chart
    window.browserLabels = {
        Chrome: <?= json_encode(adminPhrase('Chrome')) ?>,
        Firefox: <?= json_encode(adminPhrase('Firefox')) ?>,
        Safari: <?= json_encode(adminPhrase('Safari')) ?>,
        Edge: <?= json_encode(adminPhrase('Edge')) ?>,
        Opera: <?= json_encode(adminPhrase('Opera')) ?>,
        Unknown: <?= json_encode(adminPhrase('Unknown')) ?>,
    };
        var i18n = {
        totalVisits: <?= json_encode(adminPhrase('Total Visits')) ?>,
        uniqueVisitors: <?= json_encode(adminPhrase('Unique Visitors')) ?>,
        visits: <?= json_encode(adminPhrase('Visits')) ?>,
        blockIpPromptClean: <?= json_encode(adminPhrase('Block IP {ip} - Reason (optional):', ['ip' => '{ip}'])) ?>,
        blockCountryPrompt: <?= json_encode(adminPhrase('Block country {country} ({code}) - Reason (optional):', ['country' => '{country}', 'code' => '{code}'])) ?>,
        blockIpPrompt: <?= json_encode(adminPhrase('Block IP {ip} — Reason (optional):', ['ip' => ''])) ?>,
        unblockIpConfirm: <?= json_encode(adminPhrase('Unblock this IP?')) ?>,
        unblockCountryConfirm: <?= json_encode(adminPhrase('Unblock this country?')) ?>,
        errorPrefix: <?= json_encode(adminPhrase('Error: ')) ?>,
        savePolicyFailed: <?= json_encode(adminPhrase('Could not save visitor policy.')) ?>,
        runArchiveFailed: <?= json_encode(adminPhrase('Could not run visitor archive.')) ?>
    };

    // ── Theme ───────────────────────────────────────
    function phrase(template, replacements) {
        Object.keys(replacements || {}).forEach(function(key) {
            template = template.replaceAll('{' + key + '}', replacements[key]);
        });
        return template;
    }

    function getChartTheme() {
        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        return {
            isDark,
            grid: isDark ? 'rgba(255,255,255,0.10)' : 'rgba(15,23,42,0.12)',
            gridSubtle: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(15,23,42,0.08)',
            text: isDark ? '#b0b8c8' : '#475569',
            tooltipBg: isDark ? 'rgba(10,11,14,0.95)' : 'rgba(255,255,255,0.98)',
            tooltipTitle: isDark ? '#00f5d4' : '#007A6E',
            tooltipBorder: isDark ? '#2d333b' : '#e2e8f0',
            cyan: isDark ? '#00f5d4' : '#007A6E',
            cyanFill: isDark ? 'rgba(0,245,212,0.2)' : 'rgba(0,122,110,0.14)',
            purple: isDark ? '#667eea' : '#4f46e5',
            purpleFill: isDark ? 'rgba(102,126,234,0.2)' : 'rgba(79,70,229,0.14)',
            orange: isDark ? '#ff6b35' : '#D95F0A',
            orangeFill: isDark ? 'rgba(255,107,53,0.2)' : 'rgba(217,95,10,0.14)',
            border: isDark ? '#1e2229' : '#e2e8f0',
            white: '#ffffff'
        };
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    var t = getChartTheme();
    Chart.defaults.color = t.text;
    Chart.defaults.plugins.tooltip.backgroundColor = t.tooltipBg;
    Chart.defaults.plugins.tooltip.titleColor = t.tooltipTitle;
    Chart.defaults.plugins.tooltip.borderColor = t.tooltipBorder;

    // ── 1. Timeline (Line) ──────────────────────────
    var tlData = <?= json_encode($timelineData) ?>;
    new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: {
            labels: tlData.map(function(d) { return d.day.slice(5); }),
            datasets: [
                {
                    label: i18n.totalVisits,
                    data: tlData.map(function(d) { return d.visits; }),
                    borderColor: t.cyan, backgroundColor: t.cyanFill,
                    fill: true, tension: 0.4, borderWidth: 2,
                    pointRadius: 4, pointBackgroundColor: t.cyan, pointBorderColor: t.white, pointBorderWidth: 2
                },
                {
                    label: i18n.uniqueVisitors,
                    data: tlData.map(function(d) { return d.unique_visitors; }),
                    borderColor: t.purple, backgroundColor: t.purpleFill,
                    fill: true, tension: 0.4, borderWidth: 2,
                    pointRadius: 4, pointBackgroundColor: t.purple, pointBorderColor: t.white, pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, grid: { color: t.gridSubtle } }, x: { grid: { display: false } } },
            plugins: { legend: { position: 'top', labels: { padding: 12, font: { size: 11 } } } }
        }
    });

    // ── 2. Countries (Horizontal Bar) ───────────────
    var cData = <?= json_encode($countryData) ?>;
    new Chart(document.getElementById('countryChart'), {
        type: 'bar',
        data: {
            labels: cData.map(function(d) { return d.country || d.country_code; }),
            datasets: [{
                label: i18n.visits,
                data: cData.map(function(d) { return d.visits; }),
                backgroundColor: t.cyanFill, borderColor: t.cyan, borderWidth: 2, borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, grid: { color: t.gridSubtle } }, y: { grid: { display: false } } },
            plugins: { legend: { display: false } }
        }
    });

    // ── 3. Browsers (Doughnut) ──────────────────────
    var bData = <?= json_encode($browserData) ?>;
    var browserColors = ['#00f5d4','#667eea','#ff6b35','#feca57','#48dbfb','#f093fb','#ff6b6b','#1dd1a1'];
    new Chart(document.getElementById('browserChart'), {
        type: 'doughnut',
        data: {
            labels: bData.map(function(d) { var b = d.browser; return window.browserLabels && window.browserLabels[b] ? window.browserLabels[b] : b; }),
            datasets: [{
                data: bData.map(function(d) { return d.visits; }),
                backgroundColor: browserColors.slice(0, bData.length),
                borderWidth: 2, borderColor: t.border, hoverOffset: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { position: 'right', labels: { padding: 12, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } } }
        }
    });

    // ── 4. Devices (Pie) ────────────────────────────
    var dData = <?= json_encode($deviceData) ?>;
    var deviceColors = { desktop: '#00f5d4', mobile: '#667eea', tablet: '#ff6b35' };
    new Chart(document.getElementById('deviceChart'), {
        type: 'pie',
        data: {
            labels: dData.map(function(d) { var dt = d.device_type; return window.deviceTypeLabels && window.deviceTypeLabels[dt] ? window.deviceTypeLabels[dt] : dt.charAt(0).toUpperCase() + dt.slice(1); }),
            datasets: [{
                data: dData.map(function(d) { return d.visits; }),
                backgroundColor: dData.map(function(d) { return deviceColors[d.device_type] || '#48dbfb'; }),
                borderWidth: 2, borderColor: t.border, hoverOffset: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { padding: 12, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } } }
        }
    });

    // ── 5. Top Pages (Bar) ──────────────────────────
    var pData = <?= json_encode($topPages) ?>;
    new Chart(document.getElementById('pagesChart'), {
        type: 'bar',
        data: {
            labels: pData.map(function(d) { var p = d.page_url; return p.length > 30 ? p.substring(0,30) + '...' : p; }),
            datasets: [{
                label: i18n.visits,
                data: pData.map(function(d) { return d.visits; }),
                backgroundColor: t.orangeFill, borderColor: t.orange, borderWidth: 2, borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, grid: { color: t.gridSubtle } }, y: { grid: { display: false }, ticks: { font: { size: 10 } } } },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { title: function(ctx) { return pData[ctx[0].dataIndex].page_url; } } }
            }
        }
    });

    // ── 6. Hourly Traffic (Area) ────────────────────
    var hData = <?= json_encode($hourlyData) ?>;
    var hourMap = {}; for (var i = 0; i < 24; i++) hourMap[i] = 0;
    hData.forEach(function(d) { hourMap[parseInt(d.hour)] = parseInt(d.visits); });
    var hourLabels = Object.keys(hourMap).map(function(h) { return h + ':00'; });
    var hourCounts = Object.values(hourMap);

    new Chart(document.getElementById('hourlyChart'), {
        type: 'line',
        data: {
            labels: hourLabels,
            datasets: [{
                label: i18n.visits,
                data: hourCounts,
                backgroundColor: t.purpleFill, borderColor: t.purple,
                fill: true, tension: 0.4, borderWidth: 2,
                pointRadius: 3, pointBackgroundColor: t.purple, pointBorderColor: t.white, pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, grid: { color: t.gridSubtle } }, x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 90, minRotation: 90 } } },
            plugins: { legend: { display: false } }
        }
    });

    // ── Block / Unblock IP ──────────────────────────
    var API = 'api/admin-visitor-management.php';

    function postVisitorAction(body) {
        return fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        }).then(function(r) { return r.json(); });
    }

    var policyForm = document.getElementById('visitorPolicyForm');
    if (policyForm) policyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        postVisitorAction({
            action: 'save_visitor_policy',
            archive_enabled: document.getElementById('archiveEnabled').checked,
            archive_after_days: document.getElementById('archiveAfterDays').value,
            archive_prune_days: document.getElementById('archivePruneDays').value,
            archive_interval_minutes: document.getElementById('archiveIntervalMinutes').value,
            live_max_rows: document.getElementById('liveMaxRows').value,
            block_vpn: document.getElementById('blockVpn').checked,
            block_proxy: document.getElementById('blockProxy').checked,
            block_tor: document.getElementById('blockTor').checked,
            block_hosting: document.getElementById('blockHosting').checked
        }).then(function(d) {
            alert(d.message || d.error || i18n.savePolicyFailed);
            if (d.success) location.reload();
        }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
    });

    var archiveBtn = document.getElementById('runVisitorArchive');
    if (archiveBtn) archiveBtn.addEventListener('click', function() {
        archiveBtn.disabled = true;
        postVisitorAction({ action: 'run_visitor_archive' }).then(function(d) {
            alert(d.message || d.error || i18n.runArchiveFailed);
            if (d.success) location.reload();
            else archiveBtn.disabled = false;
        }).catch(function(e) {
            archiveBtn.disabled = false;
            alert(i18n.errorPrefix + e.message);
        });
    });

    // Block IP from visitor log row
    document.querySelectorAll('.block-ip-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var ip = this.dataset.ip;
            var reason = prompt(phrase(i18n.blockIpPromptClean, { ip: ip }));
            if (reason === null) return;
            fetch(API, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'block_ip', ip_address: ip, reason: reason })
            }).then(function(r) { return r.json(); }).then(function(d) {
                alert(d.message || d.error);
                if (d.success) location.reload();
            }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
        });
    });

    // Block country from visitor log row
    document.querySelectorAll('.block-country-row-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = (this.dataset.countryCode || '').toUpperCase();
            var country = this.dataset.countryName || code;
            var reason = prompt(phrase(i18n.blockCountryPrompt, { country: country, code: code }));
            if (reason === null) return;
            fetch(API, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'block_country', country_code: code, country_name: country, reason: reason })
            }).then(function(r) { return r.json(); }).then(function(d) {
                alert(d.message || d.error);
                if (d.success) location.reload();
            }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
        });
    });

    // IP block form
    var ipForm = document.getElementById('ipBlockForm');
    if (ipForm) ipForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var body = {
            action: 'block_ip',
            ip_address: document.getElementById('blockIp').value,
            reason: document.getElementById('blockIpReason').value,
            expires_at: document.getElementById('blockIpExpiry').value ? document.getElementById('blockIpExpiry').value.replace('T',' ') + ':00' : null
        };
        fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) })
        .then(function(r) { return r.json(); }).then(function(d) {
            alert(d.message || d.error);
            if (d.success) location.reload();
        }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
    });

    // Unblock IP
    document.querySelectorAll('.unblock-ip-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm(i18n.unblockIpConfirm)) return;
            fetch(API, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ action: 'unblock_ip', id: parseInt(this.dataset.id) })
            }).then(function(r) { return r.json(); }).then(function(d) {
                alert(d.message || d.error);
                if (d.success) location.reload();
            }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
        });
    });

    var countryPreset = document.getElementById('blockCountryPreset');
    if (countryPreset) countryPreset.addEventListener('change', function() {
        if (!this.value) return;
        var parts = this.value.split('|');
        document.getElementById('blockCountryCode').value = (parts[0] || '').toUpperCase();
        document.getElementById('blockCountryName').value = parts[1] || parts[0] || '';
    });

    var fillIsraelBlock = document.getElementById('fillIsraelBlock');
    if (fillIsraelBlock) fillIsraelBlock.addEventListener('click', function() {
        document.getElementById('blockCountryCode').value = 'IL';
        document.getElementById('blockCountryName').value = 'Israel';
        document.getElementById('blockCountryReason').value = document.getElementById('blockCountryReason').value || 'GeoIP block test';
    });

    // Country block form
    var cForm = document.getElementById('countryBlockForm');
    if (cForm) cForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var body = {
            action: 'block_country',
            country_code: document.getElementById('blockCountryCode').value.toUpperCase(),
            country_name: document.getElementById('blockCountryName').value,
            reason: document.getElementById('blockCountryReason').value
        };
        fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) })
        .then(function(r) { return r.json(); }).then(function(d) {
            alert(d.message || d.error);
            if (d.success) location.reload();
        }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
    });

    // Unblock country
    document.querySelectorAll('.unblock-country-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm(i18n.unblockCountryConfirm)) return;
            fetch(API, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ action: 'unblock_country', id: parseInt(this.dataset.id) })
            }).then(function(r) { return r.json(); }).then(function(d) {
                alert(d.message || d.error);
                if (d.success) location.reload();
            }).catch(function(e) { alert(i18n.errorPrefix + e.message); });
        });
    });
})();
</script>

<?php adminPageEnd(); ?>
