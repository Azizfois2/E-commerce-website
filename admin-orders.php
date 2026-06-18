<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$hasOrders = adminTableExists($pdo, 'orders');
$hasClients = adminTableExists($pdo, 'Client');
$hasOrderItems = adminTableExists($pdo, 'order_items');
$hasProducts = adminTableExists($pdo, 'products');

function adminOrderMethodLabel(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return adminPhrase('N/A');
    }
    return adminStatusLabel($value);
}

function adminOrderAddressLines(?string $address): array
{
    $lines = preg_split('/\R+/', trim((string) $address)) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== ''));
}

function adminOrderPickupCode(?string $notes): string
{
    if (preg_match('/Verification code:\s*(PICKUP-[A-Z0-9]{4}-[A-Z0-9]{4})/i', (string) $notes, $matches)) {
        return strtoupper($matches[1]);
    }
    return '';
}

function adminTranslateDetailLine(string $line): string
{
    $line = trim($line);
    if ($line === '') {
        return $line;
    }

    // Exact match first
    $exact = [
        'Store Pickup' => 'Store Pickup',
        'Pickup authorization' => 'Pickup authorization',
    ];
    if (isset($exact[$line])) {
        return adminPhrase($exact[$line]);
    }

    // Pattern: "Label: value" where label has a known translation with parameter
    if (preg_match('/^(Store|Address|Hours|Phone|Verification code|Pickup store|Pickup address|Pickup hours|Pickup phone|Billing):\s*(.*)$/i', $line, $m)) {
        $label = ucfirst(strtolower($m[1]));
        $value = $m[2];

        $keyMap = [
            'Store' => 'Store: {name}',
            'Address' => 'Address: {address}',
            'Hours' => 'Hours: {hours}',
            'Phone' => 'Phone: {phone}',
            'Verification code' => 'Verification code: {code}',
            'Pickup store' => 'Pickup store: {name}',
            'Pickup address' => 'Pickup address: {address}',
            'Pickup hours' => 'Pickup hours: {hours}',
            'Pickup phone' => 'Pickup phone: {phone}',
            'Billing' => 'Billing',
        ];

        $key = $keyMap[$label] ?? null;
        if ($key !== null) {
            $params = [];
            if (str_contains($key, '{name}'))    $params['name'] = $value;
            if (str_contains($key, '{address}')) $params['address'] = $value;
            if (str_contains($key, '{hours}'))   $params['hours'] = $value;
            if (str_contains($key, '{phone}'))   $params['phone'] = $value;
            if (str_contains($key, '{code}'))    $params['code'] = $value;
            return adminPhrase($key, $params);
        }
    }

    return $line;
}

function adminOrderProductSummary(array $items): string
{
    if ($items === []) {
        return adminPhrase('No product lines');
    }

    $first = trim((string) ($items[0]['name_at_time'] ?? '')) ?: adminPhrase('Product #{id}', ['id' => (int) ($items[0]['product_id'] ?? 0)]);
    $extra = count($items) - 1;
    return $extra > 0 ? $first . ' +' . $extra : $first;
}

$date = trim((string) ($_GET['date'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$payment = trim((string) ($_GET['payment'] ?? ''));
$customer = trim((string) ($_GET['customer'] ?? ''));
$productFilter = (int) ($_GET['product_id'] ?? 0);

// Cache products list
$products = $hasProducts
    ? adminCachedFetch($pdo, 'products_dropdown', function() use ($pdo) {
        return adminFetchAll($pdo, 'SELECT id, name FROM products ORDER BY name ASC');
    }, 600)
    : [];

// Cache order statistics
$stats = adminGetOptimizedStats($pdo, 'order_stats_' . date('YmdH'), [
    'orders' => $hasOrders ? 'SELECT COUNT(*) FROM orders' : fn() => 0,
    'paid' => $hasOrders ? "SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'" : fn() => 0,
    'pending' => $hasOrders ? "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')" : fn() => 0,
    'pickup' => $hasOrders ? "SELECT COUNT(*) FROM orders WHERE shipping_method = 'pickup'" : fn() => 0,
    'revenue' => $hasOrders ? 'SELECT COALESCE(SUM(total), 0) FROM orders' : fn() => 0.0,
], 300);

$productWhere = ($hasOrderItems && $productFilter > 0)
    ? ' AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.product_id = :product_id_filter)'
    : '';

$ordersSql = '
        SELECT o.id, o.status, o.assembly_status, o.total, o.payment_status, o.payment_method,
               o.shipping_method, o.shipping_address, o.billing_address, o.notes, o.estimated_delivery,
               o.created_at, c.nom AS client_name, c.email AS client_email, c.telephone AS client_phone
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

// Use pagination for orders
$result = ($hasOrders && $hasClients)
    ? adminPaginatedQuery($pdo, $ordersSql, $orderParams, 25)
    : ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total' => 0]];

$orders = $result['data'];
$pagination = $result['pagination'];

$orderIds = array_map(static fn($order): int => (int) $order['id'], $orders);
$itemsByOrder = [];
if ($orderIds !== [] && $hasOrderItems) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemRows = adminFetchAll($pdo, "
        SELECT order_id, product_id, quantity, price_at_time, name_at_time
        FROM order_items
        WHERE order_id IN ($placeholders)
        ORDER BY order_id ASC, id ASC
    ", $orderIds);
    foreach ($itemRows as $row) {
        $itemsByOrder[(int) $row['order_id']][] = $row;
    }
}

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

// AJAX request for infinite scroll
if (!empty($_GET['ajax']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    if (empty($orders)) {
        echo json_encode(['html' => '', 'hasMore' => false]);
        exit;
    }
    
    ob_start();
    foreach ($orders as $order):
        $orderStatus = (string) $order['status'];
        $orderId = (int) $order['id'];
        $createdAt = strtotime((string) $order['created_at']);
        $isPickup = (string) ($order['shipping_method'] ?? '') === 'pickup';
        $orderItems = $itemsByOrder[$orderId] ?? [];
        $productSummary = adminOrderProductSummary($orderItems);
        $shippingMethod = adminOrderMethodLabel($order['shipping_method'] ?? '');
        $paymentMethod = adminOrderMethodLabel($order['payment_method'] ?? '');
    ?>
        <tr>
            <td class="checkbox-cell">
                <input type="checkbox" class="order-checkbox" value="<?= $orderId ?>">
            </td>
            <td class="order-id-cell">
                <strong>#<?= $orderId ?></strong>
                <small><?= adminH($createdAt ? adminFormatDate($createdAt, 'datetime_full') : '') ?></small>
            </td>
            <td class="order-customer-cell">
                <strong><?= adminH($order['client_name'] ?: adminPhrase('Unknown customer')) ?></strong>
                <small><?= adminH($order['client_email'] ?: adminPhrase('No email')) ?></small>
            </td>
            <td><?= adminH($productSummary) ?></td>
            <td><?= adminH($shippingMethod) ?></td>
            <td>
                <span class="status-badge <?= in_array($orderStatus, ['delivered', 'shipped'], true) ? 'is-good' : ($orderStatus === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                    <?= adminH(adminStatusLabel($orderStatus)) ?>
                </span>
            </td>
            <td><?= adminMoney((float) $order['total']) ?></td>
            <td><button class="button button-light button-small">Details</button></td>
        </tr>
    <?php
    endforeach;
    $html = ob_get_clean();
    
    echo json_encode([
        'html' => $html,
        'hasMore' => $pagination['has_next']
    ]);
    exit;
}

adminPageStart('Order Tracking', 'orders');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Order Tracking')) ?></span>
        <h1><?= adminH(adminPhrase('Customer Orders')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Filter customer orders by date, status, payment state, and customer identity.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><?= adminH(adminPhrase('Dashboard')) ?></a>
        <a class="button button-light" href="admin-products.php"><?= adminH(adminPhrase('Products')) ?></a>
        <a class="button button-light" href="admin-stock.php"><?= adminH(adminPhrase('Stock')) ?></a>
        <a class="button button-light" href="api/export-orders.php?format=csv"><i class="fas fa-file-csv"></i> CSV</a>
        <a class="button button-light" href="api/export-orders.php?format=r"><i class="fas fa-code"></i> RData Script</a>
    </div>
</section>

<div class="stats-grid">
    <article class="stat-card"><strong><?= $stats['orders'] ?></strong><span><?= adminH(adminPhrase('Total Orders')) ?></span></article>
    <article class="stat-card"><strong><?= $stats['paid'] ?></strong><span><?= adminH(adminPhrase('Paid orders')) ?></span></article>
    <article class="stat-card"><strong><?= $stats['pending'] ?></strong><span><?= adminH(adminPhrase('Pending flow')) ?></span></article>
    <article class="stat-card"><strong><?= $stats['pickup'] ?></strong><span><?= adminH(adminPhrase('Pickup queue')) ?></span></article>
</div>

<section class="table-card">
    <div class="card-head">
        <h2><?= adminH(adminPhrase('Search Orders')) ?></h2>
    </div>
    <form class="filter-bar" method="get">
        <label>
            <?= adminH(adminPhrase('Date')) ?>
            <input type="date" name="date" value="<?= adminH($date) ?>">
        </label>
        <label>
            <?= adminH(adminPhrase('Status')) ?>
            <select name="status">
                <option value=""><?= adminH(adminPhrase('All statuses')) ?></option>
                <?php foreach (['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'] as $option): ?>
                    <option value="<?= adminH($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                        <?= adminH(adminStatusLabel($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= adminH(adminPhrase('Payment')) ?>
            <select name="payment">
                <option value=""><?= adminH(adminPhrase('All payments')) ?></option>
                <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $option): ?>
                    <option value="<?= adminH($option) ?>" <?= $payment === $option ? 'selected' : '' ?>>
                        <?= adminH(adminPaymentStatusLabel($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= adminH(adminPhrase('Product')) ?>
            <select name="product_id">
                <option value="0"><?= adminH(adminPhrase('All products')) ?></option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>" <?= $productFilter === (int) $product['id'] ? 'selected' : '' ?>><?= adminH($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= adminH(adminPhrase('Customer')) ?>
            <input type="text" name="customer" value="<?= adminH($customer) ?>" placeholder="<?= adminH(adminPhrase('Name or email')) ?>">
        </label>
        <button class="button button-primary" type="submit"><?= adminH(adminPhrase('Filter')) ?></button>
    </form>

    <div class="orders-workbench-meta">
        <span><?= adminH(adminPhrase('{count} orders shown', ['count' => count($orders)])) ?></span>
        <span><?= adminH(adminPhrase('Total revenue {amount}', ['amount' => adminMoney($stats['revenue'])])) ?></span>
    </div>

    <div class="bulk-actions-bar" id="bulkActionsBar" hidden>
        <span class="bulk-count"><strong id="bulkCount">0</strong> <?= adminH(adminPhrase('selected')) ?></span>
        <div class="bulk-controls">
            <select id="bulkStatusSelect" aria-label="Bulk status">
                <option value=""><?= adminH(adminPhrase('Change status to...')) ?></option>
                <?php foreach (['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'] as $opt): ?>
                    <option value="<?= adminH($opt) ?>"><?= adminH(adminStatusLabel($opt)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary button-small" type="button" id="btnBulkStatus">
                <i class="fas fa-check"></i> <?= adminH(adminPhrase('Apply')) ?>
            </button>
            <button class="button button-danger button-small" type="button" id="btnBulkDelete">
                <i class="fas fa-trash"></i> <?= adminH(adminPhrase('Delete')) ?>
            </button>
            <button class="button button-light button-small" type="button" id="btnBulkClear"><?= adminH(adminPhrase('Clear')) ?></button>
        </div>
    </div>

    <table id="ordersTable" class="orders-workbench-table">
        <thead>
            <tr>
                <th class="checkbox-cell">
                    <input type="checkbox" id="selectAllOrders" aria-label="Select all orders">
                </th>
                <th><?= adminH(adminPhrase('ORDER')) ?></th>
                <th><?= adminH(adminPhrase('Customer')) ?></th>
                <th><?= adminH(adminPhrase('Items')) ?></th>
                <th><?= adminH(adminPhrase('Fulfillment')) ?></th>
                <th><?= adminH(adminPhrase('State')) ?></th>
                <th><?= adminH(adminPhrase('Total')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders === []): ?>
                <tr>
                    <td colspan="8"><?= adminH(adminPhrase('No orders match the current filters.')) ?></td>
                </tr>
            <?php endif; ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $orderStatus = (string) $order['status'];
                $assemblyStatus = (string) ($order['assembly_status'] ?: 'not_applicable');
                $orderItems = $itemsByOrder[(int) $order['id']] ?? [];
                $shippingLines = adminOrderAddressLines($order['shipping_address'] ?? '');
                $billingLines = adminOrderAddressLines($order['billing_address'] ?? '');
                $shippingMethod = adminOrderMethodLabel($order['shipping_method'] ?? '');
                $paymentMethod = adminOrderMethodLabel($order['payment_method'] ?? '');
                $estimatedDelivery = trim((string) ($order['estimated_delivery'] ?? ''));
                $pickupCode = adminOrderPickupCode($order['notes'] ?? '');
                $orderId = (int) $order['id'];
                $createdAt = strtotime((string) $order['created_at']);
                $isPickup = (string) ($order['shipping_method'] ?? '') === 'pickup';
                $productSummary = adminOrderProductSummary($orderItems);
                ?>
                <tr id="order-row-<?= $orderId ?>" class="order-summary-row">
                    <td class="checkbox-cell">
                        <input type="checkbox" class="order-checkbox" value="<?= $orderId ?>" aria-label="Select order #<?= $orderId ?>">
                    </td>
                    <td class="order-id-cell">
                        <strong>#<?= $orderId ?></strong>
                        <small><?= adminH($createdAt ? adminFormatDate($createdAt, 'datetime_full') : '') ?></small>
                    </td>
                    <td class="order-customer-cell">
                        <strong><?= adminH($order['client_name'] ?: adminPhrase('Unknown customer')) ?></strong>
                        <small><?= adminH($order['client_email'] ?: adminPhrase('No email')) ?></small>
                        <small><?= adminH($order['client_phone'] ?: adminPhrase('No phone')) ?></small>
                    </td>
                    <td class="order-items-summary">
                        <strong><?= adminH($productSummary) ?></strong>
                        <small><?= adminH(adminPhrase(count($orderItems) === 1 ? '{count} line' : '{count} lines', ['count' => count($orderItems)])) ?></small>
                    </td>
                    <td class="fulfillment-summary">
                        <strong><i class="fas <?= $isPickup ? 'fa-store' : 'fa-truck' ?>"></i> <?= adminH($shippingMethod) ?></strong>
                        <?php if ($pickupCode !== ''): ?>
                            <code><?= adminH($pickupCode) ?></code>
                        <?php elseif ($estimatedDelivery !== ''): ?>
                            <small><?= adminH(adminPhrase('ETA {date}', ['date' => adminFormatDate($estimatedDelivery, 'date_short')])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="order-state-cell status-cell-<?= $orderId ?>">
                        <span class="status-badge <?= in_array($orderStatus, ['delivered', 'shipped', 'out_for_delivery'], true) ? 'is-good' : ($orderStatus === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                            <?= adminH(adminStatusLabel($orderStatus)) ?>
                        </span>
                        <small><?= adminH(adminPhrase('{payment} via {method}', ['payment' => adminPaymentStatusLabel($order['payment_status']), 'method' => $paymentMethod])) ?></small>
                    </td>
                    <td><?= adminMoney((float) $order['total']) ?></td>
                    <td class="order-expand-cell">
                        <button class="button button-light button-small btn-toggle-order" type="button" data-order-id="<?= $orderId ?>" aria-expanded="false" aria-controls="order-detail-<?= $orderId ?>">
                            <i class="fas fa-chevron-down"></i> <?= adminH(adminPhrase('Details')) ?>
                        </button>
                    </td>
                </tr>
                <tr class="order-detail-row" id="order-detail-<?= $orderId ?>" hidden>
                    <td colspan="8">
                        <div class="order-detail-panel">
                            <section class="order-detail-section">
                                <h3><?= adminH(adminPhrase('Products')) ?></h3>
                                <?php if ($orderItems === []): ?>
                                    <p class="empty-copy"><?= adminH(adminPhrase('No product lines found.')) ?></p>
                                <?php else: ?>
                                    <div class="order-products-list" aria-label="Products in order #<?= $orderId ?>">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php
                                            $itemName = trim((string) ($item['name_at_time'] ?? '')) ?: adminPhrase('Product #{id}', ['id' => (int) ($item['product_id'] ?? 0)]);
                                            $quantity = max(1, (int) ($item['quantity'] ?? 1));
                                            $lineTotal = $quantity * (float) ($item['price_at_time'] ?? 0);
                                            ?>
                                            <div class="order-product-line">
                                                <span class="order-product-qty"><?= $quantity ?>x</span>
                                                <span class="order-product-name"><?= adminH($itemName) ?></span>
                                                <span class="order-product-price"><?= adminH(adminMoney($lineTotal)) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <section class="order-detail-section">
                                <h3><?= adminH(adminPhrase('Fulfillment')) ?></h3>
                                <div class="detail-list">
                                    <strong><?= adminH(adminPhrase('Method')) ?></strong>
                                    <p><?= adminH($shippingMethod) ?></p>
                                    <?php if ($pickupCode !== ''): ?>
                                        <strong><?= adminH(adminPhrase('Pickup code')) ?></strong>
                                        <code class="pickup-code-pill"><?= adminH($pickupCode) ?></code>
                                    <?php endif; ?>
                                    <strong><?= adminH(adminPhrase('Address')) ?></strong>
                                    <?php if ($shippingLines === []): ?>
                                        <p class="empty-copy"><?= adminH(adminPhrase('No shipping address saved.')) ?></p>
                                    <?php else: ?>
                                        <?php foreach ($shippingLines as $line): ?>
                                            <p><?= adminH(adminTranslateDetailLine($line)) ?></p>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($billingLines !== [] && implode("\n", $billingLines) !== implode("\n", $shippingLines)): ?>
                                        <strong><?= adminH(adminPhrase('Billing')) ?></strong>
                                        <?php foreach ($billingLines as $line): ?>
                                            <p><?= adminH($line) ?></p>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($estimatedDelivery !== ''): ?>
                                        <strong><?= adminH(adminPhrase('Estimated delivery')) ?></strong>
                                        <p><?= adminH(adminFormatDate($estimatedDelivery)) ?></p>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($order['notes'] ?? '')) !== ''): ?>
                                        <strong><?= adminH(adminPhrase('Checkout notes')) ?></strong>
                                        <?php
                                        $noteLines = preg_split('/\R+/', trim((string) $order['notes']));
                                        $translatedNotes = array_map('adminTranslateDetailLine', $noteLines);
                                        ?>
                                        <p><?= nl2br(adminH(implode("\n", $translatedNotes))) ?></p>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <section class="order-detail-section order-ops-section">
                                <h3><?= adminH(adminPhrase('Operations')) ?></h3>
                                <div class="action-group">
                                    <div class="action-row">
                                        <span class="action-label"><?= adminH(adminPhrase('Order')) ?></span>
                                        <select class="status-select" data-order-id="<?= $orderId ?>" aria-label="Order status for #<?= $orderId ?>">
                                            <?php foreach (['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'] as $opt): ?>
                                                <option value="<?= adminH($opt) ?>" <?= $orderStatus === $opt ? 'selected' : '' ?>>
                                                    <?= adminH(adminStatusLabel($opt)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button-primary button-small btn-set-status" type="button"
                                            data-order-id="<?= $orderId ?>" title="<?= adminH(adminPhrase('Apply order status')) ?>" aria-label="<?= adminH(adminPhrase('Apply order status')) ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>

                                    <div class="action-row">
                                        <span class="action-label"><?= adminH(adminPhrase('Assembly')) ?></span>
                                        <select class="assembly-select" data-order-id="<?= $orderId ?>" aria-label="Assembly status for #<?= $orderId ?>">
                                            <?php foreach (['not_applicable', 'gathering_parts', 'building', 'testing', 'qc_passed', 'ready'] as $opt): ?>
                                                <option value="<?= adminH($opt) ?>" <?= $assemblyStatus === $opt ? 'selected' : '' ?>>
                                                    <?= adminH($opt === 'not_applicable' ? adminPhrase('Not Applicable') : adminStatusLabel($opt)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button-light button-small btn-set-assembly" type="button"
                                            data-order-id="<?= $orderId ?>" title="<?= adminH(adminPhrase('Apply assembly status')) ?>" aria-label="<?= adminH(adminPhrase('Apply assembly status')) ?>">
                                            <i class="fas fa-wrench"></i>
                                        </button>
                                    </div>

                                    <button class="button button-danger button-small btn-suppress" type="button"
                                        data-order-id="<?= $orderId ?>" title="<?= adminH(adminPhrase('Delete order')) ?>">
                                        <i class="fas fa-trash"></i> <?= adminH(adminPhrase('Delete order')) ?>
                                    </button>
                                </div>
                            </section>

                            <section class="order-detail-section">
                                <h3><?= adminH(adminPhrase('Timeline')) ?></h3>
                                <div class="timeline-events">
                                    <?php foreach (($timelineByOrder[$orderId] ?? []) as $event): ?>
                                        <div class="timeline-event">
                                            <strong><?= adminH(adminStatusLabel((string) $event['new_status'])) ?></strong>
                                            <span><?= adminH(adminPhrase('{date} by {actor}', ['date' => adminFormatDate($event['changed_at'], 'datetime_full'), 'actor' => adminPhrase(ucfirst(strtolower($event['changed_by'] ?: 'System')))])) ?></span>
                                            <?php if (!empty($event['notes'])): ?><p><?= adminH($event['notes']) ?></p><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($timelineByOrder[$orderId])): ?>
                                        <p class="empty-copy"><?= adminH(adminPhrase('No timeline events yet.')) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-note-form">
                                    <textarea class="order-note-input" data-order-id="<?= $orderId ?>" placeholder="<?= adminH(adminPhrase('Add internal note: packed, delayed, refunded, customer contacted...')) ?>"></textarea>
                                    <button class="button button-light button-small btn-add-note" data-order-id="<?= $orderId ?>" type="button"><?= adminH(adminPhrase('Add Note')) ?></button>
                                </div>
                            </section>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?= adminRenderPagination($pagination) ?>
</section>

<script>
    (function () {
        'use strict';

        // Translation strings
        const i18n = {
            hide: <?= i18n_script_json(adminPhrase('Hide')) ?>,
            details: <?= i18n_script_json(adminPhrase('Details')) ?>,
            currency: <?= i18n_script_json(i18n_current_locale() === 'ar' ? 'د.م.' : 'DH') ?>
        };

        async function adminApi(payload) {
            try {
                const res = await fetch('api/admin-update-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));
                return res.ok ? data : { success: false, error: data.error || <?= i18n_script_json(adminPhrase('Request failed.')) ?> };
            } catch (error) {
                return { success: false, error: <?= i18n_script_json(adminPhrase('Network error. Please try again.')) ?> };
            }
        }

        function statusBadgeClass(s) {
            if (s === 'delivered' || s === 'shipped' || s === 'out_for_delivery') return 'is-good';
            if (s === 'cancelled') return 'is-danger';
            return 'is-warn';
        }

        function formatStatus(s) {
            return String(s || '').split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        function notify(message, type) {
            if (window.showToast) {
                window.showToast(message, type);
            }
        }

        document.querySelectorAll('.btn-toggle-order').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const detailRow = document.getElementById(`order-detail-${id}`);
                if (!detailRow) return;

                const isOpen = detailRow.hasAttribute('hidden');
                detailRow.toggleAttribute('hidden', !isOpen);
                btn.setAttribute('aria-expanded', String(isOpen));
                btn.classList.toggle('is-open', isOpen);
                btn.innerHTML = isOpen
                    ? `<i class="fas fa-chevron-up"></i> ${i18n.hide}`
                    : `<i class="fas fa-chevron-down"></i> ${i18n.details}`;
            });
        });

        document.querySelectorAll('.btn-set-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const select = document.querySelector(`.status-select[data-order-id="${id}"]`);
                const newStatus = select ? select.value : '';
                if (!newStatus) return;

                const cell = document.querySelector(`.status-cell-${id}`);
                const badge = cell ? cell.querySelector('.status-badge') : null;
                const oldText = badge ? badge.textContent : '';
                const oldClass = badge ? badge.className : '';

                // Optimistic update
                if (badge) {
                    badge.className = `status-badge ${statusBadgeClass(newStatus)} is-pending`;
                    badge.textContent = formatStatus(newStatus);
                }
                btn.disabled = true;

                const r = await adminApi({ action: 'set_status', order_id: id, status: newStatus });
                btn.disabled = false;

                if (r.success) {
                    if (badge) badge.classList.remove('is-pending');
                    notify(<?= i18n_script_json(adminPhrase('Order #{id} status updated.')) ?>.replace('{id}', id), 'success');
                } else {
                    // Rollback
                    if (badge) {
                        badge.className = oldClass;
                        badge.textContent = oldText;
                    }
                    alert(r.error || <?= i18n_script_json(adminPhrase('Failed to update status.')) ?>);
                }
            });
        });

        document.querySelectorAll('.btn-set-assembly').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                const select = document.querySelector(`.assembly-select[data-order-id="${id}"]`);
                const newStatus = select ? select.value : '';
                if (!newStatus) return;

                const cell = document.querySelector(`.assembly-cell-${id}`);
                const oldHtml = cell ? cell.innerHTML : '';
                const badgeClass = newStatus === 'not_applicable' ? 'is-warn' : (newStatus === 'ready' ? 'is-good' : 'is-info');

                // Optimistic update
                if (cell) {
                    cell.innerHTML = `<span class="status-badge ${badgeClass} is-pending">${formatStatus(newStatus)}</span>`;
                }
                btn.disabled = true;

                const r = await adminApi({ action: 'set_assembly_status', order_id: id, assembly_status: newStatus });
                btn.disabled = false;

                if (r.success) {
                    const badge = cell ? cell.querySelector('.status-badge') : null;
                    if (badge) badge.classList.remove('is-pending');
                    notify(<?= i18n_script_json(adminPhrase('Order #{id} assembly updated.')) ?>.replace('{id}', id), 'success');
                } else {
                    // Rollback
                    if (cell) cell.innerHTML = oldHtml;
                    alert(r.error || <?= i18n_script_json(adminPhrase('Failed to update assembly status.')) ?>);
                }
            });
        });

        // ── Suppress (delete) ────────────────────────────────────────────────
        document.querySelectorAll('.btn-suppress').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.orderId, 10);
                if (!confirm(<?= i18n_script_json(adminPhrase('Permanently delete order #{id}? This cannot be undone.')) ?>.replace('{id}', id))) return;

                btn.disabled = true;
                const r = await adminApi({ action: 'suppress', order_id: id });

                if (r.success) {
                    const row = document.getElementById(`order-row-${id}`);
                    const timelineRow = document.getElementById(`order-detail-${id}`);
                    if (row) row.remove();
                    if (timelineRow) timelineRow.remove();
                    notify(<?= i18n_script_json(adminPhrase('Order #{id} deleted.')) ?>.replace('{id}', id), 'success');
                } else {
                    btn.disabled = false;
                    alert(r.error || <?= i18n_script_json(adminPhrase('Failed to delete order.')) ?>);
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
                    alert(r.error || <?= i18n_script_json(adminPhrase('Failed to add note.')) ?>);
                }
            });
        });

        // ── Bulk Actions ──────────────────────────────────────────────────
        const selectAll = document.getElementById('selectAllOrders');
        const checkboxes = () => document.querySelectorAll('.order-checkbox');
        const bulkBar = document.getElementById('bulkActionsBar');
        const bulkCount = document.getElementById('bulkCount');
        const bulkStatusSelect = document.getElementById('bulkStatusSelect');
        const btnBulkStatus = document.getElementById('btnBulkStatus');
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        const btnBulkClear = document.getElementById('btnBulkClear');

        function updateBulkBar() {
            const selected = Array.from(checkboxes()).filter(cb => cb.checked);
            const count = selected.length;
            bulkCount.textContent = count;
            bulkBar.hidden = count === 0;
            if (selectAll) selectAll.checked = count > 0 && count === checkboxes().length;
            checkboxes().forEach(cb => {
                const row = cb.closest('.order-summary-row');
                if (row) row.classList.toggle('is-selected', cb.checked);
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes().forEach(cb => { cb.checked = selectAll.checked; });
                updateBulkBar();
            });
        }

        document.querySelectorAll('.order-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        async function bulkApi(payload) {
            try {
                const res = await fetch('api/admin-bulk-orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));
                return res.ok ? data : { success: false, error: data.error || <?= i18n_script_json(adminPhrase('Request failed.')) ?> };
            } catch (error) {
                return { success: false, error: <?= i18n_script_json(adminPhrase('Network error. Please try again.')) ?> };
            }
        }

        function bulkFailureSummary(results) {
            const failures = (results || []).filter(res => !res.success);
            if (!failures.length) return '';

            const lines = failures.slice(0, 6).map(res => {
                const reason = res.error || <?= i18n_script_json(adminPhrase('Unknown error')) ?>;
                return `#${res.order_id}: ${reason}`;
            });
            if (failures.length > lines.length) {
                lines.push(`+${failures.length - lines.length} more`);
            }

            return lines.join('\n');
        }

        if (btnBulkStatus) {
            btnBulkStatus.addEventListener('click', async () => {
                const status = bulkStatusSelect.value;
                if (!status) { alert(<?= i18n_script_json(adminPhrase('Please select a status.')) ?>); return; }
                const ids = Array.from(checkboxes()).filter(cb => cb.checked).map(cb => parseInt(cb.value, 10));
                if (ids.length === 0) return;

                btnBulkStatus.disabled = true;
                const r = await bulkApi({ action: 'set_status', order_ids: ids, status });
                btnBulkStatus.disabled = false;

                if (r.success) {
                    let updated = 0;
                    (r.results || []).forEach(res => {
                        if (res.success) {
                            updated++;
                            const cell = document.querySelector(`.status-cell-${res.order_id}`);
                            if (cell) {
                                const badge = cell.querySelector('.status-badge');
                                if (badge) {
                                    badge.className = `status-badge ${statusBadgeClass(res.status)}`;
                                    badge.textContent = formatStatus(res.status);
                                }
                            }
                            const rowCheckbox = document.querySelector(`.order-checkbox[value="${res.order_id}"]`);
                            if (rowCheckbox) rowCheckbox.checked = false;
                        }
                    });
                    bulkStatusSelect.value = '';
                    updateBulkBar();
                    notify(<?= i18n_script_json(adminPhrase('{count} order(s) updated to {status}.')) ?>.replace('{count}', updated).replace('{status}', formatStatus(status)), 'success');
                    const failed = bulkFailureSummary(r.results);
                    if (failed) {
                        alert(<?= i18n_script_json(adminPhrase('Some orders could not be updated:')) ?> + '\n' + failed);
                    }
                } else {
                    alert(r.error || <?= i18n_script_json(adminPhrase('Bulk update failed.')) ?>);
                }
            });
        }

        if (btnBulkDelete) {
            btnBulkDelete.addEventListener('click', async () => {
                const ids = Array.from(checkboxes()).filter(cb => cb.checked).map(cb => parseInt(cb.value, 10));
                if (ids.length === 0) return;
                if (!confirm(<?= i18n_script_json(adminPhrase('Permanently delete {count} order(s)? This cannot be undone.')) ?>.replace('{count}', ids.length))) return;

                btnBulkDelete.disabled = true;
                const r = await bulkApi({ action: 'suppress', order_ids: ids });
                btnBulkDelete.disabled = false;

                if (r.success) {
                    let deleted = 0;
                    (r.results || []).forEach(res => {
                        if (res.success) {
                            deleted++;
                            const row = document.getElementById(`order-row-${res.order_id}`);
                            const detailRow = document.getElementById(`order-detail-${res.order_id}`);
                            if (row) row.remove();
                            if (detailRow) detailRow.remove();
                        }
                    });
                    updateBulkBar();
                    notify(<?= i18n_script_json(adminPhrase('{count} order(s) deleted.')) ?>.replace('{count}', deleted), 'success');
                    const failed = bulkFailureSummary(r.results);
                    if (failed) {
                        alert(<?= i18n_script_json(adminPhrase('Some orders could not be deleted:')) ?> + '\n' + failed);
                    }
                } else {
                    alert(r.error || 'Bulk delete failed.');
                }
            });
        }

        if (btnBulkClear) {
            btnBulkClear.addEventListener('click', () => {
                checkboxes().forEach(cb => { cb.checked = false; });
                if (selectAll) selectAll.checked = false;
                updateBulkBar();
            });
        }
    })();
</script>

<style>
.orders-workbench-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: -4px 0 16px;
    color: var(--muted);
    font-size: 0.82rem;
    font-weight: 800;
}
.orders-workbench-meta span {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 5px 9px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--input-bg);
}
.orders-workbench-table {
    min-width: 980px;
    border-collapse: separate;
    border-spacing: 0;
}
.orders-workbench-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: var(--card-bg);
}
.order-summary-row td {
    vertical-align: middle;
    padding-top: 16px;
    padding-bottom: 16px;
}
.order-summary-row:hover td {
    background: rgba(0, 245, 212, 0.025);
}
.order-id-cell strong,
.order-customer-cell strong,
.order-items-summary strong,
.fulfillment-summary strong {
    color: var(--text);
    line-height: 1.35;
}
.order-id-cell small,
.order-customer-cell small,
.order-items-summary small,
.order-state-cell small,
.fulfillment-summary small {
    display: block;
    margin-top: 3px;
    color: var(--muted);
    font-size: 0.76rem;
}
.order-id-cell strong {
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.02em;
}
.order-customer-cell {
    min-width: 210px;
}
.order-items-summary {
    max-width: 320px;
}
.order-items-summary strong {
    overflow-wrap: anywhere;
}
.fulfillment-summary {
    min-width: 180px;
}
.fulfillment-summary strong {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.fulfillment-summary code,
.pickup-code-pill {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    margin-top: 6px;
    padding: 6px 8px;
    border: 1px solid rgba(0, 245, 212, 0.32);
    border-radius: 8px;
    background: rgba(0, 245, 212, 0.08);
    color: var(--cyan);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.76rem;
    letter-spacing: 0.04em;
}
.order-state-cell {
    min-width: 170px;
}
.order-expand-cell {
    text-align: right;
}
.btn-toggle-order {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-width: 94px;
    justify-content: center;
}
.btn-toggle-order i {
    transition: transform 0.18s ease;
}
.btn-toggle-order.is-open i {
    transform: rotate(0deg);
}
.order-detail-row[hidden] {
    display: none;
}
.order-detail-row td {
    padding-top: 0;
    border-bottom-color: rgba(0, 245, 212, 0.16);
}
.order-detail-panel {
    display: grid;
    grid-template-columns: minmax(240px, 1.1fr) minmax(220px, 0.9fr) minmax(260px, 0.9fr) minmax(260px, 1fr);
    gap: 14px;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--input-bg);
}
.order-detail-section {
    min-width: 0;
    display: grid;
    align-content: start;
    gap: 10px;
}
.order-detail-section h3 {
    margin: 0;
    color: var(--text);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.detail-list {
    display: grid;
    gap: 5px;
}
.detail-list strong {
    margin-top: 7px;
    color: var(--text);
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.detail-list strong:first-child {
    margin-top: 0;
}
.detail-list p {
    margin: 0;
    color: var(--text-dim);
    font-size: 0.84rem;
    line-height: 1.45;
    overflow-wrap: anywhere;
}
.order-products-cell,
.order-products-list {
    min-width: 0;
}
.order-products-list {
    display: grid;
    gap: 8px;
}
.order-product-line {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: start;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
}
.order-product-qty {
    color: var(--cyan);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    font-weight: 800;
    white-space: nowrap;
}
.order-product-name {
    color: var(--text);
    font-weight: 800;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.order-product-price {
    color: var(--muted);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    white-space: nowrap;
}
.order-ops-section .action-group {
    display: grid;
    gap: 9px;
    width: 100%;
}
.action-row {
    display: grid;
    grid-template-columns: 74px minmax(0, 1fr) 38px;
    align-items: center;
    gap: 8px;
}
.action-label {
    color: var(--text-dim);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.status-select,
.assembly-select {
    width: 100%;
    min-height: 36px;
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
    color: var(--text);
    font: inherit;
    font-size: 0.78rem;
}
.btn-set-status,
.btn-set-assembly {
    width: 38px;
    min-height: 36px;
    padding: 7px;
    border-radius: 8px;
}
.btn-suppress {
    width: 100%;
    justify-content: center;
    min-height: 36px;
}
.timeline-events {
    display: grid;
    gap: 9px;
    max-height: 180px;
    overflow: auto;
}
.timeline-event {
    padding: 9px 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
}
.timeline-event strong,
.timeline-event span,
.timeline-event p {
    display: block;
}
.timeline-event span {
    color: var(--muted);
    font-size: 0.75rem;
}
.timeline-event p {
    margin: 6px 0 0;
    color: var(--text-dim);
    font-size: 0.82rem;
}
.timeline-note-form {
    display: grid;
    gap: 8px;
}
.timeline-note-form textarea {
    min-height: 82px;
    width: 100%;
    resize: vertical;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card-bg);
    color: var(--text);
    padding: 10px;
}
@media (max-width: 1180px) {
    .order-detail-panel {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 760px) {
    .orders-workbench-table {
        min-width: 900px;
    }
    .order-detail-panel {
        grid-template-columns: 1fr;
    }
}

/* ── Bulk Actions ── */
.bulk-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 16px;
    margin-bottom: 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--card-bg);
    animation: bulkBarIn 0.2s ease;
}
@keyframes bulkBarIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.bulk-count {
    font-size: 0.88rem;
    color: var(--text);
}
.bulk-count strong {
    color: var(--cyan);
    font-family: 'JetBrains Mono', monospace;
}
.bulk-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.bulk-controls select {
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--text);
    font-size: 0.85rem;
    min-width: 160px;
}
.checkbox-cell {
    width: 40px;
    text-align: center;
    vertical-align: middle;
}
.checkbox-cell input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--cyan);
    cursor: pointer;
}
.order-summary-row.is-selected {
    background: rgba(0, 245, 212, 0.04);
}
.status-badge.is-pending {
    opacity: 0.6;
    animation: pendingPulse 1.2s ease-in-out infinite;
}
@keyframes pendingPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.3; }
}
</style>

<?php adminPageEnd(); ?>
