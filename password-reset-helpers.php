<?php
declare(strict_types=1);

function ensurePasswordResetTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_email (email),
            INDEX idx_password_resets_token (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function createPasswordReset(PDO $pdo, string $email): array
{
    ensurePasswordResetTable($pdo);

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $pdo->prepare('DELETE FROM password_resets WHERE email = ? OR expires_at < NOW() OR used = 1')
        ->execute([$email]);

    $stmt = $pdo->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$email, $tokenHash, $expiresAt]);

    return [
        'token' => $rawToken,
        'expires_at' => $expiresAt,
        'link' => rtrim(APP_URL, '/') . '/reset-password.php?token=' . urlencode($rawToken),
    ];
}

function validatePasswordResetToken(PDO $pdo, string $token): array
{
    ensurePasswordResetTable($pdo);

    if (!ctype_xdigit($token) || strlen($token) !== 64) {
        return ['valid' => false, 'error' => 'Invalid or malformed reset link.'];
    }

    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare('
        SELECT email, used, expires_at
        FROM password_resets
        WHERE token_hash = ?
        LIMIT 1
    ');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['valid' => false, 'error' => 'This reset link is invalid.'];
    }

    if ((int) $row['used'] === 1) {
        return ['valid' => false, 'error' => 'This reset link has already been used.'];
    }

    if (strtotime((string) $row['expires_at']) < time()) {
        return ['valid' => false, 'error' => 'This reset link has expired. Please request a new one.'];
    }

    return [
        'valid' => true,
        'email' => (string) $row['email'],
        'token_hash' => $hash,
        'expires_at' => (string) $row['expires_at'],
    ];
}
