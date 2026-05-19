<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // File new Maintenance Request / RMA
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $orderId = (int)($input['order_id'] ?? 0);
        $productName = trim($input['product_name'] ?? '');
        $issueType = trim($input['issue_type'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if ($orderId <= 0 || empty($productName) || empty($issueType)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
            exit;
        }

        // Verify order ownership
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND client_id = ?");
        $stmt->execute([$orderId, $clientId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid order details.']);
            exit;
        }

        // Insert maintenance request
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_requests (client_id, order_id, product_name, issue_type, notes, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$clientId, $orderId, $productName, $issueType, $notes]);

        echo json_encode([
            'success' => true,
            'message' => 'Maintenance and RMA request filed successfully! Our technicians will review it shortly.'
        ]);
        exit;
    }

    if ($action === 'reply') {
        $requestId = (int)($input['request_id'] ?? 0);
        $message = trim($input['message'] ?? '');

        if ($requestId <= 0 || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Invalid message data.']);
            exit;
        }

        // Verify request ownership
        $stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE id = ? AND client_id = ?");
        $stmt->execute([$requestId, $clientId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized request access.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO maintenance_ticket_replies (request_id, sender_type, sender_id, message) VALUES (?, 'client', ?, ?)");
        $stmt->execute([$requestId, $clientId, $message]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;

} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Retrieve past requests with shipping labels
        $stmt = $pdo->prepare("
            SELECT mr.id, mr.order_id, mr.product_name, mr.issue_type, mr.notes, mr.status, mr.created_at,
                   sl.tracking_number, sl.carrier, sl.status as shipping_status
            FROM maintenance_requests mr
            LEFT JOIN rma_shipping_labels sl ON sl.request_id = mr.id
            WHERE mr.client_id = ?
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([$clientId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
        exit;
    }

    if ($action === 'replies') {
        $requestId = (int)($_GET['request_id'] ?? 0);

        // Verify request ownership
        $stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE id = ? AND client_id = ?");
        $stmt->execute([$requestId, $clientId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized request access.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT sender_type, message, sent_at FROM maintenance_ticket_replies WHERE request_id = ? ORDER BY sent_at ASC");
        $stmt->execute([$requestId]);
        echo json_encode(['success' => true, 'replies' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
