<?php
require_once 'admin-helpers.php';

adminRequireAuth();
$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$message = '';
$error = '';
$segments = adminCustomerSegmentCounts($pdo);
$recipientCounts = array_map(static fn($segment) => (int) $segment['count'], $segments);
$testRecipient = trim((string) ($_SESSION['admin_email'] ?? ''));
if ($testRecipient === '' || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
    $testRecipient = defined('SMTP_FROM') ? (string) SMTP_FROM : '';
}
$formValues = [
    'recipients_type' => adminNormalizeRecipientsType($_POST['recipients_type'] ?? 'all'),
    'scheduled_at' => trim((string) ($_POST['scheduled_at'] ?? date('Y-m-d\TH:i'))),
    'subject' => trim((string) ($_POST['subject'] ?? '')),
    'content' => trim((string) ($_POST['content'] ?? '')),
];

// Handle form submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_campaign'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = "Invalid CSRF token.";
    } else {
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            $error = "Invalid campaign.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM scheduled_emails WHERE id = ?");
            $stmt->execute([$campaignId]);
            adminLogActivity($pdo, 'delete', 'campaign', $campaignId, "Deleted scheduled email #{$campaignId}");
            header('Location: admin-marketing.php?deleted=1');
            exit();
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['send_test_email'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = "Invalid CSRF token.";
    } else {
        $subject = $formValues['subject'];
        $content = $formValues['content'];

        if ($testRecipient === '' || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            $error = "No valid admin email is available for the test send.";
        } elseif (empty($subject) || empty($content)) {
            $error = "Subject and content are required before sending a test.";
        } else {
            require_once 'mailer.php';
            $htmlBody = emailTemplate(adminH($subject), $content);
            if (sendEmail($testRecipient, '[TEST] ' . $subject, $htmlBody)) {
                $message = "Test email sent to {$testRecipient}.";
                adminLogActivity($pdo, 'test_send', 'campaign', null, "Sent marketing test email: {$subject}");
            } else {
                $lastError = function_exists('lastMailError') ? lastMailError() : null;
                $error = "Test email failed" . ($lastError ? ": {$lastError}" : ".");
            }
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['send_email'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = "Invalid CSRF token.";
    } else {
        $subject = $formValues['subject'];
        $content = $formValues['content'];
        $scheduled_at = $formValues['scheduled_at'];
        $recipients_type = $formValues['recipients_type'];

        if (empty($subject) || empty($content) || empty($scheduled_at)) {
            $error = "All fields are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO scheduled_emails (subject, content, scheduled_at, recipients_type, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$subject, $content, $scheduled_at, $recipients_type]);
                adminLogActivity($pdo, 'schedule', 'campaign', (int) $pdo->lastInsertId(), "Scheduled campaign '{$subject}' to {$recipients_type}");
                header('Location: admin-marketing.php?success=1');
                exit();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch scheduled emails
$scheduledEmails = adminFetchAll($pdo, "SELECT * FROM scheduled_emails ORDER BY created_at DESC");

adminPageStart('Marketing & Emails', 'marketing');
?>

<section class="section-heading">
    <div>
        <span class="eyebrow">Engagement Tools</span>
        <h1>Marketing Emails</h1>
        <p class="section-copy">Draft, schedule, and send mass emails to your customers and newsletter subscribers.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <button class="button button-special" id="processQueueBtn">
            <i class="fas fa-sync" id="syncIcon"></i> <span>Process Email Queue</span>
        </button>
        <button class="button button-primary" onclick="document.getElementById('emailForm').scrollIntoView({behavior: 'smooth'})">
            <i class="fas fa-plus"></i> New Campaign
        </button>
    </div>
</section>

<script>
document.getElementById('processQueueBtn').addEventListener('click', async function() {
    const btn = this;
    const icon = document.getElementById('syncIcon');
    
    if (btn.disabled) return;
    
    btn.disabled = true;
    icon.classList.add('fa-spin');
    
    try {
        const response = await fetch('api/process-email-queue.php');
        const result = await response.json();
        
        if (result.status === 'success') {
            alert(`Sent ${result.sent_count} emails for campaign #${result.campaign_id}.`);
            location.reload();
        } else if (result.status === 'idle') {
            alert('No pending campaigns ready to send.');
        } else {
            alert('Error processing queue: ' + (result.message || result.errors?.join(', ') || 'Unknown error'));
            location.reload();
        }
    } catch (err) {
        console.error(err);
        alert('Failed to connect to the mail server.');
    } finally {
        btn.disabled = false;
        icon.classList.remove('fa-spin');
    }
});
</script>

<?php if (isset($_GET['success'])): ?>
    <div class="admin-alert success">Campaign scheduled successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="admin-alert success">Campaign deleted successfully.</div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="admin-alert success"><?= adminH($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="admin-alert error"><?= adminH($error) ?></div>
<?php endif; ?>

<div class="admin-grid marketing-grid">
    <!-- New Email Form -->
    <div class="admin-card campaign-editor">
        <div class="card-header">
            <h3><i class="fas fa-paper-plane"></i> New Email Campaign</h3>
            <p>Compose your message and schedule the broadcast.</p>
        </div>
        <form method="post" class="premium-form" id="emailForm">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-users"></i> Recipients Target</label>
                    <select name="recipients_type" class="premium-input" id="recipientsType">
                        <?php foreach ($segments as $key => $segment): ?>
                            <option value="<?= adminH($key) ?>" <?= $formValues['recipients_type'] === $key ? 'selected' : '' ?>>
                                <?= adminH($segment['label']) ?> (<?= (int) $segment['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Schedule Send Time</label>
                    <input type="datetime-local" name="scheduled_at" class="premium-input" value="<?= adminH($formValues['scheduled_at']) ?>" required>
                </div>
            </div>
            <div class="recipient-insight" id="recipientInsight" data-counts='<?= adminH(json_encode($recipientCounts, JSON_THROW_ON_ERROR)) ?>'>
                <span><i class="fas fa-users-viewfinder"></i> Estimated recipients</span>
                <strong id="recipientCount"><?= (int) ($recipientCounts[$formValues['recipients_type']] ?? 0) ?></strong>
            </div>
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Email Subject</label>
                <input type="text" name="subject" id="campaignSubject" class="premium-input" placeholder="e.g. Special Weekend Promotion!" value="<?= adminH($formValues['subject']) ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Email Content (HTML Supported)</label>
                <div class="editor-container">
                    <textarea name="content" id="campaignContent" class="premium-input premium-textarea" placeholder="Write your message here..." required><?= adminH($formValues['content']) ?></textarea>
                    <div class="editor-hints">
                        <span><i class="fab fa-html5"></i> HTML active</span>
                        <span><i class="fas fa-magic"></i> Template applied automatically</span>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="button button-light" id="previewCampaignBtn">
                    <i class="fas fa-eye"></i> Preview
                </button>
                <button type="submit" name="send_test_email" class="button button-special" <?= $testRecipient === '' ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane"></i> Send Test
                </button>
                <button type="submit" name="send_email" class="button button-primary">
                    <i class="fas fa-calendar-check"></i> Schedule Campaign
                </button>
            </div>
        </form>
        <div class="campaign-preview" id="campaignPreview" hidden>
            <div class="preview-title">
                <span>Email Preview</span>
                <button type="button" class="preview-close" id="closePreviewBtn" aria-label="Close preview"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="preview-subject" id="previewSubject">Untitled campaign</div>
            <div class="preview-body" id="previewBody"></div>
        </div>
    </div>

    <!-- Queue List -->
    <div class="admin-card campaign-history">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Campaigns</h3>
            <p>Track delivery status and engagement metrics.</p>
        </div>
        <div class="campaign-list">
            <?php if (empty($scheduledEmails)): ?>
                <div class="empty-state">No campaigns found.</div>
            <?php else: ?>
                <?php foreach ($scheduledEmails as $email): ?>
                    <?php
                        $status = (string) ($email['status'] ?? 'pending');
                        $sentCount = (int) ($email['sent_count'] ?? 0);
                        $totalRecipients = (int) ($email['total_recipients'] ?? 0);
                        if ($totalRecipients <= 0 && in_array($status, ['pending', 'sending'], true)) {
                            $totalRecipients = adminMarketingRecipientCount($pdo, $email['recipients_type'] ?? 'all');
                        }
                    ?>
                    <article class="campaign-row">
                        <div class="campaign-main">
                            <span class="campaign-subject"><?= adminH($email['subject']) ?></span>
                            <span class="campaign-date">Created <?= date('M j, Y', strtotime($email['created_at'])) ?></span>
                        </div>
                        <span class="status-pill status-<?= adminH($status) ?>">
                            <i class="fas <?= $status === 'sent' ? 'fa-check-circle' : ($status === 'failed' ? 'fa-exclamation-circle' : 'fa-clock') ?>"></i>
                            <?= adminH(ucfirst($status)) ?>
                        </span>
                        <div class="campaign-meta">
                            <span><?= date('M j, Y', strtotime($email['scheduled_at'])) ?></span>
                            <small><?= date('H:i', strtotime($email['scheduled_at'])) ?></small>
                        </div>
                        <div class="delivery-stat" title="<?= adminH((string) ($email['error_message'] ?? '')) ?>">
                            <?php if ($totalRecipients > 0): ?>
                                <strong><?= $sentCount ?></strong>
                                <small>/ <?= $totalRecipients ?></small>
                            <?php else: ?>
                                <span class="delivery-empty">No recipients</span>
                            <?php endif; ?>
                        </div>
                        <form method="post" class="campaign-delete-form" onsubmit="return confirm('Delete this scheduled email?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="campaign_id" value="<?= (int) $email['id'] ?>">
                            <button type="submit" name="delete_campaign" class="button button-danger button-small campaign-delete-btn" aria-label="Delete campaign">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const recipientSelect = document.getElementById('recipientsType');
const recipientInsight = document.getElementById('recipientInsight');
const recipientCount = document.getElementById('recipientCount');
const subjectInput = document.getElementById('campaignSubject');
const contentInput = document.getElementById('campaignContent');
const preview = document.getElementById('campaignPreview');
const previewSubject = document.getElementById('previewSubject');
const previewBody = document.getElementById('previewBody');
const previewBtn = document.getElementById('previewCampaignBtn');
const closePreviewBtn = document.getElementById('closePreviewBtn');
const recipientCounts = JSON.parse(recipientInsight.dataset.counts || '{}');

function updateRecipientEstimate() {
    const count = recipientCounts[recipientSelect.value] ?? 0;
    recipientCount.textContent = new Intl.NumberFormat().format(count);
    recipientInsight.classList.toggle('is-empty', count <= 0);
}

function updatePreview() {
    previewSubject.textContent = subjectInput.value.trim() || 'Untitled campaign';
    previewBody.innerHTML = contentInput.value.trim() || '<p class="preview-muted">Your email content preview will appear here.</p>';
    preview.hidden = false;
}

recipientSelect.addEventListener('change', updateRecipientEstimate);
previewBtn.addEventListener('click', updatePreview);
closePreviewBtn.addEventListener('click', () => { preview.hidden = true; });
subjectInput.addEventListener('input', () => { if (!preview.hidden) updatePreview(); });
contentInput.addEventListener('input', () => { if (!preview.hidden) updatePreview(); });
updateRecipientEstimate();
</script>

<style>
/* ── Premium Marketing Styles ────────────────────────── */
.marketing-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.12fr) minmax(460px, 0.88fr);
    gap: 24px;
    align-items: start;
}

@media (max-width: 1320px) {
    .marketing-grid { grid-template-columns: 1fr; }
}

.campaign-editor,
.campaign-history {
    min-width: 0;
}

.admin-card {
    background: var(--page-bg-2);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.card-header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(to right, rgba(0,245,212,0.03), transparent);
}

.card-header h3 {
    margin: 0 0 6px;
    font-size: 1.1rem;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-header h3 i { color: var(--cyan); }
.card-header p { margin: 0; font-size: 0.85rem; color: var(--muted); }

/* Form Styles */
.premium-form { padding: 28px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 24px; }
.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-dim);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.form-group label i { color: var(--cyan); opacity: 0.7; }

.premium-input {
    width: 100%;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 18px;
    color: var(--text);
    font-family: 'Syne', sans-serif;
    font-size: 0.95rem;
    transition: all 0.25s ease;
}

.premium-input:focus {
    border-color: var(--cyan);
    background: rgba(0,245,212,0.02);
    box-shadow: 0 0 15px rgba(0,245,212,0.08);
    outline: none;
}

/* Fix for unreadable select options in dark mode */
.premium-input option {
    background-color: var(--page-bg-2);
    color: var(--text);
}

.premium-textarea {
    min-height: 280px;
    resize: vertical;
    line-height: 1.6;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.88rem;
}

.editor-container { position: relative; }
.editor-hints {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 8px;
}
.editor-hints span {
    font-size: 0.7rem;
    color: var(--muted);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.recipient-insight {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin: -4px 0 24px;
    padding: 12px 14px;
    background: rgba(0,245,212,0.04);
    border: 1px solid rgba(0,245,212,0.14);
    border-radius: 12px;
}

.recipient-insight span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dim);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.recipient-insight i,
.recipient-insight strong {
    color: var(--cyan);
}

.recipient-insight strong {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.05rem;
}

.recipient-insight.is-empty {
    background: rgba(255, 61, 90, 0.05);
    border-color: rgba(255, 61, 90, 0.18);
}

.recipient-insight.is-empty strong { color: var(--red); }

.form-actions {
    display: grid;
    grid-template-columns: minmax(110px, 0.7fr) minmax(130px, 0.8fr) minmax(170px, 1fr);
    gap: 10px;
    margin-top: 8px;
}

.form-actions .button {
    width: 100%;
    min-height: 46px;
    justify-content: center;
}

.campaign-preview {
    margin: 0 28px 28px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--input-bg);
    overflow: hidden;
}

.campaign-preview[hidden] { display: none; }

.preview-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    color: var(--text-dim);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.preview-close {
    display: inline-grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: transparent;
    color: var(--text-dim);
    cursor: pointer;
}

.preview-close:hover {
    color: var(--cyan);
    border-color: rgba(0,245,212,0.35);
}

.preview-subject {
    padding: 18px 18px 0;
    color: var(--cyan);
    font-weight: 800;
    font-size: 1.05rem;
}

.preview-body {
    padding: 16px 18px 20px;
    color: var(--text);
    line-height: 1.65;
    overflow-wrap: anywhere;
}

.preview-body p { margin: 0 0 12px; }
.preview-body p:last-child { margin-bottom: 0; }
.preview-muted { color: var(--muted); }

/* Campaign List */
.campaign-list {
    display: grid;
}

.campaign-row {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) auto minmax(92px, 0.7fr) minmax(78px, 0.5fr) 42px;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
}

.campaign-row:last-child { border-bottom: none; }
.campaign-row:hover { background: rgba(0,245,212,0.015); }

.campaign-main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.campaign-subject { font-weight: 700; color: var(--text); font-size: 0.95rem; }
.campaign-date { font-size: 0.75rem; color: var(--muted); }

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.2); }
.status-sent { background: rgba(0, 230, 118, 0.1); color: var(--green); border: 1px solid rgba(0, 230, 118, 0.2); }
.status-failed { background: rgba(255, 61, 90, 0.1); color: var(--red); border: 1px solid rgba(255, 61, 90, 0.2); }
.status-sending { background: rgba(0, 245, 212, 0.1); color: var(--cyan); border: 1px solid rgba(0, 245, 212, 0.2); }

.campaign-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    color: var(--text-dim);
    font-size: 0.84rem;
}

.campaign-meta small {
    color: var(--muted);
    font-size: 0.72rem;
}

.delivery-stat { display: flex; align-items: baseline; gap: 4px; }
.delivery-stat strong { font-family: 'JetBrains Mono', monospace; color: var(--cyan); }
.delivery-stat small { color: var(--muted); font-size: 0.75rem; }
.delivery-empty {
    display: inline-flex;
    color: var(--muted);
    font-size: 0.76rem;
    font-weight: 700;
    white-space: nowrap;
}

.campaign-delete-form {
    display: inline-flex;
    margin: 0;
}

.campaign-delete-btn {
    min-width: 38px;
    padding-inline: 10px;
}

.empty-state { text-align: center; padding: 60px !important; color: var(--muted); font-style: italic; }
.btn-block { width: 100%; margin-top: 10px; }

.button-special {
    background: rgba(0, 245, 212, 0.08);
    border: 1px solid var(--cyan);
    color: var(--cyan);
    box-shadow: 0 4px 15px rgba(0, 245, 212, 0.1);
}

.button-special:hover {
    background: var(--cyan);
    color: #000;
    box-shadow: 0 8px 25px rgba(0, 245, 212, 0.3);
}

.button-special i { margin-right: 8px; }

@media (max-width: 760px) {
    .campaign-row {
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
    }

    .campaign-main,
    .campaign-meta,
    .delivery-stat {
        grid-column: 1;
    }

    .status-pill {
        grid-column: 2;
        grid-row: 1;
        justify-self: end;
    }

    .campaign-delete-form {
        grid-column: 2;
        grid-row: 2 / span 2;
        justify-self: end;
    }
}

@media (max-width: 700px) {
    .form-row { grid-template-columns: 1fr; }
    .card-header,
    .premium-form { padding: 22px; }
    .form-actions { grid-template-columns: 1fr; }
    .campaign-preview { margin: 0 22px 22px; }
}
</style>

<?php adminPageEnd(); ?>
