<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SRC_PATH . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'price-alerts.php';

$root = dirname(__DIR__);
$pdo = db();

function migrateParseDataJs(string $filePath): array
{
    $js = file_get_contents($filePath);
    if ($js === false) {
        throw new RuntimeException("Unable to read {$filePath}");
    }
    $js = preg_replace('/\/\/[^\n]*/', '', $js) ?? $js;
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js) ?? $js;
    $start = strpos($js, '[');
    $end = strrpos($js, ']');
    if ($start === false || $end === false) {
        throw new RuntimeException('Could not find product array in data.js');
    }
    $json = substr($js, $start, $end - $start + 1);
    $json = preg_replace('/,\s*([\]}])/', '$1', $json) ?? $json;
    $json = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_ ]*)(\s*:)/', '$1"$2"$3', $json) ?? $json;
    $json = preg_replace("/'/", '"', $json) ?? $json;
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException('Failed to parse data.js: ' . json_last_error_msg());
    }
    return $data;
}

$pdo->exec("
  CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stock_quantity INT DEFAULT 10,
    reorder_level INT DEFAULT 5
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATE NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_date (product_id, recorded_at),
    INDEX idx_product_date (product_id, recorded_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

ensurePriceAlertsTable($pdo);

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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_price_match_status (status, created_at),
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
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
    approved_at DATETIME DEFAULT NULL,
    INDEX idx_community_status (status, created_at),
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS build_progress_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    stage_key VARCHAR(60) NOT NULL,
    stage_label VARCHAR(120) NOT NULL,
    status ENUM('pending','current','done') NOT NULL DEFAULT 'pending',
    note VARCHAR(255) DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order_stage (order_id, stage_key),
    INDEX idx_build_progress_order (order_id, sort_order),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS referral_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    code VARCHAR(32) NOT NULL UNIQUE,
    bonus_points INT NOT NULL DEFAULT 500,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$products = migrateParseDataJs($root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'data.js');
$sql = "
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
    stock_quantity = CASE
        WHEN products.stock_quantity <= 0 AND VALUES(in_stock) = 1 THEN VALUES(stock_quantity)
        WHEN VALUES(in_stock) = 0 THEN 0
        ELSE products.stock_quantity
    END,
    reorder_level = CASE
        WHEN products.reorder_level <= 0 THEN VALUES(reorder_level)
        ELSE products.reorder_level
    END
";

$stmt = $pdo->prepare($sql);
$imported = 0;
$pdo->beginTransaction();
try {
    foreach ($products as $product) {
        $stmt->execute([
            ':id' => (int) $product['id'],
            ':name' => (string) $product['name'],
            ':brand' => (string) $product['brand'],
            ':category' => (string) $product['category'],
            ':price' => (float) $product['price'],
            ':old_price' => $product['oldPrice'] ?? null,
            ':badge' => $product['badge'] ?? null,
            ':rating' => $product['rating'] ?? null,
            ':reviews' => $product['reviews'] ?? 0,
            ':image' => $product['image'] ?? null,
            ':featured' => !empty($product['featured']) ? 1 : 0,
            ':in_stock' => !empty($product['inStock']) ? 1 : 0,
            ':specs' => json_encode($product['specs'] ?? [], JSON_UNESCAPED_UNICODE),
            ':stock_quantity' => !empty($product['inStock']) ? 10 : 0,
            ':reorder_level' => 5,
        ]);
        $imported++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$today = date('Y-m-d');
$snapshot = $pdo->prepare("
    INSERT IGNORE INTO price_history (product_id, price, recorded_at)
    SELECT id, price, ? FROM products
");
$snapshot->execute([$today]);

$accessories = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE category = 'accessories'")->fetchColumn();
$alertsTable = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'price_alerts'")->fetchColumn();
$workflowTables = ['price_match_requests', 'community_builds', 'build_progress_events', 'trade_in_requests', 'bank_transfer_receipts', 'referral_codes'];
$workflowReady = 0;
foreach ($workflowTables as $tableName) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $check->execute([$tableName]);
    $workflowReady += (int) $check->fetchColumn();
}

echo "Imported {$imported} products from data.js\n";
echo "Accessories in database: {$accessories}\n";
echo "Today's price snapshot ensured for {$today}\n";
echo "price_alerts table: " . ($alertsTable ? 'ready' : 'missing') . "\n";
echo "new.md workflow tables ready: {$workflowReady}/" . count($workflowTables) . "\n";
