<?php
declare(strict_types=1);

/**
 * api/save-chatbot-feedback.php — Logs user thumbs rating (like/dislike) for AI answers.
 */
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$query = trim((string) ($input['query'] ?? ''));
$response = trim((string) ($input['response'] ?? ''));
$rating = (int) ($input['rating'] ?? 0);

if ($query === '' || $response === '' || !in_array($rating, [1, -1], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$clientId = isset($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO ai_chatbot_fine_tuning_logs (admin_id, original_query, bot_response, user_rating)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $query, $response, $rating]);

    echo json_encode(['success' => true, 'message' => 'Feedback saved!']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEV_MODE ? $e->getMessage() : 'Database error while saving feedback'
    ]);
}
