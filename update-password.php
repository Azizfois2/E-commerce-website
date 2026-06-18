<?php
declare(strict_types=1);

require_once 'config.php';
require_once 'password-reset-helpers.php';
require_once __DIR__ . '/includes/i18n.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot-password.php');
    exit;
}

if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
    jsonResponse(false, i18n_t('auth.reset_invalid_token', [], 'Invalid security token. Refresh the page and try again.'));
}

$token = (string) ($_POST['token'] ?? '');
$newPass = (string) ($_POST['newpass'] ?? '');
$confirm = (string) ($_POST['confirmpass'] ?? '');

if (strlen($newPass) < 8 || !preg_match('/[0-9]/', $newPass) || !preg_match('/[!@#$%^&*]/', $newPass)) {
    jsonResponse(false, i18n_t('auth.reset_password_weak', [], 'Password must be 8+ characters with a number and symbol.'));
}

if ($newPass !== $confirm) {
    jsonResponse(false, i18n_t('auth.reset_passwords_mismatch', [], 'Passwords do not match.'));
}

$pdo = db();
$validation = validatePasswordResetToken($pdo, $token);
if (empty($validation['valid'])) {
    jsonResponse(false, (string) ($validation['error'] ?? i18n_t('auth.reset_invalid_token', [], 'Invalid reset token.')));
}

$email = (string) $validation['email'];
$passwordHash = password_hash($newPass, PASSWORD_DEFAULT);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE Client SET mot_de_passe = ? WHERE email = ?');
    $stmt->execute([$passwordHash, $email]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Account not found.');
    }

    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE email = ?')->execute([$email]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[PASSWORD RESET] Failed to update password for ' . $email . ': ' . $e->getMessage());
    jsonResponse(false, i18n_t('auth.reset_update_failed', [], 'Could not update password. Please request a new reset link.'));
}

jsonResponse(true, i18n_t('auth.reset_updated', [], 'Password updated successfully.'));
