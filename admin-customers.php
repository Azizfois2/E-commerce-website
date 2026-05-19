<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
$hasClients = adminTableExists($pdo, 'Client');
$hasOrders = adminTableExists($pdo, 'orders');

$search = trim((string) ($_GET['search'] ?? ''));
$orderStatus = trim((string) ($_GET['status'] ?? ''));

$stats = [
    'customers' => $hasClients ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM Client') : 0,
    'with_orders' => ($hasClients && $hasOrders) ? (int) adminFetchValue($pdo, 'SELECT COUNT(DISTINCT client_id) FROM orders') : 0,
    'orders' => $hasOrders ? (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM orders') : 0,
    'revenue' => $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(total), 0) FROM orders') : 0.0,
];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $clientId = (int) $_GET['id'];
    if ($_GET['action'] === 'suspend') {
        $reason = trim((string) ($_GET['reason'] ?? 'Violation of terms of service.'));
        $stmt = $pdo->prepare("UPDATE Client SET is_suspended = 1, suspension_reason = ?, suspended_at = NOW() WHERE id_client = ?");
        $stmt->execute([$reason, $clientId]);
        
        // Send email
        require_once 'mailer.php';
        $stmt = $pdo->prepare("SELECT nom, email FROM Client WHERE id_client = ?");
        $stmt->execute([$clientId]);
        $c = $stmt->fetch();
        if ($c) {
            sendSuspensionEmail($c['email'], $c['nom'], $reason);
        }
        
        header("Location: admin-customers.php?suspended=1");
        exit();
    } elseif ($_GET['action'] === 'unsuspend') {
        $pdo->prepare("UPDATE Client SET is_suspended = 0, suspension_reason = NULL, suspended_at = NULL WHERE id_client = ?")->execute([$clientId]);
        header("Location: admin-customers.php?unsuspended=1");
        exit();
    }
}

$customers = [];
if ($hasClients && $hasOrders) {
    $statusWhere = '';
    if ($orderStatus !== '') {
        $statusWhere = ' AND EXISTS (SELECT 1 FROM orders os WHERE os.client_id = c.id_client AND os.status = :status_filter)';
    }

    $customers = adminFetchAll($pdo, '
        SELECT
            c.id_client,
            c.nom,
            c.email,
            c.telephone,
            c.adresse,
            c.created_at,
            c.is_suspended,
            c.suspension_reason,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total), 0) AS total_spent,
            MAX(o.created_at) AS last_order_at
        FROM Client c
        LEFT JOIN orders o ON o.client_id = c.id_client
        WHERE (:search_empty = 1 OR c.nom LIKE :search_name OR c.email LIKE :search_email OR c.telephone LIKE :search_phone)
          ' . $statusWhere . '
        GROUP BY c.id_client, c.nom, c.email, c.telephone, c.adresse, c.created_at, c.is_suspended, c.suspension_reason
        ORDER BY c.created_at DESC, c.id_client DESC
    ', array_filter([
        'search_empty' => $search === '' ? 1 : 0,
        'search_name' => '%' . $search . '%',
        'search_email' => '%' . $search . '%',
        'search_phone' => '%' . $search . '%',
        'status_filter' => $orderStatus !== '' ? $orderStatus : null,
    ], static fn($value) => $value !== null));
} elseif ($hasClients) {
    $customers = adminFetchAll($pdo, '
        SELECT
            c.id_client,
            c.nom,
            c.email,
            c.telephone,
            c.adresse,
            c.created_at,
            c.is_suspended,
            c.suspension_reason,
            0 AS order_count,
            0 AS total_spent,
            NULL AS last_order_at
        FROM Client c
        WHERE (:search_empty = 1 OR c.nom LIKE :search_name OR c.email LIKE :search_email OR c.telephone LIKE :search_phone)
        ORDER BY c.created_at DESC, c.id_client DESC
    ', [
        'search_empty' => $search === '' ? 1 : 0,
        'search_name' => '%' . $search . '%',
        'search_email' => '%' . $search . '%',
        'search_phone' => '%' . $search . '%',
    ]);
}

adminPageStart('Customer Management', 'customers');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">User Monitoring</span>
        <h1>Customers</h1>
        <p class="section-copy">Monitor registered users, review their order activity, and remove accounts when required. Deleting a user also deletes their orders and order items.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-orders.php">Orders</a>
        <a class="button button-light" href="api/export-customers.php?format=csv"><i class="fas fa-file-csv"></i> CSV</a>
        <a class="button button-light" href="api/export-customers.php?format=r"><i class="fas fa-code"></i> RData Script</a>
    </div>
</section>

<?php if (isset($_GET['deleted'])): ?>
    <div class="admin-alert success">Customer and related orders were deleted.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="admin-alert error"><?= adminH($_GET['error']) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <article class="stat-card"><strong><?= $stats['customers'] ?></strong><span>Total customers</span></article>
    <article class="stat-card"><strong><?= $stats['with_orders'] ?></strong><span>Customers with orders</span></article>
    <article class="stat-card"><strong><?= $stats['orders'] ?></strong><span>Total orders</span></article>
    <article class="stat-card"><strong><?= adminMoney($stats['revenue']) ?></strong><span>Customer revenue</span></article>
</div>

<section class="table-card customers-panel">
    <div class="card-head">
        <h2>Registered Users</h2>
    </div>

    <form class="filter-bar customer-filter" method="get">
        <label>
            Search
            <input type="text" name="search" value="<?= adminH($search) ?>" placeholder="Name, email, phone">
        </label>
        <label>
            Order status
            <select name="status">
                <option value="">Any order status</option>
                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                    <option value="<?= adminH($status) ?>" <?= $orderStatus === $status ? 'selected' : '' ?>><?= adminH(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button button-primary" type="submit">Filter</button>
    </form>

    <?php if ($customers === []): ?>
        <p class="empty-copy">No users match the current filters.</p>
    <?php else: ?>
        <div class="customer-list">
            <?php foreach ($customers as $customer): ?>
                <article class="customer-row">
                    <a class="customer-main" href="admin-customer-detail.php?id=<?= (int) $customer['id_client'] ?>">
                        <span class="profile-mark compact-mark">
                            <span><?= adminH(substr((string) $customer['nom'], 0, 1)) ?></span>
                        </span>
                        <span>
                            <strong><?= adminH($customer['nom']) ?></strong>
                            <small>#<?= (int) $customer['id_client'] ?> - <?= adminH($customer['email']) ?></small>
                        </span>
                    </a>

                    <div class="customer-metrics">
                        <span>
                            <strong><?= (int) $customer['order_count'] ?></strong>
                            <small>Orders</small>
                        </span>
                        <span>
                            <strong><?= adminMoney((float) $customer['total_spent']) ?></strong>
                            <small>Total spent</small>
                        </span>
                        <span>
                            <strong><?= $customer['last_order_at'] ? adminH(date('Y-m-d', strtotime((string) $customer['last_order_at']))) : 'None' ?></strong>
                            <small>Last order</small>
                        </span>
                    </div>

                    <div class="customer-actions">
                        <?php if ($customer['is_suspended']): ?>
                            <span class="status-badge status-failed" style="margin-right: 8px;">Suspended</span>
                            <a class="button button-light button-small" href="admin-customers.php?action=unsuspend&id=<?= (int) $customer['id_client'] ?>">Restore</a>
                        <?php else: ?>
                            <button class="button button-warning button-small" onclick="suspendUser(<?= (int) $customer['id_client'] ?>)">Suspend</button>
                        <?php endif; ?>
                        <a class="button button-light button-small" href="admin-customer-detail.php?id=<?= (int) $customer['id_client'] ?>">Details</a>
                        <form method="post" action="admin-customer-delete.php" onsubmit="return confirm('Delete this user and all related orders?');" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $customer['id_client'] ?>">
                            <button class="button button-danger button-small" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.button-warning { background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.3); color: #ffc107; }
.button-warning:hover { background: rgba(255, 193, 7, 0.2); }
.status-badge { padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
.status-failed { background: rgba(255, 61, 90, 0.1); color: var(--red); border: 1px solid rgba(255, 61, 90, 0.2); }
</style>

<script>
function suspendUser(id) {
    const reason = prompt("Please provide a reason for suspension (will be emailed to the user):", "Violation of terms of service.");
    if (reason !== null) {
        window.location.href = `admin-customers.php?action=suspend&id=${id}&reason=${encodeURIComponent(reason)}`;
    }
}
</script>
<?php adminPageEnd(); ?>
