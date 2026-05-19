<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

function adminRequireAuth(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: adminlogin.php');
        exit();
    }
}

function adminH($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminMoney(float $value): string
{
    return number_format($value, 2, '.', ',') . ' MAD';
}

function adminRedirect(string $path): never
{
    header('Location: ' . $path);
    exit();
}

function adminTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function adminColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ');
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function ensureAdminUsersTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT 'Administrator',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function adminCountAdmins(PDO $pdo): int
{
    if (!adminTableExists($pdo, 'admin_users')) {
        return 0;
    }
    return (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM admin_users');
}

function adminFetchValue(PDO $pdo, string $sql, array $params = [], $fallback = 0)
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value !== false && $value !== null ? $value : $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
}

function adminFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function adminEnsureProductAdminColumns(PDO $pdo): void
{
    if (!adminTableExists($pdo, 'products')) {
        $pdo->exec("
            CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                brand VARCHAR(100) NOT NULL,
                category VARCHAR(50) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                old_price DECIMAL(10,2) DEFAULT NULL,
                badge VARCHAR(50) DEFAULT NULL,
                rating DECIMAL(2,1) DEFAULT NULL,
                reviews INT DEFAULT 0,
                image VARCHAR(255) DEFAULT NULL,
                featured TINYINT(1) DEFAULT 0,
                in_stock TINYINT(1) DEFAULT 1,
                specs JSON DEFAULT NULL,
                stock_quantity INT NOT NULL DEFAULT 0,
                reorder_level INT NOT NULL DEFAULT 5,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    if (!adminTableExists($pdo, 'products')) {
        return;
    }

    if (!adminColumnExists($pdo, 'products', 'stock_quantity')) {
        $pdo->exec('ALTER TABLE products ADD stock_quantity INT NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE products SET stock_quantity = CASE WHEN in_stock = 1 THEN 10 ELSE 0 END');
    }

    if (!adminColumnExists($pdo, 'products', 'reorder_level')) {
        $pdo->exec('ALTER TABLE products ADD reorder_level INT NOT NULL DEFAULT 5');
    }

    if ((int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM products') === 0) {
        adminImportProductsFromDataJs($pdo);
    }
}

function adminEnsureClientMarketingColumns(PDO $pdo): void
{
    if (!adminTableExists($pdo, 'Client')) {
        return;
    }

    if (!adminColumnExists($pdo, 'Client', 'deleted_at')) {
        try {
            $pdo->exec('ALTER TABLE Client ADD COLUMN deleted_at DATETIME DEFAULT NULL');
        } catch (Throwable $e) {
            // Another request/setup may have added it already.
        }
    }
}

function adminEnsureMarketingTables(PDO $pdo): void
{
    adminEnsureClientMarketingColumns($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!adminColumnExists($pdo, 'newsletter_subscribers', 'subscribed_at')) {
        $pdo->exec('ALTER TABLE newsletter_subscribers ADD subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS scheduled_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            scheduled_at DATETIME NOT NULL,
            recipients_type ENUM('all','subscribers','everyone') DEFAULT 'all',
            status ENUM('pending','sending','sent','failed') DEFAULT 'pending',
            total_recipients INT DEFAULT 0,
            sent_count INT DEFAULT 0,
            sent_at DATETIME DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $scheduledColumns = [
        'recipients_type' => "ALTER TABLE scheduled_emails ADD recipients_type ENUM('all','subscribers','everyone') DEFAULT 'all'",
        'status' => "ALTER TABLE scheduled_emails ADD status ENUM('pending','sending','sent','failed') DEFAULT 'pending'",
        'total_recipients' => 'ALTER TABLE scheduled_emails ADD total_recipients INT DEFAULT 0',
        'sent_count' => 'ALTER TABLE scheduled_emails ADD sent_count INT DEFAULT 0',
        'sent_at' => 'ALTER TABLE scheduled_emails ADD sent_at DATETIME DEFAULT NULL',
        'error_message' => 'ALTER TABLE scheduled_emails ADD error_message TEXT DEFAULT NULL',
        'created_at' => 'ALTER TABLE scheduled_emails ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($scheduledColumns as $column => $sql) {
        if (!adminColumnExists($pdo, 'scheduled_emails', $column)) {
            $pdo->exec($sql);
        }
    }
    try {
        $pdo->exec("ALTER TABLE scheduled_emails MODIFY recipients_type VARCHAR(50) NOT NULL DEFAULT 'all'");
    } catch (Throwable $e) {
        // Older MySQL variants may already be compatible enough for the core targets.
    }

    $pdo->exec("
        UPDATE scheduled_emails
        SET recipients_type = 'all'
        WHERE recipients_type IS NULL
           OR recipients_type NOT IN ('all', 'subscribers', 'everyone', 'high_spenders', 'inactive_30', 'gpu_buyers', 'newsletter_only')
    ");
}

function adminEnsureFlashSalesTable(PDO $pdo): void
{
    adminEnsureProductAdminColumns($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS flash_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            sale_price DECIMAL(10,2) NOT NULL,
            original_price DECIMAL(10,2) NOT NULL,
            max_quantity INT DEFAULT NULL,
            sold_count INT DEFAULT 0,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $flashColumns = [
        'max_quantity' => 'ALTER TABLE flash_sales ADD max_quantity INT DEFAULT NULL',
        'sold_count' => 'ALTER TABLE flash_sales ADD sold_count INT DEFAULT 0',
        'created_at' => 'ALTER TABLE flash_sales ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($flashColumns as $column => $sql) {
        if (!adminColumnExists($pdo, 'flash_sales', $column)) {
            $pdo->exec($sql);
        }
    }
}

function adminEnsureAdminSuiteTables(PDO $pdo): void
{
    adminEnsureProductAdminColumns($pdo);
    adminEnsureMarketingTables($pdo);
    adminEnsureFlashSalesTable($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            actor_email VARCHAR(255) DEFAULT NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NOT NULL,
            entity_id INT DEFAULT NULL,
            summary VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activity_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupon_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NOT NULL UNIQUE,
            discount_type ENUM('percent','fixed','shipping') NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
            min_cart DECIMAL(10,2) NOT NULL DEFAULT 0,
            usage_limit INT DEFAULT NULL,
            used_count INT NOT NULL DEFAULT 0,
            starts_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock_restock_plans (
            product_id INT PRIMARY KEY,
            status ENUM('needed','ordered','received') NOT NULL DEFAULT 'needed',
            expected_at DATE DEFAULT NULL,
            notify_waiting TINYINT(1) NOT NULL DEFAULT 0,
            note VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restock_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            client_id INT DEFAULT NULL,
            notified TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!adminColumnExists($pdo, 'restock_notifications', 'notified_at')) {
        $pdo->exec('ALTER TABLE restock_notifications ADD notified_at DATETIME DEFAULT NULL');
    }
}

function adminLogActivity(PDO $pdo, string $action, string $entityType, ?int $entityId, string $summary): void
{
    try {
        adminEnsureAdminSuiteTables($pdo);
        $actor = trim((string) ($_SESSION['admin_email'] ?? ''));
        $stmt = $pdo->prepare('
            INSERT INTO admin_activity (actor_email, action, entity_type, entity_id, summary)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$actor !== '' ? $actor : null, $action, $entityType, $entityId, substr($summary, 0, 255)]);
    } catch (Throwable $e) {
        // Activity should never block the admin action itself.
    }

    // ── Also log into security_audit_logs for enterprise-grade audit trail ──
    try {
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pdo->prepare('
            INSERT INTO security_audit_logs (admin_id, action, target_table, target_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ')->execute([$adminId, $action, $entityType, $entityId ?? 0, $ip, $ua]);
    } catch (Throwable $e) {
        // Never block the admin action.
    }
}

function adminCouponDiscount(PDO $pdo, string $code, float $subtotal): array
{
    adminEnsureAdminSuiteTables($pdo);
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['valid' => false, 'error' => 'Enter a promo code.'];
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM coupon_codes
        WHERE code = ?
          AND active = 1
          AND (starts_at IS NULL OR starts_at <= NOW())
          AND (expires_at IS NULL OR expires_at >= NOW())
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$coupon) {
        return ['valid' => false, 'error' => 'Invalid or inactive promo code.'];
    }

    if ((float) $coupon['min_cart'] > $subtotal) {
        return ['valid' => false, 'error' => 'Cart total is below the minimum for this code.'];
    }
    if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return ['valid' => false, 'error' => 'This promo code has reached its usage limit.'];
    }

    $type = (string) $coupon['discount_type'];
    $value = (float) $coupon['discount_value'];
    $discount = 0.0;
    if ($type === 'percent') {
        $discount = round($subtotal * min(100, max(0, $value)) / 100, 2);
    } elseif ($type === 'fixed') {
        $discount = min($subtotal, round(max(0, $value), 2));
    } elseif ($type === 'shipping') {
        $discount = 0.0;
    }

    return [
        'valid' => true,
        'id' => (int) $coupon['id'],
        'code' => $code,
        'type' => $type,
        'value' => $value,
        'discount' => $discount,
        'label' => $type === 'shipping' ? 'Free shipping' : ($type === 'percent' ? rtrim(rtrim((string) $value, '0'), '.') . '% off' : adminMoney($discount) . ' off'),
    ];
}

function adminIncrementCouponUse(PDO $pdo, int $couponId): void
{
    if ($couponId <= 0) {
        return;
    }
    $stmt = $pdo->prepare('UPDATE coupon_codes SET used_count = used_count + 1 WHERE id = ?');
    $stmt->execute([$couponId]);
}

function adminNormalizeRecipientsType(?string $type): string
{
    $type = trim((string) $type);
    $allowed = ['all', 'subscribers', 'everyone', 'high_spenders', 'inactive_30', 'gpu_buyers', 'newsletter_only'];
    return in_array($type, $allowed, true) ? $type : 'all';
}

/**
 * @return list<string>
 */
function adminMarketingRecipientEmails(PDO $pdo, ?string $type): array
{
    adminEnsureMarketingTables($pdo);

    $type = adminNormalizeRecipientsType($type);
    $clientEmails = [];
    $subscriberEmails = [];

    if (($type === 'all' || $type === 'everyone') && adminTableExists($pdo, 'Client')) {
        $stmt = $pdo->query("SELECT email FROM Client WHERE deleted_at IS NULL AND email IS NOT NULL AND TRIM(email) <> ''");
        $clientEmails = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    if (($type === 'subscribers' || $type === 'everyone' || $type === 'newsletter_only') && adminTableExists($pdo, 'newsletter_subscribers')) {
        $stmt = $pdo->query("SELECT email FROM newsletter_subscribers WHERE email IS NOT NULL AND TRIM(email) <> ''");
        $subscriberEmails = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    if ($type === 'high_spenders' && adminTableExists($pdo, 'Client') && adminTableExists($pdo, 'orders')) {
        $clientEmails = adminFetchAll($pdo, "
            SELECT c.email
            FROM Client c
            JOIN orders o ON o.client_id = c.id_client
            WHERE c.deleted_at IS NULL AND c.email IS NOT NULL AND TRIM(c.email) <> ''
            GROUP BY c.id_client, c.email
            HAVING COALESCE(SUM(o.total), 0) >= 10000
        ");
        $clientEmails = array_column($clientEmails, 'email');
    }

    if ($type === 'inactive_30' && adminTableExists($pdo, 'Client')) {
        $clientEmails = adminFetchAll($pdo, "
            SELECT c.email
            FROM Client c
            WHERE c.deleted_at IS NULL
              AND c.email IS NOT NULL
              AND TRIM(c.email) <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM orders o
                  WHERE o.client_id = c.id_client
                    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              )
        ");
        $clientEmails = array_column($clientEmails, 'email');
    }

    if ($type === 'gpu_buyers' && adminTableExists($pdo, 'Client') && adminTableExists($pdo, 'orders') && adminTableExists($pdo, 'order_items')) {
        $clientEmails = adminFetchAll($pdo, "
            SELECT DISTINCT c.email
            FROM Client c
            JOIN orders o ON o.client_id = c.id_client
            JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE c.deleted_at IS NULL
              AND c.email IS NOT NULL
              AND TRIM(c.email) <> ''
              AND (
                  LOWER(COALESCE(p.category, '')) LIKE '%gpu%'
                  OR LOWER(COALESCE(oi.name_at_time, '')) LIKE '%rtx%'
                  OR LOWER(COALESCE(oi.name_at_time, '')) LIKE '%radeon%'
                  OR LOWER(COALESCE(oi.name_at_time, '')) LIKE '%gpu%'
              )
        ");
        $clientEmails = array_column($clientEmails, 'email');
    }

    if ($type === 'newsletter_only' && adminTableExists($pdo, 'Client')) {
        $clientLookup = adminFetchAll($pdo, "SELECT LOWER(TRIM(email)) AS email FROM Client WHERE email IS NOT NULL AND TRIM(email) <> ''");
        $clientSet = array_flip(array_column($clientLookup, 'email'));
        $subscriberEmails = array_values(array_filter(
            $subscriberEmails,
            static fn($email): bool => !isset($clientSet[strtolower(trim((string) $email))])
        ));
    }

    $emails = array_map(
        static fn($email): string => strtolower(trim((string) $email)),
        array_merge($clientEmails, $subscriberEmails)
    );

    return array_values(array_unique(array_filter($emails)));
}

function adminCustomerSegmentCounts(PDO $pdo): array
{
    $labels = [
        'all' => 'Registered clients',
        'subscribers' => 'Newsletter subscribers',
        'everyone' => 'Everyone unique',
        'high_spenders' => 'High spenders',
        'inactive_30' => 'Inactive 30 days',
        'gpu_buyers' => 'GPU buyers',
        'newsletter_only' => 'Newsletter only',
    ];
    $counts = [];
    foreach ($labels as $key => $label) {
        $counts[$key] = [
            'label' => $label,
            'count' => adminMarketingRecipientCount($pdo, $key),
        ];
    }
    return $counts;
}

function adminMarketingRecipientCount(PDO $pdo, ?string $type): int
{
    return count(adminMarketingRecipientEmails($pdo, $type));
}

function adminDashboardNotifications(PDO $pdo): array
{
    adminEnsureAdminSuiteTables($pdo);
    $items = [];

    $failedEmails = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM scheduled_emails WHERE status = 'failed'");
    if ($failedEmails > 0) {
        $items[] = ['tone' => 'danger', 'icon' => 'fa-envelope-circle-check', 'title' => 'Failed email campaigns', 'text' => $failedEmails . ' campaign(s) need review.', 'href' => 'admin-marketing.php'];
    }

    $out = (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM products WHERE stock_quantity <= 0 OR in_stock = 0');
    if ($out > 0) {
        $items[] = ['tone' => 'danger', 'icon' => 'fa-box-open', 'title' => 'Products out of stock', 'text' => $out . ' catalog item(s) cannot be sold.', 'href' => 'admin-stock.php'];
    }

    $low = (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity <= reorder_level');
    if ($low > 0) {
        $items[] = ['tone' => 'warn', 'icon' => 'fa-triangle-exclamation', 'title' => 'Low-stock alerts', 'text' => $low . ' item(s) are at or below reorder level.', 'href' => 'admin-stock.php'];
    }

    $restockRequests = adminTableExists($pdo, 'restock_notifications')
        ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM restock_notifications WHERE notified = 0')
        : 0;
    if ($restockRequests > 0) {
        $items[] = ['tone' => 'info', 'icon' => 'fa-bell', 'title' => 'Restock requests', 'text' => $restockRequests . ' customer waitlist request(s).', 'href' => 'admin-stock.php'];
    }

    $newOrders = adminTableExists($pdo, 'orders')
        ? (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")
        : 0;
    if ($newOrders > 0) {
        $items[] = ['tone' => 'info', 'icon' => 'fa-receipt', 'title' => 'Orders in progress', 'text' => $newOrders . ' order(s) still need handling.', 'href' => 'admin-orders.php'];
    }

    return $items;
}

function adminSendRestockNotifications(PDO $pdo, int $productId): int
{
    if (!adminTableExists($pdo, 'restock_notifications')) {
        return 0;
    }
    $product = adminFetchAll($pdo, 'SELECT name FROM products WHERE id = ? LIMIT 1', [$productId])[0] ?? null;
    if (!$product) {
        return 0;
    }

    $rows = adminFetchAll($pdo, 'SELECT id, email FROM restock_notifications WHERE product_id = ? AND notified = 0', [$productId]);
    if ($rows === []) {
        return 0;
    }

    require_once dirname(__DIR__, 2) . '/mailer.php';
    $sent = 0;
    foreach ($rows as $row) {
        $body = emailTemplate('Back in stock', '<p><strong>' . adminH($product['name']) . '</strong> is available again at Maroc PC.</p><div class="btn-wrap"><a href="' . APP_URL . 'products.html" class="btn">Shop now</a></div>');
        if (sendEmail((string) $row['email'], 'Back in stock: ' . $product['name'], $body)) {
            $sent++;
            $pdo->prepare('UPDATE restock_notifications SET notified = 1, notified_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
        }
    }
    return $sent;
}

function adminDataJsPath(): string
{
    return dirname(__DIR__, 2) . '/assets/js/data.js';
}

function adminParseDataJs(): array
{
    $filePath = adminDataJsPath();
    if (!is_file($filePath)) {
        return [];
    }

    $js = (string) file_get_contents($filePath);
    $js = preg_replace('/\/\/[^\n]*/', '', $js);
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);

    $start = strpos($js, '[');
    $end = strrpos($js, ']');
    if ($start === false || $end === false || $end <= $start) {
        return [];
    }

    $jsonLike = substr($js, $start, $end - $start + 1);
    $jsonLike = preg_replace('/,\s*([\]}])/', '$1', $jsonLike);
    $jsonLike = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_ ]*)(\s*:)/', '$1"$2"$3', $jsonLike);
    $jsonLike = str_replace("'", '"', $jsonLike);

    $products = json_decode($jsonLike, true);
    return is_array($products) ? $products : [];
}

function adminImportProductsFromDataJs(PDO $pdo): void
{
    $products = adminParseDataJs();
    if ($products === []) {
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO products
            (id, name, brand, category, price, old_price, badge, rating, reviews, image, featured, in_stock, specs, stock_quantity, reorder_level)
        VALUES
            (:id, :name, :brand, :category, :price, :old_price, :badge, :rating, :reviews, :image, :featured, :in_stock, :specs, :stock_quantity, :reorder_level)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            brand = VALUES(brand),
            category = VALUES(category),
            price = VALUES(price),
            old_price = VALUES(old_price),
            badge = VALUES(badge),
            rating = VALUES(rating),
            reviews = VALUES(reviews),
            image = VALUES(image),
            featured = VALUES(featured),
            in_stock = VALUES(in_stock),
            specs = VALUES(specs),
            stock_quantity = VALUES(stock_quantity),
            reorder_level = VALUES(reorder_level)
    ');

    foreach ($products as $product) {
        if (empty($product['id']) || empty($product['name'])) {
            continue;
        }

        $inStock = !empty($product['inStock']);
        $stmt->execute([
            'id' => (int) $product['id'],
            'name' => (string) $product['name'],
            'brand' => (string) ($product['brand'] ?? ''),
            'category' => (string) ($product['category'] ?? ''),
            'price' => (float) ($product['price'] ?? 0),
            'old_price' => isset($product['oldPrice']) ? (float) $product['oldPrice'] : null,
            'badge' => $product['badge'] ?? null,
            'rating' => isset($product['rating']) ? (float) $product['rating'] : null,
            'reviews' => (int) ($product['reviews'] ?? 0),
            'image' => $product['image'] ?? null,
            'featured' => !empty($product['featured']) ? 1 : 0,
            'in_stock' => $inStock ? 1 : 0,
            'specs' => json_encode($product['specs'] ?? [], JSON_UNESCAPED_SLASHES),
            'stock_quantity' => $inStock ? 10 : 0,
            'reorder_level' => 5,
        ]);
    }
}

function adminSyncMissingStockFromAvailability(PDO $pdo, int $defaultQuantity = 10): int
{
    adminEnsureProductAdminColumns($pdo);

    $stmt = $pdo->prepare('
        UPDATE products
        SET stock_quantity = :default_quantity
        WHERE in_stock = 1 AND stock_quantity <= 0
    ');
    $stmt->execute(['default_quantity' => max(1, $defaultQuantity)]);

    return $stmt->rowCount();
}

function adminExportProductsToDataJs(PDO $pdo): void
{
    $stmt = $pdo->query('
        SELECT id, name, brand, category, price, old_price, badge, rating, reviews, image, featured, in_stock, specs, stock_quantity
        FROM products
        ORDER BY id ASC
    ');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    foreach ($rows as $row) {
        $specs = [];
        if (!empty($row['specs'])) {
            $decodedSpecs = json_decode((string) $row['specs'], true);
            $specs = is_array($decodedSpecs) ? $decodedSpecs : [];
        }

        $product = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'brand' => (string) $row['brand'],
            'category' => (string) $row['category'],
            'price' => (float) $row['price'],
        ];

        if ($row['old_price'] !== null && (float) $row['old_price'] > 0) {
            $product['oldPrice'] = (float) $row['old_price'];
        }

        if (!empty($row['badge'])) {
            $product['badge'] = (string) $row['badge'];
        }

        $product['rating'] = $row['rating'] !== null ? (float) $row['rating'] : 0;
        $product['reviews'] = (int) $row['reviews'];
        $product['image'] = (string) ($row['image'] ?? '');
        $product['featured'] = !empty($row['featured']);
        $product['inStock'] = !empty($row['in_stock']) && (int) $row['stock_quantity'] > 0;
        $product['specs'] = $specs;

        $products[] = $product;
    }

    $json = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Unable to encode products for data.js.');
    }

    $content = "/**\n";
    $content .= " * data.js - Single source of truth for all product data.\n";
    $content .= " * This file is updated by the admin dashboard product tools.\n";
    $content .= " */\n";
    $content .= "const products = " . $json . ";\n";

    if (file_put_contents(adminDataJsPath(), $content, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write assets/js/data.js.');
    }
}

function adminStockBadgeClass(int $stock, int $reorderLevel): string
{
    if ($stock <= 0) {
        return 'is-danger';
    }

    if ($stock <= $reorderLevel) {
        return 'is-warn';
    }

    return 'is-good';
}

function adminPageStart(string $title, string $active): void
{
    $links = [
        'dashboard' => ['dashboard.php', 'fa-shield-halved', 'Dashboard'],
        'products' => ['admin-products.php', 'fa-box', 'Components'],
        'laptops' => ['admin-laptops.php', 'fa-laptop', 'Laptops'],
        'stock' => ['admin-stock.php', 'fa-chart-simple', 'Stock'],
        'procurement' => ['admin-procurement.php', 'fa-truck-ramp-box', 'Procurement'],
        'orders' => ['admin-orders.php', 'fa-receipt', 'Orders'],
        'diagnostics' => ['admin-diagnostics.php', 'fa-screwdriver-wrench', 'Diagnostics'],
        'customers' => ['admin-customers.php', 'fa-users', 'Customers'],
        'analytics' => ['admin-analytics.php', 'fa-chart-pie', 'Analytics'],
        'marketing' => ['admin-marketing.php', 'fa-bullhorn', 'Marketing'],
        'coupons' => ['admin-coupons.php', 'fa-ticket', 'Coupons'],
        'reviews' => ['admin-reviews.php', 'fa-star', 'Reviews'],
        'requests' => ['admin-requests.php', 'fa-inbox', 'Requests'],
        'chatbot' => ['admin-chatbot-feedback.php', 'fa-robot', 'Chatbot Logs'],
        'store' => ['index.html', 'fa-store', 'Storefront'],
        'logout' => ['logout.php', 'fa-sign-out-alt', 'Logout'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= adminH($title) ?> - Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/account.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
	<link rel="stylesheet" href="assets/css/light-mode-industrial.css">
</head>
<body class="admin-body">
    <header class="header">
        <div class="nav-container">
            <a href="index.html" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
                <span class="admin-brand-text"></span>
            </a>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>">Admin</a>
                <a href="products.html" class="nav-link">Store Products</a>
            </nav>
            <div class="nav-spacer"></div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div id="google_translate_element" class="nav-translate"></div>

        </div>
    </header>

    <section class="account-page dashboard-page">
        <div class="container">
            <div class="account-layout">
                <aside class="account-sidebar">
                    <?php foreach ($links as $key => [$href, $icon, $label]): ?>
                        <a href="<?= adminH($href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                            <i class="fas <?= adminH($icon) ?>"></i> <?= adminH($label) ?>
                        </a>
                    <?php endforeach; ?>
                </aside>
                <div class="account-content dashboard-shell">
    <?php
}

function adminPageEnd(): void
{
    ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" style="background: var(--page-bg-2); border-top: 1px solid var(--border); padding: 32px 0; text-align: center;">
        <div class="container">
            <p style="color: var(--muted);">&copy; 2026 Maroc PC. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
    <?php
}
