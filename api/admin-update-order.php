<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../admin-helpers.php';
require_once '../inventory-helpers.php';

header('Content-Type: application/json');

// Admin
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim((string) ($input['action'] ?? ''));
$orderId = (int) ($input['order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$pdo = db();
adminEnsureAdminSuiteTables($pdo);
inventoryEnsureOrderStockColumn($pdo);

//Supprimer
if ($action === 'suppress') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT status, stock_reserved FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        if (!empty($order['stock_reserved'])) {
            inventoryRestoreOrderStock($pdo, $orderId);
        }

        $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
        $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$orderId]);
        adminLogActivity($pdo, 'delete', 'order', $orderId, "Deleted order #{$orderId}");
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

//Mettre a jour
if ($action === 'set_status') {
    $allowed = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
    $newStatus = trim((string) ($input['status'] ?? ''));

    if (!in_array($newStatus, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT status, stock_reserved FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
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
        echo json_encode(['success' => true, 'status' => $newStatus]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Mettre a jour assembly status
if ($action === 'set_assembly_status') {
    $allowed = ['not_applicable', 'gathering_parts', 'building', 'testing', 'qc_passed', 'ready'];
    $newStatus = trim((string) ($input['assembly_status'] ?? ''));

    if (!in_array($newStatus, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid assembly status']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT assembly_status FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        $oldStatus = (string) $order['assembly_status'];

        if ($oldStatus !== $newStatus) {
            $pdo->prepare('UPDATE orders SET assembly_status = ? WHERE id = ?')->execute([$newStatus, $orderId]);
            $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)')
                ->execute([$orderId, $oldStatus, $newStatus, 'admin', "Assembly status updated to {$newStatus}"]);
            adminLogActivity($pdo, 'status', 'order', $orderId, "Order #{$orderId} assembly status changed from {$oldStatus} to {$newStatus}");
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'assembly_status' => $newStatus]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

if ($action === 'add_note') {
    $note = trim((string) ($input['note'] ?? ''));
    if ($note === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Note is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)')
            ->execute([$orderId, $status, $status, 'admin', $note]);
        adminLogActivity($pdo, 'note', 'order', $orderId, "Added note to order #{$orderId}");
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
