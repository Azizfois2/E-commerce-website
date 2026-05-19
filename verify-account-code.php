<?php
require_once 'config.php';

$email = $_SESSION['verify_account_email'] ?? '';
$method = $_SESSION['verify_account_method'] ?? 'sms';

if (!$email) {
    header("Location: signup.php");
    exit();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code)) {
        $error = 'Veuillez entrer le code à 6 chiffres.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT id, token_hash FROM email_verifications
            WHERE email = ? AND used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$email]);
        $record = $stmt->fetch();

        if (!$record || !password_verify($code, $record['token_hash'])) {
            $error = 'Le code est invalide ou a expiré.';
        } else {
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE id = ?");
            $stmt->execute([$record['id']]);

            // Mark client email as verified
            $stmt = $pdo->prepare("UPDATE Client SET email_verified = 1 WHERE email = ?");
            $stmt->execute([$email]);

            $success = true;
            unset($_SESSION['verify_account_email']);
        }
    }
}

// Fetch user's phone number for display
$pdo = db();
$stmt = $pdo->prepare("SELECT telephone FROM Client WHERE email = ?");
$stmt->execute([$email]);
$client = $stmt->fetch();
$phone = $client ? htmlspecialchars($client['telephone']) : '';

?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Téléphone — Maroc PC</title>
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
            font-size: 54px;
            margin-bottom: 20px;
            color: #00f5d4;
        }
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
            width: 100%;
            margin-top: 20px;
        }
        .verify-btn:hover {
            background: #00d4b8;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 245, 212, 0.3);
        }
        .code-input {
            width: 100%;
            padding: 14px 18px;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            background: var(--input-bg, #1e2229);
            border: 1px solid var(--border, #2d333b);
            border-radius: 10px;
            color: var(--text, #eef0f4);
            font-family: 'JetBrains Mono', monospace;
            transition: border-color 0.3s ease;
        }
        .code-input:focus {
            outline: none;
            border-color: #00f5d4;
        }
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 24px;
            background: rgba(255, 61, 90, 0.08);
            border: 1px solid var(--red);
            color: var(--red);
        }

    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <?php if ($success): ?>
                <div class="verify-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Compte Vérifié !</h2>
                <p>Votre numéro de téléphone a été validé avec succès. Vous pouvez maintenant vous connecter.</p>
                <a href="login.php" class="verify-btn">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            <?php else: ?>
                <div class="verify-icon" style="color: #00f5d4;">
                    <?php if ($method === 'whatsapp'): ?>
                        <i class="fab fa-whatsapp"></i>
                    <?php else: ?>
                        <i class="fas fa-comment-sms"></i>
                    <?php endif; ?>
                </div>
                <h2>Vérification par <?= $method === 'whatsapp' ? 'WhatsApp' : 'SMS' ?></h2>
                <p>Entrez le code à 6 chiffres que nous venons d'envoyer par <?= $method === 'whatsapp' ? 'WhatsApp' : 'SMS' ?> au <strong><?= $phone ?></strong>.</p>
                
                <?php if (defined('DEV_MODE') && DEV_MODE): ?>
                    <?php if ($method === 'whatsapp' && !empty($_SESSION['two_factor_login']['whatsapp_debug_code'])): ?>
                        <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid var(--green); color: var(--green); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
                            Local dev WhatsApp code: <?= htmlspecialchars($_SESSION['two_factor_login']['whatsapp_debug_code']) ?>
                        </div>
                    <?php elseif ($method === 'sms' && !empty($_SESSION['two_factor_login']['sms_debug_code'])): ?>
                        <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid var(--green); color: var(--green); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
                            Local dev SMS code: <?= htmlspecialchars($_SESSION['two_factor_login']['sms_debug_code']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>



                <form method="POST" action="verify-account-code.php">
                    <input type="text" name="code" class="code-input" maxlength="6" pattern="\d{6}" placeholder="000000" required autocomplete="one-time-code" autofocus>
                    <button type="submit" class="verify-btn">Valider le compte</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="assets/js/theme.js" defer></script>
</body>
</html>
