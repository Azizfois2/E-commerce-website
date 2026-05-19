<?php
require_once 'admin-helpers.php';

adminRequireAuth();
$pdo = db();
adminEnsureAdminSuiteTables($pdo);

// Ensure the vendor_suppliers and purchase_orders tables are present
$pdo->exec("
    CREATE TABLE IF NOT EXISTS vendor_suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_email VARCHAR(255) DEFAULT NULL,
        contact_phone VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        expected_delivery DATE DEFAULT NULL,
        status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES vendor_suppliers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = 'Invalid session token.';
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'add_supplier') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['contact_email'] ?? ''));
            $phone = trim((string) ($_POST['contact_phone'] ?? ''));

            if ($name === '') {
                $error = 'Supplier name is required.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO vendor_suppliers (name, contact_email, contact_phone) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email === '' ? null : $email, $phone === '' ? null : $phone]);
                $supplierId = $pdo->lastInsertId();
                adminLogActivity($pdo, 'create', 'supplier', $supplierId, "Added vendor supplier: {$name}");
                $success = 'Supplier added successfully.';
            }
        }

        if ($action === 'create_po') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $totalCost = max(0, (float) ($_POST['total_cost'] ?? 0));
            $expectedDelivery = trim((string) ($_POST['expected_delivery'] ?? ''));

            if ($supplierId <= 0 || $totalCost <= 0) {
                $error = 'Please select a valid supplier and specify a total cost.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, total_cost, expected_delivery) VALUES (?, ?, ?)");
                $stmt->execute([$supplierId, $totalCost, $expectedDelivery === '' ? null : $expectedDelivery]);
                $poId = $pdo->lastInsertId();
                adminLogActivity($pdo, 'create', 'purchase_order', $poId, "Created PO #{$poId} for supplier ID {$supplierId}");
                $success = 'Purchase Order created successfully.';
            }
        }

        if ($action === 'update_po_status') {
            $poId = (int) ($_POST['po_id'] ?? 0);
            $status = trim((string) ($_POST['status'] ?? ''));

            if ($poId > 0 && in_array($status, ['pending', 'ordered', 'received', 'cancelled'], true)) {
                $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $poId]);
                adminLogActivity($pdo, 'update', 'purchase_order', $poId, "Updated PO #{$poId} status to {$status}");
                $success = 'PO status updated.';
            }
        }
    }
}

// Fetch lists
$suppliers = adminFetchAll($pdo, "SELECT * FROM vendor_suppliers ORDER BY name ASC");
$purchaseOrders = adminFetchAll($pdo, "
    SELECT po.*, v.name AS supplier_name 
    FROM purchase_orders po
    JOIN vendor_suppliers v ON v.id = po.supplier_id
    ORDER BY po.created_at DESC
");

adminPageStart('B2B Procurement', 'procurement');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Supply Chain & Logistics</span>
        <h1>B2B Procurement</h1>
        <p class="section-copy">Manage wholesale component vendor suppliers, audit stock restock cycles, and tracks active Purchase Orders.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-stock.php">Inventory Stock</a>
    </div>
</section>

<?php if ($success): ?><div class="admin-alert success"><?= adminH($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert error"><?= adminH($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;margin-bottom:24px">
    <!-- Purchase Orders View -->
    <section class="table-card" style="margin-bottom:0">
        <div class="card-head"><h2>Active Purchase Orders</h2></div>
        <div style="padding:16px;max-height:600px;overflow-y:auto">
            <table>
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Supplier</th>
                        <th>Total Cost</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($purchaseOrders === []): ?>
                        <tr><td colspan="6">No purchase orders created yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($purchaseOrders as $po): ?>
                        <tr>
                            <td><strong>#<?= (int)$po['id'] ?></strong></td>
                            <td><?= adminH($po['supplier_name']) ?></td>
                            <td><strong><?= adminMoney((float)$po['total_cost']) ?></strong></td>
                            <td><?= $po['expected_delivery'] ? adminH(date('Y-m-d', strtotime((string)$po['expected_delivery']))) : '<em style="color:var(--muted)">Not set</em>' ?></td>
                            <td>
                                <span class="status-badge <?= $po['status'] === 'received' ? 'is-good' : ($po['status'] === 'cancelled' ? 'is-danger' : 'is-warn') ?>">
                                    <?= adminH(ucfirst($po['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:inline-flex;gap:4px">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_po_status">
                                    <input type="hidden" name="po_id" value="<?= (int)$po['id'] ?>">
                                    <select name="status" style="padding:4px;border-radius:6px;background:var(--page-bg);color:var(--text);border:1px solid var(--border)">
                                        <option value="pending" <?= $po['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="ordered" <?= $po['status'] === 'ordered' ? 'selected' : '' ?>>Ordered</option>
                                        <option value="received" <?= $po['status'] === 'received' ? 'selected' : '' ?>>Received</option>
                                        <option value="cancelled" <?= $po['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="button button-primary button-small"><i class="fas fa-check"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Create PO & Suppliers -->
    <div style="display:flex;flex-direction:column;gap:24px">
        <section class="table-card" style="margin-bottom:0">
            <div class="card-head"><h2>Create Purchase Order</h2></div>
            <form method="post" style="padding:20px;display:flex;flex-direction:column;gap:16px">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_po">
                
                <label>
                    Select Vendor Supplier
                    <select name="supplier_id" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                        <option value="">-- Choose Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= adminH($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Total Order Cost (MAD)
                    <input type="number" step="0.01" min="0.01" name="total_cost" placeholder="e.g. 15000" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <label>
                    Expected Delivery Date
                    <input type="date" name="expected_delivery" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <button type="submit" class="button button-primary" style="margin-top:8px">Submit Purchase Order</button>
            </form>
        </section>

        <section class="table-card" style="margin-bottom:0">
            <div class="card-head"><h2>Add New B2B Supplier</h2></div>
            <form method="post" style="padding:20px;display:flex;flex-direction:column;gap:16px">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_supplier">

                <label>
                    Supplier / Company Name
                    <input type="text" name="name" placeholder="Tech Distro Morocco" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <label>
                    Contact Email Address
                    <input type="email" name="contact_email" placeholder="contact@techdistro.ma" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <label>
                    Contact Phone Number
                    <input type="text" name="contact_phone" placeholder="+212 600-000000" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <button type="submit" class="button button-info" style="margin-top:8px">Add Supplier</button>
            </form>
        </section>
    </div>
</div>

<section class="table-card">
    <div class="card-head"><h2>Registered Component Suppliers</h2></div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Contact Email</th>
                <th>Phone Number</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($suppliers === []): ?>
                <tr><td colspan="5">No suppliers registered. Add a supplier above.</td></tr>
            <?php endif; ?>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td>#<?= (int)$s['id'] ?></td>
                    <td><strong><?= adminH($s['name']) ?></strong></td>
                    <td><?= $s['contact_email'] ? adminH($s['contact_email']) : '<em style="color:var(--muted)">Not provided</em>' ?></td>
                    <td><?= $s['contact_phone'] ? adminH($s['contact_phone']) : '<em style="color:var(--muted)">Not provided</em>' ?></td>
                    <td><?= adminH(date('Y-m-d', strtotime((string)$s['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php adminPageEnd(); ?>
