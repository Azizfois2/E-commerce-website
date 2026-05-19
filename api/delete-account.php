<?php
/**
 * api/delete-account.php — Soft-delete (or restore) user account.
 *
 * POST { action: "delete" }   → sets deleted_at = NOW()
 * POST { action: "restore" }  → clears deleted_at
 * POST { action: "confirm_delete" } → permanent delete (only if deleted_at > 5 days ago is NOT met — always allowed from grace period)
 *
 * Account is permanently purged by a cron or manual check after 5 days.
 * During the grace period the user can still log in and restore.
 */
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../mailer.php';
require_once '../inventory-helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$clientId = (int) $_SESSION['client_id'];
$pdo = db();
inventoryEnsureOrderStockColumn($pdo);

switch ($action) {

    // ── Soft delete ──────────────────────────────────────────────
    case 'delete':
        $password = $input['password'] ?? '';
        if (empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Password required to delete account.']);
            exit;
        }

        // Verify password
        $stmt = $pdo->prepare("SELECT mot_de_passe, email, nom FROM Client WHERE id_client = ?");
        $stmt->execute([$clientId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Set deleted_at and cancel open orders so reserved stock returns to inventory.
            $stmt = $pdo->prepare("UPDATE Client SET deleted_at = NOW() WHERE id_client = ?");
            $stmt->execute([$clientId]);
            $cancelledOrders = inventoryCancelOpenOrdersForClient($pdo, $clientId, 'account_delete');

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => DEV_MODE ? $e->getMessage() : 'Could not delete account.']);
            exit;
        }

        // Send notification email
        $deletionDate = date('Y-m-d H:i');
        $restoreDeadline = date('F j, Y', strtotime('+5 days'));

        $body = emailTemplate('Account Deletion Scheduled', '
            <p>Hi <strong>' . htmlspecialchars($user['nom']) . '</strong>,</p>
            <p>Your Maroc PC account has been scheduled for deletion on <strong>' . $restoreDeadline . '</strong>.</p>
            <div class="highlight">
                <p>⚠️ You have <strong>5 days</strong> to change your mind. After that, your account and all data will be permanently deleted.</p>
            </div>
            <p>To restore your account, simply log in and click <strong>"Restore My Account"</strong>.</p>
            <div class="btn-wrap">
                <a href="' . APP_URL . 'login.php" class="btn">Log In & Restore</a>
            </div>
            <p class="small">If you didn\'t request this, please secure your account immediately.</p>
        ');
        sendEmail($user['email'], 'Your Maroc PC account is scheduled for deletion', $body);

        echo json_encode([
            'success' => true,
            'message' => 'Account scheduled for deletion. You have 5 days to restore it.',
            'deleted_at' => $deletionDate,
            'restore_deadline' => $restoreDeadline,
            'cancelled_orders' => $cancelledOrders
        ]);
        break;

    // ── Restore ──────────────────────────────────────────────────
    case 'restore':
        $stmt = $pdo->prepare("SELECT deleted_at, email, nom FROM Client WHERE id_client = ?");
        $stmt->execute([$clientId]);
        $user = $stmt->fetch();

        if (!$user || empty($user['deleted_at'])) {
            echo json_encode(['success' => false, 'error' => 'Account is not scheduled for deletion.']);
            exit;
        }

        // Check if still within grace period (5 days)
        $deletedAt = new DateTime($user['deleted_at']);
        $now = new DateTime();
        $daysSince = $now->diff($deletedAt)->days;

        if ($daysSince > 5) {
            echo json_encode(['success' => false, 'error' => 'Grace period has expired. Contact support.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE Client SET deleted_at = NULL WHERE id_client = ?");
        $stmt->execute([$clientId]);

        // Send confirmation email
        $body = emailTemplate('Account Restored! 🎉', '
            <p>Hi <strong>' . htmlspecialchars($user['nom']) . '</strong>,</p>
            <p>Great news! Your Maroc PC account has been <strong>successfully restored</strong>.</p>
            <div class="highlight">
                <p>✅ Your profile, order history, and all data are intact.</p>
            </div>
            <p>Welcome back! We\'re glad you changed your mind.</p>
            <div class="btn-wrap">
                <a href="' . APP_URL . 'products.html" class="btn">Continue Shopping</a>
            </div>
        ');
        sendEmail($user['email'], 'Your Maroc PC account has been restored!', $body);

        echo json_encode(['success' => true, 'message' => 'Account restored successfully! Welcome back.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
