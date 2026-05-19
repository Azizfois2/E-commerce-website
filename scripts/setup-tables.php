<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$pdo = db();

// Client table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS Client (
    id_client       INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    date_naissance  DATE         DEFAULT NULL,
    moyen_paiement  VARCHAR(50)  DEFAULT 'not_set',
    adresse         TEXT         DEFAULT NULL,
    telephone       VARCHAR(20)  DEFAULT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    failed_login_attempts INT    NOT NULL DEFAULT 0,
    locked_until    DATETIME     DEFAULT NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add email_verified column to Client if it doesn't exist
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
} catch (PDOException $e) {
    // Column already exists — ignore
}

// Add google_id column for OAuth
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE");
} catch (PDOException $e) {
    // Column already exists — ignore
}

// Add login lockout columns
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0");
} catch (PDOException $e) {
    // Column already exists.
}

try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN locked_until DATETIME DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists.
}

// Add email-based 2FA flag
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0");
} catch (PDOException $e) {
    // Column already exists.
}

// Email verifications table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS email_verifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    token_hash      VARCHAR(64) NOT NULL,
    expires_at      DATETIME NOT NULL,
    used            TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    INDEX idx_email (email)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Password resets table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS password_resets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    token_hash      VARCHAR(64) NOT NULL,
    expires_at      DATETIME NOT NULL,
    used            TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Admin login attempt throttling
$pdo->exec("
  CREATE TABLE IF NOT EXISTS admin_login_attempts (
    attempt_key     VARCHAR(64) PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    ip_address      VARCHAR(45) NOT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until    DATETIME DEFAULT NULL,
    last_failed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_locked_until (locked_until)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Orders table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT          NOT NULL,
    status          ENUM('pending','processing','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
    total           DECIMAL(10,2) NOT NULL,
    shipping_method VARCHAR(50)  DEFAULT NULL,
    shipping_address TEXT        DEFAULT NULL,
    billing_address  TEXT        DEFAULT NULL,
    payment_method  VARCHAR(50)  DEFAULT NULL,
    payment_status  ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    stock_reserved   TINYINT(1)  NOT NULL DEFAULT 0,
    estimated_delivery DATE      DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add assembly_status column to orders if it doesn't exist
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN assembly_status ENUM('not_applicable','gathering_parts','building','testing','qc_passed','ready') DEFAULT 'not_applicable'");
} catch (PDOException $e) {
    // Column already exists
}

// Order items table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT          NOT NULL,
    product_id      INT          DEFAULT NULL,
    quantity        INT          NOT NULL,
    price_at_time   DECIMAL(10,2) NOT NULL,
    name_at_time    VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Abandoned Carts table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS abandoned_carts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    cart_data       JSON NOT NULL,
    locked_at       DATETIME NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_client_cart (client_id),
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Order status history
$pdo->exec("
  CREATE TABLE IF NOT EXISTS order_status_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT          NOT NULL,
    old_status      VARCHAR(20),
    new_status      VARCHAR(20)  NOT NULL,
    changed_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    changed_by      ENUM('system','admin','customer') DEFAULT 'system',
    notes           TEXT         DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Newsletter subscribers table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Scheduled emails for marketing campaigns
$pdo->exec("
  CREATE TABLE IF NOT EXISTS scheduled_emails (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    subject         VARCHAR(255) NOT NULL,
    content         TEXT NOT NULL,
    scheduled_at    DATETIME NOT NULL,
    recipients_type ENUM('all','subscribers','everyone') DEFAULT 'all',
    status          ENUM('pending','sending','sent','failed') DEFAULT 'pending',
    total_recipients INT DEFAULT 0,
    sent_count      INT DEFAULT 0,
    sent_at         DATETIME DEFAULT NULL,
    error_message   TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add email_verified column to Client if it doesn't exist
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN deleted_at DATETIME DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists
}

// Flash sales table
$pdo->exec("
  CREATE TABLE IF NOT EXISTS flash_sales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    product_id      INT NOT NULL,
    sale_price      DECIMAL(10,2) NOT NULL,
    original_price  DECIMAL(10,2) NOT NULL,
    max_quantity    INT DEFAULT NULL,
    sold_count      INT DEFAULT 0,
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Phase 2: Product Reviews & Verified Badges
$pdo->exec("
  CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    client_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    photo_url VARCHAR(255) DEFAULT NULL,
    is_verified_purchase TINYINT(1) DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    helpful_count INT DEFAULT 0,
    unhelpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS review_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    client_id INT NOT NULL,
    vote ENUM('helpful','unhelpful') NOT NULL,
    UNIQUE KEY unique_vote (review_id, client_id),
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (client_id, product_id),
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
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
    UNIQUE KEY unique_notify (product_id, email),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Phase 3: PC Builder — saved builds
$pdo->exec("
  CREATE TABLE IF NOT EXISTS saved_builds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT DEFAULT NULL,
    share_code VARCHAR(12) UNIQUE NOT NULL,
    build_name VARCHAR(100) DEFAULT NULL,
    use_case ENUM('gaming','streaming','editing','office','general') DEFAULT 'general',
    components JSON NOT NULL,
    total_price DECIMAL(10,2) DEFAULT 0,
    total_wattage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Phase 3: PC Builder — product compatibility data
$pdo->exec("
  CREATE TABLE IF NOT EXISTS product_compatibility (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    socket VARCHAR(50) DEFAULT NULL,
    ram_type ENUM('DDR4','DDR5') DEFAULT NULL,
    form_factor VARCHAR(30) DEFAULT NULL,
    max_tdp INT DEFAULT NULL,
    wattage INT DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Phase 3: Loyalty Points & Rewards
$pdo->exec("
  CREATE TABLE IF NOT EXISTS loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    points INT NOT NULL,
    source ENUM('purchase','review','referral','bonus','redemption') NOT NULL,
    order_id INT DEFAULT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add loyalty columns to Client
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN loyalty_tier ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze'");
} catch (PDOException $e) {
    // Column already exists
}
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN total_points INT DEFAULT 0");
} catch (PDOException $e) {
    // Column already exists
}

// Phase 3: Price History Charts
$pdo->exec("
  CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATE NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_date (product_id, recorded_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Admin users (password_hash; create via php create-admin.php) ──
$pdo->exec("
  CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'Administrator',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Optional payment columns on orders (also ensured by checkout) ──
try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN transaction_id VARCHAR(64) DEFAULT NULL AFTER payment_status');
} catch (PDOException $e) {
}
try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN paypal_order_id VARCHAR(64) DEFAULT NULL AFTER transaction_id');
} catch (PDOException $e) {
}

// ── Product listing indexes (ignore if duplicate) ──
foreach (['CREATE INDEX idx_products_category ON products (category)', 'CREATE INDEX idx_products_featured ON products (featured)'] as $idxSql) {
    try {
        $pdo->exec($idxSql);
    } catch (PDOException $e) {
    }
}

// Phase 5: Maintenance & RMA Requests
$pdo->exec("
  CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    order_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    issue_type VARCHAR(50) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Enforce Unique Telephone Constraint ──
try {
    // Fetch all duplicate telephone numbers that are not null or empty
    $stmt = $pdo->query("SELECT telephone, MIN(id_client) as first_id FROM Client WHERE telephone IS NOT NULL AND telephone != '' GROUP BY telephone HAVING COUNT(*) > 1");
    $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dupes as $dupe) {
        $tel = $dupe['telephone'];
        $firstId = $dupe['first_id'];
        // Nullify duplicate entries except the first client
        $upd = $pdo->prepare("UPDATE Client SET telephone = NULL WHERE telephone = ? AND id_client != ?");
        $upd->execute([$tel, $firstId]);
    }
} catch (PDOException $e) {
    // Ignore potential errors if query fails
}

try {
    $pdo->exec("ALTER TABLE Client ADD UNIQUE KEY unique_telephone (telephone)");
} catch (PDOException $e) {
    // Column might already have a unique index
}

// ── 30 Advanced Tables Database Expansion ──

// 1. component_compatibility_rules
$pdo->exec("
  CREATE TABLE IF NOT EXISTS component_compatibility_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_type ENUM('socket', 'chipset', 'ram_type', 'pcie_generation', 'form_factor') NOT NULL,
    source_spec VARCHAR(100) NOT NULL,
    target_spec VARCHAR(100) NOT NULL,
    is_compatible TINYINT(1) DEFAULT 1,
    warning_message VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 2. custom_build_presets
$pdo->exec("
  CREATE TABLE IF NOT EXISTS custom_build_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    target_category ENUM('gaming', 'workstation', 'office', 'streaming') NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    products_json JSON NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 3. loyalty_rewards_catalog
$pdo->exec("
  CREATE TABLE IF NOT EXISTS loyalty_rewards_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    points_required INT NOT NULL,
    reward_type ENUM('coupon', 'free_shipping', 'free_merchandise', 'warranty_upgrade') NOT NULL,
    reward_value VARCHAR(255) NOT NULL,
    stock_remaining INT DEFAULT 99,
    is_active TINYINT(1) DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 4. marketing_seasonal_campaigns
$pdo->exec("
  CREATE TABLE IF NOT EXISTS marketing_seasonal_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    tagline VARCHAR(255) DEFAULT NULL,
    banner_url VARCHAR(255) DEFAULT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 5. newsletter_campaign_templates
$pdo->exec("
  CREATE TABLE IF NOT EXISTS newsletter_campaign_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(150) NOT NULL,
    body_html TEXT NOT NULL,
    target_segment ENUM('all', 'gamers', 'professionals', 'inactive_30d') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 6. vendor_suppliers
$pdo->exec("
  CREATE TABLE IF NOT EXISTS vendor_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT,
    currency VARCHAR(3) DEFAULT 'MAD'
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 7. api_rate_limits
$pdo->exec("
  CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_count INT DEFAULT 1,
    window_expires_at DATETIME NOT NULL,
    UNIQUE KEY unique_ip_endpoint (ip_address, endpoint, window_expires_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 8. user_build_snapshots
$pdo->exec("
  CREATE TABLE IF NOT EXISTS user_build_snapshots (
    share_token VARCHAR(64) PRIMARY KEY,
    client_id INT DEFAULT NULL,
    build_name VARCHAR(100) DEFAULT 'My Custom Build',
    config_data JSON NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 9. parts_power_specifications
$pdo->exec("
  CREATE TABLE IF NOT EXISTS parts_power_specifications (
    product_id INT PRIMARY KEY,
    watts_tdp INT NOT NULL,
    peak_watts INT NOT NULL,
    recommended_min_psu_watts INT DEFAULT 500,
    rail_12v_amps DECIMAL(5,2) DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 10. chassis_clearance_specs
$pdo->exec("
  CREATE TABLE IF NOT EXISTS chassis_clearance_specs (
    product_id INT PRIMARY KEY,
    type ENUM('case', 'gpu', 'cooler', 'aio_radiator') NOT NULL,
    max_gpu_length_mm INT DEFAULT NULL,
    gpu_length_mm INT DEFAULT NULL,
    max_cpu_cooler_height_mm INT DEFAULT NULL,
    cooler_height_mm INT DEFAULT NULL,
    max_radiator_size INT DEFAULT NULL,
    radiator_size INT DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 11. chatbot_conversations
$pdo->exec("
  CREATE TABLE IF NOT EXISTS chatbot_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT DEFAULT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    chat_history JSON NOT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 12. hardware_diagnostic_reports
$pdo->exec("
  CREATE TABLE IF NOT EXISTS hardware_diagnostic_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    cpu_temp_idle INT NOT NULL,
    cpu_temp_load INT NOT NULL,
    gpu_temp_load INT NOT NULL,
    cinebench_score INT DEFAULT NULL,
    timespy_score INT DEFAULT NULL,
    technician_notes TEXT,
    diagnostic_file_url VARCHAR(255) DEFAULT NULL,
    certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 13. ai_product_recommendations
$pdo->exec("
  CREATE TABLE IF NOT EXISTS ai_product_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    product_id INT NOT NULL,
    recommendation_score DECIMAL(4,3) NOT NULL,
    reason_code VARCHAR(100) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 14. ai_chatbot_fine_tuning_logs
$pdo->exec("
  CREATE TABLE IF NOT EXISTS ai_chatbot_fine_tuning_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_query TEXT NOT NULL,
    bot_reply TEXT NOT NULL,
    corrected_reply TEXT NOT NULL,
    admin_id INT NOT NULL,
    applied_to_knowledge TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 15. loyalty_redemptions
$pdo->exec("
  CREATE TABLE IF NOT EXISTS loyalty_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_spent INT NOT NULL,
    status ENUM('redeemed', 'shipped', 'cancelled') DEFAULT 'redeemed',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES loyalty_rewards_catalog(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 16. product_wishlist_alerts
$pdo->exec("
  CREATE TABLE IF NOT EXISTS product_wishlist_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    product_id INT NOT NULL,
    target_price DECIMAL(10,2) DEFAULT NULL,
    alert_by_email TINYINT(1) DEFAULT 1,
    alert_by_whatsapp TINYINT(1) DEFAULT 0,
    alert_triggered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 17. community_build_showcases
$pdo->exec("
  CREATE TABLE IF NOT EXISTS community_build_showcases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    config_json JSON NOT NULL,
    image_gallery JSON NOT NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 18. community_build_interactions
$pdo->exec("
  CREATE TABLE IF NOT EXISTS community_build_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    showcase_id INT NOT NULL,
    client_id INT NOT NULL,
    interaction_type ENUM('upvote', 'favorite') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_vote (showcase_id, client_id, interaction_type),
    FOREIGN KEY (showcase_id) REFERENCES community_build_showcases(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 19. user_search_intent_logs
$pdo->exec("
  CREATE TABLE IF NOT EXISTS user_search_intent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT DEFAULT NULL,
    search_query VARCHAR(255) NOT NULL,
    results_returned INT NOT NULL,
    clicked_product_id INT DEFAULT NULL,
    search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL,
    FOREIGN KEY (clicked_product_id) REFERENCES products(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 20. abandoned_cart_communication_logs
$pdo->exec("
  CREATE TABLE IF NOT EXISTS abandoned_cart_communication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    cart_snapshot JSON NOT NULL,
    locked_price_total DECIMAL(10,2) NOT NULL,
    channel ENUM('email', 'whatsapp') NOT NULL,
    reminder_stage ENUM('4h', '24h', '44h') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted_to_order_id INT DEFAULT NULL,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (converted_to_order_id) REFERENCES orders(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 21. purchase_orders
$pdo->exec("
  CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    status ENUM('draft', 'ordered', 'partially_received', 'received', 'cancelled') DEFAULT 'draft',
    total_cost DECIMAL(10,2) NOT NULL,
    expected_delivery DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES vendor_suppliers(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 22. inventory_adjustments
$pdo->exec("
  CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_changed INT NOT NULL,
    reason ENUM('physical_count', 'damage', 'theft', 'return_to_supplier') NOT NULL,
    authorized_by_admin_id INT NOT NULL,
    notes TEXT,
    adjusted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 23. product_price_history
$pdo->exec("
  CREATE TABLE IF NOT EXISTS product_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(10,2) NOT NULL,
    new_price DECIMAL(10,2) NOT NULL,
    changed_by_admin_id INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 24. maintenance_ticket_replies
$pdo->exec("
  CREATE TABLE IF NOT EXISTS maintenance_ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    sender_type ENUM('client', 'technician') NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment_url VARCHAR(255) DEFAULT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 25. rma_shipping_labels
$pdo->exec("
  CREATE TABLE IF NOT EXISTS rma_shipping_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    carrier VARCHAR(50) NOT NULL DEFAULT 'AMANA',
    tracking_number VARCHAR(100) NOT NULL UNIQUE,
    label_pdf_url VARCHAR(255) NOT NULL,
    estimated_arrival DATE DEFAULT NULL,
    status ENUM('label_created', 'in_transit', 'delivered_to_lab', 'failed') DEFAULT 'label_created',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 26. warranty_extensions
$pdo->exec("
  CREATE TABLE IF NOT EXISTS warranty_extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    order_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    extension_months INT NOT NULL DEFAULT 24,
    amount_paid DECIMAL(10,2) NOT NULL,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 27. preventative_maintenance_schedules
$pdo->exec("
  CREATE TABLE IF NOT EXISTS preventative_maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    last_serviced_at DATE DEFAULT NULL,
    next_recommended_service DATE NOT NULL,
    alert_triggered TINYINT(1) DEFAULT 0,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 28. security_audit_logs
$pdo->exec("
  CREATE TABLE IF NOT EXISTS security_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    target_id INT NOT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 29. login_device_fingerprints
$pdo->exec("
  CREATE TABLE IF NOT EXISTS login_device_fingerprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    device_hash VARCHAR(64) NOT NULL,
    is_trusted TINYINT(1) DEFAULT 0,
    last_login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 30. two_factor_backup_codes
$pdo->exec("
  CREATE TABLE IF NOT EXISTS two_factor_backup_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Tables created successfully.\n";
