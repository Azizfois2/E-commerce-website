<?php
/**
 * api/chatbot.php — Store and fetch chatbot conversations
 *
 * GET  ?session_id=XYZ
 * POST { action: "save", session_id: "XYZ", message_log: [...] }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$clientId = $_SESSION['client_id'] ?? null;

if ($method === 'GET') {
    $sessionId = $_GET['session_id'] ?? '';
    if (empty($sessionId)) {
        echo json_encode(['error' => 'Missing session_id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT message_log FROM chatbot_conversations WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'log' => json_decode($row['message_log'], true)]);
    } else {
        echo json_encode(['success' => true, 'log' => []]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'save';

    if ($action === 'save') {
        $sessionId = trim($input['session_id'] ?? '');
        $log = $input['message_log'] ?? [];

        if (empty($sessionId) || empty($log)) {
            echo json_encode(['error' => 'Missing data']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO chatbot_conversations (session_id, client_id, message_log)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE message_log = VALUES(message_log)
        ");
        $stmt->execute([$sessionId, $clientId, json_encode($log)]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
