<?php
require_once 'config.php';
require_once 'two-factor-helpers.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();

if (empty($_SESSION['client_id'])) {
    $isExpired = isset($_COOKIE['has_active_session']) ? '&session_expired=1' : '';
    header('Location: login.php?next=' . urlencode('account.php') . $isExpired);
    exit();
}

$pdo = db();
ensureAccountTwoFactorColumns($pdo);
twoFactorEnsureColumns($pdo);
ensureAccountProfileImageColumn($pdo);
ensureAccountOAuthColumns($pdo);
$stmt = $pdo->prepare("SELECT id_client, nom, email, adresse, telephone, date_naissance, profile_image, created_at, deleted_at, mot_de_passe, google_id, facebook_id, discord_id, steam_id, two_factor_enabled, two_factor_method, two_factor_totp_secret FROM Client WHERE id_client = ?");
$stmt->execute([(int) $_SESSION['client_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php?next=' . urlencode('account.php'));
    exit();
}

$activeTab = $_GET['tab'] ?? 'overview';
$clientId = (int) $_SESSION['client_id'];
$defaultProfileImage = 'Images/profile/default-avatar.svg';
$profileImage = trim((string) ($user['profile_image'] ?? ''));
$hasCustomProfileImage = $profileImage !== '';
if ($profileImage === '') {
    $profileImage = $defaultProfileImage;
}
$passwordHashInfo = !empty($user['mot_de_passe']) ? password_get_info((string) $user['mot_de_passe']) : ['algo' => 0];
$hasLocalPassword = !empty($passwordHashInfo['algo']);
$hasOAuthProvider = !empty($user['google_id']) || !empty($user['facebook_id']) || !empty($user['discord_id']) || !empty($user['steam_id']);
$twoFactorConfirmMode = $hasOAuthProvider || !$hasLocalPassword ? 'email-code' : 'password';

// Calculate account stats
$orderCount = 0;
$totalSpent = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as spent FROM orders WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $stats = $stmt->fetch();
    $orderCount = (int) $stats['cnt'];
    $totalSpent = (float) $stats['spent'];
} catch (PDOException $e) {
    // orders table may not exist yet
}

// Loyalty data
$loyaltyTier = 'bronze';
$loyaltyPoints = 0;
try {
    $stmt = $pdo->prepare("SELECT loyalty_tier, total_points FROM Client WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $loyaltyData = $stmt->fetch();
    $loyaltyTier = $loyaltyData['loyalty_tier'] ?? 'bronze';
    $loyaltyPoints = (int) ($loyaltyData['total_points'] ?? 0);
} catch (PDOException $e) {
    // columns may not exist yet
}

// Account age
$createdAt = new DateTime($user['created_at'] ?? 'now');
$now = new DateTime();
$accountAge = $now->diff($createdAt);
$accountAgeStr = $accountAge->y > 0 
    ? $accountAge->y . ' ' . i18n_t($accountAge->y > 1 ? 'account.years' : 'account.year', [], $accountAge->y > 1 ? 'years' : 'year') . ' ' . $accountAge->m . ' ' . i18n_t($accountAge->m > 1 ? 'account.months_short' : 'account.month_short', [], 'mo')
    : ($accountAge->m > 0 
        ? $accountAge->m . ' ' . i18n_t($accountAge->m > 1 ? 'account.months' : 'account.month', [], $accountAge->m > 1 ? 'months' : 'month')
        : $accountAge->d . ' ' . i18n_t($accountAge->d > 1 ? 'account.days' : 'account.day', [], $accountAge->d > 1 ? 'days' : 'day'));

// Deletion status
$isDeleted = !empty($user['deleted_at']);
$deleteDeadline = '';
$daysLeft = 0;
if ($isDeleted) {
    $deletedAt = new DateTime($user['deleted_at']);
    $deadline = (clone $deletedAt)->modify('+5 days');
    $daysLeft = max(0, $now->diff($deadline)->days);
    $deleteDeadline = $deadline->format('F j, Y');
    if ($now > $deadline) {
        $daysLeft = 0;
    }
}

$wishlistCount = (int) accountScalar($pdo, 'SELECT COUNT(*) FROM wishlist WHERE client_id = ?', [$clientId], 0);
$savedBuildCount = (int) accountScalar($pdo, 'SELECT COUNT(*) FROM saved_builds WHERE client_id = ?', [$clientId], 0);
$recentOrders = accountRows($pdo, '
    SELECT id, status, total, payment_status, created_at, estimated_delivery
    FROM orders
    WHERE client_id = ?
    ORDER BY created_at DESC
    LIMIT 3
', [$clientId]);

// After-sales tickets
$supportTickets = [];
$openTicketCount = 0;
try {
    $stmt = $pdo->prepare('
        SELECT id, ticket_code, order_id, request_type, preferred_resolution, product_name,
               product_condition, serial_number, reason, status, priority, next_action, created_at, updated_at
        FROM after_sales_requests
        WHERE client_id = ?
        ORDER BY created_at DESC
    ');
    $stmt->execute([$clientId]);
    $supportTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $openTicketCount = count(array_filter($supportTickets, fn($t) => !in_array($t['status'], ['resolved', 'rejected'], true)));
} catch (PDOException $e) {
    // Table may not exist yet
}

$profileChecklist = [
    i18n_t('account.full_name', [], 'Name') => trim((string) ($user['nom'] ?? '')) !== '',
    i18n_t('account.email_address', [], 'Email') => trim((string) ($user['email'] ?? '')) !== '',
    i18n_t('account.phone_number', [], 'Phone') => trim((string) ($user['telephone'] ?? '')) !== '',
    i18n_t('account.shipping_address', [], 'Address') => trim((string) ($user['adresse'] ?? '')) !== '',
    i18n_t('account.date_of_birth', [], 'Birthday') => trim((string) ($user['date_naissance'] ?? '')) !== '',
    i18n_t('account.profile_picture', [], 'Profile photo') => trim((string) ($user['profile_image'] ?? '')) !== '',
    i18n_t('account.login_2fa', [], '2FA') => !empty($user['two_factor_enabled']),
];
$profileCompleted = count(array_filter($profileChecklist));
$profileCompletion = (int) round(($profileCompleted / max(1, count($profileChecklist))) * 100);

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function moneyMAD(float $v): string
{
    return i18n_format_money($v);
}

function ensureAccountTwoFactorColumns(PDO $pdo): void
{
    twoFactorEnsureColumns($pdo);
}

function ensureAccountProfileImageColumn(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote('profile_image'));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE Client ADD COLUMN profile_image VARCHAR(255) NULL AFTER date_naissance");
        }
    } catch (PDOException $e) {
        // Older local databases can continue without the avatar column until migration succeeds.
    }
}

function ensureAccountOAuthColumns(PDO $pdo): void
{
    $columns = [
        'google_id' => 'ALTER TABLE Client ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE',
        'facebook_id' => 'ALTER TABLE Client ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL UNIQUE',
        'discord_id' => 'ALTER TABLE Client ADD COLUMN discord_id VARCHAR(255) DEFAULT NULL UNIQUE',
        'steam_id' => 'ALTER TABLE Client ADD COLUMN steam_id VARCHAR(255) DEFAULT NULL UNIQUE',
    ];

    foreach ($columns as $column => $sql) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM Client LIKE " . $pdo->quote($column));
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($sql);
            }
        } catch (PDOException $e) {
            // The linked-account panel can still render if the local DB cannot be migrated.
        }
    }
}

function accountScalar(PDO $pdo, string $sql, array $params = [], mixed $fallback = null): mixed
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? $fallback : $value;
    } catch (PDOException $e) {
        return $fallback;
    }
}

function accountRows(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function accountStatusLabel(string $status): string
{
    $normalized = strtolower(trim($status));
    $key = 'account.' . str_replace(['-', ' '], '_', $normalized);

    return i18n_t($key, [], ucwords(str_replace('_', ' ', $status)));
}

function accountTierLabel(string $tier): string
{
    return i18n_t('account.tier_' . strtolower(trim($tier)), [], ucfirst($tier));
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(i18n_t('account.page_title', [], 'My Account — Maroc PC'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/account.css">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">
    <script src="assets/js/wishlist.js"></script>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script src="assets/js/page-transitions.js"></script>
</head>

<body>

    <header class="header">
        <div class="nav-container">
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <i class="fas fa-microchip"></i>
                <span>Maroc PC</span>
            </a>

            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.products'); ?></a>
            </nav>

            <div class="nav-spacer"></div>

            <button class="theme-toggle" id="themeToggle" aria-label="<?= htmlspecialchars(i18n_t('auth.toggle_theme', [], 'Toggle theme'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <?= i18n_language_switcher('nav-translate') ?>

            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </div>
    </header>

    <section class="account-page">
        <div class="container">
            <div class="account-layout">
                <aside class="account-sidebar">
                    <a href="?tab=overview" class="<?= $activeTab === 'overview' ? 'active' : '' ?>">
                        <i class="fas fa-gauge-high"></i> <?php i18n_e('account.overview', [], 'Overview'); ?>
                    </a>
                    <a href="?tab=profile" class="<?= $activeTab === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> <?php i18n_e('account.profile', [], 'Profile'); ?>
                    </a>
                    <a href="?tab=orders" class="<?= $activeTab === 'orders' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i> <?php i18n_e('account.orders', [], 'Orders'); ?>
                    </a>
                    <a href="?tab=wishlist" class="<?= $activeTab === 'wishlist' ? 'active' : '' ?>">
                        <i class="fas fa-heart"></i> <?php i18n_e('account.wishlist', [], 'Wishlist'); ?>
                    </a>
                    <a href="?tab=builds" class="<?= $activeTab === 'builds' ? 'active' : '' ?>">
                        <i class="fas fa-computer"></i> <?php i18n_e('account.builds', [], 'Builds'); ?>
                    </a>
                    <a href="?tab=loyalty" class="<?= $activeTab === 'loyalty' ? 'active' : '' ?>">
                        <i class="fas fa-crown"></i> <?php i18n_e('account.rewards', [], 'Rewards'); ?>
                    </a>
                    <a href="?tab=warranties" class="<?= $activeTab === 'warranties' ? 'active' : '' ?>">
                        <i class="fas fa-shield-heart"></i> <?php i18n_e('account.warranties_rmas', [], 'Warranties & RMAs'); ?>
                    </a>
                    <a href="?tab=security" class="<?= $activeTab === 'security' ? 'active' : '' ?>">
                        <i class="fas fa-shield-halved"></i> <?php i18n_e('account.security', [], 'Security'); ?>
                    </a>
                    <a href="?tab=support" class="<?= $activeTab === 'support' ? 'active' : '' ?>">
                        <i class="fas fa-headset"></i> <?php i18n_e('account.support', [], 'Support'); ?>
                        <?php if ($openTicketCount > 0): ?>
                            <span class="sidebar-badge"><?= $openTicketCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?php i18n_e('auth.logout'); ?>
                    </a>
                </aside>

                <div class="account-content account-content--flat">

                    <!-- ── Restore banner (if account is pending deletion) ── -->
                    <?php if ($isDeleted && $daysLeft > 0): ?>
                        <div class="restore-banner" id="restoreBanner">
                            <i class="fas fa-exclamation-triangle restore-icon"></i>
                            <div class="restore-text">
                                <h4><?php i18n_e('account.account_deletion_scheduled', [], 'Account Deletion Scheduled'); ?></h4>
                                <p>
                                    <?php i18n_e('account.delete_deadline', [
                                        'date' => $deleteDeadline,
                                        'days' => $daysLeft,
                                        'day_word' => i18n_t($daysLeft > 1 ? 'account.day_plural' : 'account.day_singular', [], $daysLeft > 1 ? 'days' : 'day'),
                                    ], 'Your account will be permanently deleted on {date} ({days} {day_word} left).'); ?>
                                    <?php i18n_e('account.restore_cancel_hint', [], 'Click "Restore" to cancel the deletion.'); ?>
                                </p>
                            </div>
                            <button class="btn-restore" id="restoreAccountBtn">
                                <i class="fas fa-undo"></i> <?php i18n_e('account.restore', [], 'Restore'); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- ── Hero card ──────────────────────────────── -->
                    <div class="account-hero">
                        <div class="avatar profile-avatar">
                            <img id="profileAvatarImg" src="<?= h($profileImage) ?>" alt="<?= h(i18n_t('account.profile_picture', [], 'Profile picture')) ?>" onerror="this.src='<?= h($defaultProfileImage) ?>'">
                        </div>
                        <div class="hero-info">
                            <h2><?= h($user['nom']) ?></h2>
                            <p><?= h($user['email']) ?></p>
                            <span class="member-badge">
                                <i class="fas fa-gem"></i> <?php i18n_e('account.member_since', [], 'Member Since'); ?> <?= h($accountAgeStr) ?>
                            </span>
                        </div>
                    </div>

                    <!-- ── Stats ──────────────────────────────────── -->
                    <div class="stats-grid account-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?= $orderCount ?></div>
                            <div class="stat-label"><?php i18n_e('account.total_orders', [], 'Total Orders'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= moneyMAD($totalSpent) ?></div>
                            <div class="stat-label"><?php i18n_e('account.total_spent', [], 'Total Spent'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value stat-value--orange"><?= $loyaltyPoints ?></div>
                            <div class="stat-label"><?php i18n_e('account.loyalty_points', [], 'Loyalty Points'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value stat-value--tier"><?= h(accountTierLabel($loyaltyTier)) ?></div>
                            <div class="stat-label"><?php i18n_e('account.current_tier', [], 'Current Tier'); ?></div>
                        </div>
                    </div>

                    <?php if ($activeTab === 'overview'): ?>
                        <div class="overview-grid">
                            <div class="section-card overview-panel overview-main">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow"><?php i18n_e('account.account_home', [], 'Account home'); ?></span>
                                        <h3><i class="fas fa-star"></i> <?php i18n_e('account.welcome_back', ['name' => h(strtok((string) $user['nom'], ' ') ?: 'there')], 'Welcome back, {name}'); ?></h3>
                                    </div>
                                    <a class="btn-view" href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-bag-shopping"></i> <?php i18n_e('account.shop', [], 'Shop'); ?></a>
                                </div>
                                <div class="overview-metrics">
                                    <div>
                                        <strong><?= $wishlistCount ?></strong>
                                        <span><?php i18n_e('account.wishlist_items', [], 'Wishlist items'); ?></span>
                                    </div>
                                    <div>
                                        <strong><?= $savedBuildCount ?></strong>
                                        <span><?php i18n_e('account.saved_builds', [], 'Saved builds'); ?></span>
                                    </div>
                                    <div>
                                        <strong><?= !empty($user['two_factor_enabled']) ? h(i18n_t('account.on', [], 'On')) : h(i18n_t('account.off', [], 'Off')) ?></strong>
                                        <span><?php i18n_e('account.login_2fa', [], 'Login 2FA'); ?></span>
                                    </div>
                                    <div>
                                        <strong class="overview-metric-value <?= $openTicketCount > 0 ? 'is-warn' : '' ?>"><?= $openTicketCount ?></strong>
                                        <span><?php i18n_e('account.open_tickets', [], 'Open Tickets'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="section-card overview-panel">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow"><?php i18n_e('account.profile_health', [], 'Profile health'); ?></span>
                                        <h3><i class="fas fa-user-check"></i> <?php i18n_e('account.percent_complete', ['percent' => $profileCompletion], '{percent}% complete'); ?></h3>
                                    </div>
                                    <a class="btn-view" href="?tab=profile"><i class="fas fa-pen"></i> <?php i18n_e('account.edit', [], 'Edit'); ?></a>
                                </div>
                                <div class="profile-completion-bar">
                                    <span style="width: <?= $profileCompletion ?>%;"></span>
                                </div>
                                <div class="profile-checklist">
                                    <?php foreach ($profileChecklist as $label => $done): ?>
                                        <span class="<?= $done ? 'done' : '' ?>"><i class="fas fa-<?= $done ? 'check' : 'plus' ?>"></i> <?= h($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="section-card overview-panel overview-recent">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow"><?php i18n_e('account.recent_activity', [], 'Recent activity'); ?></span>
                                        <h3><i class="fas fa-truck-fast"></i> <?php i18n_e('account.latest_orders', [], 'Latest orders'); ?></h3>
                                    </div>
                                    <a class="btn-view" href="?tab=orders"><i class="fas fa-list"></i> <?php i18n_e('account.all_orders', [], 'All orders'); ?></a>
                                </div>
                                <?php if ($recentOrders === []): ?>
                                    <p class="overview-empty"><?php i18n_e('account.no_orders_timeline', [], 'No orders yet. Your tracking timeline will appear here after checkout.'); ?></p>
                                <?php else: ?>
                                    <div class="overview-orders">
                                        <?php foreach ($recentOrders as $order): ?>
                                            <?php $status = (string) ($order['status'] ?? 'pending'); ?>
                                            <button type="button" class="overview-order-row" onclick="viewOrder(<?= (int) $order['id'] ?>)">
                                                <span>
                                                    <strong>#<?= (int) $order['id'] ?></strong>
                                                    <small><?= h(date('M j, Y', strtotime((string) $order['created_at']))) ?></small>
                                                </span>
                                                <span class="order-status <?= in_array($status, ['delivered', 'shipped'], true) ? 'status-good' : ($status === 'cancelled' ? 'status-danger' : 'status-warn') ?>">
                                                    <?= h(accountStatusLabel($status)) ?>
                                                </span>
                                                <b><?= moneyMAD((float) $order['total']) ?></b>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="section-card overview-panel overview-actions">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow"><?php i18n_e('account.fast_actions', [], 'Fast actions'); ?></span>
                                        <h3><i class="fas fa-bolt"></i> <?php i18n_e('account.shortcuts', [], 'Shortcuts'); ?></h3>
                                    </div>
                                </div>
                                <div class="quick-action-grid">
                                    <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-computer"></i><span><?php i18n_e('account.build_a_pc', [], 'Build a PC'); ?></span></a>
                                    <a href="?tab=wishlist"><i class="fas fa-heart"></i><span><?php i18n_e('account.wishlist', [], 'Wishlist'); ?></span></a>
                                    <a href="?tab=support"><i class="fas fa-headset"></i><span><?php i18n_e('account.support', [], 'Support'); ?></span></a>
                                    <a href="?tab=loyalty"><i class="fas fa-crown"></i><span><?php i18n_e('account.rewards', [], 'Rewards'); ?></span></a>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'profile'): ?>
                        <!-- ── Profile Form ───────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-user-pen"></i> <?php i18n_e('account.personal_information', [], 'Personal Information'); ?></h3>
                            <div id="profileAlert"></div>
                            <div class="profile-picture-card" data-default-profile-image="<?= h($defaultProfileImage) ?>">
                                <img id="profilePicturePreview" src="<?= h($profileImage) ?>" alt="<?= h(i18n_t('account.profile_picture_preview', [], 'Profile picture preview')) ?>" onerror="this.src='<?= h($defaultProfileImage) ?>'">
                                <div class="profile-picture-copy">
                                    <strong><?php i18n_e('account.profile_picture', [], 'Profile picture'); ?></strong>
                                    <span><?php i18n_e('account.profile_picture_help', [], 'Use a clear square JPG, PNG, or WebP image. Max 3 MB.'); ?></span>
                                </div>
                                <div class="profile-picture-actions">
                                    <label class="btn-view profile-upload-label" for="profilePictureInput">
                                        <i class="fas fa-image"></i> <?php i18n_e('account.choose_photo', [], 'Choose photo'); ?>
                                    </label>
                                    <button type="button" class="btn-save profile-upload-btn" id="uploadProfilePictureBtn">
                                        <i class="fas fa-upload"></i> <?php i18n_e('account.upload_photo', [], 'Upload Photo'); ?>
                                    </button>
                                    <button type="button" class="btn-secondary profile-remove-btn" id="removeProfilePictureBtn" <?= $hasCustomProfileImage ? '' : 'disabled' ?>>
                                        <i class="fas fa-trash"></i> <?php i18n_e('account.remove_photo', [], 'Remove Photo'); ?>
                                    </button>
                                </div>
                                <input type="file" id="profilePictureInput" accept="image/jpeg,image/png,image/webp" hidden>
                            </div>
                            <form class="account-form" id="profileForm" onsubmit="return false;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="accName"><?php i18n_e('account.full_name', [], 'Full Name'); ?></label>
                                        <input type="text" id="accName" name="nom"
                                            value="<?= h($user['nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="accEmail"><?php i18n_e('account.email_address', [], 'Email Address'); ?></label>
                                        <input type="email" id="accEmail" name="email"
                                            value="<?= h($user['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="accPhone"><?php i18n_e('account.phone_number', [], 'Phone Number'); ?></label>
                                        <input type="tel" id="accPhone" name="telephone"
                                            value="<?= h($user['telephone'] ?? '') ?>"
                                            placeholder="<?= htmlspecialchars(i18n_t('account.phone_placeholder', [], '+212 6XX XXX XXX'), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="accDob"><?php i18n_e('account.date_of_birth', [], 'Date of Birth'); ?></label>
                                        <input type="date" id="accDob" name="date_naissance"
                                            value="<?= h($user['date_naissance'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="accAddress"><?php i18n_e('account.shipping_address', [], 'Shipping Address'); ?></label>
                                    <textarea id="accAddress" name="adresse"
                                        rows="3" placeholder="<?= htmlspecialchars(i18n_t('account.address_placeholder', [], '123 Boulevard Mohammed V, Casablanca'), ENT_QUOTES, 'UTF-8') ?>"><?= h($user['adresse'] ?? '') ?></textarea>
                                </div>
                                <button type="button" class="btn-save" id="saveProfileBtn">
                                    <i class="fas fa-check"></i> <?php i18n_e('account.save_changes', [], 'Save Changes'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- ── Quick Info ─────────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-info-circle"></i> <?php i18n_e('account.account_details', [], 'Account Details'); ?></h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label"><?php i18n_e('account.account_id', [], 'Account ID'); ?></span>
                                    <span class="info-value">#<?= h($user['id_client']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><?php i18n_e('account.member_since', [], 'Member Since'); ?></span>
                                    <span class="info-value"><?= $createdAt->format('F j, Y') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><?php i18n_e('account.email_status', [], 'Email Status'); ?></span>
                                    <span class="info-value profile-info-status">
                                        <i class="fas fa-check-circle"></i> <?php i18n_e('account.verified', [], 'Verified'); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><?php i18n_e('account.account_status', [], 'Account Status'); ?></span>
                                    <span class="info-value profile-info-status <?= $isDeleted ? 'is-danger' : '' ?>">
                                        <i class="fas fa-<?= $isDeleted ? 'exclamation-triangle' : 'shield-halved' ?>"></i>
                                        <?= $isDeleted ? htmlspecialchars(i18n_t('account.pending_deletion', [], 'Pending Deletion'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(i18n_t('account.active', [], 'Active'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'orders'): ?>
                        <!-- ── Orders ─────────────────────────────── -->
                        <div class="orders-grid">
                            <div class="section-card mb-0">
                                <h3><i class="fas fa-box-open"></i> <?php i18n_e('account.order_history', [], 'Order History'); ?></h3>
                                <div id="ordersContainer">
                                    <p class="no-orders"><?php i18n_e('account.loading_orders', [], 'Loading orders...'); ?></p>
                                </div>
                            </div>
                            <div class="section-card mb-0" id="receiptCard">
                                <h3><i class="fas fa-receipt"></i> <?php i18n_e('account.log_bank_receipt', [], 'Log Bank Transfer Receipt'); ?></h3>
                                <p class="receipt-form-intro">
                                    <?php i18n_e('account.bank_receipt_intro', [], 'If you paid via bank transfer, log your receipt here for admin verification.'); ?>
                                </p>
                                <form id="bankReceiptForm" class="receipt-form">
                                    <div class="receipt-form-field">
                                        <span class="receipt-form-label"><?php i18n_e('account.order_id', [], 'Order ID'); ?></span>
                                        <input class="receipt-form-input" name="order_id" type="number" placeholder="<?= htmlspecialchars(i18n_t('account.order_id_placeholder', [], 'e.g. 1024'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="receipt-form-field">
                                        <span class="receipt-form-label"><?php i18n_e('account.bank', [], 'Bank'); ?></span>
                                        <select class="receipt-form-input" name="bank_name" required>
                                            <option value=""><?php i18n_e('account.select_bank', [], 'Select Bank'); ?></option>
                                            <option>CIH</option>
                                            <option>Attijariwafa</option>
                                            <option>BMCE</option>
                                            <option>Cash Plus</option>
                                            <option>Wafacash</option>
                                        </select>
                                    </div>
                                    <div class="receipt-form-field">
                                        <span class="receipt-form-label"><?php i18n_e('account.amount_paid_mad', [], 'Amount Paid (DH)'); ?></span>
                                        <input class="receipt-form-input" name="amount" type="number" step="0.01" placeholder="<?= htmlspecialchars(i18n_t('account.amount_placeholder', [], 'e.g. 1500'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="receipt-form-field">
                                        <span class="receipt-form-label"><?php i18n_e('account.transfer_reference_code', [], 'Transfer Reference / Code'); ?></span>
                                        <input class="receipt-form-input" name="transfer_reference" placeholder="<?= htmlspecialchars(i18n_t('account.transfer_reference_placeholder', [], 'e.g. TR-98721345'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <button class="receipt-form-submit" type="submit">
                                        <i class="fas fa-paper-plane"></i> <?php i18n_e('account.log_receipt', [], 'Log receipt'); ?>
                                    </button>
                                    <div class="receipt-form-status status-line"></div>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'wishlist'): ?>
                        <!-- ── Wishlist ───────────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-heart"></i> <?php i18n_e('account.your_wishlist', [], 'Your Wishlist'); ?></h3>
                            <div id="wishlistContainer" class="wishlist-grid">
                                <p class="no-orders"><?php i18n_e('account.loading_wishlist', [], 'Loading wishlist...'); ?></p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'builds'): ?>
                        <div class="section-card">
                            <h3><i class="fas fa-computer"></i> <?php i18n_e('account.saved_pc_builds', [], 'Saved PC Builds'); ?></h3>
                            <div id="savedBuildsContainer">
                                <p class="no-orders"><?php i18n_e('account.loading_builds', [], 'Loading builds...'); ?></p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'loyalty'): ?>
                        <!-- ── Loyalty Points & Rewards ────────────── -->
                        <?php
                            $tierColors = ['bronze' => '#cd7f32', 'silver' => '#c0c0c0', 'gold' => '#ffd700', 'platinum' => '#e5e4e2'];
                            $tierColor = $tierColors[$loyaltyTier] ?? '#cd7f32';
                            $tierColorRgb = $loyaltyTier === 'gold' ? '255,215,0'
                                : ($loyaltyTier === 'platinum' ? '229,228,226'
                                : ($loyaltyTier === 'silver' ? '192,192,192' : '205,127,50'));
                            $tierIcons = ['bronze' => 'fa-medal', 'silver' => 'fa-award', 'gold' => 'fa-crown', 'platinum' => 'fa-gem'];
                            $tierIcon = $tierIcons[$loyaltyTier] ?? 'fa-medal';
                        ?>
                        <div class="section-card tier-card" style="--tier-color: <?= h($tierColor) ?>; --tier-card-tint: rgba(<?= h($tierColorRgb) ?>, 0.06);">
                            <div class="tier-card-header">
                                <div class="tier-card-icon">
                                    <i class="fas <?= $tierIcon ?>"></i>
                                </div>
                                <div>
                                    <div class="tier-card-name"><?php i18n_e('account.tier_member', ['tier' => h(accountTierLabel($loyaltyTier))], '{tier} Member'); ?></div>
                                    <div class="tier-card-sub"><?php i18n_e('account.earn_points_every_purchase', [], 'Earn points on every purchase'); ?></div>
                                </div>
                            </div>

                            <div class="tier-stats">
                                <div class="stat-card">
                                    <div class="stat-value"><?= number_format($loyaltyPoints) ?></div>
                                    <div class="stat-label"><?php i18n_e('account.available_points', [], 'Available Points'); ?></div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?= i18n_format_money($loyaltyPoints / 10) ?></div>
                                    <div class="stat-label"><?php i18n_e('account.redeemable_value', [], 'Redeemable Value'); ?></div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value stat-value--tier"><?= h(accountTierLabel($loyaltyTier)) ?></div>
                                    <div class="stat-label"><?php i18n_e('account.current_tier', [], 'Current Tier'); ?></div>
                                </div>
                            </div>

                            <!-- Tier Progress -->
                            <div class="tier-progress">
                                <div class="tier-progress-head">
                                    <span class="tier-progress-label"><?php i18n_e('account.tier_progress', [], 'Tier Progress'); ?></span>
                                    <span class="tier-progress-value" id="loyaltyProgressLabel"><?php i18n_e('account.loading', [], 'Loading...'); ?></span>
                                </div>
                                <div class="tier-progress-track">
                                    <div class="tier-progress-fill" id="loyaltyProgressBar"></div>
                                </div>
                                <div class="tier-progress-ticks">
                                    <span class="tier-progress-tick"><?php i18n_e('account.tier_bronze', [], 'Bronze'); ?></span>
                                    <span class="tier-progress-tick"><?php i18n_e('account.tier_silver', [], 'Silver'); ?> (2K)</span>
                                    <span class="tier-progress-tick"><?php i18n_e('account.tier_gold', [], 'Gold'); ?> (5K)</span>
                                    <span class="tier-progress-tick"><?php i18n_e('account.tier_platinum', [], 'Platinum'); ?> (10K)</span>
                                </div>
                            </div>

                            <!-- Benefits -->
                            <h3><i class="fas fa-gift" style="color: var(--tier-color, var(--cyan));"></i> <?php i18n_e('account.your_benefits', [], 'Your Benefits'); ?></h3>
                            <div id="loyaltyBenefits" class="tier-benefits">
                                <div class="tier-benefit"><i class="fas fa-check"></i> <?php i18n_e('account.points_on_purchases', [], 'Points on purchases'); ?></div>
                                <div class="tier-benefit"><i class="fas fa-check"></i> <?php i18n_e('account.birthday_bonus', [], 'Birthday bonus'); ?></div>
                            </div>

                            <!-- How it works -->
                            <h3><i class="fas fa-info-circle"></i> <?php i18n_e('account.how_it_works', [], 'How It Works'); ?></h3>
                            <div class="tier-info-grid">
                                <div class="tier-info-card">
                                    <div class="tier-info-emoji">🛒</div>
                                    <div class="tier-info-title"><?php i18n_e('account.shop', [], 'Shop'); ?></div>
                                    <div class="tier-info-sub"><?php i18n_e('account.earn_points_rule', [], 'Earn 1 pt per 10 DH spent'); ?></div>
                                </div>
                                <div class="tier-info-card">
                                    <div class="tier-info-emoji">⭐</div>
                                    <div class="tier-info-title"><?php i18n_e('account.earn', [], 'Earn'); ?></div>
                                    <div class="tier-info-sub"><?php i18n_e('account.collect_points_level_up', [], 'Collect points & level up tiers'); ?></div>
                                </div>
                                <div class="tier-info-card">
                                    <div class="tier-info-emoji">🎁</div>
                                    <div class="tier-info-title"><?php i18n_e('account.redeem', [], 'Redeem'); ?></div>
                                    <div class="tier-info-sub"><?php i18n_e('account.redeem_points_rule', [], '100 pts = 10 DH discount'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Points History -->
                        <div class="section-card">
                            <h3><i class="fas fa-history"></i> <?php i18n_e('account.points_history', [], 'Points History'); ?></h3>
                            <div id="loyaltyHistory">
                                <p class="no-orders"><?php i18n_e('account.loading_history', [], 'Loading history...'); ?></p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'warranties'): ?>
                        <!-- ── Warranties & RMAs ───────────────────── -->
                        <?php
                        $warrantyMonthsMap = [
                            'processor' => 36,
                            'cpu' => 36,
                            'graphics' => 36,
                            'gpu' => 36,
                            'motherboard' => 36,
                            'memory' => 120, // 10 Years
                            'ram' => 120,
                            'storage' => 60, // 5 Years
                            'ssd' => 60,
                            'power' => 60, // 5 Years
                            'psu' => 60,
                            'cooler' => 24, // 2 Years
                            'case' => 12,
                        ];

                        $stmt = $pdo->prepare("
                            SELECT 
                                oi.product_id,
                                oi.name_at_time,
                                oi.price_at_time,
                                oi.order_id,
                                o.created_at AS order_date,
                                p.category,
                                p.image
                            FROM order_items oi
                            JOIN orders o ON o.id = oi.order_id
                            LEFT JOIN products p ON p.id = oi.product_id
                            WHERE o.client_id = ? AND o.status = 'delivered'
                            ORDER BY o.created_at DESC
                        ");
                        $stmt->execute([$_SESSION['client_id']]);
                        $purchasedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="section-card">
                            <div class="warranty-head-row">
                                <div>
                                    <span class="eyebrow"><?php i18n_e('account.active_coverage', [], 'Active Coverage'); ?></span>
                                    <h3><i class="fas fa-shield-heart"></i> <?php i18n_e('account.component_warranties', [], 'Component Warranties'); ?></h3>
                                </div>
                                <a href="returns-refunds.php#service-request" class="warranty-claim-btn">
                                    <i class="fas fa-file-invoice"></i> <?php i18n_e('account.file_warranty_claim', [], 'File RMA / Warranty Claim'); ?>
                                </a>
                            </div>

                            <?php if (empty($purchasedItems)): ?>
                                <div class="warranty-empty">
                                    <i class="fas fa-shield-halved"></i>
                                    <p class="no-orders text-lg"><?php i18n_e('account.no_warranties_active', [], 'No hardware warranties active'); ?></p>
                                    <p><?php i18n_e('account.warranties_empty_body', [], 'Once you buy custom configurations or components and your order is delivered, your active warranties will appear here.'); ?></p>
                                    <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="warranty-cta">
                                        <i class="fas fa-shopping-bag"></i> <?php i18n_e('account.browse_components', [], 'Browse Components'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="warranty-grid">
                                    <?php foreach ($purchasedItems as $item): ?>
                                        <?php
                                        $cat = strtolower($item['category'] ?? '');
                                        $months = 12; // default
                                        foreach ($warrantyMonthsMap as $key => $val) {
                                            if (strpos($cat, $key) !== false) {
                                                $months = $val;
                                                break;
                                            }
                                        }

                                        $orderTime = strtotime($item['order_date']);
                                        $expireTime = strtotime("+$months months", $orderTime);
                                        $totalDuration = $expireTime - $orderTime;
                                        $elapsed = time() - $orderTime;
                                        $remaining = $expireTime - time();
                                        $isActive = $remaining > 0;
                                        $pctLeft = $isActive ? round(($remaining / $totalDuration) * 100) : 0;
                                        ?>
                                        <div class="warranty-card">
                                            <div class="warranty-card-head">
                                                <div class="warranty-thumb">
                                                    <img src="<?= h($item['image'] ?? 'images/products/placeholder.svg') ?>" onerror="this.src='Images/products/placeholder-storage.svg'">
                                                </div>
                                                <div class="warranty-meta">
                                                    <span class="warranty-cat"><?= h($item['category'] ?? 'Component') ?></span>
                                                    <h4 class="warranty-name"><?= h($item['name_at_time']) ?></h4>
                                                </div>
                                            </div>

                                            <div class="warranty-body">
                                                <div class="warranty-progress-row">
                                                    <span class="warranty-progress-label"><?php i18n_e('account.coverage_left', ['percent' => $pctLeft], 'Coverage Left: {percent}%'); ?></span>
                                                    <span class="warranty-progress-status <?= $isActive ? 'is-active' : 'is-expired' ?>">
                                                        <?= h($isActive ? i18n_t('account.active', [], 'Active') : i18n_t('account.expired', [], 'Expired')) ?>
                                                    </span>
                                                </div>
                                                <div class="warranty-progress-track">
                                                    <div class="warranty-progress-fill" style="width: <?= $pctLeft ?>%;"></div>
                                                </div>
                                                <div class="warranty-progress-dates">
                                                    <span><?php i18n_e('account.bought_on', ['date' => date('M d, Y', $orderTime)], 'Bought: {date}'); ?></span>
                                                    <span><?php i18n_e('account.expires_on', ['date' => date('M d, Y', $expireTime)], 'Expires: {date}'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Maintenance & RMA Tickets -->
                        <div class="section-card mt-32">
                            <h3><i class="fas fa-history"></i> <?php i18n_e('account.after_sales_rma_history', [], 'After-Sales & RMA History'); ?></h3>
                            <?php if (empty($supportTickets)): ?>
                                <p class="no-orders py-24 text-center"><?php i18n_e('account.no_support_rma_requests', [], 'No support or RMA requests filed yet.'); ?></p>
                            <?php else: ?>
                                <div class="ticket-list">
                                    <?php foreach ($supportTickets as $ticket): ?>
                                        <?php
                                        $statusClass = match($ticket['status']) {
                                            'submitted' => 'status-submitted',
                                            'reviewing', 'inspecting' => 'status-reviewing',
                                            'approved', 'resolved' => 'status-approved',
                                            'awaiting_item' => 'status-progress',
                                            'rejected' => 'status-rejected',
                                            default => 'status-submitted'
                                        };
                                        ?>
                                        <div class="ticket-card" onclick="this.classList.toggle('expanded')">
                                            <div class="ticket-header">
                                                <div class="ticket-meta">
                                                    <span class="ticket-code"><?= h($ticket['ticket_code']) ?></span>
                                                    <span class="ticket-date"><?= date('M j, Y', strtotime((string)$ticket['created_at'])) ?></span>
                                                </div>
                                                <div class="ticket-badges">
                                                    <?php if ($ticket['priority'] === 'urgent'): ?>
                                                        <span class="priority-urgent"><i class="fas fa-bolt"></i> <?php i18n_e('account.urgent', [], 'Urgent'); ?></span>
                                                    <?php endif; ?>
                                                    <span class="ticket-type"><?= h(ucfirst($ticket['request_type'])) ?></span>
                                                    <span class="ticket-status <?= $statusClass ?>"><?= h(accountStatusLabel($ticket['status'])) ?></span>
                                                    <i class="fas fa-chevron-down ticket-chevron"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="ticket-product">
                                                <i class="fas fa-microchip"></i>
                                                <span><?= h($ticket['product_name']) ?></span>
                                                <?php if ($ticket['order_id']): ?>
                                                    <span class="ticket-order"><?php i18n_e('account.order_number_short', ['id' => h($ticket['order_id'])], 'Order #{id}'); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="ticket-details">
                                                <div class="ticket-detail-grid">
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.condition', [], 'Condition'); ?></span>
                                                        <span class="detail-value"><?= h(accountStatusLabel($ticket['product_condition'])) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.preferred_resolution', [], 'Preferred Resolution'); ?></span>
                                                        <span class="detail-value"><?= h(accountStatusLabel($ticket['preferred_resolution'])) ?></span>
                                                    </div>
                                                    <?php if ($ticket['serial_number']): ?>
                                                        <div>
                                                            <span class="detail-label"><?php i18n_e('account.serial_number', [], 'Serial Number'); ?></span>
                                                            <span class="detail-value font-mono"><?= h($ticket['serial_number']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="ticket-reason">
                                                    <span class="detail-label"><?php i18n_e('account.issue_description', [], 'Issue Description'); ?></span>
                                                    <p><?= nl2br(h($ticket['reason'])) ?></p>
                                                </div>
                                                
                                                <?php if ($ticket['next_action']): ?>
                                                    <div class="ticket-next-action">
                                                        <i class="fas fa-arrow-right"></i>
                                                        <div>
                                                            <strong><?php i18n_e('account.next_step', [], 'Next Step:'); ?></strong><br>
                                                            <?= h($ticket['next_action']) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>


                    <?php elseif ($activeTab === 'security'): ?>
                        <div class="section-card">
                            <h3><i class="fas fa-user-shield"></i> <?php i18n_e('account.two_factor_authentication', [], 'Two-Factor Authentication'); ?></h3>
                            <div id="twoFactorAlert"></div>
                            <div class="security-option">
                                <div>
                                    <div class="security-option-title">
                                        <i class="fas fa-envelope-circle-check"></i>
                                        <?php i18n_e('account.login_verification', [], 'Login verification'); ?>
                                        <span class="security-status <?= !empty($user['two_factor_enabled']) ? 'enabled' : '' ?>" id="twoFactorStatus">
                                            <?= !empty($user['two_factor_enabled']) ? i18n_t('account.enabled', [], 'Enabled') : i18n_t('account.disabled', [], 'Disabled') ?>
                                        </span>
                                    </div>
                                    <p class="security-option-desc">
                                        <?php i18n_e('account.two_factor_desc', [], 'Require a second step after password login. Choose email, WhatsApp, or an authenticator app.'); ?>
                                    </p>
                                    <div class="two-factor-methods">
                                        <label>
                                            <?php i18n_e('account.login_method', [], 'Login method'); ?>
                                            <select id="twoFactorMethod">
                                                <option value="email" <?= ($user['two_factor_method'] ?? 'email') === 'email' ? 'selected' : '' ?>><?php i18n_e('account.email_code', [], 'Email code'); ?></option>
                                                <option value="whatsapp" <?= ($user['two_factor_method'] ?? '') === 'whatsapp' ? 'selected' : '' ?>><?php i18n_e('account.whatsapp_code', [], 'WhatsApp code'); ?><?= empty($user['telephone']) ? ' (' . i18n_t('account.add_phone_first', [], 'add phone first') . ')' : '' ?></option>
                                                <option value="authenticator" <?= ($user['two_factor_method'] ?? '') === 'authenticator' ? 'selected' : '' ?>><?php i18n_e('account.authenticator_app', [], 'Authenticator app'); ?><?= empty($user['two_factor_totp_secret']) ? ' (' . i18n_t('account.setup_required', [], 'setup required') . ')' : '' ?></option>
                                            </select>
                                        </label>
                                        <button type="button" class="btn-secondary" id="setupAuthenticatorBtn">
                                            <i class="fas fa-qrcode"></i> <?php i18n_e('account.setup_authenticator', [], 'Setup Authenticator'); ?>
                                        </button>
                                    </div>
                                    <div class="authenticator-setup" id="authenticatorSetup">
                                        <div class="authenticator-qr">
                                            <img id="authenticatorQr" src="" alt="<?= htmlspecialchars(i18n_t('account.authenticator_qr_alt', [], 'Authenticator QR code'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div>
                                            <strong><?php i18n_e('account.scan_qr_code', [], 'Scan QR code'); ?></strong>
                                            <small><?php i18n_e('account.totp_app_hint', [], 'Use Google Authenticator, Microsoft Authenticator, 1Password, or any TOTP app.'); ?></small>
                                            <code id="authenticatorSecret"></code>
                                            <input type="text" id="authenticatorCode" inputmode="numeric" maxlength="6" placeholder="<?php i18n_e('account.six_digit_app_code', [], '6-digit app code'); ?>">
                                            <button type="button" class="btn-save" id="confirmAuthenticatorBtn"><?php i18n_e('account.confirm_authenticator', [], 'Confirm Authenticator'); ?></button>
                                        </div>
                                    </div>
                                    <div class="two-factor-confirm" id="twoFactorConfirm" data-confirm-mode="<?= h($twoFactorConfirmMode) ?>">
                                        <form class="account-form" onsubmit="return false;">
                                            <div class="form-group mb-14">
                                                <label for="twoFactorPassword">
                                                    <?= $twoFactorConfirmMode === 'password'
                                                        ? h(i18n_t('account.confirm_password', [], 'Confirm Password'))
                                                        : h(i18n_t('account.email_verification_code', [], 'Email verification code')) ?>
                                                </label>
                                                <?php if ($twoFactorConfirmMode === 'password'): ?>
                                                    <input type="password" id="twoFactorPassword" placeholder="<?php i18n_e('account.enter_current_password', [], 'Enter your current password'); ?>">
                                                <?php else: ?>
                                                    <p class="security-option-desc" style="margin-top:0;">
                                                        <?= h(i18n_t('account.oauth_2fa_email_hint', [], 'This account signs in with Google or another provider, so we will confirm ownership with an email code instead of a password.')) ?>
                                                    </p>
                                                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                                                        <button type="button" class="btn-secondary" id="sendTwoFactorCodeBtn">
                                                            <i class="fas fa-envelope"></i> <?= h(i18n_t('account.send_code', [], 'Send code')) ?>
                                                        </button>
                                                        <small id="twoFactorCodeHint" style="align-self:center;color:var(--muted);"></small>
                                                    </div>
                                                    <input type="text" id="twoFactorPassword" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="<?= h(i18n_t('account.enter_six_digit_code', [], 'Enter the 6-digit email code')) ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="two-factor-confirm-actions">
                                                <button type="button" class="btn-save" id="twoFactorConfirmBtn">
                                                    <i class="fas fa-shield-halved"></i> <?php i18n_e('account.confirm', [], 'Confirm'); ?>
                                                </button>
                                                <button type="button" class="btn-secondary" id="twoFactorCancelBtn">
                                                    <?php i18n_e('account.cancel', [], 'Cancel'); ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <label class="switch-control" aria-label="<?= htmlspecialchars(i18n_t('account.toggle_two_factor', [], 'Toggle email two-factor authentication'), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="checkbox" id="twoFactorToggle" <?= !empty($user['two_factor_enabled']) ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <div class="backup-codes-section" id="backupCodesSection" style="display: <?= !empty($user['two_factor_enabled']) ? 'block' : 'none' ?>;">
                                <div class="backup-codes-header">
                                    <div>
                                        <strong class="backup-codes-title"><i class="fas fa-file-shield"></i> <?php i18n_e('account.one_time_backup_codes', [], 'One-Time Backup Codes'); ?></strong>
                                        <small class="backup-codes-desc"><?php i18n_e('account.backup_codes_desc', [], 'Use these 8-character codes to log in if you lose access to your primary device.'); ?></small>
                                    </div>
                                    <button type="button" class="btn-secondary btn-sm" id="regenerateBackupCodesBtn">
                                        <i class="fas fa-arrows-rotate"></i> <?php i18n_e('account.regenerate_codes', [], 'Regenerate Codes'); ?>
                                    </button>
                                </div>
                                
                                <div class="backup-codes-display" id="backupCodesDisplay">
                                    <div class="backup-codes-grid" id="backupCodesGrid">
                                        <!-- Populated dynamically via JS -->
                                    </div>
                                    <div class="backup-codes-actions">
                                        <button type="button" class="btn-secondary" id="copyBackupCodesBtn">
                                            <i class="far fa-copy"></i> <?php i18n_e('account.copy_codes', [], 'Copy Codes'); ?>
                                        </button>
                                        <button type="button" class="btn-secondary" id="downloadBackupCodesBtn">
                                            <i class="fas fa-download"></i> <?php i18n_e('account.download_as_text', [], 'Download as Text'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3><i class="fas fa-link"></i> <?php i18n_e('account.linked_accounts', [], 'Linked Accounts'); ?></h3>
                            <p class="linked-accounts-desc">
                                <?php i18n_e('account.linked_accounts_desc', [], 'Connect your social accounts for faster login. You can use any linked account to sign in without a password.'); ?>
                            </p>
                            
                            <?php
                            // Fetch OAuth connection status
                            try {
                                $stmt = $pdo->prepare("SELECT google_id, facebook_id, discord_id, steam_id FROM Client WHERE id_client = ?");
                                $stmt->execute([$clientId]);
                                $oauthData = $stmt->fetch(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                // Columns may not exist yet, use empty values
                                $oauthData = [
                                    'google_id' => null,
                                    'facebook_id' => null,
                                    'discord_id' => null,
                                    'steam_id' => null
                                ];
                            }
                            
                            $linkedAccounts = [
                                'google' => [
                                    'name' => 'Google',
                                    'icon' => 'fab fa-google',
                                    'color' => '#4285F4',
                                    'connected' => !empty($oauthData['google_id']),
                                    'id' => $oauthData['google_id'] ?? null
                                ],
                                'facebook' => [
                                    'name' => 'Facebook',
                                    'icon' => 'fab fa-facebook',
                                    'color' => '#1877F2',
                                    'connected' => !empty($oauthData['facebook_id']),
                                    'id' => $oauthData['facebook_id'] ?? null
                                ],
                                'discord' => [
                                    'name' => 'Discord',
                                    'icon' => 'fab fa-discord',
                                    'color' => '#5865F2',
                                    'connected' => !empty($oauthData['discord_id']),
                                    'id' => $oauthData['discord_id'] ?? null
                                ],
                                'steam' => [
                                    'name' => 'Steam',
                                    'icon' => 'fab fa-steam',
                                    'color' => '#66c0f4',
                                    'connected' => !empty($oauthData['steam_id']),
                                    'id' => $oauthData['steam_id'] ?? null
                                ]
                            ];
                            
                            foreach ($linkedAccounts as $provider => $account):
                            ?>
                                <div class="security-option mb-16">
                                    <div>
                                        <div class="security-option-title">
                                            <i class="<?= h($account['icon']) ?>" style="color: <?= h($account['color']) ?>;"></i>
                                            <?= h($account['name']) ?>
                                            <span class="security-status <?= $account['connected'] ? 'enabled' : '' ?>">
                                                <?= h($account['connected'] ? i18n_t('account.connected', [], 'Connected') : i18n_t('account.not_connected', [], 'Not Connected')) ?>
                                            </span>
                                        </div>
                                        <p class="security-option-desc">
                                            <?php if ($account['connected']): ?>
                                                <?php i18n_e('account.sign_in_with_provider', ['provider' => $account['name']], 'Sign in with your {provider} account.'); ?>
                                                <span class="font-mono" style="font-size: 0.8rem; color: var(--cyan);">
                                                    ID: <?= h(substr($account['id'], 0, 12)) ?>...
                                                </span>
                                            <?php else: ?>
                                                <?php i18n_e('account.connect_provider', ['provider' => $account['name']], 'Connect your {provider} account for one-click login.'); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="security-option-actions">
                                        <?php if ($account['connected']): ?>
                                            <button type="button" class="btn-danger btn-security-action" 
                                                    onclick="unlinkAccount('<?= h($provider) ?>', '<?= h($account['name']) ?>')">
                                                <i class="fas fa-unlink"></i> <?php i18n_e('account.disconnect', [], 'Disconnect'); ?>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= h($provider) ?>-login.php?link=1" class="btn-secondary btn-security-connect">
                                                <i class="fas fa-link"></i> <?php i18n_e('account.connect', [], 'Connect'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="security-note">
                                <div class="security-note-content">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="security-note-body">
                                        <strong><?php i18n_e('account.security_note', [], 'Security Note'); ?></strong>
                                        <p>
                                            <?php i18n_e('account.security_note_body', [], 'Linking accounts allows you to sign in using those providers. Make sure to keep your social accounts secure with strong passwords and two-factor authentication.'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Password Change ────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-lock"></i> <?php i18n_e('account.change_password', [], 'Change Password'); ?></h3>
                            <div id="passAlert"></div>
                            <form class="account-form" onsubmit="return false;">
                                <div class="form-group">
                                    <label for="accCurrentPass"><?php i18n_e('account.current_password', [], 'Current Password'); ?></label>
                                    <input type="password" id="accCurrentPass" name="current_password"
                                        placeholder="<?= htmlspecialchars(i18n_t('account.current_password_placeholder', [], 'Enter current password'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="accNewPass"><?php i18n_e('account.new_password', [], 'New Password'); ?></label>
                                    <input type="password" id="accNewPass" name="new_password"
                                        placeholder="<?= htmlspecialchars(i18n_t('account.new_password_placeholder', [], 'Min. 8 chars, one number, one symbol'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <button type="button" class="btn-save" id="changePassBtn">
                                    <i class="fas fa-key"></i> <?php i18n_e('account.update_password', [], 'Update Password'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- ── Danger Zone ────────────────────────── -->
                        <div class="section-card danger-zone">
                            <h3><i class="fas fa-skull-crossbones"></i> <?php i18n_e('account.danger_zone', [], 'Danger Zone'); ?></h3>
                            <p><?php i18n_e('account.danger_zone_body', [], 'Once you delete your account, you have 5 days to restore it. After that, all your data (profile, orders, addresses) will be permanently erased. This action cannot be undone after the grace period.'); ?></p>
                            <button type="button" class="btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> <?php i18n_e('account.delete_my_account', [], 'Delete My Account'); ?>
                            </button>
                        </div>
                    <?php elseif ($activeTab === 'support'): ?>
                        <!-- ── Support Tickets ──────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-headset"></i> <?php i18n_e('account.my_service_tickets', [], 'My Service Tickets'); ?></h3>
                            <?php if (empty($supportTickets)): ?>
                                <div class="support-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p><?php i18n_e('account.no_service_tickets', [], 'No service tickets yet.'); ?></p>
                                    <span><?php i18n_e('account.service_tickets_empty_help', [], 'Need a return, refund, or warranty claim? File a request below.'); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="ticket-list">
                                    <?php foreach ($supportTickets as $ticket): ?>
                                        <?php
                                            $tStatus = $ticket['status'];
                                            $statusClass = match($tStatus) {
                                                'submitted' => 'status-submitted',
                                                'reviewing' => 'status-reviewing',
                                                'approved', 'resolved' => 'status-approved',
                                                'awaiting_item', 'inspecting' => 'status-progress',
                                                'rejected' => 'status-rejected',
                                                default => 'status-submitted'
                                            };
                                            $priorityIcon = $ticket['priority'] === 'urgent' ? '<span class="priority-urgent"><i class="fas fa-bolt"></i> ' . h(i18n_t('account.urgent', [], 'Urgent')) . '</span>' : '';
                                            $typeLabel = ucwords(str_replace('_', ' ', $ticket['request_type']));
                                        ?>
                                        <div class="ticket-card" onclick="this.classList.toggle('expanded')">
                                            <div class="ticket-header">
                                                <div class="ticket-meta">
                                                    <strong class="ticket-code"><?= h($ticket['ticket_code']) ?></strong>
                                                    <span class="ticket-date"><?= date('M j, Y', strtotime($ticket['created_at'])) ?></span>
                                                </div>
                                                <div class="ticket-badges">
                                                    <?= $priorityIcon ?>
                                                    <span class="ticket-type"><?= h($typeLabel) ?></span>
                                                    <span class="ticket-status <?= $statusClass ?>"><?= h(accountStatusLabel($tStatus)) ?></span>
                                                </div>
                                                <i class="fas fa-chevron-down ticket-chevron"></i>
                                            </div>
                                            <div class="ticket-product">
                                                <i class="fas fa-microchip"></i> <?= h($ticket['product_name']) ?>
                                                <?php if ($ticket['order_id']): ?>
                                                    <span class="ticket-order"><?php i18n_e('account.order_number_short', ['id' => (int) $ticket['order_id']], 'Order #{id}'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ticket-details">
                                                <div class="ticket-detail-grid">
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.condition', [], 'Condition'); ?></span>
                                                        <span class="detail-value"><?= h(ucwords(str_replace('_', ' ', $ticket['product_condition']))) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.preferred_resolution', [], 'Preferred Resolution'); ?></span>
                                                        <span class="detail-value"><?= h(ucwords(str_replace('_', ' ', $ticket['preferred_resolution']))) ?></span>
                                                    </div>
                                                    <?php if ($ticket['serial_number']): ?>
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.serial_number', [], 'Serial Number'); ?></span>
                                                        <span class="detail-value font-mono"><?= h($ticket['serial_number']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <span class="detail-label"><?php i18n_e('account.last_updated', [], 'Last Updated'); ?></span>
                                                        <span class="detail-value"><?= date('M j, Y H:i', strtotime($ticket['updated_at'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="ticket-reason">
                                                    <span class="detail-label"><?php i18n_e('account.description', [], 'Description'); ?></span>
                                                    <p><?= nl2br(h($ticket['reason'])) ?></p>
                                                </div>
                                                <?php if ($ticket['next_action']): ?>
                                                <div class="ticket-next-action">
                                                    <i class="fas fa-arrow-right"></i>
                                                    <span><?= h($ticket['next_action']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── File New Ticket ─────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-paper-plane"></i> <?php i18n_e('account.file_new_request', [], 'File a New Request'); ?></h3>
                            <p class="support-form-desc"><?php i18n_e('account.support_form_prefill', [], 'Your name and email are pre-filled from your account.'); ?></p>
                            <div id="supportFormAlert"></div>
                            <form class="account-form" id="supportTicketForm" onsubmit="return false;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-order"><?php i18n_e('account.order_number', [], 'Order Number'); ?></label>
                                        <input type="number" id="sup-order" name="order_id" min="1" placeholder="<?= htmlspecialchars(i18n_t('account.support_order_placeholder', [], 'e.g. 1004'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-product"><?php i18n_e('account.product_concerned', [], 'Product Concerned'); ?></label>
                                        <input type="text" id="sup-product" name="product_name" placeholder="<?= htmlspecialchars(i18n_t('account.support_product_placeholder', [], 'e.g. NVIDIA RTX 4080 Super'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-type"><?php i18n_e('account.request_type', [], 'Request Type'); ?></label>
                                        <select id="sup-type" name="request_type" required>
                                            <option value=""><?php i18n_e('account.choose', [], 'Choose...'); ?></option>
                                            <option value="return"><?php i18n_e('account.return', [], 'Return'); ?></option>
                                            <option value="refund"><?php i18n_e('account.refund', [], 'Refund'); ?></option>
                                            <option value="exchange"><?php i18n_e('account.exchange', [], 'Exchange'); ?></option>
                                            <option value="warranty"><?php i18n_e('account.warranty_claim', [], 'Warranty Claim'); ?></option>
                                            <option value="repair"><?php i18n_e('account.repair_diagnostic', [], 'Repair / Diagnostic'); ?></option>
                                            <option value="missing"><?php i18n_e('account.missing_item', [], 'Missing Item'); ?></option>
                                            <option value="damaged"><?php i18n_e('account.damaged_arrival', [], 'Damaged on Arrival'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-resolution"><?php i18n_e('account.preferred_resolution', [], 'Preferred Resolution'); ?></label>
                                        <select id="sup-resolution" name="preferred_resolution" required>
                                            <option value=""><?php i18n_e('account.choose', [], 'Choose...'); ?></option>
                                            <option value="refund"><?php i18n_e('account.refund', [], 'Refund'); ?></option>
                                            <option value="replacement"><?php i18n_e('account.replacement', [], 'Replacement'); ?></option>
                                            <option value="store_credit"><?php i18n_e('account.store_credit', [], 'Store Credit'); ?></option>
                                            <option value="repair"><?php i18n_e('account.repair', [], 'Repair'); ?></option>
                                            <option value="diagnostic"><?php i18n_e('account.diagnostic_report', [], 'Diagnostic Report'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-condition"><?php i18n_e('account.product_condition', [], 'Product Condition'); ?></label>
                                        <select id="sup-condition" name="product_condition" required>
                                            <option value=""><?php i18n_e('account.choose', [], 'Choose...'); ?></option>
                                            <option value="sealed"><?php i18n_e('account.sealed_unopened', [], 'Sealed / Unopened'); ?></option>
                                            <option value="opened_unused"><?php i18n_e('account.opened_unused', [], 'Opened but Unused'); ?></option>
                                            <option value="used"><?php i18n_e('account.used_installed', [], 'Used / Installed'); ?></option>
                                            <option value="defective"><?php i18n_e('account.defective', [], 'Defective'); ?></option>
                                            <option value="damaged_package"><?php i18n_e('account.damaged_packaging', [], 'Damaged Packaging'); ?></option>
                                            <option value="missing_item"><?php i18n_e('account.missing_item_accessory', [], 'Missing Item/Accessory'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-serial"><?php i18n_e('account.serial_number', [], 'Serial Number'); ?> <small style="color:var(--muted)">(<?php i18n_e('account.optional', [], 'optional'); ?>)</small></label>
                                        <input type="text" id="sup-serial" name="serial_number" placeholder="<?php i18n_e('account.recommended_warranty', [], 'Recommended for warranty'); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="sup-reason"><?php i18n_e('account.describe_issue', [], 'Describe the Issue'); ?></label>
                                    <textarea id="sup-reason" name="reason" rows="5" minlength="20" placeholder="<?php i18n_e('account.describe_issue_placeholder', [], 'Tell us what happened, when you noticed it, and what resolution you expect.'); ?>" required></textarea>
                                </div>
                                <label class="support-form-checkbox">
                                    <input type="checkbox" name="package_opened" value="1"> <?php i18n_e('account.package_opened', [], 'The retail package has been opened.'); ?>
                                </label>
                                <button type="button" class="btn-save" id="submitSupportTicket">
                                    <i class="fas fa-paper-plane"></i> <?php i18n_e('account.submit_service_ticket', [], 'Submit Service Ticket'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- ── Need Help CTA ───────────────────── -->
                        <div class="section-card text-center">
                            <p class="support-help-text"><?php i18n_e('account.need_help_policy', [], 'Need immediate help or want the full policy details?'); ?></p>
                            <div class="support-help-actions">
                                <a href="returns-refunds.php" class="btn-view"><i class="fas fa-book"></i> <?php i18n_e('account.full_policy_faq', [], 'Full Policy & FAQ'); ?></a>
                                <a href="mailto:support@marocpc.com" class="btn-view"><i class="fas fa-envelope"></i> <?php i18n_e('account.email_support', [], 'Email Support'); ?></a>
                                <a href="tel:+212618821949" class="btn-view"><i class="fas fa-phone"></i> <?php i18n_e('account.call_us', [], 'Call Us'); ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <!-- • Order Tracking Modal • -->
    <div class="tracking-modal-backdrop" id="trackingModalBackdrop">
        <div class="tracking-modal">
            <div class="tracking-header">
                <h3 id="trackingOrderId"><?php i18n_e('account.order_number_short', ['id' => 0], 'Order #{id}'); ?></h3>
                <button class="btn-tracking-close" id="trackingModalClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="tracking-body">
                <div class="estimated-delivery">
                    <?php i18n_e('account.estimated_delivery', [], 'Estimated Delivery'); ?>:
                    <span id="trackingEstimatedDelivery"><?php i18n_e('account.calculating', [], 'Calculating...'); ?></span>
                </div>

                <div class="tracking-map">
                    <div class="map-route-line"></div>
                    <div class="map-route-progress" id="mapRouteProgress" style="width: 0%;"></div>
                    <div class="city-point" id="city-casa">
                        <div class="city-dot"></div>
                        <div class="city-name"><?php i18n_e('account.city_casablanca', [], 'Casablanca'); ?></div>
                    </div>
                    <div class="city-point" id="city-rabat">
                        <div class="city-dot"></div>
                        <div class="city-name"><?php i18n_e('account.city_rabat', [], 'Rabat'); ?></div>
                    </div>
                    <div class="city-point" id="city-tanger">
                        <div class="city-dot"></div>
                        <div class="city-name"><?php i18n_e('account.city_tanger', [], 'Tanger'); ?></div>
                    </div>
                    <div class="city-point" id="city-dest">
                        <div class="city-dot"></div>
                        <div class="city-name"><?php i18n_e('account.destination', [], 'Destination'); ?></div>
                    </div>
                </div>
                
                <div class="tracking-assembly-container" id="trackingAssemblyContainer" style="display: none; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
                    <h4 class="tracking-assembly-header"><i class="fas fa-screwdriver-wrench"></i> <?php i18n_e('account.pc_assembly_progress', [], 'PC Assembly Progress'); ?></h4>
                    <div class="tracking-progress-container" style="margin-top: 10px;">
                        <div class="tracking-progress-bar">
                            <div class="tracking-progress-line"></div>
                            <div class="tracking-progress-fill" id="trackingAssemblyFill" style="width: 0%; background: var(--cyan);"></div>
                            <div class="tracking-step" id="step-assembly-gathering_parts" title="<?php i18n_e('account.gathering_parts', [], 'Gathering Parts'); ?>"><i class="fas fa-dolly"></i></div>
                            <div class="tracking-step" id="step-assembly-building" title="<?php i18n_e('account.building', [], 'Building'); ?>"><i class="fas fa-hammer"></i></div>
                            <div class="tracking-step" id="step-assembly-testing" title="<?php i18n_e('account.testing', [], 'Testing'); ?>"><i class="fas fa-microchip"></i></div>
                            <div class="tracking-step" id="step-assembly-qc_passed" title="<?php i18n_e('account.qc_passed', [], 'QC Passed'); ?>"><i class="fas fa-clipboard-check"></i></div>
                            <div class="tracking-step" id="step-assembly-ready" title="<?php i18n_e('account.ready', [], 'Ready'); ?>"><i class="fas fa-check-double"></i></div>
                        </div>
                    </div>
                    <div class="mt-16 text-center">
                        <a href="" id="assemblyGuideLink" class="tracking-assembly-guide">
                            <i class="fas fa-book-open"></i> <?php i18n_e('account.assembly_guide', [], 'View Step-by-Step Interactive Assembly Guide'); ?>
                        </a>
                    </div>
                </div>

                <div class="tracking-progress-container">
                    <h4 class="tracking-shipping-header"><i class="fas fa-truck"></i> <?php i18n_e('account.shipping_progress', [], 'Shipping Progress'); ?></h4>
                    <div class="tracking-progress-bar">
                        <div class="tracking-progress-line"></div>
                        <div class="tracking-progress-fill" id="trackingProgressFill" style="width: 0%;"></div>
                        <div class="tracking-step" id="step-pending" title="<?php i18n_e('account.pending', [], 'Pending'); ?>"><i class="fas fa-clock"></i></div>
                        <div class="tracking-step" id="step-processing" title="<?php i18n_e('account.processing', [], 'Processing'); ?>"><i class="fas fa-cog"></i></div>
                        <div class="tracking-step" id="step-shipped" title="<?php i18n_e('account.shipped', [], 'Shipped'); ?>"><i class="fas fa-box"></i></div>
                        <div class="tracking-step" id="step-out_for_delivery" title="<?php i18n_e('account.out_for_delivery', [], 'Out for Delivery'); ?>"><i class="fas fa-truck"></i></div>
                        <div class="tracking-step" id="step-delivered" title="<?php i18n_e('account.delivered', [], 'Delivered'); ?>"><i class="fas fa-check"></i></div>
                    </div>
                </div>

                <div class="tracking-timeline" id="trackingTimeline">
                    <!-- Timeline events injected here -->
                </div>

                <div class="tracking-order-summary">
                    <h4><?php i18n_e('account.items', [], 'Items'); ?></h4>
                    <div id="trackingItemsList"></div>
                    <div style="border-top: 1px solid var(--border); margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between;">
                        <span class="tracking-item-name"><?php i18n_e('account.total', [], 'Total'); ?></span>
                        <span class="tracking-item-price" id="trackingTotalCost" style="color: var(--cyan);" data-locale-symbol="<?= i18n_currency_symbol() ?>"><?= i18n_format_money(0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Delete confirmation modal ─────────────────────── -->
    <div class="delete-modal-backdrop" id="deleteModalBackdrop">
        <div class="delete-modal">
            <div class="delete-icon"><i class="fas fa-user-slash"></i></div>
            <h3><?php i18n_e('account.delete_modal_title', [], 'Delete Your Account?'); ?></h3>
            <p><?php i18n_e('account.delete_modal_body', [], 'This will schedule your account for permanent deletion in 5 days. You can restore it before then.'); ?></p>
            <input type="password" id="deleteConfirmPassword" placeholder="<?php i18n_e('account.enter_password_confirm', [], 'Enter your password to confirm'); ?>">
            <div class="delete-modal-actions">
                <button class="btn-modal-cancel" id="deleteModalCancel"><?php i18n_e('account.cancel', [], 'Cancel'); ?></button>
                <button class="btn-modal-delete" id="deleteModalConfirm">
                    <i class="fas fa-trash-alt"></i> <?php i18n_e('account.delete_account', [], 'Delete Account'); ?>
                </button>
            </div>
        </div>
    </div>

    <footer class="footer footer-main">
        <div class="container">
            <p class="footer-text">&copy; 2026 Maroc PC. <?php i18n_e('footer.all_rights', [], 'All rights reserved.'); ?></p>
            <div class="footer-links">
                <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank" class="footer-link"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="https://x.com/Maroc_PC_PHP" target="_blank" class="footer-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="14" height="14" fill="currentColor" style="vertical-align: middle; margin-right: 4px;"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg> X (Twitter)</a>
                <a href="https://www.instagram.com/marocpc57" target="_blank" class="footer-link"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank" class="footer-link"><i class="fab fa-youtube"></i> YouTube</a>
            </div>
        </div>
    </footer>

    <div class="toast" id="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <?= i18n_language_switcher_assets() ?>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/account.js"></script>
    <script>
    // ── Support ticket form submission ──────────────────
    (function() {
        const supportI18n = {
            fillRequired: <?= json_encode(i18n_t('account.support_fill_required', [], 'Please fill in all required fields (description must be at least 20 characters).'), JSON_UNESCAPED_UNICODE) ?>,
            submitting: <?= json_encode(i18n_t('account.submitting', [], 'Submitting...'), JSON_UNESCAPED_UNICODE) ?>,
            submitServiceTicket: <?= json_encode(i18n_t('account.submit_service_ticket', [], 'Submit Service Ticket'), JSON_UNESCAPED_UNICODE) ?>,
            ticketCreated: <?= json_encode(i18n_t('account.ticket_created', [], 'Ticket {ticket} created! Priority: {priority}. ETA: {eta}.'), JSON_UNESCAPED_UNICODE) ?>,
            somethingWrong: <?= json_encode(i18n_t('account.something_wrong', [], 'Something went wrong.'), JSON_UNESCAPED_UNICODE) ?>,
            networkError: <?= json_encode(i18n_t('account.network_error_simple', [], 'Network error. Please try again.'), JSON_UNESCAPED_UNICODE) ?>
        };
        const supportT = (template, values = {}) => template.replace(/\{(\w+)\}/g, (match, key) => (
            Object.prototype.hasOwnProperty.call(values, key) ? values[key] : match
        ));
        const form = document.getElementById('supportTicketForm');
        const btn = document.getElementById('submitSupportTicket');
        const alertBox = document.getElementById('supportFormAlert');
        if (!form || !btn) return;

        btn.addEventListener('click', async () => {
            const fd = new FormData(form);
            const data = Object.fromEntries(fd.entries());

            // Inject account info
            data.customer_name = <?= json_encode($user['nom'] ?? '') ?>;
            data.email = <?= json_encode($user['email'] ?? '') ?>;
            data.phone = <?= json_encode($user['telephone'] ?? '') ?>;
            data.order_id = parseInt(data.order_id, 10) || 0;
            data.package_opened = fd.has('package_opened') ? 1 : 0;

            if (!data.order_id || !data.product_name || !data.request_type || !data.preferred_resolution || !data.product_condition || (data.reason || '').length < 20) {
                showAlert(supportI18n.fillRequired, 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${supportI18n.submitting}`;

            try {
                const res = await fetch('api/after-sales-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    showAlert(
                        `<strong>${supportT(supportI18n.ticketCreated, { ticket: result.ticket, priority: result.priority, eta: result.eta })}</strong><br><small>${result.next_action}</small>`,
                        'success'
                    );
                    form.reset();
                    // Reload after a short delay to show new ticket
                    setTimeout(() => window.location.reload(), 2500);
                } else {
                    showAlert(result.error || supportI18n.somethingWrong, 'error');
                }
            } catch (err) {
                showAlert(supportI18n.networkError, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${supportI18n.submitServiceTicket}`;
            }
        });

        function showAlert(msg, type) {
            if (!alertBox) return;
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const alertClass = type === 'success' ? 'support-ticket-alert-success' : 'support-ticket-alert-error';
            alertBox.innerHTML = `<div class="support-ticket-alert ${alertClass}"><i class='fas fa-${icon}' style='margin-top:3px;'></i><span>${msg}</span></div>`;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    })();
    </script>

    <script>
        // ── Unlink OAuth Account ──────────────────────────────
        const accountInlineI18n = {
            disconnectConfirm: <?= json_encode(i18n_t('account.disconnect_confirm', [], 'Are you sure you want to disconnect your {provider} account?' . "\n\n" . 'You can reconnect it anytime.'), JSON_UNESCAPED_UNICODE) ?>,
            disconnectSuccess: <?= json_encode(i18n_t('account.disconnect_success', [], '{provider} account disconnected successfully!'), JSON_UNESCAPED_UNICODE) ?>,
            disconnectFailed: <?= json_encode(i18n_t('account.disconnect_failed', [], 'Failed to disconnect account. Please try again.'), JSON_UNESCAPED_UNICODE) ?>,
            networkError: <?= json_encode(i18n_t('account.network_error_retry', [], 'Network error. Please check your connection and try again.'), JSON_UNESCAPED_UNICODE) ?>
        };
        const accountInlineT = (template, providerName) => template.replace('{provider}', providerName);

        async function unlinkAccount(provider, providerName) {
            if (!confirm(accountInlineT(accountInlineI18n.disconnectConfirm, providerName))) {
                return;
            }

            try {
                const response = await fetch('api/unlink-oauth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ provider })
                });

                const data = await response.json();

                if (data.success) {
                    alert(accountInlineT(accountInlineI18n.disconnectSuccess, providerName));
                    location.reload();
                } else {
                    alert(data.error || accountInlineI18n.disconnectFailed);
                }
            } catch (error) {
                console.error('Unlink error:', error);
                alert(accountInlineI18n.networkError);
            }
        }

        // Plant footprint for session expiration detection
        localStorage.setItem('has_active_session', '1');
    </script>
</body>

</html>
