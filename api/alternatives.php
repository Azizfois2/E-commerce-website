<?php
/**
 * api/alternatives.php
 * Returns up to 3 in-stock alternatives from the same category.
 * GET ?category=gpu&exclude_id=42
 */
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$category   = trim($_GET['category']   ?? '');
$excludeId  = (int)($_GET['exclude_id'] ?? 0);

if (!$category) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing category']);
    exit;
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            brand,
            category,
            price,
            old_price AS oldPrice,
            badge,
            rating,
            reviews,
            image,
            stock_quantity AS quantity,
            in_stock
        FROM products
        WHERE category = :cat
          AND id != :excl
          AND in_stock = 1
          AND stock_quantity > 0
        ORDER BY rating DESC, price ASC
        LIMIT 6
    ");
    $stmt->execute([':cat' => $category, ':excl' => $excludeId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'alternatives' => $rows]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
