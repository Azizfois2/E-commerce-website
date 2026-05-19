<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function ensureAfterSalesTables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS after_sales_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_code VARCHAR(32) NOT NULL UNIQUE,
            order_id INT DEFAULT NULL,
            client_id INT DEFAULT NULL,
            customer_name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            request_type ENUM('return','refund','exchange','warranty','repair','missing','damaged') NOT NULL,
            preferred_resolution ENUM('refund','replacement','store_credit','repair','diagnostic') NOT NULL,
            product_name VARCHAR(180) NOT NULL,
            product_condition ENUM('sealed','opened_unused','used','defective','damaged_package','missing_item') NOT NULL,
            package_opened TINYINT(1) NOT NULL DEFAULT 0,
            serial_number VARCHAR(120) DEFAULT NULL,
            reason TEXT NOT NULL,
            status ENUM('submitted','reviewing','approved','awaiting_item','inspecting','resolved','rejected') NOT NULL DEFAULT 'submitted',
            priority ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
            next_action VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_after_sales_order (order_id),
            INDEX idx_after_sales_client (client_id),
            INDEX idx_after_sales_email (email),
            INDEX idx_after_sales_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function cleanText(mixed $value, int $max = 255): string
{
    $text = trim((string) $value);
    $text = preg_replace('/\s+/', ' ', $text);
    return mb_substr($text, 0, $max);
}

function failJson(string $message, int $status = 400): never
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    failJson('Invalid request payload.');
}

$allowedTypes = ['return', 'refund', 'exchange', 'warranty', 'repair', 'missing', 'damaged'];
$allowedResolutions = ['refund', 'replacement', 'store_credit', 'repair', 'diagnostic'];
$allowedConditions = ['sealed', 'opened_unused', 'used', 'defective', 'damaged_package', 'missing_item'];

$orderId = (int) ($input['order_id'] ?? 0);
$customerName = cleanText($input['customer_name'] ?? '', 120);
$email = cleanText($input['email'] ?? '', 160);
$phone = cleanText($input['phone'] ?? '', 40);
$requestType = cleanText($input['request_type'] ?? '', 20);
$resolution = cleanText($input['preferred_resolution'] ?? '', 30);
$productName = cleanText($input['product_name'] ?? '', 180);
$condition = cleanText($input['product_condition'] ?? '', 30);
$serialNumber = cleanText($input['serial_number'] ?? '', 120);
$reason = trim((string) ($input['reason'] ?? ''));
$packageOpened = !empty($input['package_opened']) ? 1 : 0;
$clientId = !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;

if ($orderId <= 0) failJson('Please enter a valid order number.');
if ($customerName === '') failJson('Please enter your full name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) failJson('Please enter a valid email address.');
if (!in_array($requestType, $allowedTypes, true)) failJson('Please choose a valid request type.');
if (!in_array($resolution, $allowedResolutions, true)) failJson('Please choose a valid resolution.');
if ($productName === '') failJson('Please tell us which product needs service.');
if (!in_array($condition, $allowedConditions, true)) failJson('Please choose the current product condition.');
if (mb_strlen($reason) < 20) failJson('Please describe the issue in at least 20 characters.');

$pdo = db();
ensureAfterSalesTables($pdo);

$orderStmt = $pdo->prepare("
    SELECT o.id, o.client_id, o.status, o.payment_status, o.created_at, c.email, c.nom
    FROM orders o
    JOIN Client c ON c.id_client = o.client_id
    WHERE o.id = ?
      AND (c.email = ? OR o.client_id = ?)
    LIMIT 1
");
$orderStmt->execute([$orderId, $email, $clientId ?: 0]);
$order = $orderStmt->fetch();

if (!$order) {
    failJson('We could not match that order number with this email/account. Check your order number or sign in first.', 404);
}

$daysSinceOrder = max(0, (int) floor((time() - strtotime((string) $order['created_at'])) / 86400));
$isReturnLike = in_array($requestType, ['return', 'refund', 'exchange'], true);
$isWarrantyLike = in_array($requestType, ['warranty', 'repair'], true);

if ($isReturnLike && $daysSinceOrder > 14) {
    failJson('This order is outside the 14-day return window. Please choose warranty or repair service instead.');
}

if ($isReturnLike && $order['status'] === 'cancelled') {
    failJson('This order is cancelled already. Contact support if a payment refund is still pending.');
}

$priority = in_array($requestType, ['damaged', 'missing'], true) ? 'urgent' : 'normal';
$nextAction = match ($requestType) {
    'damaged', 'missing' => 'Upload photos to support@marocpc.com and keep all packaging until triage is complete.',
    'warranty', 'repair' => 'Our technician will confirm serial number, symptoms, and warranty route before intake.',
    default => 'Keep the product complete with accessories. We will confirm eligibility before return drop-off.',
};

if ($isWarrantyLike && $serialNumber === '') {
    $nextAction = 'Send the serial number or product label photo to support@marocpc.com so we can validate warranty coverage.';
}

$ticketCode = 'RMA-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

$stmt = $pdo->prepare("
    INSERT INTO after_sales_requests
      (ticket_code, order_id, client_id, customer_name, email, phone, request_type, preferred_resolution,
       product_name, product_condition, package_opened, serial_number, reason, priority, next_action)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $ticketCode,
    $orderId,
    (int) $order['client_id'],
    $customerName,
    $email,
    $phone ?: null,
    $requestType,
    $resolution,
    $productName,
    $condition,
    $packageOpened,
    $serialNumber ?: null,
    mb_substr($reason, 0, 2000),
    $priority,
    $nextAction,
]);

$eta = $priority === 'urgent' ? 'within 1 business day' : 'within 2 business days';

echo json_encode([
    'success' => true,
    'ticket' => $ticketCode,
    'status' => 'submitted',
    'priority' => $priority,
    'eta' => $eta,
    'next_action' => $nextAction,
    'message' => "After-sales request {$ticketCode} submitted. Our service desk will reply {$eta}.",
], JSON_UNESCAPED_UNICODE);
