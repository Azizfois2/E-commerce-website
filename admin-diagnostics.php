<?php
require_once 'admin-helpers.php';

adminRequireAuth();
$pdo = db();
adminEnsureAdminSuiteTables($pdo);

// Ensure hardware_diagnostic_reports table is present
$pdo->exec("
    CREATE TABLE IF NOT EXISTS hardware_diagnostic_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        technician_id INT NOT NULL,
        component_type VARCHAR(50) NOT NULL DEFAULT 'system',
        test_name VARCHAR(100) NOT NULL,
        score INT DEFAULT 0,
        max_temp_c INT DEFAULT 0,
        result_details LONGTEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = adminPhrase('Invalid session token.');
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'delete_report') {
            $reportId = (int) ($_POST['report_id'] ?? 0);
            if ($reportId > 0) {
                $pdo->prepare("DELETE FROM hardware_diagnostic_reports WHERE id = ?")->execute([$reportId]);
                adminLogActivity($pdo, 'delete', 'diagnostic_report', $reportId, "Deleted hardware diagnostic report #{$reportId}");
                $success = adminPhrase('Diagnostic report deleted successfully.');
            }
        }
    }
}

// Fetch all existing diagnostic reports
$reports = adminFetchAll($pdo, "
    SELECT r.*, o.id AS order_id, c.nom AS client_name, u.name AS technician_name
    FROM hardware_diagnostic_reports r
    JOIN orders o ON o.id = r.order_id
    LEFT JOIN Client c ON c.id_client = o.client_id
    LEFT JOIN admin_users u ON u.id = r.technician_id
    ORDER BY r.created_at DESC
");

// Fetch building/testing orders to display as suggestions for uploads
$orders = adminFetchAll($pdo, "
    SELECT o.id, c.nom AS client_name, o.assembly_status
    FROM orders o
    LEFT JOIN Client c ON c.id_client = o.client_id
    WHERE o.assembly_status IN ('building', 'testing', 'ready', 'qc_passed')
    ORDER BY o.id DESC
");

adminPageStart('Hardware Diagnostics', 'diagnostics');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow"><?= adminH(adminPhrase('Quality Assurance & Testing')) ?></span>
        <h1><?= adminH(adminPhrase('Hardware Diagnostics')) ?></h1>
        <p class="section-copy"><?= adminH(adminPhrase('Upload benchmark specs, temperature thresholds, and detailed diagnostics profiles for high-end custom built rigs.')) ?></p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php"><?= adminH(adminPhrase('Dashboard')) ?></a>
        <a class="button button-light" href="admin-orders.php"><?= adminH(adminPhrase('Active Orders')) ?></a>
    </div>
</section>

<?php if ($success): ?><div class="admin-alert success"><?= adminH($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert error"><?= adminH($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.3fr;gap:24px;margin-bottom:24px">
    <!-- Upload Form -->
    <section class="table-card" style="margin-bottom:0">
        <div class="card-head"><h2><?= adminH(adminPhrase('Upload Diagnostic Report')) ?></h2></div>
        <form id="diagnosticsUploadForm" style="padding:20px;display:flex;flex-direction:column;gap:16px">
            <label>
                <?= adminH(adminPhrase('Select Active Build Order')) ?>
                <select id="diagOrderId" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                    <option value=""><?= adminH(adminPhrase('-- Choose Build Order --')) ?></option>
                    <?php foreach ($orders as $o): ?>
                        <option value="<?= (int)$o['id'] ?>"><?= adminH(adminPhrase('Order #{id} - {client} (Status: {status})', ['id' => (int) $o['id'], 'client' => $o['client_name'] ?: adminPhrase('Unknown Client'), 'status' => adminStatusLabel($o['assembly_status'])])) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <label>
                    <?= adminH(adminPhrase('Component Type')) ?>
                    <select id="diagComponent" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                        <option value="system"><?= adminH(adminPhrase('System (Overall)')) ?></option>
                        <option value="cpu"><?= adminH(adminPhrase('Processor (CPU)')) ?></option>
                        <option value="gpu"><?= adminH(adminPhrase('Graphics Card (GPU)')) ?></option>
                        <option value="ram"><?= adminH(adminPhrase('Memory (RAM)')) ?></option>
                        <option value="storage"><?= adminH(adminPhrase('Storage SSD')) ?></option>
                    </select>
                </label>

                <label>
                    <?= adminH(adminPhrase('Test / Benchmark Name')) ?>
                    <input type="text" id="diagTestName" placeholder="<?= adminH(adminPhrase('e.g. 3DMark TimeSpy')) ?>" required style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <label>
                    <?= adminH(adminPhrase('Benchmark Score (Optional)')) ?>
                    <input type="number" id="diagScore" placeholder="<?= adminH(adminPhrase('e.g. 18200')) ?>" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>

                <label>
                    <?= adminH(adminPhrase('Max Temperature reached (°C)')) ?>
                    <input type="number" id="diagTemp" placeholder="<?= adminH(adminPhrase('e.g. 74')) ?>" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text)">
                </label>
            </div>

            <label>
                <?= adminH(adminPhrase('Diagnostic Logs / Notes')) ?>
                <textarea id="diagDetails" placeholder="<?= adminH(adminPhrase('Add specific hardware metrics or notes...')) ?>" rows="4" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--page-bg);color:var(--text);font-family:inherit"></textarea>
            </label>

            <button type="button" onclick="uploadDiagnosticReport()" class="button button-primary" style="margin-top:8px">
                <i class="fas fa-upload"></i> <?= adminH(adminPhrase('Upload Diagnostic Profile')) ?>
            </button>
        </form>
    </section>

    <!-- Uploaded Reports List -->
    <section class="table-card" style="margin-bottom:0">
        <div class="card-head"><h2><?= adminH(adminPhrase('Recent Diagnostics Reports')) ?></h2></div>
        <div style="padding:16px;max-height:600px;overflow-y:auto">
            <table>
                <thead>
                    <tr>
                        <th><?= adminH(adminPhrase('ID')) ?></th>
                        <th><?= adminH(adminPhrase('Order')) ?></th>
                        <th><?= adminH(adminPhrase('Component')) ?></th>
                        <th><?= adminH(adminPhrase('Test / Score')) ?></th>
                        <th><?= adminH(adminPhrase('Max Temp')) ?></th>
                        <th><?= adminH(adminPhrase('Actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports === []): ?>
                        <tr><td colspan="6"><?= adminH(adminPhrase('No diagnostic reports uploaded yet.')) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $r): ?>
                        <tr id="report-row-<?= (int)$r['id'] ?>">
                            <td>#<?= (int)$r['id'] ?></td>
                            <td>
                                <strong><?= adminH(adminPhrase('Order #{id}', ['id' => (int) $r['order_id']])) ?></strong>
                                <small style="display:block"><?= adminH($r['client_name'] ?: adminPhrase('Anonymous')) ?></small>
                            </td>
                            <td><span class="status-badge is-info"><?= adminH(strtoupper($r['component_type'])) ?></span></td>
                            <td>
                                <strong><?= adminH($r['test_name']) ?></strong>
                                <span style="display:block;font-size:0.8rem;color:var(--cyan)"><?= (int)$r['score'] ?> pts</span>
                            </td>
                            <td>
                                <span style="color: <?= (int)$r['max_temp_c'] > 85 ? '#ff8f70' : 'var(--text)' ?>">
                                    <strong><?= (int)$r['max_temp_c'] ?>°C</strong>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:inline-block" onsubmit="return confirm(<?= i18n_script_json(adminPhrase('Delete this diagnostic report?')) ?>)">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_report">
                                    <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="button button-danger button-small"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
async function uploadDiagnosticReport() {
    const orderId = document.getElementById('diagOrderId').value;
    const componentType = document.getElementById('diagComponent').value;
    const testName = document.getElementById('diagTestName').value;
    const score = document.getElementById('diagScore').value;
    const maxTemp = document.getElementById('diagTemp').value;
    const details = document.getElementById('diagDetails').value;

    if (!orderId || !testName) {
        alert(<?= i18n_script_json(adminPhrase('Please select an active order and enter a test name.')) ?>);
        return;
    }

    try {
        const res = await fetch('api/admin-diagnostics.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'upload',
                order_id: parseInt(orderId),
                component_type: componentType,
                test_name: testName,
                score: score ? parseInt(score) : 0,
                max_temp_c: maxTemp ? parseInt(maxTemp) : 0,
                result_details: { notes: details }
            })
        });

        const data = await res.json();
        if (data.success) {
            alert(<?= i18n_script_json(adminPhrase('Diagnostic report uploaded successfully!')) ?>);
            location.reload();
        } else {
            alert(data.error || <?= i18n_script_json(adminPhrase('Failed to upload diagnostic report.')) ?>);
        }
    } catch (e) {
        console.error(e);
        alert(<?= i18n_script_json(adminPhrase('An error occurred during upload.')) ?>);
    }
}
</script>
<?php adminPageEnd(); ?>
