<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $rows = db()->query("
        SELECT id, in_stock, stock_quantity, reorder_level
        FROM products
        ORDER BY id ASC
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stock = [];
    foreach ($rows as $row) {
        $qty = (int) ($row['stock_quantity'] ?? 0);
        $reorder = (int) ($row['reorder_level'] ?? 5);
        $stock[(string) $row['id']] = [
            'in_stock' => (int) $row['in_stock'] === 1 && $qty > 0,
            'quantity' => max(0, $qty),
            'reorder_level' => max(1, $reorder),
            'tone' => $qty <= 0 ? 'out' : ($qty <= $reorder ? 'critical' : ($qty <= ($reorder * 2) ? 'low' : 'good')),
        ];
    }

    jsonResponse(true, 'Stock loaded.', ['stock' => $stock]);
} catch (Throwable $e) {
    jsonResponse(false, DEV_MODE ? $e->getMessage() : 'Unable to load stock.');
}
