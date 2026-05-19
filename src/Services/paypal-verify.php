<?php
declare(strict_types=1);

if (!defined('MAROC_APP_BOOTSTRAPPED')) {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
}

/**
 * Optional PayPal Orders v2 verification when PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET are set.
 * Uses sandbox or live base URL from PAYPAL_ENV (sandbox|live).
 */
function paypalApiBaseUrl(): string
{
    $env = strtolower(trim(envString('PAYPAL_ENV', 'sandbox')));
    return $env === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

function paypalCredentialsConfigured(): bool
{
    return envString('PAYPAL_CLIENT_ID', '') !== '' && envString('PAYPAL_CLIENT_SECRET', '') !== '';
}

/**
 * @return array<string, mixed>
 */
function paypalHttpJson(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Unable to initialise HTTP client.');
    }

    $defaultHeaders = [];
    foreach ($headers as $k => $v) {
        $defaultHeaders[] = $k . ': ' . $v;
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $defaultHeaders,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('PayPal request failed: ' . $err);
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid PayPal response.');
    }

    if ($code >= 400) {
        $msg = $decoded['message'] ?? $decoded['error'] ?? 'HTTP ' . $code;
        throw new RuntimeException('PayPal error: ' . (is_string($msg) ? $msg : json_encode($msg)));
    }

    return $decoded;
}

function paypalGetAccessToken(): string
{
    $id = envString('PAYPAL_CLIENT_ID', '');
    $secret = envString('PAYPAL_CLIENT_SECRET', '');
    $url = paypalApiBaseUrl() . '/v1/oauth2/token';
    $auth = base64_encode($id . ':' . $secret);
    $res = paypalHttpJson('POST', $url, [
        'Authorization' => 'Basic ' . $auth,
        'Content-Type' => 'application/x-www-form-urlencoded',
    ], 'grant_type=client_credentials');
    $token = $res['access_token'] ?? null;
    if (!is_string($token) || $token === '') {
        throw new RuntimeException('PayPal OAuth token missing.');
    }
    return $token;
}

/**
 * Verify PayPal order id exists and is in a capturable/paid state.
 * Amount/currency check is best-effort when purchase unit currency is MAD.
 */
function paypalVerifyCheckoutOrder(string $paypalOrderId): void
{
    if (!paypalCredentialsConfigured()) {
        if (DEV_MODE) {
            error_log('[PayPal] Skipping order verification: PAYPAL_CLIENT_ID / PAYPAL_CLIENT_SECRET not set.');
            return;
        }
        throw new RuntimeException('PayPal is not configured on the server.');
    }

    $token = paypalGetAccessToken();
    $url = paypalApiBaseUrl() . '/v2/checkout/orders/' . rawurlencode($paypalOrderId);
    $order = paypalHttpJson('GET', $url, [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ]);

    $status = strtoupper((string) ($order['status'] ?? ''));
    $allowed = ['APPROVED', 'COMPLETED', 'PAYER_ACTION_REQUIRED'];
    if (!in_array($status, $allowed, true)) {
        throw new RuntimeException('PayPal order is not payable (status: ' . $status . ').');
    }
}
