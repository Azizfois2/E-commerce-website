<?php
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($input['product_id'] ?? 0);
    $email = trim($input['email'] ?? '');
    $clientId = $_SESSION['client_id'] ?? null;

    if (!$productId || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid product or email address']);
        exit;
    }

    try {
        // Check if already subscribed
        $stmt = $pdo->prepare("SELECT id FROM restock_notifications WHERE product_id = ? AND email = ? AND notified = 0");
        $stmt->execute([$productId, $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => true, 'message' => 'You are already subscribed to notifications for this product.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO restock_notifications (product_id, email, client_id) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $email, $clientId]);

        echo json_encode(['success' => true, 'message' => 'You will be notified when this item is back in stock!']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}
