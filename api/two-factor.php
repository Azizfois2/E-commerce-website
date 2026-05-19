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

if (!in_array($action, ['enable', 'disable', 'setup_totp', 'confirm_totp', 'regenerate_backup_codes'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

if ($password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Current password is required.']);
    exit;
}

$pdo = db();
twoFactorEnsureColumns($pdo);

$stmt = $pdo->prepare("SELECT id_client, nom, email, telephone, mot_de_passe, two_factor_totp_secret FROM Client WHERE id_client = ?");
$stmt->execute([(int) $_SESSION['client_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['mot_de_passe'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
    exit;
}

if ($action === 'regenerate_backup_codes') {
    $backupCodes = generateBackupCodes($pdo, (int) $_SESSION['client_id']);
    echo json_encode([
        'success' => true,
        'backup_codes' => $backupCodes,
        'message' => 'New backup codes generated! Save them immediately.'
    ]);
    exit;
}

if ($action === 'setup_totp') {
    $secret = twoFactorGenerateSecret();
    $_SESSION['pending_totp_secret'] = $secret;
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
    if ($secret === '' || !twoFactorVerifyTotp($secret, $code)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Authenticator code is incorrect.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE Client SET two_factor_enabled = 1, two_factor_method = 'authenticator', two_factor_totp_secret = ? WHERE id_client = ?");
    $stmt->execute([$secret, (int) $_SESSION['client_id']]);
    unset($_SESSION['pending_totp_secret']);
    $backupCodes = generateBackupCodes($pdo, (int) $_SESSION['client_id']);
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
    $stmt->execute([$method, (int) $_SESSION['client_id']]);
    $backupCodes = generateBackupCodes($pdo, (int) $_SESSION['client_id']);
} else {
    $stmt = $pdo->prepare("UPDATE Client SET two_factor_enabled = 0 WHERE id_client = ?");
    $stmt->execute([(int) $_SESSION['client_id']]);
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
