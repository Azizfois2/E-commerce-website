<?php
/**
 * api/custom-builds.php — Serve Custom Build Presets
 *
 * GET  ?action=presets
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'presets';

    if ($action === 'presets') {
        $stmt = $pdo->query("SELECT * FROM custom_build_presets WHERE is_active = 1");
        $presets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($presets as &$preset) {
            $preset['products_json'] = json_decode($preset['products_json'], true) ?: [];
        }

        echo json_encode(['success' => true, 'presets' => $presets]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
