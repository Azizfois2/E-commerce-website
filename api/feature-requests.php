<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$pdo = db();

function featureEnsureTables(PDO $pdo): void
{
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS price_match_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        product_id INT DEFAULT NULL,
        product_name VARCHAR(255) NOT NULL,
        competitor_url VARCHAR(500) DEFAULT NULL,
        competitor_price DECIMAL(10,2) DEFAULT NULL,
        contact_email VARCHAR(255) DEFAULT NULL,
        contact_phone VARCHAR(32) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        status ENUM('new','reviewing','matched','declined') NOT NULL DEFAULT 'new',
        admin_note TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS community_builds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        build_name VARCHAR(255) NOT NULL,
        use_case VARCHAR(80) DEFAULT NULL,
        components JSON DEFAULT NULL,
        total_price DECIMAL(10,2) DEFAULT 0,
        image_url VARCHAR(500) DEFAULT NULL,
        caption TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        likes INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS trade_in_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        hardware_type VARCHAR(80) NOT NULL,
        hardware_name VARCHAR(255) NOT NULL,
        condition_grade VARCHAR(40) NOT NULL,
        estimated_value DECIMAL(10,2) DEFAULT NULL,
        contact_email VARCHAR(255) DEFAULT NULL,
        contact_phone VARCHAR(32) DEFAULT NULL,
        status ENUM('new','quoted','accepted','declined') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS bank_transfer_receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        order_id INT DEFAULT NULL,
        bank_name VARCHAR(80) NOT NULL,
        transfer_reference VARCHAR(120) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        receipt_path VARCHAR(500) DEFAULT NULL,
        status ENUM('new','verified','rejected') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS referral_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        code VARCHAR(32) NOT NULL UNIQUE,
        bonus_points INT NOT NULL DEFAULT 500,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function featureInput(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function featureClientId(): ?int
{
    return !empty($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
}

function featureTrim($value, int $max = 500): string
{
    return substr(trim((string) $value), 0, $max);
}

function featureEstimateTradeIn(string $type, string $condition, string $name): float
{
    $base = [
        'gpu' => 1700,
        'cpu' => 900,
        'ram' => 350,
        'storage' => 300,
        'motherboard' => 600,
        'laptop' => 2600,
    ][strtolower($type)] ?? 500;

    $conditionFactor = [
        'excellent' => 1.0,
        'good' => 0.78,
        'fair' => 0.56,
        'parts' => 0.28,
    ][strtolower($condition)] ?? 0.6;

    $nameLower = strtolower($name);
    if (str_contains($nameLower, 'rtx 40') || str_contains($nameLower, 'rtx 50')) $base *= 2.0;
    if (str_contains($nameLower, 'gtx 10') || str_contains($nameLower, 'rx 5')) $base *= 0.65;
    if (str_contains($nameLower, 'i9') || str_contains($nameLower, 'ryzen 9')) $base *= 1.45;
    if (str_contains($nameLower, 'i3') || str_contains($nameLower, 'ryzen 3')) $base *= 0.62;

    return round($base * $conditionFactor, -1);
}

featureEnsureTables($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'community') {
        $rows = $pdo->query("
            SELECT id, build_name, use_case, components, total_price, image_url, caption, likes, created_at
            FROM community_builds
            WHERE status = 'approved'
            ORDER BY created_at DESC
            LIMIT 24
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['components'] = json_decode((string) ($row['components'] ?? '[]'), true) ?: [];
            $row['total_price'] = (float) $row['total_price'];
            $row['likes'] = (int) $row['likes'];
        }
        jsonResponse(true, 'Community builds loaded.', ['builds' => $rows]);
    }

    if ($method === 'GET' && $action === 'referral') {
        $clientId = featureClientId();
        if (!$clientId) {
            jsonResponse(false, 'Sign in to generate a referral link.');
        }
        $stmt = $pdo->prepare('SELECT code, bonus_points FROM referral_codes WHERE client_id = ? LIMIT 1');
        $stmt->execute([$clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $insert = $pdo->prepare('INSERT INTO referral_codes (client_id, code) VALUES (?, ?)');
            $insert->execute([$clientId, $code]);
            $row = ['code' => $code, 'bonus_points' => 500];
        }
        jsonResponse(true, 'Referral code ready.', [
            'code' => $row['code'],
            'bonus_points' => (int) $row['bonus_points'],
            'url' => APP_URL . 'signup.php?ref=' . rawurlencode((string) $row['code']),
        ]);
    }

    if ($method !== 'POST') {
        jsonResponse(false, 'Unsupported request method.');
    }

    $input = featureInput();
    $clientId = featureClientId();
    $action = featureTrim($input['action'] ?? $action, 60);

    if ($action === 'price_match') {
        $productName = featureTrim($input['product_name'] ?? '', 255);
        if ($productName === '') {
            jsonResponse(false, 'Product name is required.');
        }
        $stmt = $pdo->prepare("
            INSERT INTO price_match_requests
              (client_id, product_id, product_name, competitor_url, competitor_price, contact_email, contact_phone, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId,
            !empty($input['product_id']) ? (int) $input['product_id'] : null,
            $productName,
            featureTrim($input['competitor_url'] ?? '', 500) ?: null,
            isset($input['competitor_price']) && $input['competitor_price'] !== '' ? (float) $input['competitor_price'] : null,
            featureTrim($input['contact_email'] ?? '', 255) ?: null,
            featureTrim($input['contact_phone'] ?? '', 32) ?: null,
            featureTrim($input['notes'] ?? '', 1000) ?: null,
        ]);
        jsonResponse(true, 'Price match request sent. The admin queue now has it.');
    }

    if ($action === 'community_build') {
        $buildName = featureTrim($input['build_name'] ?? '', 255);
        if ($buildName === '') {
            jsonResponse(false, 'Build name is required.');
        }
        $components = $input['components'] ?? [];
        $stmt = $pdo->prepare("
            INSERT INTO community_builds
              (client_id, build_name, use_case, components, total_price, image_url, caption)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId,
            $buildName,
            featureTrim($input['use_case'] ?? '', 80) ?: null,
            json_encode(is_array($components) ? $components : [], JSON_UNESCAPED_UNICODE),
            isset($input['total_price']) ? (float) $input['total_price'] : 0,
            featureTrim($input['image_url'] ?? '', 500) ?: null,
            featureTrim($input['caption'] ?? '', 1200) ?: null,
        ]);
        jsonResponse(true, 'Build submitted for admin approval.');
    }

    if ($action === 'trade_in') {
        $type = featureTrim($input['hardware_type'] ?? '', 80);
        $name = featureTrim($input['hardware_name'] ?? '', 255);
        $condition = featureTrim($input['condition_grade'] ?? '', 40);
        if ($type === '' || $name === '' || $condition === '') {
            jsonResponse(false, 'Hardware type, name, and condition are required.');
        }
        $estimate = featureEstimateTradeIn($type, $condition, $name);
        $stmt = $pdo->prepare("
            INSERT INTO trade_in_requests
              (client_id, hardware_type, hardware_name, condition_grade, estimated_value, contact_email, contact_phone)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId,
            $type,
            $name,
            $condition,
            $estimate,
            featureTrim($input['contact_email'] ?? '', 255) ?: null,
            featureTrim($input['contact_phone'] ?? '', 32) ?: null,
        ]);
        jsonResponse(true, 'Trade-in estimate saved for review.', ['estimated_value' => $estimate]);
    }

    if ($action === 'receipt') {
        $bank = featureTrim($input['bank_name'] ?? '', 80);
        $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
        if ($bank === '' || $amount <= 0) {
            jsonResponse(false, 'Bank name and transfer amount are required.');
        }
        $stmt = $pdo->prepare("
            INSERT INTO bank_transfer_receipts
              (client_id, order_id, bank_name, transfer_reference, amount, receipt_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId,
            !empty($input['order_id']) ? (int) $input['order_id'] : null,
            $bank,
            featureTrim($input['transfer_reference'] ?? '', 120) ?: null,
            $amount,
            featureTrim($input['receipt_path'] ?? '', 500) ?: null,
        ]);
        jsonResponse(true, 'Transfer receipt logged for admin verification.');
    }

    jsonResponse(false, 'Unknown feature request action.');
} catch (Throwable $e) {
    jsonResponse(false, DEV_MODE ? $e->getMessage() : 'Request failed.');
}
