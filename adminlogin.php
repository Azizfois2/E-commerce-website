<?php
require_once 'admin-helpers.php';

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
const ADMIN_LOGIN_FAILED_MESSAGE = "Invalid administrator credentials. Check your information and try again.";
const ADMIN_LOGIN_LOCK_MESSAGE = "Too many administrator attempts. Try again in a few minutes.";
const ADMIN_MAX_FAILED_ATTEMPTS = 3;
const ADMIN_LOCK_MINUTES = 15;

// ─── Si déjà connecté ────────────────────────────────
if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit();
}

$errors   = [];
$success  = false;
$email    = "";

$pdo = db();
ensureAdminUsersTable($pdo);
$adminSetupNeeded = adminCountAdmins($pdo) === 0;

if ($requestMethod === "POST") {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $errors["general"] = "Invalid session, please try again.";
    }

    if (empty($errors) && defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== '') {
        $cfToken = $_POST['cf-turnstile-response'] ?? '';
        if (!verifyTurnstile($cfToken)) {
            $errors["general"] = "CAPTCHA verification failed. Please try again.";
        }
    }

    $email    = trim($_POST["email"] ?? "");
    $pass_raw = trim($_POST["pass"]  ?? "");

    if (empty($errors) && (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors["email"] = "Invalid email address.";
    }

    if (empty($errors) && empty($pass_raw)) {
        $errors["pass"] = "Password is required.";
    }

    if (empty($errors)) {
        ensureAdminLoginAttemptsTable($pdo);
        $attemptKey = adminLoginAttemptKey($email);
        $lockedUntil = adminLoginLockedUntil($pdo, $attemptKey);
        if ($lockedUntil !== null && $lockedUntil <= new DateTime()) {
            clearAdminLoginAttempts($pdo, $attemptKey);
            $lockedUntil = null;
        }
        if ($lockedUntil !== null && $lockedUntil > new DateTime()) {
            $errors["general"] = ADMIN_LOGIN_LOCK_MESSAGE;
        } elseif (adminCountAdmins($pdo) === 0) {
            $errors["general"] = 'No administrator configured. Run from command line: php create-admin.php admin@example.com password "Display Name"';
        } else {
            $stmt = $pdo->prepare('SELECT id, password_hash, name FROM admin_users WHERE email = ? LIMIT 1');
            $stmt->execute([strtolower(trim($email))]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = is_array($admin) ? (string) ($admin['password_hash'] ?? '') : '';
            if (!is_array($admin) || $hash === '' || !password_verify($pass_raw, $hash)) {
                registerAdminFailedLogin($pdo, $attemptKey, $email);
                $errors["general"] = ADMIN_LOGIN_FAILED_MESSAGE;
            } else {
                clearAdminLoginAttempts($pdo, $attemptKey);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_nom'] = (string) ($admin['name'] ?: 'Administrator');
                $_SESSION['admin_email'] = strtolower(trim($email));

                $success = true;
                header('Location: dashboard.php');
                exit();
            }
        }
    }
}

function grp(string $field, array $errors): string {
    return isset($errors[$field]) ? 'form-group invalid' : 'form-group';
}

function adminClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function adminLoginAttemptKey(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . '|' . adminClientIp());
}

function ensureAdminLoginAttemptsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_login_attempts (
            attempt_key VARCHAR(64) PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            failed_attempts INT NOT NULL DEFAULT 0,
            locked_until DATETIME DEFAULT NULL,
            last_failed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_locked_until (locked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function adminLoginLockedUntil(PDO $pdo, string $attemptKey): ?DateTime
{
    $stmt = $pdo->prepare("SELECT locked_until FROM admin_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
    $lockedUntil = $stmt->fetchColumn();

    return $lockedUntil ? new DateTime((string) $lockedUntil) : null;
}

function registerAdminFailedLogin(PDO $pdo, string $attemptKey, string $email): void
{
    $stmt = $pdo->prepare("SELECT failed_attempts FROM admin_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
    $attempts = $stmt->fetchColumn();

    if ($attempts === false) {
        $stmt = $pdo->prepare(
            "INSERT INTO admin_login_attempts (attempt_key, email, ip_address, failed_attempts, last_failed_at)
             VALUES (?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$attemptKey, strtolower(trim($email)), adminClientIp()]);
        return;
    }

    $nextAttempts = (int) $attempts + 1;
    $lockedUntil = null;
    if ($nextAttempts >= ADMIN_MAX_FAILED_ATTEMPTS) {
        $lockedUntil = (new DateTime())->modify('+' . ADMIN_LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare(
        "UPDATE admin_login_attempts
         SET failed_attempts = ?, last_failed_at = NOW(), locked_until = ?
         WHERE attempt_key = ?"
    );
    $stmt->execute([$nextAttempts, $lockedUntil, $attemptKey]);
}

function clearAdminLoginAttempts(PDO $pdo, string $attemptKey): void
{
    $stmt = $pdo->prepare("DELETE FROM admin_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/signup.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
    <?php endif; ?>

    <style>
        .form-group.invalid .error-msg { display: block; }
        .alert-error {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-radius: 12px; font-size: 0.9rem; font-weight: 600;
            margin-bottom: 24px; background: rgba(255, 61, 90, 0.08); border: 1px solid var(--red); color: var(--red);
        }
        .hero-overlay h2 {
            color: var(--cyan);
        }

        /* ── Glitch ACCESS DENIED overlay ──────────────────── */
        .glitch-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.92);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
        }
        .glitch-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .glitch-overlay .scanlines {
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(255, 59, 92, 0.04) 2px,
                rgba(255, 59, 92, 0.04) 4px
            );
            pointer-events: none;
        }
        .glitch-text {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(2.5rem, 8vw, 5rem);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 6px;
            color: #ff3b5c;
            position: relative;
            text-shadow: 0 0 40px rgba(255, 59, 92, 0.5);
            animation: glitchFlicker 0.15s linear infinite;
        }
        .glitch-text::before,
        .glitch-text::after {
            content: attr(data-text);
            position: absolute;
            left: 0; top: 0;
            width: 100%; height: 100%;
        }
        .glitch-text::before {
            color: #00f5d4;
            animation: glitchClipTop 0.4s steps(2) infinite;
            clip-path: polygon(0 0, 100% 0, 100% 40%, 0 40%);
        }
        .glitch-text::after {
            color: #ff6b35;
            animation: glitchClipBottom 0.4s steps(2) infinite;
            clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
        }
        .glitch-sub {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #ff3b5c;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-top: 16px;
            opacity: 0.7;
        }
        .glitch-shield {
            font-size: 3rem;
            margin-bottom: 16px;
            animation: shieldPulse 0.6s ease-in-out infinite alternate;
        }

        @keyframes glitchFlicker {
            0% { opacity: 1; transform: translate(0); }
            20% { opacity: 0.8; transform: translate(-2px, 1px); }
            40% { opacity: 1; transform: translate(1px, -1px); }
            60% { opacity: 0.9; transform: translate(-1px, 2px); }
            80% { opacity: 1; transform: translate(2px, -1px); }
            100% { opacity: 0.85; transform: translate(0); }
        }
        @keyframes glitchClipTop {
            0% { transform: translate(-3px, -1px); }
            50% { transform: translate(3px, 1px); }
            100% { transform: translate(-2px, 0); }
        }
        @keyframes glitchClipBottom {
            0% { transform: translate(3px, 1px); }
            50% { transform: translate(-3px, -1px); }
            100% { transform: translate(2px, 0); }
        }
        @keyframes shieldPulse {
            from { transform: scale(1); filter: drop-shadow(0 0 15px rgba(255,59,92,0.4)); }
            to { transform: scale(1.15); filter: drop-shadow(0 0 30px rgba(255,59,92,0.7)); }
        }

        /* Screen shake on the container */
        @keyframes screenShake {
            0%, 100% { transform: translate(0); }
            10% { transform: translate(-6px, 3px); }
            20% { transform: translate(5px, -4px); }
            30% { transform: translate(-3px, 2px); }
            40% { transform: translate(4px, -2px); }
            50% { transform: translate(-2px, 4px); }
            60% { transform: translate(3px, -3px); }
            70% { transform: translate(-4px, 1px); }
            80% { transform: translate(2px, -2px); }
            90% { transform: translate(-1px, 3px); }
        }
        .shake { animation: screenShake 0.5s ease-in-out; }
    </style>
	<link rel="stylesheet" href="assets/css/light-mode-industrial.css">
</head>
<body>

    <!-- ACCESS DENIED glitch overlay -->
    <div class="glitch-overlay" id="glitchOverlay">
        <div class="scanlines"></div>
        <div class="glitch-shield">🛡️</div>
        <div class="glitch-text" data-text="ACCESS DENIED">ACCESS DENIED</div>
        <p class="glitch-sub">// unauthorized credentials</p>
    </div>

    <a href="index.html" class="back-link">← Back to Store</a>

    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" style="position: absolute; top: 1.5rem; right: 2rem;">
        <i class="fas fa-sun icon-sun"></i>
        <i class="fas fa-moon icon-moon"></i>
    </button>
    <div id="google_translate_element" class="nav-translate" style="position: absolute; top: 1.5rem; right: 6rem;"></div>


    <div class="container login-container" id="loginContainer">
        <!-- ── Côté image ─────────────────────────────────────── -->
        <div class="hero-side">
            <img src="admin_bg.png" alt="Admin Dashboard Background">
            <div class="hero-overlay" style="background: rgba(0,0,0,0.8); z-index: 10;">
                <h2 style="color: var(--cyan);">Admin Portal</h2>
                <p>Restricted access. System control, analytics, and catalog management.</p>
            </div>
        </div>

        <!-- ── Côté formulaire ────────────────────────────────── -->
        <div class="inscription">
            <?php if ($adminSetupNeeded): ?>
                <div class="alert-error" role="alert" style="margin-bottom:16px;">
                    <span aria-hidden="true">⚠</span>
                    <span>No admin account found. Create one via CLI: <code style="font-size:0.85em;">php create-admin.php email@example.com password "Name"</code></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors["general"])): ?>
                <div class="alert-error" role="alert" aria-live="polite">
                    <span aria-hidden="true">❌</span>
                    <span><?= htmlspecialchars($errors["general"]) ?></span>
                </div>
            <?php endif; ?>

            <form name="adminlogin" method="post" action="adminlogin.php" novalidate <?= $success ? 'style="display:none"' : '' ?>>
                <?= csrfField() ?>
                <h3 id="myH3">Administrator Mode</h3>
                <p class="subtitle">Highly secure connection required.</p>

                <!-- ── Email ──────────────────────────────────── -->
                <div class="<?= grp('email', $errors) ?>">
                    <label for="login-email">Admin Email</label>
                    <input type="email" name="email" id="login-email" class="hh"
                           placeholder="admin@marocpc.com" value="<?= htmlspecialchars($email) ?>" required>
                    <span class="error-msg" id="err-login-email"><?= $errors["email"] ?? "Please enter a valid email" ?></span>
                </div>

                <!-- ── Mot de passe ───────────────────────────── -->
                <div class="<?= grp('pass', $errors) ?>">
                    <label for="login-pass">Master Password</label>
                    <div class="password-wrap">
                        <input type="password" name="pass" id="login-pass" class="hh" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" id="loginTogglePass" aria-label="Show password">👁</button>
                    </div>
                    <span class="error-msg" id="err-login-pass"><?= $errors["pass"] ?? "Password is required" ?></span>
                </div>

                <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
                    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>"></div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="Bou" id="loginBtn" style="background: var(--cyan); color: #000;">System Access</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="assets/js/login.js" defer></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>

    <?php if (!empty($errors["general"])): ?>
    <script>
    (function() {
        const overlay = document.getElementById('glitchOverlay');
        const container = document.getElementById('loginContainer');

        // Show glitch overlay immediately
        overlay.classList.add('active');

        // Add screen shake to form
        container.classList.add('shake');

        // Dismiss after 2 seconds
        setTimeout(function() {
            overlay.classList.remove('active');
        }, 2000);

        // Remove shake class after animation ends
        container.addEventListener('animationend', function() {
            container.classList.remove('shake');
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>

