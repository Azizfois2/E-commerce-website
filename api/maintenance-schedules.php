<?php
/**
 * api/maintenance-schedules.php — Preventative Maintenance Schedules
 *
 * GET  ?action=list
 * POST { action: "create", product_name: "Gaming PC Build", next_recommended_service: "2026-11-19" }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$clientId = $_SESSION['client_id'] ?? null;

if (empty($clientId)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM preventative_maintenance_schedules WHERE client_id = ? ORDER BY next_recommended_service ASC");
        $stmt->execute([$clientId]);
        echo json_encode(['success' => true, 'schedules' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // Typically this would be triggered internally when an order is completed,
    // but exposing for testing/admin purposes
    if ($action === 'create') {
        $productName = trim($input['product_name'] ?? '');
        $nextService = trim($input['next_recommended_service'] ?? '');

        if (empty($productName) || empty($nextService)) {
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO preventative_maintenance_schedules (client_id, product_name, next_recommended_service)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$clientId, $productName, $nextService]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
