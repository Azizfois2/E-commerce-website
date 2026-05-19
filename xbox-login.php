<?php
require_once __DIR__ . '/bootstrap.php';

$state = bin2hex(random_bytes(16));
$_SESSION['xbox_oauth_state'] = $state;

$params = [
    'client_id' => XBOX_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => XBOX_REDIRECT_URI,
    'response_mode' => 'query',
    'scope' => 'openid profile email',
    'state' => $state
];

header('Location: https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query($params));
exit;
