<?php
/**
 * seed-flash-sales.php — Insert test flash sales for development.
 * Run once: php seed-flash-sales.php  OR  visit http://localhost/Test/seed-flash-sales.php
 * Delete after testing.
 */
require_once dirname(__DIR__) . '/bootstrap.php';
$pdo = db();

// Clear existing flash sales
$pdo->exec("DELETE FROM flash_sales");

// Get some products to put on flash sale
$products = $pdo->query("SELECT id, name, price FROM products ORDER BY id LIMIT 6")->fetchAll();

if (empty($products)) {
    echo "No products found. Add products first.\n";
    exit;
}

$now = new DateTime();
$endsAt = (clone $now)->modify('+3 days +6 hours');

$sales = [];

// Sale 1: first product — 30% off, limited stock
if (isset($products[0])) {
    $p = $products[0];
    $salePrice = round($p['price'] * 0.70, 2);
    $sales[] = [$p['id'], $salePrice, $p['price'], 15, $now->format('Y-m-d H:i:s'), $endsAt->format('Y-m-d H:i:s')];
}

// Sale 2: second product — 25% off, limited stock
if (isset($products[1])) {
    $p = $products[1];
    $salePrice = round($p['price'] * 0.75, 2);
    $sales[] = [$p['id'], $salePrice, $p['price'], 8, $now->format('Y-m-d H:i:s'), $endsAt->format('Y-m-d H:i:s')];
}

// Sale 3: third product — 20% off, unlimited
if (isset($products[2])) {
    $p = $products[2];
    $salePrice = round($p['price'] * 0.80, 2);
    $endsLater = (clone $now)->modify('+5 days');
    $sales[] = [$p['id'], $salePrice, $p['price'], null, $now->format('Y-m-d H:i:s'), $endsLater->format('Y-m-d H:i:s')];
}

// Sale 4: fourth product — 15% off
if (isset($products[3])) {
    $p = $products[3];
    $salePrice = round($p['price'] * 0.85, 2);
    $sales[] = [$p['id'], $salePrice, $p['price'], 20, $now->format('Y-m-d H:i:s'), $endsAt->format('Y-m-d H:i:s')];
}

$stmt = $pdo->prepare("
    INSERT INTO flash_sales (product_id, sale_price, original_price, max_quantity, starts_at, ends_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($sales as $sale) {
    $stmt->execute($sale);
}

echo "✅ Seeded " . count($sales) . " flash sales!\n";
echo "\nProducts on sale:\n";
foreach ($sales as $i => $sale) {
    $p = $products[$i];
    $discount = round((1 - $sale[1] / $sale[2]) * 100);
    echo "  - {$p['name']}: {$sale[2]} MAD → {$sale[1]} MAD (-{$discount}%)\n";
}
echo "\nSale ends: {$endsAt->format('Y-m-d H:i:s')}\n";
echo "Visit http://localhost/Test/index.html to see the flash sales in action!\n";
