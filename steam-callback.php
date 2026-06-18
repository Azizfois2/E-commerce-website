<?php
// steam-callback.php — Steam OpenID callback
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/two-factor-helpers.php';

// Already logged in
if (isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

// ── 1. Validate OpenID response ───────────────────────────
if (empty($_GET['openid_mode']) || $_GET['openid_mode'] !== 'id_res') {
    header('Location: login.php?error=steam_auth_failed&detail=invalid_mode');
    exit;
}

// ── 2. Verify the response with Steam ─────────────────────
$params = $_GET;
$params['openid.mode'] = 'check_authentication';

$query = http_build_query($params);
$ch = curl_init('https://steamcommunity.com/openid/login');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $query,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);
$response = curl_exec($ch);
curl_close($ch);

// Check if Steam confirmed the authentication
if (strpos($response, 'is_valid:true') === false) {
    header('Location: login.php?error=steam_auth_failed&detail=verification_failed');
    exit;
}

// ── 3. Extract Steam ID from claimed_id ───────────────────
// Format: https://steamcommunity.com/openid/id/76561197960435530
$claimedId = $_GET['openid_claimed_id'] ?? '';
if (!preg_match('/^https?:\/\/steamcommunity\.com\/openid\/id\/(\d+)$/', $claimedId, $matches)) {
    header('Location: login.php?error=steam_auth_failed&detail=invalid_steam_id');
    exit;
}

$steamId = $matches[1];

// ── 4. Get Steam profile data (optional, requires API key) ─
$name = 'Steam User';
$avatar = null;
$email = null;

// If you have a Steam API key, you can fetch profile data
if (defined('STEAM_API_KEY') && STEAM_API_KEY !== '') {
    $apiUrl = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' 
            . STEAM_API_KEY . '&steamids=' . $steamId;
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $profileJson = curl_exec($ch);
    curl_close($ch);
    
    $profileData = json_decode($profileJson, true);
    if (!empty($profileData['response']['players'][0])) {
        $player = $profileData['response']['players'][0];
        $name   = $player['personaname'] ?? $name;
        $avatar = $player['avatarfull'] ?? $avatar;
    }
}

// ── 5. Find or create user in DB ──────────────────────────
try {
    $pdo = db();
    ensureSteamOAuthColumn($pdo);
    twoFactorEnsureColumns($pdo);

    // a) Look up by steam_id
    $stmt = $pdo->prepare("SELECT * FROM Client WHERE steam_id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$steamId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // b) If not found, create a new user
    if (!$user) {
        // Steam doesn't provide email, so we use a placeholder
        $fakeEmail   = 'steam_' . $steamId . '@steam.invalid';
        $randomPass  = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $profileImg  = $avatar ?: null;

        $insertStmt = $pdo->prepare("
            INSERT INTO Client
                (nom, email, mot_de_passe, email_verified, steam_id, profile_image)
            VALUES (?, ?, ?, 1, ?, ?)
        ");
        $insertStmt->execute([$name, $fakeEmail, $randomPass, $steamId, $profileImg]);

        $newId = $pdo->lastInsertId();
        $stmt  = $pdo->prepare("SELECT * FROM Client WHERE id_client = ? LIMIT 1");
        $stmt->execute([$newId]);
        $user  = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── 6. Safety checks ──────────────────────────────────
    if (!$user) {
        error_log('Steam login error: User not found after creation');
        header('Location: login.php?error=steam_auth_failed&detail=user_not_found');
        exit;
    }

    if (!empty($user['is_suspended'])) {
        header('Location: login.php?error=account_suspended');
        exit;
    }

    if (!empty($user['two_factor_enabled'])) {
        twoFactorStartLoginChallenge($user, 'index.php', false, null);
    }

    // ── 7. Create session ─────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['client_id']    = $user['id_client'];
    $_SESSION['client_nom']   = $user['nom'];
    $_SESSION['client_email'] = $user['email'];

    // Use your existing session lifetime function
    if (function_exists('applyLoginSessionLifetime')) {
        applyLoginSessionLifetime(false);
    }

    header('Location: index.php');
    exit;

} catch (Exception $e) {
    error_log('Steam login error: ' . $e->getMessage());
    header('Location: login.php?error=steam_auth_failed&detail=' . urlencode($e->getMessage()));
    exit;
}

function ensureSteamOAuthColumn(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote('steam_id'));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE Client ADD COLUMN steam_id VARCHAR(255) DEFAULT NULL UNIQUE");
        }
    } catch (PDOException $e) {
        // The existing callback error handling will report a failed login if the DB cannot migrate.
    }
}
