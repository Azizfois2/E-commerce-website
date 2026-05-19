<?php
// scripts/seed-data.php
require_once __DIR__ . '/../bootstrap.php';
$pdo = db();

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

    // 1. Loyalty Rewards
    echo "Seeding Loyalty Rewards...\n";
    $pdo->exec("TRUNCATE TABLE loyalty_rewards_catalog");
    $stmt = $pdo->prepare("INSERT INTO loyalty_rewards_catalog (title, description, points_required, reward_type, reward_value, stock_remaining) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Free Large Mousepad', 'Premium extended desk mat for gaming.', 1500, 'physical', 'mousepad', 50]);
    $stmt->execute(['10% Cart Discount', 'Get 10% off your entire next purchase.', 3000, 'discount', '10', 999]);
    $stmt->execute(['Free Standard Shipping', 'Waive the shipping fee on your next order.', 800, 'discount', 'free_shipping', 999]);
    $stmt->execute(['RGB LED Strip', 'High quality 2M ARGB strip for PC cases.', 1200, 'physical', 'led_strip', 25]);

    // 2. Custom Build Presets
    echo "Seeding Custom Build Presets...\n";
    $pdo->exec("TRUNCATE TABLE custom_build_presets");
    $stmt = $pdo->prepare("INSERT INTO custom_build_presets (name, description, target_category, base_price, products_json) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        'Base Build',
        'A solid entry level setup featuring an AMD or Intel processor, ideal for everyday computing tasks, light multitasking, and casual gaming.',
        'gaming', 12500, '[]'
    ]);
    
    $stmt->execute([
        'Advanced Build',
        'A versatile mid range build powered by high-performance components, designed for seamless multitasking, gaming at higher settings, and content creation.',
        'gaming', 18000, '[]'
    ]);
    
    $stmt->execute([
        'Power Build',
        'A high performance system optimized for demanding workloads like 4K gaming, video editing, and advanced simulations, offering top-tier speed and efficiency.',
        'workstation', 26000, '[]'
    ]);

    $stmt->execute([
        'Legacy Enthusiast',
        'A specialized build using used server-grade components and legacy X99 architecture. Recommended for enthusiasts comfortable with tinkering.',
        'workstation', 4200, '[]'
    ]);

    // 3. Hardware Specs
    echo "Seeding Hardware Power & Clearance Specs...\n";
    $pdo->exec("TRUNCATE TABLE parts_power_specifications");
    $pdo->exec("TRUNCATE TABLE chassis_clearance_specs");

    $cpuSpecs = [
        4 => 253, // i9-14900K (PL2)
        5 => 230, // 7950X
        6 => 105, // 7700X
        15 => 181, // 14600K
        16 => 120, // 7800X3D
        31 => 90,  // E5-2640 v4
        32 => 120, // E5-2680 v4
        201 => 120, // 9800X3D
        202 => 170, // 9950X
        203 => 65,  // 9700X
        204 => 65,  // 9600X
        301 => 250, // 285K
        302 => 250, // 265K
    ];

    $gpuSpecs = [
        // id => [watts, length_mm]
        1 => [450, 304], // 4090
        2 => [355, 287], // 7900 XTX
        3 => [285, 310], // 4070 Ti Super
        17 => [320, 310], // 4080 Super
        18 => [263, 267], // 7800 XT
        35 => [50, 170],  // RX 550
        36 => [150, 255], // RX 580
        101 => [600, 330], // 5090
        102 => [400, 310], // 5080
        103 => [300, 280], // 5070 Ti
        104 => [250, 280], // 5070
        105 => [160, 240], // 5060 Ti
    ];

    $coolerSpecs = [
        // id => [tdp_capacity, height_mm]
        11 => [220, 165], // NH-D15
        12 => [300, 60],  // Kraken (AIO block height)
        22 => [270, 168], // Dark Rock
        39 => [130, 155], // Snowman
    ];

    $stmtPower = $pdo->prepare("INSERT INTO parts_power_specifications (product_id, watts_tdp, peak_watts) VALUES (?, ?, ?)");
    $stmtClearance = $pdo->prepare("INSERT INTO chassis_clearance_specs (product_id, type, gpu_length_mm, cooler_height_mm, radiator_size) VALUES (?, ?, ?, ?, ?)");

    foreach ($cpuSpecs as $id => $tdp) {
        $stmtPower->execute([$id, $tdp, $tdp * 1.2]); // 20% headroom for peak
    }

    foreach ($gpuSpecs as $id => $data) {
        $stmtPower->execute([$id, $data[0], $data[0] * 1.2]);
        $stmtClearance->execute([$id, 'gpu', $data[1], null, null]);
    }

    foreach ($coolerSpecs as $id => $data) {
        $stmtClearance->execute([$id, 'cooler', null, $data[1], null]);
    }

    echo "Successfully seeded all databases!\n";

} catch (Exception $e) {
    echo "Error seeding database: " . $e->getMessage() . "\n";
}
