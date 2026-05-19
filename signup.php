<?php
require_once 'config.php';
require_once 'mailer.php';
require_once 'src/Services/two-factor-helpers.php';

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

$errors = [];
$success = false;
$fullname = $email = $dob = $adresse = $telephone = "";
$verify_method = "email";

if ($requestMethod !== "POST") {
    $oauthError = $_GET['error'] ?? '';
    $providerLabels = [
        'facebook' => 'Facebook',
        'xbox' => 'Xbox',
        'apple' => 'Apple',
    ];

    if ($oauthError === 'google_auth_failed') {
        $errors["general"] = "Google signup failed. Please try again or use the form.";
    } elseif ($oauthError === 'provider_unavailable') {
        $provider = strtolower((string) ($_GET['provider'] ?? ''));
        $providerName = $providerLabels[$provider] ?? 'This provider';
        $errors["general"] = $providerName . " is not configured for signup yet.";
    }
}

if ($requestMethod === "POST") {

    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $errors["general"] = "Session invalide, veuillez réessayer.";
    }

    if (empty($errors) && defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== '') {
        $cfToken = $_POST['cf-turnstile-response'] ?? '';
        if (!verifyTurnstile($cfToken)) {
            $errors["general"] = "CAPTCHA verification failed. Please try again.";
        }
    }

    // ── Sanitize ──────────────────────────────────────────
    $fullname = trim($_POST["fullname"] ?? "");
    $fullname = strip_tags($fullname);
    $fullname = preg_replace('/[^\p{L}\p{N}\p{Z}\p{Pd}\p{Pc}]/u', '', $fullname);
    $fullname = trim($fullname);

    $email = trim($_POST["email"] ?? "");
    $pass_raw = $_POST["pass"] ?? "";
    $dob = trim($_POST["dob"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $verify_method = $_POST["verify_method"] ?? "email";

    // ── Validate ──────────────────────────────────────────
    if (empty($errors) && mb_strlen($fullname) < 2) {
        $errors["fullname"] = "Please enter your full name (letters and spaces only).";
    }

    if (empty($errors) && mb_strlen($adresse) < 5) {
        $errors["adresse"] = "Address must be at least 5 characters.";
    }

    if (empty($errors) && (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors["email"] = "Invalid email address.";
    }

    if (
        empty($errors) && (strlen($pass_raw) < 8 ||
            !preg_match('/[0-9]/', $pass_raw) ||
            !preg_match('/[^a-zA-Z0-9]/', $pass_raw))
    ) {
        $errors["pass"] = "Min 8 chars, 1 number, and 1 symbol.";
    }

    if (empty($errors) && (empty($dob) || $dob >= date('Y-m-d'))) {
        $errors["dob"] = "Birth date is required and must be in the past.";
    }

    if (empty($errors) && in_array($verify_method, ['whatsapp', 'sms'])) {
        $telephone = twoFactorNormalizePhone($telephone);
        if ($telephone === '') {
            $errors["telephone"] = "Invalid phone number for " . ($verify_method === 'whatsapp' ? 'WhatsApp' : 'SMS') . ".";
        }
    }

    // ── Check duplicate email ─────────────────────────────
    if (empty($errors)) {
        $stmt = db()->prepare("SELECT id_client FROM Client WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors["email"] = "This email is already in use.";
        }
    }

    // ── Check duplicate phone ─────────────────────────────
    if (empty($errors) && !empty($telephone)) {
        $stmt = db()->prepare("SELECT id_client FROM Client WHERE telephone = ?");
        $stmt->execute([$telephone]);
        if ($stmt->fetch()) {
            $errors["telephone"] = "This phone number is already in use.";
        }
    }

    // ── Insert + send verification email ──────────────────
    if (empty($errors)) {
        try {
            $pdo = db();

            // Insert user with email_verified = 0
            $stmt = $pdo->prepare(
                "INSERT INTO Client (nom, email, mot_de_passe, date_naissance, moyen_paiement, adresse, telephone, email_verified)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
            );
            $stmt->execute([
                $fullname,
                $email,
                password_hash($pass_raw, PASSWORD_DEFAULT),
                $dob,
                'not_set',
                $adresse,
                $telephone
            ]);

            if ($verify_method === 'whatsapp' || $verify_method === 'sms') {
                $code = (string) random_int(100000, 999999);
                $tokenHash = password_hash($code, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                $stmt = $pdo->prepare("INSERT INTO email_verifications (email, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $tokenHash, $expiresAt]);
                
                if ($verify_method === 'whatsapp') {
                    sendTwoFactorCodeWhatsApp($telephone, $fullname, $code);
                } else {
                    sendTwoFactorCodeSMS($telephone, $fullname, $code);
                }
                
                session_start();
                $_SESSION['verify_account_email'] = $email;
                $_SESSION['verify_account_method'] = $verify_method;
                header("Location: verify-account-code.php");
                exit();
            } else {
                // Generate verification token for Email
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("INSERT INTO email_verifications (email, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $tokenHash, $expiresAt]);

                // Send verification email
                sendVerificationEmail($email, $fullname, $token);

                $success = true;
                $fullname = $email = $dob = $adresse = $telephone = "";
            }
        } catch (PDOException $e) {
            $errors["general"] = DEV_MODE
                ? "DB Error: " . $e->getMessage()
                : "Error during registration. Please try again.";
        }
    }
}

function grp(string $field, array $errors): string
{
    return isset($errors[$field]) ? 'form-group invalid' : 'form-group';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an account — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
    <?php endif; ?>

    <style>
        /* ── Styles manquants dans signup.css : alertes + surcharge invalid ── */

        /* Force l'affichage du .error-msg quand PHP ajoute .invalid */
        .form-group.invalid .error-msg {
            display: block;
        }

        /* Alertes globales */
        .alert-success,
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .alert-success {
            background: rgba(0, 230, 118, 0.08);
            border: 1px solid var(--green);
            color: var(--green);
        }

        .alert-success a {
            color: var(--green);
            font-weight: 800;
            text-decoration: underline;
            margin-left: auto;
        }

        .alert-error {
            background: rgba(255, 61, 90, 0.08);
            border: 1px solid var(--red);
            color: var(--red);
        }
    </style>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
</head>

<!-- ── Confirmation Modal ──────────────────────────────────────── -->
<div class="confirm-overlay" id="confirmOverlay"></div>
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-header">
        <h4>Confirm your details</h4>
        <button type="button" class="confirm-close" id="confirmClose">✕</button>
    </div>
    <div class="confirm-body">
        <p class="confirm-subtitle">Please verify your information before creating your account.</p>
        <ul class="confirm-list">
            <li>
                <span class="confirm-label">Full Name</span>
                <span class="confirm-value" id="cf-name">—</span>
            </li>
            <li>
                <span class="confirm-label">Email</span>
                <span class="confirm-value" id="cf-email">—</span>
            </li>
            <li>
                <span class="confirm-label">Password</span>
                <span class="confirm-value" id="cf-pass">—</span>
            </li>
            <li>
                <span class="confirm-label">Date of Birth</span>
                <span class="confirm-value" id="cf-dob">—</span>
            </li>
            <li>
                <span class="confirm-label">Phone</span>
                <span class="confirm-value" id="cf-telephone">—</span>
            </li>
            <li>
                <span class="confirm-label">Address</span>
                <span class="confirm-value" id="cf-adresse">—</span>
            </li>
        </ul>
    </div>
    <div class="confirm-actions">
        <button type="button" class="btn-secondary" id="confirmEdit">← Edit</button>
        <button type="button" class="Bou" id="confirmSubmit">Create Account →</button>
    </div>
</div>

<body>

    <a href="index.html" class="back-link">← Back to Store</a>

    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme"
        style="position: absolute; top: 1.5rem; right: 2rem;">
        <i class="fas fa-sun icon-sun"></i>
        <i class="fas fa-moon icon-moon"></i>
    </button>
    <div id="google_translate_element" class="nav-translate" style="position: absolute; top: 1.5rem; right: 6rem;">
    </div>


    <div class="container">

        <!-- ── Côté image ─────────────────────────────────────── -->
        <div class="hero-side">
            <img src="signup.png" alt="Gaming setup workspace">
            <div class="hero-overlay">
                <h2>Join the Elite</h2>
                <p>Unlock exclusive deals, track your orders, and enjoy member pricing.</p>
            </div>
        </div>

        <!-- ── Côté formulaire ────────────────────────────────── -->
        <div class="inscription">

            <?php if ($success): ?>
                <div class="alert-success" style="flex-direction:column;align-items:flex-start;">
                    <span>✅ Account successfully created!</span>
                    <span style="font-size:0.85rem;font-weight:400;margin-top:6px;color:var(--muted,#b0b8c8);">
                        📧 A verification email has been sent. Check your inbox (and spam) to activate your account.
                    </span>
                    <a href="login.php" style="margin-top:12px;">Sign In →</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors["general"])): ?>
                <div class="alert-error">
                    ❌ <?= htmlspecialchars($errors["general"]) ?>
                </div>
            <?php endif; ?>

            <form name="signup" method="post" action="signup.php" novalidate>
                <?= csrfField() ?>
                <h3 id="myH3">Create an Account</h3>
                <p class="subtitle">Start your journey with Maroc PC</p>

                <!-- ── Nom complet ────────────────────────────── -->
                <div class="<?= grp('fullname', $errors) ?>">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="hh" placeholder="John Doe"
                        value="<?= htmlspecialchars($fullname) ?>" required>
                    <span class="error-msg" id="err-name">
                        <?= isset($errors["fullname"])
                            ? htmlspecialchars($errors["fullname"])
                            : "Please enter your full name" ?>
                    </span>
                </div>

                <!-- ── Email ──────────────────────────────────── -->
                <div class="<?= grp('email', $errors) ?>">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="hh" placeholder="john@example.com"
                        value="<?= htmlspecialchars($email) ?>" required>
                    <span class="error-msg" id="err-email">
                        <?= isset($errors["email"])
                            ? htmlspecialchars($errors["email"])
                            : "Please enter a valid email" ?>
                    </span>
                </div>

                <!-- ── Mot de passe ───────────────────────────── -->
                <div class="<?= grp('pass', $errors) ?>">
                    <label for="pass">Password</label>
                    <div class="password-wrap">
                        <input type="password" name="pass" id="pass" class="hh" placeholder="••••••••" required
                            minlength="8">
                        <button type="button" class="toggle-pass" id="togglePass"
                            aria-label="Show password">👁</button>
                    </div>
                    <span class="hint">At least 8 characters with a number and symbol</span>
                    <span class="error-msg" id="err-pass">
                        <?= isset($errors["pass"])
                            ? htmlspecialchars($errors["pass"])
                            : "Password is too weak" ?>
                    </span>
                </div>

                <!-- ── Date de naissance ──────────────────────── -->
                <div class="<?= grp('dob', $errors) ?>">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="hh" value="<?= htmlspecialchars($dob) ?>" required>
                    <span class="error-msg" id="err-dob">
                        <?= isset($errors["dob"])
                            ? htmlspecialchars($errors["dob"])
                            : "Required" ?>
                    </span>
                </div>

                <!-- ── Adresse ──────────────────────────────────────── -->
                <div class="<?= grp('adresse', $errors) ?>">
                    <label for="adresse">Shipping Address</label>
                    <input type="text" name="adresse" id="adresse" class="hh"
                        placeholder="123 Example Street, City" value="<?= htmlspecialchars($adresse) ?>" required>
                    <span class="error-msg" id="err-adresse">
                        <?= isset($errors["adresse"])
                            ? htmlspecialchars($errors["adresse"])
                            : "Address is required" ?>
                    </span>
                </div>

                <!-- ── Téléphone ──────────────────────────────────────── -->
                <div class="<?= grp('telephone', $errors) ?>">
                    <label for="telephone">Phone Number</label>
                    <input type="tel" name="telephone" id="telephone" class="hh"
                        placeholder="+212600000000" value="<?= htmlspecialchars($telephone) ?>" required>
                    <span class="error-msg" id="err-telephone">
                        <?= isset($errors["telephone"])
                            ? htmlspecialchars($errors["telephone"])
                            : "Phone number is required" ?>
                    </span>
                </div>

                <!-- ── Méthode de vérification ──────────────────────────────────────── -->
                <div class="form-group">
                    <label>How would you like to verify your account?</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="verify_method" value="email" <?= $verify_method === 'email' ? 'checked' : '' ?>>
                            Email
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="verify_method" value="whatsapp" <?= $verify_method === 'whatsapp' ? 'checked' : '' ?>>
                            WhatsApp
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="verify_method" value="sms" <?= $verify_method === 'sms' ? 'checked' : '' ?>>
                            SMS
                        </label>
                    </div>
                </div>

                <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
                    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>"></div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="Bou">Create Account</button>
                </div>

                <div class="social-login">
                    <p>Or sign up with</p>
                    <div class="social-provider-grid">
                        <a href="google-callback.php?next=index.html" class="social-provider provider-google">
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </a>
                        <a href="facebook-login.php" class="social-provider provider-facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="discord-login.php" class="social-provider provider-discord">
                            <i class="fab fa-discord"></i>
                            <span>Discord</span>
                        </a>
                        <a href="xbox-login.php" class="social-provider provider-xbox">
                            <i class="fab fa-xbox"></i>
                            <span>Xbox</span>
                        </a>

                    </div>
                </div>

                <p class="login-link" style="margin-top: 1.5rem;">
                    Already have an account? <a href="login.php">Sign In</a>
                </p>

            </form>
        </div>
    </div>

    <script src="assets/js/translate.js"></script>
    <script src="assets/js/form.js" defer></script>
    <script src="assets/js/theme.js" defer></script>
</body>

</html>
