<?php
declare(strict_types=1);

/**
 * admin-chatbot-feedback.php — Portal to manage chatbot ratings and interactions.
 */
require_once __DIR__ . '/admin-helpers.php';
adminRequireAuth();

$pdo = db();

// Clear logs action if requested
$action = $_GET['action'] ?? '';
if ($action === 'clear_all') {
    try {
        $pdo->exec("DELETE FROM chatbot_feedback");
        adminLogActivity($pdo, 'delete_all', 'chatbot_feedback', null, 'Cleared all chatbot feedback logs');
        header('Location: admin-chatbot-feedback.php?success=1');
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Single log delete
if ($action === 'delete' && isset($_GET['id'])) {
    $logId = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM chatbot_feedback WHERE id = ?");
        $stmt->execute([$logId]);
        adminLogActivity($pdo, 'delete', 'chatbot_feedback', $logId, "Deleted chatbot feedback log #{$logId}");
        header('Location: admin-chatbot-feedback.php?success=2');
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Get Filters
$ratingFilter = $_GET['rating'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

// Build query
$queryStr = "
    SELECT f.*, c.nom as client_name, c.email as client_email
    FROM chatbot_feedback f
    LEFT JOIN client c ON f.client_id = c.id_client
    WHERE 1=1
";
$params = [];

if ($ratingFilter === 'likes') {
    $queryStr .= " AND f.rating = 1";
} elseif ($ratingFilter === 'dislikes') {
    $queryStr .= " AND f.rating = -1";
}

if ($searchQuery !== '') {
    $queryStr .= " AND (f.query LIKE ? OR f.response LIKE ? OR c.nom LIKE ?)";
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

$queryStr .= " ORDER BY f.created_at DESC";

try {
    $stmt = $pdo->prepare($queryStr);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute Quick Stats
    $totalCount = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM chatbot_feedback");
    $likesCount = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM chatbot_feedback WHERE rating = 1");
    $dislikesCount = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM chatbot_feedback WHERE rating = -1");
    
    $approvalRate = $totalCount > 0 ? round(($likesCount / $totalCount) * 100, 1) : 0.0;
} catch (\Throwable $e) {
    $logs = [];
    $error = $e->getMessage();
    $totalCount = $likesCount = $dislikesCount = 0;
    $approvalRate = 0.0;
}

adminPageStart('Chatbot Logs & Ratings', 'chatbot');
?>

<div class="section-heading">
    <div>
        <span class="eyebrow">Interactive Insights</span>
        <h1>Chatbot Assistant Logs</h1>
        <p class="section-copy">Monitor and review AI-driven user queries, auto-responses, and thumbs feedback rating logs.</p>
    </div>
    <div class="heading-actions">
        <?php if ($totalCount > 0): ?>
            <a href="admin-chatbot-feedback.php?action=clear_all" class="button button-danger" onclick="return confirm('Are you sure you want to permanently clear all chatbot interaction logs?');" style="gap: 8px;">
                <i class="fas fa-trash-alt"></i> Clear All Logs
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="admin-alert success" style="margin: 20px 0;">
        <i class="fas fa-circle-check"></i> 
        <?= $_GET['success'] === '1' ? 'All logs successfully cleared!' : 'Interaction log successfully deleted.' ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="admin-alert error" style="margin: 20px 0;">
        <i class="fas fa-triangle-exclamation"></i> <?= adminH($error) ?>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid" style="margin: 24px 0 32px;">
    <div class="stat-card">
        <strong><?= $totalCount ?></strong>
        <span>Total Queries Logged</span>
    </div>
    
    <div class="stat-card">
        <strong style="color: #2ecc71;"><?= $likesCount ?></strong>
        <span style="color: #2ecc71;">Likes (Positive) <i class="fas fa-thumbs-up"></i></span>
    </div>

    <div class="stat-card">
        <strong style="color: #e74c3c;"><?= $dislikesCount ?></strong>
        <span style="color: #e74c3c;">Dislikes (Negative) <i class="fas fa-thumbs-down"></i></span>
    </div>

    <div class="stat-card">
        <strong><?= $approvalRate ?>%</strong>
        <span>AI Approval Rate</span>
    </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="admin-chatbot-feedback.php" class="filter-bar" style="grid-template-columns: minmax(160px, 200px) minmax(240px, 1fr) auto auto; margin-bottom: 24px; background: var(--card-bg); padding: 18px; border: 1px solid var(--border); border-radius: 14px;">
    <label>
        Rating Status
        <select name="rating" id="rating" onchange="this.form.submit();">
            <option value="all" <?= $ratingFilter === 'all' ? 'selected' : '' ?>>All Interactions</option>
            <option value="likes" <?= $ratingFilter === 'likes' ? 'selected' : '' ?>>👍 Likes Only</option>
            <option value="dislikes" <?= $ratingFilter === 'dislikes' ? 'selected' : '' ?>>👎 Dislikes Only</option>
        </select>
    </label>

    <label>
        Search Queries & Responses
        <input type="text" name="search" placeholder="Type user queries, responses..." value="<?= adminH($searchQuery) ?>">
    </label>

    <button type="submit" class="button button-primary button-small" style="height: 46px;"><i class="fas fa-search"></i> Filter</button>
    <?php if ($ratingFilter !== 'all' || $searchQuery !== ''): ?>
        <a href="admin-chatbot-feedback.php" class="button button-light button-small" style="height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;"><i class="fas fa-times"></i> Reset</a>
    <?php endif; ?>
</form>

<!-- Logs Table -->
<div class="table-card" style="margin-bottom: 30px;">
    <?php if (count($logs) > 0): ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr>
                    <th style="width: 10%;">ID & Date</th>
                    <th style="width: 20%;">User / Customer</th>
                    <th style="width: 30%;">User Query</th>
                    <th style="width: 25%;">AI Agent Response</th>
                    <th style="text-align: center; width: 10%;">Feedback</th>
                    <th style="text-align: center; width: 5%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; color: var(--text-dim);">
                            #<?= $log['id'] ?><br>
                            <span style="font-size: 0.72rem; color: var(--muted);"><?= date('m/d H:i', strtotime($log['created_at'])) ?></span>
                        </td>
                        <td>
                            <?php if ($log['client_id']): ?>
                                <strong style="color: var(--white); display: block; font-size: 0.88rem;"><?= adminH($log['client_name']) ?></strong>
                                <span style="font-size: 0.78rem; color: var(--cyan); display: block;"><?= adminH($log['client_email']) ?></span>
                            <?php else: ?>
                                <span style="color: var(--muted); font-style: italic; font-size: 0.82rem;">Anonymous Guest</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text); vertical-align: top; max-width: 250px; word-wrap: break-word; white-space: normal; line-height: 1.4;">
                            <?= adminH($log['query']) ?>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-dim); vertical-align: top; max-width: 300px; word-wrap: break-word; white-space: normal; line-height: 1.4;">
                            <?= nl2br(adminH($log['response'])) ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php if ((int)$log['rating'] === 1): ?>
                                <span class="status-badge is-good" style="min-width: 90px; padding: 4px 10px;">
                                    <i class="fas fa-thumbs-up" style="margin-right: 4px;"></i> Like
                                </span>
                            <?php else: ?>
                                <span class="status-badge is-danger" style="min-width: 90px; padding: 4px 10px;">
                                    <i class="fas fa-thumbs-down" style="margin-right: 4px;"></i> Dislike
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <a href="admin-chatbot-feedback.php?action=delete&id=<?= $log['id'] ?>" class="button button-danger button-small" onclick="return confirm('Delete this rating record?');" style="min-height: 32px; padding: 4px 10px; display: inline-flex; align-items: center;" title="Delete Interaction Log">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: var(--text-dim);">
            <i class="fas fa-comments-slash" style="font-size: 3rem; color: #444; margin-bottom: 15px; display: block;"></i>
            No chatbot interaction logs match the filter criteria.
        </div>
    <?php endif; ?>
</div>

<?php
adminPageEnd();
