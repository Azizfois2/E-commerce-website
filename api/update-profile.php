<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/rate-limiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$input = json_decode(file_get_contents('php://input'), true);

$nom = trim($input['nom'] ?? '');
$nom = strip_tags($nom);
$nom = preg_replace('/[^\p{L}\p{N}\p{Z}\p{Pd}\p{Pc}]/u', '', $nom);
$nom = trim($nom);

$email = trim($input['email'] ?? '');
$adresse = trim($input['adresse'] ?? '');
$telephone = trim($input['telephone'] ?? '');
$dateNaissance = trim($input['date_naissance'] ?? '');
$currentPass = $input['current_password'] ?? '';
$newPass = $input['new_password'] ?? '';

$errors = [];

if (mb_strlen($nom) < 2) {
    $errors['nom'] = 'Name must be at least 2 characters (letters and spaces only).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email address.';
}

if ($dateNaissance !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $dateNaissance);
    if (!$date || $date->format('Y-m-d') !== $dateNaissance) {
        $errors['date_naissance'] = 'Invalid birth date.';
    }
}

$pdo = db();

// Check email uniqueness (excluding self)
if (empty($errors['email'])) {
    $stmt = $pdo->prepare("SELECT id_client FROM Client WHERE email = ? AND id_client != ?");
    $stmt->execute([$email, $clientId]);
    if ($stmt->fetch()) {
        $errors['email'] = 'This email is already in use.';
    }
}

// Check telephone uniqueness (excluding self)
if (!empty($telephone)) {
    $stmt = $pdo->prepare("SELECT id_client FROM Client WHERE telephone = ? AND id_client != ?");
    $stmt->execute([$telephone, $clientId]);
    if ($stmt->fetch()) {
        $errors['telephone'] = 'This phone number is already in use.';
    }
}

// If changing password, verify current password
if (!empty($newPass)) {
    if (strlen($newPass) < 8 || !preg_match('/[0-9]/', $newPass) || !preg_match('/[^a-zA-Z0-9]/', $newPass)) {
        $errors['new_password'] = 'Min. 8 chars, one number, one symbol.';
    }

    $stmt = $pdo->prepare("SELECT mot_de_passe FROM Client WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($currentPass, $row['mot_de_passe'])) {
        $errors['current_password'] = 'Current password is incorrect.';
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['errors' => $errors]);
    exit;
}

try {
    $fields = ['nom' => $nom, 'email' => $email, 'adresse' => $adresse, 'telephone' => $telephone, 'date_naissance' => $dateNaissance];
    $sql = "UPDATE Client SET nom = ?, email = ?, adresse = ?, telephone = ?, date_naissance = ?";
    $params = [$nom, $email, $adresse, $telephone, $dateNaissance !== '' ? $dateNaissance : null];

    if (!empty($newPass)) {
        $sql .= ", mot_de_passe = ?";
        $params[] = password_hash($newPass, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id_client = ?";
    $params[] = $clientId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Update session name
    $_SESSION['client_nom'] = $nom;
    $_SESSION['client_email'] = $email;

    echo json_encode(['success' => true, 'message' => 'Profile updated.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEV_MODE ? $e->getMessage() : 'Update failed.']);
}
