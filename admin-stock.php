<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);
// Ensure laptop restock plans table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS laptop_restock_plans (
        laptop_id INT PRIMARY KEY,
        status ENUM('needed','ordered','received') NOT NULL DEFAULT 'needed',
        expected_at DATE DEFAULT NULL,
        notify_waiting TINYINT(1) NOT NULL DEFAULT 0,
        note VARCHAR(255) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$syncedStock = adminSyncMissingStockFromAvailability($pdo);
if ($syncedStock > 0) {
    adminExportProductsToDataJs($pdo);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        adminRedirect('admin-stock.php?error=' . urlencode('Invalid session token.'));
    }

    $itemId = (int) ($_POST['item_id'] ?? 0);
    $isLaptop = (int) ($_POST['is_laptop'] ?? 0);
    $statusPost = in_array($_POST['restock_status'] ?? '', ['needed', 'ordered', 'received'], true) ? $_POST['restock_status'] : 'needed';
    $expectedAt = trim((string) ($_POST['expected_at'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    $notifyWaiting = isset($_POST['notify_waiting']) ? 1 : 0;

    if ($itemId > 0) {
        if ($isLaptop) {
            $stmt = $pdo->prepare('
                INSERT INTO laptop_restock_plans (laptop_id, status, expected_at, notify_waiting, note)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    expected_at = VALUES(expected_at),
                    notify_waiting = VALUES(notify_waiting),
                    note = VALUES(note)
            ');
            $stmt->execute([$itemId, $statusPost, $expectedAt !== '' ? $expectedAt : null, $notifyWaiting, $note !== '' ? $note : null]);
            adminLogActivity($pdo, 'restock_plan', 'laptop', $itemId, "Updated restock plan for laptop #{$itemId}");
            $sent = 0;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO stock_restock_plans (product_id, status, expected_at, notify_waiting, note)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    expected_at = VALUES(expected_at),
                    notify_waiting = VALUES(notify_waiting),
                    note = VALUES(note)
            ');
            $stmt->execute([$itemId, $statusPost, $expectedAt !== '' ? $expectedAt : null, $notifyWaiting, $note !== '' ? $note : null]);

            $sent = 0;
            if ($statusPost === 'received' && $notifyWaiting) {
                $sent = adminSendRestockNotifications($pdo, $itemId);
            }
            adminLogActivity($pdo, 'restock_plan', 'product', $itemId, "Updated restock plan for product #{$itemId}");
        }
        adminRedirect('admin-stock.php?restock_saved=1' . ($sent > 0 ? '&sent=' . $sent : ''));
    }
}

$category = trim((string) ($_GET['category'] ?? ''));
$categories = adminFetchAll($pdo, 'SELECT DISTINCT category FROM products ORDER BY category ASC');
$categories[] = ['category' => 'Laptop'];

$stats = [
    'references' => (int) adminFetchValue($pdo, 'SELECT (SELECT COUNT(*) FROM products) + (SELECT COUNT(*) FROM laptops)'),
    'units' => (int) adminFetchValue($pdo, 'SELECT (SELECT COALESCE(SUM(stock_quantity), 0) FROM products) + (SELECT COALESCE(SUM(stock_quantity), 0) FROM laptops)'),
    'alerts' => (int) adminFetchValue($pdo, 'SELECT (SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level) + (SELECT COUNT(*) FROM laptops WHERE stock_quantity <= reorder_level)'),
    'out' => (int) adminFetchValue($pdo, 'SELECT (SELECT COUNT(*) FROM products WHERE stock_quantity <= 0 OR in_stock = 0) + (SELECT COUNT(*) FROM laptops WHERE stock_quantity <= 0 OR in_stock = 0)'),
];

if ($category === 'Laptop') {
    $products = adminFetchAll($pdo, "
        SELECT l.id, l.name, l.brand, 'Laptop' AS category, l.price, l.stock_quantity, l.reorder_level, l.in_stock,
               lr.status AS restock_status, lr.expected_at, lr.notify_waiting, lr.note,
               0 AS waitlist_count,
               1 AS is_laptop
        FROM laptops l
        LEFT JOIN laptop_restock_plans lr ON lr.laptop_id = l.id
        ORDER BY l.stock_quantity ASC, l.name ASC
    ");
} elseif ($category !== '') {
    $products = adminFetchAll($pdo, '
        SELECT p.id, p.name, p.brand, p.category, p.price, p.stock_quantity, p.reorder_level, p.in_stock,
               rp.status AS restock_status, rp.expected_at, rp.notify_waiting, rp.note,
               COUNT(rn.id) AS waitlist_count,
               0 AS is_laptop
        FROM products p
        LEFT JOIN stock_restock_plans rp ON rp.product_id = p.id
        LEFT JOIN restock_notifications rn ON rn.product_id = p.id AND rn.notified = 0
        WHERE p.category = :category_filter
        GROUP BY p.id, p.name, p.brand, p.category, p.price, p.stock_quantity, p.reorder_level, p.in_stock,
                 rp.status, rp.expected_at, rp.notify_waiting, rp.note
        ORDER BY p.stock_quantity ASC, p.name ASC
    ', ['category_filter' => $category]);
} else {
    $products = adminFetchAll($pdo, "
        SELECT id, name, brand, category, price, stock_quantity, reorder_level, in_stock,
               restock_status, expected_at, notify_waiting, note, waitlist_count, is_laptop
        FROM (
            SELECT p.id, p.name, p.brand, p.category, p.price, p.stock_quantity, p.reorder_level, p.in_stock,
                   rp.status AS restock_status, rp.expected_at, rp.notify_waiting, rp.note,
                   COUNT(rn.id) AS waitlist_count,
                   0 AS is_laptop
            FROM products p
            LEFT JOIN stock_restock_plans rp ON rp.product_id = p.id
            LEFT JOIN restock_notifications rn ON rn.product_id = p.id AND rn.notified = 0
            GROUP BY p.id, p.name, p.brand, p.category, p.price, p.stock_quantity, p.reorder_level, p.in_stock,
                     rp.status, rp.expected_at, rp.notify_waiting, rp.note
            
            UNION ALL
            
            SELECT l.id, l.name, l.brand, 'Laptop' AS category, l.price, l.stock_quantity, l.reorder_level, l.in_stock,
                   lr.status AS restock_status, lr.expected_at, lr.notify_waiting, lr.note,
                   0 AS waitlist_count,
                   1 AS is_laptop
            FROM laptops l
            LEFT JOIN laptop_restock_plans lr ON lr.laptop_id = l.id
        ) combined
        ORDER BY stock_quantity ASC, name ASC
    ");
}

$reorderProducts = adminFetchAll($pdo, "
    SELECT id, name, category, stock_quantity, reorder_level, 0 AS is_laptop
    FROM products
    WHERE stock_quantity <= reorder_level
    UNION ALL
    SELECT id, name, 'Laptop' AS category, stock_quantity, reorder_level, 1 AS is_laptop
    FROM laptops
    WHERE stock_quantity <= reorder_level
    ORDER BY stock_quantity ASC, name ASC
");

adminPageStart('Stock Monitoring', 'stock');
?>
<style>
/* Modern styling enhancement for Stock Management */
.stock-admin-page {
    gap: 28px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: var(--cyan);
    box-shadow: 0 8px 30px rgba(0, 245, 212, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--cyan);
    opacity: 0.7;
}

.stat-card strong {
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    color: var(--text);
    display: block;
    margin-bottom: 6px;
}

.stat-card span {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
}

.reorder-chip {
    transition: all 0.3s ease;
    border-left: 4px solid #ffcf4d !important;
}

.reorder-chip:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.15);
    background: rgba(255, 193, 7, 0.12);
}

.inventory-row {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.inventory-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    border-color: var(--cyan);
}

.inventory-metric strong {
    font-size: 1.05rem;
    color: var(--text);
}

.inventory-metric span.status-badge {
    padding: 6px 12px;
    font-size: 0.9rem;
    font-weight: 800;
    border-radius: 30px;
    display: inline-block;
    text-align: center;
}

.inventory-metric span.status-badge.is-danger {
    background: rgba(255, 61, 90, 0.15);
    color: var(--red);
    border: 1px solid rgba(255, 61, 90, 0.3);
}

.inventory-metric span.status-badge.is-warn {
    background: rgba(255, 193, 7, 0.15);
    color: #ffcf4d;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.inventory-metric span.status-badge.is-good {
    background: rgba(0, 245, 212, 0.1);
    color: var(--cyan);
    border: 1px solid rgba(0, 245, 212, 0.2);
}

.restock-mini-form {
    border-top: 1px solid var(--border);
    padding-top: 14px;
    margin-top: 10px;
}

.restock-mini-form select,
.restock-mini-form input {
    background: var(--card-bg) !important;
    border-color: var(--border) !important;
}

.restock-mini-form select:focus,
.restock-mini-form input:focus {
    border-color: var(--cyan) !important;
}
</style>

<div class="stock-admin-page">
<section class="section-heading">
    <div>
        <span class="eyebrow">Stock Monitoring</span>
        <h1>Stock Section</h1>
        <p class="section-copy">Monitor inventory pressure, log restock plans, and manage reordering thresholds for both Components & Laptops.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-products.php">Components</a>
        <a class="button button-light" href="admin-laptops.php">Laptops</a>
        <a class="button button-primary" href="admin-product-form.php">Add Component</a>
    </div>
</section>

<?php if (isset($_GET['restock_saved'])): ?>
    <div class="admin-alert success">Restock workflow updated<?= isset($_GET['sent']) ? '; notified ' . (int) $_GET['sent'] . ' waiting customer(s)' : '' ?>.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <article class="stat-card"><strong><?= $stats['references'] ?></strong><span>Total References</span></article>
    <article class="stat-card"><strong><?= $stats['units'] ?></strong><span>Units available</span></article>
    <article class="stat-card"><strong><?= $stats['alerts'] ?></strong><span>Reorder alerts</span></article>
    <article class="stat-card"><strong><?= $stats['out'] ?></strong><span>Out of stock</span></article>
</div>

<section class="table-card reorder-panel">
    <div class="card-head">
        <h2>Items to Reorder (Low Stock Thresholds)</h2>
    </div>
    <?php if ($reorderProducts === []): ?>
        <p class="empty-copy">No catalog items are currently below reorder level.</p>
    <?php else: ?>
        <div class="reorder-strip">
            <?php foreach ($reorderProducts as $product): ?>
                <a href="<?= $product['is_laptop'] ? 'admin-laptop-form.php' : 'admin-product-form.php' ?>?id=<?= (int) $product['id'] ?>" class="reorder-chip">
                    <strong><?= adminH($product['name']) ?></strong>
                    <span style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><?= adminH(ucfirst($product['category'])) ?></span>
                        <span><?= (int) $product['stock_quantity'] ?> / <?= (int) $product['reorder_level'] ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="table-card inventory-panel">
    <div class="card-head">
        <div>
            <h2>Detailed Stock Listing</h2>
            <p class="card-copy">Unified registry displaying both components and laptop stock levels. Sorted by lowest stock first.</p>
        </div>
    </div>
    <form class="filter-bar stock-filter-bar" method="get">
        <label>
            Filter Category
            <select name="category">
                <option value="">All categories & laptops</option>
                <?php foreach ($categories as $row): ?>
                    <option value="<?= adminH($row['category']) ?>" <?= $category === $row['category'] ? 'selected' : '' ?>><?= adminH($row['category']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button button-primary" type="submit">Filter</button>
    </form>

    <?php if ($products === []): ?>
        <p class="empty-copy">No stock records found matching filters.</p>
    <?php else: ?>
        <div class="inventory-list">
            <?php foreach ($products as $product): ?>
                <?php
                    $stock = (int) $product['stock_quantity'];
                    $reorderLevel = (int) $product['reorder_level'];
                    $badgeClass = adminStockBadgeClass($stock, $reorderLevel);
                    $stockLabel = $stock <= 0 ? 'Out' : ($stock <= $reorderLevel ? 'Low' : 'Ready');
                ?>
                <article class="inventory-row <?= $badgeClass ?>">
                    <div class="inventory-product">
                        <strong><?= adminH($product['name']) ?></strong>
                        <small><?= adminH($product['brand']) ?> - <span style="text-transform: uppercase; font-size: 0.72rem; color: var(--cyan);"><?= adminH($product['category']) ?></span></small>
                    </div>
                    <div class="inventory-metric">
                        <span class="status-badge <?= $badgeClass ?>"><?= $stock ?></span>
                        <small><?= adminH($stockLabel) ?></small>
                    </div>
                    <div class="inventory-metric">
                        <strong><?= $reorderLevel ?></strong>
                        <small>Reorder at</small>
                    </div>
                    <div class="inventory-metric inventory-price">
                        <strong><?= adminMoney((float) $product['price']) ?></strong>
                        <small>Unit price</small>
                    </div>
                    <div class="inventory-metric inventory-value">
                        <strong><?= adminMoney((float) $product['price'] * $stock) ?></strong>
                        <small>Stock value</small>
                    </div>
                    <form method="post" class="restock-mini-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="item_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="is_laptop" value="<?= (int) $product['is_laptop'] ?>">
                        <select name="restock_status" title="Restock status">
                            <?php foreach (['needed' => 'Needed', 'ordered' => 'Ordered', 'received' => 'Received'] as $value => $label): ?>
                                <option value="<?= adminH($value) ?>" <?= ($product['restock_status'] ?? 'needed') === $value ? 'selected' : '' ?>><?= adminH($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="expected_at" value="<?= adminH($product['expected_at'] ?? '') ?>" title="Expected restock date">
                        <label class="restock-notify">
                            <input type="checkbox" name="notify_waiting" <?= !empty($product['notify_waiting']) ? 'checked' : '' ?>>
                            Notify <?= (int) $product['waitlist_count'] ?>
                        </label>
                        <input type="text" name="note" value="<?= adminH($product['note'] ?? '') ?>" placeholder="Supplier note">
                        <button class="button button-primary button-small" type="submit">Save</button>
                    </form>
                    <a class="button button-light button-small" href="<?= $product['is_laptop'] ? 'admin-laptop-form.php' : 'admin-product-form.php' ?>?id=<?= (int) $product['id'] ?>">Update</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</div>
<?php adminPageEnd(); ?>
