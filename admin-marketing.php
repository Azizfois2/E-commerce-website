<?php
require_once 'admin-helpers.php';
require_once __DIR__ . '/includes/i18n.php';

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
$reviewTranslationLocales = array_filter(
    i18n_locale_labels(),
    static fn($locale): bool => $locale !== I18N_DEFAULT_LOCALE,
    ARRAY_FILTER_USE_KEY
);
$reviewTranslationColumns = [];
foreach ($reviewTranslationLocales as $locale => $label) {
    if (!preg_match('/^[a-z]{2}$/', (string) $locale)) {
        continue;
    }
    $reviewTranslationColumns[] = "reviewer_name_{$locale}";
    $reviewTranslationColumns[] = "reviewer_role_{$locale}";
    $reviewTranslationColumns[] = "quote_{$locale}";
}

// Handle form submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_gamer_review'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = adminPhrase('Invalid CSRF token.');
    } else {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $name = trim((string) ($_POST['reviewer_name'] ?? ''));
        $role = trim((string) ($_POST['reviewer_role'] ?? ''));
        $quote = trim((string) ($_POST['quote'] ?? ''));
        $localizedReview = [];
        foreach ($reviewTranslationLocales as $locale => $label) {
            $localizedReview["reviewer_name_{$locale}"] = trim((string) ($_POST["reviewer_name_{$locale}"] ?? '')) ?: null;
            $localizedReview["reviewer_role_{$locale}"] = trim((string) ($_POST["reviewer_role_{$locale}"] ?? '')) ?: null;
            $localizedReview["quote_{$locale}"] = trim((string) ($_POST["quote_{$locale}"] ?? '')) ?: null;
        }
        $avatar = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($_POST['avatar_initials'] ?? '')), 0, 4));
        $rating = max(1, min(5, (float) ($_POST['rating'] ?? 5)));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $role === '' || $quote === '') {
            $error = adminPhrase('Name, role, and quote are required.');
        } elseif ($reviewId > 0) {
            $translationAssignments = array_map(
                static fn(string $column): string => "{$column} = ?",
                $reviewTranslationColumns
            );
            $translationValues = array_map(
                static fn(string $column) => $localizedReview[$column] ?? null,
                $reviewTranslationColumns
            );
            $setParts = array_merge(
                ['reviewer_name = ?', 'reviewer_role = ?', 'quote = ?'],
                $translationAssignments,
                ['avatar_initials = ?', 'rating = ?', 'sort_order = ?', 'is_active = ?']
            );
            $stmt = $pdo->prepare("
                UPDATE homepage_gamer_reviews
                SET " . implode(', ', $setParts) . "
                WHERE id = ?
            ");
            $stmt->execute(array_merge(
                [$name, $role, $quote],
                $translationValues,
                [$avatar ?: null, $rating, $sortOrder, $isActive, $reviewId]
            ));
            adminLogActivity($pdo, 'update', 'homepage_gamer_review', $reviewId, "Updated homepage gamer review for {$name}");
            header('Location: admin-marketing.php?review_saved=1');
            exit();
        } else {
            $insertColumns = array_merge(
                ['reviewer_name', 'reviewer_role', 'quote'],
                $reviewTranslationColumns,
                ['avatar_initials', 'rating', 'sort_order', 'is_active']
            );
            $translationValues = array_map(
                static fn(string $column) => $localizedReview[$column] ?? null,
                $reviewTranslationColumns
            );
            $stmt = $pdo->prepare("
                INSERT INTO homepage_gamer_reviews (" . implode(', ', $insertColumns) . ")
                VALUES (" . implode(', ', array_fill(0, count($insertColumns), '?')) . ")
            ");
            $stmt->execute(array_merge(
                [$name, $role, $quote],
                $translationValues,
                [$avatar ?: null, $rating, $sortOrder, $isActive]
            ));
            adminLogActivity($pdo, 'create', 'homepage_gamer_review', (int) $pdo->lastInsertId(), "Added homepage gamer review for {$name}");
            header('Location: admin-marketing.php?review_saved=1');
            exit();
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_gamer_review'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = adminPhrase('Invalid CSRF token.');
    } else {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $stmt = $pdo->prepare("DELETE FROM homepage_gamer_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            adminLogActivity($pdo, 'delete', 'homepage_gamer_review', $reviewId, "Deleted homepage gamer review #{$reviewId}");
        }
        header('Location: admin-marketing.php?review_deleted=1');
        exit();
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['create_flash_sale_event'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = adminPhrase('Invalid CSRF token.');
    } else {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $salePrice = (float) ($_POST['sale_price'] ?? 0);
        $maxQty = trim((string) ($_POST['max_quantity'] ?? ''));
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $eventName = trim((string) ($_POST['event_name'] ?? ''));
        $eventBadge = trim((string) ($_POST['event_badge'] ?? ''));
        $eventNote = trim((string) ($_POST['event_note'] ?? ''));
        $product = null;

        if ($productId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if (!$product || $salePrice <= 0 || $startsAt === '' || $endsAt === '') {
            $error = adminPhrase('Product, sale price, start, and end are required.');
        } elseif ($salePrice >= (float) $product['price']) {
            $error = adminPhrase('Sale price must be lower than current price.');
        } elseif (strtotime($endsAt) <= strtotime($startsAt)) {
            $error = adminPhrase('End time must be after start time.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO flash_sales (product_id, sale_price, original_price, max_quantity, event_name, event_badge, event_note, starts_at, ends_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $salePrice,
                $product['price'],
                $maxQty !== '' ? (int) $maxQty : null,
                $eventName !== '' ? $eventName : null,
                $eventBadge !== '' ? $eventBadge : null,
                $eventNote !== '' ? $eventNote : null,
                str_replace('T', ' ', $startsAt),
                str_replace('T', ' ', $endsAt),
            ]);
            adminLogActivity($pdo, 'create', 'flash_sale', (int) $pdo->lastInsertId(), "Declared special event deal for {$product['name']}");
            header('Location: admin-marketing.php?flash_saved=1');
            exit();
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_flash_sale'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = adminPhrase('Invalid CSRF token.');
    } else {
        $flashId = (int) ($_POST['flash_sale_id'] ?? 0);
        if ($flashId > 0) {
            $stmt = $pdo->prepare("DELETE FROM flash_sales WHERE id = ?");
            $stmt->execute([$flashId]);
            adminLogActivity($pdo, 'delete', 'flash_sale', $flashId, "Deleted flash sale #{$flashId}");
        }
        header('Location: admin-marketing.php?flash_deleted=1');
        exit();
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_campaign'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
            $error = adminPhrase('Invalid CSRF token.');
    } else {
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            $error = adminPhrase('Invalid campaign.');
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
            $error = adminPhrase('Invalid CSRF token.');
    } else {
        $subject = $formValues['subject'];
        $content = $formValues['content'];

        if ($testRecipient === '' || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            $error = adminPhrase('No valid admin email is available for the test send.');
        } elseif (empty($subject) || empty($content)) {
            $error = adminPhrase('Subject and content are required before sending a test.');
        } else {
            require_once 'mailer.php';
            $htmlBody = emailTemplate(adminH($subject), $content);
            if (sendEmail($testRecipient, '[TEST] ' . $subject, $htmlBody)) {
                $message = adminPhrase('Test email sent to {email}.', ['email' => $testRecipient]);
                adminLogActivity($pdo, 'test_send', 'campaign', null, "Sent marketing test email: {$subject}");
            } else {
                $lastError = function_exists('lastMailError') ? lastMailError() : null;
                $error = adminPhrase('Test email failed') . ($lastError ? ": {$lastError}" : ".");
            }
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['send_email'])) {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
            $error = adminPhrase('Invalid CSRF token.');
    } else {
        $subject = $formValues['subject'];
        $content = $formValues['content'];
        $scheduled_at = $formValues['scheduled_at'];
        $recipients_type = $formValues['recipients_type'];

        if (empty($subject) || empty($content) || empty($scheduled_at)) {
            $error = adminPhrase('All fields are required.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO scheduled_emails (subject, content, scheduled_at, recipients_type, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$subject, $content, $scheduled_at, $recipients_type]);
                adminLogActivity($pdo, 'schedule', 'campaign', (int) $pdo->lastInsertId(), "Scheduled campaign '{$subject}' to {$recipients_type}");
                header('Location: admin-marketing.php?success=1');
                exit();
            } catch (Exception $e) {
                $error = adminPhrase('Error:') . ' ' . $e->getMessage();
            }
        }
    }
}

// Fetch scheduled emails
$scheduledEmails = adminFetchAll($pdo, "SELECT * FROM scheduled_emails ORDER BY created_at DESC");
$homepageGamerReviews = adminFetchAll($pdo, "
    SELECT *
    FROM homepage_gamer_reviews
    ORDER BY is_active DESC, sort_order ASC, created_at DESC
");
$flashSaleProducts = adminFetchAll($pdo, "
    SELECT id, name, price
    FROM products
    WHERE inStock = 1 OR stock_quantity > 0
    ORDER BY name ASC
");
$flashSales = adminFetchAll($pdo, "
    SELECT fs.*, p.name AS product_name
    FROM flash_sales fs
    JOIN products p ON p.id = fs.product_id
    ORDER BY fs.ends_at DESC
");

adminPageStart('Marketing & Emails', 'marketing');
?>

<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Engagement Tools')) ?></span>
        <h1><?= adminH(adminPhrase('Marketing Emails')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Draft, schedule, and send mass emails to your customers and newsletter subscribers.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><i class="fas fa-arrow-left"></i> <?= adminH(adminPhrase('Dashboard')) ?></a>
        <button class="button button-special" id="processQueueBtn">
            <i class="fas fa-sync" id="syncIcon"></i> <span><?= adminH(adminPhrase('Process Email Queue')) ?></span>
        </button>
        <button class="button button-primary" onclick="document.getElementById('emailForm').scrollIntoView({behavior: 'smooth'})">
            <i class="fas fa-plus"></i> <?= adminH(adminPhrase('New Campaign')) ?>
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
            alert(<?= i18n_script_json(adminPhrase('Sent {count} emails for campaign #{id}.')) ?>.replace('{count}', result.sent_count).replace('{id}', result.campaign_id));
            location.reload();
        } else if (result.status === 'idle') {
            alert(<?= i18n_script_json(adminPhrase('No pending campaigns ready to send.')) ?>);
        } else {
            alert(<?= i18n_script_json(adminPhrase('Error processing queue:')) ?> + ' ' + (result.message || result.errors?.join(', ') || <?= i18n_script_json(adminPhrase('Unknown error')) ?>));
            location.reload();
        }
    } catch (err) {
        console.error(err);
        alert(<?= i18n_script_json(adminPhrase('Failed to connect to the mail server.')) ?>);
    } finally {
        btn.disabled = false;
        icon.classList.remove('fa-spin');
    }
});
</script>

<?php if (isset($_GET['success'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Campaign scheduled successfully.')) ?></div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Campaign deleted successfully.')) ?></div>
<?php endif; ?>

<?php if (isset($_GET['review_saved'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Homepage gamer review saved.')) ?></div>
<?php endif; ?>

<?php if (isset($_GET['review_deleted'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Homepage gamer review deleted.')) ?></div>
<?php endif; ?>

<?php if (isset($_GET['flash_saved'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Special event deal declared.')) ?></div>
<?php endif; ?>

<?php if (isset($_GET['flash_deleted'])): ?>
    <div class="admin-alert success"><?= adminH(adminPhrase('Special event deal deleted.')) ?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="admin-alert success"><?= adminH($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="admin-alert error"><?= adminH($error) ?></div>
<?php endif; ?>

<div class="homepage-controls-grid">
    <div class="admin-card gamer-review-admin">
        <div class="card-header">
            <h3><i class="fas fa-gamepad"></i> <?= adminH(adminPhrase('Trusted by Gamers Reviews')) ?></h3>
            <p><?= adminH(adminPhrase('Control the homepage testimonial cards shown in the Trusted by Gamers section.')) ?></p>
        </div>
        <form method="post" class="premium-form compact-form">
            <?= csrfField() ?>
            <input type="hidden" name="review_id" value="0">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> <?= adminH(adminPhrase('Reviewer Name')) ?></label>
                    <input type="text" name="reviewer_name" class="premium-input" placeholder="<?= adminH(adminPhrase('Sami L.')) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-badge"></i> <?= adminH(adminPhrase('Role / Label')) ?></label>
                    <input type="text" name="reviewer_role" class="premium-input" placeholder="<?= adminH(adminPhrase('Verified gamer')) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-quote-right"></i> <?= adminH(adminPhrase('Quote')) ?></label>
                <textarea name="quote" class="premium-input premium-textarea small-textarea" required></textarea>
            </div>
            <details class="translation-panel">
                <summary><i class="fas fa-language"></i> <?= adminH(adminPhrase('Custom translations')) ?></summary>
                <p><?= adminH(adminPhrase('Optional. Empty fields fall back to the default review copy.')) ?></p>
                <?php foreach ($reviewTranslationLocales as $locale => $label): ?>
                    <div class="translation-locale-block" lang="<?= adminH($locale) ?>" dir="<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>">
                        <strong><?= adminH($label) ?></strong>
                        <div class="form-row">
                            <input type="text" name="reviewer_name_<?= adminH($locale) ?>" class="premium-input" placeholder="<?= adminH(adminPhrase('Reviewer Name')) ?>">
                            <input type="text" name="reviewer_role_<?= adminH($locale) ?>" class="premium-input" placeholder="<?= adminH(adminPhrase('Role / Label')) ?>">
                        </div>
                        <textarea name="quote_<?= adminH($locale) ?>" class="premium-input premium-textarea small-textarea" placeholder="<?= adminH(adminPhrase('Quote')) ?>"></textarea>
                    </div>
                <?php endforeach; ?>
            </details>
            <div class="form-row three-cols">
                <div class="form-group">
                    <label><i class="fas fa-circle-user"></i> <?= adminH(adminPhrase('Initials')) ?></label>
                    <input type="text" name="avatar_initials" class="premium-input" maxlength="4" placeholder="SL">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-star"></i> <?= adminH(adminPhrase('Rating')) ?></label>
                    <input type="number" name="rating" class="premium-input" min="1" max="5" step="0.5" value="5">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-arrow-down-1-9"></i> <?= adminH(adminPhrase('Sort')) ?></label>
                    <input type="number" name="sort_order" class="premium-input" value="<?= count($homepageGamerReviews) + 1 ?>">
                </div>
            </div>
            <label class="admin-check-row">
                <input type="checkbox" name="is_active" checked>
                <span><?= adminH(adminPhrase('Show on homepage')) ?></span>
            </label>
            <div class="form-actions single-action">
                <button type="submit" name="save_gamer_review" class="button button-primary">
                    <i class="fas fa-plus"></i> <?= adminH(adminPhrase('Add Review')) ?>
                </button>
            </div>
        </form>
        <div class="admin-mini-list">
            <?php if (empty($homepageGamerReviews)): ?>
                <div class="empty-state"><?= adminH(adminPhrase('No custom gamer reviews yet. The homepage will use its built-in fallback reviews.')) ?></div>
            <?php else: ?>
                <?php foreach ($homepageGamerReviews as $review): ?>
                    <details class="admin-detail-row" <?= !(int) $review['is_active'] ? 'open' : '' ?>>
                        <summary>
                            <span>
                                <strong><?= adminH($review['reviewer_name']) ?></strong>
                                <small><?= adminH($review['reviewer_role']) ?> · <?= adminH((string) $review['rating']) ?>/5</small>
                            </span>
                            <em><?= (int) $review['is_active'] ? adminH(adminPhrase('Active')) : adminH(adminPhrase('Hidden')) ?></em>
                        </summary>
                        <form method="post" class="premium-form inline-edit-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                            <div class="form-row">
                                <input type="text" name="reviewer_name" class="premium-input" value="<?= adminH($review['reviewer_name']) ?>" required>
                                <input type="text" name="reviewer_role" class="premium-input" value="<?= adminH($review['reviewer_role']) ?>" required>
                            </div>
                            <textarea name="quote" class="premium-input premium-textarea small-textarea" required><?= adminH($review['quote']) ?></textarea>
                            <details class="translation-panel">
                                <summary><i class="fas fa-language"></i> <?= adminH(adminPhrase('Custom translations')) ?></summary>
                                <p><?= adminH(adminPhrase('Optional. Empty fields fall back to the default review copy.')) ?></p>
                                <?php foreach ($reviewTranslationLocales as $locale => $label): ?>
                                    <div class="translation-locale-block" lang="<?= adminH($locale) ?>" dir="<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>">
                                        <strong><?= adminH($label) ?></strong>
                                        <div class="form-row">
                                            <input type="text" name="reviewer_name_<?= adminH($locale) ?>" class="premium-input" value="<?= adminH($review["reviewer_name_{$locale}"] ?? '') ?>" placeholder="<?= adminH(adminPhrase('Reviewer Name')) ?>">
                                            <input type="text" name="reviewer_role_<?= adminH($locale) ?>" class="premium-input" value="<?= adminH($review["reviewer_role_{$locale}"] ?? '') ?>" placeholder="<?= adminH(adminPhrase('Role / Label')) ?>">
                                        </div>
                                        <textarea name="quote_<?= adminH($locale) ?>" class="premium-input premium-textarea small-textarea" placeholder="<?= adminH(adminPhrase('Quote')) ?>"><?= adminH($review["quote_{$locale}"] ?? '') ?></textarea>
                                    </div>
                                <?php endforeach; ?>
                            </details>
                            <div class="form-row three-cols">
                                <input type="text" name="avatar_initials" class="premium-input" maxlength="4" value="<?= adminH($review['avatar_initials'] ?? '') ?>">
                                <input type="number" name="rating" class="premium-input" min="1" max="5" step="0.5" value="<?= adminH($review['rating']) ?>">
                                <input type="number" name="sort_order" class="premium-input" value="<?= (int) $review['sort_order'] ?>">
                            </div>
                            <label class="admin-check-row">
                                <input type="checkbox" name="is_active" <?= (int) $review['is_active'] ? 'checked' : '' ?>>
                                <span><?= adminH(adminPhrase('Show on homepage')) ?></span>
                            </label>
                            <div class="inline-actions">
                                <button type="submit" name="save_gamer_review" class="button button-primary button-small"><i class="fas fa-save"></i> <?= adminH(adminPhrase('Save')) ?></button>
                                <button type="submit" name="delete_gamer_review" class="button button-danger button-small" onclick="return confirm(<?= i18n_script_json(adminPhrase('Delete this review?')) ?>);"><i class="fas fa-trash"></i> <?= adminH(adminPhrase('Delete')) ?></button>
                            </div>
                        </form>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card flash-event-admin">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> <?= adminH(adminPhrase('Special Event Deals')) ?></h3>
            <p><?= adminH(adminPhrase('Declare an event by creating flash sale rows with the same event name and date window. Active rows appear automatically on the storefront.')) ?></p>
        </div>
        <form method="post" class="premium-form compact-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label><i class="fas fa-box"></i> <?= adminH(adminPhrase('Product')) ?></label>
                <select name="product_id" class="premium-input" required>
                    <option value=""><?= adminH(adminPhrase('Choose product')) ?></option>
                    <?php foreach ($flashSaleProducts as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= adminH($product['name']) ?> · <?= adminH(adminMoney((float) $product['price'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> <?= adminH(adminPhrase('Sale Price')) ?></label>
                    <input type="number" name="sale_price" class="premium-input" min="1" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-boxes-stacked"></i> <?= adminH(adminPhrase('Max Quantity')) ?></label>
                    <input type="number" name="max_quantity" class="premium-input" min="1" placeholder="<?= adminH(adminPhrase('Unlimited')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-plus"></i> <?= adminH(adminPhrase('Starts')) ?></label>
                    <input type="datetime-local" name="starts_at" class="premium-input" value="<?= adminH(date('Y-m-d\TH:i')) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-xmark"></i> <?= adminH(adminPhrase('Ends')) ?></label>
                    <input type="datetime-local" name="ends_at" class="premium-input" value="<?= adminH(date('Y-m-d\TH:i', strtotime('+3 days'))) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-star"></i> <?= adminH(adminPhrase('Event Name')) ?></label>
                <input type="text" name="event_name" class="premium-input" placeholder="<?= adminH(adminPhrase('Weekend GPU Drops')) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-certificate"></i> <?= adminH(adminPhrase('Event Badge')) ?></label>
                    <input type="text" name="event_badge" class="premium-input" placeholder="<?= adminH(adminPhrase('Flash Sale Live')) ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-note-sticky"></i> <?= adminH(adminPhrase('Deal Note')) ?></label>
                    <input type="text" name="event_note" class="premium-input" placeholder="<?= adminH(adminPhrase('Limited stock for this event.')) ?>">
                </div>
            </div>
            <div class="event-helper">
                <strong><?= adminH(adminPhrase('How to declare an event')) ?></strong>
                <span><?= adminH(adminPhrase('Use the same Event Name, Badge, Starts, and Ends for every product included in the event. The public deal section groups the active rows by the nearest ending sale.')) ?></span>
            </div>
            <div class="form-actions single-action">
                <button type="submit" name="create_flash_sale_event" class="button button-special">
                    <i class="fas fa-bolt"></i> <?= adminH(adminPhrase('Declare Deal')) ?>
                </button>
            </div>
        </form>
        <div class="admin-mini-list">
            <?php if (empty($flashSales)): ?>
                <div class="empty-state"><?= adminH(adminPhrase('No flash deals declared yet.')) ?></div>
            <?php else: ?>
                <?php foreach ($flashSales as $sale): ?>
                    <?php
                        $now = time();
                        $starts = strtotime((string) $sale['starts_at']);
                        $ends = strtotime((string) $sale['ends_at']);
                        $state = $starts > $now ? adminPhrase('Upcoming') : ($ends > $now ? adminPhrase('Live') : adminPhrase('Ended'));
                    ?>
                    <article class="flash-event-row">
                        <div>
                            <strong><?= adminH($sale['event_name'] ?: $sale['product_name']) ?></strong>
                            <span><?= adminH($sale['product_name']) ?> · <?= adminH(adminMoney((float) $sale['sale_price'])) ?></span>
                            <small><?= adminH(adminFormatDate($sale['starts_at'])) ?> → <?= adminH(adminFormatDate($sale['ends_at'])) ?></small>
                        </div>
                        <span class="status-pill status-<?= $state === adminPhrase('Live') ? 'sent' : ($state === adminPhrase('Upcoming') ? 'pending' : 'failed') ?>"><?= adminH($state) ?></span>
                        <form method="post" onsubmit="return confirm(<?= i18n_script_json(adminPhrase('Delete this flash sale?')) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="flash_sale_id" value="<?= (int) $sale['id'] ?>">
                            <button type="submit" name="delete_flash_sale" class="button button-danger button-small"><i class="fas fa-trash"></i></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="admin-grid marketing-grid">
    <!-- New Email Form -->
    <div class="admin-card campaign-editor">
        <div class="card-header">
            <h3><i class="fas fa-paper-plane"></i> <?= adminH(adminPhrase('New Email Campaign')) ?></h3>
            <p><?= adminH(adminPhrase('Compose your message and schedule the broadcast.')) ?></p>
        </div>
        <form method="post" class="premium-form" id="emailForm">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-users"></i> <?= adminH(adminPhrase('Recipients Target')) ?></label>
                    <select name="recipients_type" class="premium-input" id="recipientsType">
                        <?php foreach ($segments as $key => $segment): ?>
                            <option value="<?= adminH($key) ?>" <?= $formValues['recipients_type'] === $key ? 'selected' : '' ?>>
                                <?= adminH($segment['label']) ?> (<?= (int) $segment['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> <?= adminH(adminPhrase('Schedule Send Time')) ?></label>
                    <input type="datetime-local" name="scheduled_at" class="premium-input" value="<?= adminH($formValues['scheduled_at']) ?>" required>
                </div>
            </div>
            <div class="recipient-insight" id="recipientInsight" data-counts='<?= adminH(json_encode($recipientCounts, JSON_THROW_ON_ERROR)) ?>'>
                <span><i class="fas fa-users-viewfinder"></i> <?= adminH(adminPhrase('Estimated recipients')) ?></span>
                <strong id="recipientCount"><?= (int) ($recipientCounts[$formValues['recipients_type']] ?? 0) ?></strong>
            </div>
            <div class="form-group">
                <label><i class="fas fa-heading"></i> <?= adminH(adminPhrase('Email Subject')) ?></label>
                <input type="text" name="subject" id="campaignSubject" class="premium-input" placeholder="<?= adminH(adminPhrase('e.g. Special Weekend Promotion!')) ?>" value="<?= adminH($formValues['subject']) ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> <?= adminH(adminPhrase('Email Content (HTML Supported)')) ?></label>
                <div class="editor-container">
                    <textarea name="content" id="campaignContent" class="premium-input premium-textarea" placeholder="<?= adminH(adminPhrase('Write your message here...')) ?>" required><?= adminH($formValues['content']) ?></textarea>
                    <div class="editor-hints">
                        <span><i class="fab fa-html5"></i> <?= adminH(adminPhrase('HTML active')) ?></span>
                        <span><i class="fas fa-magic"></i> <?= adminH(adminPhrase('Template applied automatically')) ?></span>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="button button-light" id="previewCampaignBtn">
                    <i class="fas fa-eye"></i> <?= adminH(adminPhrase('Preview')) ?>
                </button>
                <button type="submit" name="send_test_email" class="button button-special" <?= $testRecipient === '' ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane"></i> <?= adminH(adminPhrase('Send Test')) ?>
                </button>
                <button type="submit" name="send_email" class="button button-primary">
                    <i class="fas fa-calendar-check"></i> <?= adminH(adminPhrase('Schedule Campaign')) ?>
                </button>
            </div>
        </form>
        <div class="campaign-preview" id="campaignPreview" hidden>
            <div class="preview-title">
                <span><?= adminH(adminPhrase('Email Preview')) ?></span>
                <button type="button" class="preview-close" id="closePreviewBtn" aria-label="<?= adminH(adminPhrase('Close preview')) ?>"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="preview-subject" id="previewSubject"><?= adminH(adminPhrase('Untitled campaign')) ?></div>
            <div class="preview-body" id="previewBody"></div>
        </div>
    </div>

    <!-- Queue List -->
    <div class="admin-card campaign-history">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> <?= adminH(adminPhrase('Recent Campaigns')) ?></h3>
            <p><?= adminH(adminPhrase('Track delivery status and engagement metrics.')) ?></p>
        </div>
        <div class="campaign-list">
            <?php if (empty($scheduledEmails)): ?>
                <div class="empty-state"><?= adminH(adminPhrase('No campaigns found.')) ?></div>
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
                            <span class="campaign-date"><?= adminH(adminPhrase('Created {date}', ['date' => adminFormatDate($email['created_at'])])) ?></span>
                        </div>
                        <span class="status-pill status-<?= adminH($status) ?>">
                            <i class="fas <?= $status === 'sent' ? 'fa-check-circle' : ($status === 'failed' ? 'fa-exclamation-circle' : 'fa-clock') ?>"></i>
                            <?= adminH(adminStatusLabel($status)) ?>
                        </span>
                        <div class="campaign-meta">
                            <span><?= adminH(adminFormatDate($email['scheduled_at'])) ?></span>
                            <small><?= date('H:i', strtotime($email['scheduled_at'])) ?></small>
                        </div>
                        <div class="delivery-stat" title="<?= adminH((string) ($email['error_message'] ?? '')) ?>">
                            <?php if ($totalRecipients > 0): ?>
                                <strong><?= $sentCount ?></strong>
                                <small>/ <?= $totalRecipients ?></small>
                            <?php else: ?>
                                <span class="delivery-empty"><?= adminH(adminPhrase('No recipients')) ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="post" class="campaign-delete-form" onsubmit="return confirm(<?= i18n_script_json(adminPhrase('Delete this scheduled email?')) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="campaign_id" value="<?= (int) $email['id'] ?>">
                            <button type="submit" name="delete_campaign" class="button button-danger button-small campaign-delete-btn" aria-label="<?= adminH(adminPhrase('Delete campaign')) ?>">
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
const marketingText = {
    untitled: <?= i18n_script_json(adminPhrase('Untitled campaign')) ?>,
    previewMuted: <?= i18n_script_json(adminPhrase('Your email content preview will appear here.')) ?>
};

function updateRecipientEstimate() {
    const count = recipientCounts[recipientSelect.value] ?? 0;
    recipientCount.textContent = new Intl.NumberFormat().format(count);
    recipientInsight.classList.toggle('is-empty', count <= 0);
}

function updatePreview() {
    previewSubject.textContent = subjectInput.value.trim() || marketingText.untitled;
    previewBody.innerHTML = contentInput.value.trim() || '<p class="preview-muted">' + marketingText.previewMuted + '</p>';
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

.homepage-controls-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
    margin-bottom: 24px;
}

@media (max-width: 1320px) {
    .marketing-grid,
    .homepage-controls-grid { grid-template-columns: 1fr; }
}

.campaign-editor,
.campaign-history,
.gamer-review-admin,
.flash-event-admin {
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

.compact-form .form-group { margin-bottom: 16px; }
.small-textarea { min-height: 112px; }
.three-cols { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.single-action { grid-template-columns: 1fr; }

.translation-panel {
    border: 1px dashed rgba(0,245,212,0.24);
    border-radius: 14px;
    padding: 12px 14px;
    margin: 0 0 16px;
    background: rgba(0,245,212,0.025);
}

.translation-panel summary {
    cursor: pointer;
    color: var(--cyan);
    font-weight: 900;
    font-size: 0.86rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.translation-panel summary i { margin-right: 8px; }

.translation-panel p {
    margin: 10px 0 14px;
    color: var(--muted);
    font-size: 0.82rem;
}

.translation-locale-block {
    display: grid;
    gap: 10px;
    padding: 12px 0;
    border-top: 1px solid var(--border);
}

.translation-locale-block:first-of-type { border-top: none; }
.translation-locale-block strong { color: var(--text); }
.translation-locale-block[dir="rtl"] .premium-input { text-align: right; }

.admin-check-row {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    color: var(--text-dim);
    font-weight: 800;
    font-size: 0.82rem;
    margin-bottom: 14px;
}

.admin-check-row input { accent-color: var(--cyan); }

.admin-mini-list {
    border-top: 1px solid var(--border);
    display: grid;
}

.admin-detail-row,
.flash-event-row {
    border-bottom: 1px solid var(--border);
}

.admin-detail-row:last-child,
.flash-event-row:last-child { border-bottom: none; }

.admin-detail-row summary {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 22px;
    color: var(--text);
}

.admin-detail-row summary strong,
.flash-event-row strong {
    color: var(--text);
    display: block;
}

.admin-detail-row summary small,
.flash-event-row span,
.flash-event-row small {
    color: var(--muted);
    display: block;
    margin-top: 4px;
    font-size: 0.76rem;
}

.admin-detail-row summary em {
    color: var(--cyan);
    font-style: normal;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
}

.inline-edit-form {
    border-top: 1px solid var(--border);
    padding: 18px 22px 22px;
}

.inline-edit-form .form-row { margin-bottom: 10px; }
.inline-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.event-helper {
    display: grid;
    gap: 6px;
    margin: 0 0 16px;
    padding: 12px 14px;
    border: 1px solid rgba(0,245,212,0.16);
    border-radius: 12px;
    background: rgba(0,245,212,0.04);
}

.event-helper strong {
    color: var(--cyan);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.event-helper span {
    color: var(--muted);
    font-size: 0.82rem;
    line-height: 1.5;
}

.flash-event-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto 42px;
    align-items: center;
    gap: 12px;
    padding: 16px 22px;
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
