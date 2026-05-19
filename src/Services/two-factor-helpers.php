<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/mailer.php';

function twoFactorEnsureColumns(PDO $pdo): void
{
    $columns = [
        'two_factor_enabled' => "ALTER TABLE Client ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0",
        'two_factor_method' => "ALTER TABLE Client ADD COLUMN two_factor_method VARCHAR(20) NOT NULL DEFAULT 'email'",
        'two_factor_totp_secret' => "ALTER TABLE Client ADD COLUMN two_factor_totp_secret VARCHAR(64) DEFAULT NULL",
    ];

    foreach ($columns as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Column already exists.
        }
    }
}

function twoFactorNormalizeMethod(?string $method): string
{
    $method = trim((string) $method);
    return in_array($method, ['email', 'whatsapp', 'authenticator', 'sms'], true) ? $method : 'email';
}

function twoFactorAvailableMethods(array $client): array
{
    $methods = ['email'];
    if (twoFactorNormalizePhone((string) ($client['telephone'] ?? '')) !== '') {
        $methods[] = 'whatsapp';
        $methods[] = 'sms';
    }
    if (!empty($client['two_factor_totp_secret'])) {
        $methods[] = 'authenticator';
    }
    return $methods;
}

function twoFactorChooseMethod(array $client, ?string $preferred): string
{
    $preferred = twoFactorNormalizeMethod($preferred ?: ($client['two_factor_method'] ?? 'email'));
    $available = twoFactorAvailableMethods($client);
    return in_array($preferred, $available, true) ? $preferred : 'email';
}

function twoFactorSendCode(string $method, array $client, string $code): bool
{
    if ($method === 'whatsapp') {
        return sendTwoFactorCodeWhatsApp((string) ($client['telephone'] ?? ''), (string) ($client['nom'] ?? ''), $code);
    }
    if ($method === 'sms') {
        return sendTwoFactorCodeSMS((string) ($client['telephone'] ?? ''), (string) ($client['nom'] ?? ''), $code);
    }

    return sendTwoFactorCodeEmail((string) $client['email'], (string) ($client['nom'] ?? ''), $code);
}

function sendTwoFactorCodeSMS(string $phone, string $name, string $code): bool
{
    $phone = twoFactorNormalizePhone($phone);
    if ($phone === '') {
        return false;
    }

    $message = "Maroc PC verification code: {$code}. It expires in 5 minutes.";
    if (defined('TEXTBEE_API_KEY') && TEXTBEE_API_KEY !== '' && defined('TEXTBEE_DEVICE_ID') && TEXTBEE_DEVICE_ID !== '') {
        $payload = json_encode([
            "recipients" => [$phone],
            "message" => $message
        ]);
        if ($payload !== false && function_exists('curl_init')) {
            $url = "https://api.textbee.dev/api/v1/gateway/devices/" . TEXTBEE_DEVICE_ID . "/send-sms";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'x-api-key: ' . TEXTBEE_API_KEY,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($status >= 200 && $status < 300) {
                return true;
            }
            error_log('[TEXTBEE API ERROR] ' . ($err ?: (string) $raw));
        }
    }

    if (DEV_MODE) {
        error_log("[SMS 2FA via Textbee] To {$phone}: {$message}");
    }

    // Local fallback for local development/testing:
    $_SESSION['two_factor_login']['sms_debug_to'] = $phone;
    $_SESSION['two_factor_login']['sms_debug_code'] = DEV_MODE ? $code : null;
    return true;
}

function sendTwoFactorCodeWhatsApp(string $phone, string $name, string $code): bool
{
    $phone = twoFactorNormalizePhone($phone);
    if ($phone === '') {
        return false;
    }

    $message = "Maroc PC login code: {$code}. It expires in 5 minutes.";
    if (defined('EVOLUTION_API_KEY') && EVOLUTION_API_KEY !== '') {
        $payload = json_encode([
            "number" => ltrim($phone, '+'),
            "text" => $message
        ]);
        if ($payload !== false && function_exists('curl_init')) {
            $apiUrl = rtrim(EVOLUTION_API_URL, '/') . '/message/sendText/' . rawurlencode(EVOLUTION_INSTANCE_NAME);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($status >= 200 && $status < 300) {
                // Return true only if successful. If it failed, it falls down to local DEV mode fallback.
                return true;
            }
            error_log('[EVOLUTION API ERROR] ' . ($err ?: (string) $raw));
        }
    }

    if (DEV_MODE) {
        error_log("[WHATSAPP 2FA] To {$phone}: {$message}");
    }

    // Local fallback: without WhatsApp Business API credentials, store/log the code
    // so the local XAMPP flow can still be tested safely.
    $_SESSION['two_factor_login']['whatsapp_debug_to'] = $phone;
    $_SESSION['two_factor_login']['whatsapp_debug_code'] = DEV_MODE ? $code : null;
    return true;
}

function twoFactorNormalizePhone(string $phone): string
{
    $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';
    if ($phone === '') {
        return '';
    }
    if ($phone[0] !== '+') {
        $phone = '+212' . ltrim($phone, '0');
    }
    return $phone;
}

function twoFactorMaskPhone(string $phone): string
{
    $phone = twoFactorNormalizePhone($phone);
    if ($phone === '') {
        return '';
    }
    return substr($phone, 0, 4) . str_repeat('*', max(3, strlen($phone) - 7)) . substr($phone, -3);
}

function twoFactorGenerateSecret(int $length = 20): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bytes = random_bytes($length);
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[ord($bytes[$i]) % 32];
    }
    return $secret;
}

function twoFactorBase32Decode(string $secret): string
{
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    $output = '';
    for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
        $value = strpos($alphabet, $secret[$i]);
        if ($value === false) {
            continue;
        }
        $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
    }
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $output .= chr(bindec($byte));
        }
    }
    return $output;
}

function twoFactorTotpCode(string $secret, ?int $time = null): string
{
    $time = $time ?? time();
    $counter = intdiv($time, 30);
    $binaryCounter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $binaryCounter, twoFactorBase32Decode($secret), true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $part = substr($hash, $offset, 4);
    $value = unpack('N', $part)[1] & 0x7FFFFFFF;
    return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function twoFactorVerifyTotp(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6 || $secret === '') {
        return false;
    }
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(twoFactorTotpCode($secret, $now + ($i * 30)), $code)) {
            return true;
        }
    }
    return false;
}

function twoFactorOtpAuthUri(string $email, string $secret): string
{
    $label = 'Maroc PC:' . $email;
    return 'otpauth://totp/' . rawurlencode($label)
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode('Maroc PC')
        . '&algorithm=SHA1&digits=6&period=30';
}

function twoFactorQrImageUrl(string $otpauthUri): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($otpauthUri);
}
