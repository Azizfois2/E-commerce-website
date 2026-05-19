<?php
require_once __DIR__ . '/bootstrap.php';

$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

$params = [
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'identify email',
    'state' => $state
];

header('Location: https://discord.com/api/oauth2/authorize?' . http_build_query($params));
exit;
