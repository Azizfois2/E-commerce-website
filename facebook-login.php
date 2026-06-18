<?php
require_once __DIR__ . '/bootstrap.php';

function safeRedirectTarget(?string $target, string $fallback = 'index.php'): string
{
    $target = trim((string) $target);
    if ($target === '') return $fallback;
    if (preg_match('#^(https?://|//|javascript:)#i', $target) || strpos($target, '..') !== false || strpbrk($target, "\r\n") !== false) {
        return $fallback;
    }
    return $target;
}

// Generate a random state for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['fb_oauth_state'] = $state;
$_SESSION['fb_oauth_next'] = safeRedirectTarget($_GET['next'] ?? null);

$loginUrl = "https://www.facebook.com/v25.0/dialog/oauth?" . http_build_query([
    'client_id' => FB_APP_ID,
    'redirect_uri' => FB_REDIRECT_URI,
    'state' => $state,
    'scope' => 'email,public_profile',
    'response_type' => 'code'
]);

header("Location: " . $loginUrl);
exit;
