<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$loggedIn = isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);

echo json_encode([
    'loggedIn' => $loggedIn,
    'user' => $loggedIn ? ($_SESSION['client_nom'] ?? null) : null,
    'rememberMe' => $loggedIn ? !empty($_SESSION['remember_me']) : false,
], JSON_UNESCAPED_UNICODE);
