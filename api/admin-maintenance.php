<?php
require_once dirname(__DIR__) . '/admin-helpers.php';
require_once SRC_PATH . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'price-alerts.php';

adminRequireJsonAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$action = (string) ($input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '');

function adminMaintenanceEnsurePriceHistoryTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS price_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            recorded_at DATE NOT NULL,
            INDEX idx_product_date (product_id, recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function adminMaintenanceEnsureLoyaltyColumns(PDO $pdo): void
{
    if (!adminColumnExists($pdo, 'Client', 'loyalty_tier')) {
        $pdo->exec("ALTER TABLE Client ADD loyalty_tier ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze'");
    }
    if (!adminColumnExists($pdo, 'Client', 'total_points')) {
        $pdo->exec('ALTER TABLE Client ADD total_points INT DEFAULT 0');
    }
}

function adminMaintenanceTierFromLifetime(int $lifetimeEarned): string
{
    if ($lifetimeEarned >= 10000) {
        return 'platinum';
    }
    if ($lifetimeEarned >= 5000) {
        return 'gold';
    }
    if ($lifetimeEarned >= 2000) {
        return 'silver';
    }
    return 'bronze';
}

if ($action === 'snapshot_price_history') {
    adminMaintenanceEnsurePriceHistoryTable($pdo);

    $today = date('Y-m-d');
    $alreadyDone = (int) $pdo->query("SELECT COUNT(*) FROM price_history WHERE recorded_at = " . $pdo->quote($today))->fetchColumn();
    if ($alreadyDone > 0) {
        $alerts = processDuePriceAlerts($pdo);
        adminLogActivity($pdo, 'snapshot', 'price_history', null, "Price history already captured for {$today}");
        jsonResponse(true, "Already snapshotted for {$today}.", [
            'count' => $alreadyDone,
            'alerts' => $alerts,
        ]);
    }

    $products = $pdo->query('SELECT id, price FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)');

    $count = 0;
    foreach ($products as $product) {
        $stmt->execute([(int) $product['id'], (float) $product['price'], $today]);
        $count++;
    }

    $alerts = processDuePriceAlerts($pdo);
    adminLogActivity($pdo, 'snapshot', 'price_history', null, "Captured {$count} product prices for {$today}");

    jsonResponse(true, "Snapshotted {$count} product prices for {$today}.", [
        'count' => $count,
        'alerts' => $alerts,
    ]);
}

if ($action === 'sync_loyalty_tiers') {
    adminMaintenanceEnsureLoyaltyColumns($pdo);

    if (!adminTableExists($pdo, 'loyalty_points')) {
        jsonResponse(false, 'Loyalty points table does not exist yet.');
    }

    $clients = $pdo->query("
        SELECT
            c.id_client,
            COALESCE(c.total_points, 0) AS old_points,
            COALESCE(c.loyalty_tier, 'bronze') AS old_tier,
            COALESCE(SUM(lp.points), 0) AS current_points,
            COALESCE(SUM(CASE WHEN lp.points > 0 THEN lp.points ELSE 0 END), 0) AS lifetime_earned
        FROM Client c
        LEFT JOIN loyalty_points lp ON lp.client_id = c.id_client
        GROUP BY c.id_client, c.total_points, c.loyalty_tier
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('UPDATE Client SET total_points = ?, loyalty_tier = ? WHERE id_client = ?');
    $updated = 0;
    $tierChanges = 0;

    foreach ($clients as $client) {
        $currentPoints = (int) $client['current_points'];
        $newTier = adminMaintenanceTierFromLifetime((int) $client['lifetime_earned']);
        $oldPoints = (int) $client['old_points'];
        $oldTier = (string) $client['old_tier'];

        if ($oldPoints !== $currentPoints || $oldTier !== $newTier) {
            $stmt->execute([$currentPoints, $newTier, (int) $client['id_client']]);
            $updated++;
            if ($oldTier !== $newTier) {
                $tierChanges++;
            }
        }
    }

    adminLogActivity($pdo, 'sync', 'loyalty_tiers', null, "Synced {$updated} customer loyalty record(s)");

    jsonResponse(true, "Synced {$updated} customer loyalty record(s).", [
        'updated' => $updated,
        'tier_changes' => $tierChanges,
        'checked' => count($clients),
    ]);
}

jsonResponse(false, 'Unknown maintenance action.');
