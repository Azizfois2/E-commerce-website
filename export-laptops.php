<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function adminExportLaptopsToDataJs(PDO $pdo): void
{
    $stmt = $pdo->query('
        SELECT l.id, l.name, l.brand, l.price, l.old_price, l.image, l.usage_category, l.portability_tier,
               l.screen_size, l.screen_quality, l.gpu_tier, l.battery_wh, l.weight_kg, l.specs, l.in_stock, l.stock_quantity,
               l.category, l.form_factor, l.dimensions, l.cooling_type, l.max_displays,
               a.npu_model, a.npu_tops, a.npu_vendor, a.is_copilot_plus, a.ai_tier
        FROM laptops l
        LEFT JOIN laptop_ai_specs a ON a.laptop_id = l.id
        ORDER BY l.id ASC
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
        $npuTops = $row['npu_tops'] !== null ? (float) $row['npu_tops'] : 0.0;

        $ramGb = 0;
        if (isset($specs['RAM']) && preg_match('/(\d+)\s*GB/i', (string) $specs['RAM'], $m)) {
            $ramGb = (int) $m[1];
        }
        $storageGb = 0;
        if (isset($specs['Storage']) && preg_match('/(\d+(?:\.\d+)?)\s*(TB|GB)/i', (string) $specs['Storage'], $m)) {
            $storageGb = (float) $m[1] * (strtoupper($m[2]) === 'TB' ? 1024 : 1);
        }

        $clampScore = static fn (float $value): float => round(max(1.0, min(10.0, $value)), 1);
        $gpuScore = match ((string) $row['gpu_tier']) {
            'dedicated' => 9.0,
            'integrated' => 6.5,
            default => 5.0,
        };
        $screenScore = match ((string) $row['screen_quality']) {
            'oled' => 9.5,
            'high_refresh' => 8.7,
            default => $screenSize > 0 ? 7.0 : 1.0,
        };
        // AI Processor score: piecewise-linear on NPU TOPS.
        // Anchors (reviewer-spec'd): 0 TOPS → 1.0 (legacy, no NPU), 16 TOPS → 3.5
        // (e.g. Ryzen 9 8945HS), 50 TOPS → 10.0 (Copilot+ class). This reserves 1/10
        // for zero-AI-hardware inventory and stops penalizing premium non-Copilot+ chips.
        $aiScore = (static function () use ($npuTops, $clampScore): float {
            if ($npuTops <= 0.0) return 1.0;
            if ($npuTops < 16.0) {
                // 0 → 1.0, 16 → 3.5
                return $clampScore(1.0 + (($npuTops / 16.0) * 2.5));
            }
            if ($npuTops < 50.0) {
                // 16 → 3.5, 50 → 10.0
                return $clampScore(3.5 + ((($npuTops - 16.0) / 34.0) * 6.5));
            }
            return 10.0;
        })();
        $memoryScore = $ramGb > 0 ? $clampScore(($ramGb / 32.0) * 8.0 + 2.0) : 5.0;
        $storageScore = $storageGb > 0 ? $clampScore(($storageGb / 1024.0) * 6.0 + 4.0) : 5.0;
        $performanceScore = $clampScore(($gpuScore * 0.45) + ($aiScore * 0.25) + ($memoryScore * 0.20) + ($storageScore * 0.10));
        $portabilityScore = $clampScore(
            ($weight > 0 ? max(0.0, 10.0 - (($weight - 1.0) * 4.0)) : 4.0)
            + ($battery > 0 ? min(2.0, $battery / 50.0) : 0.0)
            - ($screenSize >= 17 ? 1.0 : 0.0)
        );
        $factScore = ($performanceScore * 0.35) + ($portabilityScore * 0.20) + ($screenScore * 0.20) + ($aiScore * 0.15) + ($storageScore * 0.10);
        $valueScore = $clampScore(($factScore / max(1.0, $price / 10000.0)) * 1.25);

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
            'category' => (string) ($row['category'] ?? 'laptop'),
            'formFactor' => $row['form_factor'] ?? null,
            'dimensions' => $row['dimensions'] ?? null,
            'coolingType' => $row['cooling_type'] ?? null,
            'maxDisplays' => $row['max_displays'] !== null ? (int) $row['max_displays'] : null,
            'npuModel' => $row['npu_model'] ?? null,
            'npuTops' => $row['npu_tops'] !== null ? (float) $row['npu_tops'] : 0,
            'npuVendor' => $row['npu_vendor'] ?? 'None',
            'isCopilotPlus' => !empty($row['is_copilot_plus']),
            'aiTier' => $row['ai_tier'] ?? 'none',
            'scores' => [
                'performance' => $performanceScore,
                'portability' => $portabilityScore,
                'screen' => $screenScore,
                'ai' => $aiScore,
                'value' => $valueScore,
            ],
            'scoreBasis' => [
                'performance' => 'Blended 1-10: 45% GPU tier, 25% AI/NPU score, 20% RAM, 10% storage (from catalog specs).',
                'portability' => '1-10 from weight, battery Wh, and screen size (lighter + longer battery = higher).',
                'screen' => '1-10 by stored panel class: OLED 9.5, high refresh 8.7, standard 7.0.',
                'ai' => 'Scored 1-10 from NPU TOPS: 1.0 at 0 TOPS, 3.5 at 16 TOPS, 10 at 50+ TOPS.',
                'value' => 'Catalog hardware score divided by the current retail price (higher spec per dirham = higher).',
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
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    try {
        $pdo = db();
        if (function_exists('adminEnsureAdminSuiteTables')) {
            adminEnsureAdminSuiteTables($pdo);
        }
        adminExportLaptopsToDataJs($pdo);
        echo "Successfully exported laptops to assets/js/laptop_data.js\n";
    } catch (Throwable $e) {
        echo "Export failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
