<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/rate-limiter.php';
header('Content-Type: application/json');

$pdo = db();
$clientId = $_SESSION['client_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch reviews for a product
if ($method === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if (!$productId) {
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom AS client_name 
            FROM product_reviews r
            JOIN Client c ON c.id_client = r.client_id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$productId]);
        $reviews = $stmt->fetchAll();

        // Find user votes if logged in
        $userVotes = [];
        if ($clientId) {
            $stmtVotes = $pdo->prepare("SELECT review_id, vote FROM review_votes WHERE client_id = ?");
            $stmtVotes->execute([$clientId]);
            foreach ($stmtVotes->fetchAll() as $vote) {
                $userVotes[$vote['review_id']] = $vote['vote'];
            }
        }

        // Format
        $result = [];
        foreach ($reviews as $rev) {
            $result[] = [
                'id' => (int)$rev['id'],
                'client_name' => $rev['client_name'],
                'rating' => (int)$rev['rating'],
                'review_text' => $rev['review_text'],
                'is_verified' => (bool)$rev['is_verified_purchase'],
                'helpful_count' => (int)$rev['helpful_count'],
                'unhelpful_count' => (int)$rev['unhelpful_count'],
                'created_at' => $rev['created_at'],
                'user_vote' => $userVotes[$rev['id']] ?? null
            ];
        }

        echo json_encode(['success' => true, 'reviews' => $result]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Handle POST: Create review or Vote
if ($method === 'POST') {
    if (!$clientId) {
        echo json_encode(['error' => 'Must be logged in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // Create a new review
    if ($action === 'create') {
        $productId = (int)($input['product_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $text = trim($input['review_text'] ?? '');

        if (!$productId || $rating < 1 || $rating > 5 || empty($text)) {
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        try {
            // Check if user already reviewed
            $stmtCheck = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND client_id = ?");
            $stmtCheck->execute([$productId, $clientId]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['error' => 'You have already reviewed this product']);
                exit;
            }

            // Check if verified purchase
            $isVerified = 0;
            $stmtVerified = $pdo->prepare("
                SELECT oi.id 
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.client_id = ? AND oi.product_id = ? AND o.status IN ('delivered', 'shipped')
                LIMIT 1
            ");
            $stmtVerified->execute([$clientId, $productId]);
            if ($stmtVerified->fetch()) {
                $isVerified = 1;
            }

            // Insert review (status = approved by default for MVP, can change to pending if moderation needed)
            $stmt = $pdo->prepare("
                INSERT INTO product_reviews (product_id, client_id, rating, review_text, is_verified_purchase, status)
                VALUES (?, ?, ?, ?, ?, 'approved')
            ");
            $stmt->execute([$productId, $clientId, $rating, $text, $isVerified]);

            // Update product average rating
            updateProductRating($pdo, $productId);

            echo json_encode(['success' => true, 'message' => 'Review posted successfully']);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error']);
        }
        exit;
    }

    // Vote on a review
    if ($action === 'vote') {
        $reviewId = (int)($input['review_id'] ?? 0);
        $voteType = $input['vote'] ?? '';

        if (!$reviewId || !in_array($voteType, ['helpful', 'unhelpful'])) {
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Check if review exists
            $stmtRev = $pdo->prepare("SELECT id, client_id FROM product_reviews WHERE id = ?");
            $stmtRev->execute([$reviewId]);
            $rev = $stmtRev->fetch();
            if (!$rev) throw new Exception("Review not found");
            if ($rev['client_id'] == $clientId) throw new Exception("Cannot vote on your own review");

            // Check existing vote
            $stmtCheck = $pdo->prepare("SELECT vote FROM review_votes WHERE review_id = ? AND client_id = ?");
            $stmtCheck->execute([$reviewId, $clientId]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                if ($existing['vote'] === $voteType) {
                    // Remove vote if clicking same button again
                    $pdo->prepare("DELETE FROM review_votes WHERE review_id = ? AND client_id = ?")->execute([$reviewId, $clientId]);
                    $pdo->prepare("UPDATE product_reviews SET {$voteType}_count = GREATEST(0, {$voteType}_count - 1) WHERE id = ?")->execute([$reviewId]);
                } else {
                    // Switch vote
                    $pdo->prepare("UPDATE review_votes SET vote = ? WHERE review_id = ? AND client_id = ?")->execute([$voteType, $reviewId, $clientId]);
                    $oldVote = $existing['vote'];
                    $pdo->prepare("UPDATE product_reviews SET {$oldVote}_count = GREATEST(0, {$oldVote}_count - 1), {$voteType}_count = {$voteType}_count + 1 WHERE id = ?")->execute([$reviewId]);
                }
            } else {
                // New vote
                $pdo->prepare("INSERT INTO review_votes (review_id, client_id, vote) VALUES (?, ?, ?)")->execute([$reviewId, $clientId, $voteType]);
                $pdo->prepare("UPDATE product_reviews SET {$voteType}_count = {$voteType}_count + 1 WHERE id = ?")->execute([$reviewId]);
            }

            $pdo->commit();

            // Get updated counts
            $stmtCounts = $pdo->prepare("SELECT helpful_count, unhelpful_count FROM product_reviews WHERE id = ?");
            $stmtCounts->execute([$reviewId]);
            $counts = $stmtCounts->fetch();

            echo json_encode(['success' => true, 'counts' => $counts]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

function updateProductRating($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    $stats = $stmt->fetch();
    
    $avg = round((float)$stats['avg_rating'], 1);
    $count = (int)$stats['review_count'];
    
    $pdo->prepare("UPDATE products SET rating = ?, reviews = ? WHERE id = ?")->execute([$avg, $count, $productId]);
}
