<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = !empty($_SESSION['client_id']);
$remembered = !empty($_SESSION['remember_me']);
$isAdminExit = isset($_POST['admin']) && $_POST['admin'] === '1';
$isAdminLoggedIn = !empty($_SESSION['admin_id']);

if ($isAdminExit && $isAdminLoggedIn) {
    destroyAdminSession();
} elseif ($isLoggedIn && !$remembered) {
    destroyAppSession();
}

echo json_encode(['success' => true]);
