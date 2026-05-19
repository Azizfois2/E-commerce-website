<?php
/**
 * api/admin-diagnostics.php — Upload Hardware Diagnostic Reports
 *
 * POST { action: "upload", order_id: 123, component_type: "gpu", test_name: "TimeSpy", score: 18000, max_temp_c: 75, result_details: {...} }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure admin/technician access
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$technicianId = (int)$_SESSION['admin_id'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'upload') {
        $orderId = (int)($input['order_id'] ?? 0);
        $componentType = trim($input['component_type'] ?? 'system');
        $testName = trim($input['test_name'] ?? '');
        $score = (int)($input['score'] ?? 0);
        $maxTemp = (int)($input['max_temp_c'] ?? 0);
        $details = $input['result_details'] ?? [];

        if ($orderId <= 0 || empty($testName)) {
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO hardware_diagnostic_reports (order_id, technician_id, component_type, test_name, score, max_temp_c, result_details)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId, 
            $technicianId, 
            $componentType, 
            $testName, 
            $score, 
            $maxTemp, 
            json_encode($details)
        ]);

        echo json_encode(['success' => true, 'report_id' => $pdo->lastInsertId()]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
