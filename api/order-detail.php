<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$pdo = db();

// Fetch order (verify ownership)
$stmt = $pdo->prepare("
    SELECT * FROM orders
    WHERE id = ? AND client_id = ?
");
$stmt->execute([$orderId, $clientId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Fetch items
$stmt = $pdo->prepare("
    SELECT oi.*, p.image
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch history
$stmt = $pdo->prepare("
    SELECT new_status, changed_at, notes
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY changed_at ASC
");
$stmt->execute([$orderId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['order' => $order, 'items' => $items, 'history' => $history]);
