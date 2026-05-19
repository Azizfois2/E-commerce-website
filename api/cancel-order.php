<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../inventory-helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($input['order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$pdo = db();
inventoryEnsureOrderStockColumn($pdo);

try {
    $pdo->beginTransaction();

    // Verify order ownership and current status
    $stmt = $pdo->prepare("SELECT status, stock_reserved FROM orders WHERE id = ? AND client_id = ? FOR UPDATE");
    $stmt->execute([$orderId, $clientId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    if (!in_array($order['status'], ['pending', 'processing'])) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Order cannot be cancelled in its current state (' . $order['status'] . ')']);
        exit;
    }

    if (!empty($order['stock_reserved'])) {
        inventoryRestoreOrderStock($pdo, $orderId);
    }

    // Update status to cancelled
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $updateStmt->execute([$orderId]);
    $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)')
        ->execute([$orderId, $order['status'], 'cancelled', 'customer']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => DEV_MODE ? $e->getMessage() : 'An error occurred while cancelling the order.'
    ]);
}
