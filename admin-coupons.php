<?php
require_once 'admin-helpers.php';

adminRequireAuth();
$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error = 'Invalid session token.';
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));
        $couponId = (int) ($_POST['id'] ?? 0);

        if ($action === 'delete' && $couponId > 0) {
            $pdo->prepare('DELETE FROM coupon_codes WHERE id = ?')->execute([$couponId]);
            adminLogActivity($pdo, 'delete', 'coupon', $couponId, "Deleted coupon #{$couponId}");
            adminRedirect('admin-coupons.php?deleted=1');
        }

        if ($action === 'toggle' && $couponId > 0) {
            $pdo->prepare('UPDATE coupon_codes SET active = 1 - active WHERE id = ?')->execute([$couponId]);
            adminLogActivity($pdo, 'toggle', 'coupon', $couponId, "Toggled coupon #{$couponId}");
            adminRedirect('admin-coupons.php?updated=1');
        }

        if ($action === 'save') {
            $code = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', (string) ($_POST['code'] ?? '')));
            $type = in_array($_POST['discount_type'] ?? '', ['percent', 'fixed', 'shipping'], true) ? $_POST['discount_type'] : 'percent';
            $value = max(0, (float) ($_POST['discount_value'] ?? 0));
            $minCart = max(0, (float) ($_POST['min_cart'] ?? 0));
            $usageLimit = ($_POST['usage_limit'] ?? '') === '' ? null : max(1, (int) $_POST['usage_limit']);
            $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
            $expiresAt = trim((string) ($_POST['expires_at'] ?? ''));

            if ($code === '') {
                $error = 'Coupon code is required.';
            } elseif ($type !== 'shipping' && $value <= 0) {
                $error = 'Discount value must be greater than zero.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO coupon_codes (code, discount_type, discount_value, min_cart, usage_limit, starts_at, expires_at, active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE
                        discount_type = VALUES(discount_type),
                        discount_value = VALUES(discount_value),
                        min_cart = VALUES(min_cart),
                        usage_limit = VALUES(usage_limit),
                        starts_at = VALUES(starts_at),
                        expires_at = VALUES(expires_at),
                        active = 1
                ');
                $stmt->execute([
                    $code,
                    $type,
                    $type === 'shipping' ? 0 : $value,
                    $minCart,
                    $usageLimit,
                    $startsAt !== '' ? str_replace('T', ' ', $startsAt) . ':00' : null,
                    $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . ':00' : null,
                ]);
                adminLogActivity($pdo, 'save', 'coupon', null, "Saved coupon {$code}");
                adminRedirect('admin-coupons.php?saved=1');
            }
        }
    }
}

$coupons = adminFetchAll($pdo, 'SELECT * FROM coupon_codes ORDER BY created_at DESC, id DESC');

adminPageStart('Coupons', 'coupons');
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Promotion Control</span>
        <h1>Coupons</h1>
        <p class="section-copy">Create promo codes with percent, fixed amount, free shipping, expiration, usage limits, and minimum cart values.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="admin-marketing.php">Marketing</a>
    </div>
</section>

<?php if (isset($_GET['saved'])): ?>
    <div class="admin-alert success">Coupon saved.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="admin-alert success">Coupon deleted.</div>
<?php elseif (isset($_GET['updated'])): ?>
    <div class="admin-alert success">Coupon status updated.</div>
<?php endif; ?>
<?php if ($error): ?><div class="admin-alert error"><?= adminH($error) ?></div><?php endif; ?>

<section class="table-card">
    <div class="card-head"><h2>Create Coupon</h2></div>
    <form method="post" class="filter-bar coupon-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <label class="coupon-code">Code <input name="code" placeholder="WELCOME10" required></label>
        <label class="coupon-type">Type
            <select name="discount_type">
                <option value="percent">Percent</option>
                <option value="fixed">Fixed MAD</option>
                <option value="shipping">Free shipping</option>
            </select>
        </label>
        <label class="coupon-value">Value <input type="number" step="0.01" min="0" name="discount_value" placeholder="10"></label>
        <label class="coupon-min">Minimum cart <input type="number" step="0.01" min="0" name="min_cart" value="0"></label>
        <label class="coupon-limit">Usage limit <input type="number" min="1" name="usage_limit" placeholder="Unlimited"></label>
        <label class="coupon-start">Starts <input type="datetime-local" name="starts_at"></label>
        <label class="coupon-expires">Expires <input type="datetime-local" name="expires_at"></label>
        <button class="button button-primary coupon-submit" type="submit">Save Coupon</button>
    </form>
</section>

<section class="table-card">
    <div class="card-head"><h2>Active Promo Codes</h2></div>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Discount</th>
                <th>Minimum</th>
                <th>Usage</th>
                <th>Window</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($coupons === []): ?>
                <tr><td colspan="7">No coupons yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><strong><?= adminH($coupon['code']) ?></strong></td>
                    <td><?= adminH($coupon['discount_type']) ?> <?= $coupon['discount_type'] !== 'shipping' ? adminH((string) $coupon['discount_value']) : '' ?></td>
                    <td><?= adminMoney((float) $coupon['min_cart']) ?></td>
                    <td><?= (int) $coupon['used_count'] ?> / <?= $coupon['usage_limit'] === null ? '∞' : (int) $coupon['usage_limit'] ?></td>
                    <td>
                        <small><?= $coupon['starts_at'] ? adminH(date('M j, Y H:i', strtotime((string) $coupon['starts_at']))) : 'Now' ?></small><br>
                        <small><?= $coupon['expires_at'] ? adminH(date('M j, Y H:i', strtotime((string) $coupon['expires_at']))) : 'No expiry' ?></small>
                    </td>
                    <td><span class="status-badge <?= !empty($coupon['active']) ? 'is-good' : 'is-danger' ?>"><?= !empty($coupon['active']) ? 'Active' : 'Paused' ?></span></td>
                    <td class="table-actions">
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>">
                            <button class="button button-light button-small" name="action" value="toggle" type="submit">Toggle</button>
                            <button class="button button-danger button-small" name="action" value="delete" type="submit" onclick="return confirm('Delete this coupon?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<style>
.coupon-form {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    grid-template-areas:
        "code type value minimum limit"
        "start . expires submit submit";
    gap: 24px 34px;
    align-items: end;
}

.coupon-form *,
.coupon-form input,
.coupon-form select {
    box-sizing: border-box;
    min-width: 0;
}

.coupon-code { grid-area: code; }
.coupon-type { grid-area: type; }
.coupon-value { grid-area: value; }
.coupon-min { grid-area: minimum; }
.coupon-limit { grid-area: limit; }
.coupon-start { grid-area: start; }
.coupon-expires { grid-area: expires; }
.coupon-submit {
    grid-area: submit;
    min-width: 180px;
    justify-self: start;
    margin-left: 0;
}

@media (max-width: 1100px) {
    .coupon-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-areas:
            "code type"
            "value minimum"
            "limit start"
            "expires submit";
    }
}

@media (max-width: 640px) {
    .coupon-form {
        grid-template-columns: 1fr;
        grid-template-areas:
            "code"
            "type"
            "value"
            "minimum"
            "limit"
            "start"
            "expires"
            "submit";
    }

    .coupon-submit {
        width: 100%;
        margin-left: 0;
    }
}
</style>
<?php adminPageEnd(); ?>
