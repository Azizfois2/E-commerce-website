<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();

// Ensure feedback table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            type ENUM('suggestion', 'bug', 'compliment', 'complaint', 'question', 'other') NOT NULL,
            rating TINYINT NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'reviewed', 'resolved', 'archived') DEFAULT 'new',
            admin_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_type (type),
            INDEX idx_created (created_at),
            FOREIGN KEY (client_id) REFERENCES Client(id_client) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    // Table might already exist
}
adminEnsureFeedbackTestimonialApprovalsTable($pdo);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['feedback_id'], $_POST['status'])) {
        $feedbackId = (int) $_POST['feedback_id'];
        $status = $_POST['status'];
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $validStatuses = ['new', 'reviewed', 'resolved', 'archived'];
        if (!in_array($status, $validStatuses, true)) {
            header('Location: admin-feedback.php?error=invalid_status');
            exit;
        }
        
        $pdo->exec(
            "UPDATE customer_feedback SET status = " . $pdo->quote($status) .
            ", admin_notes = " . $pdo->quote($adminNotes) .
            " WHERE id = " . (int) $feedbackId
        );
        adminLogActivity($pdo, 'update', 'customer_feedback', $feedbackId, "Feedback marked {$status}");
        
        header('Location: admin-feedback.php?updated=1');
        exit;
    } elseif ($_POST['action'] === 'request_testimonial_approval' && isset($_POST['feedback_id'])) {
        $feedbackId = (int) $_POST['feedback_id'];
        $result = adminCreateFeedbackApprovalRequest($pdo, $feedbackId);

        if ($result['success']) {
            header('Location: admin-feedback.php?approval_sent=1');
            exit;
        }

        header('Location: admin-feedback.php?approval_error=' . urlencode((string) $result['message']));
        exit;
    }
}

// Get filter parameters
$filterStatus = $_GET['status'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Build query
$where = [];
$validStatuses = ['new', 'reviewed', 'resolved', 'archived'];
$validTypes = ['suggestion', 'bug', 'compliment', 'complaint', 'question', 'other'];

if ($filterStatus !== 'all' && in_array($filterStatus, $validStatuses, true)) {
    $where[] = 'f.status = ' . $pdo->quote($filterStatus);
}

if ($filterType !== 'all' && in_array($filterType, $validTypes, true)) {
    $where[] = 'f.type = ' . $pdo->quote($filterType);
}

if ($search !== '') {
    $searchTerm = $pdo->quote('%' . $search . '%');
    $where[] = "(f.name LIKE {$searchTerm} OR f.email LIKE {$searchTerm} OR f.message LIKE {$searchTerm})";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get feedback
$feedbackSql = "
    SELECT
        f.*,
        c.nom AS client_name,
        ta.testimonial_approval_status,
        ta.testimonial_approval_expires_at,
        ta.testimonial_homepage_review_id
    FROM customer_feedback f
    LEFT JOIN Client c ON c.id_client = f.client_id
    LEFT JOIN (
        SELECT
            a.feedback_id,
            a.status AS testimonial_approval_status,
            a.expires_at AS testimonial_approval_expires_at,
            a.homepage_review_id AS testimonial_homepage_review_id
        FROM feedback_testimonial_approvals a
        INNER JOIN (
            SELECT feedback_id, MAX(id) AS latest_id
            FROM feedback_testimonial_approvals
            GROUP BY feedback_id
        ) latest ON latest.latest_id = a.id
    ) ta ON ta.feedback_id = f.id
    $whereClause
    ORDER BY 
        CASE f.status 
            WHEN 'new' THEN 1 
            WHEN 'reviewed' THEN 2 
            WHEN 'resolved' THEN 3 
            WHEN 'archived' THEN 4 
        END,
        f.created_at DESC
";
$feedbackList = $pdo->query($feedbackSql)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => (int) $pdo->query('SELECT COUNT(*) FROM customer_feedback')->fetchColumn(),
    'new' => (int) $pdo->query("SELECT COUNT(*) FROM customer_feedback WHERE status = 'new'")->fetchColumn(),
    'reviewed' => (int) $pdo->query("SELECT COUNT(*) FROM customer_feedback WHERE status = 'reviewed'")->fetchColumn(),
    'resolved' => (int) $pdo->query("SELECT COUNT(*) FROM customer_feedback WHERE status = 'resolved'")->fetchColumn(),
    'avg_rating' => (float) $pdo->query('SELECT COALESCE(AVG(rating), 0) FROM customer_feedback')->fetchColumn(),
];

adminPageStart('Customer Feedback', 'feedback');
?>

<style>
    .feedback-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--cyan);
        font-family: 'JetBrains Mono', monospace;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-dim);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 8px;
    }
    
    .feedback-filters {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        padding: 20px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
    }
    
    .feedback-filters select,
    .feedback-filters input {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text);
        font-family: inherit;
    }
    
    .feedback-filters input[type="search"] {
        flex: 1;
        min-width: 250px;
    }
    
    .feedback-list {
        display: grid;
        gap: 16px;
    }
    
    .feedback-item {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s ease;
    }
    
    .feedback-item:hover {
        border-color: var(--cyan);
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 16px;
        gap: 16px;
    }
    
    .feedback-meta {
        flex: 1;
    }
    
    .feedback-name {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text);
        margin-bottom: 4px;
    }
    
    .feedback-email {
        color: var(--text-dim);
        font-size: 0.9rem;
        font-family: 'JetBrains Mono', monospace;
    }
    
    .feedback-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .badge-type {
        background: rgba(0,245,212,0.1);
        color: var(--cyan);
        border: 1px solid rgba(0,245,212,0.2);
    }
    
    .badge-status {
        border: 1px solid;
    }
    
    .badge-status.new {
        background: rgba(255,193,7,0.1);
        color: #ffc107;
        border-color: rgba(255,193,7,0.3);
    }
    
    .badge-status.reviewed {
        background: rgba(33,150,243,0.1);
        color: #2196f3;
        border-color: rgba(33,150,243,0.3);
    }
    
    .badge-status.resolved {
        background: rgba(76,175,80,0.1);
        color: #4caf50;
        border-color: rgba(76,175,80,0.3);
    }
    
    .badge-status.archived {
        background: rgba(158,158,158,0.1);
        color: #9e9e9e;
        border-color: rgba(158,158,158,0.3);
    }
    
    .rating-stars {
        display: flex;
        gap: 4px;
        color: var(--orange);
    }
    
    .feedback-message {
        background: var(--input-bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px;
        margin: 16px 0;
        line-height: 1.6;
        color: var(--text);
    }
    
    .feedback-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--border);
    }
    
    .feedback-date {
        color: var(--text-dim);
        font-size: 0.85rem;
    }
    
    .feedback-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .inline-feedback-action {
        margin: 0;
    }

    .approval-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid rgba(0,245,212,0.28);
        border-radius: 999px;
        background: rgba(0,245,212,0.08);
        color: var(--cyan);
        font-size: 0.82rem;
        font-weight: 700;
    }

    .approval-pill.pending {
        border-color: rgba(255, 183, 3, 0.35);
        background: rgba(255, 183, 3, 0.08);
        color: #ffb703;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--input-bg);
        color: var(--text);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-small:hover {
        border-color: var(--cyan);
        color: var(--cyan);
    }
    
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal-backdrop.active {
        display: flex;
    }
    
    .modal-content {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 32px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        margin-bottom: 24px;
    }
    
    .modal-header h2 {
        margin: 0 0 8px;
        color: var(--text);
    }

    .modal-helper {
        margin: 0;
        color: var(--text-dim);
        line-height: 1.5;
        font-size: 0.9rem;
    }

    .modal-context {
        display: inline-flex;
        margin-top: 10px;
        padding: 5px 10px;
        border: 1px solid rgba(0,245,212,0.2);
        border-radius: 999px;
        color: var(--cyan);
        background: rgba(0,245,212,0.06);
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text);
    }
    
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text);
        font-family: inherit;
    }
    
    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-dim);
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.3;
    }
</style>

<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Customer Insights')) ?></span>
        <h1><?= adminH(adminPhrase('Feedback Management')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Review customer feedback, suggestions, and bug reports to improve the platform.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">
            <i class="fas fa-arrow-left"></i> <?= adminH(adminPhrase('Back to Dashboard')) ?>
        </a>
    </div>
</section>

<!-- Statistics -->
<div class="feedback-stats">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label"><?= adminH(adminPhrase('Total Feedback')) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['new'] ?></div>
        <div class="stat-label"><?= adminH(adminPhrase('New')) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['reviewed'] ?></div>
        <div class="stat-label"><?= adminH(adminPhrase('Reviewed')) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['resolved'] ?></div>
        <div class="stat-label"><?= adminH(adminPhrase('Resolved')) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($stats['avg_rating'], 1) ?> <i class="fas fa-star" style="color: var(--orange); font-size: 1.2rem;"></i></div>
        <div class="stat-label"><?= adminH(adminPhrase('Avg Rating')) ?></div>
    </div>
</div>

<!-- Filters -->
<form class="feedback-filters" method="GET">
    <select name="status" onchange="this.form.submit()">
        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>><?= adminH(adminPhrase('All Status')) ?></option>
        <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>><?= adminH(adminPhrase('New')) ?></option>
        <option value="reviewed" <?= $filterStatus === 'reviewed' ? 'selected' : '' ?>><?= adminH(adminPhrase('Reviewed')) ?></option>
        <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>><?= adminH(adminPhrase('Resolved')) ?></option>
        <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>><?= adminH(adminPhrase('Archived')) ?></option>
    </select>
    
    <select name="type" onchange="this.form.submit()">
        <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>><?= adminH(adminPhrase('All Types')) ?></option>
        <option value="suggestion" <?= $filterType === 'suggestion' ? 'selected' : '' ?>><?= adminH(adminPhrase('Suggestions')) ?></option>
        <option value="bug" <?= $filterType === 'bug' ? 'selected' : '' ?>><?= adminH(adminPhrase('Bug Reports')) ?></option>
        <option value="compliment" <?= $filterType === 'compliment' ? 'selected' : '' ?>><?= adminH(adminPhrase('Compliments')) ?></option>
        <option value="complaint" <?= $filterType === 'complaint' ? 'selected' : '' ?>><?= adminH(adminPhrase('Complaints')) ?></option>
        <option value="question" <?= $filterType === 'question' ? 'selected' : '' ?>><?= adminH(adminPhrase('Questions')) ?></option>
        <option value="other" <?= $filterType === 'other' ? 'selected' : '' ?>><?= adminH(adminPhrase('Other')) ?></option>
    </select>
    
    <input type="search" name="search" placeholder="<?= adminH(adminPhrase('Search feedback...')) ?>" value="<?= adminH($search) ?>">
    
    <button type="submit" class="button button-primary">
        <i class="fas fa-search"></i> <?= adminH(adminPhrase('Filter')) ?>
    </button>
    
    <?php if ($filterStatus !== 'all' || $filterType !== 'all' || $search !== ''): ?>
        <a href="admin-feedback.php" class="button button-light">
            <i class="fas fa-times"></i> <?= adminH(adminPhrase('Clear')) ?>
        </a>
    <?php endif; ?>
</form>

<!-- Feedback List -->
<div class="feedback-list">
    <?php if (empty($feedbackList)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p><?= adminH(adminPhrase('No feedback found matching your filters.')) ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($feedbackList as $feedback): ?>
            <div class="feedback-item">
                <div class="feedback-header">
                    <div class="feedback-meta">
                        <div class="feedback-name">
                            <?= adminH($feedback['name']) ?>
                            <?php if ($feedback['client_name']): ?>
                                <span style="color: var(--cyan); font-size: 0.85rem;">(<?= adminH($feedback['client_name']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="feedback-email"><?= adminH($feedback['email']) ?></div>
                    </div>
                    <div class="feedback-badges">
                        <span class="badge badge-type"><?= adminH(adminLabelFromValue($feedback['type'])) ?></span>
                        <span class="badge badge-status <?= adminH($feedback['status']) ?>"><?= adminH(adminStatusLabel($feedback['status'])) ?></span>
                        <div class="rating-stars">
                            <?php for ($i = 0; $i < $feedback['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php for ($i = $feedback['rating']; $i < 5; $i++): ?>
                                <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="feedback-message">
                    <?= nl2br(adminH($feedback['message'])) ?>
                </div>
                
                <?php if ($feedback['admin_notes']): ?>
                    <div style="background: rgba(0,245,212,0.05); border: 1px solid rgba(0,245,212,0.2); border-radius: 8px; padding: 12px; margin-top: 12px;">
                        <strong style="color: var(--cyan); font-size: 0.85rem;"><?= adminH(adminPhrase('Admin Notes:')) ?></strong>
                        <p style="margin: 8px 0 0; color: var(--text);"><?= nl2br(adminH($feedback['admin_notes'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="feedback-footer">
                    <div class="feedback-date">
                        <i class="fas fa-clock"></i> <?= adminH(adminFormatDate($feedback['created_at'], 'datetime_full')) ?>
                    </div>
                    <div class="feedback-actions">
                        <?php
                        $approvalStatus = (string) ($feedback['testimonial_approval_status'] ?? '');
                        $approvalExpiry = (string) ($feedback['testimonial_approval_expires_at'] ?? '');
                        ?>
                        <?php if ($approvalStatus === 'accepted'): ?>
                            <span class="approval-pill" title="<?= adminH(adminPhrase('Customer approved this feedback for the homepage.')) ?>">
                                <i class="fas fa-check-circle"></i> <?= adminH(adminPhrase('Homepage Approved')) ?>
                            </span>
                        <?php elseif ($approvalStatus === 'pending'): ?>
                            <span class="approval-pill pending" title="<?= adminH(adminPhrase('Waiting for customer approval.')) ?>">
                                <i class="fas fa-clock"></i> <?= adminH(adminPhrase('Approval Pending')) ?>
                                <?php if ($approvalExpiry !== ''): ?>
                                    <?= adminH(adminPhrase('until')) ?> <?= adminH(adminFormatDate($approvalExpiry, 'date_short')) ?>
                                <?php endif; ?>
                            </span>
                            <form method="POST" class="inline-feedback-action" onsubmit="return confirm(<?= i18n_script_json(adminPhrase('Send a fresh approval email to this customer? The previous pending link will be cancelled.')) ?>);">
                                <input type="hidden" name="action" value="request_testimonial_approval">
                                <input type="hidden" name="feedback_id" value="<?= (int) $feedback['id'] ?>">
                                <button class="btn-small" type="submit" title="<?= adminH(adminPhrase('Send a new approval email')) ?>">
                                    <i class="fas fa-paper-plane"></i> <?= adminH(adminPhrase('Resend Approval')) ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="inline-feedback-action" onsubmit="return confirm(<?= i18n_script_json(adminPhrase('Email this customer to ask permission to publish their feedback on the homepage?')) ?>);">
                                <input type="hidden" name="action" value="request_testimonial_approval">
                                <input type="hidden" name="feedback_id" value="<?= (int) $feedback['id'] ?>">
                                <button class="btn-small" type="submit" title="<?= adminH(adminPhrase('Ask customer before publishing this as a homepage testimonial')) ?>">
                                    <i class="fas fa-paper-plane"></i> <?= adminH(adminPhrase('Request Homepage Approval')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <button class="btn-small" type="button" title="<?= adminH(adminPhrase('Update status and internal notes')) ?>" onclick="openUpdateModal(<?= (int) $feedback['id'] ?>, <?= json_encode($feedback['status']) ?>, <?= json_encode($feedback['admin_notes'] ?? '') ?>)">
                            <i class="fas fa-edit"></i> <?= adminH(adminPhrase('Update Status')) ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Update Modal -->
<div class="modal-backdrop" id="updateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?= adminH(adminPhrase('Update Feedback Status')) ?></h2>
            <p class="modal-helper"><?= adminH(adminPhrase('Use this to move feedback through your review workflow and keep private admin notes. It does not email the customer.')) ?></p>
            <span class="modal-context" id="modalFeedbackContext"><?= adminH(adminPhrase('Feedback')) ?></span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="feedback_id" id="modalFeedbackId">
            
            <div class="form-group">
                <label for="modalStatus"><?= adminH(adminPhrase('Status')) ?></label>
                <select name="status" id="modalStatus" required>
                    <option value="new"><?= adminH(adminPhrase('New')) ?></option>
                    <option value="reviewed"><?= adminH(adminPhrase('Reviewed')) ?></option>
                    <option value="resolved"><?= adminH(adminPhrase('Resolved')) ?></option>
                    <option value="archived"><?= adminH(adminPhrase('Archived')) ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modalAdminNotes"><?= adminH(adminPhrase('Admin Notes (Optional)')) ?></label>
                <textarea name="admin_notes" id="modalAdminNotes" placeholder="<?= adminH(adminPhrase('Add internal notes about this feedback...')) ?>"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="button button-light" onclick="closeUpdateModal()"><?= adminH(adminPhrase('Cancel')) ?></button>
                <button type="submit" class="button button-primary"><?= adminH(adminPhrase('Save Changes')) ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openUpdateModal(id, status, notes) {
    document.getElementById('modalFeedbackId').value = id;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalAdminNotes').value = notes || '';
    document.getElementById('modalFeedbackContext').textContent = <?= i18n_script_json(adminPhrase('Feedback #{id}')) ?>.replace('{id}', id);
    document.getElementById('updateModal').classList.add('active');
    document.getElementById('modalStatus').focus();
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.remove('active');
}

// Close modal on overlay click
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUpdateModal();
    }
});

// Show success message if updated
<?php if (isset($_GET['updated'])): ?>
    if (window.showToast) {
        window.showToast(<?= i18n_script_json(adminPhrase('Feedback status updated.')) ?>, 'success');
    } else {
        alert(<?= i18n_script_json(adminPhrase('Feedback status updated.')) ?>);
    }
<?php elseif (isset($_GET['approval_sent'])): ?>
    if (window.showToast) {
        window.showToast(<?= i18n_script_json(adminPhrase('Approval email sent. The feedback will publish only after the customer accepts.')) ?>, 'success');
    } else {
        alert(<?= i18n_script_json(adminPhrase('Approval email sent. The feedback will publish only after the customer accepts.')) ?>);
    }
<?php elseif (isset($_GET['approval_error'])): ?>
    alert(<?= json_encode((string) ($_GET['approval_error'] ?? adminPhrase('Could not send approval email.'))) ?>);
<?php elseif (isset($_GET['error'])): ?>
    alert(<?= i18n_script_json(adminPhrase('Could not update feedback: invalid status.')) ?>);
<?php endif; ?>
</script>

<?php adminPageEnd(); ?>
