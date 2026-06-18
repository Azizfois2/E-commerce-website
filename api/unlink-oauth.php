<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!csrfVerify($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$provider = strtolower(trim($input['provider'] ?? ''));

// Validate provider
$validProviders = ['google', 'facebook', 'discord', 'steam'];
if (!in_array($provider, $validProviders, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid provider']);
    exit;
}

// Map provider to column name
$columnMap = [
    'google' => 'google_id',
    'facebook' => 'facebook_id',
    'discord' => 'discord_id',
    'steam' => 'steam_id'
];

$column = $columnMap[$provider];
$clientId = (int) $_SESSION['client_id'];

try {
    $pdo = db();
    ensureSteamOAuthColumn($pdo);
    
    // Check if the account has a password set
    // Don't allow unlinking if it's the only login method
    $stmt = $pdo->prepare("SELECT mot_de_passe, google_id, facebook_id, discord_id, steam_id FROM Client WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Count how many OAuth connections exist
    $oauthConnections = 0;
    foreach (['google_id', 'facebook_id', 'discord_id', 'steam_id'] as $col) {
        if (!empty($user[$col])) {
            $oauthConnections++;
        }
    }
    
    // Check if user has a password
    $hasPassword = !empty($user['mot_de_passe']) && 
                   strlen($user['mot_de_passe']) > 10 && 
                   password_get_info($user['mot_de_passe'])['algo'] !== null;
    
    // Don't allow unlinking if it's the only login method
    if (!$hasPassword && $oauthConnections <= 1) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot disconnect your only login method. Please set a password first or connect another account.'
        ]);
        exit;
    }
    
    // Unlink the OAuth account
    $stmt = $pdo->prepare("UPDATE Client SET {$column} = NULL WHERE id_client = ?");
    $stmt->execute([$clientId]);
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($provider) . ' account disconnected successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("OAuth unlink error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}

function ensureSteamOAuthColumn(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote('steam_id'));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE Client ADD COLUMN steam_id VARCHAR(255) DEFAULT NULL UNIQUE");
        }
    } catch (PDOException $e) {
        // Let the main unlink flow return the database error if the column is still unavailable.
    }
}
