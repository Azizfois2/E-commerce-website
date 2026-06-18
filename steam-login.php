<?php
// steam-login.php — Steam OpenID login
require_once __DIR__ . '/bootstrap.php';

// Already logged in
if (isset($_SESSION['client_id'])) {
    header('Location: index.html');
    exit;
}

// Steam uses OpenID 2.0
$returnUrl = APP_URL . 'steam-callback.php';
$realm = rtrim(APP_URL, '/');

$params = http_build_query([
    'openid.ns'         => 'http://specs.openid.net/auth/2.0',
    'openid.mode'       => 'checkid_setup',
    'openid.return_to'  => $returnUrl,
    'openid.realm'      => $realm,
    'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
    'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
]);

header('Location: https://steamcommunity.com/openid/login?' . $params);
exit;
