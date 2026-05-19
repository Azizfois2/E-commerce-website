<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function adminExportLaptopsToDataJs(PDO $pdo): void
{
    $stmt = $pdo->query('
        SELECT id, name, brand, price, old_price, image, usage_category, portability_tier,
               screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, specs, in_stock, stock_quantity
        FROM laptops
        ORDER BY id ASC
    ');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $laptops = [];
    foreach ($rows as $row) {
        $specs = [];
        if (!empty($row['specs'])) {
            $decodedSpecs = json_decode((string) $row['specs'], true);
            $specs = is_array($decodedSpecs) ? $decodedSpecs : [];
        }

        // Parse numeric fields
        $price = (float) $row['price'];
        $oldPrice = $row['old_price'] !== null ? (float) $row['old_price'] : null;
        $weight = (float) $row['weight_kg'];
        $screenSize = (float) $row['screen_size'];
        $battery = (int) $row['battery_wh'];

        // Compute Portability Score (bounded between 1.0 and 10.0)
        // Less weight and smaller screen = more portable.
        $portabilityScore = round(10.0 - (($weight - 0.9) * 3.0 + ($screenSize - 13.0) * 0.8), 1);
        $portabilityScore = max(1.0, min(10.0, $portabilityScore));

        // Compute Performance Score (bounded between 1.0 and 10.0)
        $perfScore = 5.0;
        if ($row['usage_category'] === 'gaming') {
            $perfScore = ($row['gpu_tier'] === 'dedicated') ? 9.6 : 7.0;
        } elseif ($row['usage_category'] === 'creative') {
            $perfScore = ($row['gpu_tier'] === 'dedicated') ? 9.2 : 7.5;
        } elseif ($row['usage_category'] === 'business') {
            $perfScore = ($row['gpu_tier'] === 'dedicated') ? 8.5 : 7.8;
        } elseif ($row['usage_category'] === 'student') {
            $perfScore = ($row['gpu_tier'] === 'dedicated') ? 8.0 : 6.5;
        }

        // Compute Screen Clarity Score
        $screenScore = 7.0;
        if ($row['screen_quality'] === 'oled') {
            $screenScore = 9.8;
        } elseif ($row['screen_quality'] === 'high_refresh') {
            $screenScore = 9.0;
        }

        // Compute Value Score (ratio of specs vs price)
        // Lower price and higher performance increases value
        $baseValue = 10.0 - ($price / 6000.0);
        $valueScore = round($baseValue + ($perfScore * 0.15) + ($screenScore * 0.1), 1);
        $valueScore = max(4.0, min(9.9, $valueScore));

        $laptops[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'brand' => (string) $row['brand'],
            'price' => $price,
            'oldPrice' => $oldPrice,
            'image' => (string) ($row['image'] ?? 'Images/products/placeholder-laptop.svg'),
            'usageCategory' => (string) $row['usage_category'],
            'portabilityTier' => (string) $row['portability_tier'],
            'screenSize' => $screenSize,
            'screenQuality' => (string) $row['screen_quality'],
            'gpuTier' => (string) $row['gpu_tier'],
            'batteryWh' => $battery,
            'weightKg' => $weight,
            'specs' => $specs,
            'inStock' => !empty($row['in_stock']) && (int) $row['stock_quantity'] > 0,
            'stockQuantity' => (int) $row['stock_quantity'],
            'scores' => [
                'portability' => $portabilityScore,
                'performance' => $perfScore,
                'screen' => $screenScore,
                'value' => $valueScore
            ]
        ];
    }

    $json = json_encode($laptops, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Unable to encode laptops for laptop_data.js.');
    }

    $content = "/**\n";
    $content .= " * laptop_data.js - Outcome-oriented curated laptop database.\n";
    $content .= " * Generated dynamically from database by export-laptops.php.\n";
    $content .= " */\n";
    $content .= "const laptops = " . $json . ";\n";

    $jsPath = __DIR__ . '/assets/js/laptop_data.js';
    if (file_put_contents($jsPath, $content, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write assets/js/laptop_data.js.');
    }
}

// Run if accessed directly or via CLI
if (php_sapi_name() === 'cli' || realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    try {
        $pdo = db();
        adminExportLaptopsToDataJs($pdo);
        echo "Successfully exported laptops to assets/js/laptop_data.js\n";
    } catch (Throwable $e) {
        echo "Export failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
