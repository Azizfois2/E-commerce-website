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
    // Sync / Save Cart
    $input = json_decode(file_get_contents('php://input'), true);
    $cartItems = $input['cart'] ?? [];

    if (!is_array($cartItems)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid cart data']);
        exit;
    }

    if (empty($cartItems)) {
        // If cart is empty, we can delete the abandoned cart entry
        $stmt = $pdo->prepare("DELETE FROM abandoned_carts WHERE client_id = ?");
        $stmt->execute([$clientId]);
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        exit;
    }

    // Resolve current DB prices for these items to lock them in
    $productIds = [];
    foreach ($cartItems as $item) {
        if (isset($item['id'])) {
            $productIds[] = (int)$item['id'];
        }
    }

    $lockedCart = [];
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $products[(int)$row['id']] = $row;
        }

        foreach ($cartItems as $item) {
            $pid = (int)($item['id'] ?? 0);
            if (isset($products[$pid])) {
                $lockedCart[] = [
                    'id' => $pid,
                    'name' => $products[$pid]['name'],
                    'price' => (float)$products[$pid]['price'], // lock the price
                    'image' => $products[$pid]['image'],
                    'quantity' => (int)($item['quantity'] ?? 1)
                ];
            }
        }
    }

    // Insert or update abandoned cart entry
    // Since unique key is client_id, we can use ON DUPLICATE KEY UPDATE
    $cartJson = json_encode($lockedCart);
    $stmt = $pdo->prepare("
        INSERT INTO abandoned_carts (client_id, cart_data, locked_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE cart_data = VALUES(cart_data), locked_at = NOW()
    ");
    $stmt->execute([$clientId, $cartJson]);

    echo json_encode([
        'success' => true,
        'message' => 'Cart synced and prices locked for 48 hours.',
        'locked_at' => date('c'),
        'expires_at' => date('c', strtotime('+48 hours'))
    ]);
    exit;

} elseif ($method === 'GET') {
    // Fetch Locked Cart
    $stmt = $pdo->prepare("
        SELECT cart_data, locked_at 
        FROM abandoned_carts 
        WHERE client_id = ?
    ");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => true, 'has_locked' => false]);
        exit;
    }

    $lockedAt = strtotime($row['locked_at']);
    $expiresAt = $lockedAt + (48 * 3600);
    $now = time();
    $isActive = $now < $expiresAt;

    echo json_encode([
        'success' => true,
        'has_locked' => true,
        'is_active' => $isActive,
        'locked_at' => date('c', $lockedAt),
        'expires_at' => date('c', $expiresAt),
        'seconds_remaining' => max(0, $expiresAt - $now),
        'cart' => json_decode($row['cart_data'], true)
    ]);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
