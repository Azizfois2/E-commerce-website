<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$hasOrders = adminTableExists($pdo, 'orders');
$hasClients = adminTableExists($pdo, 'Client');
$hasOrderItems = adminTableExists($pdo, 'order_items');
$hasProducts = adminTableExists($pdo, 'products');

$date = trim((string) ($_GET['date'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$payment = trim((string) ($_GET['payment'] ?? ''));
$customer = trim((string) ($_GET['customer'] ?? ''));
$productFilter = (int) ($_GET['product_id'] ?? 0);

$products = $hasProducts
    ? adminFetchAll($pdo, 'SELECT id, name FROM products ORDER BY name ASC')
    : [];

$stats = [
    'orders' => $hasOrders ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM orders') : 0,
    'paid' => $hasOrders ? (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'") : 0,
    'pending' => $hasOrders ? (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')") : 0,
    'revenue' => $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(total), 0) FROM orders') : 0.0,
];

$productWhere = ($hasOrderItems && $productFilter > 0)
    ? ' AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.product_id = :product_id_filter)'
    : '';

$ordersSql = '
        SELECT o.id, o.status, o.assembly_status, o.total, o.payment_status, o.payment_method, o.created_at, c.nom AS client_name, c.email AS client_email
        FROM orders o
        LEFT JOIN Client c ON c.id_client = o.client_id
        WHERE (:date_empty = 1 OR DATE(o.created_at) = :date_filter)
          AND (:status_empty = 1 OR o.status = :status_filter)
          AND (:payment_empty = 1 OR o.payment_status = :payment_filter)
          AND (:customer_empty = 1 OR c.nom LIKE :customer_name OR c.email LIKE :customer_email)
          ' . $productWhere . '
        ORDER BY o.created_at DESC, o.id DESC
    ';

$orderParams = [
    'date_empty' => $date === '' ? 1 : 0,
    'date_filter' => $date,
    'status_empty' => $status === '' ? 1 : 0,
    'status_filter' => $status,
    'payment_empty' => $payment === '' ? 1 : 0,
    'payment_filter' => $payment,
    'customer_empty' => $customer === '' ? 1 : 0,
    'customer_name' => '%' . $customer . '%',
    'customer_email' => '%' . $customer . '%',
];

if ($hasOrderItems && $productFilter > 0) {
    $orderParams['product_id_filter'] = $productFilter;
}

$orders = ($hasOrders && $hasClients)
    ? adminFetchAll($pdo, $ordersSql, $orderParams)
    : [];
$orderIds = array_map(static fn($order): int => (int) $order['id'], $orders);
$timelineByOrder = [];
if ($orderIds !== [] && adminTableExists($pdo, 'order_status_history')) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $timelineRows = adminFetchAll($pdo, "
        SELECT order_id, old_status, new_status, changed_by, notes, changed_at
        FROM order_status_history
        WHERE order_id IN ($placeholders)
        ORDER BY changed_at DESC, id DESC
    ", $orderIds);
    foreach ($timelineRows as $row) {
        $timelineByOrder[(int) $row['order_id']][] = $row;
    }
}

adminPageStart('Order Tracking', 'orders');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Order Tracking</span>
        <h1>Customer Orders</h1>
        <p class="section-copy">Filter customer orders by date, status, payment state, and customer identity.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-products.php">Products</a>
        <a class="button button-light" href="admin-stock.php">Stock</a>
        <a class="button button-light" href="api/export-orders.php?format=csv"><i class="fas fa-file-csv"></i> CSV</a>
        <a class="button button-light" href="api/export-orders.php?format=r"><i class="fas fa-code"></i> RData Script</a>
    </div>
</section>

<div class="stats-grid">
    <article class="stat-card"><strong><?= $stats['orders'] ?></strong><span>Total orders</span></article>
    <article class="stat-card"><strong><?= $stats['paid'] ?></strong><span>Paid orders</span></article>
    <article class="stat-card"><strong><?= $stats['pending'] ?></strong><span>Pending flow</span></article>
    <article class="stat-card"><strong><?= adminMoney($stats['revenue']) ?></strong><span>Total revenue</span></article>
</div>

<section class="table-card">
    <div class="card-head">
        <h2>Search Orders</h2>
    </div>
    <form class="filter-bar" method="get">
        <label>
            Date
            <input type="date" name="date" value="<?= adminH($date) ?>">
        </label>
        <label>
            Status
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $option): ?>
                    <option value="<?= adminH($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                        <?= adminH(ucfirst($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Payment
            <select name="payment">
                <option value="">All payments</option>
                <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $option): ?>
                    <option value="<?= adminH($option) ?>" <?= $payment === $option ? 'selected' : '' ?>>
                        <?= adminH(ucfirst($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Product
            <select name="product_id">
                <option value="0">All products</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>" <?= $productFilter === (int) $product['id'] ? 'selected' : '' ?>><?= adminH($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Customer
            <input type="text" name="customer" value="<?= adminH($customer) ?>" placeholder="Name or email">
        </label>
        <button class="button button-primary" type="submit">Filter</button>
    </form>

    <table id="ordersTable">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Assembly</th>
                <th>Payment</th>
                <th>Method</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders === []): ?>
                <tr>
                    <td colspan="8">No orders match the current filters.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($orders as $order): ?>
                <?php 
                $orderStatus = (string) $order['status']; 
                $assemblyStatus = (string) $order['assembly_status'];
                ?>
                <tr id="order-row-<?= (int) $order['id'] ?>">
                    <td>#<?= (int) $order['id'] ?></td>
                    <td>
                        <strong><?= adminH($order['client_name'] ?: 'Unknown customer') ?></strong>
                        <small><?= adminH($order['client_email'] ?: '') ?></small>
                    </td>
                    <td><?= adminH(date('Y-m-d', strtotime((string) $order['created_at']))) ?></td>
                    <td class="status-cell-<?= (int) $order['id'] ?>">
                        <span
                            class="status-badge <?= in_array($orderStatus, ['delivered', 'shipped'], true) ? 'is-good' : ($orderStatus === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                            <?= adminH(ucfirst($orderStatus)) ?>
                        </span>
                    </td>
                    <td class="assembly-cell-<?= (int) $order['id'] ?>">
                        <span class="status-badge <?= $assemblyStatus === 'not_applicable' ? 'is-warn' : ($assemblyStatus === 'ready' ? 'is-good' : 'is-info') ?>">
                            <?= adminH(ucwords(str_replace('_', ' ', $assemblyStatus))) ?>
                        </span>
                    </td>
                    <td><?= adminH(ucfirst((string) $order['payment_status'])) ?></td>
                    <td><?= adminH($order['payment_method'] ?: 'N/A') ?></td>
                    <td><?= adminMoney((float) $order['total']) ?></td>
                    <td class="order-actions">
                        <div class="action-group">
                            <select class="status-select" data-order-id="<?= (int) $order['id'] ?>">
                                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $opt): ?>
                                    <option value="<?= adminH($opt) ?>" <?= $orderStatus === $opt ? 'selected' : '' ?>>
                                        <?= adminH(ucfirst($opt)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button button-primary button-small btn-set-status"
                                data-order-id="<?= (int) $order['id'] ?>" title="Apply status">
                                <i class="fas fa-check"></i>
                            </button>
                            
                            <select class="assembly-select" data-order-id="<?= (int) $order['id'] ?>">
                                <?php foreach (['not_applicable', 'gathering_parts', 'building', 'testing', 'qc_passed', 'ready'] as $opt): ?>
                                    <option value="<?= adminH($opt) ?>" <?= $assemblyStatus === $opt ? 'selected' : '' ?>>
                                        <?= adminH(ucwords(str_replace('_', ' ', $opt))) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button button-info button-small btn-set-assembly"
                                data-order-id="<?= (int) $order['id'] ?>" title="Apply assembly status">
                                <i class="fas fa-wrench"></i>
                            </button>

                            <button class="button button-danger button-small btn-suppress"
                                data-order-id="<?= (int) $order['id'] ?>" title="Delete order">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="order-timeline-row" id="timeline-row-<?= (int) $order['id'] ?>">
                    <td colspan="8">
                        <details class="order-timeline">
                            <summary><i class="fas fa-timeline"></i> Timeline and internal notes</summary>
                            <div class="timeline-grid">
                                <div class="timeline-events">
                                    <?php foreach (($timelineByOrder[(int) $order['id']] ?? []) as $event): ?>
                                        <div class="timeline-event">
                                            <strong><?= adminH(ucfirst((string) $event['new_status'])) ?></strong>
                                            <span><?= adminH(date('M j, Y H:i', strtotime((string) $event['changed_at']))) ?> by <?= adminH($event['changed_by']) ?></span>
                                            <?php if (!empty($event['notes'])): ?><p><?= adminH($event['notes']) ?></p><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($timelineByOrder[(int) $order['id']])): ?>
                                        <p class="empty-copy">No timeline events yet.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-note-form">
                                    <textarea class="order-note-input" data-order-id="<?= (int) $order['id'] ?>" placeholder="Add internal note: packed, delayed, refunded, customer contacted..."></textarea>
                                    <button class="button button-light button-small btn-add-note" data-order-id="<?= (int) $order['id'] ?>" type="button">Add Note</button>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
    (function () {
        'use strict';

        async function adminApi(payload) {
            const res = await fetch('api/admin-update-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });
            return res.json().catch(() => ({}));
        }

        function statusBadgeClass(s) {
            if (s === 'delivered' || s === 'shipped') return 'is-good';
            if (s === 'cancelled') return 'is-danger';
            return 'is-warn';
        }

        document.querySelectorAll('.btn-set-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const select = document.querySelector(`.status-select[data-order-id="${id}"]`);
                const status = select ? select.value : '';
                if (!status) return;

                btn.disabled = true;
                const r = await adminApi({ action: 'set_status', order_id: id, status });
                btn.disabled = false;

                if (r.success) {
                    const cell = document.querySelector(`.status-cell-${id}`);
                    if (cell) {
                        cell.innerHTML = `<span class="status-badge ${statusBadgeClass(status)}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                    }
                } else {
                    alert(r.error || 'Failed to update status.');
                }
            });
        });

        document.querySelectorAll('.btn-set-assembly').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const select = document.querySelector(`.assembly-select[data-order-id="${id}"]`);
                const status = select ? select.value : '';
                if (!status) return;

                btn.disabled = true;
                const r = await adminApi({ action: 'set_assembly_status', order_id: id, assembly_status: status });
                btn.disabled = false;

                if (r.success) {
                    const cell = document.querySelector(`.assembly-cell-${id}`);
                    if (cell) {
                        const badgeClass = status === 'not_applicable' ? 'is-warn' : (status === 'ready' ? 'is-good' : 'is-info');
                        const statusText = status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                        cell.innerHTML = `<span class="status-badge ${badgeClass}">${statusText}</span>`;
                    }
                } else {
                    alert(r.error || 'Failed to update assembly status.');
                }
            });
        });

        // ── Suppress (delete) ────────────────────────────────────────────────
        document.querySelectorAll('.btn-suppress').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                if (!confirm(`Permanently delete order #${id}? This cannot be undone.`)) return;

                btn.disabled = true;
                const r = await adminApi({ action: 'suppress', order_id: id });

                if (r.success) {
                    const row = document.getElementById(`order-row-${id}`);
                    if (row) row.remove();
                } else {
                    btn.disabled = false;
                    alert(r.error || 'Failed to delete order.');
                }
            });
        });

        document.querySelectorAll('.btn-add-note').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const input = document.querySelector(`.order-note-input[data-order-id="${id}"]`);
                const note = input ? input.value.trim() : '';
                if (!note) return;

                btn.disabled = true;
                const r = await adminApi({ action: 'add_note', order_id: id, note });
                btn.disabled = false;
                if (r.success) {
                    location.reload();
                } else {
                    alert(r.error || 'Failed to add note.');
                }
            });
        });
    })();
</script>

<style>
.order-timeline-row td { padding-top: 0; border-top: 0; }
.order-timeline {
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
}
.order-timeline summary {
    cursor: pointer;
    color: var(--cyan);
    font-weight: 800;
}
.timeline-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 0.7fr);
    gap: 16px;
    margin-top: 14px;
}
.timeline-events { display: grid; gap: 10px; }
.timeline-event {
    border-left: 2px solid var(--cyan);
    padding-left: 12px;
}
.timeline-event strong,
.timeline-event span,
.timeline-event p { display: block; }
.timeline-event span { color: var(--muted); font-size: 0.76rem; }
.timeline-event p { margin: 6px 0 0; color: var(--text-dim); }
.timeline-note-form { display: grid; gap: 10px; align-content: start; }
.timeline-note-form textarea {
    min-height: 96px;
    width: 100%;
    resize: vertical;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card-bg);
    color: var(--text);
    padding: 12px;
}
@media (max-width: 860px) {
    .timeline-grid { grid-template-columns: 1fr; }
}
</style>

<?php adminPageEnd(); ?>
