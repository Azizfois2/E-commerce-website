<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../admin-helpers.php';
require_once '../mailer.php';
require_once '../order-checkout.php';
require_once '../paypal-verify.php';
require_once __DIR__ . '/rate-limiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$clientId = (int) $_SESSION['client_id'];

// Validate input
$items = $input['items'] ?? [];
$shippingMethod = trim($input['shippingMethod'] ?? '');
$paymentMethod = trim($input['paymentMethod'] ?? '');
$shippingAddress = trim($input['shippingAddress'] ?? '');
$billingAddress = trim($input['billingAddress'] ?? '');
$notes = trim($input['notes'] ?? '');
$transactionId = trim($input['transaction_id'] ?? '');
$paypalOrderId = trim($input['paypal_order_id'] ?? '');
$promoCode = trim((string) ($input['promo_code'] ?? ''));

if (empty($items) || empty($shippingMethod) || empty($paymentMethod) || empty($shippingAddress)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate payment method
$validMethods = ['credit-card', 'paypal', 'bitcoin', 'apple-pay', 'google-pay', 'cod', 'nfc-biometric'];
if (!in_array($paymentMethod, $validMethods, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payment method']);
    exit;
}

if ($paymentMethod === 'paypal' && $paypalOrderId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'PayPal order id is required for PayPal checkout.']);
    exit;
}

$pdo = db();
checkoutEnsureOrdersPaymentColumns($pdo);

try {
    $pdo->beginTransaction();

    $resolved = checkoutResolveCartLines($pdo, $items);
    $lines = $resolved['lines'];
    $subtotal = $resolved['subtotal'];

    if ($subtotal <= 0) {
        throw new RuntimeException('Invalid order total.');
    }

    $promoDiscount = 0.0;
    $promoCouponId = 0;
    if ($promoCode !== '') {
        $promo = adminCouponDiscount($pdo, $promoCode, $subtotal);
        if (empty($promo['valid'])) {
            throw new RuntimeException($promo['error'] ?? 'Invalid promo code.');
        }
        $promoDiscount = (float) ($promo['discount'] ?? 0);
        $promoCouponId = (int) ($promo['id'] ?? 0);
        $promoCode = (string) ($promo['code'] ?? $promoCode);
    }

    $discountedSubtotal = max(0.0, round($subtotal - $promoDiscount, 2));

    $pointsRedeemed = (int) ($input['points_redeemed'] ?? 0);
    $currentPts = 0;
    if ($pointsRedeemed > 0) {
        $checkPts = $pdo->prepare('SELECT total_points FROM Client WHERE id_client = ? FOR UPDATE');
        $checkPts->execute([$clientId]);
        $currentPts = (int) $checkPts->fetchColumn();
    }

    $pointsBreak = checkoutApplyPointsDiscount($discountedSubtotal, $pointsRedeemed, $currentPts);
    $total = $pointsBreak['payable'];

    $productQuantities = inventoryQuantitiesFromCartItems($items);
    inventoryReserveQuantities($pdo, $productQuantities);
    $stockReserved = $productQuantities === [] ? 0 : 1;

    if ($paymentMethod === 'paypal') {
        paypalVerifyCheckoutOrder($paypalOrderId);
    }

    // Calculate estimated delivery
    $estimatedDelivery = date('Y-m-d', strtotime('+3 days'));
    if ($shippingMethod === 'standard') {
        $estimatedDelivery = date('Y-m-d', strtotime('+5 days'));
    } elseif ($shippingMethod === 'express') {
        $estimatedDelivery = date('Y-m-d', strtotime('+2 days'));
    }

    $paymentStatus = 'pending';
    if (in_array($paymentMethod, ['credit-card', 'paypal'], true)) {
        $paymentStatus = 'paid';
    } elseif ($paymentMethod === 'cod') {
        $paymentStatus = 'pending';
    }

    $stmt = $pdo->prepare('
        INSERT INTO orders
          (client_id, status, total, shipping_method, shipping_address, billing_address, payment_method, payment_status, stock_reserved, transaction_id, paypal_order_id, notes, estimated_delivery)
        VALUES (?, \'pending\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $clientId,
        $total,
        $shippingMethod,
        $shippingAddress,
        $billingAddress,
        $paymentMethod,
        $paymentStatus,
        $stockReserved,
        $transactionId !== '' ? $transactionId : null,
        $paypalOrderId !== '' ? $paypalOrderId : null,
        $notes,
        $estimatedDelivery,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO order_status_history (order_id, new_status, changed_by) VALUES (?, 'pending', 'system')")->execute([$orderId]);

    // ── Save user info for later ─────────────────────────────
    if (!empty($input['save_info'])) {
        $fullName = trim(($input['firstName'] ?? '') . ' ' . ($input['lastName'] ?? ''));
        $fullName = strip_tags($fullName);
        $fullName = preg_replace('/[^\p{L}\p{N}\p{Z}\p{Pd}\p{Pc}]/u', '', $fullName);
        $fullName = trim($fullName);

        if (mb_strlen($fullName) < 2) {
            throw new RuntimeException('Please enter a valid full name (letters and spaces only).');
        }

        $phone = trim($input['phone'] ?? '');
        if ($phone !== '') {
            $stmt = $pdo->prepare("SELECT id_client FROM Client WHERE telephone = ? AND id_client != ?");
            $stmt->execute([$phone, $clientId]);
            if ($stmt->fetch()) {
                throw new RuntimeException('This phone number is already registered to another account.');
            }
        }

        $updateStmt = $pdo->prepare('UPDATE Client SET nom = ?, telephone = ?, adresse = ? WHERE id_client = ?');
        $updateStmt->execute([$fullName, $phone !== '' ? $phone : null, $shippingAddress, $clientId]);
    }

    // ── Newsletter subscription ──────────────────────────────
    if (!empty($input['newsletter'])) {
        $clientEmail = $pdo->query("SELECT email FROM Client WHERE id_client = $clientId")->fetchColumn();
        if ($clientEmail) {
            $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)')->execute([$clientEmail]);
        }
    }

    // ── Remember payment info (Simulated) ────────────────────
    if (!empty($input['save_card']) && $paymentMethod === 'credit-card') {
        // For a real app, we'd save a token. For this demo, we'll just set a flag.
        $pdo->prepare("UPDATE Client SET moyen_paiement = 'credit-card-saved' WHERE id_client = ?")->execute([$clientId]);
    }

    $itemStmt = $pdo->prepare('
        INSERT INTO order_items (order_id, product_id, quantity, price_at_time, name_at_time)
        VALUES (?, ?, ?, ?, ?)
    ');

    foreach ($lines as $line) {
        $itemStmt->execute([
            $orderId,
            $line['product_id'],
            $line['quantity'],
            round($line['unit_price'], 2),
            $line['name'],
        ]);
    }

    checkoutIncrementFlashSalesSold($pdo, $lines);
    if ($promoCouponId > 0) {
        adminIncrementCouponUse($pdo, $promoCouponId);
        adminLogActivity($pdo, 'use', 'coupon', $promoCouponId, "Promo {$promoCode} used on order #{$orderId}");
    }

    if ($pointsBreak['effective_points'] > 0) {
        $pdo->prepare("INSERT INTO loyalty_points (client_id, points, source, order_id, description) VALUES (?, ?, 'redemption', ?, ?)")
            ->execute([$clientId, -$pointsBreak['effective_points'], $orderId, "Redeemed for order #$orderId"]);

        $pdo->prepare('UPDATE Client SET total_points = total_points - ? WHERE id_client = ?')
            ->execute([$pointsBreak['effective_points'], $clientId]);
    }

    $pointsToAward = (int) floor($total / 10);
    if ($pointsToAward > 0) {
        $tierStmt = $pdo->prepare('SELECT loyalty_tier FROM Client WHERE id_client = ?');
        $tierStmt->execute([$clientId]);
        $tier = $tierStmt->fetchColumn() ?: 'bronze';

        $multiplier = match ($tier) {
            'platinum' => 2.0,
            'gold' => 1.5,
            'silver' => 1.2,
            default => 1.0,
        };

        $finalPoints = (int) floor($pointsToAward * $multiplier);

        $pdo->prepare("INSERT INTO loyalty_points (client_id, points, source, order_id, description) VALUES (?, ?, 'purchase', ?, ?)")
            ->execute([$clientId, $finalPoints, $orderId, "Points earned from order #$orderId"]);

        $pdo->prepare('UPDATE Client SET total_points = total_points + ? WHERE id_client = ?')
            ->execute([$finalPoints, $clientId]);

        $lifetimeStmt = $pdo->prepare('SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE client_id = ? AND points > 0');
        $lifetimeStmt->execute([$clientId]);
        $lifetimePoints = (int) $lifetimeStmt->fetchColumn();

        $newTier = 'bronze';
        if ($lifetimePoints >= 10000) {
            $newTier = 'platinum';
        } elseif ($lifetimePoints >= 5000) {
            $newTier = 'gold';
        } elseif ($lifetimePoints >= 2000) {
            $newTier = 'silver';
        }

        if ($newTier !== $tier) {
            $pdo->prepare('UPDATE Client SET loyalty_tier = ? WHERE id_client = ?')
                ->execute([$newTier, $clientId]);
        }
    }

    $pdo->commit();

    $emailItems = [];
    foreach ($lines as $line) {
        $emailItems[] = [
            'id' => $line['product_id'],
            'quantity' => $line['quantity'],
            'price' => $line['unit_price'],
            'name' => $line['name'],
        ];
    }

    try {
        $clientStmt = $pdo->prepare('SELECT nom, email FROM Client WHERE id_client = ?');
        $clientStmt->execute([$clientId]);
        $clientInfo = $clientStmt->fetch();

        if ($clientInfo) {
            sendOrderConfirmationEmail(
                $clientInfo['email'],
                $clientInfo['nom'],
                $orderId,
                $total,
                $paymentMethod,
                $emailItems
            );
        }
    } catch (Exception $e) {
        error_log('Failed to send order email: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'transactionId' => $transactionId !== '' ? $transactionId : null,
        'total' => $total,
        'subtotal' => $subtotal,
        'message' => 'Order placed successfully',
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e instanceof RuntimeException ? 400 : 500);
    echo json_encode([
        'error' => DEV_MODE ? $e->getMessage() : 'Failed to place order. Please try again.',
    ]);
}
