<?php
declare(strict_types=1);

/**
 * CLI: create or update an administrator (bcrypt password).
 *
 *   php create-admin.php admin@example.com "YourPassword" "Display Name"
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SRC_PATH . '/Services/admin-helpers.php';

$email = isset($argv[1]) ? trim((string) $argv[1]) : '';
$password = isset($argv[2]) ? (string) $argv[2] : '';
$name = isset($argv[3]) ? trim((string) $argv[3]) : 'Administrator';

if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php create-admin.php email@example.com password \"Display Name\"\n");
    exit(1);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$email = strtolower($email);
$pdo = db();
ensureAdminUsersTable($pdo);

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Could not hash password.\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $upd = $pdo->prepare('UPDATE admin_users SET password_hash = ?, name = ? WHERE email = ?');
    $upd->execute([$hash, $name !== '' ? $name : 'Administrator', $email]);
    fwrite(STDOUT, "Updated password for admin: {$email}\n");
    exit(0);
}

$ins = $pdo->prepare('INSERT INTO admin_users (email, password_hash, name) VALUES (?, ?, ?)');
$ins->execute([$email, $hash, $name !== '' ? $name : 'Administrator']);
fwrite(STDOUT, "Created admin: {$email}\n");
exit(0);
