<?php
declare(strict_types=1);

require_once 'admin-helpers.php';
adminRequireAuth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Invalid session token.'));
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Invalid laptop ID.'));
    }

    $pdo = db();
    
    // Fetch name for activity logging
    $name = adminFetchValue($pdo, 'SELECT name FROM laptops WHERE id = ?', [$id], '');
    
    $stmt = $pdo->prepare('DELETE FROM laptops WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($name !== '') {
        adminLogActivity($pdo, 'delete', 'laptop', $id, "Deleted laptop '{$name}'");
    }

    // Export laptops
    require_once 'export-laptops.php';
    try {
        adminExportLaptopsToDataJs($pdo);
    } catch (Throwable $e) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Laptop deleted, but assets/js/laptop_data.js could not be updated.'));
    }

    adminRedirect('admin-laptops.php?deleted=1');
} else {
    adminRedirect('admin-laptops.php');
}
