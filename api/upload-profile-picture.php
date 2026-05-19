<?php
require_once dirname(__DIR__) . '/bootstrap.php';

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

function ensureProfileImageColumn(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote('profile_image'));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE Client ADD COLUMN profile_image VARCHAR(255) NULL AFTER date_naissance");
    }
}

if (empty($_FILES['profile_picture']) || !is_uploaded_file($_FILES['profile_picture']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Choose an image first.']);
    exit;
}

$file = $_FILES['profile_picture'];
if ((int) $file['size'] > 3 * 1024 * 1024) {
    http_response_code(422);
    echo json_encode(['error' => 'Profile picture must be 3 MB or smaller.']);
    exit;
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$mime = mime_content_type($file['tmp_name']) ?: '';
if (!isset($allowed[$mime])) {
    http_response_code(422);
    echo json_encode(['error' => 'Use JPG, PNG, or WebP for your profile picture.']);
    exit;
}

$clientId = (int) $_SESSION['client_id'];
$targetDir = dirname(__DIR__) . '/Images/profile';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create the profile image folder.']);
    exit;
}

$fileName = 'profile-' . $clientId . '-' . time() . '.' . $allowed[$mime];
$targetPath = $targetDir . '/' . $fileName;
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save the uploaded image.']);
    exit;
}

$relativePath = 'Images/profile/' . $fileName;

try {
    $pdo = db();
    ensureProfileImageColumn($pdo);
    $stmt = $pdo->prepare('UPDATE Client SET profile_image = ? WHERE id_client = ?');
    $stmt->execute([$relativePath, $clientId]);

    echo json_encode([
        'success' => true,
        'image' => $relativePath,
        'message' => 'Profile picture updated.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => DEV_MODE ? $e->getMessage() : 'Profile picture update failed.']);
}
