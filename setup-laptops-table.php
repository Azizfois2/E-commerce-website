<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = db();

echo "Initializing laptops table...\n";

// Drop laptops table if exists during developer testing? No, use CREATE TABLE IF NOT EXISTS.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS laptops (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        brand VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        old_price DECIMAL(10,2) DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        usage_category ENUM('gaming', 'business', 'student', 'creative') NOT NULL,
        portability_tier ENUM('ultralight', 'standard', 'desktop_replacement') NOT NULL,
        screen_size DECIMAL(4,1) NOT NULL,
        screen_quality ENUM('oled', 'high_refresh', 'standard') NOT NULL,
        gpu_tier ENUM('integrated', 'dedicated') NOT NULL,
        battery_wh INT NOT NULL,
        weight_kg DECIMAL(3,2) NOT NULL,
        specs JSON DEFAULT NULL,
        in_stock TINYINT(1) NOT NULL DEFAULT 1,
        stock_quantity INT NOT NULL DEFAULT 10,
        reorder_level INT NOT NULL DEFAULT 2,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

echo "Table 'laptops' created or verified.\n";

// Seed sample laptops if empty
$count = (int) $pdo->query("SELECT COUNT(*) FROM laptops")->fetchColumn();
if ($count === 0) {
    echo "Seeding sample laptops...\n";
    $samples = [
        [
            'name' => 'ASUS ROG Strix SCAR 18',
            'brand' => 'ASUS',
            'price' => 35000.00,
            'old_price' => 37999.00,
            'image' => 'images/products/scar18.png',
            'usage_category' => 'gaming',
            'portability_tier' => 'desktop_replacement',
            'screen_size' => 18.0,
            'screen_quality' => 'high_refresh',
            'gpu_tier' => 'dedicated',
            'battery_wh' => 90,
            'weight_kg' => 3.10,
            'specs' => json_encode([
                'CPU' => 'Intel Core i9-14900HX',
                'RAM' => '32 GB DDR5 5600MHz',
                'Storage' => '2 TB PCIe Gen4 NVMe SSD',
                'GPU' => 'NVIDIA GeForce RTX 4090 16GB',
                'Display' => '18" QHD+ (2560x1600) 240Hz ROG Nebula Display',
                'Ports' => '1x Thunderbolt 4, 1x USB-C 3.2 Gen 2, 2x USB 3.2 Gen 2, 1x HDMI 2.1, 1x 2.5G LAN'
            ]),
            'in_stock' => 1,
            'stock_quantity' => 5,
        ],
        [
            'name' => 'Lenovo ThinkPad X1 Carbon Gen 12',
            'brand' => 'Lenovo',
            'price' => 19999.00,
            'old_price' => null,
            'image' => 'images/products/x1carbon.png',
            'usage_category' => 'business',
            'portability_tier' => 'ultralight',
            'screen_size' => 14.0,
            'screen_quality' => 'oled',
            'gpu_tier' => 'integrated',
            'battery_wh' => 57,
            'weight_kg' => 1.09,
            'specs' => json_encode([
                'CPU' => 'Intel Core Ultra 7 155U (with vPro)',
                'RAM' => '32 GB LPDDR5X Dual-Channel',
                'Storage' => '1 TB PCIe NVMe Gen4 Performance SSD',
                'GPU' => 'Intel Graphics',
                'Display' => '14" 2.8K (2880x1800) OLED 120Hz Anti-glare',
                'Ports' => '2x Thunderbolt 4, 2x USB-A 3.2 Gen 1, 1x HDMI 2.1, 1x Headphone/Mic combo'
            ]),
            'in_stock' => 1,
            'stock_quantity' => 12,
        ],
        [
            'name' => 'HP Pavilion Aero 13',
            'brand' => 'HP',
            'price' => 7499.00,
            'old_price' => 8499.00,
            'image' => 'images/products/pavilion13.png',
            'usage_category' => 'student',
            'portability_tier' => 'ultralight',
            'screen_size' => 13.3,
            'screen_quality' => 'standard',
            'gpu_tier' => 'integrated',
            'battery_wh' => 43,
            'weight_kg' => 0.99,
            'specs' => json_encode([
                'CPU' => 'AMD Ryzen 5 7535U (6 Cores / 12 Threads)',
                'RAM' => '16 GB LPDDR5 6400MHz',
                'Storage' => '512 GB PCIe NVMe M.2 SSD',
                'GPU' => 'AMD Radeon 660M Graphics',
                'Display' => '13.3" WUXGA (1920x1200) IPS 400 nits 100% sRGB',
                'Ports' => '1x USB-C 10Gbps, 2x USB-A 5Gbps, 1x HDMI 2.1, 1x Headphone/Mic'
            ]),
            'in_stock' => 1,
            'stock_quantity' => 15,
        ],
        [
            'name' => 'Apple MacBook Pro 16" (M3 Max)',
            'brand' => 'Apple',
            'price' => 39999.00,
            'old_price' => null,
            'image' => 'images/products/macbookpro16.png',
            'usage_category' => 'creative',
            'portability_tier' => 'standard',
            'screen_size' => 16.2,
            'screen_quality' => 'oled',
            'gpu_tier' => 'dedicated',
            'battery_wh' => 100,
            'weight_kg' => 2.16,
            'specs' => json_encode([
                'CPU' => 'Apple M3 Max Chip (16-core CPU)',
                'RAM' => '48 GB Unified Memory',
                'Storage' => '1 TB Superfast PCIe SSD',
                'GPU' => 'Apple M3 Max (40-core GPU)',
                'Display' => '16.2" Liquid Retina XDR (3456x2234) 120Hz ProMotion',
                'Ports' => '3x Thunderbolt 4 (USB-C), 1x HDMI, 1x SDXC Card Slot, 1x MagSafe 3'
            ]),
            'in_stock' => 1,
            'stock_quantity' => 3,
        ],
        [
            'name' => 'ASUS ROG Zephyrus G14',
            'brand' => 'ASUS',
            'price' => 21999.00,
            'old_price' => 23999.00,
            'image' => 'images/products/zephyrusg14.png',
            'usage_category' => 'gaming',
            'portability_tier' => 'ultralight',
            'screen_size' => 14.0,
            'screen_quality' => 'oled',
            'gpu_tier' => 'dedicated',
            'battery_wh' => 73,
            'weight_kg' => 1.50,
            'specs' => json_encode([
                'CPU' => 'AMD Ryzen 9 8945HS (8 Cores / 16 Threads)',
                'RAM' => '32 GB LPDDR5X Dual-Channel',
                'Storage' => '1 TB PCIe 4.0 NVMe M.2 SSD',
                'GPU' => 'NVIDIA GeForce RTX 4070 8GB GDDR6 (90W)',
                'Display' => '14" 3K (2880x1800) OLED 120Hz ROG Nebula Display',
                'Ports' => '1x USB4, 1x USB-C 3.2 Gen 2, 2x USB-A 3.2 Gen 2, 1x HDMI 2.1, 1x MicroSD Reader'
            ]),
            'in_stock' => 1,
            'stock_quantity' => 8,
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO laptops 
        (name, brand, price, old_price, image, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, specs, in_stock, stock_quantity)
        VALUES 
        (:name, :brand, :price, :old_price, :image, :usage_category, :portability_tier, :screen_size, :screen_quality, :gpu_tier, :battery_wh, :weight_kg, :specs, :in_stock, :stock_quantity)
    ");

    foreach ($samples as $sample) {
        $stmt->execute($sample);
    }
    echo "Sample laptops seeded successfully!\n";
} else {
    echo "Laptops table already seeded with $count records.\n";
}

echo "Database initialization complete!\n";
