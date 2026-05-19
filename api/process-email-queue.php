<?php
require_once __DIR__ . '/../admin-helpers.php';
require_once __DIR__ . '/../mailer.php';

// Only admins can trigger this via web, OR it can be run via CLI
if (php_sapi_name() !== 'cli') {
    adminRequireAuth();
}

$pdo = db();
adminEnsureMarketingTables($pdo);

// 1. Get campaigns that are pending and ready to be sent
$stmt = $pdo->prepare("SELECT * FROM scheduled_emails WHERE status = 'pending' AND scheduled_at <= NOW() LIMIT 1");
$stmt->execute();
$campaign = $stmt->fetch();

if (!$campaign) {
    if (php_sapi_name() === 'cli') echo "No pending campaigns.\n";
    else echo json_encode(['status' => 'idle', 'message' => 'No pending campaigns.']);
    exit();
}

$id = (int)$campaign['id'];
$subject = $campaign['subject'];
$content = $campaign['content'];
$type = adminNormalizeRecipientsType($campaign['recipients_type'] ?? 'all');

// 2. Mark as sending
$pdo->prepare("UPDATE scheduled_emails SET status = 'sending' WHERE id = ?")->execute([$id]);

// 3. Get recipients
$recipients = adminMarketingRecipientEmails($pdo, $type);
$total = count($recipients);
$pdo->prepare("UPDATE scheduled_emails SET total_recipients = ? WHERE id = ?")->execute([$total, $id]);

if ($total === 0) {
    $pdo->prepare("UPDATE scheduled_emails SET status = 'failed', sent_count = 0, sent_at = NOW(), error_message = ? WHERE id = ?")
        ->execute(['No recipients matched this campaign target.', $id]);
    $response = [
        'status' => 'error',
        'campaign_id' => $id,
        'sent_count' => 0,
        'total' => 0,
        'message' => 'No recipients matched this campaign target.'
    ];
    if (php_sapi_name() === 'cli') {
        print_r($response);
    } else {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    exit();
}

$sentCount = 0;
$errors = [];

// 4. Send emails
foreach ($recipients as $email) {
    // Wrap content in template
    $htmlBody = emailTemplate($subject, $content);
    
    if (sendEmail($email, $subject, $htmlBody)) {
        $sentCount++;
    } else {
        $errors[] = function_exists('lastMailError')
            ? (lastMailError() ?: 'Mail server rejected a recipient.')
            : 'Mail server rejected a recipient.';
    }
    
    // Update progress every 5 emails to avoid timeout issues if possible
    if ($sentCount % 5 === 0) {
        $pdo->prepare("UPDATE scheduled_emails SET sent_count = ? WHERE id = ?")->execute([$sentCount, $id]);
    }
}

// 5. Finalize status
$finalStatus = ($sentCount === $total && $total > 0) ? 'sent' : ($sentCount > 0 ? 'sent' : 'failed');
$errors = array_values(array_unique($errors));
$errorMsg = !empty($errors) ? implode(", ", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "..." : "") : null;

$stmt = $pdo->prepare("UPDATE scheduled_emails SET status = ?, sent_count = ?, sent_at = NOW(), error_message = ? WHERE id = ?");
$stmt->execute([$finalStatus, $sentCount, $errorMsg, $id]);

$response = [
    'status' => 'success',
    'campaign_id' => $id,
    'sent_count' => $sentCount,
    'total' => $total,
    'message' => $errorMsg,
    'errors' => $errors
];

if (php_sapi_name() === 'cli') {
    print_r($response);
} else {
    header('Content-Type: application/json');
    echo json_encode($response);
}
