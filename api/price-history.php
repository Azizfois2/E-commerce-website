<?php
/**
 * api/price-history.php — Price history data & snapshot
 *
 * GET  ?product_id=X             → Get price history for a product
 * GET  ?action=snapshot          → Snapshot all current prices (admin/cron)
 * GET  ?action=seed&product_id=X → Seed demo data for a product (dev only)
 */
require_once dirname(__DIR__) . '/bootstrap.php';
require_once SRC_PATH . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'price-alerts.php';

header('Content-Type: application/json');
$pdo = db();

$action = $_GET['action'] ?? 'history';

// ── Get price history for a product ──────────────────────
if ($action === 'history') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId <= 0) {
        jsonResponse(false, 'Product ID required.');
    }

    $days = min(365, max(7, (int)($_GET['days'] ?? 90)));

    $stmt = $pdo->prepare("
        SELECT price, recorded_at
        FROM price_history
        WHERE product_id = ? AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY recorded_at ASC
    ");
    $stmt->execute([$productId, $days]);
    $history = $stmt->fetchAll();

    // Get current price
    $priceStmt = $pdo->prepare("SELECT price, old_price, name FROM products WHERE id = ?");
    $priceStmt->execute([$productId]);
    $product = $priceStmt->fetch();

    if (!$product) {
        jsonResponse(false, 'Product not found.');
    }

    // Calculate stats
    $prices = array_column($history, 'price');
    $stats = [
        'current' => (float)$product['price'],
        'lowest' => $prices ? min(array_map('floatval', $prices)) : (float)$product['price'],
        'highest' => $prices ? max(array_map('floatval', $prices)) : (float)$product['price'],
        'average' => $prices ? round(array_sum(array_map('floatval', $prices)) / count($prices), 2) : (float)$product['price'],
    ];

    // If no history, include today's price as a single data point
    if (empty($history)) {
        $history = [['price' => $product['price'], 'recorded_at' => date('Y-m-d')]];
    }

    jsonResponse(true, 'Price history loaded.', [
        'product_name' => $product['name'],
        'history' => $history,
        'stats' => $stats,
        'days' => $days,
    ]);
}

// ── Snapshot current prices (run daily via cron or admin) ─
if ($action === 'snapshot') {
    // Optional: restrict to admin
    $today = date('Y-m-d');

    // Check if already snapshotted today
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM price_history WHERE recorded_at = ?");
    $checkStmt->execute([$today]);
    $alreadyDone = (int)$checkStmt->fetchColumn();

    if ($alreadyDone > 0) {
        $alerts = processDuePriceAlerts($pdo);
        jsonResponse(true, "Already snapshotted for {$today}.", [
            'count' => $alreadyDone,
            'alerts' => $alerts,
        ]);
    }

    $products = $pdo->query("SELECT id, price FROM products")->fetchAll();

    $insertStmt = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");

    $count = 0;
    foreach ($products as $p) {
        $insertStmt->execute([(int)$p['id'], (float)$p['price'], $today]);
        $count++;
    }

    $alerts = processDuePriceAlerts($pdo);

    jsonResponse(true, "Snapshotted {$count} product prices for {$today}.", [
        'count' => $count,
        'alerts' => $alerts,
    ]);
}

// ── Seed demo price history data (dev mode only) ─────────
if ($action === 'seed') {
    if (!defined('DEV_MODE') || !DEV_MODE) {
        jsonResponse(false, 'Only available in dev mode.');
    }

    $productId = (int)($_GET['product_id'] ?? 0);

    // If no product_id, seed all products
    if ($productId > 0) {
        $products = $pdo->prepare("SELECT id, price FROM products WHERE id = ?");
        $products->execute([$productId]);
        $products = $products->fetchAll();
    } else {
        $products = $pdo->query("SELECT id, price FROM products")->fetchAll();
    }

    $insertStmt = $pdo->prepare("INSERT IGNORE INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
    $count = 0;

    foreach ($products as $p) {
        $basePrice = (float)$p['price'];

        // Generate 90 days of slightly varying prices
        for ($d = 90; $d >= 0; $d--) {
            $date = date('Y-m-d', strtotime("-{$d} days"));

            // Create realistic price fluctuations
            $variation = (mt_rand(-8, 5) / 100); // -8% to +5%
            $dayPrice = round($basePrice * (1 + $variation), 2);

            // Occasional sale prices (every ~15 days)
            if ($d % 15 === 0 && $d > 0) {
                $dayPrice = round($basePrice * (1 - mt_rand(10, 25) / 100), 2);
            }

            $insertStmt->execute([(int)$p['id'], $dayPrice, $date]);
            $count++;
        }
    }

    jsonResponse(true, "Seeded {$count} price history records.", ['count' => $count]);
}

jsonResponse(false, 'Unknown action.');
