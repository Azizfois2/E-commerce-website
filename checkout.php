<?php
require_once 'config.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();

// Alias for shorter translation function calls
if (!function_exists('t')) {
    function t(string $key, array $params = [], ?string $fallback = null): string {
        return i18n_t($key, $params, $fallback);
    }
}

// Enforce login for checkout
if (!isset($_SESSION['client_id'])) {
    $isExpired = isset($_COOKIE['has_active_session']) ? '&session_expired=1' : '';
    header('Location: login.php?next=checkout.php' . $isExpired);
    exit();
}

$pdo = db();
$clientId = $_SESSION['client_id'];
$stmt = $pdo->prepare('SELECT * FROM Client WHERE id_client = ?');
$stmt->execute([$clientId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Split name into first and last if possible
$names = explode(' ', (string)$user['nom'], 2);
$firstName = $names[0] ?? '';
$lastName = $names[1] ?? '';
$email = (string)$user['email'];
$phone = (string)$user['telephone'];
$address = (string)$user['adresse'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('checkout.page_title') ?> - <?= t('common.site_name') ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
	<link rel="stylesheet" href="assets/css/light-mode-industrial.css">
	<script src="assets/js/page-transitions.js"></script>
    <script>
        window.__checkoutAuthenticated = true;
    </script>
    <!-- Stripe JavaScript Library -->
    <script src="https://js.stripe.com/v3/"></script>
    <?= i18n_preference_assets() ?>
</head>

<body>
    <!-- Header - Simplified for checkout -->
    <header class="header checkout-header">
        <div class="container">
            <div class="header-content">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                    <i class="fas fa-microchip"></i>
                    <span><?= t('common.site_name') ?></span>
                </a>
                <div class="checkout-steps">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text"><?= t('checkout.cart') ?></span>
                    </div>
                    <div class="step active" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-text"><?= t('checkout.checkout') ?></span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-text"><?= t('checkout.confirmation') ?></span>
                    </div>
                </div>
                <button class="theme-toggle" id="themeToggle" aria-label="<?= t('common.toggle_theme') ?>" style="margin-right: 1.5rem;">
                    <i class="fas fa-sun icon-sun"></i>
                    <i class="fas fa-moon icon-moon"></i>
                </button>
                <?= i18n_language_switcher('nav-translate', 'margin-right: 1.5rem;') ?>

                <div class="cart-wrapper" id="userNav">
                    <a href="<?= htmlspecialchars(i18n_url('account.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?= t('common.account') ?>">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
                <div class="secure-badge">
                    <i class="fas fa-lock"></i>
                    <span><?= t('checkout.secure_checkout') ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Checkout Page -->
    <section class="checkout-page">
        <div class="container">
            <div class="checkout-layout">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <!-- Guest / Login -->
                    <div class="checkout-section" id="authSection">
                        <h2><?= t('checkout.account') ?></h2>
                        <div class="auth-options">
                            <div class="auth-option active">
                                <i class="fas fa-user-check" style="color: var(--green);"></i>
                                <div style="display: flex; flex-direction: column; margin-left: 12px;">
                                    <span style="font-weight: 700; color: var(--text);"><?= str_replace('{name}', htmlspecialchars($user['nom']), t('checkout.logged_in_as')) ?></span>
                                    <span style="font-size: 0.8rem; color: var(--muted);"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-truck"></i> <?= t('checkout.shipping_information') ?></h2>
                        <form id="shippingForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName"><?= t('checkout.first_name') ?> *</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName"><?= t('checkout.last_name') ?> *</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email"><?= t('checkout.email_address') ?> *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone"><?= t('checkout.phone_number') ?> *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address"><?= t('checkout.street_address') ?> *</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address2"><?= t('checkout.apartment_suite_etc_optional') ?></label>
                                <input type="text" id="address2" name="address2">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city"><?= t('checkout.city') ?> *</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="state"><?= t('checkout.region') ?> *</label>
                                    <select id="state" name="state" required>
                                        <option value=""><?= t('checkout.select_region') ?></option>
                                        <option value="CS"><?= t('regions.casablanca_settat') ?></option>
                                        <option value="RK"><?= t('regions.rabat_sale_kenitra') ?></option>
                                        <option value="TT"><?= t('regions.tanger_tetouan_al_hoceima') ?></option>
                                        <option value="FM"><?= t('regions.fes_meknes') ?></option>
                                        <option value="MS"><?= t('regions.marrakech_safi') ?></option>
                                        <option value="SM"><?= t('regions.souss_massa') ?></option>
                                        <option value="OK"><?= t('regions.oriental') ?></option>
                                        <option value="BM"><?= t('regions.beni_mellal_khenifra') ?></option>
                                        <option value="DA"><?= t('regions.draa_tafilalet') ?></option>
                                        <option value="GL"><?= t('regions.guelmim_oued_noun') ?></option>
                                        <option value="LS"><?= t('regions.laayoune_sakia_el_hamra') ?></option>
                                        <option value="ED"><?= t('regions.dakhla_oued_ed_dahab') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="zip"><?= t('checkout.zip_postal_code') ?> *</label>
                                    <input type="text" id="zip" name="zip" required>
                                </div>
                                <div class="form-group">
                                    <label for="country"><?= t('checkout.country') ?> *</label>
                                    <select id="country" name="country" required>
                                        <option value="MA" selected><?= t('checkout.morocco') ?></option>
                                        <option value="FR"><?= t('checkout.france') ?></option>
                                        <option value="ES"><?= t('checkout.spain') ?></option>
                                        <option value="DE"><?= t('checkout.germany') ?></option>
                                        <option value="UK"><?= t('checkout.united_kingdom') ?></option>
                                        <option value="US"><?= t('checkout.united_states') ?></option>
                                        <option value="CA"><?= t('checkout.canada') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="saveInfo" name="saveInfo">
                                <label for="saveInfo"><?= t('checkout.save_this_information_for_next_time') ?></label>
                            </div>
                        </form>
                    </div>

                    <!-- Shipping Method -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-shipping-fast"></i> <?= t('checkout.shipping_method') ?></h2>
                        <div class="shipping-options">
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="standard" checked>
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name"><?= t('checkout.standard_shipping') ?></span>
                                        <span class="option-time"><?= t('checkout.business_days_5_7') ?></span>
                                    </div>
                                    <span class="option-price" id="standardShipping">100 DH</span>
                                </div>
                            </label>
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="express">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name"><?= t('checkout.express_shipping') ?></span>
                                        <span class="option-time"><?= t('checkout.business_days_2_3') ?></span>
                                    </div>
                                    <span class="option-price">200 DH</span>
                                </div>
                            </label>
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="overnight">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name"><?= t('checkout.overnight_shipping') ?></span>
                                        <span class="option-time"><?= t('checkout.business_day_1') ?></span>
                                    </div>
                                    <span class="option-price">400 DH</span>
                                </div>
                            </label>
                            <label class="shipping-option free-option">
                                <input type="radio" name="shipping" value="free">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name"><?= t('checkout.free_shipping') ?></span>
                                        <span class="option-time"><?= t('checkout.business_days_7_10') ?></span>
                                    </div>
                                    <span class="option-price free"><?= t('checkout.free') ?></span>
                                </div>
                            </label>
                            <label class="shipping-option free-option" id="pickupOptionLabel">
                                <input type="radio" name="shipping" value="pickup">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name"><?= t('checkout.store_pickup') ?></span>
                                        <span class="option-time"><?= t('checkout.available_today_at_select_locations') ?></span>
                                    </div>
                                    <span class="option-price free"><?= t('checkout.free') ?></span>
                                </div>
                            </label>
                        </div>
                        
                        <div id="pickupMapContainer" style="display:none; margin-top:20px; padding:24px; background:var(--page-bg-3); border:1px solid var(--border); border-radius:16px;">
                            <h3 style="margin-top:0; font-family:'Orbitron', sans-serif; font-size:1rem; letter-spacing:1px; color:var(--text);"><i class="fas fa-map-location-dot" style="color:var(--cyan);"></i> <?= t('checkout.select_pickup_location') ?></h3>
                            <p style="color:var(--muted); font-size:0.85rem; margin-bottom:20px; font-family:'Space Mono', monospace;"><?= t('checkout.interactive_terminal_grid') ?></p>
                            <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; align-items:start;">
                                <!-- Map with overlay nodes (same pattern as index.html) -->
                                <div class="pickup-map-wrapper" style="position:relative; border:1px solid rgba(0,245,212,0.15); border-radius:12px; background:transparent; aspect-ratio:800/795;">
                                    <?php include 'morocco-full-styled.svg'; ?>
                                    
                                    <!-- City Nodes (positioned like index.html) -->
                                    <div class="pickup-node" data-city="tangier" style="position:absolute; top:1%; left:74.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="rabat" style="position:absolute; top:11%; left:68.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="casablanca" style="position:absolute; top:14.5%; left:64%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="fes" style="position:absolute; top:10%; left:75.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="marrakech" style="position:absolute; top:27%; left:61%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="agadir" style="position:absolute; top:37%; left:53.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="oujda" style="position:absolute; top:8%; left:92%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="laayoune" style="position:absolute; top:57.5%; left:28.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="dakhla" style="position:absolute; top:80.5%; left:9.5%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                </div>
                                <!-- Details panel -->
                                <div class="pickup-details" id="pickupDetails" style="padding:20px; background:rgba(0,245,212,0.02); border:1px solid rgba(0,245,212,0.1); border-radius:12px; min-height:200px;">
                                    <div style="text-align:center; padding:40px 10px; color:var(--muted);">
                                        <i class="fas fa-map-pin" style="font-size:2rem; color:rgba(0,245,212,0.3); margin-bottom:12px; display:block;"></i>
                                        <p style="font-size:0.9rem; margin:0;"><?= t('checkout.click_city_node') ?></p>
                                    </div>
                                </div>
                            </div>
                            <style>
                                .pickup-node { cursor:pointer; z-index:2; transform: translate(-50%, -50%); }
                                .pickup-dot {
                                    display:block; width:12px; height:12px; border-radius:50%;
                                    background:var(--cyan, #00f5d4); border:2px solid var(--page-bg, #fff);
                                    box-shadow: 0 0 10px rgba(0,245,212,0.6);
                                    transition: transform 0.25s, box-shadow 0.25s, background 0.2s;
                                    animation: pickupPulse 2s ease-in-out infinite;
                                }
                                body.dark-mode .pickup-dot {
                                    border-color: #0f172a; /* Dark background to cut out the dot */
                                }
                                .pickup-node:hover .pickup-dot {
                                    transform:scale(1.5);
                                    box-shadow: 0 0 15px rgba(0,245,212,0.8);
                                }
                                .pickup-node.selected .pickup-dot {
                                    background:#fff; transform:scale(1.4);
                                    box-shadow: 0 0 15px rgba(0,245,212,1);
                                    animation:none;
                                }
                                @keyframes pickupPulse {
                                    0%, 100% { box-shadow: 0 0 6px rgba(0,245,212,0.4); transform: scale(1); }
                                    50% { box-shadow: 0 0 12px rgba(0,245,212,0.7); transform: scale(1.1); }
                                }
                                @media (max-width:768px) {
                                    #pickupMapContainer > div:nth-child(3) { grid-template-columns:1fr !important; }
                                }
                            </style>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-credit-card"></i> <?= t('checkout.payment_method') ?></h2>

                        <!-- Payment Method Selection -->
                        <div class="payment-method-selection">
                            <?php if (($user['moyen_paiement'] ?? '') === 'credit-card-saved'): ?>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="credit-card" checked>
                                <div class="payment-method-content">
                                    <i class="fas fa-credit-card"></i>
                                    <span><?= t('checkout.use_saved_card') ?></span>
                                </div>
                            </label>
                            <?php endif; ?>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="credit-card" <?php echo ($user['moyen_paiement'] ?? '') !== 'credit-card-saved' ? 'checked' : ''; ?>>
                                <div class="payment-method-content">
                                    <div class="payment-icons-group">
                                        <i class="fab fa-cc-visa"></i>
                                        <i class="fab fa-cc-mastercard"></i>
                                        <i class="fab fa-cc-amex"></i>
                                        <i class="fab fa-cc-discover"></i>
                                    </div>
                                    <span><?= t('checkout.credit_debit_card') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="paypal">
                                <div class="payment-method-content">
                                    <i class="fab fa-cc-paypal"></i>
                                    <span><?= t('checkout.paypal') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="bitcoin">
                                <div class="payment-method-content">
                                    <i class="fab fa-bitcoin"></i>
                                    <span><?= t('checkout.bitcoin_cryptocurrency') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="apple-pay">
                                <div class="payment-method-content">
                                    <i class="fab fa-apple-pay"></i>
                                    <span><?= t('checkout.apple_pay') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="google-pay">
                                <div class="payment-method-content">
                                    <i class="fab fa-google-pay"></i>
                                    <span><?= t('checkout.google_pay') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="nfc-biometric">
                                <div class="payment-method-content">
                                    <i class="fas fa-fingerprint"></i>
                                    <span><?= t('checkout.nfc_biometrics') ?></span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="cod">
                                <div class="payment-method-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?= t('checkout.cash_on_delivery') ?></span>
                                </div>
                            </label>
                        </div>

                        <!-- Credit Card Form -->
                        <div class="payment-form" id="creditCardForm">
                            <?php if (false): // Stripe disabled, fallback to gorgeous custom simulation ?>
                                <!-- Stripe Payment Element Unified Container -->
                                <div id="stripe-payment-element" style="margin-top: 15px; min-height: 150px; background: rgba(255,255,255,0.015); border: 1px solid var(--border); border-radius: var(--r-md); padding: 20px;">
                                    <div id="stripe-loading" class="text-center" style="padding: 30px; text-align: center; color: var(--text);">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--cyan); margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;"></i>
                                        <span style="font-size: 0.85rem; font-family: var(--font-body);"><?= t('checkout.initializing_secure_checkout') ?></span>
                                    </div>
                                    <div id="payment-element"></div>
                                </div>
                            <?php else: ?>
                                <div class="card-preview">
                                    <div class="card-front">
                                        <div class="card-chip"></div>
                                        <div class="card-number" id="previewCardNumber">•••• •••• •••• ••••</div>
                                        <div class="card-details">
                                            <div class="card-holder">
                                                <span class="label"><?= t('checkout.card_holder') ?></span>
                                                <span class="value" id="previewCardHolder"><?= t('checkout.your_name') ?></span>
                                            </div>
                                            <div class="card-expiry">
                                                <span class="label"><?= t('checkout.expires') ?></span>
                                                <span class="value" id="previewCardExpiry">MM/YY</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cardNumber"><?= t('checkout.card_number') ?> *</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="cardNumber" name="cardNumber"
                                            placeholder="<?= t('checkout.card_number_placeholder') ?>" maxlength="19" required>
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cardHolder"><?= t('checkout.cardholder_name') ?> *</label>
                                    <input type="text" id="cardHolder" name="cardHolder" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" placeholder="<?= t('checkout.cardholder_placeholder') ?>" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiryDate"><?= t('checkout.expiry_date') ?> *</label>
                                        <input type="text" id="expiryDate" name="expiryDate" placeholder="<?= t('checkout.expiry_placeholder') ?>"
                                            maxlength="5" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv"><?= t('checkout.cvv') ?> *</label>
                                        <div class="input-with-icon">
                                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                                            <i class="fas fa-question-circle" title="<?= t('checkout.cvv_tooltip') ?>"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="saveCard" name="saveCard">
                                    <label for="saveCard"><?= t('checkout.save_card_for_future_purchases') ?></label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- PayPal Form -->
                        <div class="payment-form hidden" id="paypalForm">
                            <div class="external-payment-info">
                                <i class="fab fa-paypal"></i>
                                <h3><?= t('checkout.pay_with_paypal') ?></h3>
                                <p><?= t('checkout.complete_purchase_paypal') ?></p>
                                <div id="paypal-button-container" style="min-height:55px; margin-top:8px;"></div>
                                <p class="crypto-note" style="margin-top:12px;"><?= t('checkout.redirected_to_paypal') ?></p>
                            </div>
                        </div>

                        <!-- COD Form -->
                        <div class="payment-form hidden" id="codForm">
                            <div class="external-payment-info cod-info">
                                <i class="fas fa-money-bill-wave"></i>
                                <h3><?= t('checkout.cash_on_delivery_title') ?></h3>
                                <p><?= t('checkout.cod_description') ?></p>
                                <div class="cod-features">
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span><?= t('checkout.no_card_required') ?></span></div>
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span><?= t('checkout.inspect_before_pay') ?></span></div>
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span><?= t('checkout.available_across_morocco') ?></span></div>
                                </div>
                                <div class="cod-fee-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <span><?= t('checkout.cod_handling_fee') ?></span>
                                </div>
                                <div id="codDepositNotice" style="display:none; margin-top:20px; padding:16px; background:rgba(255,160,0,0.1); border:1px solid rgba(255,160,0,0.3); border-radius:12px;">
                                    <h4 style="margin-top:0; color:#ffb300; display:flex; align-items:center; gap:8px;">
                                        <i class="fas fa-shield-halved"></i> <?= t('checkout.security_deposit_required') ?>
                                    </h4>
                                    <p style="font-size:0.9rem; margin-bottom:12px;">
                                        <?= t('checkout.cod_deposit_notice') ?>
                                    </p>
                                    <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.85rem; cursor:pointer;">
                                        <input type="checkbox" id="codDepositAgree" style="margin-top:2px;">
                                        <span><?= t('checkout.cod_deposit_agree') ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bitcoin Form -->
                        <div class="payment-form hidden" id="bitcoinForm">
                            <div class="external-payment-info">
                                <i class="fab fa-bitcoin"></i>
                                <h3><?= t('checkout.pay_with_cryptocurrency') ?></h3>
                                <p><?= t('checkout.crypto_description') ?></p>
                                <div class="crypto-options">
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="btc" checked>
                                        <span><i class="fab fa-bitcoin"></i> <?= t('checkout.bitcoin') ?></span>
                                    </label>
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="eth">
                                        <span><i class="fab fa-ethereum"></i> <?= t('checkout.ethereum') ?></span>
                                    </label>
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="usdt">
                                        <span>₮ USDT</span>
                                    </label>
                                </div>
                                <p class="crypto-note"><?= t('checkout.crypto_note') ?></p>
                            </div>
                        </div>

                        <!-- Apple Pay Form -->
                        <div class="payment-form hidden" id="applePayForm">
                            <div class="external-payment-info">
                                <i class="fab fa-apple-pay"></i>
                                <h3><?= t('checkout.apple_pay') ?></h3>
                                <p><?= t('checkout.apple_pay_description') ?></p>
                                <button type="button" class="btn btn-dark apple-pay-btn" id="applePayBtn">
                                    <i class="fab fa-apple"></i> <?= t('checkout.pay_with_apple_pay') ?>
                                </button>
                            </div>
                        </div>

                        <!-- Google Pay Form -->
                        <div class="payment-form hidden" id="googlePayForm">
                            <div class="external-payment-info">
                                <i class="fab fa-google-pay"></i>
                                <h3><?= t('checkout.google_pay') ?></h3>
                                <p><?= t('checkout.google_pay_description') ?></p>
                                <button type="button" class="btn btn-dark google-pay-btn" id="googlePayBtn">
                                    <i class="fab fa-google"></i> <?= t('checkout.pay_with_google_pay') ?>
                                </button>
                            </div>
                        </div>

                        <!-- NFC & Biometric Form -->
                        <div class="payment-form hidden" id="nfcBiometricForm">
                            <div class="nfc-biometric-container">
                                <div class="biometric-laser" id="biometricLaser"></div>
                                <div class="nfc-biometric-header">
                                    <h3><i class="fas fa-fingerprint" style="color: var(--cyan); margin-right: 5px;"></i> <?= t('checkout.contactless_biometric_terminal') ?></h3>
                                    <p><?= t('checkout.contactless_description') ?></p>
                                </div>
                                <div class="nfc-scanners">
                                    <div class="scanner-container">
                                        <button type="button" class="biometric-scanner-btn" id="biometricScanBtn" title="<?= t('checkout.scan_fingerprint') ?>">
                                            <i class="fas fa-fingerprint"></i>
                                        </button>
                                        <span class="scanner-label"><?= t('checkout.biometric_touchid') ?></span>
                                    </div>
                                    <div class="scanner-container">
                                        <button type="button" class="nfc-radar-btn" id="nfcScanBtn" title="<?= t('checkout.scan_nfc_device') ?>">
                                            <i class="fas fa-rss"></i>
                                        </button>
                                        <span class="scanner-label"><?= t('checkout.contactless_nfc') ?></span>
                                    </div>
                                </div>
                                <div class="nfc-status-readout" id="nfcStatusReadout">
                                    <?= t('checkout.status_ready_handshake') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-file-invoice"></i> <?= t('checkout.billing_address') ?></h2>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="sameAsShipping" name="sameAsShipping" checked>
                            <label for="sameAsShipping"><?= t('checkout.same_as_shipping_address') ?></label>
                        </div>
                        <div class="billing-address-form hidden" id="billingAddressForm">
                            <!-- Same fields as shipping but for billing -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingAddress"><?= t('checkout.street_address') ?> *</label>
                                    <input type="text" id="billingAddress" name="billingAddress">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingCity"><?= t('checkout.city') ?> *</label>
                                    <input type="text" id="billingCity" name="billingCity">
                                </div>
                                <div class="form-group">
                                    <label for="billingState"><?= t('checkout.region') ?> *</label>
                                    <select id="billingState" name="billingState">
                                        <option value=""><?= t('checkout.select_region') ?></option>
                                        <option value="CS"><?= t('regions.casablanca_settat') ?></option>
                                        <option value="RK"><?= t('regions.rabat_sale_kenitra') ?></option>
                                        <option value="TT"><?= t('regions.tanger_tetouan_al_hoceima') ?></option>
                                        <option value="FM"><?= t('regions.fes_meknes') ?></option>
                                        <option value="MS"><?= t('regions.marrakech_safi') ?></option>
                                        <option value="SM"><?= t('regions.souss_massa') ?></option>
                                        <option value="OK"><?= t('regions.oriental') ?></option>
                                        <option value="BM"><?= t('regions.beni_mellal_khenifra') ?></option>
                                        <option value="DA"><?= t('regions.draa_tafilalet') ?></option>
                                        <option value="GL"><?= t('regions.guelmim_oued_noun') ?></option>
                                        <option value="LS"><?= t('regions.laayoune_sakia_el_hamra') ?></option>
                                        <option value="ED"><?= t('regions.dakhla_oued_ed_dahab') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingZip"><?= t('checkout.zip_postal_code') ?> *</label>
                                    <input type="text" id="billingZip" name="billingZip">
                                </div>
                                <div class="form-group">
                                    <label for="billingCountry"><?= t('checkout.country') ?> *</label>
                                    <select id="billingCountry" name="billingCountry">
                                        <option value="MA" selected><?= t('checkout.morocco') ?></option>
                                        <option value="FR"><?= t('checkout.france') ?></option>
                                        <option value="ES"><?= t('checkout.spain') ?></option>
                                        <option value="DE"><?= t('checkout.germany') ?></option>
                                        <option value="UK"><?= t('checkout.united_kingdom') ?></option>
                                        <option value="US"><?= t('checkout.united_states') ?></option>
                                        <option value="CA"><?= t('checkout.canada') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-sticky-note"></i> <?= t('checkout.order_notes_optional') ?></h2>
                        <div class="form-group">
                            <textarea id="orderNotes" name="orderNotes" rows="3"
                                placeholder="<?= t('checkout.special_instructions_placeholder') ?>"></textarea>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="checkout-section terms-section">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="termsAgree" name="termsAgree" required>
                            <label for="termsAgree"><?= t('checkout.terms_agree') ?> *</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="newsletterSignup" name="newsletterSignup">
                            <label for="newsletterSignup"><?= t('checkout.subscribe_newsletter') ?></label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="order-summary-sidebar">
                    <div class="order-summary-card">
                        <h3><?= t('checkout.order_summary') ?></h3>

                        <div class="promo-redemption" style="padding: 16px; background: rgba(0,245,212,0.04); border: 1px solid rgba(0,245,212,0.1); border-radius: 12px; margin-bottom: 20px;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; color:var(--cyan); font-weight:700; font-size:0.85rem;">
                                <i class="fas fa-ticket"></i> <span><?= t('checkout.promo_code') ?></span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="checkoutPromoCode" placeholder="WELCOME10" style="flex:1; padding:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:6px; font-size:0.85rem; color:var(--text); text-transform:uppercase;">
                                <button type="button" id="applyCheckoutPromoBtn" class="button button-primary" style="padding:0 12px; font-size:0.75rem; height:36px; background:var(--cyan); border:none; border-radius:6px; cursor:pointer; font-weight:700;"><?= t('checkout.apply') ?></button>
                            </div>
                            <div id="checkoutPromoMessage" style="margin-top:8px; font-size:0.75rem;"></div>
                        </div>
                        
                        <!-- Phase 3: Loyalty Rewards -->
                        <div class="loyalty-redemption" id="loyaltySection" style="display: none; padding: 16px; background: rgba(0,245,212,0.05); border: 1px solid rgba(0,245,212,0.1); border-radius: 12px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: var(--cyan); font-weight: 700; font-size: 0.85rem;">
                                <i class="fas fa-crown"></i> <span><?= t('checkout.loyalty_rewards') ?></span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 12px;">
                                <?= t('checkout.you_have_points') ?>
                                <br><small><?= t('checkout.points_conversion') ?></small>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" id="redeemPointsInput" placeholder="<?= t('checkout.points_to_use') ?>" style="flex: 1; padding: 8px; background: var(--page-bg); border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: var(--text);">
                                <button type="button" id="applyPointsBtn" class="button button-primary" style="padding: 0 12px; font-size: 0.75rem; height: 36px; background: var(--cyan); border: none; border-radius: 6px; cursor: pointer; font-weight: 700;"><?= t('checkout.apply') ?></button>
                            </div>
                            <div id="pointsMessage" style="margin-top: 8px; font-size: 0.75rem;"></div>
                        </div>

                        <div class="order-items" id="orderItems">
                            <!-- Order items will be loaded via JavaScript -->
                        </div>
                        <div class="checkout-upsells" id="checkoutUpsells" style="display: none;">
                            <div class="checkout-upsells-header">
                                <span><i class="fas fa-toolbox"></i> <?= t('checkout.oops_dont_forget') ?></span>
                                <small><?= t('checkout.smart_accessories') ?></small>
                            </div>
                            <div class="checkout-upsell-list" id="checkoutUpsellList"></div>
                        </div>
                        <div class="order-totals">
                            <div class="total-row">
                                <span><?= t('checkout.subtotal') ?></span>
                                <span id="orderSubtotal">0.00 DH</span>
                            </div>
                            <div class="total-row">
                                <span><?= t('checkout.shipping') ?></span>
                                <span id="orderShipping">100 DH</span>
                            </div>
                            <div class="total-row">
                                <span><?= t('checkout.tax') ?></span>
                                <span id="orderTax">0.00 DH</span>
                            </div>
                            <div class="total-row discount" id="orderDiscountRow" style="display: none;">
                                <span><?= t('checkout.discount') ?></span>
                                <span id="orderDiscount">-0.00 DH</span>
                            </div>
                            <div class="total-row grand-total">
                                <span><?= t('checkout.total') ?></span>
                                <span id="orderTotal">0.00 DH</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block place-order-btn" id="placeOrderBtn"
                            form="shippingForm">
                            <i class="fas fa-lock"></i> <?= t('checkout.place_order') ?>
                        </button>

                        <div class="checkout-badges">
                            <div class="badge">
                                <i class="fas fa-shield-alt"></i>
                                <span><?= t('checkout.ssl_secured') ?></span>
                            </div>
                            <div class="badge">
                                <i class="fas fa-undo"></i>
                                <span><?= t('checkout.30_day_returns') ?></span>
                            </div>
                            <div class="badge">
                                <i class="fas fa-check-circle"></i>
                                <span><?= t('checkout.verified_merchant') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Need Help -->
                    <div class="need-help">
                        <h4><?= t('checkout.need_help') ?></h4>
                        <div class="help-options">
                            <a href="tel:+15551234567"><i class="fas fa-phone"></i>+212 618821949</a>
                            <a href="mailto:support@techgear.com"><i class="fas fa-envelope"></i>
                                support@marocpc.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Processing Overlay -->
    <div class="payment-processing-overlay" id="paymentProcessing">
        <div class="processing-backdrop"></div>
        <div class="processing-card">
            <div class="processing-icon-wrap">
                <div class="processing-spinner"></div>
                <i class="fas fa-credit-card processing-card-icon"></i>
            </div>
            <h3 class="processing-title" id="processingTitle"><?= t('checkout.processing_payment') ?></h3>
            <p class="processing-subtitle" id="processingSubtitle"><?= t('checkout.please_do_not_close') ?></p>
            <div class="processing-steps">
                <div class="processing-step active" id="stepVerify">
                    <div class="step-dot"></div>
                    <span><?= t('checkout.verifying_card_details') ?></span>
                </div>
                <div class="processing-step" id="stepAuth">
                    <div class="step-dot"></div>
                    <span><?= t('checkout.authorizing_payment') ?></span>
                </div>
                <div class="processing-step" id="stepConfirm">
                    <div class="step-dot"></div>
                    <span><?= t('checkout.confirming_transaction') ?></span>
                </div>
            </div>
            <div class="processing-amount" id="processingAmount"></div>
        </div>
    </div>

    <!-- Crypto Payment Modal -->
    <div class="modal" id="cryptoModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.2rem; margin: 0;"><i class="fab fa-bitcoin" style="color: var(--amber); margin-right: 8px;"></i> <?= t('checkout.cryptocurrency_payment') ?></h2>
                <button type="button" class="close-modal" id="closeCryptoModal" style="background: none; border: none; color: var(--text); font-size: 1.2rem; cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <p><?= t('checkout.send_exactly') ?></p>
                
                <div style="margin: 20px auto; padding: 16px; background: white; width: fit-content; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <img id="cryptoQrCode" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh" alt="<?= t('checkout.qr_code') ?>" style="display: block;" />
                </div>
                
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label><?= t('checkout.wallet_address') ?></label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="cryptoAddress" value="bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh" readonly style="font-family: var(--font-mono); font-size: 0.85rem;" />
                        <button type="button" class="btn btn-outline" id="copyCryptoBtn" style="padding: 0 16px; min-width: unset;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                
                <div style="margin-top: 16px; margin-bottom: 24px; font-size: 0.85rem; color: var(--text-soft); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-circle-notch fa-spin" style="color: var(--cyan);"></i> <?= t('checkout.waiting_for_confirmation') ?>
                </div>
            </div>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button type="button" class="btn btn-primary" id="confirmCryptoPaymentBtn" style="width: 100%;">
                    <?= t('checkout.i_have_sent_payment') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-overlay"></div>
        <div class="modal-content confirmation-modal">
            <div class="confirmation-content">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2><?= t('checkout.order_confirmed') ?></h2>
                <p id="confirmationMessage"><?= t('checkout.thank_you_purchase') ?></p>
                <div class="order-info">
                    <div class="info-row">
                        <span><?= t('checkout.order_number') ?></span>
                        <strong id="orderNumber">#000000</strong>
                    </div>
                    <div class="info-row">
                        <span><?= t('checkout.transaction_id') ?></span>
                        <strong id="transactionId">—</strong>
                    </div>
                    <div class="info-row">
                        <span><?= t('checkout.payment_method_label') ?></span>
                        <strong id="confirmPaymentMethod">—</strong>
                    </div>
                    <div class="info-row">
                        <span><?= t('checkout.amount_charged') ?></span>
                        <strong id="confirmAmount">—</strong>
                    </div>
                    <div class="info-row" id="pickupCodeRow" style="display:none;">
                        <span><?= t('checkout.pickup_code') ?></span>
                        <strong id="confirmPickupCode">—</strong>
                    </div>
                    <div class="info-row">
                        <span><?= t('checkout.confirmation_email') ?></span>
                        <span id="confirmEmail"><?= t('checkout.sent_to_your_email') ?></span>
                    </div>
                </div>
                <div class="confirmation-actions">
                    <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?= t('checkout.continue_shopping') ?></a>
                    <button class="btn btn-outline" id="downloadPickupTicketBtn" type="button" style="display:none;">
                        <i class="fas fa-file-arrow-down"></i> <?= t('checkout.download_pickup_ticket') ?>
                    </button>
                    <button class="btn btn-outline" id="trackOrderBtn"><?= t('checkout.track_order') ?></button>
                    <button class="btn btn-outline" id="closeConfirmationBtn" type="button"><?= t('checkout.close') ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer - Simplified -->
    <footer class="footer checkout-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 <?= t('common.site_name') ?>. <?= t('checkout.all_rights_reserved') ?></p>
                <div class="footer-links">
                    <a href="privacy-policy.php"><?= t('checkout.privacy_policy') ?></a>
                    <a href="terms-of-service.php"><?= t('checkout.terms_and_conditions') ?></a>
                    <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank">Facebook</a>
                    <a href="https://x.com/Maroc_PC_PHP" target="_blank">X (Twitter)</a>
                    <a href="https://www.instagram.com/marocpc57" target="_blank">Instagram</a>
                    <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank">YouTube</a>
                    <a href="index.html#contact"><?= t('checkout.contact_us') ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"><?= t('checkout.item_added_to_cart') ?></span>
    </div>


    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars(envString('PAYPAL_CLIENT_ID', 'sb')); ?>&currency=USD&intent=capture&disable-funding=credit,card" data-sdk-integration-source="button-factory"></script>

    <script>
        window.STRIPE_PUBLISHABLE_KEY = '';
    </script>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/currency.js"></script>
    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <?= i18n_language_switcher_assets() ?>
    <script>
        window.__i18n = window.__i18n || {};
        window.__i18n.checkout = {
            cart_empty: <?= json_encode(t('checkout.cart_empty') !== 'checkout.cart_empty' ? t('checkout.cart_empty') : 'Your cart is empty.') ?>,
            processing_payment: <?= json_encode(t('checkout.processing_payment')) ?>,
            please_do_not_close: <?= json_encode(t('checkout.please_do_not_close')) ?>,
            payment_failed: <?= json_encode(t('checkout.payment_failed') !== 'checkout.payment_failed' ? t('checkout.payment_failed') : 'Payment Failed') ?>,
            payment_approved: <?= json_encode(t('checkout.payment_approved') !== 'checkout.payment_approved' ? t('checkout.payment_approved') : 'Payment Approved!') ?>,
            finalizing_order: <?= json_encode(t('checkout.finalizing_order') !== 'checkout.finalizing_order' ? t('checkout.finalizing_order') : 'Finalizing your order...') ?>,
            invalid_promo: <?= json_encode(t('checkout.invalid_promo') !== 'checkout.invalid_promo' ? t('checkout.invalid_promo') : 'Invalid promo code') ?>,
            applied: <?= json_encode(t('checkout.applied') !== 'checkout.applied' ? t('checkout.applied') : 'Applied:') ?>,
            enter_promo: <?= json_encode(t('checkout.enter_promo') !== 'checkout.enter_promo' ? t('checkout.enter_promo') : 'Enter a promo code.') ?>,
            invalid_card: <?= json_encode(t('checkout.invalid_card') !== 'checkout.invalid_card' ? t('checkout.invalid_card') : 'Invalid credit card number. Please check your details.') ?>,
            invalid_cvv: <?= json_encode(t('checkout.invalid_cvv') !== 'checkout.invalid_cvv' ? t('checkout.invalid_cvv') : 'Invalid CVV.') ?>,
            invalid_expiry: <?= json_encode(t('checkout.invalid_expiry') !== 'checkout.invalid_expiry' ? t('checkout.invalid_expiry') : 'Invalid expiry date. Use MM/YY format.') ?>,
            card_expired: <?= json_encode(t('checkout.card_expired') !== 'checkout.card_expired' ? t('checkout.card_expired') : 'Credit card has expired.') ?>,
            present_id_pickup: <?= json_encode(t('checkout.present_id_pickup') !== 'checkout.present_id_pickup' ? t('checkout.present_id_pickup') : 'Present your order confirmation code at the counter. Valid ID required.') ?>,
            select_this_store: <?= json_encode(t('checkout.select_this_store') !== 'checkout.select_this_store' ? t('checkout.select_this_store') : 'Select This Store') ?>,
            selected: <?= json_encode(t('checkout.selected') !== 'checkout.selected' ? t('checkout.selected') : 'Selected') ?>,
            method_credit_card: <?= json_encode(t('checkout.credit_debit_card')) ?>,
            method_paypal: <?= json_encode(t('checkout.paypal')) ?>,
            method_bitcoin: <?= json_encode(t('checkout.bitcoin_cryptocurrency')) ?>,
            method_apple_pay: <?= json_encode(t('checkout.apple_pay')) ?>,
            method_google_pay: <?= json_encode(t('checkout.google_pay')) ?>,
            method_nfc: <?= json_encode(t('checkout.nfc_biometrics')) ?>,
            method_cod: <?= json_encode(t('checkout.cash_on_delivery')) ?>
        };
    </script>
    <script src="assets/js/checkout.js?v=<?= urlencode((string) filemtime(__DIR__ . '/assets/js/checkout.js')) ?>" defer></script>
    
    <script>
        // Plant footprint for session expiration detection
        localStorage.setItem('has_active_session', '1');
    </script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>

