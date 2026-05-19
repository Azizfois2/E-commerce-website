<?php
// scripts/seed-price-history.php — Seed 90 days of realistic price history
require_once __DIR__ . '/../bootstrap.php';
$pdo = db();

$products = $pdo->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
$insertStmt = $pdo->prepare("INSERT IGNORE INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
$count = 0;

foreach ($products as $p) {
    $basePrice = (float)$p['price'];
    for ($d = 90; $d >= 0; $d--) {
        $date = date('Y-m-d', strtotime("-{$d} days"));
        $variation = (mt_rand(-8, 5) / 100);
        $dayPrice = round($basePrice * (1 + $variation), 2);
        if ($d % 15 === 0 && $d > 0) {
            $dayPrice = round($basePrice * (1 - mt_rand(10, 25) / 100), 2);
        }
        $insertStmt->execute([(int)$p['id'], $dayPrice, $date]);
        $count++;
    }
}

echo "Seeded {$count} price history records for " . count($products) . " products.\n";
