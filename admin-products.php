<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureProductAdminColumns($pdo);

// Handle CSV Import
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['csv_file'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-products.php?error=' . urlencode('Invalid session token.'));
    }

    if (empty($_FILES['csv_file']['name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        adminRedirect('admin-products.php?error=' . urlencode('Please select a valid CSV file.'));
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        adminRedirect('admin-products.php?error=' . urlencode('Could not open the uploaded file.'));
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        adminRedirect('admin-products.php?error=' . urlencode('CSV file is empty or invalid.'));
    }

    $headers = array_map(function($h) {
        return strtolower(trim(str_replace([' ', '_'], '', $h)));
    }, $headers);

    $columnMap = [
        'name' => 'name',
        'brand' => 'brand',
        'category' => 'category',
        'price' => 'price',
        'oldprice' => 'old_price',
        'badge' => 'badge',
        'rating' => 'rating',
        'reviews' => 'reviews',
        'image' => 'image',
        'instock' => 'in_stock',
        'stockquantity' => 'stock_quantity',
        'reorderlevel' => 'reorder_level',
        'specs' => 'specs'
    ];

    $headerIndexes = [];
    foreach ($headers as $index => $header) {
        if (isset($columnMap[$header])) {
            $headerIndexes[$columnMap[$header]] = $index;
        }
    }

    $required = ['name', 'brand', 'category', 'price'];
    $missing = [];
    foreach ($required as $req) {
        if (!isset($headerIndexes[$req])) {
            $missing[] = $req;
        }
    }

    if ($missing !== []) {
        fclose($handle);
        adminRedirect('admin-products.php?error=' . urlencode('Missing required columns: ' . implode(', ', $missing)));
    }

    $checkStmt = $pdo->prepare('SELECT id FROM products WHERE name = ? LIMIT 1');
    
    $insertStmt = $pdo->prepare('
        INSERT INTO products 
        (name, brand, category, price, old_price, badge, rating, reviews, image, in_stock, stock_quantity, reorder_level, specs)
        VALUES 
        (:name, :brand, :category, :price, :old_price, :badge, :rating, :reviews, :image, :in_stock, :stock_quantity, :reorder_level, :specs)
    ');

    $updateStmt = $pdo->prepare('
        UPDATE products
        SET name = :name, brand = :brand, category = :category, price = :price, old_price = :old_price, 
            badge = :badge, rating = :rating, reviews = :reviews, image = :image, 
            in_stock = :in_stock, stock_quantity = :stock_quantity, reorder_level = :reorder_level, specs = :specs
        WHERE id = :id
    ');

    $inserted = 0;
    $updated = 0;
    $rowNum = 1;

    try {
        $pdo->beginTransaction();
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            
            $val = function($colName, $default = null) use ($row, $headerIndexes) {
                if (isset($headerIndexes[$colName]) && isset($row[$headerIndexes[$colName]])) {
                    return trim($row[$headerIndexes[$colName]]);
                }
                return $default;
            };

            $name = $val('name');
            if ($name === '') continue;

            $brand = $val('brand');
            $category = $val('category');
            $price = (float) $val('price');
            $oldPriceVal = $val('old_price');
            $oldPrice = ($oldPriceVal === '' || $oldPriceVal === null) ? null : (float) $oldPriceVal;
            $badge = $val('badge') ?: null;
            $ratingVal = $val('rating');
            $rating = ($ratingVal === '' || $ratingVal === null) ? null : (float) $ratingVal;
            $reviews = (int) $val('reviews', '0');
            $image = $val('image') ?: null;

            $stockQty = (int) $val('stock_quantity', '10');
            $reorderLvl = (int) $val('reorder_level', '5');
            $inStockVal = $val('in_stock', '1');
            $inStock = ($inStockVal === '0' || $stockQty <= 0) ? 0 : 1;

            $specsStr = $val('specs', '{}');
            $specs = json_decode($specsStr, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $specs = ["Details" => $specsStr];
            }
            $specsJson = json_encode($specs, JSON_UNESCAPED_SLASHES);

            $checkStmt->execute([$name]);
            $existingId = $checkStmt->fetchColumn();

            $payload = [
                'name' => $name, 'brand' => $brand, 'category' => $category, 'price' => $price,
                'old_price' => $oldPrice, 'badge' => $badge, 'rating' => $rating, 'reviews' => $reviews,
                'image' => $image, 'in_stock' => $inStock, 'stock_quantity' => $stockQty, 
                'reorder_level' => $reorderLvl, 'specs' => $specsJson
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

        try {
            adminExportProductsToDataJs($pdo);
        } catch (Throwable $e) {
            // JS update failure shouldn't rollback the db update since they were inserted correctly
        }

        adminRedirect("admin-products.php?saved=1&msg=" . urlencode("Successfully imported CSV. Inserted: {$inserted}, Updated: {$updated} components."));
    } catch (Throwable $e) {
        $pdo->rollBack();
        fclose($handle);
        adminRedirect('admin-products.php?error=' . urlencode($e->getMessage()));
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$categories = adminFetchAll($pdo, 'SELECT DISTINCT category FROM products ORDER BY category ASC');
$params = [
    'search_empty' => $search === '' ? 1 : 0,
    'search_name' => '%' . $search . '%',
    'search_brand' => '%' . $search . '%',
    'search_category' => '%' . $search . '%',
    'category_empty' => $category === '' ? 1 : 0,
    'category_filter' => $category,
];

$statusWhere = '';
if ($status === 'in_stock') {
    $statusWhere = ' AND in_stock = 1 AND stock_quantity > 0';
} elseif ($status === 'low_stock') {
    $statusWhere = ' AND stock_quantity <= reorder_level';
} elseif ($status === 'out_of_stock') {
    $statusWhere = ' AND (in_stock = 0 OR stock_quantity <= 0)';
}

$products = adminFetchAll($pdo, '
    SELECT id, name, brand, category, price, old_price, stock_quantity, reorder_level, featured, in_stock, created_at
    FROM products
    WHERE (:search_empty = 1 OR name LIKE :search_name OR brand LIKE :search_brand OR category LIKE :search_category)
      AND (:category_empty = 1 OR category = :category_filter)
      ' . $statusWhere . '
    ORDER BY created_at DESC, id DESC
', $params);

adminPageStart('Components Admin', 'products');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Data Management</span>
        <h1>Components</h1>
        <p class="section-copy">Search, edit, add, and remove catalog records from one table.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-stock.php">Stock</a>
        <a class="button button-light" href="api/export-products.php?format=csv"><i class="fas fa-file-csv"></i> CSV</a>
        <a class="button button-light" href="api/export-products.php?format=r"><i class="fas fa-code"></i> RData Script</a>
        <a class="button button-primary" href="admin-product-form.php">Add Component</a>
    </div>
</section>

<section class="table-card csv-import-card" style="margin-top: 20px; margin-bottom: 24px; border: 1px dashed rgba(0, 245, 212, 0.3); background: rgba(0, 245, 212, 0.02); transition: all 0.3s ease;">
    <div class="card-head" style="border-bottom: none; margin-bottom: 15px;">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--text); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-import" style="color: var(--cyan);"></i> Bulk Import Components (CSV)
            </h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 0.82rem;">Update or populate the entire hardware catalog using a standard CSV format.</p>
        </div>
        <a class="button button-light button-small" href="admin-component-csv-template.php" style="border-color: rgba(0,245,212,0.2);"><i class="fas fa-download"></i> Download CSV Template</a>
    </div>
    
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
</section>


<?php if (isset($_GET['saved'])): ?>
    <div class="admin-alert success">Component saved successfully.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="admin-alert success">Component deleted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<section class="table-card">
    <div class="card-head">
        <h2>Catalog Records</h2>
    </div>
    <form class="filter-bar" method="get">
        <label>
            Search
            <input type="text" name="search" value="<?= adminH($search) ?>" placeholder="Name, brand, category">
        </label>
        <label>
            Category
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $row): ?>
                    <option value="<?= adminH($row['category']) ?>" <?= $category === $row['category'] ? 'selected' : '' ?>><?= adminH($row['category']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Status
            <select name="status">
                <option value="">All statuses</option>
                <option value="in_stock" <?= $status === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                <option value="low_stock" <?= $status === 'low_stock' ? 'selected' : '' ?>>Low stock</option>
                <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
            </select>
        </label>
        <button class="button button-primary" type="submit">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Featured</th>
                <th>Added</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products === []): ?>
                <tr><td colspan="6">No products match the current filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <strong><?= adminH($product['name']) ?></strong>
                        <small><?= adminH($product['brand']) ?> - <?= adminH($product['category']) ?></small>
                    </td>
                    <td>
                        <?= adminMoney((float) $product['price']) ?>
                        <?php if (!empty($product['old_price'])): ?>
                            <small class="inline-note">Old <?= adminMoney((float) $product['old_price']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= adminStockBadgeClass((int) $product['stock_quantity'], (int) $product['reorder_level']) ?>">
                            <?= (int) $product['stock_quantity'] ?>
                        </span>
                    </td>
                    <td><?= !empty($product['featured']) ? 'Yes' : 'No' ?></td>
                    <td><?= adminH(substr((string) $product['created_at'], 0, 10)) ?></td>
                    <td class="table-actions">
                        <a class="button button-light button-small" href="admin-product-form.php?id=<?= (int) $product['id'] ?>">Edit</a>
                        <form method="post" action="admin-product-delete.php" onsubmit="return confirm('Delete this product?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <button class="button button-danger button-small" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php adminPageEnd(); ?>
