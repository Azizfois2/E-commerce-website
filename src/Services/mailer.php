<?php
/**
 * mailer.php — Centralized email helper using PHPMailer.
 *
 * Usage:
 *   require_once 'mailer.php';
 *   sendEmail('user@example.com', 'Subject', '<h1>Hello</h1>');
 */

$marocRoot = dirname(__DIR__, 2);
require_once $marocRoot . '/lib/PHPMailer/Exception.php';
require_once $marocRoot . '/lib/PHPMailer/PHPMailer.php';
require_once $marocRoot . '/lib/PHPMailer/SMTP.php';
require_once $marocRoot . '/bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send an email via SMTP.
 *
 * @param string $to        Recipient email
 * @param string $subject   Email subject
 * @param string $htmlBody  HTML body content
 * @return bool             True on success, false on failure
 */
function sendEmail(string $to, string $subject, string $htmlBody): bool
{
    $GLOBALS['MAROC_LAST_MAIL_ERROR'] = null;
    $mail = new PHPMailer(true);

    try {
        // ── SMTP configuration ──────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // ── Sender / recipient ──────────────────────────────────
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // ── Content ─────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();

        if (DEV_MODE) {
            error_log("[MAIL] Sent to {$to}: {$subject}");
        }

        return true;
    } catch (MailException $e) {
        $GLOBALS['MAROC_LAST_MAIL_ERROR'] = $mail->ErrorInfo ?: $e->getMessage();
        error_log("[MAIL ERROR] Could not send message: " . $GLOBALS['MAROC_LAST_MAIL_ERROR']);
        return false;
    }
}

function lastMailError(): ?string
{
    $error = $GLOBALS['MAROC_LAST_MAIL_ERROR'] ?? null;
    return is_string($error) && $error !== '' ? $error : null;
}

// ── Email template wrapper ──────────────────────────────────────
function emailTemplate(string $title, string $bodyContent): string
{
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background:#050505; color:#eef0f4; font-family:Arial,Helvetica,sans-serif; margin:0; padding:40px 20px; }
        .wrap { max-width:520px; margin:0 auto; background:#0a0b0e; border:1px solid rgba(0,245,212,0.2); border-radius:16px; padding:40px; }
        .logo { text-align:center; margin-bottom:24px; }
        .logo i { color:#00f5d4; font-size:28px; }
        .logo span { color:#eef0f4; font-family:Orbitron,monospace; font-size:20px; font-weight:700; margin-left:8px; }
        h2 { color:#00f5d4; font-family:Orbitron,monospace; margin:0 0 16px; text-align:center; font-size:22px; }
        p { color:#b0b8c8; line-height:1.7; margin:0 0 20px; font-size:15px; }
        .btn { display:inline-block; padding:14px 36px; background:#00f5d4; color:#000; text-decoration:none; border-radius:8px; font-weight:700; font-size:15px; text-align:center; }
        .btn:hover { background:#00d4b8; }
        .btn-wrap { text-align:center; margin:28px 0; }
        .small { color:#5a6170; font-size:12px; margin-top:24px; text-align:center; }
        .link { color:#00f5d4; word-break:break-all; }
        .footer-line { border-top:1px solid rgba(255,255,255,0.06); margin-top:32px; padding-top:20px; text-align:center; }
        .footer-line p { color:#3a4050; font-size:12px; margin:0; }
        .highlight { background:rgba(0,245,212,0.08); border:1px solid rgba(0,245,212,0.15); border-radius:10px; padding:16px 20px; margin:20px 0; }
        .highlight p { margin:0; color:#b0b8c8; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">
            <span>⚡ Maroc PC</span>
        </div>
        <h2>' . $title . '</h2>
        ' . $bodyContent . '
        <div class="footer-line">
            <p>&copy; ' . date('Y') . ' Maroc PC. All rights reserved.</p>
            <p style="margin-top:8px;">123 Boulevard Zerktouni, Maarif, Casablanca</p>
        </div>
    </div>
</body>
</html>';
}

// ── Specific email builders ─────────────────────────────────────

/**
 * Send a welcome email to a new newsletter subscriber.
 */
function sendWelcomeEmail(string $to): bool
{
    $body = emailTemplate('Welcome to Maroc PC! 🎉', '
        <p>Thank you for subscribing to our newsletter!</p>
        <div class="highlight">
            <p>🔔 You\'ll be the first to know about:</p>
            <p style="margin-top:8px;">• Exclusive deals & flash sales<br>
            • New product arrivals<br>
            • Price drop alerts<br>
            • Tech tips & build guides</p>
        </div>
        <div class="btn-wrap">
            <a href="' . APP_URL . 'products.html" class="btn">Shop Now</a>
        </div>
        <p class="small">You received this because you subscribed at marocpc.ma. If this wasn\'t you, simply ignore this email.</p>
    ');

    return sendEmail($to, 'Welcome to Maroc PC! 🎉', $body);
}

/**
 * Send an email verification link to a newly registered user.
 */
function sendVerificationEmail(string $to, string $name, string $token): bool
{
    $verifyLink = APP_URL . 'verify-email.php?token=' . urlencode($token) . '&email=' . urlencode($to);

    $body = emailTemplate('Verify Your Email', '
        <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
        <p>Thank you for creating an account at Maroc PC! Please verify your email address to activate your account.</p>
        <div class="btn-wrap">
            <a href="' . $verifyLink . '" class="btn">Verify My Email</a>
        </div>
        <p class="small">Or copy this link:<br><span class="link">' . $verifyLink . '</span></p>
        <p class="small">This link expires in <strong>24 hours</strong>. If you didn\'t create this account, ignore this email.</p>
    ');

    return sendEmail($to, 'Verify your Maroc PC email', $body);
}

/**
 * Send a password reset email (replaces the old sendResetEmail in config.php).
 */
function sendPasswordResetEmail(string $to, string $resetLink): bool
{
    $body = emailTemplate('Password Reset', '
        <p>You requested to reset your Maroc PC password. Click the button below to set a new one.</p>
        <div class="btn-wrap">
            <a href="' . $resetLink . '" class="btn">Reset Password</a>
        </div>
        <p class="small">Or copy this link:<br><span class="link">' . $resetLink . '</span></p>
        <p class="small">This link expires in 1 hour. If you didn\'t request this, ignore this email.</p>
    ');

    return sendEmail($to, 'Reset your Maroc PC password', $body);
}

/**
 * Send a one-time login code for email-based two-factor authentication.
 */
function sendTwoFactorCodeEmail(string $to, string $name, string $code): bool
{
    $safeName = htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

    $body = emailTemplate('Your Login Code', '
        <p>Hi <strong>' . $safeName . '</strong>,</p>
        <p>Use this one-time code to finish signing in to your Maroc PC account.</p>
        <div class="highlight" style="text-align:center;">
            <p style="font-size:30px;letter-spacing:8px;font-weight:800;color:#00f5d4;font-family:Orbitron,monospace;">' . $safeCode . '</p>
        </div>
        <p class="small">This code expires in <strong>10 minutes</strong>. If you were not trying to sign in, change your password immediately.</p>
    ');

    return sendEmail($to, 'Your Maroc PC login code', $body);
}
/**
 * Send an order confirmation email to the client.
 */
function sendOrderConfirmationEmail(string $to, string $name, int $orderId, float $total, string $paymentMethod, array $items): bool
{
    $itemsList = '';
    foreach ($items as $item) {
        $price = number_format((float)$item['price'], 2);
        $itemsList .= "<li><strong>" . htmlspecialchars($item['name']) . "</strong> x" . $item['quantity'] . " — {$price} MAD</li>";
    }

    $formattedTotal = number_format($total, 2) . ' MAD';
    $methodLabel = ucwords(str_replace('-', ' ', $paymentMethod));

    $body = emailTemplate('Order Confirmed! 📦', '
        <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
        <p>Great news! Your order <strong>#' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) . '</strong> has been placed successfully and is now being processed.</p>
        <div class="highlight">
            <p><strong>Order Summary:</strong></p>
            <ul style="color:#b0b8c8; margin-top:10px; padding-left:20px;">
                ' . $itemsList . '
            </ul>
            <p style="margin-top:12px; border-top:1px solid rgba(0,245,212,0.1); padding-top:10px;">
                <strong>Payment Method:</strong> ' . $methodLabel . '<br>
                <strong>Total Amount:</strong> ' . $formattedTotal . '
            </p>
        </div>
        <p>We\'ll send you another update once your package is on its way!</p>
        <div class="btn-wrap">
            <a href="' . APP_URL . 'account.php?tab=orders" class="btn">Track Order</a>
        </div>
        <p class="small">Thank you for choosing Maroc PC!</p>
    ');

    return sendEmail($to, "Order Confirmation #" . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT), $body);
}

/**
 * Send a notification email to a suspended user.
 */
function sendSuspensionEmail(string $to, string $name, string $reason): bool
{
    $body = emailTemplate('Account Suspended 🚫', '
        <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
        <p>We are writing to inform you that your Maroc PC account has been suspended.</p>
        <div class="highlight" style="border-color: rgba(255, 61, 90, 0.3); background: rgba(255, 61, 90, 0.05);">
            <p><strong>Reason for suspension:</strong></p>
            <p style="margin-top:10px; color:#ff3d5a;">' . nl2br(htmlspecialchars($reason)) . '</p>
        </div>
        <p>As a result of this suspension, you will no longer be able to log in or place new orders. If you believe this is a mistake, please contact our support team.</p>
        <p class="small">This is an automated security notification.</p>
    ');

    return sendEmail($to, 'Your Maroc PC account has been suspended', $body);
}
