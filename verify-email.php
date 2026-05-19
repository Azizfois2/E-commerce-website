<?php
/**
 * verify-email.php — Handles email verification links.
 * 
 * URL format: verify-email.php?token=XXXXX&email=user@example.com
 */
require_once 'config.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = false;

if (empty($token) || empty($email)) {
    $error = 'Invalid verification link.';
} else {
    $pdo = db();

    // Look up the token
    $stmt = $pdo->prepare("
        SELECT id, email FROM email_verifications
        WHERE token_hash = ? AND email = ? AND used = 0 AND expires_at > ?
    ");
    $stmt->execute([hash('sha256', $token), $email, date('Y-m-d H:i:s')]);
    $record = $stmt->fetch();

    if (!$record) {
        $error = 'This verification link is invalid or has expired.';
    } else {
        // Mark token as used
        $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE id = ?");
        $stmt->execute([$record['id']]);

        // Mark client email as verified
        $stmt = $pdo->prepare("UPDATE Client SET email_verified = 1 WHERE email = ?");
        $stmt->execute([$email]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .verify-card {
            background: var(--card-bg, #0a0b0e);
            border: 1px solid var(--border, #1e2229);
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .verify-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .verify-icon.success { color: #00f5d4; }
        .verify-icon.error { color: #ff3d5a; }
        .verify-card h2 {
            font-family: 'Orbitron', monospace;
            font-size: 1.5rem;
            margin: 0 0 12px;
            color: var(--text, #eef0f4);
        }
        .verify-card p {
            color: var(--muted, #b0b8c8);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0 0 28px;
        }
        .verify-btn {
            display: inline-block;
            padding: 14px 36px;
            background: #00f5d4;
            color: #000;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .verify-btn:hover {
            background: #00d4b8;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 245, 212, 0.3);
        }
        .verify-btn.secondary {
            background: transparent;
            color: #00f5d4;
            border: 1px solid rgba(0,245,212,0.3);
            margin-left: 12px;
        }
        .verify-btn.secondary:hover {
            background: rgba(0,245,212,0.08);
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <?php if ($success): ?>
                <div class="verify-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Email Verified!</h2>
                <p>Your email has been successfully verified. You can now log in to your account and start shopping!</p>
                <a href="login.php" class="verify-btn">
                    <i class="fas fa-sign-in-alt"></i> Log In Now
                </a>
            <?php else: ?>
                <div class="verify-icon error">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h2>Verification Failed</h2>
                <p><?= htmlspecialchars($error) ?></p>
                <a href="signup.php" class="verify-btn secondary">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <a href="index.html" class="verify-btn secondary" style="margin-top:12px;">
                    <i class="fas fa-home"></i> Go Home
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/theme.js" defer></script>
</body>
</html>
