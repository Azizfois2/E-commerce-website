<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id_client, nom, email, date_naissance, moyen_paiement, adresse, telephone, created_at
    FROM Client
    WHERE id_client = ?
");
$stmt->execute([(int)$_SESSION['client_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode(['user' => $user]);
