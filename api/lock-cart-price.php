<?php
/**
 * api/lock-cart-price.php
 * Saves cart items and current prices to a price lock table when a user abandons the cart.
 * Guarantees the price for 24 hours.
 */
require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No items provided']);
    exit;
}

try {
    $pdo = db();
    
    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart_price_locks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            user_id INT NULL,
            locked_total DECIMAL(10, 2) NOT NULL,
            items_json TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $sessionId = session_id() ?: bin2hex(random_bytes(16));
    $userId = $_SESSION['user_id'] ?? null;
    $total = $input['total'] ?? 0;
    
    // Lock for 24 hours
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("
        INSERT INTO cart_price_locks (session_id, user_id, locked_total, items_json, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $sessionId,
        $userId,
        $total,
        json_encode($input['items']),
        $expiresAt
    ]);

    echo json_encode(['success' => true, 'message' => 'Price locked for 24 hours']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
