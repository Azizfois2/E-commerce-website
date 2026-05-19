<?php
require_once __DIR__ . '/src/Services/admin-helpers.php';

adminRequireAuth();
$pdo = db();

function requestRows(PDO $pdo, string $table, string $sql): array
{
    if (!adminTableExists($pdo, $table)) {
        return [];
    }
    return adminFetchAll($pdo, $sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = (string) ($_POST['table'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    $allowed = [
        'price_match_requests' => ['new', 'reviewing', 'matched', 'declined'],
        'community_builds' => ['pending', 'approved', 'rejected'],
        'trade_in_requests' => ['new', 'quoted', 'accepted', 'declined'],
        'bank_transfer_receipts' => ['new', 'verified', 'rejected'],
    ];
    if ($id > 0 && isset($allowed[$table]) && in_array($status, $allowed[$table], true)) {
        $stmt = $pdo->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        if ($table === 'community_builds' && $status === 'approved') {
            $pdo->prepare('UPDATE community_builds SET approved_at = COALESCE(approved_at, NOW()) WHERE id = ?')->execute([$id]);
        }
        adminLogActivity($pdo, 'request_status', $table, $id, "Set {$table} #{$id} to {$status}");
    }
    adminRedirect('admin-requests.php');
}

$priceMatches = requestRows($pdo, 'price_match_requests', "
    SELECT pmr.*, p.price AS catalog_price
    FROM price_match_requests pmr
    LEFT JOIN products p ON p.id = pmr.product_id
    ORDER BY pmr.created_at DESC
    LIMIT 25
");
$community = requestRows($pdo, 'community_builds', "
    SELECT *
    FROM community_builds
    ORDER BY created_at DESC
    LIMIT 25
");
$trades = requestRows($pdo, 'trade_in_requests', "
    SELECT *
    FROM trade_in_requests
    ORDER BY created_at DESC
    LIMIT 25
");
$receipts = requestRows($pdo, 'bank_transfer_receipts', "
    SELECT *
    FROM bank_transfer_receipts
    ORDER BY created_at DESC
    LIMIT 25
");
$referrals = requestRows($pdo, 'referral_codes', "
    SELECT rc.*, c.email, c.nom
    FROM referral_codes rc
    LEFT JOIN Client c ON c.id_client = rc.client_id
    ORDER BY rc.created_at DESC
    LIMIT 25
");

function statusForm(string $table, int $id, string $current, array $statuses): string
{
    $options = '';
    foreach ($statuses as $status) {
        $selected = $status === $current ? ' selected' : '';
        $options .= '<option value="' . adminH($status) . '"' . $selected . '>' . adminH($status) . '</option>';
    }
    return '
        <form method="post" class="request-status-form">
            <input type="hidden" name="table" value="' . adminH($table) . '">
            <input type="hidden" name="id" value="' . $id . '">
            <select name="status">' . $options . '</select>
            <button type="submit">Save</button>
        </form>
    ';
}

adminPageStart('Request Queues', 'requests');
?>
<style>
    .request-board { display: grid; gap: 22px; }
    .request-section { border: 1px solid var(--border); border-radius: 14px; background: var(--card-bg); overflow: hidden; }
    .request-section h2 { margin: 0; padding: 18px 20px; border-bottom: 1px solid var(--border); font-size: 1rem; display: flex; align-items: center; gap: 10px; }
    .request-table { width: 100%; border-collapse: collapse; }
    .request-table th, .request-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; color: var(--text); font-size: 0.86rem; }
    .request-table th { color: var(--muted); font-family: 'JetBrains Mono', monospace; font-size: 0.66rem; text-transform: uppercase; }
    .request-table small { display: block; color: var(--muted); margin-top: 4px; }
    .request-status-form { display: flex; gap: 6px; align-items: center; }
    .request-status-form select, .request-status-form button { min-height: 34px; border: 1px solid var(--border); border-radius: 8px; background: var(--page-bg-3); color: var(--text); padding: 0 9px; }
    .request-status-form button { color: var(--cyan); cursor: pointer; font-weight: 800; }
    .empty-queue { padding: 18px 20px; color: var(--muted); }
    @media (max-width: 860px) { .request-table { display: block; overflow-x: auto; } }
</style>

<div class="dashboard-header">
    <div>
        <p class="eyebrow">New.md Workflows</p>
        <h1>Request Queues</h1>
        <p>Price matches, community showcases, trade-ins, bank receipts, and referral codes.</p>
    </div>
</div>

<div class="request-board">
    <section class="request-section">
        <h2><i class="fas fa-scale-balanced"></i> Price Match Requests</h2>
        <?php if ($priceMatches): ?>
        <table class="request-table"><thead><tr><th>Product</th><th>Competitor</th><th>Contact</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($priceMatches as $row): ?>
            <tr>
                <td><?= adminH($row['product_name']) ?><small>Catalog: <?= $row['catalog_price'] !== null ? adminMoney((float) $row['catalog_price']) : 'N/A' ?></small></td>
                <td><?= adminH($row['competitor_url'] ?: 'No URL') ?><small><?= $row['competitor_price'] ? adminMoney((float) $row['competitor_price']) : 'No price' ?></small></td>
                <td><?= adminH($row['contact_email'] ?: 'No email') ?><small><?= adminH($row['contact_phone'] ?: 'No phone') ?></small></td>
                <td><?= statusForm('price_match_requests', (int) $row['id'], (string) $row['status'], ['new','reviewing','matched','declined']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue">No price match requests yet.</div><?php endif; ?>
    </section>

    <section class="request-section">
        <h2><i class="fas fa-images"></i> Community Build Submissions</h2>
        <?php if ($community): ?>
        <table class="request-table"><thead><tr><th>Build</th><th>Caption</th><th>Total</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($community as $row): ?>
            <tr>
                <td><?= adminH($row['build_name']) ?><small><?= adminH($row['use_case'] ?: 'General') ?></small></td>
                <td><?= adminH(substr((string) ($row['caption'] ?? ''), 0, 120)) ?><small><?= adminH($row['image_url'] ?: 'No image') ?></small></td>
                <td><?= adminMoney((float) $row['total_price']) ?></td>
                <td><?= statusForm('community_builds', (int) $row['id'], (string) $row['status'], ['pending','approved','rejected']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue">No community builds yet.</div><?php endif; ?>
    </section>

    <section class="request-section">
        <h2><i class="fas fa-right-left"></i> Trade-In Requests</h2>
        <?php if ($trades): ?>
        <table class="request-table"><thead><tr><th>Hardware</th><th>Estimate</th><th>Contact</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($trades as $row): ?>
            <tr>
                <td><?= adminH($row['hardware_name']) ?><small><?= adminH($row['hardware_type']) ?>, <?= adminH($row['condition_grade']) ?></small></td>
                <td><?= $row['estimated_value'] ? adminMoney((float) $row['estimated_value']) : 'Pending' ?></td>
                <td><?= adminH($row['contact_email'] ?: 'No email') ?><small><?= adminH($row['contact_phone'] ?: 'No phone') ?></small></td>
                <td><?= statusForm('trade_in_requests', (int) $row['id'], (string) $row['status'], ['new','quoted','accepted','declined']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue">No trade-in requests yet.</div><?php endif; ?>
    </section>

    <section class="request-section">
        <h2><i class="fas fa-receipt"></i> Bank Transfer Receipts</h2>
        <?php if ($receipts): ?>
        <table class="request-table"><thead><tr><th>Bank</th><th>Amount</th><th>Reference</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($receipts as $row): ?>
            <tr>
                <td><?= adminH($row['bank_name']) ?><small>Order #<?= adminH($row['order_id'] ?: 'N/A') ?></small></td>
                <td><?= adminMoney((float) $row['amount']) ?></td>
                <td><?= adminH($row['transfer_reference'] ?: 'No reference') ?></td>
                <td><?= statusForm('bank_transfer_receipts', (int) $row['id'], (string) $row['status'], ['new','verified','rejected']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue">No receipt uploads logged yet.</div><?php endif; ?>
    </section>

    <section class="request-section">
        <h2><i class="fas fa-share-nodes"></i> Referral Codes</h2>
        <?php if ($referrals): ?>
        <table class="request-table"><thead><tr><th>Client</th><th>Code</th><th>Bonus</th><th>Created</th></tr></thead><tbody>
            <?php foreach ($referrals as $row): ?>
            <tr>
                <td><?= adminH($row['nom'] ?: 'Client #' . $row['client_id']) ?><small><?= adminH($row['email'] ?: '') ?></small></td>
                <td><strong><?= adminH($row['code']) ?></strong></td>
                <td><?= (int) $row['bonus_points'] ?> pts</td>
                <td><?= adminH($row['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue">No referral codes generated yet.</div><?php endif; ?>
    </section>
</div>
<?php adminPageEnd(); ?>
