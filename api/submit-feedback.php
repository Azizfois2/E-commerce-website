<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Services/mailer.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$type = trim($input['type'] ?? '');
$rating = (int) ($input['rating'] ?? 0);
$message = trim($input['message'] ?? '');

if (empty($name) || empty($email) || empty($type) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit;
}

// Validate type
$validTypes = ['suggestion', 'bug', 'compliment', 'complaint', 'question', 'other'];
if (!in_array($type, $validTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid feedback type']);
    exit;
}

try {
    $pdo = db();
    
    // Create feedback table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            type ENUM('suggestion', 'bug', 'compliment', 'complaint', 'question', 'other') NOT NULL,
            rating TINYINT NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'reviewed', 'resolved', 'archived') DEFAULT 'new',
            admin_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_type (type),
            INDEX idx_created (created_at),
            FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Get client_id if user is logged in
    $clientId = !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
    
    // Insert feedback
    $stmt = $pdo->prepare("
        INSERT INTO customer_feedback (client_id, name, email, type, rating, message, status)
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");
    
    $stmt->execute([
        $clientId,
        $name,
        $email,
        $type,
        $rating,
        $message
    ]);
    
    $feedbackId = $pdo->lastInsertId();
    
    // Send confirmation email to user using PHPMailer
    $typeEmojis = [
        'suggestion' => '💡',
        'bug' => '🐛',
        'compliment' => '⭐',
        'complaint' => '⚠️',
        'question' => '❓',
        'other' => '📝'
    ];
    
    $typeLabel = ucfirst($type);
    $typeEmoji = $typeEmojis[$type] ?? '📝';
    $stars = str_repeat('⭐', $rating);
    
    $userEmailBody = emailTemplate('Thank You for Your Feedback! ' . $typeEmoji, '
        <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
        <p>Thank you for taking the time to share your feedback with us. We truly appreciate your input!</p>
        <div class="highlight">
            <p><strong>Your Feedback Summary:</strong></p>
            <p style="margin-top:10px;">
                <strong>Type:</strong> ' . $typeEmoji . ' ' . $typeLabel . '<br>
                <strong>Rating:</strong> ' . $stars . ' (' . $rating . '/5)<br>
                <strong>Reference ID:</strong> #' . str_pad((string)$feedbackId, 6, '0', STR_PAD_LEFT) . '
            </p>
            <p style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,245,212,0.1);">
                <strong>Your Message:</strong><br>
                <span style="color:#b0b8c8; font-style:italic;">"' . nl2br(htmlspecialchars(substr($message, 0, 200))) . (strlen($message) > 200 ? '..."' : '"') . '</span>
            </p>
        </div>
        <p>Our team reviews all feedback within 24-48 hours. If your feedback requires a response, we\'ll get back to you at this email address.</p>
        <div class="btn-wrap">
            <a href="' . APP_URL . 'index.html" class="btn">Visit Our Store</a>
        </div>
        <p class="small">Your feedback helps us improve our services and provide you with the best experience possible. Thank you for being a valued part of the Maroc PC community!</p>
    ');
    
    $userEmailSent = sendEmail($email, 'Thank you for your feedback - Maroc PC', $userEmailBody);
    
    // Send notification email to admin
    try {
        $adminEmail = envString('ADMIN_EMAIL', 'admin@marocpc.com');
        $adminEmailBody = emailTemplate('New Customer Feedback Received', '
            <p>A new feedback submission has been received:</p>
            <div class="highlight">
                <p><strong>From:</strong> ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')<br>
                <strong>Type:</strong> ' . $typeEmoji . ' ' . $typeLabel . '<br>
                <strong>Rating:</strong> ' . $stars . ' (' . $rating . '/5)<br>
                <strong>Reference ID:</strong> #' . str_pad((string)$feedbackId, 6, '0', STR_PAD_LEFT) . '</p>
                <p style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,245,212,0.1);">
                    <strong>Message:</strong><br>
                    <span style="color:#b0b8c8;">' . nl2br(htmlspecialchars($message)) . '</span>
                </p>
            </div>
            <div class="btn-wrap">
                <a href="' . APP_URL . 'admin-feedback.php" class="btn">View in Dashboard</a>
            </div>
            <p class="small">This is an automated notification from your Maroc PC feedback system.</p>
        ');
        
        sendEmail($adminEmail, "New Feedback: $typeLabel from $name", $adminEmailBody);
    } catch (Exception $e) {
        // Admin email failed, but user confirmation was sent
        error_log("Failed to send admin feedback notification: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your feedback! We\'ve sent a confirmation to your email.',
        'feedback_id' => $feedbackId,
        'email_sent' => $userEmailSent
    ]);
    
} catch (PDOException $e) {
    error_log("Feedback submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred. Please try again later.']);
}
