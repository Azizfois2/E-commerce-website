<?php
/**
 * api/admin-flash-sales.php — Admin CRUD for flash sales.
 *
 * POST { action: "create", product_id, sale_price, starts_at, ends_at, max_quantity?, event_name?, event_badge?, event_note? }
 * POST { action: "delete", id }
 * GET  → list all flash sales (active + upcoming + expired)
 */
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/admin-helpers.php';

header('Content-Type: application/json');

adminRequireJsonAuth();

$pdo = db();
adminEnsureFlashSalesTable($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List all flash sales
    $stmt = $pdo->query("
        SELECT fs.*, p.name AS product_name, p.price AS current_price, p.image AS product_image
        FROM flash_sales fs
        JOIN products p ON p.id = fs.product_id
        ORDER BY fs.ends_at DESC
    ");
    echo json_encode(['success' => true, 'sales' => $stmt->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'create':
        $productId = (int) ($input['product_id'] ?? 0);
        $salePrice = (float) ($input['sale_price'] ?? 0);
        $startsAt = $input['starts_at'] ?? '';
        $endsAt = $input['ends_at'] ?? '';
        $maxQty = !empty($input['max_quantity']) ? (int) $input['max_quantity'] : null;
        $eventName = trim((string) ($input['event_name'] ?? ''));
        $eventBadge = trim((string) ($input['event_badge'] ?? ''));
        $eventNote = trim((string) ($input['event_note'] ?? ''));

        if (!$productId || !$salePrice || !$startsAt || !$endsAt) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
            exit;
        }

        // Get original price
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'error' => 'Product not found.']);
            exit;
        }

        if ($salePrice >= $product['price']) {
            echo json_encode(['success' => false, 'error' => 'Sale price must be lower than current price.']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO flash_sales (product_id, sale_price, original_price, max_quantity, event_name, event_badge, event_note, starts_at, ends_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $productId,
            $salePrice,
            $product['price'],
            $maxQty,
            $eventName !== '' ? $eventName : null,
            $eventBadge !== '' ? $eventBadge : null,
            $eventNote !== '' ? $eventNote : null,
            $startsAt,
            $endsAt,
        ]);

        echo json_encode(['success' => true, 'message' => 'Flash sale created!', 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete':
        $id = (int) ($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Sale ID required.']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM flash_sales WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Flash sale deleted.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
