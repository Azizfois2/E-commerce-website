<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
require_once '../admin-helpers.php';
require_once '../order-checkout.php';
require_once '../vendor/autoload.php';

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

if (empty(STRIPE_SECRET_KEY)) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe is not configured. Please add keys to .env.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$items = $input['items'] ?? [];
$shippingMethod = trim((string) ($input['shippingMethod'] ?? 'standard'));
$pointsRedeemed = (int) ($input['points_redeemed'] ?? 0);
$promoCode = trim((string) ($input['promo_code'] ?? ''));

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your cart is empty.']);
    exit;
}

try {
    $pdo = db();
    $resolved = checkoutResolveCartLines($pdo, $items);
    $subtotal = $resolved['subtotal'];

    $promoDiscount = 0.0;
    if ($promoCode !== '') {
        $promo = adminCouponDiscount($pdo, $promoCode, $subtotal);
        if (!empty($promo['valid'])) {
            $promoDiscount = (float) ($promo['discount'] ?? 0);
        }
    }

    $discountedSubtotal = max(0.0, round($subtotal - $promoDiscount, 2));

    // Resolve loyalty points discount
    $clientId = (int) $_SESSION['client_id'];
    $checkPts = $pdo->prepare('SELECT total_points FROM Client WHERE id_client = ?');
    $checkPts->execute([$clientId]);
    $currentPts = (int) $checkPts->fetchColumn();

    $pointsBreak = checkoutApplyPointsDiscount($discountedSubtotal, $pointsRedeemed, $currentPts);
    $payable = $pointsBreak['payable'];

    // Shipping fee addition
    $shippingPrices = [
        'standard' => 100,
        'express' => 200,
        'overnight' => 400,
    ];
    $shippingFee = $shippingPrices[$shippingMethod] ?? 100;
    if ($payable >= 1000 && $shippingMethod === 'standard') {
        $shippingFee = 0;
    }

    // Add tax (8.25%)
    $taxRate = 0.0825;
    $tax = $payable * $taxRate;

    $grandTotal = max(0.0, round($payable + $shippingFee + $tax, 2));

    if ($grandTotal <= 0) {
        throw new RuntimeException('Order total must be greater than zero.');
    }

    // Setup Stripe client and create PaymentIntent
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    \Stripe\Stripe::setVerifySslCerts(false); // Bypasses local SSL certificate issues on XAMPP/localhost

    // Stripe amount is in subunits (cents). E.g., 100 MAD = 10000 subunits
    $amountInSubunits = (int) round($grandTotal * 100);

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amountInSubunits,
        'currency' => 'mad', // Maroc PC runs on Moroccan Dirham (MAD)
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'metadata' => [
            'client_id' => $clientId,
            'promo_code' => $promoCode,
            'points_redeemed' => $pointsRedeemed,
        ]
    ]);

    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'publishableKey' => STRIPE_PUBLISHABLE_KEY,
        'amount' => $grandTotal
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
