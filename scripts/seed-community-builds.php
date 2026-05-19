<?php
// scripts/seed-community-builds.php
require_once __DIR__ . '/../bootstrap.php';
$pdo = db();

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    $pdo->exec("TRUNCATE TABLE community_build_showcases");
    $pdo->exec("TRUNCATE TABLE community_build_interactions");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

    $showcases = [
        [
            'client_id' => 2,
            'title' => 'Ultimate 4K Gaming Machine',
            'description' => 'Built this beast specifically for Cyberpunk 2077 Phantom Liberty in 4K Path Tracing. High refresh rate, custom fan curve, quiet operation under full GPU loads.',
            'config' => [
                'cpu' => ['id' => 201, 'name' => 'AMD Ryzen 7 9800X3D', 'price' => 4999.9],
                'gpu' => ['id' => 101, 'name' => 'NVIDIA RTX 5090 Founders Edition', 'price' => 24999.9],
                'ram' => ['id' => 8, 'name' => 'G.Skill Trident Z5 RGB 64 GB', 'price' => 2999.9],
                'storage' => ['id' => 9, 'name' => 'Samsung 990 Pro NVMe SSD 2 TB', 'price' => 1699.9],
            ],
            'images' => ['images/products/rtx5090.png'],
            'views' => 1204
        ],
        [
            'client_id' => 4,
            'title' => 'Silent Studio Workstation',
            'description' => 'A compact editing rig for Adobe Premiere Pro and DaVinci Resolve. Built inside a sound-dampened chassis. Clean aesthetics and optimized memory latency.',
            'config' => [
                'cpu' => ['id' => 301, 'name' => 'Intel Core Ultra 9 285K', 'price' => 5999.9],
                'gpu' => ['id' => 102, 'name' => 'NVIDIA RTX 5080 Founders Edition', 'price' => 15999.9],
                'ram' => ['id' => 8, 'name' => 'G.Skill Trident Z5 RGB 64 GB', 'price' => 2999.9],
                'storage' => ['id' => 9, 'name' => 'Samsung 990 Pro NVMe SSD 2 TB', 'price' => 1699.9],
            ],
            'images' => ['images/products/core-ultra9-285k.png'],
            'views' => 432
        ],
        [
            'client_id' => 5,
            'title' => 'Esports budget build',
            'description' => 'Max performance per MAD. Perfect for Valorant and League of Legends at 240Hz+ without breaking the bank.',
            'config' => [
                'cpu' => ['id' => 204, 'name' => 'AMD Ryzen 5 9600X', 'price' => 2699.9],
                'gpu' => ['id' => 104, 'name' => 'NVIDIA RTX 5070', 'price' => 7999.9],
                'ram' => ['id' => 19, 'name' => 'Kingston Fury Beast DDR5 32 GB', 'price' => 1499.9],
            ],
            'images' => ['images/products/rtx5070.png'],
            'views' => 875
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO community_build_showcases (client_id, title, description, config_json, image_gallery, view_count, created_at)
        VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))
    ");

    $iStmt = $pdo->prepare("
        INSERT INTO community_build_interactions (showcase_id, client_id, interaction_type)
        VALUES (?, ?, ?)
    ");

    foreach ($showcases as $index => $s) {
        $configJson = json_encode($s['config']);
        $imagesJson = json_encode($s['images']);
        
        $stmt->execute([
            $s['client_id'],
            $s['title'],
            $s['description'],
            $configJson,
            $imagesJson,
            $s['views'],
            $index * 2
        ]);
        
        $showcaseId = $pdo->lastInsertId();
        
        // Seed some random interactions (upvotes and favorites)
        $clientIds = [1, 2, 4, 5, 6];
        foreach ($clientIds as $cid) {
            if (mt_rand(0, 1)) {
                $iStmt->execute([$showcaseId, $cid, 'upvote']);
            }
            if (mt_rand(0, 1)) {
                $iStmt->execute([$showcaseId, $cid, 'favorite']);
            }
        }
    }

    echo "Successfully seeded community builds showcases and interactions!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
