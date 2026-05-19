<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Maroc PC</title>
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
                <h2>Locked Out?</h2>
                <p>No worries. We will send a secure reset link to your email.</p>
            </div>
        </div>

        <div class="inscription">
            <form id="forgotForm" method="post" action="send-reset.php">
                <h3 id="myH3">Reset Password</h3>
                <p class="subtitle">Enter your account email and we will send you a reset link.</p>

                <?php if (!empty($_GET['sent'])): ?>
                    <div class="toast show" style="position:static;transform:none;margin-bottom:20px;opacity:1;pointer-events:auto;">
                        <i class="fas fa-envelope"></i>
                        <span>If an account exists, a reset link has been sent.</span>
                    </div>
                <?php endif; ?>

                <div class="toast" id="resetNotice" style="position:static;transform:none;margin-bottom:20px;opacity:0;pointer-events:none;"></div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="hh" placeholder="john@example.com" required autocomplete="email">
                    <span class="error-msg" id="err-email">Please enter a valid email.</span>
                </div>

                <?= csrfField(); ?>

                <div class="form-actions">
                    <button type="submit" class="Bou" id="submitBtn">Send Reset Link</button>
                </div>

                <p class="login-link">Remember your password? <a href="login.php">Sign in</a></p>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('forgotForm');
        const notice = document.getElementById('resetNotice');
        const btn = document.getElementById('submitBtn');

        function showNotice(message, isError = false) {
            notice.className = 'toast show' + (isError ? ' error' : '');
            notice.style.opacity = '1';
            notice.style.pointerEvents = 'auto';
            notice.innerHTML = `<i class="fas ${isError ? 'fa-triangle-exclamation' : 'fa-envelope'}"></i><span>${message}</span>`;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const original = btn.textContent;
            btn.textContent = 'Sending...';
            btn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                const data = await response.json();

                showNotice(data.message || 'If an account exists, a reset link has been sent.', !data.success);
                if (data.dev_link) {
                    console.info('Password reset dev link:', data.dev_link);
                }
            } catch (error) {
                showNotice('Network error. Please try again.', true);
            } finally {
                btn.textContent = original;
                btn.disabled = false;
            }
        });
    </script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
