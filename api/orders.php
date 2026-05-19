<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$pdo = db();

// List orders with item count
$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.status,
        o.total,
        o.payment_method,
        o.payment_status,
        o.estimated_delivery,
        o.created_at,
        COUNT(oi.id) AS item_count,
        GROUP_CONCAT(
            CONCAT_WS('@@',
                REPLACE(COALESCE(oi.name_at_time, 'Product'), '||', ' '),
                COALESCE(p.image, ''),
                COALESCE(oi.quantity, 1)
            )
            ORDER BY oi.id ASC
            SEPARATOR '||'
        ) AS items_preview
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE o.client_id = ?
    GROUP BY o.id, o.status, o.total, o.payment_method, o.payment_status, o.estimated_delivery, o.created_at
    ORDER BY o.created_at DESC
");
$stmt->execute([$clientId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['orders' => $orders]);
