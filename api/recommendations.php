<?php
/**
 * api/recommendations.php — Log AI Product Recommendations
 *
 * POST { action: "log", product_id: 123, recommendation_score: 0.95, context_trigger: "cart_view" }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$clientId = $_SESSION['client_id'] ?? null;

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'log') {
        $productId = (int)($input['product_id'] ?? 0);
        $score = (float)($input['recommendation_score'] ?? 0);
        $context = trim($input['context_trigger'] ?? 'system');

        if ($productId <= 0 || $score <= 0) {
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO ai_product_recommendations (client_id, product_id, recommendation_score, context_trigger)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$clientId, $productId, $score, $context]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
