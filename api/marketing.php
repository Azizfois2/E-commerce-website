<?php
/**
 * api/marketing.php — Seasonal Campaigns and Newsletter Templates
 *
 * GET  ?action=active_campaign
 * POST { action: "create_campaign", ... } (admin)
 * GET  ?action=newsletter_templates (admin)
 * POST { action: "save_template", ... } (admin)
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'active_campaign';

    if ($action === 'active_campaign') {
        // Fetch current active campaign
        $stmt = $pdo->prepare("
            SELECT * FROM marketing_seasonal_campaigns 
            WHERE is_active = 1 
              AND starts_at <= CURRENT_DATE 
              AND ends_at >= CURRENT_DATE 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute();
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'campaign' => $campaign ?: null]);
        exit;
    }

    // Admin routes
    if (empty($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'newsletter_templates') {
        $stmt = $pdo->query("SELECT * FROM newsletter_campaign_templates ORDER BY created_at DESC");
        echo json_encode(['success' => true, 'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

if ($method === 'POST') {
    if (empty($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'create_campaign') {
        $name = trim($input['name'] ?? '');
        $discount = (float)($input['discount_percentage'] ?? 0);
        $startsAt = $input['starts_at'] ?? '';
        $endsAt = $input['ends_at'] ?? '';
        $bannerUrl = trim($input['banner_image_url'] ?? '');

        if (empty($name) || empty($startsAt) || empty($endsAt)) {
            echo json_encode(['error' => 'Invalid campaign data']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO marketing_seasonal_campaigns (name, discount_percentage, starts_at, ends_at, banner_image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $discount, $startsAt, $endsAt, $bannerUrl]);

        echo json_encode(['success' => true, 'campaign_id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'save_template') {
        $name = trim($input['template_name'] ?? '');
        $subject = trim($input['subject_line'] ?? '');
        $html = trim($input['html_content'] ?? '');

        if (empty($name) || empty($subject) || empty($html)) {
            echo json_encode(['error' => 'Invalid template data']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO newsletter_campaign_templates (template_name, subject_line, html_content) VALUES (?, ?, ?)");
        $stmt->execute([$name, $subject, $html]);

        echo json_encode(['success' => true, 'template_id' => $pdo->lastInsertId()]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
