<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once SRC_PATH . '/Services/admin-helpers.php';

$pdo = db();
adminEnsureProductAdminColumns($pdo);

$motherboards = array_values(array_filter(
    adminParseDataJs(),
    static fn(array $product): bool => ($product['category'] ?? '') === 'motherboard'
));

if ($motherboards === []) {
    echo "No motherboard products found in assets/js/data.js\n";
    exit(0);
}

$stmt = $pdo->prepare('
    INSERT INTO products
        (id, name, brand, category, price, old_price, badge, rating, reviews, image, featured, in_stock, specs, stock_quantity, reorder_level)
    VALUES
        (:id, :name, :brand, :category, :price, :old_price, :badge, :rating, :reviews, :image, :featured, :in_stock, :specs, :stock_quantity, :reorder_level)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        brand = VALUES(brand),
        category = VALUES(category),
        price = VALUES(price),
        old_price = VALUES(old_price),
        badge = VALUES(badge),
        rating = VALUES(rating),
        reviews = VALUES(reviews),
        image = VALUES(image),
        featured = VALUES(featured),
        in_stock = VALUES(in_stock),
        specs = VALUES(specs),
        stock_quantity = VALUES(stock_quantity),
        reorder_level = VALUES(reorder_level)
');

foreach ($motherboards as $product) {
    $inStock = !empty($product['inStock']);
    $stmt->execute([
        'id' => (int) $product['id'],
        'name' => (string) $product['name'],
        'brand' => (string) ($product['brand'] ?? ''),
        'category' => (string) ($product['category'] ?? ''),
        'price' => (float) ($product['price'] ?? 0),
        'old_price' => isset($product['oldPrice']) ? (float) $product['oldPrice'] : null,
        'badge' => $product['badge'] ?? null,
        'rating' => isset($product['rating']) ? (float) $product['rating'] : null,
        'reviews' => (int) ($product['reviews'] ?? 0),
        'image' => $product['image'] ?? null,
        'featured' => !empty($product['featured']) ? 1 : 0,
        'in_stock' => $inStock ? 1 : 0,
        'specs' => json_encode($product['specs'] ?? [], JSON_UNESCAPED_SLASHES),
        'stock_quantity' => $inStock ? 10 : 0,
        'reorder_level' => 5,
    ]);
}

echo 'Upserted ' . count($motherboards) . " motherboard products\n";
