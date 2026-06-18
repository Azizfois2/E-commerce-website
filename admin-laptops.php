<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

function adminRenderUsageTag($val) {
    $valLower = strtolower($val);
    $styles = [
        'gaming'   => 'background: rgba(124, 58, 237, 0.12); color: #c084fc; border: 1px solid rgba(124, 58, 237, 0.25);',
        'creative' => 'background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25);',
        'business' => 'background: rgba(59, 130, 246, 0.12); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25);',
        'student'  => 'background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25);'
    ];
    $style = $styles[$valLower] ?? 'background: rgba(100, 116, 139, 0.12); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.25);';
    return '<span style="font-size:0.75rem; font-weight:700; font-family:\'Space Mono\', monospace; text-transform:uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 2px 4px 2px 0; ' . $style . '">' . adminH(adminStatusLabel($val)) . '</span>';
}

function adminRenderPortabilityTag($val) {
    $valLower = strtolower($val);
    $styles = [
        'ultralight'          => 'background: rgba(6, 182, 212, 0.12); color: #22d3ee; border: 1px solid rgba(6, 182, 212, 0.25);',
        'standard'            => 'background: rgba(148, 163, 184, 0.12); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.25);',
        'desktop_replacement' => 'background: rgba(249, 115, 22, 0.12); color: #fdba74; border: 1px solid rgba(249, 115, 22, 0.25);',
        'heavy'               => 'background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25);'
    ];
    $style = $styles[$valLower] ?? 'background: rgba(100, 116, 139, 0.12); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.25);';
    $labels = [
        'ultralight' => 'Ultralight (<1.5kg)',
        'standard' => 'Standard (1.5kg-2.2kg)',
        'desktop_replacement' => 'Desktop Replacement',
        'heavy' => 'Heavy',
    ];
    $label = adminPhrase($labels[$valLower] ?? str_replace('_', ' ', ucfirst($val)));
    return '<span style="font-size:0.75rem; font-weight:700; font-family:\'Space Mono\', monospace; text-transform:uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 2px 4px 2px 0; ' . $style . '">' . adminH($label) . '</span>';
}

function adminRenderGpuTag($val) {
    $valLower = strtolower($val);
    $styles = [
        'dedicated'   => 'background: rgba(234, 179, 8, 0.12); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.25);',
        'integrated'  => 'background: rgba(100, 116, 139, 0.12); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.25);'
    ];
    $style = $styles[$valLower] ?? 'background: rgba(100, 116, 139, 0.12); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.25);';
    $label = adminPhrase(ucfirst($val) . ' GPU');
    return '<span style="font-size:0.75rem; font-weight:700; font-family:\'Space Mono\', monospace; text-transform:uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 2px 4px 2px 0; ' . $style . '">' . adminH($label) . '</span>';
}

// Handle CSV Import
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['csv_file'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Invalid session token.'));
    }

    if (empty($_FILES['csv_file']['name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Please select a valid CSV file.'));
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Could not open the uploaded file.'));
    }

    // Read headers
    $headers = fgetcsv($handle, 0, ',', '"', '\\');
    if (!$headers) {
        fclose($handle);
        adminRedirect('admin-laptops.php?error=' . urlencode('CSV file is empty or invalid.'));
    }

    // Normalize headers
    $headers = array_map(function($h) {
        return strtolower(trim(str_replace([' ', '_'], '', $h)));
    }, $headers);

    // Expected headers map to DB columns
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
        'aimarketingbadge' => 'ai_marketing_badge'
    ];

    // Determine column indexes
    $headerIndexes = [];
    foreach ($headers as $index => $header) {
        if (isset($columnMap[$header])) {
            $headerIndexes[$columnMap[$header]] = $index;
        }
    }

    // Check required columns
    $required = ['name', 'brand', 'price', 'usage_category', 'portability_tier', 'screen_size', 'screen_quality', 'gpu_tier', 'battery_wh', 'weight_kg'];
    $missing = [];
    foreach ($required as $req) {
        if (!isset($headerIndexes[$req])) {
            $missing[] = $req;
        }
    }

    if ($missing !== []) {
        fclose($handle);
        adminRedirect('admin-laptops.php?error=' . urlencode('Missing required columns: ' . implode(', ', $missing)));
    }

    // Prepare INSERT/UPDATE statement
    // We check if a laptop with the same name already exists; if so, we update, else we insert.
    $checkStmt = $pdo->prepare('SELECT id FROM laptops WHERE name = ? LIMIT 1');
    
    $insertStmt = $pdo->prepare('
        INSERT INTO laptops 
        (name, brand, price, old_price, image, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, specs, stock_quantity, in_stock, category, form_factor, dimensions, cooling_type, max_displays)
        VALUES 
        (:name, :brand, :price, :old_price, :image, :usage_category, :portability_tier, :screen_size, :screen_quality, :gpu_tier, :battery_wh, :weight_kg, :specs, :stock_quantity, :in_stock, :category, :form_factor, :dimensions, :cooling_type, :max_displays)
    ');

    $updateStmt = $pdo->prepare('
        UPDATE laptops
        SET name = :name, brand = :brand, price = :price, old_price = :old_price, image = :image, usage_category = :usage_category,
            portability_tier = :portability_tier, screen_size = :screen_size, screen_quality = :screen_quality,
            gpu_tier = :gpu_tier, battery_wh = :battery_wh, weight_kg = :weight_kg, specs = :specs,
            stock_quantity = :stock_quantity, in_stock = :in_stock,
            category = :category, form_factor = :form_factor, dimensions = :dimensions,
            cooling_type = :cooling_type, max_displays = :max_displays
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

    try {
        $pdo->beginTransaction();
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNum++;
            
            // Extract values safely
            $val = function($colName, $default = null) use ($row, $headerIndexes) {
                if (isset($headerIndexes[$colName]) && isset($row[$headerIndexes[$colName]])) {
                    return trim($row[$headerIndexes[$colName]]);
                }
                return $default;
            };

            $name = $val('name');
            if ($name === '') {
                continue; // skip empty rows
            }

            $brand = $val('brand');
            $price = (float) $val('price');
            $oldPriceVal = $val('old_price');
            $oldPrice = ($oldPriceVal === '' || $oldPriceVal === null) ? null : (float) $oldPriceVal;
            $image = $val('image', 'images/products/placeholder-laptop.svg');
            if ($image === '') {
                $image = 'images/products/placeholder-laptop.svg';
            }
            $usage = strtolower($val('usage_category'));
            $portability = strtolower($val('portability_tier'));
            $screenSize = (float) $val('screen_size');
            $screenQual = strtolower($val('screen_quality'));
            $gpu = strtolower($val('gpu_tier'));
            $battery = (int) $val('battery_wh');
            $weight = (float) $val('weight_kg');
            
            // specs parsing: if valid JSON, use it, else structure it as text specs
            $specsStr = $val('specs', '{}');
            $specs = json_decode($specsStr, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $specs = ["Features" => $specsStr];
            }
            $specsJson = json_encode($specs, JSON_UNESCAPED_SLASHES);

            $stockQty = (int) $val('stock_quantity', '10');
            $inStockVal = $val('in_stock', '1');
            $inStock = ($inStockVal === '0' || $stockQty <= 0) ? 0 : 1;
            $category = strtolower($val('category', 'laptop'));
            $formFactor = $val('form_factor', null);
            $dimensions = $val('dimensions', null);
            $coolingType = $val('cooling_type', null);
            $maxDisplays = max(1, (int) $val('max_displays', '1'));

            $npuModel = $val('npu_model', null);
            $npuTopsRaw = $val('npu_tops', '');
            $npuTops = $npuTopsRaw === '' ? 0.0 : (float) $npuTopsRaw;
            $npuVendor = $val('npu_vendor', 'None');
            $isCopilotPlusVal = strtolower($val('is_copilot_plus', $npuTops >= 40 ? '1' : '0'));
            $isCopilotPlus = in_array($isCopilotPlusVal, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
            $aiTier = strtolower($val('ai_tier', $npuTops >= 80 ? 'workstation' : ($npuTops >= 40 ? 'copilot' : ($npuTops >= 10 ? 'basic' : 'none'))));
            $aiMarketingBadge = $val('ai_marketing_badge', $isCopilotPlus ? 'Copilot+' : null);

            // Validate Enum values
            if (!in_array($usage, ['gaming', 'business', 'student', 'creative'], true)) {
                throw new Exception("Row {$rowNum}: Invalid usage_category '{$usage}'. Must be gaming, business, student, or creative.");
            }
            if (!in_array($portability, ['ultralight', 'standard', 'desktop_replacement'], true)) {
                throw new Exception("Row {$rowNum}: Invalid portability_tier '{$portability}'. Must be ultralight, standard, or desktop_replacement.");
            }
            if (!in_array($screenQual, ['oled', 'high_refresh', 'standard'], true)) {
                throw new Exception("Row {$rowNum}: Invalid screen_quality '{$screenQual}'. Must be oled, high_refresh, or standard.");
            }
            if (!in_array($gpu, ['integrated', 'dedicated'], true)) {
                throw new Exception("Row {$rowNum}: Invalid gpu_tier '{$gpu}'. Must be integrated or dedicated.");
            }
            if (!in_array($category, ['laptop', 'mini_pc', 'workstation'], true)) {
                throw new Exception("Row {$rowNum}: Invalid category '{$category}'. Must be laptop, mini_pc, or workstation.");
            }
            if (!in_array($npuVendor, ['Intel', 'AMD', 'Qualcomm', 'Apple', 'None'], true)) {
                throw new Exception("Row {$rowNum}: Invalid npu_vendor '{$npuVendor}'. Must be Intel, AMD, Qualcomm, Apple, or None.");
            }
            if (!in_array($aiTier, ['none', 'basic', 'copilot', 'workstation'], true)) {
                throw new Exception("Row {$rowNum}: Invalid ai_tier '{$aiTier}'. Must be none, basic, copilot, or workstation.");
            }

            // Check if existing laptop
            $checkStmt->execute([$name]);
            $existingId = $checkStmt->fetchColumn();

            $payload = [
                'name' => $name,
                'brand' => $brand,
                'price' => $price,
                'old_price' => $oldPrice,
                'image' => $image,
                'usage_category' => $usage,
                'portability_tier' => $portability,
                'screen_size' => $screenSize,
                'screen_quality' => $screenQual,
                'gpu_tier' => $gpu,
                'battery_wh' => $battery,
                'weight_kg' => $weight,
                'specs' => $specsJson,
                'stock_quantity' => $stockQty,
                'in_stock' => $inStock,
                'category' => $category,
                'form_factor' => $formFactor !== '' ? $formFactor : null,
                'dimensions' => $dimensions !== '' ? $dimensions : null,
                'cooling_type' => $coolingType !== '' ? $coolingType : null,
                'max_displays' => $maxDisplays
            ];

            if ($existingId) {
                $updateStmt->execute($payload + ['id' => $existingId]);
                $laptopId = (int) $existingId;
                $updated++;
            } else {
                $insertStmt->execute($payload);
                $laptopId = (int) $pdo->lastInsertId();
                $inserted++;
            }

            if (isset($headerIndexes['npu_model']) || isset($headerIndexes['npu_tops']) || isset($headerIndexes['ai_tier'])) {
                $deleteAiStmt->execute([$laptopId]);
                $deleteAiStmt->closeCursor();
                $insertAiStmt->execute([
                    'laptop_id' => $laptopId,
                    'npu_model' => $npuModel !== '' ? $npuModel : null,
                    'npu_tops' => $npuTops,
                    'npu_vendor' => $npuVendor,
                    'is_copilot_plus' => $isCopilotPlus,
                    'ai_tier' => $aiTier,
                    'ai_marketing_badge' => $aiMarketingBadge !== '' ? $aiMarketingBadge : null,
                    'has_windows_studio_effects' => $isCopilotPlus,
                    'has_live_captions' => $isCopilotPlus,
                    'has_recall' => $isCopilotPlus,
                    'has_paint_cocreator' => $isCopilotPlus,
                    'has_copilot_key' => $isCopilotPlus,
                ]);
            }
        }
        $pdo->commit();
        fclose($handle);

        // Re-export static Javascript file
        require_once 'export-laptops.php';
        adminExportLaptopsToDataJs($pdo);

        adminRedirect("admin-laptops.php?saved=1&msg=" . urlencode("Successfully imported CSV. Inserted: {$inserted}, Updated: {$updated} laptops."));
    } catch (Throwable $e) {
        $pdo->rollBack();
        fclose($handle);
        adminRedirect('admin-laptops.php?error=' . urlencode($e->getMessage()));
    }
}

// Listing Fetch logic
$search = trim((string) ($_GET['search'] ?? ''));
$usageCategory = trim((string) ($_GET['usage_category'] ?? ''));
$portabilityTier = trim((string) ($_GET['portability_tier'] ?? ''));
$stockStatus = trim((string) ($_GET['stock_status'] ?? ''));

$params = [
    'search_empty' => $search === '' ? 1 : 0,
    'search_name' => '%' . $search . '%',
    'search_brand' => '%' . $search . '%',
    'usage_empty' => $usageCategory === '' ? 1 : 0,
    'usage_filter' => $usageCategory,
    'portability_empty' => $portabilityTier === '' ? 1 : 0,
    'portability_filter' => $portabilityTier,
];

$statusWhere = '';
if ($stockStatus === 'in_stock') {
    $statusWhere = ' AND in_stock = 1 AND stock_quantity > 0';
} elseif ($stockStatus === 'low_stock') {
    $statusWhere = ' AND stock_quantity <= reorder_level';
} elseif ($stockStatus === 'out_of_stock') {
    $statusWhere = ' AND (in_stock = 0 OR stock_quantity <= 0)';
}

$laptops = adminFetchAll($pdo, '
    SELECT id, name, brand, price, old_price, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, stock_quantity, reorder_level, in_stock, created_at
    FROM laptops
    WHERE (:search_empty = 1 OR name LIKE :search_name OR brand LIKE :search_brand)
      AND (:usage_empty = 1 OR usage_category = :usage_filter)
      AND (:portability_empty = 1 OR portability_tier = :portability_filter)
      ' . $statusWhere . '
    ORDER BY created_at DESC, id DESC
', $params);

adminPageStart('Laptops Admin', 'laptops');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Outcome Curator Dashboard')) ?></span>
        <h1><?= adminH(adminPhrase('Laptops Ecosystem')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Manage laptop catalog records, outcome mapping, specs, and bulk-load inventory.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><?= adminH(adminPhrase('Dashboard')) ?></a>
        <a class="button button-light" href="admin-laptop-csv-template.php"><i class="fas fa-file-csv"></i> <?= adminH(adminPhrase('Download Template')) ?></a>
        <a class="button button-primary" href="admin-laptop-form.php"><?= adminH(adminPhrase('Add Laptop')) ?></a>
    </div>
</section>

<?php if (isset($_GET['saved'])): ?>
    <div class="admin-alert success"><?= isset($_GET['msg']) ? adminH($_GET['msg']) : adminH(adminPhrase('Laptop saved successfully.')) ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Laptop deleted successfully.')) ?></div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<!-- CSV BULK IMPORT CARD -->
<section class="table-card csv-import-card" style="margin-bottom: 24px; border: 1px dashed rgba(0, 245, 212, 0.3); background: rgba(0, 245, 212, 0.02); transition: all 0.3s ease;">
    <div class="card-head" style="border-bottom: none; margin-bottom: 15px;">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--text); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-import" style="color: var(--cyan);"></i> <?= adminH(adminPhrase('Bulk Import Laptops (CSV)')) ?>
            </h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 0.82rem;"><?= adminH(adminPhrase('Update or populate the entire laptop catalog using a standard CSV format.')) ?></p>
        </div>
    </div>
    
    <div style="padding: 20px; pt: 0; background: transparent;">
        <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            <?= csrfField() ?>
            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--muted); letter-spacing: 0.05em;"><?= adminH(adminPhrase('Upload Supplier Inventory Spreadsheet (.csv)')) ?></span>
            
            <div style="display: flex; gap: 16px; align-items: stretch; width: 100%; flex-wrap: wrap;">
                <!-- Premium Custom File Upload Dropzone -->
                <label class="custom-file-upload-zone" style="flex: 1; min-width: 300px;">
                    <i class="fas fa-file-csv" style="font-size: 2.5rem; color: var(--cyan); margin-bottom: 10px;"></i>
                    <span style="font-family: 'Orbitron', sans-serif; font-weight: 800; color: var(--white); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;" id="csvFileNameDisplay"><?= adminH(adminPhrase('Select CSV Inventory File')) ?></span>
                    <span style="font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px;" id="csvSubtextDisplay"><?= adminH(adminPhrase('Drag & drop or click to browse')) ?></span>
                    <input type="file" name="csv_file" accept=".csv" required style="display: none;" onchange="
                        const file = this.files[0];
                        const t = (source) => (window.__marocPcPhraseMap && window.__marocPcPhraseMap[source]) || source;
                        if (file) {
                            document.getElementById('csvFileNameDisplay').textContent = file.name;
                            document.getElementById('csvFileNameDisplay').style.color = 'var(--cyan)';
                            document.getElementById('csvSubtextDisplay').textContent = t('File loaded successfully');
                            document.getElementById('csvSubtextDisplay').style.color = '#00f5d4';
                        } else {
                            document.getElementById('csvFileNameDisplay').textContent = t('Select CSV Inventory File');
                            document.getElementById('csvFileNameDisplay').style.color = 'var(--white)';
                            document.getElementById('csvSubtextDisplay').textContent = t('Drag & drop or click to browse');
                            document.getElementById('csvSubtextDisplay').style.color = 'var(--muted)';
                        }
                    ">
                </label>
                
                <button class="button button-primary" type="submit" style="min-height: auto; height: auto; padding: 0 32px; font-family: 'Orbitron', sans-serif; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 10px; border-radius: 12px; font-size: 0.9rem; flex-shrink: 0; min-width: 180px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 1.1rem;"></i> <?= adminH(adminPhrase('Import & Sync')) ?>
                </button>
            </div>
        </form>
        <div style="margin-top: 14px; font-size: 0.85rem; color: var(--muted); line-height: 1.5;">
            <p><strong><?= adminH(adminPhrase('CSV Formatting Guideline:')) ?></strong></p>
            <ul style="margin: 6px 0 0 16px; padding: 0; list-style-type: square;">
                <li><strong><?= adminH(adminPhrase('Required columns:')) ?></strong> <code style="color: var(--cyan);">name, brand, price, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg</code></li>
                <li><strong><?= adminH(adminPhrase('Valid usage enums:')) ?></strong> <code>gaming | business | student | creative</code></li>
                <li><strong><?= adminH(adminPhrase('Valid portability enums:')) ?></strong> <code>ultralight | standard | desktop_replacement</code></li>
                <li><strong><?= adminH(adminPhrase('Valid screen enums:')) ?></strong> <code>oled | high_refresh | standard</code></li>
                <li><strong><?= adminH(adminPhrase('Valid GPU enums:')) ?></strong> <code>integrated | dedicated</code></li>
                <li><strong><?= adminH(adminPhrase('Optional AI columns:')) ?></strong> <code>category, form_factor, dimensions, cooling_type, max_displays, npu_model, npu_tops, npu_vendor, is_copilot_plus, ai_tier, ai_marketing_badge</code></li>
                <li><strong><?= adminH(adminPhrase('Valid product categories:')) ?></strong> <code>laptop | mini_pc | workstation</code></li>
                <li><?= adminH(adminPhrase('Laptops matching existing names will automatically merge and update inventory.')) ?></li>
            </ul>
        </div>
    </div>
</section>

<!-- FILTERING & TABLE LISTING -->
<section class="table-card">
    <div class="card-head">
        <h2><?= adminH(adminPhrase('Active Inventory')) ?></h2>
    </div>
    <form class="filter-bar" method="get">
        <label>
            <?= adminH(adminPhrase('Search')) ?>
            <input type="text" name="search" value="<?= adminH($search) ?>" placeholder="<?= adminH(adminPhrase('Name or brand')) ?>">
        </label>
        <label>
            <?= adminH(adminPhrase('Usage')) ?>
            <select name="usage_category">
                <option value=""><?= adminH(adminPhrase('All usages')) ?></option>
                <option value="gaming" <?= $usageCategory === 'gaming' ? 'selected' : '' ?>><?= adminH(adminPhrase('Gaming')) ?></option>
                <option value="business" <?= $usageCategory === 'business' ? 'selected' : '' ?>><?= adminH(adminPhrase('Business')) ?></option>
                <option value="student" <?= $usageCategory === 'student' ? 'selected' : '' ?>><?= adminH(adminPhrase('Student')) ?></option>
                <option value="creative" <?= $usageCategory === 'creative' ? 'selected' : '' ?>><?= adminH(adminPhrase('Creative')) ?></option>
            </select>
        </label>
        <label>
            <?= adminH(adminPhrase('Portability')) ?>
            <select name="portability_tier">
                <option value=""><?= adminH(adminPhrase('All portability')) ?></option>
                <option value="ultralight" <?= $portabilityTier === 'ultralight' ? 'selected' : '' ?>><?= adminH(adminPhrase('Ultralight (<1.5kg)')) ?></option>
                <option value="standard" <?= $portabilityTier === 'standard' ? 'selected' : '' ?>><?= adminH(adminPhrase('Standard (1.5kg-2.2kg)')) ?></option>
                <option value="desktop_replacement" <?= $portabilityTier === 'desktop_replacement' ? 'selected' : '' ?>><?= adminH(adminPhrase('Desktop Replacement')) ?></option>
            </select>
        </label>
        <label>
            <?= adminH(adminPhrase('Stock Status')) ?>
            <select name="stock_status">
                <option value=""><?= adminH(adminPhrase('All statuses')) ?></option>
                <option value="in_stock" <?= $stockStatus === 'in_stock' ? 'selected' : '' ?>><?= adminH(adminPhrase('In stock')) ?></option>
                <option value="low_stock" <?= $stockStatus === 'low_stock' ? 'selected' : '' ?>><?= adminH(adminPhrase('Low stock')) ?></option>
                <option value="out_of_stock" <?= $stockStatus === 'out_of_stock' ? 'selected' : '' ?>><?= adminH(adminPhrase('Out of stock')) ?></option>
            </select>
        </label>
        <button class="button button-primary" type="submit"><?= adminH(adminPhrase('Filter')) ?></button>
    </form>

    <table>
        <thead>
            <tr>
                <th><?= adminH(adminPhrase('Laptop Details')) ?></th>
                <th><?= adminH(adminPhrase('Outcome Traits')) ?></th>
                <th><?= adminH(adminPhrase('Price')) ?></th>
                <th><?= adminH(adminPhrase('Stock')) ?></th>
                <th><?= adminH(adminPhrase('Added')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($laptops === []): ?>
                <tr><td colspan="6"><?= adminH(adminPhrase('No laptops match the current filters.')) ?></td></tr>
            <?php endif; ?>
            <?php foreach ($laptops as $laptop): ?>
                <tr>
                    <td>
                        <strong><?= adminH($laptop['name']) ?></strong>
                        <small><?= adminH($laptop['brand']) ?> &bull; <?= adminH($laptop['screen_size']) ?>" <?= adminH(strtoupper($laptop['screen_quality'])) ?></small>
                    </td>
                    <td>
                        <?= adminRenderUsageTag($laptop['usage_category']) ?>
                        <?= adminRenderPortabilityTag($laptop['portability_tier']) ?>
                        <?= adminRenderGpuTag($laptop['gpu_tier']) ?>
                    </td>
                    <td>
                        <?= adminMoney((float) $laptop['price']) ?>
                        <?php if (!empty($laptop['old_price'])): ?>
                            <small class="inline-note" style="text-decoration: line-through; display: block; color: var(--muted); font-size: 0.75rem;"><?= adminH(adminPhrase('Old {amount}', ['amount' => adminMoney((float) $laptop['old_price'])])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= adminStockBadgeClass((int) $laptop['stock_quantity'], (int) $laptop['reorder_level']) ?>">
                            <?= (int) $laptop['stock_quantity'] ?>
                        </span>
                    </td>
                    <td><?= adminH(substr((string) $laptop['created_at'], 0, 10)) ?></td>
                    <td class="table-actions">
                        <a class="button button-light button-small" href="admin-laptop-form.php?id=<?= (int) $laptop['id'] ?>"><?= adminH(adminPhrase('Edit')) ?></a>
                        <form method="post" action="admin-laptop-delete.php" onsubmit="return confirm('<?= adminH(adminPhrase('Delete this laptop from the catalog?')) ?>');">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $laptop['id'] ?>">
                            <button class="button button-danger button-small" type="submit"><?= adminH(adminPhrase('Delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php adminPageEnd(); ?>
