<?php
/**
 * api/flash-sales.php — Public API to fetch active flash sales.
 *
 * GET  → returns all currently active flash sales with product info
 */
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = db();

try {
    $stmt = $pdo->prepare("
        SELECT 
            fs.id,
            fs.product_id,
            fs.sale_price,
            fs.original_price,
            fs.max_quantity,
            fs.sold_count,
            fs.starts_at,
            fs.ends_at,
            p.name AS product_name,
            p.image AS product_image,
            p.brand AS product_brand,
            p.category AS product_category,
            p.rating AS product_rating,
            p.reviews AS product_reviews,
            p.stock_quantity
        FROM flash_sales fs
        JOIN products p ON p.id = fs.product_id
        WHERE fs.starts_at <= NOW()
          AND fs.ends_at > NOW()
          AND (fs.max_quantity IS NULL OR fs.sold_count < fs.max_quantity)
        ORDER BY fs.ends_at ASC
    ");
    $stmt->execute();
    $sales = $stmt->fetchAll();

    // Calculate remaining quantities and discount percentages
    foreach ($sales as &$sale) {
        $sale['discount_pct'] = round((($sale['original_price'] - $sale['sale_price']) / $sale['original_price']) * 100);
        $sale['remaining'] = $sale['max_quantity'] !== null
            ? max(0, (int) $sale['max_quantity'] - (int) $sale['sold_count'])
            : null;
    }

    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'server_time' => date('c')
    ]);
} catch (PDOException $e) {
    // Table might not exist yet
    echo json_encode([
        'success' => true,
        'sales' => [],
        'server_time' => date('c'),
        'note' => 'Flash sales table not available'
    ]);
}
