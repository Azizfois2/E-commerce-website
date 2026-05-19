<?php
/**
 * resend-verification.php — Resends the email verification link.
 */
require_once 'config.php';
require_once 'mailer.php';

$email = $_GET['email'] ?? '';
$message = '';
$isError = false;

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Invalid email address.';
    $isError = true;
} else {
    $pdo = db();

    // Check if the user exists and isn't already verified
    $stmt = $pdo->prepare("SELECT id_client, nom, email_verified FROM Client WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = 'No account found with this email.';
        $isError = true;
    } elseif (!empty($user['email_verified'])) {
        $message = 'Your email is already verified! You can log in.';
        $isError = false;
    } else {
        // Invalidate old tokens
        $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE email = ? AND used = 0");
        $stmt->execute([$email]);

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("INSERT INTO email_verifications (email, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $tokenHash, $expiresAt]);

        // Send the email
        $sent = sendVerificationEmail($email, $user['nom'], $token);

        if ($sent) {
            $message = 'A new verification email has been sent! Check your inbox.';
        } else {
            $message = 'Failed to send email. Please try again later.';
            $isError = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification — Maroc PC</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <style>
        .verify-container { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 20px; }
        .verify-card { background:var(--card-bg,#0a0b0e); border:1px solid var(--border,#1e2229); border-radius:20px; padding:48px 40px; max-width:480px; width:100%; text-align:center; animation:fadeUp 0.5s ease; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .verify-icon { font-size:64px; margin-bottom:20px; }
        .verify-icon.success { color:#00f5d4; }
        .verify-icon.error { color:#ff3d5a; }
        .verify-card h2 { font-family:'Orbitron',monospace; font-size:1.5rem; margin:0 0 12px; color:var(--text,#eef0f4); }
        .verify-card p { color:var(--muted,#b0b8c8); font-size:0.95rem; line-height:1.6; margin:0 0 28px; }
        .verify-btn { display:inline-block; padding:14px 36px; background:#00f5d4; color:#000; text-decoration:none; border-radius:10px; font-weight:700; font-size:0.95rem; transition:all 0.3s ease; }
        .verify-btn:hover { background:#00d4b8; transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,245,212,0.3); }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-icon <?= $isError ? 'error' : 'success' ?>">
                <i class="fas <?= $isError ? 'fa-exclamation-circle' : 'fa-envelope-circle-check' ?>"></i>
            </div>
            <h2><?= $isError ? 'Oops!' : 'Email Sent!' ?></h2>
            <p><?= htmlspecialchars($message) ?></p>
            <a href="login.php" class="verify-btn">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </a>
        </div>
    </div>
    <script src="assets/js/theme.js" defer></script>
</body>
</html>
