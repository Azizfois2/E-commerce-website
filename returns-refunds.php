<?php
require_once 'config.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();
$returnsPhraseMap = i18n_page_phrase_map(i18n_current_locale());
$returnsT = static function (string $text) use ($returnsPhraseMap): string {
    return $returnsPhraseMap[$text] ?? $text;
};
$returnsE = static function (string $text) use ($returnsT): string {
    return htmlspecialchars($returnsT($text), ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $returnsE('Returns, Refunds & After-Sales Service'); ?> - Maroc PC</title>
    <meta name="description" content="<?= $returnsE('Start a return, refund, warranty, repair, missing item, or damaged package request with Maroc PC after-sales support.'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/after-sales.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <?= i18n_preference_assets() ?>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>
            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.products'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.pc_build_wizard'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('index.php#categories'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.categories'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.deals'); ?></a>
            </nav>
            <div style="flex:1"></div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <?= i18n_language_switcher('nav-translate') ?>
            <div class="cart-wrapper" id="userNav">
                <a href="<?= htmlspecialchars(i18n_url('account.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.account'); ?>">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.shopping_cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <main class="after-sales-page">
        <section class="after-hero">
            <div class="after-hero-copy">
                <span class="eyebrow"><i class="fas fa-headset"></i> <?= $returnsE('After-Sales Desk'); ?></span>
                <h1><?= $returnsE('Returns, Refunds & Warranty Service'); ?></h1>
                <p><?= $returnsE('Open a service ticket for returns, refunds, exchanges, warranty diagnostics, damaged parcels, or missing items. We triage hardware cases with order checks, serial verification, and clear next steps.'); ?></p>
                <div class="after-actions">
                    <a href="#service-request" class="btn btn-primary"><?= $returnsE('Start a Request'); ?></a>
                    <a href="account.php?tab=orders" class="btn btn-secondary"><?= $returnsE('Track My Orders'); ?></a>
                </div>
            </div>
            <div class="after-hero-panel" aria-label="Service promise">
                <strong><?= $returnsE('RMA response'); ?></strong>
                <span><?= $returnsE('1-2 business days'); ?></span>
                <small><?= $returnsE('Urgent triage for damaged or missing items.'); ?></small>
            </div>
        </section>

        <section class="service-metrics" aria-label="After-sales commitments">
            <article>
                <i class="fas fa-rotate-left"></i>
                <strong><?= $returnsE('14 days'); ?></strong>
                <span><?= $returnsE('Return or exchange window for eligible complete items.'); ?></span>
            </article>
            <article>
                <i class="fas fa-screwdriver-wrench"></i>
                <strong><?= $returnsE('48h'); ?></strong>
                <span><?= $returnsE('Initial diagnostic plan for warranty and repair cases.'); ?></span>
            </article>
            <article>
                <i class="fas fa-money-bill-transfer"></i>
                <strong><?= $returnsE('3-10 days'); ?></strong>
                <span><?= $returnsE('Refund processing after inspection approval.'); ?></span>
            </article>
            <article>
                <i class="fas fa-box-open"></i>
                <strong><?= $returnsE('24h'); ?></strong>
                <span><?= $returnsE('Damaged parcel or missing-item priority review.'); ?></span>
            </article>
        </section>

        <section class="after-grid">
            <article class="policy-panel">
                <span class="eyebrow">Policy</span>
                <h2><?= $returnsE('What We Can Handle'); ?></h2>
                <div class="policy-list">
                    <div>
                        <strong><?= $returnsE('Returns & exchanges'); ?></strong>
                        <p><?= $returnsE('Accepted for complete products with accessories, manuals, packaging, and no physical damage. Opened items may need inspection before approval.'); ?></p>
                    </div>
                    <div>
                        <strong><?= $returnsE('Refunds'); ?></strong>
                        <p><?= $returnsE('Issued to the original payment method after service approval and product inspection. COD refunds may require bank or wallet details.'); ?></p>
                    </div>
                    <div>
                        <strong><?= $returnsE('Warranty & repairs'); ?></strong>
                        <p><?= $returnsE('We validate serial numbers, symptoms, purchase date, and manufacturer coverage before routing to repair, replacement, or brand service.'); ?></p>
                    </div>
                    <div>
                        <strong><?= $returnsE('Damaged or missing items'); ?></strong>
                        <p><?= $returnsE('Report within 24 hours of delivery. Keep all packaging and send photos of the parcel, labels, and product condition.'); ?></p>
                    </div>
                </div>
            </article>

            <article class="timeline-panel">
                <span class="eyebrow">Flow</span>
                <h2><?= $returnsE('Service Timeline'); ?></h2>
                <ol class="service-timeline">
                    <li><strong><?= $returnsE('Submit ticket'); ?></strong><span><?= $returnsE('Order number, product, issue type, and preferred resolution.'); ?></span></li>
                    <li><strong><?= $returnsE('Eligibility check'); ?></strong><span><?= $returnsE('We verify order, payment, delivery status, return window, and warranty path.'); ?></span></li>
                    <li><strong><?= $returnsE('Return intake'); ?></strong><span><?= $returnsE('Drop-off or courier instructions are sent after approval.'); ?></span></li>
                    <li><strong><?= $returnsE('Inspection'); ?></strong><span><?= $returnsE('Technicians check completeness, damage, serials, and fault symptoms.'); ?></span></li>
                    <li><strong><?= $returnsE('Resolution'); ?></strong><span><?= $returnsE('Refund, replacement, store credit, repair, or diagnostic report.'); ?></span></li>
                </ol>
            </article>
        </section>

        <section class="request-section" id="service-request">
            <div class="request-copy">
                <span class="eyebrow">RMA Form</span>
                <h2><?= $returnsE('Start an After-Sales Request'); ?></h2>
                <p><?= $returnsE('Use the same email as your order. If you are signed in, the ticket will also attach to your account. Add the product serial number for warranty or repair cases when available.'); ?></p>
                <div class="support-card">
                    <strong><?= $returnsE('Need help now?'); ?></strong>
                    <a href="tel:+212618821949"><i class="fas fa-phone"></i> +212 618821949</a>
                    <a href="mailto:support@marocpc.com"><i class="fas fa-envelope"></i> support@marocpc.com</a>
                </div>
            </div>

            <form class="after-form" id="afterSalesForm">
                <div class="form-row">
                    <label><?= $returnsE('Order number'); ?>
                        <input type="number" name="order_id" min="1" placeholder="<?= $returnsE('Example: 1004'); ?>" required>
                    </label>
                    <label><?= $returnsE('Full name'); ?>
                        <input type="text" name="customer_name" placeholder="<?= $returnsE('Your name'); ?>" required>
                    </label>
                </div>
                <div class="form-row">
                    <label><?= $returnsE('Email used on order'); ?>
                        <input type="email" name="email" placeholder="<?= $returnsE('you@example.com'); ?>" required>
                    </label>
                    <label>Phone
                        <input type="tel" name="phone" placeholder="+212 ...">
                    </label>
                </div>
                <div class="form-row">
                    <label><?= $returnsE('Request type'); ?>
                        <select name="request_type" required>
                            <option value=""><?= $returnsE('Choose...'); ?></option>
                            <option value="return"><?= $returnsE('Return'); ?></option>
                            <option value="refund"><?= $returnsE('Refund'); ?></option>
                            <option value="exchange"><?= $returnsE('Exchange'); ?></option>
                            <option value="warranty"><?= $returnsE('Warranty claim'); ?></option>
                            <option value="missing"><?= $returnsE('Missing item'); ?></option>
                            <option value="damaged"><?= $returnsE('Damaged on arrival'); ?></option>
                        </select>
                    </label>
                    <label><?= $returnsE('Preferred resolution'); ?>
                        <select name="preferred_resolution" required>
                            <option value=""><?= $returnsE('Choose...'); ?></option>
                            <option value="refund"><?= $returnsE('Refund'); ?></option>
                            <option value="replacement"><?= $returnsE('Replacement'); ?></option>
                            <option value="store_credit"><?= $returnsE('Store credit'); ?></option>
                        </select>
                    </label>
                </div>
                <label><?= $returnsE('Product concerned'); ?>
                    <input type="text" name="product_name" placeholder="<?= $returnsE('Example: NVIDIA RTX 4080 Super'); ?>" required>
                </label>
                <div class="form-row">
                    <label><?= $returnsE('Product condition'); ?>
                        <select name="product_condition" required>
                            <option value=""><?= $returnsE('Choose...'); ?></option>
                            <option value="sealed"><?= $returnsE('Sealed / unopened'); ?></option>
                            <option value="opened_unused"><?= $returnsE('Opened but unused'); ?></option>
                            <option value="used"><?= $returnsE('Used / installed'); ?></option>
                            <option value="defective"><?= $returnsE('Defective'); ?></option>
                            <option value="damaged_package"><?= $returnsE('Damaged packaging'); ?></option>
                            <option value="missing_item"><?= $returnsE('Missing item/accessory'); ?></option>
                        </select>
                    </label>
                    <label><?= $returnsE('Serial number'); ?>
                        <input type="text" name="serial_number" placeholder="<?= $returnsE('Optional, recommended for warranty'); ?>">
                    </label>
                </div>
                <label class="checkbox-line">
                    <input type="checkbox" name="package_opened" value="1">
                    <span><?= $returnsE('The retail package has been opened.'); ?></span>
                </label>
                <label><?= $returnsE('Product / Damage Photo (Required for damaged items)'); ?>
                    <input type="file" name="rma_image" accept="image/jpeg,image/png,image/webp">
                </label>
                <label><?= $returnsE('Describe the issue'); ?>
                    <textarea name="reason" rows="6" minlength="20" placeholder="<?= $returnsE('Tell us what happened, when you noticed it, and what resolution you expect.'); ?>" required></textarea>
                </label>
                <button class="btn btn-primary after-submit" type="submit">
                    <i class="fas fa-paper-plane"></i> <?= $returnsE('Submit Service Ticket'); ?>
                </button>
                <div class="after-form-result" id="afterSalesResult" role="status" aria-live="polite"></div>
            </form>
        </section>

        <section class="fine-print">
            <h2><?= $returnsE('Important Conditions'); ?></h2>
            <ul>
                <li><?= $returnsE('Data on storage devices should be backed up before any return, repair, or warranty intake.'); ?></li>
                <li><?= $returnsE('Physical damage, missing accessories, liquid damage, burned components, or modified firmware can affect eligibility.'); ?></li>
                <li><?= $returnsE('Refund approval depends on inspection result, payment status, and return completeness.'); ?></li>
                <li><?= $returnsE('Manufacturer warranty timelines vary by brand and product category.'); ?></li>
            </ul>
        </section>
    </main>

    <?php
require_once __DIR__ . '/includes/store-footer.php';
storeFooter();
?>

    <div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title">Sign In</p>
            <p class="role-modal-subtitle">Select your account type to continue to the login page.</p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon"><i class="fas fa-user"></i></span>
                <div><strong>Customer Account</strong><small>Track orders, wishlists &amp; purchases</small></div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon"><i class="fas fa-shield-alt"></i></span>
                <div><strong>Admin Portal</strong><small>Inventory, orders &amp; site management</small></div>
            </button>
            <div class="role-modal-divider">or</div>
            <button class="role-cancel" onclick="closeRoleModal()">Cancel</button>
        </div>
    </div>

    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <?= i18n_language_switcher_assets() ?>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/after-sales.js"></script>
    <script>
        function selectRole(role) {
            closeRoleModal();
            window.location.href = role === 'user' ? 'login.php' : 'adminlogin.php';
        }
        function closeRoleModal() {
            const modal = document.getElementById('roleModal');
            if (modal) modal.style.display = 'none';
        }
    </script>
</body>
</html>
