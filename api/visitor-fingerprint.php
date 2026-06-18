<?php
/**
 * Visitor Fingerprint API
 *
 * Receives browser fingerprint data from the client-side JS
 * and updates the corresponding visitor_logs row.
 *
 * POST JSON: { hash, screen, timezone, language, platform, vendor,
 *              webglRenderer, touchPoints, hardwareConcurrency,
 *              fonts, pageUrl, referrer }
 */

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Rate limit: max 1 fingerprint call per session per page load
if (!empty($_SESSION['__fp_sent_' . ($_SERVER['REQUEST_URI'] ?? '')])) {
    echo json_encode(['success' => true, 'message' => 'Already recorded']);
    exit;
}

$logId = (int) ($_SESSION['__visitor_log_id'] ?? 0);
if ($logId <= 0) {
    echo json_encode(['success' => false, 'error' => 'No active visit log']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$fingerprintHash = substr(trim((string) ($data['hash'] ?? '')), 0, 64);
$screen = substr(trim((string) ($data['screen'] ?? '')), 0, 20);
$language = substr(trim((string) ($data['language'] ?? '')), 0, 16);
$pageUrl = substr(trim((string) ($data['pageUrl'] ?? '')), 0, 512);
$referrer = substr(trim((string) ($data['referrer'] ?? '')), 0, 512);

if ($fingerprintHash === '') {
    echo json_encode(['success' => false, 'error' => 'Missing fingerprint hash']);
    exit;
}

try {
    $pdo = db();

    // Update the visitor_logs row with fingerprint data
    $stmt = $pdo->prepare('
        UPDATE visitor_logs
        SET fingerprint_hash = ?,
            screen_resolution = COALESCE(NULLIF(?, \'\'), screen_resolution),
            language = COALESCE(NULLIF(?, \'\'), language),
            page_url = COALESCE(NULLIF(?, \'\'), page_url),
            referrer = COALESCE(NULLIF(?, \'\'), referrer)
        WHERE id = ?
    ');
    $stmt->execute([$fingerprintHash, $screen, $language, $pageUrl, $referrer, $logId]);

    // Mark session so we don't re-process
    $_SESSION['__fp_sent_' . ($_SERVER['REQUEST_URI'] ?? '')] = true;

    echo json_encode(['success' => true, 'message' => 'Fingerprint recorded']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
