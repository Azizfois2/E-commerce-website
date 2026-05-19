<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$productId = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
$editing = $productId > 0;
$product = $editing
    ? adminFetchAll($pdo, 'SELECT * FROM products WHERE id = ? LIMIT 1', [$productId])[0] ?? null
    : null;

if ($editing && !$product) {
    adminRedirect('admin-products.php?error=' . urlencode('Product not found.'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-products.php?error=' . urlencode('Invalid session token.'));
    }

    $uploadedImagePath = null;
    if (!empty($_FILES['product_image']['name']) && is_uploaded_file($_FILES['product_image']['tmp_name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $mime = mime_content_type($_FILES['product_image']['tmp_name']) ?: '';
        if (!isset($allowed[$mime])) {
            adminRedirect('admin-product-form.php' . ($editing ? '?id=' . $productId . '&' : '?') . 'error=' . urlencode('Image must be JPG, PNG, WEBP, or SVG.'));
        }
        if ((int) $_FILES['product_image']['size'] > 4 * 1024 * 1024) {
            adminRedirect('admin-product-form.php' . ($editing ? '?id=' . $productId . '&' : '?') . 'error=' . urlencode('Image must be smaller than 4 MB.'));
        }
        $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($_POST['name'] ?? 'product')));
        $base = trim($base, '-') ?: 'product';
        $fileName = $base . '-' . time() . '.' . $allowed[$mime];
        $targetDir = __DIR__ . '/Images/products';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            adminRedirect('admin-product-form.php' . ($editing ? '?id=' . $productId . '&' : '?') . 'error=' . urlencode('Image upload failed.'));
        }
        $uploadedImagePath = 'Images/products/' . $fileName;
    }

    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'brand' => trim((string) ($_POST['brand'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'price' => (float) ($_POST['price'] ?? 0),
        'old_price' => ($_POST['old_price'] ?? '') === '' ? null : (float) $_POST['old_price'],
        'badge' => trim((string) ($_POST['badge'] ?? '')),
        'rating' => ($_POST['rating'] ?? '') === '' ? null : (float) $_POST['rating'],
        'reviews' => max(0, (int) ($_POST['reviews'] ?? 0)),
        'image' => trim((string) ($_POST['image'] ?? '')),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'in_stock' => isset($_POST['in_stock']) ? 1 : 0,
        'stock_quantity' => max(0, (int) ($_POST['stock_quantity'] ?? 0)),
        'reorder_level' => max(0, (int) ($_POST['reorder_level'] ?? 0)),
        'specs' => trim((string) ($_POST['specs'] ?? '')),
    ];
    if ($uploadedImagePath !== null) {
        $payload['image'] = $uploadedImagePath;
    }

    if ($payload['name'] === '' || $payload['brand'] === '' || $payload['category'] === '' || $payload['price'] <= 0) {
        adminRedirect('admin-product-form.php' . ($editing ? '?id=' . $productId . '&' : '?') . 'error=' . urlencode('Name, brand, category, and price are required.'));
    }

    $specs = $payload['specs'] === '' ? [] : json_decode($payload['specs'], true);
    if ($payload['specs'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
        adminRedirect('admin-product-form.php' . ($editing ? '?id=' . $productId . '&' : '?') . 'error=' . urlencode('Specs must be valid JSON.'));
    }
    $payload['specs'] = json_encode($specs, JSON_UNESCAPED_SLASHES);
    $payload['badge'] = $payload['badge'] === '' ? null : $payload['badge'];
    $payload['image'] = $payload['image'] === '' ? null : $payload['image'];
    if ($payload['stock_quantity'] <= 0) {
        $payload['in_stock'] = 0;
    }

    if ($editing) {
        $oldStock = (int) adminFetchValue($pdo, 'SELECT stock_quantity FROM products WHERE id = ?', [$productId]);
        $oldPrice = (float) adminFetchValue($pdo, 'SELECT price FROM products WHERE id = ?', [$productId]);

        $stmt = $pdo->prepare('
            UPDATE products
            SET name = :name, brand = :brand, category = :category, price = :price, old_price = :old_price,
                badge = :badge, rating = :rating, reviews = :reviews, image = :image, featured = :featured,
                in_stock = :in_stock, stock_quantity = :stock_quantity, reorder_level = :reorder_level, specs = :specs
            WHERE id = :id
        ');
        $stmt->execute($payload + ['id' => $productId]);
        adminLogActivity($pdo, 'update', 'product', $productId, "Updated product '{$payload['name']}'");

        // ── Log price change into product_price_history ──
        if (abs($oldPrice - $payload['price']) > 0.001) {
            try {
                $adminId = (int) ($_SESSION['admin_id'] ?? 0);
                $pdo->prepare('INSERT INTO product_price_history (product_id, old_price, new_price, changed_by_admin_id) VALUES (?, ?, ?, ?)')
                    ->execute([$productId, $oldPrice, $payload['price'], $adminId]);
            } catch (Throwable $e) { /* Never block the save */ }
        }

        // ── Log stock change into inventory_adjustments ──
        $stockDelta = $payload['stock_quantity'] - $oldStock;
        if ($stockDelta !== 0) {
            try {
                $adminId = (int) ($_SESSION['admin_id'] ?? 0);
                $pdo->prepare('INSERT INTO inventory_adjustments (product_id, quantity_changed, reason, authorized_by_admin_id, notes) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$productId, $stockDelta, 'physical_count', $adminId, "Stock adjusted from {$oldStock} to {$payload['stock_quantity']} via product form"]);
            } catch (Throwable $e) { /* Never block the save */ }
        }

        // Trigger restock notifications if stock went from 0 to >0
        if ($oldStock === 0 && $payload['stock_quantity'] > 0) {
            $notifs = adminFetchAll($pdo, "SELECT id, email FROM restock_notifications WHERE product_id = ? AND notified = 0", [$productId]);
            foreach ($notifs as $n) {
                error_log("RESTOCK NOTIFICATION: Product '{$payload['name']}' is back in stock. Email sent to: {$n['email']}");
                $pdo->prepare("UPDATE restock_notifications SET notified = 1 WHERE id = ?")->execute([$n['id']]);
            }
        }
    } else {
        $nextId = (int) adminFetchValue($pdo, 'SELECT COALESCE(MAX(id), 0) + 1 FROM products', [], 1);
        $stmt = $pdo->prepare('
            INSERT INTO products
                (id, name, brand, category, price, old_price, badge, rating, reviews, image, featured, in_stock, stock_quantity, reorder_level, specs)
            VALUES
                (:id, :name, :brand, :category, :price, :old_price, :badge, :rating, :reviews, :image, :featured, :in_stock, :stock_quantity, :reorder_level, :specs)
        ');
        $stmt->execute($payload + ['id' => $nextId]);
        adminLogActivity($pdo, 'create', 'product', $nextId, "Created product '{$payload['name']}'");
    }

    try {
        adminExportProductsToDataJs($pdo);
    } catch (Throwable $e) {
        adminRedirect('admin-products.php?error=' . urlencode('Product saved, but js/data.js could not be updated.'));
    }

    adminRedirect('admin-products.php?saved=1');
}

$specsValue = $product && !empty($product['specs'])
    ? json_encode(json_decode((string) $product['specs'], true) ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    : "{\n  \"CPU\": \"\",\n  \"GPU\": \"\"\n}";

adminPageStart($editing ? 'Edit Product' : 'Add Product', 'products');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">CRUD Product</span>
        <h1><?= $editing ? 'Edit Product' : 'Add Product' ?></h1>
        <p class="section-copy">Keep product data, pricing, visibility, and stock thresholds accurate.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="admin-products.php">Back to Products</a>
    </div>
</section>

<?php if (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<section class="table-card">
    <form method="post" class="stack-form form-grid" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">

        <label>
            Product name
            <input type="text" name="name" value="<?= adminH($product['name'] ?? '') ?>" required>
        </label>
        <label>
            Brand
            <input type="text" name="brand" value="<?= adminH($product['brand'] ?? '') ?>" required>
        </label>
        <label>
            Category
            <input type="text" name="category" value="<?= adminH($product['category'] ?? '') ?>" required>
        </label>
        <label>
            Image path
            <input type="text" name="image" id="imagePathInput" value="<?= adminH($product['image'] ?? '') ?>" placeholder="Images/products/item.png">
        </label>
        <label style="display: block;">
            Upload / replace image
            <label class="custom-file-upload" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed rgba(0, 245, 212, 0.25); border-radius: 12px; padding: 20px; background: rgba(20, 22, 28, 0.5); cursor: pointer; transition: all 0.2s ease; margin-top: 6px; text-align: center;">
                <i class="fas fa-image" style="font-size: 1.8rem; color: var(--cyan); margin-bottom: 6px;"></i>
                <span style="font-weight: 700; color: var(--text); font-size: 0.85rem;" id="productUploadNameDisplay">Select Product Image</span>
                <span style="font-size: 0.7rem; color: var(--muted); margin-top: 2px;">PNG, JPG, WEBP, SVG</span>
                <input type="file" name="product_image" id="productImageUpload" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display: none;" onchange="document.getElementById('productUploadNameDisplay').textContent = this.files[0] ? this.files[0].name : 'Select Product Image'">
            </label>
        </label>
        <div class="image-manager full-span">
            <div class="image-preview-box">
                <img id="productImagePreview" src="<?= adminH($product['image'] ?? 'Images/products/placeholder-storage.svg') ?>" alt="Product preview" onerror="this.src='Images/products/placeholder-storage.svg'">
            </div>
            <div>
                <strong>Image manager</strong>
                <small>Upload a product image or paste an existing path. Saving updates the database and exported data.js.</small>
            </div>
        </div>
        <label>
            Price
            <input type="number" step="0.01" min="0" name="price" value="<?= adminH($product['price'] ?? '') ?>" required>
        </label>
        <label>
            Old price
            <input type="number" step="0.01" min="0" name="old_price" value="<?= adminH($product['old_price'] ?? '') ?>">
        </label>
        <label>
            Stock quantity
            <input type="number" min="0" name="stock_quantity" value="<?= adminH($product['stock_quantity'] ?? 10) ?>">
        </label>
        <label>
            Reorder level
            <input type="number" min="0" name="reorder_level" value="<?= adminH($product['reorder_level'] ?? 5) ?>">
        </label>
        <label>
            Rating
            <input type="number" step="0.1" min="0" max="5" name="rating" value="<?= adminH($product['rating'] ?? '') ?>">
        </label>
        <label>
            Reviews
            <input type="number" min="0" name="reviews" value="<?= adminH($product['reviews'] ?? 0) ?>">
        </label>
        <label>
            Badge
            <input type="text" name="badge" value="<?= adminH($product['badge'] ?? '') ?>" placeholder="Sale, New, Hot">
        </label>
        <div class="check-row">
            <label><input type="checkbox" name="featured" <?= !empty($product['featured']) ? 'checked' : '' ?>> Featured</label>
            <label><input type="checkbox" name="in_stock" <?= !$product || !empty($product['in_stock']) ? 'checked' : '' ?>> In stock</label>
        </div>
        <label class="full-span">
            Specs JSON
            <textarea name="specs" rows="8"><?= adminH($specsValue) ?></textarea>
        </label>
        <div class="full-span form-actions">
            <button class="button button-primary" type="submit"><?= $editing ? 'Save Changes' : 'Create Product' ?></button>
        </div>
    </form>
</section>
<script>
const imageInput = document.getElementById('imagePathInput');
const imageUpload = document.getElementById('productImageUpload');
const imagePreview = document.getElementById('productImagePreview');
if (imageInput && imagePreview) {
    imageInput.addEventListener('input', () => {
        imagePreview.src = imageInput.value || 'Images/products/placeholder-storage.svg';
    });
}
if (imageUpload && imagePreview) {
    imageUpload.addEventListener('change', () => {
        const file = imageUpload.files && imageUpload.files[0];
        if (file) imagePreview.src = URL.createObjectURL(file);
    });
}
</script>
<style>
.image-manager {
    display: grid;
    grid-template-columns: 96px minmax(0, 1fr);
    gap: 14px;
    align-items: center;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--input-bg);
}
.image-preview-box {
    width: 96px;
    aspect-ratio: 1;
    border: 1px solid var(--border);
    border-radius: 10px;
    display: grid;
    place-items: center;
    overflow: hidden;
    background: var(--card-bg);
}
.image-preview-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.image-manager strong,
.image-manager small { display: block; }
.image-manager small { color: var(--muted); margin-top: 4px; }
</style>
<?php adminPageEnd(); ?>
