<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SRC_PATH . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'price-alerts.php';

header('Content-Type: application/json');

$pdo = db();
ensurePriceAlertsTable($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['action'] ?? '') === 'process') {
        if (empty($_SESSION['admin_id']) && !DEV_MODE) {
            http_response_code(403);
            jsonResponse(false, 'Admin access required.');
        }
        $result = processDuePriceAlerts($pdo);
        jsonResponse(true, 'Price alerts processed.', $result);
    }

    if (empty($_SESSION['client_id'])) {
        jsonResponse(false, 'Login required.');
    }

    $stmt = $pdo->prepare("
        SELECT pa.id, pa.product_id, pa.threshold, pa.channel, pa.status, pa.created_at, pa.triggered_at,
               p.name, p.price, p.image
        FROM price_alerts pa
        JOIN products p ON p.id = pa.product_id
        WHERE pa.client_id = ?
        ORDER BY pa.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([(int) $_SESSION['client_id']]);
    jsonResponse(true, 'Alerts loaded.', ['alerts' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (empty($_SESSION['client_id'])) {
        jsonResponse(false, 'Login required.');
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $alertId = (int) ($input['id'] ?? 0);
    if ($alertId <= 0) {
        jsonResponse(false, 'Alert ID required.');
    }
    $stmt = $pdo->prepare("UPDATE price_alerts SET status = 'cancelled' WHERE id = ? AND client_id = ?");
    $stmt->execute([$alertId, (int) $_SESSION['client_id']]);
    jsonResponse(true, 'Alert cancelled.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$productId = (int) ($input['product_id'] ?? 0);
$threshold = (float) ($input['threshold'] ?? 0);
$channel = (string) ($input['channel'] ?? 'email');
$email = trim((string) ($input['email'] ?? ''));
$phone = normalizeAlertPhone((string) ($input['phone'] ?? ''));
$clientId = !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;

if (!in_array($channel, ['email', 'whatsapp', 'both'], true)) {
    jsonResponse(false, 'Invalid alert channel.');
}
if ($productId <= 0 || $threshold <= 0) {
    jsonResponse(false, 'Product and threshold are required.');
}

$productStmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
$productStmt->execute([$productId]);
$product = $productStmt->fetch();
if (!$product) {
    jsonResponse(false, 'Product not found.');
}

if ($clientId !== null) {
    $clientStmt = $pdo->prepare("SELECT email, telephone FROM Client WHERE id_client = ?");
    $clientStmt->execute([$clientId]);
    $client = $clientStmt->fetch() ?: [];
    if ($email === '') {
        $email = (string) ($client['email'] ?? '');
    }
    if ($phone === '') {
        $phone = normalizeAlertPhone((string) ($client['telephone'] ?? ''));
    }
}

if (($channel === 'email' || $channel === 'both') && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'A valid email is required for email alerts.');
}
if (($channel === 'whatsapp' || $channel === 'both') && $phone === '') {
    jsonResponse(false, 'A valid WhatsApp phone number is required.');
}

$stmt = $pdo->prepare("
    INSERT INTO price_alerts (client_id, product_id, email, phone, channel, threshold)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$clientId, $productId, $email ?: null, $phone ?: null, $channel, $threshold]);

jsonResponse(true, 'Price alert created.', [
    'alert_id' => (int) $pdo->lastInsertId(),
    'product_name' => $product['name'],
    'current_price' => (float) $product['price'],
    'threshold' => $threshold,
]);
