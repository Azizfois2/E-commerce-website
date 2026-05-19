<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$adminName = trim((string) ($_SESSION['admin_nom'] ?? 'Administrator'));
$adminEmail = trim((string) ($_SESSION['admin_email'] ?? ''));
$hasOrders = adminTableExists($pdo, 'orders');
$hasClients = adminTableExists($pdo, 'Client');

$stats = [
    'products' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM products'),
    'laptops' => (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM laptops'),
    'stock' => (int) adminFetchValue($pdo, 'SELECT (SELECT COALESCE(SUM(stock_quantity), 0) FROM products) + (SELECT COALESCE(SUM(stock_quantity), 0) FROM laptops)'),
    'alerts' => (int) adminFetchValue($pdo, 'SELECT (SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level) + (SELECT COUNT(*) FROM laptops WHERE stock_quantity <= reorder_level)'),
    'customers' => $hasClients ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM Client') : 0,
    'orders' => $hasOrders ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM orders') : 0,
    'pending' => $hasOrders ? (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')") : 0,
    'revenue' => $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(total), 0) FROM orders') : 0.0,
    'out_of_stock' => (int) adminFetchValue($pdo, 'SELECT (SELECT COUNT(*) FROM products WHERE stock_quantity <= 0 OR in_stock = 0) + (SELECT COUNT(*) FROM laptops WHERE stock_quantity <= 0)'),
    'avg_order' => $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(AVG(total), 0) FROM orders') : 0.0,
    'today_revenue' => $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = CURDATE()') : 0.0,
];

// Flash sales data
$hasFlashSales = adminTableExists($pdo, 'flash_sales');
$flashSales = [];
$activeFlashCount = 0;
if ($hasFlashSales) {
    $flashSales = adminFetchAll($pdo, "
        SELECT fs.*, p.name AS product_name, p.price AS current_price, p.image AS product_image
        FROM flash_sales fs
        JOIN products p ON p.id = fs.product_id
        ORDER BY fs.ends_at DESC
    ");
    $activeFlashCount = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM flash_sales WHERE starts_at <= NOW() AND ends_at > NOW()");
}

$allProducts = adminFetchAll($pdo, 'SELECT id, name, price, brand FROM products ORDER BY name ASC');

$recentProducts = adminFetchAll($pdo, '
    SELECT id, name, brand, category, price, stock_quantity, reorder_level, in_stock, created_at
    FROM products
    ORDER BY created_at DESC, id DESC
    LIMIT 6
');

$lowStockProducts = adminFetchAll($pdo, '
    SELECT id, name, category, stock_quantity, reorder_level
    FROM products
    WHERE stock_quantity <= reorder_level
    ORDER BY stock_quantity ASC, name ASC
    LIMIT 8
');

$mostWantedRestocks = adminFetchAll($pdo, '
    SELECT p.id, p.name, p.category, p.stock_quantity, COUNT(rn.id) as subscriber_count
    FROM restock_notifications rn
    JOIN products p ON p.id = rn.product_id
    WHERE rn.notified = 0 AND p.stock_quantity <= 0
    GROUP BY p.id, p.name, p.category, p.stock_quantity
    ORDER BY subscriber_count DESC
    LIMIT 5
');

$recentOrders = ($hasOrders && $hasClients)
    ? adminFetchAll($pdo, '
        SELECT o.id, o.status, o.total, o.payment_status, o.created_at, c.nom AS client_name
        FROM orders o
        LEFT JOIN Client c ON c.id_client = o.client_id
        ORDER BY o.created_at DESC, o.id DESC
        LIMIT 8
    ')
    : [];

$notifications = adminDashboardNotifications($pdo);
$recentActivity = adminFetchAll($pdo, '
    SELECT actor_email, action, entity_type, entity_id, summary, created_at
    FROM admin_activity
    ORDER BY created_at DESC, id DESC
    LIMIT 8
');



adminPageStart('Admin Dashboard', 'dashboard');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<section class="section-heading">
    <div>
        <span class="eyebrow">Administration</span>
        <h1>Admin Dashboard</h1>
        <p class="section-copy">Welcome back, <?= adminH($adminName) ?>. Manage catalog data, watch stock pressure, and follow customer orders from one focused workspace.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="admin-products.php">Components</a>
        <a class="button button-light" href="admin-stock.php">Stock</a>
        <a class="button button-light" href="admin-procurement.php">Procurement</a>
        <a class="button button-light" href="admin-orders.php">Orders</a>
        <a class="button button-light" href="admin-diagnostics.php">Diagnostics</a>
        <a class="button button-light" href="admin-customers.php">Customers</a>
        <a class="button button-light" href="admin-marketing.php">Marketing</a>
        <a class="button button-light" href="#flash-sales-section">⚡ Flash Sales</a>
        <a class="button button-primary" href="admin-product-form.php">Add Product</a>
    </div>
</section>

<!-- System Actions Bar -->
<section class="section-heading" style="margin-top: 20px; padding: 20px; background: rgba(0,245,212,0.05); border: 1px solid rgba(0,245,212,0.1); border-radius: 16px;">
    <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
        <div style="flex:1">
            <h3 style="margin:0; font-family: 'Orbitron', sans-serif; font-size: 1rem; color: var(--cyan);">System Maintenance</h3>
            <p style="margin:4px 0 0; font-size: 0.8rem; color: var(--muted);">Manual overrides for background tasks and data syncing.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="triggerSnapshot()" id="snapshotBtn" class="button button-light" style="font-size: 0.75rem;">
                <i class="fas fa-camera"></i> Capture Price History
            </button>
            <button onclick="recalculateTiers()" id="tiersBtn" class="button button-light" style="font-size: 0.75rem;">
                <i class="fas fa-sync"></i> Sync Loyalty Tiers
            </button>
        </div>
    </div>
</section>

<script>
async function triggerSnapshot() {
    const btn = document.getElementById('snapshotBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const res = await fetch('api/price-history.php?action=snapshot');
        const data = await res.json();
        alert(data.success ? data.message : (data.error || 'Failed to capture snapshot'));
    } catch (e) {
        alert('Network error while capturing snapshot');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function recalculateTiers() {
    const btn = document.getElementById('tiersBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    
    try {
        const res = await fetch('api/loyalty.php?action=recalculate_all');
        const data = await res.json();
        alert(data.success ? data.message : (data.error || 'Failed to sync tiers'));
    } catch (e) {
        alert('Network error while syncing tiers');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}
</script>

<div class="admin-profile-bar">
    <div class="profile-mark">
        <span><?= adminH(substr($adminName, 0, 1)) ?></span>
    </div>
    <div>
        <strong><?= adminH($adminName) ?></strong>
        <small><?= adminH($adminEmail ?: 'Administrator session active') ?></small>
    </div>
</div>

<?php if ($notifications !== []): ?>
<section class="notification-grid">
    <?php foreach ($notifications as $note): ?>
        <a class="notification-card <?= adminH($note['tone']) ?>" href="<?= adminH($note['href']) ?>">
            <i class="fas <?= adminH($note['icon']) ?>"></i>
            <span>
                <strong><?= adminH($note['title']) ?></strong>
                <small><?= adminH($note['text']) ?></small>
            </span>
        </a>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<div class="stats-grid">
    <article class="stat-card">
        <strong><?= $stats['products'] ?></strong>
        <span>Components</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['laptops'] ?></strong>
        <span>Laptops</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['stock'] ?></strong>
        <span>Units in stock</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['alerts'] ?></strong>
        <span>Reorder alerts</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['orders'] ?></strong>
        <span>Customer orders</span>
    </article>
</div>

<div class="stats-grid secondary-stats">
    <article class="stat-card">
        <strong><?= $stats['out_of_stock'] ?></strong>
        <span>Out of stock</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['pending'] ?></strong>
        <span>Pending orders</span>
    </article>
    <article class="stat-card">
        <strong><?= adminMoney($stats['revenue']) ?></strong>
        <span>Revenue</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['customers'] ?></strong>
        <span>Customers</span>
        <a class="stat-link" href="admin-customers.php">Manage users</a>
    </article>
</div>


<section class="table-card activity-feed-card">
    <div class="card-head">
        <h2>Dashboard Activity Feed</h2>
    </div>
    <?php if ($recentActivity === []): ?>
        <p class="empty-copy">No admin activity logged yet.</p>
    <?php else: ?>
        <div class="activity-feed">
            <?php foreach ($recentActivity as $event): ?>
                <div class="activity-item">
                    <strong><?= adminH($event['summary']) ?></strong>
                    <span><?= adminH($event['actor_email'] ?: 'System') ?> · <?= adminH(date('M j, H:i', strtotime((string) $event['created_at']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


<div class="dashboard-grid">
    <section class="table-card">
        <div class="card-head">
            <h2>Latest Components</h2>
            <a class="button button-light button-small" href="admin-products.php">Manage</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentProducts === []): ?>
                    <tr><td colspan="5">No products yet. Add your first catalog item.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentProducts as $product): ?>
                    <tr>
                        <td>
                            <strong><?= adminH($product['name']) ?></strong>
                            <small>#<?= (int) $product['id'] ?></small>
                        </td>
                        <td><?= adminH($product['brand']) ?></td>
                        <td><?= adminH($product['category']) ?></td>
                        <td><?= adminMoney((float) $product['price']) ?></td>
                        <td>
                            <span class="status-badge <?= adminStockBadgeClass((int) $product['stock_quantity'], (int) $product['reorder_level']) ?>">
                                <?= (int) $product['stock_quantity'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <aside class="table-card">
        <div class="card-head">
            <h2>Reorder List</h2>
            <a class="button button-light button-small" href="admin-stock.php">Stock</a>
        </div>
        <?php if ($lowStockProducts === []): ?>
            <p class="empty-copy">No product is below its reorder level.</p>
        <?php else: ?>
            <ul class="alert-list">
                <?php foreach ($lowStockProducts as $product): ?>
                    <li>
                        <strong><?= adminH($product['name']) ?></strong>
                        <span><?= adminH($product['category']) ?> - Stock <?= (int) $product['stock_quantity'] ?> / reorder at <?= (int) $product['reorder_level'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>

    <aside class="table-card">
        <div class="card-head">
            <h2>Most Wanted Restocks</h2>
        </div>
        <?php if ($mostWantedRestocks === []): ?>
            <p class="empty-copy">No out-of-stock products have restock requests.</p>
        <?php else: ?>
            <ul class="alert-list">
                <?php foreach ($mostWantedRestocks as $product): ?>
                    <li>
                        <strong><?= adminH($product['name']) ?></strong>
                        <span style="color: var(--cyan); font-weight: bold;"><i class="fas fa-bell"></i> <?= (int) $product['subscriber_count'] ?> subscribers</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
</div>

<section class="table-card">
    <div class="card-head">
        <h2>Recent Customer Orders</h2>
        <a class="button button-light button-small" href="admin-orders.php">Track orders</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($recentOrders === []): ?>
                <tr><td colspan="6">No order data available yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $order): ?>
                <?php $status = (string) $order['status']; ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= adminH($order['client_name'] ?: 'Unknown customer') ?></td>
                    <td><?= adminH(date('Y-m-d', strtotime((string) $order['created_at']))) ?></td>
                    <td>
                        <span class="status-badge <?= in_array($status, ['delivered', 'shipped'], true) ? 'is-good' : ($status === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                            <?= adminH(ucfirst($status)) ?>
                        </span>
                    </td>
                    <td><?= adminH(ucfirst((string) $order['payment_status'])) ?></td>
                    <td><?= adminMoney((float) $order['total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<!-- Flash Sales Management -->
<section class="table-card" id="flash-sales-section">
    <div class="card-head">
        <h2>⚡ Flash Sales (<?= $activeFlashCount ?> active)</h2>
    </div>

    <div style="padding: 20px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px; font-size: 1rem; color: var(--text);">Create Flash Sale</h3>
        <form id="flashSaleForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Product</label>
                <select id="fsProduct" required style="width:100%; padding:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
                    <option value="">Select product...</option>
                    <?php foreach ($allProducts as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" data-price="<?= (float) $p['price'] ?>">
                            <?= adminH($p['name']) ?> — <?= adminMoney((float) $p['price']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Sale Price (MAD)</label>
                <input type="number" id="fsSalePrice" step="0.01" min="1" required placeholder="e.g. 4999.90" style="width:100%; padding:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Starts At</label>
                <input type="datetime-local" id="fsStartsAt" required style="width:100%; padding:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Ends At</label>
                <input type="datetime-local" id="fsEndsAt" required style="width:100%; padding:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--muted); margin-bottom:4px;">Max Quantity (optional)</label>
                <input type="number" id="fsMaxQty" min="1" placeholder="Unlimited" style="width:100%; padding:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
            </div>
            <div>
                <button type="submit" class="button button-primary" style="width:100%; padding:10px;">⚡ Create Sale</button>
            </div>
        </form>
        <div id="fsMessage" style="margin-top: 10px; font-size: 0.85rem;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Original</th>
                <th>Sale Price</th>
                <th>Discount</th>
                <th>Stock</th>
                <th>Period</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($flashSales)): ?>
                <tr><td colspan="8">No flash sales yet. Create one above.</td></tr>
            <?php endif; ?>
            <?php foreach ($flashSales as $sale): ?>
                <?php
                    $nowDt = new DateTime();
                    $starts = new DateTime($sale['starts_at']);
                    $ends = new DateTime($sale['ends_at']);
                    $isActive = $nowDt >= $starts && $nowDt < $ends;
                    $isUpcoming = $nowDt < $starts;
                    $discount = round((1 - (float)$sale['sale_price'] / (float)$sale['original_price']) * 100);
                    $statusClass = $isActive ? 'is-good' : ($isUpcoming ? 'is-warn' : 'is-danger');
                    $statusText = $isActive ? 'Active' : ($isUpcoming ? 'Upcoming' : 'Expired');
                    $soldInfo = $sale['max_quantity']
                        ? ((int)$sale['sold_count'] . '/' . (int)$sale['max_quantity'])
                        : 'Unlimited';
                ?>
                <tr data-sale-id="<?= (int) $sale['id'] ?>">
                    <td>
                        <strong><?= adminH($sale['product_name']) ?></strong>
                        <small>#<?= (int) $sale['product_id'] ?></small>
                    </td>
                    <td><?= adminMoney((float) $sale['original_price']) ?></td>
                    <td style="color: #ff3d5a; font-weight: 700;"><?= adminMoney((float) $sale['sale_price']) ?></td>
                    <td><span style="color: #00e676; font-weight: 700;">-<?= $discount ?>%</span></td>
                    <td><?= $soldInfo ?></td>
                    <td>
                        <small><?= adminH($starts->format('M d, H:i')) ?></small><br>
                        <small>→ <?= adminH($ends->format('M d, H:i')) ?></small>
                    </td>
                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                    <td>
                        <button class="button button-light button-small fs-delete-btn" data-id="<?= (int) $sale['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
(function() {
    // Auto-fill dates
    var now = new Date();
    var later = new Date(now.getTime() + 3 * 86400000);
    function toLocal(d) {
        var pad = function(n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }
    var startInput = document.getElementById('fsStartsAt');
    var endInput = document.getElementById('fsEndsAt');
    if (startInput && !startInput.value) startInput.value = toLocal(now);
    if (endInput && !endInput.value) endInput.value = toLocal(later);

    // Auto-suggest sale price when product changes
    var prodSelect = document.getElementById('fsProduct');
    if (prodSelect) {
        prodSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            var price = parseFloat(opt.getAttribute('data-price') || 0);
            if (price > 0) {
                document.getElementById('fsSalePrice').value = (price * 0.80).toFixed(2);
            }
        });
    }

    // Create flash sale
    var form = document.getElementById('flashSaleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var msgEl = document.getElementById('fsMessage');
            var body = {
                action: 'create',
                product_id: parseInt(document.getElementById('fsProduct').value),
                sale_price: parseFloat(document.getElementById('fsSalePrice').value),
                starts_at: document.getElementById('fsStartsAt').value.replace('T', ' ') + ':00',
                ends_at: document.getElementById('fsEndsAt').value.replace('T', ' ') + ':00',
                max_quantity: document.getElementById('fsMaxQty').value ? parseInt(document.getElementById('fsMaxQty').value) : null
            };
            fetch('api/admin-flash-sales.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                msgEl.style.color = data.success ? '#00e676' : '#ff3d5a';
                msgEl.textContent = data.message || data.error;
                if (data.success) setTimeout(function() { location.reload(); }, 1000);
            })
            .catch(function(err) {
                msgEl.style.color = '#ff3d5a';
                msgEl.textContent = 'Network error: ' + err.message;
            });
        });
    }

    // Delete flash sale
    document.querySelectorAll('.fs-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this flash sale?')) return;
            var id = parseInt(this.getAttribute('data-id'));
            var row = this.closest('tr');
            fetch('api/admin-flash-sales.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && row) row.remove();
                else if (!data.success) alert(data.error);
            })
            .catch(function(err) { alert('Error: ' + err.message); });
        });
    });
})();
</script>



<?php adminPageEnd(); ?>
