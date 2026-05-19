<?php
require_once 'config.php';
require_once 'mailer.php';
require_once 'two-factor-helpers.php';

if (!empty($_SESSION['client_id'])) {
    header('Location: account.php');
    exit();
}

$pending = $_SESSION['two_factor_login'] ?? null;
if (!$pending || empty($pending['client_id']) || empty($pending['method'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = false;
$method = twoFactorNormalizeMethod($pending['method'] ?? 'email');
$maskedEmail = maskEmail((string) ($pending['email'] ?? ''));
$maskedPhone = twoFactorMaskPhone((string) ($pending['phone'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $errors['general'] = 'Session invalide, veuillez reessayer.';
    } else {
        $action = $_POST['action'] ?? 'verify';

        if ($action === 'cancel') {
            unset($_SESSION['two_factor_login']);
            header('Location: login.php');
            exit();
        }

        if ($action === 'resend') {
            resendTwoFactorCode();
            $success = true;
        } elseif ($action === 'switch_channel') {
            switchTwoFactorChannel($errors);
            $success = empty($errors);
        } else {
            verifyTwoFactorCode($errors);
        }
    }
}

$pending = $_SESSION['two_factor_login'] ?? $pending;
$method = twoFactorNormalizeMethod($pending['method'] ?? $method);
$maskedPhone = twoFactorMaskPhone((string) ($pending['phone'] ?? ''));

function verifyTwoFactorCode(array &$errors): void
{
    $pending = $_SESSION['two_factor_login'] ?? [];
    $rawCode = strtoupper(trim((string) ($_POST['code'] ?? '')));
    
    // Check if it's an 8-character alphanumeric backup code
    $isBackupCode = (strlen($rawCode) === 8 && ctype_alnum($rawCode));
    
    if ($isBackupCode) {
        $clientId = (int) ($pending['client_id'] ?? 0);
        if ($clientId <= 0) {
            $errors['general'] = 'Une erreur est survenue. Veuillez vous reconnecter.';
            return;
        }
        
        $pdo = db();
        // Fetch all stored backup codes for this client
        $stmt = $pdo->prepare("SELECT id, code_hash FROM two_factor_backup_codes WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $storedCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $matched = false;
        $matchedId = 0;
        foreach ($storedCodes as $stored) {
            if (password_verify($rawCode, $stored['code_hash'])) {
                $matched = true;
                $matchedId = (int) $stored['id'];
                break;
            }
        }
        
        if ($matched && $matchedId > 0) {
            // Delete used backup code (one-time use)
            $pdo->prepare("DELETE FROM two_factor_backup_codes WHERE id = ?")->execute([$matchedId]);
            // Clear attempts
            $_SESSION['two_factor_login']['attempts'] = 0;
        } else {
            $_SESSION['two_factor_login']['attempts'] = ((int) ($pending['attempts'] ?? 0)) + 1;
            if ($_SESSION['two_factor_login']['attempts'] > 5) {
                unset($_SESSION['two_factor_login']);
                $errors['general'] = 'Trop de tentatives. Veuillez vous reconnecter.';
                return;
            }
            $errors['code'] = 'Code de secours incorrect.';
            return;
        }
    } else {
        // Normal 6-digit verification code check
        $code = preg_replace('/\D+/', '', $rawCode);
        $method = twoFactorNormalizeMethod($pending['method'] ?? 'email');
        if ($method !== 'authenticator' && time() > (int) ($pending['expires_at'] ?? 0)) {
            $errors['general'] = 'Ce code a expire. Demandez un nouveau code.';
            return;
        }

        if (strlen($code) !== 6) {
            $errors['code'] = 'Entrez le code a 6 chiffres ou un code de secours a 8 caracteres.';
            return;
        }

        $_SESSION['two_factor_login']['attempts'] = ((int) ($pending['attempts'] ?? 0)) + 1;
        if ($_SESSION['two_factor_login']['attempts'] > 5) {
            unset($_SESSION['two_factor_login']);
            $errors['general'] = 'Trop de tentatives. Veuillez vous reconnecter.';
            return;
        }

        if ($method === 'authenticator') {
            $pdo = db();
            twoFactorEnsureColumns($pdo);
            $stmt = $pdo->prepare("SELECT two_factor_totp_secret FROM Client WHERE id_client = ?");
            $stmt->execute([(int) $pending['client_id']]);
            $secret = (string) $stmt->fetchColumn();
            if (!twoFactorVerifyTotp($secret, $code)) {
                $errors['code'] = 'Code incorrect.';
                return;
            }
        } elseif (!password_verify($code, (string) ($pending['code_hash'] ?? ''))) {
            $errors['code'] = 'Code incorrect.';
            return;
        }
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT id_client, nom, email FROM Client WHERE id_client = ?");
    $stmt->execute([(int) $pending['client_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        unset($_SESSION['two_factor_login']);
        $errors['general'] = 'Compte introuvable. Veuillez vous reconnecter.';
        return;
    }

    $remember = !empty($pending['remember']);
    $next = safeTwoFactorRedirect((string) ($pending['next'] ?? 'index.html'));

    session_regenerate_id(true);
    $_SESSION['client_id'] = $client['id_client'];
    $_SESSION['client_nom'] = $client['nom'];
    $_SESSION['client_email'] = $client['email'];
    unset($_SESSION['two_factor_login']);

    applyLoginSessionLifetime($remember);

    header("Location: $next");
    exit();
}

function resendTwoFactorCode(): void
{
    $pending = $_SESSION['two_factor_login'];
    $method = twoFactorNormalizeMethod($pending['method'] ?? 'email');
    if ($method === 'authenticator') {
        return;
    }

    $code = (string) random_int(100000, 999999);
    $_SESSION['two_factor_login']['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['two_factor_login']['expires_at'] = time() + 600;
    $_SESSION['two_factor_login']['attempts'] = 0;

    twoFactorSendCode($method, [
        'email' => (string) $pending['email'],
        'telephone' => (string) ($pending['phone'] ?? ''),
        'nom' => (string) $pending['name'],
    ], $code);
}

function switchTwoFactorChannel(array &$errors): void
{
    $target = twoFactorNormalizeMethod($_POST['method'] ?? 'email');
    if ($target === 'authenticator') {
        $errors['general'] = 'Use the authenticator code from your app.';
        return;
    }

    $pending = $_SESSION['two_factor_login'] ?? [];
    $available = twoFactorAvailableMethods([
        'email' => (string) ($pending['email'] ?? ''),
        'telephone' => (string) ($pending['phone'] ?? ''),
        'two_factor_totp_secret' => null,
    ]);
    if (!in_array($target, $available, true)) {
        $errors['general'] = $target === 'whatsapp'
            ? 'Aucun numero WhatsApp valide sur ce compte.'
            : 'Methode indisponible.';
        return;
    }

    $_SESSION['two_factor_login']['method'] = $target;
    resendTwoFactorCode();
}

function safeTwoFactorRedirect(string $target, string $fallback = 'index.html'): string
{
    $target = trim($target);
    if ($target === '') {
        return $fallback;
    }

    if (
        preg_match('#^(https?://|//|javascript:)#i', $target) ||
        strpos($target, '..') !== false ||
        strpbrk($target, "\r\n") !== false
    ) {
        return $fallback;
    }

    return $target;
}

function maskEmail(string $email): string
{
    if (!str_contains($email, '@')) {
        return $email;
    }

    [$name, $domain] = explode('@', $email, 2);
    $visible = mb_substr($name, 0, 2);
    return $visible . str_repeat('*', max(2, mb_strlen($name) - 2)) . '@' . $domain;
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification 2FA - Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <style>
        .two-factor-panel {
            max-width: 460px;
            margin: 12vh auto;
            background: var(--card-bg, #111318);
            border: 1px solid var(--border, #2a2e38);
            border-radius: 18px;
            padding: 36px;
            box-shadow: 0 18px 60px rgba(0,0,0,0.35);
        }
        .two-factor-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,245,212,0.12);
            color: var(--cyan, #00f5d4);
            font-size: 1.6rem;
            margin-bottom: 18px;
        }
        .two-factor-panel h1 {
            margin: 0 0 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.35rem;
            color: var(--text, #eef0f4);
        }
        .two-factor-panel p {
            color: var(--muted, #b0b8c8);
            line-height: 1.6;
            margin: 0 0 22px;
        }
        .code-input {
            text-align: center;
            letter-spacing: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .two-factor-actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        .two-factor-actions button {
            flex: 1;
        }
        .link-button {
            background: transparent;
            border: 1px solid var(--border, #2a2e38);
            color: var(--text, #eef0f4);
            border-radius: 12px;
            padding: 13px 18px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            cursor: pointer;
        }
        .link-button:hover {
            border-color: var(--cyan, #00f5d4);
            color: var(--cyan, #00f5d4);
        }
        .alert-error,
        .alert-success {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .alert-error {
            border: 1px solid var(--red, #ff3d5a);
            color: var(--red, #ff3d5a);
            background: rgba(255,61,90,0.08);
        }
        .alert-success {
            border: 1px solid var(--green, #00e676);
            color: var(--green, #00e676);
            background: rgba(0,230,118,0.08);
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-link">Retour a la connexion</a>

    <main class="two-factor-panel">
        <div class="two-factor-icon"><i class="fas fa-shield-halved"></i></div>
        <h1>Verification en deux etapes</h1>
        <?php if ($method === 'authenticator'): ?>
            <p>Entrez le code a 6 chiffres depuis votre application Authenticator.</p>
        <?php elseif ($method === 'whatsapp'): ?>
            <p>Entrez le code a 6 chiffres envoye par WhatsApp a <strong><?= h($maskedPhone ?: 'votre telephone') ?></strong>.</p>
            <?php if (DEV_MODE && !empty($_SESSION['two_factor_login']['whatsapp_debug_code'])): ?>
                <div class="alert-success">Local dev WhatsApp code: <?= h($_SESSION['two_factor_login']['whatsapp_debug_code']) ?></div>
            <?php endif; ?>
        <?php else: ?>
            <p>Entrez le code a 6 chiffres envoye a <strong><?= h($maskedEmail) ?></strong>.</p>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error"><?= h($errors['general']) ?></div>
        <?php elseif ($success): ?>
            <div class="alert-success">Nouveau code envoye. Verifiez <?= $method === 'whatsapp' ? 'WhatsApp' : 'votre boite mail' ?>.</div>
        <?php endif; ?>

        <?php if (!empty($errors['code'])): ?>
            <div class="alert-error"><?= h($errors['code']) ?></div>
        <?php endif; ?>

        <form method="post" action="verify-2fa.php" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="verify">
            <div class="form-group">
                <label for="code">Code de verification / secours</label>
                <input class="hh code-input" type="text" id="code" name="code" maxlength="8" placeholder="123456 ou A3F2B1C9" autocomplete="one-time-code" autofocus style="text-transform: uppercase;">
                <small style="display:block; color:var(--muted); margin-top:6px; font-size:0.8rem; text-align:center;">Entrez le code à 6 chiffres ou un code de secours à 8 caractères.</small>
            </div>
            <button type="submit" class="Bou" style="width:100%;">Verifier et continuer</button>
        </form>

        <div class="two-factor-actions">
            <form method="post" action="verify-2fa.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="link-button" <?= $method === 'authenticator' ? 'disabled' : '' ?>>Renvoyer le code</button>
            </form>
            <?php if ($method !== 'email'): ?>
                <form method="post" action="verify-2fa.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="switch_channel">
                    <input type="hidden" name="method" value="email">
                    <button type="submit" class="link-button">Utiliser email</button>
                </form>
            <?php endif; ?>
            <?php if ($method !== 'whatsapp' && $maskedPhone !== ''): ?>
                <form method="post" action="verify-2fa.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="switch_channel">
                    <input type="hidden" name="method" value="whatsapp">
                    <button type="submit" class="link-button">Utiliser WhatsApp</button>
                </form>
            <?php endif; ?>
            <form method="post" action="verify-2fa.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="link-button">Annuler</button>
            </form>
        </div>
    </main>
</body>
</html>
