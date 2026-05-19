<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();

function adminRenderUsageTag($val) {
    $valLower = strtolower($val);
    $styles = [
        'gaming'   => 'background: rgba(124, 58, 237, 0.12); color: #c084fc; border: 1px solid rgba(124, 58, 237, 0.25);',
        'creative' => 'background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25);',
        'business' => 'background: rgba(59, 130, 246, 0.12); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25);',
        'student'  => 'background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25);'
    ];
    $style = $styles[$valLower] ?? 'background: rgba(100, 116, 139, 0.12); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.25);';
    return '<span style="font-size:0.75rem; font-weight:700; font-family:\'Space Mono\', monospace; text-transform:uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 2px 4px 2px 0; ' . $style . '">' . adminH(ucfirst($val)) . '</span>';
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
    $label = str_replace('_', ' ', ucfirst($val));
    return '<span style="font-size:0.75rem; font-weight:700; font-family:\'Space Mono\', monospace; text-transform:uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; display: inline-block; margin: 2px 4px 2px 0; ' . $style . '">' . adminH($label) . '</span>';
}

function adminRenderGpuTag($val) {
    $valLower = strtolower($val);
    $styles = [
        'dedicated'   => 'background: rgba(234, 179, 8, 0.12); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.25);',
        'integrated'  => 'background: rgba(100, 116, 139, 0.12); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.25);'
    ];
    $style = $styles[$valLower] ?? 'background: rgba(100, 116, 139, 0.12); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.25);';
    $label = ucfirst($val) . ' GPU';
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
    $headers = fgetcsv($handle);
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
        'instock' => 'in_stock'
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
        (name, brand, price, old_price, image, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, specs, stock_quantity, in_stock)
        VALUES 
        (:name, :brand, :price, :old_price, :image, :usage_category, :portability_tier, :screen_size, :screen_quality, :gpu_tier, :battery_wh, :weight_kg, :specs, :stock_quantity, :in_stock)
    ');

    $updateStmt = $pdo->prepare('
        UPDATE laptops
        SET name = :name, brand = :brand, price = :price, old_price = :old_price, image = :image, usage_category = :usage_category,
            portability_tier = :portability_tier, screen_size = :screen_size, screen_quality = :screen_quality,
            gpu_tier = :gpu_tier, battery_wh = :battery_wh, weight_kg = :weight_kg, specs = :specs,
            stock_quantity = :stock_quantity, in_stock = :in_stock
        WHERE id = :id
    ');

    $inserted = 0;
    $updated = 0;
    $rowNum = 1;

    try {
        $pdo->beginTransaction();
        while (($row = fgetcsv($handle)) !== false) {
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
                'in_stock' => $inStock
            ];

            if ($existingId) {
                $updateStmt->execute($payload + ['id' => $existingId]);
                $updated++;
            } else {
                $insertStmt->execute($payload);
                $inserted++;
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
        <span class="eyebrow">Outcome Curator Dashboard</span>
        <h1>Laptops Ecosystem</h1>
        <p class="section-copy">Manage laptop catalog records, outcome mapping, specs, and bulk-load inventory.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-laptop-csv-template.php"><i class="fas fa-file-csv"></i> Download Template</a>
        <a class="button button-primary" href="admin-laptop-form.php">Add Laptop</a>
    </div>
</section>

<?php if (isset($_GET['saved'])): ?>
    <div class="admin-alert success"><?= isset($_GET['msg']) ? adminH($_GET['msg']) : 'Laptop saved successfully.' ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="admin-alert success">Laptop deleted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<!-- CSV BULK IMPORT CARD -->
<section class="table-card csv-import-card" style="margin-bottom: 24px; border: 1px dashed rgba(0, 245, 212, 0.3); background: rgba(0, 245, 212, 0.02); transition: all 0.3s ease;">
    <div class="card-head" style="border-bottom: none; margin-bottom: 15px;">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--text); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-import" style="color: var(--cyan);"></i> Bulk Import Laptops (CSV)
            </h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 0.82rem;">Update or populate the entire laptop catalog using a standard CSV format.</p>
        </div>
    </div>
    
    <div style="padding: 20px; pt: 0; background: transparent;">
        <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            <?= csrfField() ?>
            <span style="display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--muted); letter-spacing: 0.05em;">Upload Supplier Inventory Spreadsheet (.csv)</span>
            
            <div style="display: flex; gap: 16px; align-items: stretch; width: 100%; flex-wrap: wrap;">
                <!-- Premium Custom File Upload Dropzone -->
                <label class="custom-file-upload-zone" style="flex: 1; min-width: 300px;">
                    <i class="fas fa-file-csv" style="font-size: 2.5rem; color: var(--cyan); margin-bottom: 10px;"></i>
                    <span style="font-family: 'Orbitron', sans-serif; font-weight: 800; color: var(--white); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;" id="csvFileNameDisplay">Select CSV Inventory File</span>
                    <span style="font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px;" id="csvSubtextDisplay">Drag & drop or click to browse</span>
                    <input type="file" name="csv_file" accept=".csv" required style="display: none;" onchange="
                        const file = this.files[0];
                        if (file) {
                            document.getElementById('csvFileNameDisplay').textContent = file.name;
                            document.getElementById('csvFileNameDisplay').style.color = 'var(--cyan)';
                            document.getElementById('csvSubtextDisplay').textContent = 'File loaded successfully';
                            document.getElementById('csvSubtextDisplay').style.color = '#00f5d4';
                        } else {
                            document.getElementById('csvFileNameDisplay').textContent = 'Select CSV Inventory File';
                            document.getElementById('csvFileNameDisplay').style.color = 'var(--white)';
                            document.getElementById('csvSubtextDisplay').textContent = 'Drag & drop or click to browse';
                            document.getElementById('csvSubtextDisplay').style.color = 'var(--muted)';
                        }
                    ">
                </label>
                
                <button class="button button-primary" type="submit" style="min-height: auto; height: auto; padding: 0 32px; font-family: 'Orbitron', sans-serif; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 10px; border-radius: 12px; font-size: 0.9rem; flex-shrink: 0; min-width: 180px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 1.1rem;"></i> Import & Sync
                </button>
            </div>
        </form>
        <div style="margin-top: 14px; font-size: 0.85rem; color: var(--muted); line-height: 1.5;">
            <p><strong>CSV Formatting Guideline:</strong></p>
            <ul style="margin: 6px 0 0 16px; padding: 0; list-style-type: square;">
                <li><strong>Required columns:</strong> <code style="color: var(--cyan);">name, brand, price, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg</code></li>
                <li><strong>Valid category enums:</strong> <code>gaming | business | student | creative</code></li>
                <li><strong>Valid portability enums:</strong> <code>ultralight | standard | desktop_replacement</code></li>
                <li><strong>Valid screen enums:</strong> <code>oled | high_refresh | standard</code></li>
                <li><strong>Valid GPU enums:</strong> <code>integrated | dedicated</code></li>
                <li>Laptops matching existing names will automatically merge and update inventory.</li>
            </ul>
        </div>
    </div>
</section>

<!-- FILTERING & TABLE LISTING -->
<section class="table-card">
    <div class="card-head">
        <h2>Active Inventory</h2>
    </div>
    <form class="filter-bar" method="get">
        <label>
            Search
            <input type="text" name="search" value="<?= adminH($search) ?>" placeholder="Name or brand">
        </label>
        <label>
            Usage
            <select name="usage_category">
                <option value="">All usages</option>
                <option value="gaming" <?= $usageCategory === 'gaming' ? 'selected' : '' ?>>Gaming</option>
                <option value="business" <?= $usageCategory === 'business' ? 'selected' : '' ?>>Business</option>
                <option value="student" <?= $usageCategory === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="creative" <?= $usageCategory === 'creative' ? 'selected' : '' ?>>Creative</option>
            </select>
        </label>
        <label>
            Portability
            <select name="portability_tier">
                <option value="">All portability</option>
                <option value="ultralight" <?= $portabilityTier === 'ultralight' ? 'selected' : '' ?>>Ultralight (&lt;1.5kg)</option>
                <option value="standard" <?= $portabilityTier === 'standard' ? 'selected' : '' ?>>Standard (1.5kg-2.2kg)</option>
                <option value="desktop_replacement" <?= $portabilityTier === 'desktop_replacement' ? 'selected' : '' ?>>Desktop Replacement</option>
            </select>
        </label>
        <label>
            Stock Status
            <select name="stock_status">
                <option value="">All statuses</option>
                <option value="in_stock" <?= $stockStatus === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                <option value="low_stock" <?= $stockStatus === 'low_stock' ? 'selected' : '' ?>>Low stock</option>
                <option value="out_of_stock" <?= $stockStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
            </select>
        </label>
        <button class="button button-primary" type="submit">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Laptop Details</th>
                <th>Outcome Traits</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Added</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($laptops === []): ?>
                <tr><td colspan="6">No laptops match the current filters.</td></tr>
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
                            <small class="inline-note" style="text-decoration: line-through; display: block; color: var(--muted); font-size: 0.75rem;">Old <?= adminMoney((float) $laptop['old_price']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= adminStockBadgeClass((int) $laptop['stock_quantity'], (int) $laptop['reorder_level']) ?>">
                            <?= (int) $laptop['stock_quantity'] ?>
                        </span>
                    </td>
                    <td><?= adminH(substr((string) $laptop['created_at'], 0, 10)) ?></td>
                    <td class="table-actions">
                        <a class="button button-light button-small" href="admin-laptop-form.php?id=<?= (int) $laptop['id'] ?>">Edit</a>
                        <form method="post" action="admin-laptop-delete.php" onsubmit="return confirm('Delete this laptop from the catalog?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $laptop['id'] ?>">
                            <button class="button button-danger button-small" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php adminPageEnd(); ?>
