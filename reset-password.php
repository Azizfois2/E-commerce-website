<?php
require_once 'config.php';
require_once 'password-reset-helpers.php';

$token = (string) ($_GET['token'] ?? '');
$validation = validatePasswordResetToken(db(), $token);
$valid = (bool) ($validation['valid'] ?? false);
$email = $valid ? (string) $validation['email'] : '';
$error = $valid ? '' : (string) ($validation['error'] ?? 'This reset link is invalid.');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>
    <a href="login.php" class="back-link">Back to Sign In</a>

    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" style="position:absolute;top:1.5rem;right:2rem;">
        <i class="fas fa-sun icon-sun"></i>
        <i class="fas fa-moon icon-moon"></i>
    </button>
    <div id="google_translate_element" class="nav-translate" style="position:absolute;top:1.5rem;right:6rem;"></div>

    <div class="container login-container">
        <div class="hero-side">
            <img src="signup.png" alt="Gaming setup">
            <div class="hero-overlay">
                <h2>Secure Reset</h2>
                <p>Choose a strong password you have not used before.</p>
            </div>
        </div>

        <div class="inscription">
            <?php if (!$valid): ?>
                <h3 id="myH3">Reset Link Problem</h3>
                <p class="subtitle" style="color:var(--red);"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="form-actions" style="margin-top:24px;">
                    <a href="forgot-password.php" class="Bou" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Request New Link</a>
                </div>
            <?php else: ?>
                <form id="resetForm" method="post" action="update-password.php">
                    <h3 id="myH3">New Password</h3>
                    <p class="subtitle">Resetting password for <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    <div class="toast" id="resetNotice" style="position:static;transform:none;margin-bottom:20px;opacity:0;pointer-events:none;"></div>

                    <div class="form-group">
                        <label for="newpass">New Password</label>
                        <div class="password-wrap">
                            <input type="password" name="newpass" id="newpass" class="hh" placeholder="Password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">Show</button>
                        </div>
                        <span class="hint">At least 8 characters with a number and symbol.</span>
                        <span class="error-msg" id="err-pass">Password too weak.</span>
                    </div>

                    <div class="form-group">
                        <label for="confirmpass">Confirm Password</label>
                        <input type="password" name="confirmpass" id="confirmpass" class="hh" placeholder="Repeat password" required autocomplete="new-password">
                        <span class="error-msg" id="err-match">Passwords do not match.</span>
                    </div>

                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrfField(); ?>

                    <div class="form-actions">
                        <button type="submit" class="Bou" id="submitBtn">Update Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($valid): ?>
        <script>
            const form = document.getElementById('resetForm');
            const notice = document.getElementById('resetNotice');
            const btn = document.getElementById('submitBtn');

            function showNotice(message, isError = false) {
                notice.className = 'toast show' + (isError ? ' error' : '');
                notice.style.opacity = '1';
                notice.style.pointerEvents = 'auto';
                notice.innerHTML = `<i class="fas ${isError ? 'fa-triangle-exclamation' : 'fa-check-circle'}"></i><span>${message}</span>`;
            }

            document.getElementById('togglePass').addEventListener('click', function () {
                const password = document.getElementById('newpass');
                password.type = password.type === 'password' ? 'text' : 'password';
                this.textContent = password.type === 'password' ? 'Show' : 'Hide';
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const pass = document.getElementById('newpass').value;
                const confirm = document.getElementById('confirmpass').value;
                const passGroup = document.getElementById('newpass').closest('.form-group');
                const matchGroup = document.getElementById('confirmpass').closest('.form-group');

                passGroup.classList.remove('invalid');
                matchGroup.classList.remove('invalid');

                let ok = true;
                if (!/^(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/.test(pass)) {
                    passGroup.classList.add('invalid');
                    ok = false;
                }
                if (pass !== confirm) {
                    matchGroup.classList.add('invalid');
                    ok = false;
                }
                if (!ok) return;

                const original = btn.textContent;
                btn.textContent = 'Updating...';
                btn.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                    });
                    const data = await response.json();

                    if (data.success) {
                        showNotice('Password updated. Redirecting to login...');
                        setTimeout(() => { window.location.href = 'login.php'; }, 900);
                    } else {
                        showNotice(data.message || 'Could not update password.', true);
                        btn.textContent = original;
                        btn.disabled = false;
                    }
                } catch (error) {
                    showNotice('Network error. Please try again.', true);
                    btn.textContent = original;
                    btn.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
