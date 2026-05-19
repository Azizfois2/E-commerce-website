<?php
require_once 'admin-helpers.php';

adminRequireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('admin-customers.php');
}

if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
    adminRedirect('admin-customers.php?error=' . urlencode('Invalid session token.'));
}

$customerId = (int) ($_POST['id'] ?? 0);
if ($customerId <= 0) {
    adminRedirect('admin-customers.php?error=' . urlencode('Invalid user.'));
}

$pdo = db();

try {
    $pdo->beginTransaction();

    if (adminTableExists($pdo, 'order_items') && adminTableExists($pdo, 'orders')) {
        $stmt = $pdo->prepare('
            DELETE oi
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.client_id = ?
        ');
        $stmt->execute([$customerId]);
    }

    if (adminTableExists($pdo, 'orders')) {
        $stmt = $pdo->prepare('DELETE FROM orders WHERE client_id = ?');
        $stmt->execute([$customerId]);
    }

    $stmt = $pdo->prepare('DELETE FROM Client WHERE id_client = ?');
    $stmt->execute([$customerId]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('User not found.');
    }

    $pdo->commit();
    adminRedirect('admin-customers.php?deleted=1');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    adminRedirect('admin-customers.php?error=' . urlencode('Unable to delete this user and related orders.'));
}
