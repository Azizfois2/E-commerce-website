<?php
declare(strict_types=1);

require_once 'config.php';
require_once 'mailer.php';
require_once 'password-reset-helpers.php';
require_once __DIR__ . '/includes/i18n.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot-password.php');
    exit;
}

if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
    jsonResponse(false, i18n_t('auth.reset_invalid_token', [], 'Invalid security token. Refresh the page and try again.'));
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$genericMessage = i18n_t('auth.reset_sent_generic', [], 'If an account exists, a reset link has been sent.');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(true, $genericMessage);
}

$pdo = db();
ensurePasswordResetTable($pdo);

$stmt = $pdo->prepare('SELECT id_client, nom, email FROM Client WHERE LOWER(email) = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(true, $genericMessage);
}

$reset = createPasswordReset($pdo, (string) $user['email']);
$sent = sendPasswordResetEmail((string) $user['email'], $reset['link']);

if (!$sent) {
    error_log('[PASSWORD RESET] Failed to send reset email to ' . $user['email']);
    jsonResponse(false, DEV_MODE ? 'Email could not be sent. Check SMTP settings in config.php.' : i18n_t('auth.reset_mail_failed', [], 'Email service is temporarily unavailable. Please try again later.'));
}

$extra = [];
if (DEV_MODE) {
    $extra['dev_link'] = $reset['link'];
}

jsonResponse(true, $genericMessage, $extra);
