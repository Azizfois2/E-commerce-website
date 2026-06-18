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
        'repair_service_requests' => ['new', 'quoting', 'in-progress', 'repaired', 'declined'],
        'after_sales_requests' => ['submitted', 'reviewing', 'approved', 'awaiting_item', 'inspecting', 'resolved', 'rejected'],
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
$repairs = requestRows($pdo, 'repair_service_requests', "
    SELECT *
    FROM repair_service_requests
    ORDER BY created_at DESC
    LIMIT 25
");
$rmas = requestRows($pdo, 'after_sales_requests', "
    SELECT *
    FROM after_sales_requests
    ORDER BY created_at DESC
    LIMIT 25
");

function statusForm(string $table, int $id, string $current, array $statuses): string
{
    $options = '';
    foreach ($statuses as $status) {
        $selected = $status === $current ? ' selected' : '';
        $options .= '<option value="' . adminH($status) . '"' . $selected . '>' . adminH(adminStatusLabel($status)) . '</option>';
    }
    return '
        <form method="post" class="request-status-form">
            <input type="hidden" name="table" value="' . adminH($table) . '">
            <input type="hidden" name="id" value="' . $id . '">
            <select name="status">' . $options . '</select>
            <button type="submit">' . adminH(adminPhrase('Save')) . '</button>
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
        <p class="eyebrow"><?= adminH(adminPhrase('New.md Workflows')) ?></p>
        <h1><?= adminH(adminPhrase('Request Queues')) ?></h1>
        <p><?= adminH(adminPhrase('Price matches, community showcases, trade-ins, repairs, bank receipts, and referral codes.')) ?></p>
    </div>
</div>

<div class="request-board">
    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Price Match Queue')) ?></h2></div>
        <?php if ($priceMatches): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Product')) ?></th><th><?= adminH(adminPhrase('Competitor')) ?></th><th><?= adminH(adminPhrase('Contact')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($priceMatches as $row): ?>
            <tr>
                <td><?= adminH($row['product_name']) ?><small><?= adminH(adminPhrase('Catalog')) ?>: <?= $row['catalog_price'] !== null ? adminMoney((float) $row['catalog_price']) : adminH(adminPhrase('N/A')) ?></small></td>
                <td><?= adminH($row['competitor_url'] ?: adminPhrase('No URL')) ?><small><?= $row['competitor_price'] ? adminMoney((float) $row['competitor_price']) : adminH(adminPhrase('No price')) ?></small></td>
                <td><?= adminH($row['contact_email'] ?: adminPhrase('No email')) ?><small><?= adminH($row['contact_phone'] ?: adminPhrase('No phone')) ?></small></td>
                <td><?= statusForm('price_match_requests', (int) $row['id'], (string) $row['status'], ['new','reviewing','matched','declined']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No price match requests yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Community Builds')) ?></h2></div>
        <?php if ($community): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Build')) ?></th><th><?= adminH(adminPhrase('Caption')) ?></th><th><?= adminH(adminPhrase('Total')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($community as $row): ?>
            <tr>
                <td><?= adminH($row['build_name']) ?><small><?= adminH($row['use_case'] ?: adminPhrase('General')) ?></small></td>
                <td><?= adminH(substr((string) ($row['caption'] ?? ''), 0, 120)) ?><small><?= adminH($row['image_url'] ?: adminPhrase('No image')) ?></small></td>
                <td><?= adminMoney((float) $row['total_price']) ?></td>
                <td><?= statusForm('community_builds', (int) $row['id'], (string) $row['status'], ['pending','approved','rejected']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No community builds yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Trade-in Evaluations')) ?></h2></div>
        <?php if ($trades): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Hardware')) ?></th><th><?= adminH(adminPhrase('Image')) ?></th><th><?= adminH(adminPhrase('Estimate')) ?></th><th><?= adminH(adminPhrase('Contact')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($trades as $row): ?>
            <tr>
                <td><?= adminH($row['hardware_name']) ?><small><?= adminH($row['hardware_type']) ?>, <?= adminH($row['condition_grade']) ?></small></td>
                <td>
                    <?php if (!empty($row['product_image'])): ?>
                        <a href="<?= adminH($row['product_image']) ?>" target="_blank">
                            <img src="<?= adminH($row['product_image']) ?>" alt="Hardware" style="width: 80px; height: 80px; object-fit: contain; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: #000;">
                        </a>
                    <?php else: ?>
                        <span style="color: #666; font-size: 0.85rem;"><?= adminH(adminPhrase('No Image')) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= $row['estimated_value'] ? adminMoney((float) $row['estimated_value']) : adminH(adminPhrase('Pending')) ?></td>
                <td><?= adminH($row['contact_email'] ?: adminPhrase('No email')) ?><small><?= adminH($row['contact_phone'] ?: adminPhrase('No phone')) ?></small></td>
                <td><?= statusForm('trade_in_requests', (int) $row['id'], (string) $row['status'], ['new','quoted','accepted','declined']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No trade-in requests yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Payment Receipts')) ?></h2></div>
        <?php if ($receipts): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Bank')) ?></th><th><?= adminH(adminPhrase('Amount')) ?></th><th><?= adminH(adminPhrase('Reference')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($receipts as $row): ?>
            <tr>
                <td><?= adminH($row['bank_name']) ?><small><?= adminH(adminPhrase('Order')) ?> #<?= adminH($row['order_id'] ?: adminPhrase('N/A')) ?></small></td>
                <td><?= adminMoney((float) $row['amount']) ?></td>
                <td><?= adminH($row['transfer_reference'] ?: adminPhrase('No reference')) ?></td>
                <td><?= statusForm('bank_transfer_receipts', (int) $row['id'], (string) $row['status'], ['new','verified','rejected']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No receipt uploads logged yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Repairs & Upgrades')) ?></h2></div>
        <?php if ($repairs): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Device')) ?></th><th><?= adminH(adminPhrase('Issue')) ?></th><th><?= adminH(adminPhrase('Contact')) ?></th><th><?= adminH(adminPhrase('Date')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($repairs as $row): ?>
            <tr>
                <td><?= adminH($row['device_name']) ?><small><?= adminH($row['device_type']) ?></small></td>
                <td><?= adminH(substr((string) ($row['issue_description'] ?? ''), 0, 150)) ?></td>
                <td><?= adminH($row['contact_email'] ?: adminPhrase('No email')) ?><small><?= adminH($row['contact_phone'] ?: adminPhrase('No phone')) ?></small></td>
                <td><small><?= adminH($row['created_at']) ?></small></td>
                <td><?= statusForm('repair_service_requests', (int) $row['id'], (string) $row['status'], ['new','quoting','in-progress','repaired','declined']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No repair requests yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('RMA & Warranty')) ?></h2></div>
        <?php if ($rmas): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Ticket')) ?></th><th><?= adminH(adminPhrase('Product')) ?></th><th><?= adminH(adminPhrase('Customer')) ?></th><th><?= adminH(adminPhrase('Date')) ?></th><th><?= adminH(adminPhrase('Status')) ?></th></tr></thead><tbody>
            <?php foreach ($rmas as $row): ?>
            <tr>
                <td><?= adminH($row['ticket_code']) ?><small><?= adminH(ucfirst($row['request_type'])) ?></small></td>
                <td><?= adminH($row['product_name']) ?><small><?= adminH(adminPhrase('Order')) ?> #<?= adminH($row['order_id'] ?: adminPhrase('N/A')) ?></small></td>
                <td><?= adminH($row['customer_name']) ?><small><?= adminH($row['email']) ?></small></td>
                <td><small><?= adminH($row['created_at']) ?></small></td>
                <td><?= statusForm('after_sales_requests', (int) $row['id'], (string) $row['status'], ['submitted','reviewing','approved','awaiting_item','inspecting','resolved','rejected']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No RMA requests yet.')) ?></div><?php endif; ?>
    </section>

    <section class="request-section">
        <div class="card-head"><h2><?= adminH(adminPhrase('Referral Codes Generated')) ?></h2></div>
        <?php if ($referrals): ?>
        <table class="request-table"><thead><tr><th><?= adminH(adminPhrase('Client')) ?></th><th><?= adminH(adminPhrase('Code')) ?></th><th><?= adminH(adminPhrase('Bonus')) ?></th><th><?= adminH(adminPhrase('Created')) ?></th></tr></thead><tbody>
            <?php foreach ($referrals as $row): ?>
            <tr>
                <td><?= adminH($row['nom'] ?: adminPhrase('Client') . ' #' . $row['client_id']) ?><small><?= adminH($row['email'] ?: '') ?></small></td>
                <td><strong><?= adminH($row['code']) ?></strong></td>
                <td><?= (int) $row['bonus_points'] ?> pts</td>
                <td><?= adminH($row['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-queue"><?= adminH(adminPhrase('No referral codes generated yet.')) ?></div><?php endif; ?>
    </section>
</div>
<?php adminPageEnd(); ?>
