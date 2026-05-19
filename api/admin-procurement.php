<?php
/**
 * api/admin-procurement.php — B2B Procurement and Stock Auditing
 *
 * GET  ?action=suppliers
 * POST { action: "add_supplier", name: "Tech Distro", contact_email: "...", ... }
 * GET  ?action=purchase_orders
 * POST { action: "create_po", supplier_id: 1, total_cost: 5000, expected_delivery: "2026-12-01" }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure admin access
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'suppliers';

    if ($action === 'suppliers') {
        $stmt = $pdo->query("SELECT * FROM vendor_suppliers ORDER BY name ASC");
        echo json_encode(['success' => true, 'suppliers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'purchase_orders') {
        $stmt = $pdo->query("
            SELECT po.*, v.name as supplier_name 
            FROM purchase_orders po
            JOIN vendor_suppliers v ON v.id = po.supplier_id
            ORDER BY po.created_at DESC
        ");
        echo json_encode(['success' => true, 'purchase_orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'add_supplier') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['contact_email'] ?? '');
        $phone = trim($input['contact_phone'] ?? '');

        if (empty($name)) {
            echo json_encode(['error' => 'Supplier name is required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO vendor_suppliers (name, contact_email, contact_phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $phone]);

        echo json_encode(['success' => true, 'supplier_id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'create_po') {
        $supplierId = (int)($input['supplier_id'] ?? 0);
        $totalCost = (float)($input['total_cost'] ?? 0);
        $expectedDelivery = trim($input['expected_delivery'] ?? '');

        if ($supplierId <= 0 || $totalCost <= 0) {
            echo json_encode(['error' => 'Invalid PO data']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, total_cost, expected_delivery) VALUES (?, ?, ?)");
        $stmt->execute([$supplierId, $totalCost, empty($expectedDelivery) ? null : $expectedDelivery]);

        echo json_encode(['success' => true, 'po_id' => $pdo->lastInsertId()]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
