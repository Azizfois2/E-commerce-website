<?php
require_once 'admin-helpers.php';

adminRequireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('admin-products.php');
}

if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
    adminRedirect('admin-products.php?error=' . urlencode('Invalid session token.'));
}

$productId = (int) ($_POST['id'] ?? 0);
if ($productId <= 0) {
    adminRedirect('admin-products.php?error=' . urlencode('Invalid product.'));
}

try {
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    adminExportProductsToDataJs(db());
    adminRedirect('admin-products.php?deleted=1');
} catch (Throwable $e) {
    adminRedirect('admin-products.php?error=' . urlencode('Unable to delete this product or update js/data.js.'));
}
