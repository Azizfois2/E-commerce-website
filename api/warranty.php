<?php
/**
 * api/warranty.php — Warranty Extensions
 *
 * GET  ?action=list
 * POST { action: "purchase", order_id: 123, product_name: "GPU", extension_months: 24, amount_paid: 500 }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$clientId = $_SESSION['client_id'] ?? null;

if (empty($clientId)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM warranty_extensions WHERE client_id = ? ORDER BY ends_at DESC");
        $stmt->execute([$clientId]);
        echo json_encode(['success' => true, 'warranties' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'purchase') {
        $orderId = (int)($input['order_id'] ?? 0);
        $productName = trim($input['product_name'] ?? '');
        $months = (int)($input['extension_months'] ?? 24);
        $amount = (float)($input['amount_paid'] ?? 0);

        if ($orderId <= 0 || empty($productName) || $months <= 0) {
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }

        // Verify order ownership
        $stmt = $pdo->prepare("SELECT id, created_at FROM orders WHERE id = ? AND client_id = ?");
        $stmt->execute([$orderId, $clientId]);
        $order = $stmt->fetch();
        if (!$order) {
            echo json_encode(['error' => 'Unauthorized order access']);
            exit;
        }

        // Assuming warranty starts 1 year after order creation (standard warranty end)
        $startsAt = date('Y-m-d', strtotime($order['created_at'] . ' + 1 year'));
        $endsAt = date('Y-m-d', strtotime($startsAt . " + $months months"));

        $stmt = $pdo->prepare("
            INSERT INTO warranty_extensions (client_id, order_id, product_name, extension_months, amount_paid, starts_at, ends_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientId, $orderId, $productName, $months, $amount, $startsAt, $endsAt]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
