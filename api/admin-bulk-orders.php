<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../admin-helpers.php';
require_once '../inventory-helpers.php';

header('Content-Type: application/json');

adminRequireJsonAuth();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim((string) ($input['action'] ?? ''));
$orderIds = array_filter(array_map('intval', (array) ($input['order_ids'] ?? [])));

if ($orderIds === []) {
    http_response_code(400);
    echo json_encode(['error' => 'No orders selected']);
    exit;
}

$pdo = db();
adminEnsureAdminSuiteTables($pdo);
inventoryEnsureOrderStockColumn($pdo);

$allowedStatuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
$results = [];

function adminBulkOrderErrorMessage(Throwable $e): string
{
    error_log('[admin-bulk-orders] ' . $e->getMessage());

    if ($e instanceof RuntimeException) {
        return $e->getMessage();
    }

    return (defined('DEV_MODE') && DEV_MODE) ? $e->getMessage() : 'Database error';
}

if ($action === 'set_status') {
    $newStatus = trim((string) ($input['status'] ?? ''));
    if (!in_array($newStatus, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }

    foreach ($orderIds as $orderId) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status, stock_reserved FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $pdo->rollBack();
                $results[] = ['order_id' => $orderId, 'success' => false, 'error' => 'Not found'];
                continue;
            }

            $oldStatus = (string) $order['status'];
            if ($oldStatus !== $newStatus) {
                inventorySyncOrderStockForStatus($pdo, $orderId, $oldStatus, $newStatus, !empty($order['stock_reserved']));
                $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);
                $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)')
                    ->execute([$orderId, $oldStatus, $newStatus, 'admin']);
                adminLogActivity($pdo, 'status', 'order', $orderId, "Order #{$orderId} changed from {$oldStatus} to {$newStatus}");
            }
            $pdo->commit();
            $results[] = ['order_id' => $orderId, 'success' => true, 'status' => $newStatus];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[] = ['order_id' => $orderId, 'success' => false, 'error' => adminBulkOrderErrorMessage($e)];
        }
    }
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

if ($action === 'suppress') {
    foreach ($orderIds as $orderId) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status, stock_reserved FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $pdo->rollBack();
                $results[] = ['order_id' => $orderId, 'success' => false, 'error' => 'Not found'];
                continue;
            }

            if (!empty($order['stock_reserved'])) {
                inventoryRestoreOrderStock($pdo, $orderId);
            }
            $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$orderId]);
            adminLogActivity($pdo, 'delete', 'order', $orderId, "Deleted order #{$orderId}");
            $pdo->commit();
            $results[] = ['order_id' => $orderId, 'success' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[] = ['order_id' => $orderId, 'success' => false, 'error' => adminBulkOrderErrorMessage($e)];
        }
    }
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
