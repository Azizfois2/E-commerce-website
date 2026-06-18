<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/admin-helpers.php';
require_once dirname(__DIR__) . '/export-laptops.php';

$csvPath = $argv[1] ?? dirname(__DIR__) . '/ai_laptop_inventory_2026.csv';
if (!is_file($csvPath)) {
    fwrite(STDERR, "CSV not found: {$csvPath}\n");
    exit(1);
}

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$handle = fopen($csvPath, 'r');
if (!$handle) {
    fwrite(STDERR, "Unable to open CSV: {$csvPath}\n");
    exit(1);
}

$headers = fgetcsv($handle, 0, ',', '"', '\\');
if (!$headers) {
    fwrite(STDERR, "CSV is empty: {$csvPath}\n");
    exit(1);
}

$headers = array_map(static fn($h) => strtolower(trim(str_replace([' ', '_'], '', (string) $h))), $headers);
$columnMap = [
    'name' => 'name',
    'brand' => 'brand',
    'price' => 'price',
    'oldprice' => 'old_price',
    'image' => 'image',
    'usagecategory' => 'usage_category',
    'portabilitytier' => 'portability_tier',
    'screensize' => 'screen_size',
    'screenquality' => 'screen_quality',
    'gputier' => 'gpu_tier',
    'batterywh' => 'battery_wh',
    'weightkg' => 'weight_kg',
    'specs' => 'specs',
    'stockquantity' => 'stock_quantity',
    'instock' => 'in_stock',
    'category' => 'category',
    'formfactor' => 'form_factor',
    'dimensions' => 'dimensions',
    'coolingtype' => 'cooling_type',
    'maxdisplays' => 'max_displays',
    'npumodel' => 'npu_model',
    'nputops' => 'npu_tops',
    'npuvendor' => 'npu_vendor',
    'iscopilotplus' => 'is_copilot_plus',
    'aitier' => 'ai_tier',
    'aimarketingbadge' => 'ai_marketing_badge',
];

$indexes = [];
foreach ($headers as $index => $header) {
    if (isset($columnMap[$header])) {
        $indexes[$columnMap[$header]] = $index;
    }
}

$required = ['name', 'brand', 'price', 'usage_category', 'portability_tier', 'screen_size', 'screen_quality', 'gpu_tier', 'battery_wh', 'weight_kg'];
$missing = array_values(array_filter($required, static fn($key) => !isset($indexes[$key])));
if ($missing !== []) {
    fwrite(STDERR, 'Missing required columns: ' . implode(', ', $missing) . "\n");
    exit(1);
}

$checkStmt = $pdo->prepare('SELECT id FROM laptops WHERE name = ? LIMIT 1');
$insertStmt = $pdo->prepare('
    INSERT INTO laptops
    (name, brand, price, old_price, image, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, specs, stock_quantity, in_stock, category, form_factor, dimensions, cooling_type, max_displays)
    VALUES
    (:name, :brand, :price, :old_price, :image, :usage_category, :portability_tier, :screen_size, :screen_quality, :gpu_tier, :battery_wh, :weight_kg, :specs, :stock_quantity, :in_stock, :category, :form_factor, :dimensions, :cooling_type, :max_displays)
');
$updateStmt = $pdo->prepare('
    UPDATE laptops
    SET name = :name, brand = :brand, price = :price, old_price = :old_price, image = :image,
        usage_category = :usage_category, portability_tier = :portability_tier,
        screen_size = :screen_size, screen_quality = :screen_quality, gpu_tier = :gpu_tier,
        battery_wh = :battery_wh, weight_kg = :weight_kg, specs = :specs,
        stock_quantity = :stock_quantity, in_stock = :in_stock, category = :category,
        form_factor = :form_factor, dimensions = :dimensions, cooling_type = :cooling_type,
        max_displays = :max_displays
    WHERE id = :id
');
$deleteAiStmt = $pdo->prepare('DELETE FROM laptop_ai_specs WHERE laptop_id = ?');
$insertAiStmt = $pdo->prepare('
    INSERT INTO laptop_ai_specs
    (laptop_id, npu_model, npu_tops, npu_vendor, is_copilot_plus, ai_tier, ai_marketing_badge,
     has_windows_studio_effects, has_live_captions, has_recall, has_paint_cocreator, has_copilot_key)
    VALUES
    (:laptop_id, :npu_model, :npu_tops, :npu_vendor, :is_copilot_plus, :ai_tier, :ai_marketing_badge,
     :has_windows_studio_effects, :has_live_captions, :has_recall, :has_paint_cocreator, :has_copilot_key)
');

$inserted = 0;
$updated = 0;
$rowNum = 1;

$pdo->beginTransaction();
try {
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        $rowNum++;
        $val = static function (string $name, ?string $default = null) use ($row, $indexes): ?string {
            return isset($indexes[$name], $row[$indexes[$name]]) ? trim((string) $row[$indexes[$name]]) : $default;
        };

        $name = $val('name', '');
        if ($name === '') {
            continue;
        }

        $specs = json_decode((string) $val('specs', '{}'), true);
        if (!is_array($specs)) {
            $specs = ['Features' => (string) $val('specs', '')];
        }

        $npuTops = (float) $val('npu_tops', '0');
        $isCopilot = in_array(strtolower((string) $val('is_copilot_plus', $npuTops >= 40 ? '1' : '0')), ['1', 'true', 'yes', 'y'], true) ? 1 : 0;

        $payload = [
            'name' => $name,
            'brand' => $val('brand', ''),
            'price' => (float) $val('price', '0'),
            'old_price' => $val('old_price', '') === '' ? null : (float) $val('old_price', '0'),
            'image' => $val('image', 'images/products/placeholder-laptop.svg'),
            'usage_category' => strtolower((string) $val('usage_category', 'business')),
            'portability_tier' => strtolower((string) $val('portability_tier', 'standard')),
            'screen_size' => (float) $val('screen_size', '0'),
            'screen_quality' => strtolower((string) $val('screen_quality', 'standard')),
            'gpu_tier' => strtolower((string) $val('gpu_tier', 'integrated')),
            'battery_wh' => (int) $val('battery_wh', '0'),
            'weight_kg' => (float) $val('weight_kg', '0'),
            'specs' => json_encode($specs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'stock_quantity' => (int) $val('stock_quantity', '0'),
            'in_stock' => ((int) $val('stock_quantity', '0') > 0 && $val('in_stock', '1') !== '0') ? 1 : 0,
            'category' => strtolower((string) $val('category', 'laptop')),
            'form_factor' => $val('form_factor', '') ?: null,
            'dimensions' => $val('dimensions', '') ?: null,
            'cooling_type' => $val('cooling_type', '') ?: null,
            'max_displays' => max(1, (int) $val('max_displays', '1')),
        ];

        $checkStmt->execute([$name]);
        $existingId = $checkStmt->fetchColumn();
        if ($existingId) {
            $updateStmt->execute($payload + ['id' => (int) $existingId]);
            $laptopId = (int) $existingId;
            $updated++;
        } else {
            $insertStmt->execute($payload);
            $laptopId = (int) $pdo->lastInsertId();
            $inserted++;
        }

        $deleteAiStmt->execute([$laptopId]);
        $deleteAiStmt->closeCursor();
        $insertAiStmt->execute([
            'laptop_id' => $laptopId,
            'npu_model' => $val('npu_model', '') ?: null,
            'npu_tops' => $npuTops,
            'npu_vendor' => $val('npu_vendor', 'None'),
            'is_copilot_plus' => $isCopilot,
            'ai_tier' => strtolower((string) $val('ai_tier', 'none')),
            'ai_marketing_badge' => $val('ai_marketing_badge', '') ?: null,
            'has_windows_studio_effects' => $isCopilot,
            'has_live_captions' => $isCopilot,
            'has_recall' => $isCopilot,
            'has_paint_cocreator' => $isCopilot,
            'has_copilot_key' => $isCopilot,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Import failed on row {$rowNum}: {$e->getMessage()}\n");
    exit(1);
} finally {
    fclose($handle);
}

adminExportLaptopsToDataJs($pdo);
echo "Imported AI inventory. Inserted: {$inserted}, Updated: {$updated}\n";
