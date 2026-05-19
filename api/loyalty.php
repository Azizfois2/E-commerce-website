<?php
/**
 * api/loyalty.php — Loyalty Points & Rewards system
 *
 * GET  ?action=balance    → Get user's points balance & tier
 * GET  ?action=history    → Get points transaction history
 * POST { action: "redeem", points: N } → Redeem points at checkout
 */
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');
$pdo = db();

// All loyalty endpoints require authentication
if (empty($_SESSION['client_id'])) {
    jsonResponse(false, 'Login required.');
}

$clientId = (int)$_SESSION['client_id'];

// ── Tier calculation helper ──────────────────────────────
function calculateTier(int $totalPoints): string {
    if ($totalPoints >= 10000) return 'platinum';
    if ($totalPoints >= 5000) return 'gold';
    if ($totalPoints >= 2000) return 'silver';
    return 'bronze';
}

function tierMultiplier(string $tier): float {
    return match($tier) {
        'platinum' => 2.0,
        'gold' => 1.5,
        'silver' => 1.2,
        default => 1.0,
    };
}

function tierBenefits(string $tier): array {
    return match($tier) {
        'platinum' => ['2x points on purchases', 'Free express shipping', 'Early access to sales', 'Birthday bonus 500 pts'],
        'gold' => ['1.5x points on purchases', 'Free standard shipping', 'Early access to sales', 'Birthday bonus 300 pts'],
        'silver' => ['1.2x points on purchases', 'Free standard shipping', 'Birthday bonus 200 pts'],
        default => ['1x points on purchases', 'Birthday bonus 100 pts'],
    };
}

// Points to MAD conversion: 100 points = 10 MAD
function pointsToMAD(int $points): float {
    return round($points / 10, 2);
}

// ── GET requests ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'balance';

    if ($action === 'balance') {
        $stmt = $pdo->prepare("SELECT total_points, loyalty_tier FROM Client WHERE id_client = ?");
        $stmt->execute([$clientId]);
        $user = $stmt->fetch();

        $totalPoints = (int)($user['total_points'] ?? 0);
        $tier = $user['loyalty_tier'] ?? calculateTier($totalPoints);

        // Earned total (lifetime)
        $earnedStmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE client_id = ? AND points > 0");
        $earnedStmt->execute([$clientId]);
        $lifetimeEarned = (int)$earnedStmt->fetchColumn();

        // Redeemed total
        $redeemedStmt = $pdo->prepare("SELECT COALESCE(SUM(ABS(points)), 0) FROM loyalty_points WHERE client_id = ? AND points < 0");
        $redeemedStmt->execute([$clientId]);
        $lifetimeRedeemed = (int)$redeemedStmt->fetchColumn();

        // Next tier progress
        $nextTierPoints = match($tier) {
            'bronze' => 2000,
            'silver' => 5000,
            'gold' => 10000,
            'platinum' => 10000,
            default => 2000,
        };
        $nextTier = match($tier) {
            'bronze' => 'silver',
            'silver' => 'gold',
            'gold' => 'platinum',
            'platinum' => 'platinum',
            default => 'silver',
        };
        // Linear progress across the entire 0-10k range for the UI markers
        $tierProgress = min(100, round(($lifetimeEarned / 10000) * 100));

        jsonResponse(true, 'Balance loaded.', [
            'balance' => $totalPoints,
            'tier' => $tier,
            'tier_multiplier' => tierMultiplier($tier),
            'tier_benefits' => tierBenefits($tier),
            'lifetime_earned' => $lifetimeEarned,
            'lifetime_redeemed' => $lifetimeRedeemed,
            'mad_value' => pointsToMAD($totalPoints),
            'next_tier' => $nextTier,
            'next_tier_points' => $nextTierPoints,
            'tier_progress' => $tierProgress,
            'next_tier_progress' => $tier === 'platinum' ? 100 : min(100, round(($lifetimeEarned / $nextTierPoints) * 100))
        ]);
    }

    if ($action === 'history') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT * FROM loyalty_points WHERE client_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$clientId, $limit, $offset]);
        $history = $stmt->fetchAll();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_points WHERE client_id = ?");
        $countStmt->execute([$clientId]);
        $total = (int)$countStmt->fetchColumn();

        jsonResponse(true, 'History loaded.', [
            'history' => $history,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    if ($action === 'catalog') {
        $stmt = $pdo->query("SELECT id, title, description, points_required, reward_type, reward_value, stock_remaining FROM loyalty_rewards_catalog WHERE is_active = 1 AND stock_remaining > 0 ORDER BY points_required ASC");
        $catalog = $stmt->fetchAll();
        jsonResponse(true, 'Catalog loaded.', ['catalog' => $catalog]);
    }

    jsonResponse(false, 'Unknown action.');
}

// ── POST requests ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid request body.');
}

$action = $input['action'] ?? '';

// ── Redeem reward from catalog ───────────────────────────
if ($action === 'redeem') {
    $rewardId = (int)($input['reward_id'] ?? 0);
    if ($rewardId <= 0) {
        // Fallback for legacy generic points redemption
        $points = (int)($input['points'] ?? 0);
        if ($points <= 0 || $points % 100 !== 0) {
            jsonResponse(false, 'Invalid reward ID or points value.');
        }
        $discount = pointsToMAD($points);
        $description = "Redeemed {$points} points for {$discount} MAD discount";
    } else {
        // Fetch reward from catalog
        $stmt = $pdo->prepare("SELECT id, title, points_required, reward_type, reward_value, stock_remaining FROM loyalty_rewards_catalog WHERE id = ? AND is_active = 1 FOR UPDATE");
        $pdo->beginTransaction();
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch();

        if (!$reward) {
            $pdo->rollBack();
            jsonResponse(false, 'Reward not found or unavailable.');
        }
        if ($reward['stock_remaining'] <= 0) {
            $pdo->rollBack();
            jsonResponse(false, 'This reward is out of stock.');
        }
        $points = (int)$reward['points_required'];
        $description = "Redeemed reward: {$reward['title']}";
    }

    // Check balance
    $stmt = $pdo->prepare("SELECT total_points FROM Client WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $balance = (int)$stmt->fetchColumn();

    if ($points > $balance) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(false, 'Insufficient points. You have ' . $balance . ' points.');
    }

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        // Deduct points
        $pdo->prepare("INSERT INTO loyalty_points (client_id, points, source, description) VALUES (?, ?, 'redemption', ?)")
            ->execute([$clientId, -$points, $description]);

        $pdo->prepare("UPDATE Client SET total_points = total_points - ? WHERE id_client = ?")
            ->execute([$points, $clientId]);

        if ($rewardId > 0) {
            // Log into loyalty_redemptions
            $pdo->prepare("INSERT INTO loyalty_redemptions (client_id, reward_id, points_spent, status) VALUES (?, ?, ?, 'redeemed')")
                ->execute([$clientId, $rewardId, $points]);

            // Decrement stock
            $pdo->prepare("UPDATE loyalty_rewards_catalog SET stock_remaining = stock_remaining - 1 WHERE id = ?")
                ->execute([$rewardId]);
        }

        $pdo->commit();

        $responseData = ['remaining_points' => $balance - $points];
        if (isset($discount)) $responseData['discount'] = $discount;

        jsonResponse(true, $description . ' successfully!', $responseData);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Failed to redeem points.');
    }
}

// ── Award points (internal — called from place-order.php) ─
if ($action === 'award') {
    // This should only be called internally, but we validate
    $points = (int)$input['points'] ?? 0;
    $source = $input['source'] ?? 'bonus';
    $orderId = !empty($input['order_id']) ? (int)$input['order_id'] : null;
    $description = $input['description'] ?? 'Points awarded';

    if ($points <= 0) {
        jsonResponse(false, 'Invalid points amount.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO loyalty_points (client_id, points, source, order_id, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$clientId, $points, $source, $orderId, $description]);

        $pdo->prepare("UPDATE Client SET total_points = total_points + ? WHERE id_client = ?")
            ->execute([$points, $clientId]);

        // Recalculate tier
        $earnedStmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE client_id = ? AND points > 0");
        $earnedStmt->execute([$clientId]);
        $lifetimeEarned = (int)$earnedStmt->fetchColumn();
        $newTier = calculateTier($lifetimeEarned);

        $pdo->prepare("UPDATE Client SET loyalty_tier = ? WHERE id_client = ?")
            ->execute([$newTier, $clientId]);

        $pdo->commit();
        jsonResponse(true, "Awarded {$points} points!", ['new_balance' => 0, 'tier' => $newTier]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Failed to award points.');
    }
}

jsonResponse(false, 'Unknown action.');
