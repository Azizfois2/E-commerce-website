<?php
require_once 'config.php';
require_once 'admin-helpers.php';
adminRequireAuth();

$pdo = db();
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT r.*, p.name AS product_name, c.nom AS client_name, c.email AS client_email
    FROM product_reviews r
    JOIN products p ON p.id = r.product_id
    JOIN Client c ON c.id_client = r.client_id
";

$params = [];
if ($statusFilter !== 'all') {
    $sql .= " WHERE r.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$stats = [
    'total' => adminFetchValue($pdo, "SELECT COUNT(*) FROM product_reviews"),
    'pending' => adminFetchValue($pdo, "SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'"),
    'approved' => adminFetchValue($pdo, "SELECT COUNT(*) FROM product_reviews WHERE status = 'approved'"),
    'rejected' => adminFetchValue($pdo, "SELECT COUNT(*) FROM product_reviews WHERE status = 'rejected'")
];

adminPageStart('Manage Reviews', 'reviews');
?>

<section class="section-heading">
    <div>
        <span class="eyebrow">Moderation</span>
        <h1>Product Reviews</h1>
        <p class="section-copy">Review and moderate customer feedback. Keep your product ratings authentic and high-quality.</p>
    </div>
</section>

<div class="stats-grid" style="margin-bottom: 24px;">
    <article class="stat-card">
        <strong><?= $stats['total'] ?></strong>
        <span>Total Reviews</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['pending'] ?></strong>
        <span>Pending</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['approved'] ?></strong>
        <span>Approved</span>
    </article>
    <article class="stat-card">
        <strong><?= $stats['rejected'] ?></strong>
        <span>Rejected</span>
    </article>
</div>

<div class="filter-bar">
    <label>
        Filter by Status
        <select onchange="window.location.href='admin-reviews.php?status='+this.value">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Reviews</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
    </label>
</div>

<section class="table-card">
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Customer</th>
                <th>Rating & Review</th>
                <th>Votes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="6">No reviews found matching the criteria.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviews as $rev): ?>
                <tr data-id="<?= (int)$rev['id'] ?>">
                    <td>
                        <strong><?= adminH($rev['product_name']) ?></strong>
                        <small>ID: #<?= (int)$rev['product_id'] ?></small>
                    </td>
                    <td>
                        <strong><?= adminH($rev['client_name']) ?></strong>
                        <small><?= adminH($rev['client_email']) ?></small>
                        <?php if ($rev['is_verified_purchase']): ?>
                            <span class="status-badge is-good" style="padding: 2px 6px; font-size: 0.65rem; margin-top: 4px;">Verified</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width: 300px;">
                        <div style="color: #ffb400; margin-bottom: 4px; font-size: 0.85rem;">
                            <?= str_repeat('<i class="fas fa-star"></i>', (int)$rev['rating']) ?>
                            <?= str_repeat('<i class="far fa-star"></i>', 5 - (int)$rev['rating']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-dim); line-height: 1.4;">
                            <?= nl2br(adminH($rev['review_text'])) ?>
                        </div>
                    </td>
                    <td>
                        <small style="color: var(--green);"><i class="fas fa-thumbs-up"></i> <?= (int)$rev['helpful_count'] ?></small><br>
                        <small style="color: var(--red);"><i class="fas fa-thumbs-down"></i> <?= (int)$rev['unhelpful_count'] ?></small>
                    </td>
                    <td>
                        <?php
                            $sClass = $rev['status'] === 'approved' ? 'is-good' : ($rev['status'] === 'rejected' ? 'is-danger' : 'is-warn');
                        ?>
                        <span class="status-badge <?= $sClass ?>"><?= adminH(ucfirst($rev['status'])) ?></span>
                        <small style="display:block; margin-top:4px;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></small>
                    </td>
                    <td>
                        <div class="table-actions">
                            <?php if ($rev['status'] !== 'approved'): ?>
                                <button class="button button-light button-small mod-btn" data-action="approve" style="color: var(--green); border-color: var(--green);">Approve</button>
                            <?php endif; ?>
                            <?php if ($rev['status'] !== 'rejected'): ?>
                                <button class="button button-light button-small mod-btn" data-action="reject" style="color: var(--orange); border-color: var(--orange);">Reject</button>
                            <?php endif; ?>
                            <button class="button button-danger button-small mod-btn" data-action="delete">Delete</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
document.querySelectorAll('.mod-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const row = this.closest('tr');
        const id = parseInt(row.dataset.id);
        const action = this.dataset.action;

        if (action === 'delete' && !confirm('Are you sure you want to delete this review?')) return;

        try {
            const res = await fetch('api/admin-reviews.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error');
            }
        } catch (e) {
            alert('Network error');
        }
    });
});
</script>

<?php adminPageEnd(); ?>
