<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
$customerId = (int) ($_GET['id'] ?? 0);

if ($customerId <= 0) {
    adminRedirect('admin-customers.php?error=' . urlencode('Invalid user.'));
}

$customer = adminFetchAll($pdo, '
    SELECT id_client, nom, email, telephone, adresse, date_naissance, moyen_paiement, created_at
    FROM Client
    WHERE id_client = ?
    LIMIT 1
', [$customerId])[0] ?? null;

if (!$customer) {
    adminRedirect('admin-customers.php?error=' . urlencode('User not found.'));
}

$hasOrders = adminTableExists($pdo, 'orders');
$hasOrderItems = adminTableExists($pdo, 'order_items');

$orders = $hasOrders
    ? adminFetchAll($pdo, '
        SELECT id, status, total, shipping_method, shipping_address, payment_method, payment_status, notes, created_at
        FROM orders
        WHERE client_id = ?
        ORDER BY created_at DESC, id DESC
    ', [$customerId])
    : [];

$orderItems = [];
if ($hasOrderItems && $orders !== []) {
    $orderIds = array_map(static fn($order) => (int) $order['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $items = adminFetchAll($pdo, '
        SELECT order_id, product_id, quantity, price_at_time, name_at_time
        FROM order_items
        WHERE order_id IN (' . $placeholders . ')
        ORDER BY id ASC
    ', $orderIds);

    foreach ($items as $item) {
        $orderItems[(int) $item['order_id']][] = $item;
    }
}

$stats = [
    'orders' => count($orders),
    'spent' => array_reduce($orders, static fn($carry, $order) => $carry + (float) $order['total'], 0.0),
    'paid' => count(array_filter($orders, static fn($order) => $order['payment_status'] === 'paid')),
    'pending' => count(array_filter($orders, static fn($order) => in_array($order['status'], ['pending', 'processing'], true))),
];

adminPageStart('Customer Details', 'customers');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Customer Commands</span>
        <h1><?= adminH($customer['nom']) ?></h1>
        <p class="section-copy">Review this customer profile and every command/order linked to the account.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="admin-customers.php">Customers</a>
        <a class="button button-light" href="admin-orders.php?customer=<?= urlencode((string) $customer['email']) ?>">Filter Orders</a>
        <form method="post" action="admin-customer-delete.php" onsubmit="return confirm('Delete this user and all related orders?');">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $customer['id_client'] ?>">
            <button class="button button-danger" type="submit">Delete User</button>
        </form>
    </div>
</section>

<div class="stats-grid">
    <article class="stat-card"><strong><?= $stats['orders'] ?></strong><span>Total commands</span></article>
    <article class="stat-card"><strong><?= adminMoney($stats['spent']) ?></strong><span>Total spent</span></article>
    <article class="stat-card"><strong><?= $stats['paid'] ?></strong><span>Paid commands</span></article>
    <article class="stat-card"><strong><?= $stats['pending'] ?></strong><span>Pending flow</span></article>
</div>

<div class="dashboard-grid">
    <section class="table-card">
        <div class="card-head">
            <h2>Customer Profile</h2>
        </div>
        <div class="detail-list clean-details">
            <div><span>Email</span><strong><?= adminH($customer['email']) ?></strong></div>
            <div><span>Phone</span><strong><?= adminH($customer['telephone'] ?: 'No phone') ?></strong></div>
            <div><span>Address</span><strong><?= adminH($customer['adresse'] ?: 'No address') ?></strong></div>
            <div><span>Birth date</span><strong><?= adminH($customer['date_naissance'] ?: 'Not set') ?></strong></div>
            <div><span>Payment</span><strong><?= adminH($customer['moyen_paiement'] ?: 'Not set') ?></strong></div>
            <div><span>Joined</span><strong><?= adminH(substr((string) $customer['created_at'], 0, 10)) ?></strong></div>
        </div>
    </section>

    <aside class="table-card">
        <div class="card-head">
            <h2>Quick Actions</h2>
        </div>
        <div class="quick-actions">
            <a class="button button-light" href="mailto:<?= adminH($customer['email']) ?>">Email User</a>
            <a class="button button-light" href="admin-orders.php?customer=<?= urlencode((string) $customer['email']) ?>">Open in Orders</a>
        </div>
    </aside>
</div>

<section class="table-card">
    <div class="card-head">
        <h2>Commands</h2>
    </div>

    <?php if ($orders === []): ?>
        <p class="empty-copy">This user has no commands yet.</p>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
                <?php
                    $orderStatus = (string) $order['status'];
                    $items = $orderItems[(int) $order['id']] ?? [];
                ?>
                <article class="order-card-admin">
                    <div class="order-card-head">
                        <div>
                            <strong>#<?= (int) $order['id'] ?></strong>
                            <small><?= adminH(date('Y-m-d H:i', strtotime((string) $order['created_at']))) ?></small>
                        </div>
                        <div class="order-card-meta">
                            <span class="status-badge <?= in_array($orderStatus, ['delivered', 'shipped'], true) ? 'is-good' : ($orderStatus === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                                <?= adminH(ucfirst($orderStatus)) ?>
                            </span>
                            <strong><?= adminMoney((float) $order['total']) ?></strong>
                        </div>
                    </div>

                    <div class="order-subgrid">
                        <span><small>Payment</small><?= adminH(ucfirst((string) $order['payment_status'])) ?></span>
                        <span><small>Method</small><?= adminH($order['payment_method'] ?: 'N/A') ?></span>
                        <span><small>Shipping</small><?= adminH($order['shipping_method'] ?: 'N/A') ?></span>
                    </div>

                    <?php if ($items === []): ?>
                        <p class="empty-copy">No command items stored for this order.</p>
                    <?php else: ?>
                        <div class="mini-items">
                            <?php foreach ($items as $item): ?>
                                <div>
                                    <strong><?= adminH($item['name_at_time'] ?: 'Product #' . (int) $item['product_id']) ?></strong>
                                    <span>x<?= (int) $item['quantity'] ?> - <?= adminMoney((float) $item['price_at_time']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php adminPageEnd(); ?>
