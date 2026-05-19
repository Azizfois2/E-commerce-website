<?php
/**
 * api/search-log.php — Logs user search queries into user_search_intent_logs.
 *
 * POST body: { "query": "rtx 4090", "results_count": 3, "clicked_product_id": null }
 */
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$query = trim((string) ($input['query'] ?? ''));
$resultsCount = (int) ($input['results_count'] ?? 0);
$clickedProductId = isset($input['clicked_product_id']) && $input['clicked_product_id'] !== null
    ? (int) $input['clicked_product_id']
    : null;

if ($query === '' || mb_strlen($query) < 2) {
    echo json_encode(['success' => false, 'error' => 'Query too short']);
    exit;
}

$clientId = !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;

try {
    $pdo = db();
    $stmt = $pdo->prepare('
        INSERT INTO user_search_intent_logs (client_id, search_query, results_returned, clicked_product_id)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$clientId, mb_substr($query, 0, 255), $resultsCount, $clickedProductId]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    // Search logging should never fail visibly
    echo json_encode(['success' => true]);
}
