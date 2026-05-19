<?php
require_once __DIR__ . '/src/bootstrap/application.php';
$hasSession = isset($_COOKIE['has_active_session']) ? 'YES' : 'NO';
echo "Cookie has_active_session is: " . $hasSession . "\n";
if (isset($_GET['set'])) {
    setcookie('has_active_session', '1', appCookieOptions(time() + 31536000));
    echo "Attempted to set cookie.\n";
}
