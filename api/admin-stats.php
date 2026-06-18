<?php
/**
 * api/admin-stats.php — Lightweight dashboard stats endpoint for auto-refresh
 */
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/admin-helpers.php';
header('Content-Type: application/json');

adminRequireJsonAuth();

$pdo = db();

// Pending orders
$pendingStmt = $pdo->query("SELECT COUNT(*) FROM `Order` WHERE status = 'pending'");
$pendingOrders = (int) $pendingStmt->fetchColumn();

// Orders today
$todayStmt = $pdo->query("SELECT COUNT(*) FROM `Order` WHERE DATE(created_at) = CURDATE()");
$ordersToday = (int) $todayStmt->fetchColumn();

// Total revenue today
$revenueStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `Order` WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$revenueToday = (float) $revenueStmt->fetchColumn();

// Low stock items
$lowStockStmt = $pdo->query("SELECT COUNT(*) FROM Product WHERE stock <= reorder_level");
$lowStock = (int) $lowStockStmt->fetchColumn();

echo json_encode([
    'pending_orders' => $pendingOrders,
    'orders_today' => $ordersToday,
    'revenue_today' => $revenueToday,
    'low_stock' => $lowStock,
]);
