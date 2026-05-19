<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = !empty($_SESSION['client_id']);
$remembered = !empty($_SESSION['remember_me']);

if ($isLoggedIn && !$remembered) {
    destroyAppSession();
}

echo json_encode(['success' => true]);
