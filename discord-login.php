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

$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;
$_SESSION['discord_oauth_next'] = safeRedirectTarget($_GET['next'] ?? null);

$params = [
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'identify email',
    'state' => $state
];

header('Location: https://discord.com/api/oauth2/authorize?' . http_build_query($params));
exit;
