<?php
/**
 * api/process-abandoned-carts.php — Simulated Cron Job to process abandoned carts
 *
 * GET / POST to trigger processing
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();

// In production this would be protected by a cron secret token or admin login.
// For this environment, we'll just allow it to run to simulate cron.

// Fetch abandoned carts older than 4 hours but less than 48 hours that haven't been converted to orders
// and haven't had a 4h reminder sent yet.
$stmt = $pdo->query("
    SELECT ac.*, c.email, c.phone 
    FROM abandoned_carts ac
    JOIN Client c ON c.id_client = ac.client_id
    WHERE ac.locked_at < DATE_SUB(NOW(), INTERVAL 4 HOUR)
      AND ac.locked_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)
      AND ac.client_id NOT IN (
          SELECT client_id FROM abandoned_cart_communication_logs WHERE reminder_stage = '4h'
      )
");
$cartsToProcess = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processed = 0;
foreach ($cartsToProcess as $cart) {
    // Simulate sending email/whatsapp
    $channel = !empty($cart['phone']) ? 'whatsapp' : 'email';
    
    // Calculate total from JSON
    $cartData = json_decode($cart['cart_data'], true) ?: [];
    $total = 0;
    foreach ($cartData as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
    }

    // Log the communication
    $logStmt = $pdo->prepare("
        INSERT INTO abandoned_cart_communication_logs (client_id, cart_snapshot, locked_price_total, channel, reminder_stage)
        VALUES (?, ?, ?, ?, '4h')
    ");
    $logStmt->execute([$cart['client_id'], $cart['cart_data'], $total, $channel]);
    $processed++;
}

echo json_encode([
    'success' => true, 
    'message' => "Processed $processed abandoned carts for 4h reminders."
]);
