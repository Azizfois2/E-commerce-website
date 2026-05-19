<?php
declare(strict_types=1);

require_once __DIR__ . '/inventory-helpers.php';

/**
 * Ensure optional payment columns exist on orders (prefer running setup-tables.php once).
 */
function checkoutEnsureOrdersPaymentColumns(PDO $pdo): void
{
    inventoryEnsureOrderStockColumn($pdo);

    $hasColumn = static function (PDO $pdo, string $column): bool {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
        ');
        $stmt->execute(['orders', $column]);
        return (bool) $stmt->fetchColumn();
    };

    if (!$hasColumn($pdo, 'transaction_id')) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN transaction_id VARCHAR(64) DEFAULT NULL AFTER payment_status');
    }
    if (!$hasColumn($pdo, 'paypal_order_id')) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN paypal_order_id VARCHAR(64) DEFAULT NULL AFTER transaction_id');
    }
}

/**
 * @param array<int, array<string, mixed>> $items Raw cart lines from client (id, quantity, …)
 * @return array{
 *   lines: list<array{product_id:int, quantity:int, unit_price:float, name:string, flash_sale_id:?int}>,
 *   subtotal: float
 * }
 */
function checkoutResolveCartLines(PDO $pdo, array $items): array
{
    $merged = [];
    foreach ($items as $item) {
        $rawId = $item['id'] ?? null;
        if (!is_int($rawId) && !(is_string($rawId) && ctype_digit($rawId))) {
            continue;
        }
        $productId = (int) $rawId;
        if ($productId <= 0) {
            continue;
        }
        $qty = max(1, (int) ($item['quantity'] ?? 1));
        $merged[$productId] = ($merged[$productId] ?? 0) + $qty;
    }

    $normalized = [];
    foreach ($merged as $productId => $qty) {
        $normalized[] = ['product_id' => $productId, 'quantity' => $qty];
    }

    if ($normalized === []) {
        throw new RuntimeException('No valid products in cart.');
    }

    $ids = array_unique(array_column($normalized, 'product_id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $products[(int) $row['id']] = [
            'name' => (string) $row['name'],
            'price' => (float) $row['price'],
        ];
    }

    $flashByProduct = [];
    try {
        $flashStmt = $pdo->prepare("
        SELECT id, product_id, sale_price, max_quantity, sold_count
        FROM flash_sales
        WHERE product_id IN ($placeholders)
          AND starts_at <= NOW()
          AND ends_at > NOW()
          AND (max_quantity IS NULL OR sold_count < max_quantity)
        FOR UPDATE
    ");
        $flashStmt->execute($ids);
        foreach ($flashStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pid = (int) $row['product_id'];
            if (!isset($flashByProduct[$pid])) {
                $flashByProduct[$pid] = [
                    'id' => (int) $row['id'],
                    'sale_price' => (float) $row['sale_price'],
                    'max_quantity' => $row['max_quantity'] !== null ? (int) $row['max_quantity'] : null,
                    'sold_count' => (int) $row['sold_count'],
                ];
            }
        }
    } catch (PDOException $e) {
        // flash_sales may not exist on minimal installs
        $flashByProduct = [];
    }

    $lockedCartPrices = [];
    $clientId = $_SESSION['client_id'] ?? null;
    if ($clientId) {
        try {
            $lockStmt = $pdo->prepare("
                SELECT cart_data 
                FROM abandoned_carts 
                WHERE client_id = ? 
                  AND locked_at >= NOW() - INTERVAL 48 HOUR
            ");
            $lockStmt->execute([$clientId]);
            $lockRow = $lockStmt->fetch(PDO::FETCH_ASSOC);
            if ($lockRow) {
                $lockedItems = json_decode($lockRow['cart_data'], true);
                if (is_array($lockedItems)) {
                    foreach ($lockedItems as $li) {
                        if (isset($li['id']) && isset($li['price'])) {
                            $lockedCartPrices[(int)$li['id']] = (float)$li['price'];
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            // Table or column might be missing
        }
    }

    $lines = [];
    $subtotal = 0.0;

    foreach ($normalized as $row) {
        $pid = $row['product_id'];
        $qty = $row['quantity'];
        if (!isset($products[$pid])) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }
        $name = $products[$pid]['name'];
        $basePrice = $products[$pid]['price'];
        
        // Respect 48-hour locked price if lower than current store price
        if (isset($lockedCartPrices[$pid])) {
            $basePrice = min($basePrice, $lockedCartPrices[$pid]);
        }

        $flashSaleId = null;
        $unit = $basePrice;

        if (isset($flashByProduct[$pid])) {
            $fs = $flashByProduct[$pid];
            $remaining = $fs['max_quantity'] === null
                ? PHP_INT_MAX
                : max(0, $fs['max_quantity'] - $fs['sold_count']);
            if ($qty <= $remaining) {
                $unit = $fs['sale_price'];
                $flashSaleId = $fs['id'];
            }
        }

        $lineTotal = round($unit * $qty, 2);
        $subtotal = round($subtotal + $lineTotal, 2);
        $lines[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'unit_price' => $unit,
            'name' => $name,
            'flash_sale_id' => $flashSaleId,
        ];
    }

    return ['lines' => $lines, 'subtotal' => $subtotal];
}

/**
 * Apply loyalty points redemption cap: 10 points = 1 MAD discount, not exceeding subtotal.
 *
 * @return array{effective_points:int, discount:float, payable:float}
 */
function checkoutApplyPointsDiscount(float $subtotal, int $pointsRequested, int $pointsAvailable): array
{
    if ($pointsRequested <= 0 || $subtotal <= 0) {
        return ['effective_points' => 0, 'discount' => 0.0, 'payable' => round($subtotal, 2)];
    }
    $maxPointsBySubtotal = (int) floor($subtotal * 10);
    $effective = max(0, min($pointsRequested, $pointsAvailable, $maxPointsBySubtotal));
    $discount = round($effective / 10.0, 2);
    $payable = max(0.0, round($subtotal - $discount, 2));

    return [
        'effective_points' => $effective,
        'discount' => $discount,
        'payable' => $payable,
    ];
}

function checkoutIncrementFlashSalesSold(PDO $pdo, array $lines): void
{
    foreach ($lines as $line) {
        $fid = $line['flash_sale_id'] ?? null;
        if ($fid === null) {
            continue;
        }
        $qty = (int) $line['quantity'];
        $upd = $pdo->prepare('
            UPDATE flash_sales
            SET sold_count = sold_count + ?
            WHERE id = ?
              AND starts_at <= NOW()
              AND ends_at > NOW()
              AND (max_quantity IS NULL OR sold_count + ? <= max_quantity)
        ');
        $upd->execute([$qty, $fid, $qty]);
        if ($upd->rowCount() === 0) {
            throw new RuntimeException('A flash sale ended or sold out while placing your order.');
        }
    }
}
