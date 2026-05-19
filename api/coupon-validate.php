<?php
require_once __DIR__ . '/../admin-helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim((string) ($input['code'] ?? ''));
$subtotal = max(0, (float) ($input['subtotal'] ?? 0));

try {
    $pdo = db();
    $result = adminCouponDiscount($pdo, $code, $subtotal);
    if (!$result['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Invalid code']);
        exit;
    }
    echo json_encode(['success' => true] + $result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => DEV_MODE ? $e->getMessage() : 'Coupon validation failed']);
}
