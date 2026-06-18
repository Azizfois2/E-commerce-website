<?php
/**
 * i18n Diagnostic endpoint.
 * Visit: /api/i18n-diagnostic.php?lang=fr
 * Or:   /api/i18n-diagnostic.php?lang=es
 */
header('Content-Type: text/plain; charset=utf-8');

// Manually set locale
$lang = $_GET['lang'] ?? 'fr';
setcookie('marocpc_lang', $lang, 0, '/');

$_GET['lang'] = $lang;
$_COOKIE['marocpc_lang'] = $lang;

// Load i18n
require_once dirname(__DIR__) . '/includes/i18n.php';

echo "=== LOCALE ===\n";
$locale = i18n_current_locale();
echo "Current locale: " . var_export($locale, true) . "\n";
echo "GET lang: " . var_export($_GET['lang'] ?? 'none', true) . "\n";
echo "COOKIE lang: " . var_export($_COOKIE['marocpc_lang'] ?? 'none', true) . "\n\n";

echo "=== PHRASE MAP for '$locale' ===\n";
$map = i18n_page_phrase_map($locale);

$testKeys = [
    'Captured {count} product prices for {date}',
    'Synced {count} customer loyalty record(s)',
    'Requested customer approval for Trusted by Gamers publication',
    'Updated homepage gamer review for {name}',
    'Dashboard Activity Feed',
    'Visitor archival is disabled.',
];

foreach ($testKeys as $key) {
    if (array_key_exists($key, $map)) {
        echo "  ✓ '$key' => '" . $map[$key] . "'\n";
    } else {
        echo "  ✗ '$key' -- KEY NOT FOUND IN PHRASE MAP!\n";
    }
}

echo "\n=== CATALOG check for '$locale' ===\n";
$catalog = i18n_catalog($locale);
$catalogKeys = [
    'admin.dashboard_activity_feed',
];
foreach ($catalogKeys as $key) {
    $val = i18n_lookup($catalog, $key);
    if ($val !== null) {
        echo "  ✓ '$key' => '" . $val . "'\n";
    } else {
        echo "  ✗ '$key' -- NOT FOUND IN CATALOG\n";
    }
}

echo "\n=== adminPhrase() TEST ===\n";
// Simulate what adminActivitySummaryLabel does for the user's activity items
$testCases = [
    ['Captured {count} product prices for {date}', ['count' => '92', 'date' => '2026-06-14']],
    ['Synced {count} customer loyalty record(s)', ['count' => '0']],
    ['Requested customer approval for Trusted by Gamers publication', []],
    ['Updated homepage gamer review for {name}', ['name' => 'Sofia T.']],
];

foreach ($testCases as $idx => [$key, $params]) {
    $translated = $key;
    if (array_key_exists($key, $map)) {
        $translated = $map[$key];
    } else {
        echo "  '$key' -- MISSING, using English fallback\n";
    }
    foreach ($params as $name => $replacement) {
        $translated = str_replace('{' . $name . '}', (string) $replacement, $translated);
    }
    echo "  [" . ($idx+1) . "] => $translated\n";
}

echo "\n=== PHP VERSION ===\n";
echo phpversion() . "\n";

echo "\n=== DONE ===\n";
