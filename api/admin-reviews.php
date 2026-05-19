<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../admin-helpers.php';
header('Content-Type: application/json');

adminRequireAuth();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $reviewId = (int)($input['id'] ?? 0);

    if (!$reviewId) {
        echo json_encode(['error' => 'Review ID required']);
        exit;
    }

    try {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE id = ?")->execute([$reviewId]);
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE product_reviews SET status = 'rejected' WHERE id = ?")->execute([$reviewId]);
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM product_reviews WHERE id = ?")->execute([$reviewId]);
        } else {
            echo json_encode(['error' => 'Invalid action']);
            exit;
        }

        // Recalculate average rating
        $stmtRev = $pdo->prepare("SELECT product_id FROM product_reviews WHERE id = ?");
        $stmtRev->execute([$reviewId]);
        $rev = $stmtRev->fetch();
        if ($rev) {
            updateProductRating($pdo, $rev['product_id']);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

function updateProductRating($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    $stats = $stmt->fetch();
    
    $avg = round((float)$stats['avg_rating'], 1);
    $count = (int)$stats['review_count'];
    
    $pdo->prepare("UPDATE products SET rating = ?, reviews = ? WHERE id = ?")->execute([$avg, $count, $productId]);
}
