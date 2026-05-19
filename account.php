<?php
require_once 'config.php';
require_once 'two-factor-helpers.php';

if (empty($_SESSION['client_id'])) {
    $isExpired = isset($_COOKIE['has_active_session']) ? '&session_expired=1' : '';
    header('Location: login.php?next=' . urlencode('account.php') . $isExpired);
    exit();
}

$pdo = db();
ensureAccountTwoFactorColumns($pdo);
twoFactorEnsureColumns($pdo);
ensureAccountProfileImageColumn($pdo);
$stmt = $pdo->prepare("SELECT id_client, nom, email, adresse, telephone, date_naissance, profile_image, created_at, deleted_at, two_factor_enabled, two_factor_method, two_factor_totp_secret FROM Client WHERE id_client = ?");
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
if ($profileImage === '') {
    $profileImage = $defaultProfileImage;
}

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
$accountAgeStr = $accountAge->y > 0 ? $accountAge->y . 'y ' . $accountAge->m . 'mo' : ($accountAge->m > 0 ? $accountAge->m . ' months' : $accountAge->d . ' days');

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
    'Name' => trim((string) ($user['nom'] ?? '')) !== '',
    'Email' => trim((string) ($user['email'] ?? '')) !== '',
    'Phone' => trim((string) ($user['telephone'] ?? '')) !== '',
    'Address' => trim((string) ($user['adresse'] ?? '')) !== '',
    'Birthday' => trim((string) ($user['date_naissance'] ?? '')) !== '',
    'Profile photo' => trim((string) ($user['profile_image'] ?? '')) !== '',
    '2FA' => !empty($user['two_factor_enabled']),
];
$profileCompleted = count(array_filter($profileChecklist));
$profileCompletion = (int) round(($profileCompleted / max(1, count($profileChecklist))) * 100);

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function moneyMAD(float $v): string
{
    return number_format($v, 2, '.', ',') . ' MAD';
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
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/account.css">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">
    <script src="assets/js/wishlist.js"></script>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <style>
        /* ── Account page enhancements ───────────────────────── */
        .account-hero {
            background: linear-gradient(135deg, rgba(0,245,212,0.06) 0%, transparent 60%);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 36px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 28px;
            position: relative;
            overflow: hidden;
        }
        .account-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(0,245,212,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, #00f5d4, #00b8a9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
            font-size: 1.8rem;
            font-weight: 800;
            color: #000;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(0,245,212,0.2);
        }
        .hero-info h2 {
            margin: 0 0 4px;
            font-size: 1.5rem;
            color: var(--text);
        }
        .hero-info p {
            color: var(--muted);
            font-size: 0.88rem;
            margin: 0;
        }
        .hero-info .member-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 4px 14px;
            background: rgba(0,245,212,0.08);
            border: 1px solid rgba(0,245,212,0.2);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #00f5d4;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* ── Stats grid ────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .account-stats {
            display: flex;
            flex-wrap: nowrap;
        }
        .account-stats .stat-card {
            flex: 1 1 0;
            min-width: 0;
        }
        .stat-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: rgba(0,245,212,0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .stat-card .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--cyan);
            margin-bottom: 4px;
        }
        .stat-card .stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* ── Section cards ──────────────────────────────────── */
        .section-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 36px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }
        .section-card h3 {
            font-size: 1.1rem;
            margin: 0 0 24px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-card h3 i { color: var(--cyan); }
        .section-card .overview-heading h3 {
            margin: 4px 0 0;
            font-size: 1rem;
            line-height: 1.35;
        }

        /* ── Danger zone ───────────────────────────────────── */
        .danger-zone {
            border-color: rgba(255,61,90,0.25);
            background: rgba(255,61,90,0.02);
        }
        .danger-zone h3 i { color: var(--red); }
        .danger-zone p {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0 0 20px;
        }
        .btn-danger {
            background: transparent;
            color: var(--red);
            border: 1px solid rgba(255,61,90,0.4);
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Syne', sans-serif;
        }
        .btn-danger:hover {
            background: rgba(255,61,90,0.1);
            border-color: var(--red);
            transform: translateY(-1px);
        }

        /* ── Restore banner ─────────────────────────────────── */
        .restore-banner {
            background: rgba(255,193,7,0.06);
            border: 1px solid rgba(255,193,7,0.3);
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .restore-banner .restore-icon {
            font-size: 2.4rem;
            color: #ffcf4d;
            flex-shrink: 0;
        }
        .restore-banner .restore-text h4 {
            margin: 0 0 4px;
            color: #ffcf4d;
            font-size: 1rem;
        }
        .restore-banner .restore-text p {
            margin: 0;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .btn-restore {
            background: #ffcf4d;
            color: #000;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Orbitron', sans-serif;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .btn-restore:hover {
            background: #ffc107;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,193,7,0.3);
        }

        /* ── Delete modal ──────────────────────────────────── */
        .delete-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .delete-modal-backdrop.is-open {
            opacity: 1;
            pointer-events: all;
        }
        .delete-modal {
            width: 100%;
            max-width: 460px;
            background: var(--page-bg-2);
            border: 1px solid rgba(255,61,90,0.3);
            border-radius: 20px;
            padding: 36px;
            text-align: center;
            transform: scale(0.95);
            transition: transform 0.25s ease;
        }
        .delete-modal-backdrop.is-open .delete-modal {
            transform: scale(1);
        }
        .delete-modal .delete-icon {
            font-size: 3rem;
            color: var(--red);
            margin-bottom: 16px;
        }
        .delete-modal h3 {
            margin: 0 0 8px;
            font-size: 1.2rem;
            color: var(--text);
        }
        .delete-modal p {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0 0 24px;
        }
        .delete-modal input {
            width: 100%;
            padding: 14px 18px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: 20px;
            transition: border-color 0.2s;
        }
        .delete-modal input:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(255,61,90,0.1);
        }
        .delete-modal-actions {
            display: flex;
            gap: 12px;
        }
        .delete-modal-actions button {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Syne', sans-serif;
        }
        .btn-modal-cancel {
            background: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .btn-modal-cancel:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }
        .btn-modal-delete {
            background: var(--red);
            border: 1px solid var(--red);
            color: #fff;
        }
        .btn-modal-delete:hover {
            background: #e0354f;
            transform: translateY(-1px);
        }
        .btn-modal-delete:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .security-option {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: center;
            padding: 18px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 18px;
        }
        .security-option-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-weight: 800;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }
        .security-option-title i { color: var(--cyan); }
        .security-option-desc {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.5;
            margin: 0;
        }
        .security-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 61, 90, 0.2);
            background: rgba(255, 61, 90, 0.08);
            color: var(--red);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .security-status.enabled {
            border-color: rgba(0, 230, 118, 0.2);
            background: rgba(0, 230, 118, 0.08);
            color: var(--green);
        }
        .switch-control {
            position: relative;
            display: inline-flex;
            width: 58px;
            height: 32px;
            flex-shrink: 0;
        }
        .switch-control input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            transition: all 0.2s ease;
        }
        .switch-slider::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            left: 3px;
            top: 3px;
            border-radius: 50%;
            background: var(--muted);
            transition: all 0.2s ease;
        }
        .switch-control input:checked + .switch-slider {
            border-color: var(--cyan);
            background: rgba(0, 245, 212, 0.14);
        }
        .switch-control input:checked + .switch-slider::before {
            transform: translateX(26px);
            background: var(--cyan);
        }
        .two-factor-confirm {
            display: none;
            margin-top: 14px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        .two-factor-confirm.is-open {
            display: block;
        }
        .two-factor-confirm-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .two-factor-methods {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
            margin: 14px 0;
        }
        .two-factor-methods label {
            display: grid;
            gap: 8px;
            color: var(--text-dim);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .two-factor-methods select,
        .authenticator-setup input {
            min-height: 44px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--input-bg);
            color: var(--text);
            padding: 10px 12px;
        }
        .authenticator-setup {
            display: none;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 16px;
            padding: 16px;
            margin: 12px 0;
            border: 1px solid rgba(0,245,212,0.18);
            border-radius: 14px;
            background: rgba(0,245,212,0.04);
        }
        .authenticator-setup.is-open { display: grid; }
        .authenticator-qr {
            width: 160px;
            height: 160px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }
        .authenticator-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .authenticator-setup strong,
        .authenticator-setup small,
        .authenticator-setup code {
            display: block;
        }
        .authenticator-setup small {
            color: var(--muted);
            margin: 5px 0 10px;
        }
        .authenticator-setup code {
            margin-bottom: 10px;
            color: var(--cyan);
            overflow-wrap: anywhere;
        }
        .btn-secondary {
            background: var(--input-bg);
            color: var(--text);
            border: 1px solid var(--border);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Syne', sans-serif;
        }
        .btn-secondary:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }

        /* ── Quick info row ─────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px 18px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        .info-item .info-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }
        .info-item .info-value {
            font-size: 0.95rem;
            color: var(--text);
            font-weight: 600;
        }

        /* ── Sidebar badge ──────────────────────────────────── */
        .sidebar-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--orange);
            color: #000;
            font-size: 0.7rem;
            font-weight: 800;
            margin-left: auto;
        }

        /* ── Ticket cards ───────────────────────────────────── */
        .ticket-list { display: flex; flex-direction: column; gap: 12px; }
        .ticket-card {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 20px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .ticket-card:hover {
            border-color: rgba(0,245,212,0.25);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .ticket-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .ticket-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ticket-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--cyan);
        }
        .ticket-date {
            font-size: 0.78rem;
            color: var(--muted);
        }
        .ticket-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ticket-type {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 3px 10px;
            border-radius: 6px;
            background: rgba(0,245,212,0.08);
            color: var(--cyan);
            border: 1px solid rgba(0,245,212,0.15);
        }
        .ticket-status {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 3px 10px;
            border-radius: 6px;
        }
        .status-submitted { background: rgba(255,165,0,0.08); color: #ffa500; border: 1px solid rgba(255,165,0,0.2); }
        .status-reviewing { background: rgba(59,130,246,0.08); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
        .status-approved { background: rgba(0,230,118,0.08); color: #00e676; border: 1px solid rgba(0,230,118,0.2); }
        .status-progress { background: rgba(168,85,247,0.08); color: #a855f7; border: 1px solid rgba(168,85,247,0.2); }
        .status-rejected { background: rgba(255,61,90,0.08); color: #ff3d5a; border: 1px solid rgba(255,61,90,0.2); }
        .priority-urgent {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 6px;
            background: rgba(255,61,90,0.1);
            color: #ff3d5a;
            border: 1px solid rgba(255,61,90,0.25);
        }
        .ticket-chevron {
            color: var(--muted);
            font-size: 0.8rem;
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }
        .ticket-card.expanded .ticket-chevron { transform: rotate(180deg); }
        .ticket-product {
            margin-top: 10px;
            font-size: 0.88rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ticket-product i { color: var(--cyan); font-size: 0.8rem; }
        .ticket-order {
            font-size: 0.78rem;
            color: var(--muted);
            margin-left: auto;
            font-family: 'JetBrains Mono', monospace;
        }
        .ticket-details {
            display: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        .ticket-card.expanded .ticket-details { display: block; }
        .ticket-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
        .detail-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 0.88rem;
            color: var(--text);
            font-weight: 600;
        }
        .ticket-reason p {
            color: var(--text);
            font-size: 0.88rem;
            line-height: 1.6;
            margin: 4px 0 0;
        }
        .ticket-next-action {
            margin-top: 14px;
            padding: 12px 16px;
            background: rgba(0,245,212,0.04);
            border: 1px solid rgba(0,245,212,0.12);
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--cyan);
            line-height: 1.5;
        }
        .ticket-next-action i { margin-top: 3px; flex-shrink: 0; }

        /* ── Support empty state ────────────────────────────── */
        .support-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .support-empty i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        .support-empty p {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }

        @media (max-width: 768px) {
            .account-hero { flex-direction: column; text-align: center; padding: 24px; }
            .stats-grid { grid-template-columns: 1fr; }
            .account-stats { flex-direction: column; }
            .info-grid { grid-template-columns: 1fr; }
            .restore-banner { flex-direction: column; text-align: center; }
            .btn-restore { margin-left: 0; margin-top: 12px; }
            .section-card { padding: 24px; }
            .ticket-header { flex-direction: column; gap: 8px; }
            .ticket-detail-grid { grid-template-columns: 1fr; }
            .delete-modal-actions { flex-direction: column; }
            .security-option,
            .two-factor-methods,
            .authenticator-setup { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="nav-container">
            <a href="index.html" class="logo">
                <i class="fas fa-microchip"></i>
                <span>Maroc PC</span>
            </a>

            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Products</a>
            </nav>

            <div class="nav-spacer"></div>

            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div id="google_translate_element" class="nav-translate"></div>

            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
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
                        <i class="fas fa-gauge-high"></i> Overview
                    </a>
                    <a href="?tab=profile" class="<?= $activeTab === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="?tab=orders" class="<?= $activeTab === 'orders' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i> Orders
                    </a>
                    <a href="?tab=wishlist" class="<?= $activeTab === 'wishlist' ? 'active' : '' ?>">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                    <a href="?tab=builds" class="<?= $activeTab === 'builds' ? 'active' : '' ?>">
                        <i class="fas fa-computer"></i> Builds
                    </a>
                    <a href="?tab=loyalty" class="<?= $activeTab === 'loyalty' ? 'active' : '' ?>">
                        <i class="fas fa-crown"></i> Rewards
                    </a>
                    <a href="?tab=warranties" class="<?= $activeTab === 'warranties' ? 'active' : '' ?>">
                        <i class="fas fa-shield-heart"></i> Warranties & RMAs
                    </a>
                    <a href="?tab=security" class="<?= $activeTab === 'security' ? 'active' : '' ?>">
                        <i class="fas fa-shield-halved"></i> Security
                    </a>
                    <a href="?tab=support" class="<?= $activeTab === 'support' ? 'active' : '' ?>">
                        <i class="fas fa-headset"></i> Support
                        <?php if ($openTicketCount > 0): ?>
                            <span class="sidebar-badge"><?= $openTicketCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </aside>

                <div class="account-content" style="background:transparent;border:none;padding:0;box-shadow:none;">

                    <!-- ── Restore banner (if account is pending deletion) ── -->
                    <?php if ($isDeleted && $daysLeft > 0): ?>
                        <div class="restore-banner" id="restoreBanner">
                            <i class="fas fa-exclamation-triangle restore-icon"></i>
                            <div class="restore-text">
                                <h4>Account Deletion Scheduled</h4>
                                <p>Your account will be permanently deleted on <strong><?= h($deleteDeadline) ?></strong>
                                    (<?= $daysLeft ?> day<?= $daysLeft > 1 ? 's' : '' ?> left).
                                    Click "Restore" to cancel the deletion.</p>
                            </div>
                            <button class="btn-restore" id="restoreAccountBtn">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- ── Hero card ──────────────────────────────── -->
                    <div class="account-hero">
                        <div class="avatar profile-avatar">
                            <img id="profileAvatarImg" src="<?= h($profileImage) ?>" alt="<?= h($user['nom'] ?? 'User') ?> profile picture" onerror="this.src='<?= h($defaultProfileImage) ?>'">
                        </div>
                        <div class="hero-info">
                            <h2><?= h($user['nom']) ?></h2>
                            <p><?= h($user['email']) ?></p>
                            <span class="member-badge">
                                <i class="fas fa-gem"></i> Member for <?= $accountAgeStr ?>
                            </span>
                        </div>
                    </div>

                    <!-- ── Stats ──────────────────────────────────── -->
                    <div class="stats-grid account-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?= $orderCount ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= moneyMAD($totalSpent) ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: var(--orange);"><?= $loyaltyPoints ?></div>
                            <div class="stat-label">Loyalty Points</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="text-transform: capitalize;"><?= h($loyaltyTier) ?></div>
                            <div class="stat-label">Current Tier</div>
                        </div>
                    </div>

                    <?php if ($activeTab === 'overview'): ?>
                        <div class="overview-grid">
                            <div class="section-card overview-panel overview-main">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow">Account home</span>
                                        <h3><i class="fas fa-star"></i> Welcome back, <?= h(strtok((string) $user['nom'], ' ') ?: 'there') ?></h3>
                                    </div>
                                    <a class="btn-view" href="products.html"><i class="fas fa-bag-shopping"></i> Shop</a>
                                </div>
                                <div class="overview-metrics">
                                    <div>
                                        <strong><?= $wishlistCount ?></strong>
                                        <span>Wishlist items</span>
                                    </div>
                                    <div>
                                        <strong><?= $savedBuildCount ?></strong>
                                        <span>Saved builds</span>
                                    </div>
                                    <div>
                                        <strong><?= !empty($user['two_factor_enabled']) ? 'On' : 'Off' ?></strong>
                                        <span>Login 2FA</span>
                                    </div>
                                    <div>
                                        <strong style="color: <?= $openTicketCount > 0 ? 'var(--orange)' : 'var(--cyan)' ?>"><?= $openTicketCount ?></strong>
                                        <span>Open Tickets</span>
                                    </div>
                                </div>
                            </div>

                            <div class="section-card overview-panel">
                                <div class="overview-heading">
                                    <div>
                                        <span class="eyebrow">Profile health</span>
                                        <h3><i class="fas fa-user-check"></i> <?= $profileCompletion ?>% complete</h3>
                                    </div>
                                    <a class="btn-view" href="?tab=profile"><i class="fas fa-pen"></i> Edit</a>
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
                                        <span class="eyebrow">Recent activity</span>
                                        <h3><i class="fas fa-truck-fast"></i> Latest orders</h3>
                                    </div>
                                    <a class="btn-view" href="?tab=orders"><i class="fas fa-list"></i> All orders</a>
                                </div>
                                <?php if ($recentOrders === []): ?>
                                    <p class="overview-empty">No orders yet. Your tracking timeline will appear here after checkout.</p>
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
                                        <span class="eyebrow">Fast actions</span>
                                        <h3><i class="fas fa-bolt"></i> Shortcuts</h3>
                                    </div>
                                </div>
                                <div class="quick-action-grid">
                                    <a href="builder.php"><i class="fas fa-computer"></i><span>Build a PC</span></a>
                                    <a href="?tab=wishlist"><i class="fas fa-heart"></i><span>Wishlist</span></a>
                                    <a href="?tab=support"><i class="fas fa-headset"></i><span>Support</span></a>
                                    <a href="?tab=loyalty"><i class="fas fa-crown"></i><span>Rewards</span></a>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'profile'): ?>
                        <!-- ── Profile Form ───────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-user-pen"></i> Personal Information</h3>
                            <div id="profileAlert"></div>
                            <div class="profile-picture-card">
                                <img id="profilePicturePreview" src="<?= h($profileImage) ?>" alt="Profile picture preview" onerror="this.src='<?= h($defaultProfileImage) ?>'">
                                <div class="profile-picture-copy">
                                    <strong>Profile picture</strong>
                                    <span>Use a clear square JPG, PNG, or WebP image. Max 3 MB.</span>
                                </div>
                                <label class="btn-view profile-upload-label" for="profilePictureInput">
                                    <i class="fas fa-image"></i> Choose
                                </label>
                                <input type="file" id="profilePictureInput" accept="image/jpeg,image/png,image/webp" hidden>
                                <button type="button" class="btn-save profile-upload-btn" id="uploadProfilePictureBtn">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            </div>
                            <form class="account-form" id="profileForm" onsubmit="return false;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="accName">Full Name</label>
                                        <input type="text" id="accName" name="nom"
                                            value="<?= h($user['nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="accEmail">Email Address</label>
                                        <input type="email" id="accEmail" name="email"
                                            value="<?= h($user['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="accPhone">Phone Number</label>
                                        <input type="tel" id="accPhone" name="telephone"
                                            value="<?= h($user['telephone'] ?? '') ?>"
                                            placeholder="+212 6XX XXX XXX">
                                    </div>
                                    <div class="form-group">
                                        <label for="accDob">Date of Birth</label>
                                        <input type="date" id="accDob" name="date_naissance"
                                            value="<?= h($user['date_naissance'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="accAddress">Shipping Address</label>
                                    <textarea id="accAddress" name="adresse"
                                        rows="3" placeholder="123 Boulevard Mohammed V, Casablanca"><?= h($user['adresse'] ?? '') ?></textarea>
                                </div>
                                <button type="button" class="btn-save" id="saveProfileBtn">
                                    <i class="fas fa-check"></i> Save Changes
                                </button>
                            </form>
                        </div>

                        <!-- ── Quick Info ─────────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-info-circle"></i> Account Details</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Account ID</span>
                                    <span class="info-value">#<?= h($user['id_client']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Member Since</span>
                                    <span class="info-value"><?= $createdAt->format('F j, Y') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email Status</span>
                                    <span class="info-value" style="color:#00e676;">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Account Status</span>
                                    <span class="info-value" style="color:<?= $isDeleted ? '#ff3d5a' : '#00e676' ?>;">
                                        <i class="fas fa-<?= $isDeleted ? 'exclamation-triangle' : 'shield-halved' ?>"></i>
                                        <?= $isDeleted ? 'Pending Deletion' : 'Active' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'orders'): ?>
                        <!-- ── Orders ─────────────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-box-open"></i> Order History</h3>
                            <div id="ordersContainer">
                                <p class="no-orders">Loading orders...</p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'wishlist'): ?>
                        <!-- ── Wishlist ───────────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-heart"></i> Your Wishlist</h3>
                            <div id="wishlistContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                                <p class="no-orders">Loading wishlist...</p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'builds'): ?>
                        <div class="section-card">
                            <h3><i class="fas fa-computer"></i> Saved PC Builds</h3>
                            <div id="savedBuildsContainer">
                                <p class="no-orders">Loading builds...</p>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'loyalty'): ?>
                        <!-- ── Loyalty Points & Rewards ────────────── -->
                        <?php
                            $tierColors = ['bronze' => '#cd7f32', 'silver' => '#c0c0c0', 'gold' => '#ffd700', 'platinum' => '#e5e4e2'];
                            $tierColor = $tierColors[$loyaltyTier] ?? '#cd7f32';
                            $tierIcons = ['bronze' => 'fa-medal', 'silver' => 'fa-award', 'gold' => 'fa-crown', 'platinum' => 'fa-gem'];
                            $tierIcon = $tierIcons[$loyaltyTier] ?? 'fa-medal';
                        ?>
                        <div class="section-card" style="background: linear-gradient(135deg, rgba(<?= $loyaltyTier === 'gold' ? '255,215,0' : ($loyaltyTier === 'platinum' ? '229,228,226' : ($loyaltyTier === 'silver' ? '192,192,192' : '205,127,50')) ?>,0.06) 0%, var(--page-bg-2) 60%);">
                            <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;">
                                <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,<?= $tierColor ?>,<?= $tierColor ?>aa);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#000;">
                                    <i class="fas <?= $tierIcon ?>"></i>
                                </div>
                                <div>
                                    <div style="font-family:'Orbitron',monospace;font-size:1.3rem;font-weight:800;color:<?= $tierColor ?>;text-transform:uppercase;"><?= h($loyaltyTier) ?> Member</div>
                                    <div style="color:var(--muted);font-size:0.85rem;">Earn points on every purchase</div>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;">
                                <div class="stat-card">
                                    <div class="stat-value"><?= number_format($loyaltyPoints) ?></div>
                                    <div class="stat-label">Available Points</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?= number_format($loyaltyPoints / 10, 2) ?> MAD</div>
                                    <div class="stat-label">Redeemable Value</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" style="font-size:1rem;"><?= ucfirst($loyaltyTier) ?></div>
                                    <div class="stat-label">Current Tier</div>
                                </div>
                            </div>

                            <!-- Tier Progress -->
                            <div style="margin-bottom:24px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                                    <span style="font-size:0.78rem;color:var(--muted);font-weight:600;">TIER PROGRESS</span>
                                    <span style="font-size:0.78rem;color:var(--cyan);font-family:'JetBrains Mono',monospace;" id="loyaltyProgressLabel">Loading...</span>
                                </div>
                                <div style="width:100%;height:8px;background:rgba(255,255,255,0.06);border-radius:4px;overflow:hidden;">
                                    <div id="loyaltyProgressBar" style="height:100%;border-radius:4px;background:linear-gradient(90deg,<?= $tierColor ?>,var(--cyan));transition:width 0.5s ease;width:0%;"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-top:6px;">
                                    <span style="font-size:0.7rem;color:var(--muted);">Bronze</span>
                                    <span style="font-size:0.7rem;color:var(--muted);">Silver (2K)</span>
                                    <span style="font-size:0.7rem;color:var(--muted);">Gold (5K)</span>
                                    <span style="font-size:0.7rem;color:var(--muted);">Platinum (10K)</span>
                                </div>
                            </div>

                            <!-- Benefits -->
                            <h3><i class="fas fa-gift" style="color:<?= $tierColor ?>"></i> Your Benefits</h3>
                            <div id="loyaltyBenefits" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:24px;">
                                <div style="padding:10px 14px;background:var(--page-bg);border:1px solid var(--border);border-radius:10px;font-size:0.82rem;color:var(--text);"><i class="fas fa-check" style="color:#00e676;margin-right:6px;"></i> Points on purchases</div>
                                <div style="padding:10px 14px;background:var(--page-bg);border:1px solid var(--border);border-radius:10px;font-size:0.82rem;color:var(--text);"><i class="fas fa-check" style="color:#00e676;margin-right:6px;"></i> Birthday bonus</div>
                            </div>

                            <!-- How it works -->
                            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                                <div style="text-align:center;padding:16px;background:var(--page-bg);border:1px solid var(--border);border-radius:12px;">
                                    <div style="font-size:1.5rem;margin-bottom:8px;">🛒</div>
                                    <div style="font-size:0.82rem;font-weight:700;color:var(--text);margin-bottom:4px;">Shop</div>
                                    <div style="font-size:0.75rem;color:var(--muted);">Earn 1 pt per 10 MAD spent</div>
                                </div>
                                <div style="text-align:center;padding:16px;background:var(--page-bg);border:1px solid var(--border);border-radius:12px;">
                                    <div style="font-size:1.5rem;margin-bottom:8px;">⭐</div>
                                    <div style="font-size:0.82rem;font-weight:700;color:var(--text);margin-bottom:4px;">Earn</div>
                                    <div style="font-size:0.75rem;color:var(--muted);">Collect points & level up tiers</div>
                                </div>
                                <div style="text-align:center;padding:16px;background:var(--page-bg);border:1px solid var(--border);border-radius:12px;">
                                    <div style="font-size:1.5rem;margin-bottom:8px;">🎁</div>
                                    <div style="font-size:0.82rem;font-weight:700;color:var(--text);margin-bottom:4px;">Redeem</div>
                                    <div style="font-size:0.75rem;color:var(--muted);">100 pts = 10 MAD discount</div>
                                </div>
                            </div>
                        </div>

                        <!-- Points History -->
                        <div class="section-card">
                            <h3><i class="fas fa-history"></i> Points History</h3>
                            <div id="loyaltyHistory">
                                <p class="no-orders">Loading history...</p>
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

                        $stmt = $pdo->prepare("
                            SELECT id, order_id, product_name, issue_type, notes, status, created_at
                            FROM maintenance_requests
                            WHERE client_id = ?
                            ORDER BY created_at DESC
                        ");
                        $stmt->execute([$_SESSION['client_id']]);
                        $maintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="section-card">
                            <div class="overview-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                                <div>
                                    <span class="eyebrow" style="color: var(--cyan); text-transform: uppercase;">Active Coverage</span>
                                    <h3 style="margin: 4px 0 0;"><i class="fas fa-shield-heart"></i> Component Warranties</h3>
                                </div>
                                <button type="button" class="btn-view" onclick="openMaintenanceModal()" style="border: 1px solid var(--cyan); background: rgba(0,245,212,0.05); color: var(--cyan); font-weight: 700; border-radius: 8px; padding: 8px 16px; cursor: pointer; transition: all 0.2s;">
                                    <i class="fas fa-notes-medical"></i> File Maintenance / RMA
                                </button>
                            </div>

                            <?php if (empty($purchasedItems)): ?>
                                <div style="text-align: center; padding: 48px 20px;">
                                    <i class="fas fa-shield-halved" style="font-size: 3rem; color: var(--muted); margin-bottom: 16px; display: block; opacity: 0.3;"></i>
                                    <p class="no-orders" style="font-size: 1.1rem;">No hardware warranties active</p>
                                    <p style="color: var(--muted); font-size: 0.88rem; margin-top: 6px;">Once you buy custom configurations or components and your order is delivered, your active warranties will appear here.</p>
                                    <a href="products.html" style="display:inline-block; margin-top:20px; padding:12px 28px; background:var(--cyan); color:#000; border-radius:10px; font-weight:700; text-decoration:none; transition:all 0.2s;">
                                        <i class="fas fa-shopping-bag"></i> Browse Components
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
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
                                        <div style="background: var(--page-bg-2); border: 1px solid var(--border); border-radius: 16px; padding: 20px; position: relative; overflow: hidden; display: flex; flex-direction: column; backdrop-filter: blur(8px);">
                                            <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;">
                                                <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                                    <img src="<?= h($item['image'] ?? 'images/products/placeholder.svg') ?>" style="max-width: 90%; max-height: 90%; object-fit: contain;" onerror="this.src='Images/products/placeholder-storage.svg'">
                                                </div>
                                                <div style="flex: 1;">
                                                    <span style="font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;"><?= h($item['category'] ?? 'Component') ?></span>
                                                    <h4 style="margin: 4px 0 0; font-size: 0.92rem; font-weight: 700; line-height: 1.4; color: var(--text);"><?= h($item['name_at_time']) ?></h4>
                                                </div>
                                            </div>

                                            <div style="margin-top: auto;">
                                                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 6px;">
                                                    <span style="color: var(--muted);">Coverage Left: <?= $pctLeft ?>%</span>
                                                    <span style="font-weight: 700; color: <?= $isActive ? 'var(--cyan)' : '#ff3d5a' ?>;">
                                                        <?= $isActive ? 'Active' : 'Expired' ?>
                                                    </span>
                                                </div>
                                                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 99px; overflow: hidden; border: 1px solid var(--border); margin-bottom: 12px;">
                                                    <div style="height: 100%; width: <?= $pctLeft ?>%; background: linear-gradient(90deg, #ff3d5a, var(--cyan)); border-radius: 99px;"></div>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--muted);">
                                                    <span>Bought: <?= date('M d, Y', $orderTime) ?></span>
                                                    <span>Expires: <?= date('M d, Y', $expireTime) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Maintenance & RMA Tickets -->
                        <div class="section-card" style="margin-top: 32px;">
                            <h3><i class="fas fa-history"></i> Maintenance & RMA History</h3>
                            <?php if (empty($maintenanceRequests)): ?>
                                <p class="no-orders" style="padding: 24px 0; text-align: center;">No support or checkup requests filed yet.</p>
                            <?php else: ?>
                                <div class="orders-list">
                                    <?php foreach ($maintenanceRequests as $req): ?>
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'status-warn',
                                            'approved' => 'status-good',
                                            'in_progress' => 'status-warn',
                                            'resolved' => 'status-good',
                                            'rejected' => 'status-danger'
                                        ];
                                        $reqClass = $statusClasses[$req['status']] ?? 'status-neutral';
                                        ?>
                                        <div class="order-card" style="display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 20px;">
                                            <div>
                                                <div class="order-id" style="font-size: 0.95rem; font-weight: 700;"><?= h($req['product_name']) ?></div>
                                                <div class="order-date">
                                                    Order #<?= $req['order_id'] ?> · <?= ucfirst(h($req['issue_type'])) ?> · Filed on <?= date('M d, Y', strtotime($req['created_at'])) ?>
                                                </div>
                                                <?php if (!empty($req['notes'])): ?>
                                                    <div style="margin-top: 8px; font-size: 0.8rem; color: var(--muted); background: rgba(255,255,255,0.01); border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px;">
                                                        <strong>Notes:</strong> <?= h($req['notes']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700;" class="order-status <?= $reqClass ?>"><?= strtoupper(h($req['status'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Maintenance Request Modal -->
                        <div class="delete-modal-backdrop" id="maintenanceModalBackdrop" style="display: none; align-items: center; justify-content: center; z-index: 1100; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);">
                            <div class="delete-modal" style="background: var(--page-bg-2); border: 1px solid var(--border); max-width: 500px; width: 90%; border-radius: 24px; padding: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                                <h3 style="font-family: 'Orbitron', sans-serif; text-transform: uppercase; letter-spacing: 0.05em; font-size: 1.25rem; margin-bottom: 8px; color: var(--cyan); display: flex; align-items: center; gap: 8px; margin-top: 0;"><i class="fas fa-notes-medical"></i> File Maintenance</h3>
                                <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 24px;">Submit a diagnostic, checkup, or return request for your components. Our expert builders will assist you.</p>
                                
                                <form id="maintenanceForm" onsubmit="submitMaintenance(event)">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label for="maintProduct" style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; color: var(--text);">Select Component / Hardware</label>
                                        <select id="maintProduct" required style="width: 100%; padding: 12px; background: var(--page-bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 0.88rem;">
                                            <option value="">-- Choose Hardware --</option>
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
                                                if ($expireTime > time()): // only active warranties are eligible
                                                ?>
                                                    <option value="<?= h($item['name_at_time']) ?>" data-order-id="<?= $item['order_id'] ?>">
                                                        <?= h($item['name_at_time']) ?> (Order #<?= $item['order_id'] ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label for="maintIssue" style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; color: var(--text);">Issue Type</label>
                                        <select id="maintIssue" required style="width: 100%; padding: 12px; background: var(--page-bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 0.88rem;">
                                            <option value="diagnostic">Diagnostic / Performance Checkup</option>
                                            <option value="repair">Component Repair / RMA</option>
                                            <option value="cleaning">Professional Deep Cleaning</option>
                                            <option value="upgrade">Upgrade Advisory Service</option>
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="maintNotes" style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; color: var(--text);">Describe the problem / request details</label>
                                        <textarea id="maintNotes" rows="4" placeholder="Describe fan noise, screen glitches, benchmark targets, etc..." required style="width: 100%; padding: 12px; background: var(--page-bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 0.88rem; resize: vertical;"></textarea>
                                    </div>

                                    <div class="delete-modal-actions" style="display: flex; gap: 12px; justify-content: flex-end;">
                                        <button type="button" class="btn-modal-cancel" onclick="closeMaintenanceModal()" style="border: 1px solid var(--border); background: transparent; padding: 12px 20px; border-radius: 10px; color: var(--text); font-weight: 700; cursor: pointer;">Cancel</button>
                                        <button type="submit" class="btn-save" style="background: var(--cyan); color: #000; padding: 12px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; border: none;">Submit Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            function openMaintenanceModal() {
                                const m = document.getElementById('maintenanceModalBackdrop');
                                if (m) {
                                    m.style.display = 'flex';
                                    m.classList.add('is-open');
                                }
                            }

                            function closeMaintenanceModal() {
                                const m = document.getElementById('maintenanceModalBackdrop');
                                if (m) {
                                    m.style.display = 'none';
                                    m.classList.remove('is-open');
                                }
                            }

                            async function submitMaintenance(e) {
                                e.preventDefault();
                                const select = document.getElementById('maintProduct');
                                const product = select.value;
                                const option = select.options[select.selectedIndex];
                                const orderId = option.getAttribute('data-order-id');
                                const issue = document.getElementById('maintIssue').value;
                                const notes = document.getElementById('maintNotes').value;

                                if (!product || !orderId) {
                                    alert("Please select a valid hardware component.");
                                    return;
                                }

                                try {
                                    const res = await fetch('api/maintenance.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            order_id: parseInt(orderId, 10),
                                            product_name: product,
                                            issue_type: issue,
                                            notes: notes
                                        })
                                    });
                                    const r = await res.json();
                                    if (r.success) {
                                        closeMaintenanceModal();
                                        showToast(r.message, 'success');
                                        setTimeout(() => window.location.reload(), 2000);
                                    } else {
                                        alert(r.error || 'Failed to file request.');
                                    }
                                } catch (err) {
                                    alert('Failed to connect to server.');
                                }
                            }
                        </script>

                    <?php elseif ($activeTab === 'security'): ?>
                        <!-- Two-Factor Authentication -->
                        <div class="section-card">
                            <h3><i class="fas fa-user-shield"></i> Two-Factor Authentication</h3>
                            <div id="twoFactorAlert"></div>
                            <div class="security-option">
                                <div>
                                    <div class="security-option-title">
                                        <i class="fas fa-envelope-circle-check"></i>
                                        Login verification
                                        <span class="security-status <?= !empty($user['two_factor_enabled']) ? 'enabled' : '' ?>" id="twoFactorStatus">
                                            <?= !empty($user['two_factor_enabled']) ? 'Enabled' : 'Disabled' ?>
                                        </span>
                                    </div>
                                    <p class="security-option-desc">
                                        Require a second step after password login. Choose email, WhatsApp, or an authenticator app.
                                    </p>
                                    <div class="two-factor-methods">
                                        <label>
                                            Login method
                                            <select id="twoFactorMethod">
                                                <option value="email" <?= ($user['two_factor_method'] ?? 'email') === 'email' ? 'selected' : '' ?>>Email code</option>
                                                <option value="whatsapp" <?= ($user['two_factor_method'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp code<?= empty($user['telephone']) ? ' (add phone first)' : '' ?></option>
                                                <option value="authenticator" <?= ($user['two_factor_method'] ?? '') === 'authenticator' ? 'selected' : '' ?>>Authenticator app<?= empty($user['two_factor_totp_secret']) ? ' (setup required)' : '' ?></option>
                                            </select>
                                        </label>
                                        <button type="button" class="btn-secondary" id="setupAuthenticatorBtn">
                                            <i class="fas fa-qrcode"></i> Setup Authenticator
                                        </button>
                                    </div>
                                    <div class="authenticator-setup" id="authenticatorSetup">
                                        <div class="authenticator-qr">
                                            <img id="authenticatorQr" src="" alt="Authenticator QR code">
                                        </div>
                                        <div>
                                            <strong>Scan QR code</strong>
                                            <small>Use Google Authenticator, Microsoft Authenticator, 1Password, or any TOTP app.</small>
                                            <code id="authenticatorSecret"></code>
                                            <input type="text" id="authenticatorCode" inputmode="numeric" maxlength="6" placeholder="6-digit app code">
                                            <button type="button" class="btn-save" id="confirmAuthenticatorBtn">Confirm Authenticator</button>
                                        </div>
                                    </div>
                                    <div class="two-factor-confirm" id="twoFactorConfirm">
                                        <form class="account-form" onsubmit="return false;">
                                            <div class="form-group" style="margin-bottom:14px;">
                                                <label for="twoFactorPassword">Confirm Password</label>
                                                <input type="password" id="twoFactorPassword" placeholder="Enter your current password">
                                            </div>
                                            <div class="two-factor-confirm-actions">
                                                <button type="button" class="btn-save" id="twoFactorConfirmBtn">
                                                    <i class="fas fa-shield-halved"></i> Confirm
                                                </button>
                                                <button type="button" class="btn-secondary" id="twoFactorCancelBtn">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <label class="switch-control" aria-label="Toggle email two-factor authentication">
                                    <input type="checkbox" id="twoFactorToggle" <?= !empty($user['two_factor_enabled']) ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <!-- Backup Codes Block -->
                            <div class="backup-codes-section" id="backupCodesSection" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border); display: <?= !empty($user['two_factor_enabled']) ? 'block' : 'none' ?>;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <div>
                                        <strong style="display: block; font-size: 0.95rem; color: var(--text);"><i class="fas fa-file-shield" style="color: var(--cyan); margin-right: 6px;"></i> One-Time Backup Codes</strong>
                                        <small style="color: var(--muted);">Use these 8-character codes to log in if you lose access to your primary device.</small>
                                    </div>
                                    <button type="button" class="btn-secondary" id="regenerateBackupCodesBtn" style="font-size: 0.82rem; padding: 8px 14px; border-radius: 8px;">
                                        <i class="fas fa-arrows-rotate"></i> Regenerate Codes
                                    </button>
                                </div>
                                
                                <div class="backup-codes-display" id="backupCodesDisplay" style="display: none; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-top: 16px;">
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;" id="backupCodesGrid">
                                        <!-- Populated dynamically via JS -->
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="button" class="btn-secondary" id="copyBackupCodesBtn" style="font-size: 0.8rem; padding: 6px 12px; border-radius: 6px;">
                                            <i class="far fa-copy"></i> Copy Codes
                                        </button>
                                        <button type="button" class="btn-secondary" id="downloadBackupCodesBtn" style="font-size: 0.8rem; padding: 6px 12px; border-radius: 6px;">
                                            <i class="fas fa-download"></i> Download as Text
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ── Password Change ────────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <div id="passAlert"></div>
                            <form class="account-form" onsubmit="return false;">
                                <div class="form-group">
                                    <label for="accCurrentPass">Current Password</label>
                                    <input type="password" id="accCurrentPass" name="current_password"
                                        placeholder="Enter current password">
                                </div>
                                <div class="form-group">
                                    <label for="accNewPass">New Password</label>
                                    <input type="password" id="accNewPass" name="new_password"
                                        placeholder="Min. 8 chars, one number, one symbol">
                                </div>
                                <button type="button" class="btn-save" id="changePassBtn">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <!-- ── Danger Zone ────────────────────────── -->
                        <div class="section-card danger-zone">
                            <h3><i class="fas fa-skull-crossbones"></i> Danger Zone</h3>
                            <p>Once you delete your account, you have <strong>5 days</strong> to restore it. After that, all your data
                                (profile, orders, addresses) will be permanently erased. This action cannot be undone after the grace period.</p>
                            <button type="button" class="btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash-alt"></i> Delete My Account
                            </button>
                        </div>
                    <?php elseif ($activeTab === 'support'): ?>
                        <!-- ── Support Tickets ──────────────────── -->
                        <div class="section-card">
                            <h3><i class="fas fa-headset"></i> My Service Tickets</h3>
                            <?php if (empty($supportTickets)): ?>
                                <div class="support-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No service tickets yet.</p>
                                    <span>Need a return, refund, or warranty claim? File a request below.</span>
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
                                            $priorityIcon = $ticket['priority'] === 'urgent' ? '<span class="priority-urgent"><i class="fas fa-bolt"></i> Urgent</span>' : '';
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
                                                    <span class="ticket-order">Order #<?= (int) $ticket['order_id'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ticket-details">
                                                <div class="ticket-detail-grid">
                                                    <div>
                                                        <span class="detail-label">Condition</span>
                                                        <span class="detail-value"><?= h(ucwords(str_replace('_', ' ', $ticket['product_condition']))) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="detail-label">Preferred Resolution</span>
                                                        <span class="detail-value"><?= h(ucwords(str_replace('_', ' ', $ticket['preferred_resolution']))) ?></span>
                                                    </div>
                                                    <?php if ($ticket['serial_number']): ?>
                                                    <div>
                                                        <span class="detail-label">Serial Number</span>
                                                        <span class="detail-value" style="font-family:'JetBrains Mono',monospace;"><?= h($ticket['serial_number']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <span class="detail-label">Last Updated</span>
                                                        <span class="detail-value"><?= date('M j, Y H:i', strtotime($ticket['updated_at'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="ticket-reason">
                                                    <span class="detail-label">Description</span>
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
                            <h3><i class="fas fa-paper-plane"></i> File a New Request</h3>
                            <p style="color:var(--muted);font-size:0.9rem;margin:-16px 0 24px;">Your name and email are pre-filled from your account.</p>
                            <div id="supportFormAlert"></div>
                            <form class="account-form" id="supportTicketForm" onsubmit="return false;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-order">Order Number</label>
                                        <input type="number" id="sup-order" name="order_id" min="1" placeholder="e.g. 1004" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-product">Product Concerned</label>
                                        <input type="text" id="sup-product" name="product_name" placeholder="e.g. NVIDIA RTX 4080 Super" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-type">Request Type</label>
                                        <select id="sup-type" name="request_type" required>
                                            <option value="">Choose...</option>
                                            <option value="return">Return</option>
                                            <option value="refund">Refund</option>
                                            <option value="exchange">Exchange</option>
                                            <option value="warranty">Warranty Claim</option>
                                            <option value="repair">Repair / Diagnostic</option>
                                            <option value="missing">Missing Item</option>
                                            <option value="damaged">Damaged on Arrival</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-resolution">Preferred Resolution</label>
                                        <select id="sup-resolution" name="preferred_resolution" required>
                                            <option value="">Choose...</option>
                                            <option value="refund">Refund</option>
                                            <option value="replacement">Replacement</option>
                                            <option value="store_credit">Store Credit</option>
                                            <option value="repair">Repair</option>
                                            <option value="diagnostic">Diagnostic Report</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sup-condition">Product Condition</label>
                                        <select id="sup-condition" name="product_condition" required>
                                            <option value="">Choose...</option>
                                            <option value="sealed">Sealed / Unopened</option>
                                            <option value="opened_unused">Opened but Unused</option>
                                            <option value="used">Used / Installed</option>
                                            <option value="defective">Defective</option>
                                            <option value="damaged_package">Damaged Packaging</option>
                                            <option value="missing_item">Missing Item/Accessory</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="sup-serial">Serial Number <small style="color:var(--muted)">(optional)</small></label>
                                        <input type="text" id="sup-serial" name="serial_number" placeholder="Recommended for warranty">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="sup-reason">Describe the Issue</label>
                                    <textarea id="sup-reason" name="reason" rows="5" minlength="20" placeholder="Tell us what happened, when you noticed it, and what resolution you expect." required></textarea>
                                </div>
                                <label style="display:flex;align-items:center;gap:8px;margin-bottom:20px;cursor:pointer;color:var(--muted);font-size:0.9rem;">
                                    <input type="checkbox" name="package_opened" value="1"> The retail package has been opened.
                                </label>
                                <button type="button" class="btn-save" id="submitSupportTicket">
                                    <i class="fas fa-paper-plane"></i> Submit Service Ticket
                                </button>
                            </form>
                        </div>

                        <!-- ── Need Help CTA ───────────────────── -->
                        <div class="section-card" style="text-align:center;">
                            <p style="color:var(--muted);margin:0 0 12px;">Need immediate help or want the full policy details?</p>
                            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                                <a href="returns-refunds.php" class="btn-view" style="text-decoration:none;"><i class="fas fa-book"></i> Full Policy & FAQ</a>
                                <a href="mailto:support@marocpc.com" class="btn-view" style="text-decoration:none;"><i class="fas fa-envelope"></i> Email Support</a>
                                <a href="tel:+212618821949" class="btn-view" style="text-decoration:none;"><i class="fas fa-phone"></i> Call Us</a>
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
                <h3 id="trackingOrderId">Order #0</h3>
                <button class="btn-tracking-close" id="trackingModalClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="tracking-body">
                <div class="estimated-delivery">
                    Estimated Delivery:
                    <span id="trackingEstimatedDelivery">Calculating...</span>
                </div>

                <div class="tracking-map">
                    <div class="map-route-line"></div>
                    <div class="map-route-progress" id="mapRouteProgress" style="width: 0%;"></div>
                    <div class="city-point" id="city-casa">
                        <div class="city-dot"></div>
                        <div class="city-name">Casablanca</div>
                    </div>
                    <div class="city-point" id="city-rabat">
                        <div class="city-dot"></div>
                        <div class="city-name">Rabat</div>
                    </div>
                    <div class="city-point" id="city-tanger">
                        <div class="city-dot"></div>
                        <div class="city-name">Tanger</div>
                    </div>
                    <div class="city-point" id="city-dest">
                        <div class="city-dot"></div>
                        <div class="city-name">Destination</div>
                    </div>
                </div>
                
                <div class="tracking-assembly-container" id="trackingAssemblyContainer" style="display: none; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
                    <h4 style="margin: 0 0 16px; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cyan); display: flex; align-items: center; gap: 8px;"><i class="fas fa-screwdriver-wrench"></i> PC Assembly Progress</h4>
                    <div class="tracking-progress-container" style="margin-top: 10px;">
                        <div class="tracking-progress-bar">
                            <div class="tracking-progress-line"></div>
                            <div class="tracking-progress-fill" id="trackingAssemblyFill" style="width: 0%; background: var(--cyan);"></div>
                            <div class="tracking-step" id="step-assembly-gathering_parts" title="Gathering Parts"><i class="fas fa-dolly"></i></div>
                            <div class="tracking-step" id="step-assembly-building" title="Building"><i class="fas fa-hammer"></i></div>
                            <div class="tracking-step" id="step-assembly-testing" title="Testing"><i class="fas fa-microchip"></i></div>
                            <div class="tracking-step" id="step-assembly-qc_passed" title="QC Passed"><i class="fas fa-clipboard-check"></i></div>
                            <div class="tracking-step" id="step-assembly-ready" title="Ready"><i class="fas fa-check-double"></i></div>
                        </div>
                    </div>
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="" id="assemblyGuideLink" class="btn-view" style="display: inline-block; text-decoration: none; font-size: 0.82rem; font-weight: 700; border-radius: 8px; border: 1px solid var(--cyan); padding: 8px 16px; background: rgba(0,245,212,0.06); color: var(--cyan); transition: all 0.2s;">
                            <i class="fas fa-book-open"></i> View Step-by-Step Interactive Assembly Guide
                        </a>
                    </div>
                </div>

                <div class="tracking-progress-container">
                    <h4 style="margin: 0 0 16px; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text); display: flex; align-items: center; gap: 8px;"><i class="fas fa-truck"></i> Shipping Progress</h4>
                    <div class="tracking-progress-bar">
                        <div class="tracking-progress-line"></div>
                        <div class="tracking-progress-fill" id="trackingProgressFill" style="width: 0%;"></div>
                        <div class="tracking-step" id="step-pending" title="Pending"><i class="fas fa-clock"></i></div>
                        <div class="tracking-step" id="step-processing" title="Processing"><i class="fas fa-cog"></i></div>
                        <div class="tracking-step" id="step-shipped" title="Shipped"><i class="fas fa-box"></i></div>
                        <div class="tracking-step" id="step-out_for_delivery" title="Out for Delivery"><i class="fas fa-truck"></i></div>
                        <div class="tracking-step" id="step-delivered" title="Delivered"><i class="fas fa-check"></i></div>
                    </div>
                </div>

                <div class="tracking-timeline" id="trackingTimeline">
                    <!-- Timeline events injected here -->
                </div>

                <div class="tracking-order-summary">
                    <h4>Items</h4>
                    <div id="trackingItemsList"></div>
                    <div style="border-top: 1px solid var(--border); margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between;">
                        <span class="tracking-item-name">Total</span>
                        <span class="tracking-item-price" id="trackingTotalCost" style="color: var(--cyan);">0 MAD</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Delete confirmation modal ─────────────────────── -->
    <div class="delete-modal-backdrop" id="deleteModalBackdrop">
        <div class="delete-modal">
            <div class="delete-icon"><i class="fas fa-user-slash"></i></div>
            <h3>Delete Your Account?</h3>
            <p>This will schedule your account for permanent deletion in <strong>5 days</strong>.
                You can restore it anytime during that period by logging in.</p>
            <input type="password" id="deleteConfirmPassword" placeholder="Enter your password to confirm">
            <div class="delete-modal-actions">
                <button class="btn-modal-cancel" id="deleteModalCancel">Cancel</button>
                <button class="btn-modal-delete" id="deleteModalConfirm">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>

    <footer class="footer"
        style="background: var(--page-bg-2); border-top: 1px solid var(--border); padding: 32px 0; text-align: center;">
        <div class="container">
            <p style="color: var(--muted);">&copy; 2026 Maroc PC. All rights reserved.</p>
            <div style="margin-top: 10px; display: flex; justify-content: center; gap: 15px;">
                <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="https://x.com/Maroc_PC_PHP" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-x-twitter"></i> X (Twitter)</a>
                <a href="https://www.instagram.com/marocpc57" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-youtube"></i> YouTube</a>
            </div>
        </div>
    </footer>

    <div class="toast" id="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script src="assets/js/cart.js"></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/account.js"></script>
    <script>
    // ── Support ticket form submission ──────────────────
    (function() {
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
                showAlert('Please fill in all required fields (description must be at least 20 characters).', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            try {
                const res = await fetch('api/after-sales-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    showAlert(
                        `<strong>Ticket ${result.ticket} created!</strong> Priority: ${result.priority}. ETA: ${result.eta}.<br><small>${result.next_action}</small>`,
                        'success'
                    );
                    form.reset();
                    // Reload after a short delay to show new ticket
                    setTimeout(() => window.location.reload(), 2500);
                } else {
                    showAlert(result.error || 'Something went wrong.', 'error');
                }
            } catch (err) {
                showAlert('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Service Ticket';
            }
        });

        function showAlert(msg, type) {
            if (!alertBox) return;
            const color = type === 'success' ? '#00e676' : '#ff3d5a';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            alertBox.innerHTML = `<div style="display:flex;align-items:flex-start;gap:10px;padding:14px 18px;border-radius:12px;font-size:0.9rem;font-weight:600;margin-bottom:20px;background:rgba(${type==='success'?'0,230,118':'255,61,90'},0.06);border:1px solid ${color};color:${color};line-height:1.5;"><i class='fas fa-${icon}' style='margin-top:3px;'></i><span>${msg}</span></div>`;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    })();
    </script>

    <script>
        // Plant footprint for session expiration detection
        localStorage.setItem('has_active_session', '1');
    </script>
</body>

</html>
