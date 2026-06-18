<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();

$laptopId = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
$editing = $laptopId > 0;
$laptop = $editing
    ? adminFetchAll($pdo, 'SELECT * FROM laptops WHERE id = ? LIMIT 1', [$laptopId])[0] ?? null
    : null;

if ($editing && !$laptop) {
    adminRedirect('admin-laptops.php?error=' . urlencode('Laptop not found.'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Invalid session token.'));
    }

    $uploadedImagePath = null;
    if (!empty($_FILES['laptop_image']['name']) && is_uploaded_file($_FILES['laptop_image']['tmp_name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $mime = mime_content_type($_FILES['laptop_image']['tmp_name']) ?: '';
        if (!isset($allowed[$mime])) {
            adminRedirect('admin-laptop-form.php' . ($editing ? '?id=' . $laptopId . '&' : '?') . 'error=' . urlencode('Image must be JPG, PNG, WEBP, or SVG.'));
        }
        if ((int) $_FILES['laptop_image']['size'] > 4 * 1024 * 1024) {
            adminRedirect('admin-laptop-form.php' . ($editing ? '?id=' . $laptopId . '&' : '?') . 'error=' . urlencode('Image must be smaller than 4 MB.'));
        }
        $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($_POST['name'] ?? 'laptop')));
        $base = trim($base, '-') ?: 'laptop';
        $fileName = $base . '-' . time() . '.' . $allowed[$mime];
        $targetDir = __DIR__ . '/images/products';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($_FILES['laptop_image']['tmp_name'], $targetPath)) {
            adminRedirect('admin-laptop-form.php' . ($editing ? '?id=' . $laptopId . '&' : '?') . 'error=' . urlencode('Image upload failed.'));
        }
        $uploadedImagePath = 'images/products/' . $fileName;
    }

    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'brand' => trim((string) ($_POST['brand'] ?? '')),
        'price' => (float) ($_POST['price'] ?? 0),
        'old_price' => ($_POST['old_price'] ?? '') === '' ? null : (float) $_POST['old_price'],
        'image' => trim((string) ($_POST['image'] ?? 'images/products/placeholder-laptop.svg')),
        'usage_category' => strtolower(trim((string) ($_POST['usage_category'] ?? 'gaming'))),
        'portability_tier' => strtolower(trim((string) ($_POST['portability_tier'] ?? 'standard'))),
        'screen_size' => (float) ($_POST['screen_size'] ?? 15.6),
        'screen_quality' => strtolower(trim((string) ($_POST['screen_quality'] ?? 'standard'))),
        'gpu_tier' => strtolower(trim((string) ($_POST['gpu_tier'] ?? 'integrated'))),
        'battery_wh' => max(0, (int) ($_POST['battery_wh'] ?? 50)),
        'weight_kg' => (float) ($_POST['weight_kg'] ?? 1.8),
        'stock_quantity' => max(0, (int) ($_POST['stock_quantity'] ?? 10)),
        'reorder_level' => max(0, (int) ($_POST['reorder_level'] ?? 2)),
        'in_stock' => isset($_POST['in_stock']) ? 1 : 0,
        'specs' => trim((string) ($_POST['specs'] ?? '')),
    ];
    
    if ($uploadedImagePath !== null) {
        $payload['image'] = $uploadedImagePath;
    }

    if ($payload['name'] === '' || $payload['brand'] === '' || $payload['price'] <= 0) {
        adminRedirect('admin-laptop-form.php' . ($editing ? '?id=' . $laptopId . '&' : '?') . 'error=' . urlencode('Name, brand, and price are required.'));
    }

    $specs = $payload['specs'] === '' ? [] : json_decode($payload['specs'], true);
    if ($payload['specs'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
        adminRedirect('admin-laptop-form.php' . ($editing ? '?id=' . $laptopId . '&' : '?') . 'error=' . urlencode('Specs must be valid JSON.'));
    }
    $payload['specs'] = json_encode($specs, JSON_UNESCAPED_SLASHES);

    if ($payload['stock_quantity'] <= 0) {
        $payload['in_stock'] = 0;
    }

    if ($editing) {
        $stmt = $pdo->prepare('
            UPDATE laptops
            SET name = :name, brand = :brand, price = :price, old_price = :old_price, image = :image,
                usage_category = :usage_category, portability_tier = :portability_tier, screen_size = :screen_size,
                screen_quality = :screen_quality, gpu_tier = :gpu_tier, battery_wh = :battery_wh, weight_kg = :weight_kg,
                stock_quantity = :stock_quantity, reorder_level = :reorder_level, in_stock = :in_stock, specs = :specs
            WHERE id = :id
        ');
        $stmt->execute($payload + ['id' => $laptopId]);
        adminLogActivity($pdo, 'update', 'laptop', $laptopId, "Updated laptop '{$payload['name']}'");
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO laptops
                (name, brand, price, old_price, image, usage_category, portability_tier, screen_size, screen_quality, gpu_tier, battery_wh, weight_kg, stock_quantity, reorder_level, in_stock, specs)
            VALUES
                (:name, :brand, :price, :old_price, :image, :usage_category, :portability_tier, :screen_size, :screen_quality, :gpu_tier, :battery_wh, :weight_kg, :stock_quantity, :reorder_level, :in_stock, :specs)
        ');
        $stmt->execute($payload);
        $nextId = (int) $pdo->lastInsertId();
        adminLogActivity($pdo, 'create', 'laptop', $nextId, "Created laptop '{$payload['name']}'");
    }

    // Export laptops
    require_once 'export-laptops.php';
    try {
        adminExportLaptopsToDataJs($pdo);
    } catch (Throwable $e) {
        adminRedirect('admin-laptops.php?error=' . urlencode('Laptop saved, but assets/js/laptop_data.js could not be updated.'));
    }

    adminRedirect('admin-laptops.php?saved=1');
}

$specsValue = $laptop && !empty($laptop['specs'])
    ? json_encode(json_decode((string) $laptop['specs'], true) ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    : "{\n  \"CPU\": \"\",\n  \"RAM\": \"\",\n  \"Storage\": \"\",\n  \"GPU\": \"\",\n  \"Display\": \"\",\n  \"Ports\": \"\"\n}";

adminPageStart($editing ? 'Edit Laptop' : 'Add Laptop', 'laptops');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('CRUD Laptop')) ?></span>
        <h1><?= adminH(adminPhrase($editing ? 'Edit Laptop' : 'Add Laptop')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Map outcome traits, physical metrics, battery, and pricing for this laptop.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="admin-laptops.php"><?= adminH(adminPhrase('Back to Laptops')) ?></a>
    </div>
</section>

<?php if (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<section class="table-card">
    <form method="post" class="stack-form form-grid" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) ($laptop['id'] ?? 0) ?>">

        <label>
            <?= adminH(adminPhrase('Laptop name')) ?>
            <input type="text" name="name" value="<?= adminH($laptop['name'] ?? '') ?>" required>
        </label>
        <label>
            <?= adminH(adminPhrase('Brand')) ?>
            <input type="text" name="brand" value="<?= adminH($laptop['brand'] ?? '') ?>" required>
        </label>
        
        <label>
            <?= adminH(adminPhrase('Usage Category')) ?>
            <select name="usage_category" required>
                <option value="gaming" <?= ($laptop['usage_category'] ?? '') === 'gaming' ? 'selected' : '' ?>><?= adminH(adminPhrase('Gaming')) ?></option>
                <option value="business" <?= ($laptop['usage_category'] ?? '') === 'business' ? 'selected' : '' ?>><?= adminH(adminPhrase('Business')) ?></option>
                <option value="student" <?= ($laptop['usage_category'] ?? '') === 'student' ? 'selected' : '' ?>><?= adminH(adminPhrase('Student')) ?></option>
                <option value="creative" <?= ($laptop['usage_category'] ?? '') === 'creative' ? 'selected' : '' ?>><?= adminH(adminPhrase('Creative')) ?></option>
            </select>
        </label>

        <label>
            <?= adminH(adminPhrase('Portability Tier')) ?>
            <select name="portability_tier" required>
                <option value="ultralight" <?= ($laptop['portability_tier'] ?? '') === 'ultralight' ? 'selected' : '' ?>><?= adminH(adminPhrase('Ultralight (< 1.5kg)')) ?></option>
                <option value="standard" <?= ($laptop['portability_tier'] ?? '') === 'standard' ? 'selected' : '' ?>><?= adminH(adminPhrase('Standard (1.5kg - 2.2kg)')) ?></option>
                <option value="desktop_replacement" <?= ($laptop['portability_tier'] ?? '') === 'desktop_replacement' ? 'selected' : '' ?>><?= adminH(adminPhrase('Desktop Replacement')) ?></option>
            </select>
        </label>

        <label>
            <?= adminH(adminPhrase('Screen Size (Inches)')) ?>
            <input type="number" step="0.1" name="screen_size" value="<?= adminH($laptop['screen_size'] ?? '15.6') ?>" required>
        </label>

        <label>
            <?= adminH(adminPhrase('Screen Quality')) ?>
            <select name="screen_quality" required>
                <option value="standard" <?= ($laptop['screen_quality'] ?? '') === 'standard' ? 'selected' : '' ?>><?= adminH(adminPhrase('Standard IPS / LCD')) ?></option>
                <option value="high_refresh" <?= ($laptop['screen_quality'] ?? '') === 'high_refresh' ? 'selected' : '' ?>><?= adminH(adminPhrase('High Refresh Rate')) ?></option>
                <option value="oled" <?= ($laptop['screen_quality'] ?? '') === 'oled' ? 'selected' : '' ?>><?= adminH(adminPhrase('OLED Premium Display')) ?></option>
            </select>
        </label>

        <label>
            <?= adminH(adminPhrase('GPU Tier')) ?>
            <select name="gpu_tier" required>
                <option value="integrated" <?= ($laptop['gpu_tier'] ?? '') === 'integrated' ? 'selected' : '' ?>><?= adminH(adminPhrase('Integrated Graphics')) ?></option>
                <option value="dedicated" <?= ($laptop['gpu_tier'] ?? '') === 'dedicated' ? 'selected' : '' ?>><?= adminH(adminPhrase('Dedicated Graphics (RTX / Radeon)')) ?></option>
            </select>
        </label>

        <label>
            <?= adminH(adminPhrase('Battery (Watt-hours)')) ?>
            <input type="number" name="battery_wh" value="<?= adminH($laptop['battery_wh'] ?? '60') ?>" required>
        </label>

        <label>
            <?= adminH(adminPhrase('Weight (Kilograms)')) ?>
            <input type="number" step="0.01" name="weight_kg" value="<?= adminH($laptop['weight_kg'] ?? '1.60') ?>" required>
        </label>

        <label>
            <?= adminH(adminPhrase('Image path')) ?>
            <input type="text" name="image" id="imagePathInput" value="<?= adminH($laptop['image'] ?? 'images/products/generic-laptop.png') ?>" placeholder="<?= adminH(adminPhrase('images/products/item.png')) ?>">
        </label>
        <label style="display: block;">
            <?= adminH(adminPhrase('Upload / replace image')) ?>
            <label class="custom-file-upload" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed rgba(0, 245, 212, 0.25); border-radius: 12px; padding: 20px; background: rgba(20, 22, 28, 0.5); cursor: pointer; transition: all 0.2s ease; margin-top: 6px; text-align: center;">
                <i class="fas fa-image" style="font-size: 1.8rem; color: var(--cyan); margin-bottom: 6px;"></i>
                <span style="font-weight: 700; color: var(--text); font-size: 0.85rem;" id="imageUploadNameDisplay"><?= adminH(adminPhrase('Select Laptop Image')) ?></span>
                <span style="font-size: 0.7rem; color: var(--muted); margin-top: 2px;"><?= adminH(adminPhrase('PNG, JPG, WEBP, SVG')) ?></span>
                <input type="file" name="laptop_image" id="laptopImageUpload" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display: none;" onchange="document.getElementById('imageUploadNameDisplay').textContent = this.files[0] ? this.files[0].name : <?= i18n_script_json(adminPhrase('Select Laptop Image')) ?>">
            </label>
        </label>
        <div class="image-manager full-span">
            <div class="image-preview-box">
                <img id="laptopImagePreview" src="<?= adminH($laptop['image'] ?? 'images/products/generic-laptop.png') ?>" alt="<?= adminH(adminPhrase('Laptop preview')) ?>" onerror="this.src='images/products/generic-laptop.png'">
            </div>
            <div>
                <strong><?= adminH(adminPhrase('Laptop image manager')) ?></strong>
                <small><?= adminH(adminPhrase('Upload a laptop photo or use a placeholder path. This updates database and laptop_data.js.')) ?></small>
            </div>
        </div>
        <label>
            <?= adminH(adminPhrase('Price (DH)')) ?>
            <input type="number" step="0.01" min="0" name="price" value="<?= adminH($laptop['price'] ?? '') ?>" required>
        </label>
        <label>
            <?= adminH(adminPhrase('Old price (DH)')) ?>
            <input type="number" step="0.01" min="0" name="old_price" value="<?= adminH($laptop['old_price'] ?? '') ?>">
        </label>
        <label>
            <?= adminH(adminPhrase('Stock quantity')) ?>
            <input type="number" min="0" name="stock_quantity" value="<?= adminH($laptop['stock_quantity'] ?? 10) ?>">
        </label>
        <label>
            <?= adminH(adminPhrase('Reorder level')) ?>
            <input type="number" min="0" name="reorder_level" value="<?= adminH($laptop['reorder_level'] ?? 2) ?>">
        </label>
        <div class="check-row full-span">
            <label><input type="checkbox" name="in_stock" <?= !$laptop || !empty($laptop['in_stock']) ? 'checked' : '' ?>> <?= adminH(adminPhrase('In stock')) ?></label>
        </div>
        <label class="full-span">
            <?= adminH(adminPhrase('Specs JSON')) ?>
            <textarea name="specs" rows="8"><?= adminH($specsValue) ?></textarea>
        </label>
        <div class="full-span form-actions">
            <button class="button button-primary" type="submit"><?= adminH(adminPhrase($editing ? 'Save Changes' : 'Create Laptop')) ?></button>
        </div>
    </form>
</section>
<script>
const imageInput = document.getElementById('imagePathInput');
const imageUpload = document.getElementById('laptopImageUpload');
const imagePreview = document.getElementById('laptopImagePreview');
if (imageInput && imagePreview) {
    imageInput.addEventListener('input', () => {
        imagePreview.src = imageInput.value || 'images/products/placeholder-laptop.svg';
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
