<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/mailer.php';

function ensurePriceAlertsTable(PDO $pdo): void
{
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS price_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        product_id INT NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(32) DEFAULT NULL,
        channel ENUM('email','whatsapp','both') NOT NULL DEFAULT 'email',
        threshold DECIMAL(10,2) NOT NULL,
        status ENUM('active','triggered','cancelled') NOT NULL DEFAULT 'active',
        last_notified_at DATETIME DEFAULT NULL,
        triggered_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_alert_product_status (product_id, status),
        INDEX idx_alert_client_status (client_id, status)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function normalizeAlertPhone(string $phone): string
{
    $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';
    if ($phone === '') {
        return '';
    }
    if ($phone[0] !== '+') {
        $phone = '+212' . ltrim($phone, '0');
    }
    return preg_match('/^\+\d{8,15}$/', $phone) ? $phone : '';
}

function sendPriceDropWhatsApp(string $phone, string $productName, float $price, string $url): bool
{
    $phone = normalizeAlertPhone($phone);
    if ($phone === '') {
        return false;
    }

    $message = sprintf(
        "Maroc PC price alert: %s is now %.2f MAD. View it: %s",
        $productName,
        $price,
        $url
    );

    if (defined('EVOLUTION_API_KEY') && EVOLUTION_API_KEY !== '' && function_exists('curl_init')) {
        $payload = json_encode([
            'number' => ltrim($phone, '+'),
            'text' => $message,
        ]);

        if ($payload !== false && function_exists('curl_init')) {
            $apiUrl = rtrim(EVOLUTION_API_URL, '/') . '/message/sendText/' . rawurlencode(EVOLUTION_INSTANCE_NAME);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($status >= 200 && $status < 300) {
                return true;
            }
            error_log('[EVOLUTION PRICE ALERT ERROR] ' . ($err ?: (string) $raw));
        }
    }

    if (DEV_MODE) {
        error_log("[WHATSAPP PRICE ALERT] To {$phone}: {$message}");
        return true;
    }

    return false;
}

function sendPriceDropEmail(string $email, string $name, float $price, float $threshold, string $url): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $body = emailTemplate('Price Drop Alert', '
        <p>The component you are watching has dropped below your target price.</p>
        <div class="highlight">
            <p><strong>' . $safeName . '</strong></p>
            <p style="margin-top:10px;">
                Current price: <strong>' . number_format($price, 2) . ' MAD</strong><br>
                Your threshold: ' . number_format($threshold, 2) . ' MAD
            </p>
        </div>
        <div class="btn-wrap">
            <a href="' . $safeUrl . '" class="btn">View Product</a>
        </div>
        <p class="small">You received this because you created a Maroc PC price alert.</p>
    ');

    return sendEmail($email, 'Price Drop Alert: ' . $name, $body);
}

function processDuePriceAlerts(PDO $pdo): array
{
    ensurePriceAlertsTable($pdo);

    $stmt = $pdo->query("
        SELECT
            pa.*,
            p.name AS product_name,
            p.price AS current_price,
            c.email AS client_email,
            c.telephone AS client_phone
        FROM price_alerts pa
        JOIN products p ON p.id = pa.product_id
        LEFT JOIN Client c ON c.id_client = pa.client_id
        WHERE pa.status = 'active'
          AND p.price <= pa.threshold
          AND (pa.last_notified_at IS NULL OR pa.last_notified_at < DATE_SUB(NOW(), INTERVAL 1 DAY))
        ORDER BY pa.created_at ASC
        LIMIT 100
    ");

    $alerts = $stmt->fetchAll();
    $sent = 0;
    $failed = 0;
    $productBaseUrl = APP_URL . 'products.html?search=';

    foreach ($alerts as $alert) {
        $email = (string) ($alert['email'] ?: $alert['client_email'] ?: '');
        $phone = (string) ($alert['phone'] ?: $alert['client_phone'] ?: '');
        $channel = (string) $alert['channel'];
        $name = (string) $alert['product_name'];
        $price = (float) $alert['current_price'];
        $threshold = (float) $alert['threshold'];
        $url = $productBaseUrl . urlencode($name);

        $ok = false;
        if (($channel === 'email' || $channel === 'both') && $email !== '') {
            $ok = sendPriceDropEmail($email, $name, $price, $threshold, $url) || $ok;
        }
        if (($channel === 'whatsapp' || $channel === 'both') && $phone !== '') {
            $ok = sendPriceDropWhatsApp($phone, $name, $price, $url) || $ok;
        }

        if ($ok) {
            $sent++;
            $update = $pdo->prepare("
                UPDATE price_alerts
                SET status = 'triggered', last_notified_at = NOW(), triggered_at = NOW()
                WHERE id = ?
            ");
            $update->execute([(int) $alert['id']]);
        } else {
            $failed++;
            $update = $pdo->prepare("UPDATE price_alerts SET last_notified_at = NOW() WHERE id = ?");
            $update->execute([(int) $alert['id']]);
        }
    }

    return ['checked' => count($alerts), 'sent' => $sent, 'failed' => $failed];
}
