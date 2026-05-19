<?php
require_once 'config.php';
require_once 'mailer.php';
require_once 'two-factor-helpers.php';

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
const LOGIN_MAX_FAILED_ATTEMPTS = 3;
const LOGIN_LOCK_MINUTES = 15;

// ── Validate redirect target (prevent open redirect) ─────────────
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.html');
// Only allow relative paths — block absolute URLs, protocol-relative, and javascript:
if (preg_match('#^(https?://|//|javascript:)#i', $next) || strpos($next, '..') !== false) {
    $next = 'index.html';
}

// ─── Si déjà connecté → rediriger ────────────────────────────────
if (isset($_SESSION["client_id"])) {
    header("Location: $next");
    exit();
}

// ─── Variables ───────────────────────────────────────────────────
$errors   = [];
$success  = false;
$email    = "";

if ($requestMethod !== "POST") {
    $oauthError = $_GET['error'] ?? '';
    $providerLabels = [
        'facebook' => 'Facebook',
        'xbox' => 'Xbox',
        'apple' => 'Apple',
    ];

    if ($oauthError === 'google_auth_failed') {
        $errors["general"] = "Google login failed. Please try again or use your email.";
    } elseif ($oauthError === 'provider_unavailable') {
        $provider = strtolower((string) ($_GET['provider'] ?? ''));
        $providerName = $providerLabels[$provider] ?? 'This provider';
        $errors["general"] = $providerName . " is not configured for login yet.";
    }
}

// ─── Traitement du formulaire ─────────────────────────────────────
if ($requestMethod === "POST") {

    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $errors["general"] = "Session invalide, veuillez réessayer.";
    }

    $email    = trim($_POST["email"] ?? "");
    $pass_raw = trim($_POST["pass"]  ?? "");
    $remember = isset($_POST["remember"]);

    // ── Validation basique ────────────────────────────────────────
    if (empty($errors) && (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors["email"] = "Invalid email address.";
    }

    if (empty($errors) && empty($pass_raw)) {
        $errors["pass"] = "Password is required.";
    }

    // ── Vérification en base ──────────────────────────────────────
    if (empty($errors)) {
        $pdo = db();
        ensureLoginLockoutColumns($pdo);
        ensureLoginAttemptsTable($pdo);
        twoFactorEnsureColumns($pdo);

        $attemptKey = loginAttemptKey($email);
        $globalLockedUntil = loginLockedUntil($pdo, $attemptKey);
        
        if ($globalLockedUntil !== null && $globalLockedUntil <= new DateTime()) {
            clearGlobalLoginAttempts($pdo, $attemptKey);
            $globalLockedUntil = null;
        }

        if ($globalLockedUntil !== null && $globalLockedUntil > new DateTime()) {
            $errors["general"] = "Too many attempts. Try again after " . $globalLockedUntil->format('H:i') . ".";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id_client, nom, email, telephone, mot_de_passe, email_verified, deleted_at, failed_login_attempts, locked_until, two_factor_enabled, two_factor_method, two_factor_totp_secret, is_suspended, suspension_reason FROM Client WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && !empty($client["is_suspended"])) {
            $errors["general"] = "Your account has been suspended. Reason: " . ($client["suspension_reason"] ?: "Not specified");
        }

        if (empty($errors)) {
            $lockedUntil = ($client && !empty($client["locked_until"])) ? new DateTime($client["locked_until"]) : null;
        $now = new DateTime();

        if ($client && $lockedUntil && $lockedUntil <= $now) {
            resetFailedLogins($pdo, (int) $client["id_client"]);
            $client["failed_login_attempts"] = 0;
            $client["locked_until"] = null;
            $lockedUntil = null;
        }

        if ($client && $lockedUntil && $lockedUntil > $now) {
            $errors["general"] = "Too many attempts. Try again after " . $lockedUntil->format('H:i') . ".";
        } elseif (!$client || !password_verify($pass_raw, $client["mot_de_passe"])) {
            registerGlobalFailedLogin($pdo, $attemptKey, $email);
            if ($client) {
                $isNowLocked = registerFailedLogin($pdo, (int) $client["id_client"], (int) $client["failed_login_attempts"]);
                if ($isNowLocked) {
                    $lockUntil = (new DateTime())->modify('+' . LOGIN_LOCK_MINUTES . ' minutes');
                    $errors["general"] = "Too many attempts. Try again after " . $lockUntil->format('H:i') . ".";
                }
            }
            // Check global lock after registering failed attempt
            $newGlobalLockedUntil = loginLockedUntil($pdo, $attemptKey);
            if ($newGlobalLockedUntil !== null && $newGlobalLockedUntil > new DateTime()) {
                $errors["general"] = "Too many attempts. Try again after " . $newGlobalLockedUntil->format('H:i') . ".";
            }
            if (empty($errors["general"])) {
                $errors["general"] = "Incorrect email or password.";
            }
        } elseif (empty($client["email_verified"])) {
            resetFailedLogins($pdo, (int) $client["id_client"]);
            $errors["general"] = "Your email is not verified yet. Check your inbox.";
            $showResendLink = true;
        } elseif (!empty($client["deleted_at"])) {
            resetFailedLogins($pdo, (int) $client["id_client"]);
            $deletedAt = new DateTime($client["deleted_at"]);
            $deadline = (clone $deletedAt)->modify('+5 days');
            if ($now > $deadline) {
                $errors["general"] = "This account has been deleted. Contact support for assistance.";
            } else {
                // Still in grace period — allow login, redirect to account
                if (!empty($client['two_factor_enabled'])) {
                    startTwoFactorLogin($client, 'account.php?tab=security', false, $_POST['two_factor_method'] ?? null);
                }
                session_regenerate_id(true);
                $_SESSION["client_id"]  = $client["id_client"];
                $_SESSION["client_nom"] = $client["nom"];
                $_SESSION["client_email"] = $email;
                applyLoginSessionLifetime(false);
                $success = true;
                header("Location: account.php?tab=security");
                exit();
            }
        } else {
            resetFailedLogins($pdo, (int) $client["id_client"]);
            clearGlobalLoginAttempts($pdo, $attemptKey);
            // ── Connexion réussie ─────────────────────────────────
            if (!empty($client['two_factor_enabled'])) {
                startTwoFactorLogin($client, $next, $remember, $_POST['two_factor_method'] ?? null);
            }
            session_regenerate_id(true);
            $_SESSION["client_id"]  = $client["id_client"];
            $_SESSION["client_nom"] = $client["nom"];
            $_SESSION["client_email"] = $email;

            // ── Record device fingerprint ──
            try {
                $fpIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $fpUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $fpHash = hash('sha256', $fpIp . '|' . $fpUa);
                $fpStmt = $pdo->prepare("SELECT id FROM login_device_fingerprints WHERE client_id = ? AND device_hash = ?");
                $fpStmt->execute([(int) $client["id_client"], $fpHash]);
                if ($fpStmt->fetch()) {
                    $pdo->prepare("UPDATE login_device_fingerprints SET last_login_at = NOW() WHERE client_id = ? AND device_hash = ?")->execute([(int) $client["id_client"], $fpHash]);
                } else {
                    $pdo->prepare("INSERT INTO login_device_fingerprints (client_id, ip_address, user_agent, device_hash) VALUES (?, ?, ?, ?)")->execute([(int) $client["id_client"], $fpIp, $fpUa, $fpHash]);
                }
            } catch (Throwable $e) { /* Never block the login */ }

            // Remember me → cookie 30 jours
            applyLoginSessionLifetime($remember);

            $success = true;

            header("Location: $next");
            exit();
            }
        }
    }
}

// ─── Helper CSS ───────────────────────────────────────────────────
function grp(string $field, array $errors): string {
    return isset($errors[$field]) ? 'form-group invalid' : 'form-group';
}

function ensureLoginLockoutColumns(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE Client ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0");
    } catch (PDOException $e) {
        // Column already exists.
    }

    try {
        $pdo->exec("ALTER TABLE Client ADD COLUMN locked_until DATETIME DEFAULT NULL");
    } catch (PDOException $e) {
        // Column already exists.
    }

    try {
        $pdo->exec("ALTER TABLE Client ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0");
    } catch (PDOException $e) {
        // Column already exists.
    }
}

function startTwoFactorLogin(array $client, string $next, bool $remember, ?string $preferredMethod): void
{
    $method = twoFactorChooseMethod($client, $preferredMethod);
    $_SESSION['two_factor_login'] = [
        'client_id' => (int) $client['id_client'],
        'name' => (string) $client['nom'],
        'email' => (string) $client['email'],
        'phone' => (string) ($client['telephone'] ?? ''),
        'method' => $method,
        'next' => $next,
        'remember' => $remember,
        'code_hash' => null,
        'expires_at' => time() + 300,
        'attempts' => 0,
    ];

    if ($method !== 'authenticator') {
        $code = (string) random_int(100000, 999999);
        $_SESSION['two_factor_login']['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
        twoFactorSendCode($method, $client, $code);
    }

    header('Location: verify-2fa.php');
    exit();
}

function registerFailedLogin(PDO $pdo, int $clientId, int $currentAttempts): bool
{
    $nextAttempts = $currentAttempts + 1;

    if ($nextAttempts >= LOGIN_MAX_FAILED_ATTEMPTS) {
        $stmt = $pdo->prepare(
            "UPDATE Client
             SET failed_login_attempts = ?,
                 locked_until = DATE_ADD(NOW(), INTERVAL " . LOGIN_LOCK_MINUTES . " MINUTE)
             WHERE id_client = ?"
        );
        $stmt->execute([$nextAttempts, $clientId]);
        return true;
    }

    $stmt = $pdo->prepare(
        "UPDATE Client
         SET failed_login_attempts = ?, locked_until = NULL
         WHERE id_client = ?"
    );
    $stmt->execute([$nextAttempts, $clientId]);
    return false;
}

function resetFailedLogins(PDO $pdo, int $clientId): void
{
    $stmt = $pdo->prepare(
        "UPDATE Client
         SET failed_login_attempts = 0, locked_until = NULL
         WHERE id_client = ?"
    );
    $stmt->execute([$clientId]);
}

function loginClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function loginAttemptKey(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . '|' . loginClientIp());
}

function ensureLoginAttemptsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS client_login_attempts (
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

function loginLockedUntil(PDO $pdo, string $attemptKey): ?DateTime
{
    $stmt = $pdo->prepare("SELECT locked_until FROM client_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
    $lockedUntil = $stmt->fetchColumn();
    return $lockedUntil ? new DateTime((string) $lockedUntil) : null;
}

function registerGlobalFailedLogin(PDO $pdo, string $attemptKey, string $email): void
{
    $stmt = $pdo->prepare("SELECT failed_attempts FROM client_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
    $attempts = $stmt->fetchColumn();

    if ($attempts === false) {
        $stmt = $pdo->prepare(
            \"INSERT INTO client_login_attempts (attempt_key, email, ip_address, failed_attempts, last_failed_at)
             VALUES (?, ?, ?, 1, NOW())\"
        );
        $stmt->execute([$attemptKey, strtolower(trim($email)), loginClientIp()]);
        return;
    }

    $nextAttempts = (int) $attempts + 1;
    $lockSql = $nextAttempts >= LOGIN_MAX_FAILED_ATTEMPTS
        ? \", locked_until = DATE_ADD(NOW(), INTERVAL \" . LOGIN_LOCK_MINUTES . \" MINUTE)\"
        : \", locked_until = NULL\";

    $stmt = $pdo->prepare(
        \"UPDATE client_login_attempts
         SET failed_attempts = ?, last_failed_at = NOW() {$lockSql}
         WHERE attempt_key = ?\"
    );
    $stmt->execute([$nextAttempts, $attemptKey]);
}

function clearGlobalLoginAttempts(PDO $pdo, string $attemptKey): void
{
    $stmt = $pdo->prepare("DELETE FROM client_login_attempts WHERE attempt_key = ?");
    $stmt->execute([$attemptKey]);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>

    <style>
        /* ── Surcharge : affiche .error-msg quand PHP ajoute .invalid ── */
        .form-group.invalid .error-msg {
            display: block;
        }

        /* ── Alerte générale erreur ─────────────────────────────────── */
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

        /* ── Toast succès ───────────────────────────────────────────── */
        .alert-success-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 24px;
            background: rgba(0, 230, 118, 0.08);
            border: 1px solid var(--green);
            color: var(--green);
        }
    </style>
	<link rel="stylesheet" href="assets/css/light-mode-industrial.css">
</head>
<body>

    <a href="index.html" class="back-link">← Back to Store</a>

    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" style="position: absolute; top: 1.5rem; right: 2rem;">
        <i class="fas fa-sun icon-sun"></i>
        <i class="fas fa-moon icon-moon"></i>
    </button>
    <div id="google_translate_element" class="nav-translate" style="position: absolute; top: 1.5rem; right: 6rem;"></div>


    <div class="container login-container">

        <!-- ── Côté image ─────────────────────────────────────── -->
        <div class="hero-side">
            <img src="signup.png" alt="Gaming setup workspace">
            <div class="hero-overlay">
                <h2>Welcome Back</h2>
                <p>Access your orders, saved builds, and member-exclusive deals.</p>
            </div>
        </div>

        <!-- ── Côté formulaire ────────────────────────────────── -->
        <div class="inscription">

            <?php if ($success): ?>
                <div class="alert-success-toast">
                    ✅ Login successful! Redirecting…
                </div>
            <?php endif; ?>

            <?php if (!empty($errors["general"])): ?>
                <div class="alert-error">
                    ❌ <?= htmlspecialchars($errors["general"]) ?>
                    <?php if (!empty($showResendLink)): ?>
                        <br><a href="resend-verification.php?email=<?= urlencode($email) ?>" style="color:#00f5d4;text-decoration:underline;font-size:0.85rem;">Resend verification email →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form name="login" method="post" action="login.php?next=<?= urlencode($next) ?>" novalidate
                  <?= $success ? 'style="display:none"' : '' ?>>
                <?= csrfField() ?>
                <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

                <h3 id="myH3">Sign In</h3>
                <p class="subtitle">Enter your credentials to continue</p>

                <!-- ── Email ──────────────────────────────────── -->
                <div class="<?= grp('email', $errors) ?>">
                    <label for="login-email">Email Address</label>
                    <input type="email" name="email" id="login-email" class="hh"
                           placeholder="john@example.com"
                           value="<?= htmlspecialchars($email) ?>" required>
                    <span class="error-msg" id="err-login-email">
                        <?= isset($errors["email"])
                            ? htmlspecialchars($errors["email"])
                            : "Please enter a valid email" ?>
                    </span>
                </div>

                <!-- ── Mot de passe ───────────────────────────── -->
                <div class="<?= grp('pass', $errors) ?>">
                    <label for="login-pass">Password</label>
                    <div class="password-wrap">
                        <input type="password" name="pass" id="login-pass" class="hh"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" id="loginTogglePass"
                                aria-label="Show password">👁</button>
                    </div>
                    <span class="error-msg" id="err-login-pass">
                        <?= isset($errors["pass"])
                            ? htmlspecialchars($errors["pass"])
                            : "Password is required" ?>
                    </span>
                </div>


                <div class="form-options">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" id="remember"
                               <?= isset($_POST["remember"]) ? "checked" : "" ?>>
                        <span class="check-box"></span>
                        <span class="remember-text">Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>

                <div class="form-actions">
                    <button type="submit" class="Bou" id="loginBtn">Sign In</button>
                </div>

                <div class="social-login">
                    <p>Or sign in with</p>
                    <div class="social-provider-grid">
                        <a href="google-callback.php?next=<?= urlencode($next) ?>" class="social-provider provider-google">
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </a>
                        <a href="facebook-login.php?next=<?= urlencode($next) ?>" class="social-provider provider-facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="discord-login.php?next=<?= urlencode($next) ?>" class="social-provider provider-discord">
                            <i class="fab fa-discord"></i>
                            <span>Discord</span>
                        </a>
                        <a href="xbox-login.php?next=<?= urlencode($next) ?>" class="social-provider provider-xbox">
                            <i class="fab fa-xbox"></i>
                            <span>Xbox</span>
                        </a>

                    </div>
                </div>

                <p class="login-link" style="margin-top: 1.5rem;">
                    New here? <a href="signup.php">Create an account</a>
                </p>

            </form>
        </div>
    </div>

    <!-- ── Toast Notification ─────────────────────────────────── -->
    <div class="toast <?= $success ? 'show' : '' ?>" id="loginToast">
        <i>⚡</i>
        <span id="loginToastMsg">
            <?php if ($success): ?>
                Welcome, <?= htmlspecialchars($_SESSION["client_nom"]) ?>!
            <?php else: ?>
                Welcome!
            <?php endif; ?>
        </span>
    </div>

    <script src="assets/js/translate.js"></script>
    <script src="assets/js/login.js" defer></script>
    <script src="assets/js/theme.js" defer></script>
    
    <script>
        (function() {
            // 1. If PHP logged us in successfully, plant the footprint
            <?php if ($success): ?>
                localStorage.setItem('has_active_session', '1');
            <?php endif; ?>

            // 2. If we are on the login page, and we have a footprint, it means we got disconnected!
            <?php if (!$success): ?>
                // Also check if PHP explicitly told us session expired via GET parameter
                const isGetExpired = <?= (isset($_GET['session_expired']) && $_GET['session_expired'] == 1) ? 'true' : 'false' ?>;
                
                if (localStorage.getItem('has_active_session') === '1' || isGetExpired) {
                    // Remove footprint so we don't show toast again on refresh
                    localStorage.removeItem('has_active_session');
                    // Empty the shopping cart because session expired
                    localStorage.removeItem('cart');
                    
                    const displayToast = () => {
                        setTimeout(() => {
                            const toast = document.getElementById('loginToast');
                            const toastMsg = document.getElementById('loginToastMsg');
                            if (toast && toastMsg) {
                                toastMsg.textContent = 'Your session has expired. Please sign in again.';
                                toast.style.borderColor = 'var(--red)';
                                const icon = toast.querySelector('i');
                                if (icon) {
                                    icon.textContent = '✕';
                                    icon.style.color = 'var(--red)';
                                }
                                toast.classList.add('show');
                                setTimeout(() => toast.classList.remove('show'), 5000);
                            }
                        }, 100);
                    };
                    
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', displayToast);
                    } else {
                        displayToast();
                    }
                }
            <?php endif; ?>
        })();
    </script>
</body>
</html>
