<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

function ensureLaptopPriceAlertsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS laptop_price_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT DEFAULT NULL,
            laptop_id INT NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(32) DEFAULT NULL,
            channel ENUM('email','whatsapp','both') NOT NULL DEFAULT 'email',
            threshold DECIMAL(10,2) NOT NULL,
            status ENUM('active','triggered','cancelled') NOT NULL DEFAULT 'active',
            last_notified_at DATETIME DEFAULT NULL,
            triggered_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL,
            FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE,
            INDEX idx_laptop_alert_status (laptop_id, status),
            INDEX idx_laptop_alert_client_status (client_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

$pdo = db();
ensureLaptopPriceAlertsTable($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$laptopId = (int) ($input['laptop_id'] ?? 0);
$threshold = (float) ($input['threshold'] ?? 0);
$channel = (string) ($input['channel'] ?? 'email');
$email = trim((string) ($input['email'] ?? ''));
$clientId = !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;

if (!in_array($channel, ['email', 'whatsapp', 'both'], true)) {
    jsonResponse(false, 'Invalid alert channel.');
}
if ($laptopId <= 0 || $threshold <= 0) {
    jsonResponse(false, 'Laptop and threshold are required.');
}
if (($channel === 'email' || $channel === 'both') && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'A valid email is required for email alerts.');
}

$stmt = $pdo->prepare('SELECT id, name, price FROM laptops WHERE id = ?');
$stmt->execute([$laptopId]);
$laptop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$laptop) {
    jsonResponse(false, 'Laptop not found.');
}

$stmt = $pdo->prepare("
    INSERT INTO laptop_price_alerts (client_id, laptop_id, email, channel, threshold)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$clientId, $laptopId, $email !== '' ? $email : null, $channel, $threshold]);

jsonResponse(true, 'Laptop price alert created.', [
    'alert_id' => (int) $pdo->lastInsertId(),
    'laptop_name' => $laptop['name'],
    'current_price' => (float) $laptop['price'],
    'threshold' => $threshold,
]);
