<?php
/**
 * Admin Visitor Management API
 *
 * Actions: block_ip, unblock_ip, block_country, unblock_country, list_blocks
 * Protected by admin auth.
 */

require_once dirname(__DIR__) . '/admin-helpers.php';

adminRequireJsonAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$action = trim((string) ($input['action'] ?? $_GET['action'] ?? ''));

try {
    switch ($action) {

        // ── Block an IP ────────────────────────────────────
        case 'block_ip':
            $ip = trim((string) ($input['ip_address'] ?? ''));
            $reason = trim((string) ($input['reason'] ?? ''));
            $expiresAt = !empty($input['expires_at']) ? trim((string) $input['expires_at']) : null;

            if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
                echo json_encode(['success' => false, 'error' => 'Invalid IP address.']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO ip_blocks (ip_address, reason, blocked_by, expires_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_by = VALUES(blocked_by),
                    expires_at = VALUES(expires_at), created_at = NOW()
            ');
            $adminEmail = trim((string) ($_SESSION['admin_email'] ?? 'admin'));
            $stmt->execute([$ip, $reason ?: null, $adminEmail, $expiresAt ?: null]);

            adminLogActivity($pdo, 'block_ip', 'ip_blocks', null, "Blocked IP: {$ip}" . ($reason ? " — {$reason}" : ''));

            echo json_encode(['success' => true, 'message' => "IP {$ip} has been blocked."]);
            break;

        // ── Unblock an IP ──────────────────────────────────
        case 'unblock_ip':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid block ID.']);
                exit;
            }

            $row = $pdo->prepare('SELECT ip_address FROM ip_blocks WHERE id = ?');
            $row->execute([$id]);
            $block = $row->fetch(PDO::FETCH_ASSOC);
            if (!$block) {
                echo json_encode(['success' => false, 'error' => 'Block not found.']);
                exit;
            }

            $pdo->prepare('DELETE FROM ip_blocks WHERE id = ?')->execute([$id]);
            adminLogActivity($pdo, 'unblock_ip', 'ip_blocks', $id, "Unblocked IP: {$block['ip_address']}");

            echo json_encode(['success' => true, 'message' => "IP {$block['ip_address']} has been unblocked."]);
            break;

        // ── Block a Country ────────────────────────────────
        case 'block_country':
            $code = strtoupper(trim((string) ($input['country_code'] ?? '')));
            $name = trim((string) ($input['country_name'] ?? $code));
            $reason = trim((string) ($input['reason'] ?? ''));

            if ($code === '' || strlen($code) !== 2) {
                echo json_encode(['success' => false, 'error' => 'Country code must be 2 letters (e.g. US, CN).']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO country_blocks (country_code, country_name, reason, blocked_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE country_name = VALUES(country_name), reason = VALUES(reason),
                    blocked_by = VALUES(blocked_by), created_at = NOW()
            ');
            $adminEmail = trim((string) ($_SESSION['admin_email'] ?? 'admin'));
            $stmt->execute([$code, $name, $reason ?: null, $adminEmail]);

            adminLogActivity($pdo, 'block_country', 'country_blocks', null, "Blocked country: {$name} ({$code})" . ($reason ? " — {$reason}" : ''));

            echo json_encode(['success' => true, 'message' => "Country {$name} ({$code}) has been blocked."]);
            break;

        // ── Unblock a Country ──────────────────────────────
        case 'unblock_country':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid block ID.']);
                exit;
            }

            $row = $pdo->prepare('SELECT country_name, country_code FROM country_blocks WHERE id = ?');
            $row->execute([$id]);
            $cBlock = $row->fetch(PDO::FETCH_ASSOC);
            if (!$cBlock) {
                echo json_encode(['success' => false, 'error' => 'Country block not found.']);
                exit;
            }

            $pdo->prepare('DELETE FROM country_blocks WHERE id = ?')->execute([$id]);
            adminLogActivity($pdo, 'unblock_country', 'country_blocks', $id, "Unblocked country: {$cBlock['country_name']} ({$cBlock['country_code']})");

            echo json_encode(['success' => true, 'message' => "{$cBlock['country_name']} has been unblocked."]);
            break;

        // ── List all active blocks ─────────────────────────
        case 'list_blocks':
            $ipBlocks = adminFetchAll($pdo, 'SELECT * FROM ip_blocks ORDER BY created_at DESC');
            $countryBlocks = adminFetchAll($pdo, 'SELECT * FROM country_blocks ORDER BY created_at DESC');

            echo json_encode([
                'success' => true,
                'ip_blocks' => $ipBlocks,
                'country_blocks' => $countryBlocks,
            ]);
            break;

        case 'save_visitor_policy':
            $archiveAfter = max(1, min(365, (int) ($input['archive_after_days'] ?? 30)));
            $pruneAfter = max($archiveAfter + 1, min(3650, (int) ($input['archive_prune_days'] ?? 365)));
            $interval = max(5, min(1440, (int) ($input['archive_interval_minutes'] ?? 60)));
            $liveMaxRows = max(1000, min(1000000, (int) ($input['live_max_rows'] ?? 50000)));

            $settings = [
                'visitor_archive_enabled' => !empty($input['archive_enabled']) ? '1' : '0',
                'visitor_archive_after_days' => (string) $archiveAfter,
                'visitor_archive_prune_days' => (string) $pruneAfter,
                'visitor_archive_interval_minutes' => (string) $interval,
                'visitor_live_max_rows' => (string) $liveMaxRows,
                'visitor_block_vpn' => !empty($input['block_vpn']) ? '1' : '0',
                'visitor_block_proxy' => !empty($input['block_proxy']) ? '1' : '0',
                'visitor_block_tor' => !empty($input['block_tor']) ? '1' : '0',
                'visitor_block_hosting' => !empty($input['block_hosting']) ? '1' : '0',
            ];
            foreach ($settings as $key => $value) {
                adminSetSetting($pdo, $key, $value);
            }

            adminLogActivity($pdo, 'update', 'visitor_policy', null, 'Updated visitor archive and network blocking policy');
            echo json_encode(['success' => true, 'message' => 'Visitor policy saved.']);
            break;

        case 'run_visitor_archive':
            $result = adminVisitorArchiveOldLogs($pdo, true);
            adminLogActivity($pdo, 'archive', 'visitor_logs', null, $result['message'] ?? 'Visitor archive run');
            echo json_encode(['success' => true, 'message' => $result['message'] ?? 'Visitor archive complete.', 'result' => $result]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
