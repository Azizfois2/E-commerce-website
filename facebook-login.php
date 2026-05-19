<?php
require_once __DIR__ . '/bootstrap.php';

// Generate a random state for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['fb_oauth_state'] = $state;

$loginUrl = "https://www.facebook.com/v25.0/dialog/oauth?" . http_build_query([
    'client_id' => FB_APP_ID,
    'redirect_uri' => FB_REDIRECT_URI,
    'state' => $state,
    'scope' => 'email,public_profile',
    'response_type' => 'code'
]);

header("Location: " . $loginUrl);
exit;
