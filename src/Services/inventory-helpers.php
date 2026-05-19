<?php
declare(strict_types=1);

function inventoryEnsureOrderStockColumn(PDO $pdo): void
{
    try {
        $pdo->query('SELECT stock_reserved FROM orders LIMIT 0');
    } catch (PDOException $e) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN stock_reserved TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_status');
    }
}

function inventoryReservedStatuses(): array
{
    return ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
}

function inventoryIsReservedStatus(?string $status): bool
{
    return in_array((string) $status, inventoryReservedStatuses(), true);
}

function inventoryQuantitiesFromCartItems(array $items): array
{
    $quantities = [];

    foreach ($items as $item) {
        $rawId = $item['id'] ?? null;
        if (!is_int($rawId) && !(is_string($rawId) && ctype_digit($rawId))) {
            continue;
        }

        $productId = (int) $rawId;
        if ($productId <= 0) {
            continue;
        }

        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
    }

    return $quantities;
}

function inventoryQuantitiesFromOrder(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare('
        SELECT product_id, SUM(quantity) AS quantity
        FROM order_items
        WHERE order_id = ? AND product_id IS NOT NULL
        GROUP BY product_id
    ');
    $stmt->execute([$orderId]);

    $quantities = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productId = (int) $row['product_id'];
        $quantity = (int) $row['quantity'];
        if ($productId > 0 && $quantity > 0) {
            $quantities[$productId] = $quantity;
        }
    }

    return $quantities;
}

function inventoryReserveQuantities(PDO $pdo, array $quantities): void
{
    foreach ($quantities as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT name, stock_quantity FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }

        $available = (int) $product['stock_quantity'];
        if ($available < $quantity) {
            throw new RuntimeException(sprintf(
                'Only %d unit(s) left for %s.',
                $available,
                (string) $product['name']
            ));
        }

        $update = $pdo->prepare('
            UPDATE products
            SET stock_quantity = stock_quantity - ?,
                in_stock = CASE WHEN stock_quantity - ? > 0 THEN 1 ELSE 0 END
            WHERE id = ?
        ');
        $update->execute([$quantity, $quantity, $productId]);
    }
}

function inventoryRestoreQuantities(PDO $pdo, array $quantities): void
{
    foreach ($quantities as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $stmt = $pdo->prepare('
            UPDATE products
            SET stock_quantity = stock_quantity + ?,
                in_stock = 1
            WHERE id = ?
        ');
        $stmt->execute([$quantity, $productId]);
    }
}

function inventoryReserveOrderStock(PDO $pdo, int $orderId): void
{
    inventoryReserveQuantities($pdo, inventoryQuantitiesFromOrder($pdo, $orderId));
    $pdo->prepare('UPDATE orders SET stock_reserved = 1 WHERE id = ?')->execute([$orderId]);
}

function inventoryRestoreOrderStock(PDO $pdo, int $orderId): void
{
    inventoryRestoreQuantities($pdo, inventoryQuantitiesFromOrder($pdo, $orderId));
    $pdo->prepare('UPDATE orders SET stock_reserved = 0 WHERE id = ?')->execute([$orderId]);
}

function inventorySyncOrderStockForStatus(PDO $pdo, int $orderId, ?string $oldStatus, string $newStatus, bool $stockReserved): void
{
    if ($oldStatus === $newStatus) {
        return;
    }

    if ($stockReserved && inventoryIsReservedStatus($oldStatus) && !inventoryIsReservedStatus($newStatus)) {
        inventoryRestoreOrderStock($pdo, $orderId);
        return;
    }

    if (!$stockReserved && inventoryIsReservedStatus($newStatus)) {
        inventoryReserveOrderStock($pdo, $orderId);
    }
}

function inventoryCancelOpenOrdersForClient(PDO $pdo, int $clientId, string $changedBy = 'system'): int
{
    inventoryEnsureOrderStockColumn($pdo);
    $historyActor = in_array($changedBy, ['system', 'admin', 'customer'], true) ? $changedBy : 'system';

    $stmt = $pdo->prepare("
        SELECT id, status, stock_reserved
        FROM orders
        WHERE client_id = ? AND status IN ('pending', 'processing')
        FOR UPDATE
    ");
    $stmt->execute([$clientId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $orderId = (int) $order['id'];
        $oldStatus = (string) $order['status'];

        if (!empty($order['stock_reserved'])) {
            inventoryRestoreOrderStock($pdo, $orderId);
        }

        $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
        $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)')
            ->execute([$orderId, $oldStatus, 'cancelled', $historyActor, 'Cancelled because the customer account was deleted.']);
    }

    return count($orders);
}
