<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/two-factor-helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';
$password = (string) ($input['password'] ?? '');
$ownershipCode = preg_replace('/\D+/', '', (string) ($input['ownership_code'] ?? ''));

if (!in_array($action, ['enable', 'disable', 'setup_totp', 'confirm_totp', 'regenerate_backup_codes', 'request_ownership_code'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$pdo = db();
twoFactorEnsureColumns($pdo);

foreach (['google_id', 'facebook_id', 'discord_id', 'steam_id'] as $oauthColumn) {
    try {
        $exists = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote($oauthColumn))->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $pdo->exec("ALTER TABLE Client ADD COLUMN {$oauthColumn} VARCHAR(255) DEFAULT NULL UNIQUE");
        }
    } catch (PDOException $e) {
        // If an older local DB cannot migrate here, the password path can still work.
    }
}

$stmt = $pdo->prepare("SELECT id_client, nom, email, telephone, mot_de_passe, google_id, facebook_id, discord_id, steam_id, two_factor_totp_secret FROM Client WHERE id_client = ?");
$stmt->execute([(int) $_SESSION['client_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Account not found.']);
    exit;
}

$passwordHash = (string) ($user['mot_de_passe'] ?? '');
$passwordInfo = $passwordHash !== '' ? password_get_info($passwordHash) : ['algo' => 0];
$hasUsablePassword = !empty($passwordInfo['algo']);
$hasOAuthProvider = !empty($user['google_id']) || !empty($user['facebook_id']) || !empty($user['discord_id']) || !empty($user['steam_id']);
$prefersOwnershipCode = $hasOAuthProvider || !$hasUsablePassword;
$clientId = (int) $_SESSION['client_id'];

if ($action === 'request_ownership_code') {
    $email = trim((string) ($user['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Your account does not have a valid email address.']);
        exit;
    }

    $code = (string) random_int(100000, 999999);
    $_SESSION['two_factor_ownership'] = [
        'client_id' => $clientId,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + 600,
        'attempts' => 0,
    ];

    require_once dirname(__DIR__) . '/mailer.php';
    $sent = sendTwoFactorCodeEmail($email, (string) ($user['nom'] ?? 'there'), $code);
    if (!$sent) {
        unset($_SESSION['two_factor_ownership']);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => function_exists('lastMailError') ? (lastMailError() ?: 'Could not send verification code.') : 'Could not send verification code.']);
        exit;
    }

    $response = [
        'success' => true,
        'message' => 'Verification code sent to your email. It expires in 10 minutes.',
    ];
    if (defined('DEV_MODE') && DEV_MODE) {
        $response['debug_code'] = $code;
    }
    echo json_encode($response);
    exit;
}

function twoFactorVerifyOwnershipCodeForSession(string $code, int $clientId): bool
{
    $challenge = $_SESSION['two_factor_ownership'] ?? null;
    if (!is_array($challenge) || (int) ($challenge['client_id'] ?? 0) !== $clientId) {
        return false;
    }
    if ((int) ($challenge['expires_at'] ?? 0) < time()) {
        unset($_SESSION['two_factor_ownership']);
        return false;
    }
    if ((int) ($challenge['attempts'] ?? 0) >= 5) {
        unset($_SESSION['two_factor_ownership']);
        return false;
    }

    $_SESSION['two_factor_ownership']['attempts'] = (int) ($challenge['attempts'] ?? 0) + 1;
    if ($code !== '' && password_verify($code, (string) ($challenge['code_hash'] ?? ''))) {
        unset($_SESSION['two_factor_ownership']);
        $_SESSION['two_factor_ownership_verified_until'] = time() + 600;
        return true;
    }

    return false;
}

function twoFactorSessionRecentlyVerified(): bool
{
    return (int) ($_SESSION['two_factor_ownership_verified_until'] ?? 0) >= time();
}

$passwordVerified = $hasUsablePassword && $password !== '' && password_verify($password, $passwordHash);
$ownershipVerified = $prefersOwnershipCode && twoFactorVerifyOwnershipCodeForSession($ownershipCode, $clientId);
$authorized = $passwordVerified || $ownershipVerified;
$ownershipError = $prefersOwnershipCode ? 'Email verification code is incorrect or expired.' : 'Current password is incorrect.';

if ($action === 'regenerate_backup_codes') {
    if (!$authorized) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $ownershipError]);
        exit;
    }
    $backupCodes = generateBackupCodes($pdo, $clientId);
    echo json_encode([
        'success' => true,
        'backup_codes' => $backupCodes,
        'message' => 'New backup codes generated! Save them immediately.'
    ]);
    exit;
}

if ($action === 'setup_totp') {
    if (!$authorized) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $ownershipError]);
        exit;
    }
    $secret = twoFactorGenerateSecret();
    $_SESSION['pending_totp_secret'] = $secret;
    $_SESSION['pending_totp_authorized_until'] = time() + 600;
    $uri = twoFactorOtpAuthUri((string) $user['email'], $secret);
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'otpauth_uri' => $uri,
        'qr_url' => twoFactorQrImageUrl($uri),
        'message' => 'Scan the QR code, then enter the 6-digit code from your app.'
    ]);
    exit;
}

if ($action === 'confirm_totp') {
    $secret = (string) ($_SESSION['pending_totp_secret'] ?? '');
    $code = (string) ($input['code'] ?? '');
    $totpSetupAuthorized = $passwordVerified || twoFactorSessionRecentlyVerified() || ((int) ($_SESSION['pending_totp_authorized_until'] ?? 0) >= time());
    if (!$totpSetupAuthorized) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Please verify account ownership before confirming authenticator setup.']);
        exit;
    }
    if ($secret === '' || !twoFactorVerifyTotp($secret, $code)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Authenticator code is incorrect.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE Client SET two_factor_enabled = 1, two_factor_method = 'authenticator', two_factor_totp_secret = ? WHERE id_client = ?");
    $stmt->execute([$secret, $clientId]);
    unset($_SESSION['pending_totp_secret']);
    unset($_SESSION['pending_totp_authorized_until']);
    unset($_SESSION['two_factor_ownership_verified_until']);
    $backupCodes = generateBackupCodes($pdo, $clientId);
    echo json_encode([
        'success' => true,
        'enabled' => true,
        'method' => 'authenticator',
        'backup_codes' => $backupCodes,
        'message' => 'Authenticator app two-factor authentication is now enabled.'
    ]);
    exit;
}

$enabled = $action === 'enable';
$method = twoFactorNormalizeMethod($input['method'] ?? 'email');
if (!$authorized) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $ownershipError]);
    exit;
}
if ($enabled && $method === 'authenticator') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Use authenticator setup first.']);
    exit;
}
if ($enabled && $method === 'whatsapp' && twoFactorNormalizePhone((string) ($user['telephone'] ?? '')) === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Add a phone number before enabling WhatsApp codes.']);
    exit;
}

if ($enabled) {
    $stmt = $pdo->prepare("UPDATE Client SET two_factor_enabled = 1, two_factor_method = ? WHERE id_client = ?");
    $stmt->execute([$method, $clientId]);
    unset($_SESSION['two_factor_ownership_verified_until']);
    $backupCodes = generateBackupCodes($pdo, $clientId);
} else {
    $stmt = $pdo->prepare("UPDATE Client SET two_factor_enabled = 0 WHERE id_client = ?");
    $stmt->execute([$clientId]);
    unset($_SESSION['two_factor_ownership_verified_until']);
    $backupCodes = [];
}

echo json_encode([
    'success' => true,
    'enabled' => $enabled,
    'method' => $method,
    'backup_codes' => $backupCodes,
    'message' => $enabled
        ? (($method === 'whatsapp') ? 'WhatsApp login codes are now enabled.' : 'Email login codes are now enabled.')
        : 'Two-factor authentication is now disabled.'
]);

/**
 * Generate 8 one-time backup codes, hash and store in two_factor_backup_codes.
 * Returns plaintext codes (shown to user once only).
 */
function generateBackupCodes(PDO $pdo, int $clientId): array
{
    try {
        // Delete any old codes
        $pdo->prepare('DELETE FROM two_factor_backup_codes WHERE client_id = ?')->execute([$clientId]);

        $plaintextCodes = [];
        $stmt = $pdo->prepare('INSERT INTO two_factor_backup_codes (client_id, code_hash) VALUES (?, ?)');
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // e.g. "A3F2B1C9"
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $stmt->execute([$clientId, $hash]);
            $plaintextCodes[] = $code;
        }
        return $plaintextCodes;
    } catch (Throwable $e) {
        return [];
    }
}
