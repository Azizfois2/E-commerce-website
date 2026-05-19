<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/rate-limiter.php';
header('Content-Type: application/json');

$pdo = db();
$clientId = $_SESSION['client_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Helper to get user's wishlist
function getUserWishlist($pdo, $clientId) {
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE client_id = ?");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Helper to get detailed wishlist products for the account page
function getDetailedWishlist($pdo, $clientId) {
    $stmt = $pdo->prepare("
        SELECT p.*, w.added_at, a.target_price, a.alert_by_email, a.alert_by_whatsapp
        FROM wishlist w
        JOIN products p ON p.id = w.product_id
        LEFT JOIN product_wishlist_alerts a ON a.client_id = w.client_id AND a.product_id = w.product_id
        WHERE w.client_id = ?
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($method === 'GET') {
    if (!$clientId) {
        echo json_encode(['success' => true, 'wishlist' => []]);
        exit;
    }
    
    $details = $_GET['details'] ?? 'false';
    if ($details === 'true') {
        echo json_encode(['success' => true, 'products' => getDetailedWishlist($pdo, $clientId)]);
    } else {
        echo json_encode(['success' => true, 'wishlist' => getUserWishlist($pdo, $clientId)]);
    }
    exit;
}

if ($method === 'POST') {
    if (!$clientId) {
        echo json_encode(['error' => 'Must be logged in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'sync') {
        $localWishlist = $input['localWishlist'] ?? [];
        if (is_array($localWishlist)) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (client_id, product_id) VALUES (?, ?)");
            foreach ($localWishlist as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) $stmt->execute([$clientId, $pid]);
            }
        }
        echo json_encode(['success' => true, 'wishlist' => getUserWishlist($pdo, $clientId)]);
        exit;
    }

    if ($action === 'toggle') {
        $productId = (int)($input['product_id'] ?? 0);
        if (!$productId) {
            echo json_encode(['error' => 'Invalid product_id']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE client_id = ? AND product_id = ?");
        $stmt->execute([$clientId, $productId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $pdo->prepare("DELETE FROM wishlist WHERE id = ?")->execute([$exists['id']]);
            $status = 'removed';
        } else {
            $pdo->prepare("INSERT INTO wishlist (client_id, product_id) VALUES (?, ?)")->execute([$clientId, $productId]);
            $status = 'added';
        }

        echo json_encode(['success' => true, 'status' => $status, 'wishlist' => getUserWishlist($pdo, $clientId)]);
        exit;
    }

    if ($action === 'set_alert') {
        $productId = (int)($input['product_id'] ?? 0);
        $targetPrice = isset($input['target_price']) ? (float)$input['target_price'] : null;
        $email = !empty($input['alert_email']) ? 1 : 0;
        $whatsapp = !empty($input['alert_whatsapp']) ? 1 : 0;

        if (!$productId) {
            echo json_encode(['error' => 'Invalid product_id']);
            exit;
        }

        // Make sure it's in their wishlist first
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (client_id, product_id) VALUES (?, ?)");
        $stmt->execute([$clientId, $productId]);

        if ($targetPrice === null || $targetPrice <= 0) {
            // Remove alert
            $pdo->prepare("DELETE FROM product_wishlist_alerts WHERE client_id = ? AND product_id = ?")->execute([$clientId, $productId]);
            echo json_encode(['success' => true, 'status' => 'removed']);
        } else {
            // Check if alert exists
            $stmt = $pdo->prepare("SELECT id FROM product_wishlist_alerts WHERE client_id = ? AND product_id = ?");
            $stmt->execute([$clientId, $productId]);
            $existingAlert = $stmt->fetch();

            if ($existingAlert) {
                $stmt = $pdo->prepare("UPDATE product_wishlist_alerts SET target_price = ?, alert_by_email = ?, alert_by_whatsapp = ? WHERE id = ?");
                $stmt->execute([$targetPrice, $email, $whatsapp, $existingAlert['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO product_wishlist_alerts (client_id, product_id, target_price, alert_by_email, alert_by_whatsapp) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$clientId, $productId, $targetPrice, $email, $whatsapp]);
            }
            echo json_encode(['success' => true, 'status' => 'set']);
        }
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    exit;
}
