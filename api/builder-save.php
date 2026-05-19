<?php
/**
 * api/builder-save.php — Save, load, and share PC builds
 * 
 * GET  ?code=XXXX        → Load a shared build
 * GET  ?my=1             → Load user's saved builds (auth required)
 * POST { action: "save", build_name, use_case, components, total_price, total_wattage }
 */
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');
$pdo = db();
ensureSavedBuildsTable($pdo);

// ── GET: Load build by share token or list user builds ────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load specific build
    if (!empty($_GET['code'])) {
        $token = trim($_GET['code']);
        $stmt = $pdo->prepare("SELECT * FROM user_build_snapshots WHERE share_token = ?");
        $stmt->execute([$token]);
        $build = $stmt->fetch();
        if (!$build) {
            jsonResponse(false, 'Build not found.');
        }
        $build['components'] = json_decode($build['config_data'], true);
        $build['share_code'] = $build['share_token'];
        jsonResponse(true, 'Build loaded.', ['build' => $build]);
    }

    // List user's builds
    if (!empty($_GET['my'])) {
        if (empty($_SESSION['client_id'])) {
            jsonResponse(false, 'Login required.');
        }
        $stmt = $pdo->prepare("SELECT share_token as share_code, build_name, total_price, created_at FROM user_build_snapshots WHERE client_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([(int)$_SESSION['client_id']]);
        jsonResponse(true, 'Builds loaded.', ['builds' => $stmt->fetchAll()]);
    }

    jsonResponse(false, 'Missing parameters.');
}

// ── POST: Save a build ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request body.');
}

$action = $input['action'] ?? 'save';

if ($action === 'save') {
    $components = $input['components'] ?? [];
    if (empty($components)) {
        jsonResponse(false, 'No components selected.');
    }

    // Embed extra metadata into config
    $configData = [
        'components' => $components,
        'use_case' => $input['use_case'] ?? 'general',
        'total_wattage' => (int)($input['total_wattage'] ?? 0)
    ];

    // Generate unique share token
    $shareToken = bin2hex(random_bytes(16)); // 32 chars

    $clientId = !empty($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO user_build_snapshots (share_token, client_id, build_name, config_data, total_price)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $shareToken,
        $clientId,
        trim($input['build_name'] ?? 'My Build'),
        json_encode($configData),
        (float)($input['total_price'] ?? 0)
    ]);

    jsonResponse(true, 'Build saved!', [
        'share_code' => $shareToken
    ]);
}

if ($action === 'delete') {
    if (empty($_SESSION['client_id'])) {
        jsonResponse(false, 'Login required.');
    }
    $token = trim($input['share_code'] ?? '');
    $stmt = $pdo->prepare("DELETE FROM user_build_snapshots WHERE share_token = ? AND client_id = ?");
    $stmt->execute([$token, (int)$_SESSION['client_id']]);
    jsonResponse(true, 'Build deleted.');
}

jsonResponse(false, 'Unknown action.');
