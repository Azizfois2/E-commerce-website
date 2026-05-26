<?php
require_once 'config.php';

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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Maroc PC</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
	<link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <!-- Stripe JavaScript Library -->
    <script src="https://js.stripe.com/v3/"></script>
</head>

<body>
    <!-- Header - Simplified for checkout -->
    <header class="header checkout-header">
        <div class="container">
            <div class="header-content">
                <a href="index.html" class="logo">
                    <i class="fas fa-microchip"></i>
                    <span>Maroc PC</span>
                </a>
                <div class="checkout-steps">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text">Cart</span>
                    </div>
                    <div class="step active" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-text">Checkout</span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-text">Confirmation</span>
                    </div>
                </div>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" style="margin-right: 1.5rem;">
                    <i class="fas fa-sun icon-sun"></i>
                    <i class="fas fa-moon icon-moon"></i>
                </button>
                <div id="google_translate_element" class="nav-translate" style="margin-right: 1.5rem;"></div>

                <div class="cart-wrapper" id="userNav">
                    <a href="login.html" class="cart-icon" aria-label="Account">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
                <div class="secure-badge">
                    <i class="fas fa-lock"></i>
                    <span>Secure Checkout</span>
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
                        <h2>Account</h2>
                        <div class="auth-options">
                            <div class="auth-option active">
                                <i class="fas fa-user-check" style="color: var(--green);"></i>
                                <div style="display: flex; flex-direction: column; margin-left: 12px;">
                                    <span style="font-weight: 700; color: var(--text);">Logged in as <?php echo htmlspecialchars($user['nom']); ?></span>
                                    <span style="font-size: 0.8rem; color: var(--muted);"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-truck"></i> Shipping Information</h2>
                        <form id="shippingForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name *</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address2">Apartment, Suite, etc. (Optional)</label>
                                <input type="text" id="address2" name="address2">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="state">Region *</label>
                                    <select id="state" name="state" required>
                                        <option value="">Select Region</option>
                                        <option value="CS">Casablanca-Settat</option>
                                        <option value="RK">Rabat-Salé-Kénitra</option>
                                        <option value="TT">Tanger-Tétouan-Al Hoceïma</option>
                                        <option value="FM">Fès-Meknès</option>
                                        <option value="MS">Marrakech-Safi</option>
                                        <option value="SM">Souss-Massa</option>
                                        <option value="OK">Oriental</option>
                                        <option value="BM">Béni Mellal-Khénifra</option>
                                        <option value="DA">Drâa-Tafilalet</option>
                                        <option value="GL">Guelmim-Oued Noun</option>
                                        <option value="LS">Laâyoune-Sakia El Hamra</option>
                                        <option value="ED">Dakhla-Oued Ed-Dahab</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="zip">ZIP/Postal Code *</label>
                                    <input type="text" id="zip" name="zip" required>
                                </div>
                                <div class="form-group">
                                    <label for="country">Country *</label>
                                    <select id="country" name="country" required>
                                        <option value="MA" selected>Morocco</option>
                                        <option value="FR">France</option>
                                        <option value="ES">Spain</option>
                                        <option value="DE">Germany</option>
                                        <option value="UK">United Kingdom</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="saveInfo" name="saveInfo">
                                <label for="saveInfo">Save this information for next time</label>
                            </div>
                        </form>
                    </div>

                    <!-- Shipping Method -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-shipping-fast"></i> Shipping Method</h2>
                        <div class="shipping-options">
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="standard" checked>
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name">Standard Shipping</span>
                                        <span class="option-time">5-7 Business Days</span>
                                    </div>
                                    <span class="option-price" id="standardShipping">100 MAD</span>
                                </div>
                            </label>
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="express">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name">Express Shipping</span>
                                        <span class="option-time">2-3 Business Days</span>
                                    </div>
                                    <span class="option-price">200 MAD</span>
                                </div>
                            </label>
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="overnight">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name">Overnight Shipping</span>
                                        <span class="option-time">1 Business Day</span>
                                    </div>
                                    <span class="option-price">400 MAD</span>
                                </div>
                            </label>
                            <label class="shipping-option free-option">
                                <input type="radio" name="shipping" value="free">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name">Free Shipping</span>
                                        <span class="option-time">7-10 Business Days</span>
                                    </div>
                                    <span class="option-price free">FREE</span>
                                </div>
                            </label>
                            <label class="shipping-option free-option" id="pickupOptionLabel">
                                <input type="radio" name="shipping" value="pickup">
                                <div class="option-content">
                                    <div class="option-info">
                                        <span class="option-name">Store Pickup</span>
                                        <span class="option-time">Available today at select locations</span>
                                    </div>
                                    <span class="option-price free">FREE</span>
                                </div>
                            </label>
                        </div>
                        
                        <div id="pickupMapContainer" style="display:none; margin-top:20px; padding:24px; background:var(--page-bg-3); border:1px solid var(--border); border-radius:16px;">
                            <h3 style="margin-top:0; font-family:'Orbitron', sans-serif; font-size:1rem; letter-spacing:1px; color:var(--text);"><i class="fas fa-map-location-dot" style="color:var(--cyan);"></i> Select a Pickup Location</h3>
                            <p style="color:var(--muted); font-size:0.85rem; margin-bottom:20px; font-family:'Space Mono', monospace;">Interactive terminal grid — click a city node to select your store</p>
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
                                    <div class="pickup-node" data-city="laayoune" style="position:absolute; top:62%; left:38%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                    <div class="pickup-node" data-city="dakhla" style="position:absolute; top:82%; left:30%;">
                                        <span class="pickup-dot"></span>
                                    </div>
                                </div>
                                <!-- Details panel -->
                                <div class="pickup-details" id="pickupDetails" style="padding:20px; background:rgba(0,245,212,0.02); border:1px solid rgba(0,245,212,0.1); border-radius:12px; min-height:200px;">
                                    <div style="text-align:center; padding:40px 10px; color:var(--muted);">
                                        <i class="fas fa-map-pin" style="font-size:2rem; color:rgba(0,245,212,0.3); margin-bottom:12px; display:block;"></i>
                                        <p style="font-size:0.9rem; margin:0;">Click a city node on the map to view store details.</p>
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
                        <h2><i class="fas fa-credit-card"></i> Payment Method</h2>

                        <!-- Payment Method Selection -->
                        <div class="payment-method-selection">
                            <?php if (($user['moyen_paiement'] ?? '') === 'credit-card-saved'): ?>
                            <label class="payment-method-option active">
                                <input type="radio" name="paymentMethod" value="credit-card" checked>
                                <div class="payment-method-content">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Use Saved Card (•••• 4242)</span>
                                </div>
                            </label>
                            <?php endif; ?>
                            <label class="payment-method-option <?php echo ($user['moyen_paiement'] ?? '') !== 'credit-card-saved' ? 'active' : ''; ?>">
                                <input type="radio" name="paymentMethod" value="credit-card" <?php echo ($user['moyen_paiement'] ?? '') !== 'credit-card-saved' ? 'checked' : ''; ?>>
                                <div class="payment-method-content">
                                    <div class="payment-icons-group">
                                        <i class="fab fa-cc-visa"></i>
                                        <i class="fab fa-cc-mastercard"></i>
                                        <i class="fab fa-cc-amex"></i>
                                        <i class="fab fa-cc-discover"></i>
                                    </div>
                                    <span>Credit / Debit Card</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="paypal">
                                <div class="payment-method-content">
                                    <i class="fab fa-cc-paypal"></i>
                                    <span>PayPal</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="bitcoin">
                                <div class="payment-method-content">
                                    <i class="fab fa-bitcoin"></i>
                                    <span>Bitcoin / Cryptocurrency</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="apple-pay">
                                <div class="payment-method-content">
                                    <i class="fab fa-apple-pay"></i>
                                    <span>Apple Pay</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="google-pay">
                                <div class="payment-method-content">
                                    <i class="fab fa-google-pay"></i>
                                    <span>Google Pay</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="nfc-biometric">
                                <div class="payment-method-content">
                                    <i class="fas fa-fingerprint"></i>
                                    <span>NFC & Biometrics</span>
                                </div>
                            </label>
                            <label class="payment-method-option">
                                <input type="radio" name="paymentMethod" value="cod">
                                <div class="payment-method-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Cash on Delivery</span>
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
                                        <span style="font-size: 0.85rem; font-family: var(--font-body);">Initializing secure checkout window...</span>
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
                                                <span class="label">Card Holder</span>
                                                <span class="value" id="previewCardHolder">YOUR NAME</span>
                                            </div>
                                            <div class="card-expiry">
                                                <span class="label">Expires</span>
                                                <span class="value" id="previewCardExpiry">MM/YY</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cardNumber">Card Number *</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="cardNumber" name="cardNumber"
                                            placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cardHolder">Cardholder Name *</label>
                                    <input type="text" id="cardHolder" name="cardHolder" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" placeholder="John Doe" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiryDate">Expiry Date *</label>
                                        <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY"
                                            maxlength="5" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv">CVV *</label>
                                        <div class="input-with-icon">
                                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                                            <i class="fas fa-question-circle" title="3-4 digit code on back of card"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" id="saveCard" name="saveCard">
                                    <label for="saveCard">Save card for future purchases</label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- PayPal Form -->
                        <div class="payment-form hidden" id="paypalForm">
                            <div class="external-payment-info">
                                <i class="fab fa-paypal"></i>
                                <h3>Pay with PayPal</h3>
                                <p>Complete your purchase securely through PayPal. Click the button below to proceed.</p>
                                <div id="paypal-button-container" style="min-height:55px; margin-top:8px;"></div>
                                <p class="crypto-note" style="margin-top:12px;">You will be redirected to PayPal to authorize payment, then returned here.</p>
                            </div>
                        </div>

                        <!-- COD Form -->
                        <div class="payment-form hidden" id="codForm">
                            <div class="external-payment-info cod-info">
                                <i class="fas fa-money-bill-wave"></i>
                                <h3>Cash on Delivery</h3>
                                <p>Pay in cash when your order is delivered to your door. No online payment needed.</p>
                                <div class="cod-features">
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span>No card or online account required</span></div>
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span>Inspect before you pay</span></div>
                                    <div class="cod-feature"><i class="fas fa-check-circle"></i><span>Available across Morocco</span></div>
                                </div>
                                <div class="cod-fee-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <span>A handling fee of <strong>30 MAD</strong> applies for COD orders</span>
                                </div>
                                <div id="codDepositNotice" style="display:none; margin-top:20px; padding:16px; background:rgba(255,160,0,0.1); border:1px solid rgba(255,160,0,0.3); border-radius:12px;">
                                    <h4 style="margin-top:0; color:#ffb300; display:flex; align-items:center; gap:8px;">
                                        <i class="fas fa-shield-halved"></i> Security Deposit Required
                                    </h4>
                                    <p style="font-size:0.9rem; margin-bottom:12px;">
                                        Because your order exceeds <strong>8,000 MAD</strong>, a fully refundable security deposit of <strong>200 MAD</strong> is required before shipping via Wafacash or CIH Bank to prevent fake orders. Our agent will contact you with payment details.
                                    </p>
                                    <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.85rem; cursor:pointer;">
                                        <input type="checkbox" id="codDepositAgree" style="margin-top:2px;">
                                        <span>I understand and agree to pay the 200 MAD security deposit.</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bitcoin Form -->
                        <div class="payment-form hidden" id="bitcoinForm">
                            <div class="external-payment-info">
                                <i class="fab fa-bitcoin"></i>
                                <h3>Pay with Cryptocurrency</h3>
                                <p>We accept Bitcoin (BTC), Ethereum (ETH), and other major cryptocurrencies.</p>
                                <div class="crypto-options">
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="btc" checked>
                                        <span><i class="fab fa-bitcoin"></i> Bitcoin</span>
                                    </label>
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="eth">
                                        <span><i class="fab fa-ethereum"></i> Ethereum</span>
                                    </label>
                                    <label class="crypto-option">
                                        <input type="radio" name="crypto" value="usdt">
                                        <span>₮ USDT</span>
                                    </label>
                                </div>
                                <p class="crypto-note">You will receive a wallet address to send payment after
                                    confirming your order.</p>
                            </div>
                        </div>

                        <!-- Apple Pay Form -->
                        <div class="payment-form hidden" id="applePayForm">
                            <div class="external-payment-info">
                                <i class="fab fa-apple-pay"></i>
                                <h3>Apple Pay</h3>
                                <p>Use Touch ID or Face ID for a quick and secure checkout.</p>
                                <button type="button" class="btn btn-dark apple-pay-btn" id="applePayBtn">
                                    <i class="fab fa-apple"></i> Pay with Apple Pay
                                </button>
                            </div>
                        </div>

                        <!-- Google Pay Form -->
                        <div class="payment-form hidden" id="googlePayForm">
                            <div class="external-payment-info">
                                <i class="fab fa-google-pay"></i>
                                <h3>Google Pay</h3>
                                <p>Pay quickly and securely with your saved cards.</p>
                                <button type="button" class="btn btn-dark google-pay-btn" id="googlePayBtn">
                                    <i class="fab fa-google"></i> Pay with Google Pay
                                </button>
                            </div>
                        </div>

                        <!-- NFC & Biometric Form -->
                        <div class="payment-form hidden" id="nfcBiometricForm">
                            <div class="nfc-biometric-container">
                                <div class="biometric-laser" id="biometricLaser"></div>
                                <div class="nfc-biometric-header">
                                    <h3><i class="fas fa-fingerprint" style="color: var(--cyan); margin-right: 5px;"></i> Contactless & Biometric Terminal</h3>
                                    <p>Simulate a secure contactless device scan or a biometric scan to authorize payment.</p>
                                </div>
                                <div class="nfc-scanners">
                                    <div class="scanner-container">
                                        <button type="button" class="biometric-scanner-btn" id="biometricScanBtn" title="Scan Fingerprint">
                                            <i class="fas fa-fingerprint"></i>
                                        </button>
                                        <span class="scanner-label">Biometric (TouchID)</span>
                                    </div>
                                    <div class="scanner-container">
                                        <button type="button" class="nfc-radar-btn" id="nfcScanBtn" title="Scan NFC Device">
                                            <i class="fas fa-rss"></i>
                                        </button>
                                        <span class="scanner-label">Contactless (NFC)</span>
                                    </div>
                                </div>
                                <div class="nfc-status-readout" id="nfcStatusReadout">
                                    STATUS: READY FOR TERMINAL HANDSHAKE...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-file-invoice"></i> Billing Address</h2>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="sameAsShipping" name="sameAsShipping" checked>
                            <label for="sameAsShipping">Same as shipping address</label>
                        </div>
                        <div class="billing-address-form hidden" id="billingAddressForm">
                            <!-- Same fields as shipping but for billing -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingAddress">Street Address *</label>
                                    <input type="text" id="billingAddress" name="billingAddress">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingCity">City *</label>
                                    <input type="text" id="billingCity" name="billingCity">
                                </div>
                                <div class="form-group">
                                    <label for="billingState">Region *</label>
                                    <select id="billingState" name="billingState">
                                        <option value="">Select Region</option>
                                        <option value="CS">Casablanca-Settat</option>
                                        <option value="RK">Rabat-Salé-Kénitra</option>
                                        <option value="TT">Tanger-Tétouan-Al Hoceïma</option>
                                        <option value="FM">Fès-Meknès</option>
                                        <option value="MS">Marrakech-Safi</option>
                                        <option value="SM">Souss-Massa</option>
                                        <option value="OK">Oriental</option>
                                        <option value="BM">Béni Mellal-Khénifra</option>
                                        <option value="DA">Drâa-Tafilalet</option>
                                        <option value="GL">Guelmim-Oued Noun</option>
                                        <option value="LS">Laâyoune-Sakia El Hamra</option>
                                        <option value="ED">Dakhla-Oued Ed-Dahab</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="billingZip">ZIP Code *</label>
                                    <input type="text" id="billingZip" name="billingZip">
                                </div>
                                <div class="form-group">
                                    <label for="billingCountry">Country *</label>
                                    <select id="billingCountry" name="billingCountry">
                                        <option value="MA" selected>Morocco</option>
                                        <option value="FR">France</option>
                                        <option value="ES">Spain</option>
                                        <option value="DE">Germany</option>
                                        <option value="UK">United Kingdom</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-sticky-note"></i> Order Notes (Optional)</h2>
                        <div class="form-group">
                            <textarea id="orderNotes" name="orderNotes" rows="3"
                                placeholder="Special instructions for your order..."></textarea>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="checkout-section terms-section">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="termsAgree" name="termsAgree" required>
                            <label for="termsAgree">I agree to the <a href="terms-of-service.php" target="_blank">Terms and Conditions</a>
                                and <a href="privacy-policy.php" target="_blank">Privacy Policy</a> *</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="newsletterSignup" name="newsletterSignup">
                            <label for="newsletterSignup">Subscribe to our newsletter for exclusive deals and
                                updates</label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="order-summary-sidebar">
                    <div class="order-summary-card">
                        <h3>Order Summary</h3>

                        <div class="promo-redemption" style="padding: 16px; background: rgba(0,245,212,0.04); border: 1px solid rgba(0,245,212,0.1); border-radius: 12px; margin-bottom: 20px;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; color:var(--cyan); font-weight:700; font-size:0.85rem;">
                                <i class="fas fa-ticket"></i> <span>Promo Code</span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="checkoutPromoCode" placeholder="WELCOME10" style="flex:1; padding:8px; background:var(--page-bg); border:1px solid var(--border); border-radius:6px; font-size:0.85rem; color:var(--text); text-transform:uppercase;">
                                <button type="button" id="applyCheckoutPromoBtn" class="button button-primary" style="padding:0 12px; font-size:0.75rem; height:36px; background:var(--cyan); border:none; border-radius:6px; cursor:pointer; font-weight:700;">Apply</button>
                            </div>
                            <div id="checkoutPromoMessage" style="margin-top:8px; font-size:0.75rem;"></div>
                        </div>
                        
                        <!-- Phase 3: Loyalty Rewards -->
                        <div class="loyalty-redemption" id="loyaltySection" style="display: none; padding: 16px; background: rgba(0,245,212,0.05); border: 1px solid rgba(0,245,212,0.1); border-radius: 12px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: var(--cyan); font-weight: 700; font-size: 0.85rem;">
                                <i class="fas fa-crown"></i> <span>Loyalty Rewards</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 12px;">
                                You have <strong id="userPointsBalance" style="color: var(--text);">0</strong> points.
                                <br><small>(100 pts = 10 MAD discount)</small>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" id="redeemPointsInput" placeholder="Points to use" style="flex: 1; padding: 8px; background: var(--page-bg); border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: var(--text);">
                                <button type="button" id="applyPointsBtn" class="button button-primary" style="padding: 0 12px; font-size: 0.75rem; height: 36px; background: var(--cyan); border: none; border-radius: 6px; cursor: pointer; font-weight: 700;">Apply</button>
                            </div>
                            <div id="pointsMessage" style="margin-top: 8px; font-size: 0.75rem;"></div>
                        </div>

                        <div class="order-items" id="orderItems">
                            <!-- Order items will be loaded via JavaScript -->
                        </div>
                        <div class="checkout-upsells" id="checkoutUpsells" style="display: none;">
                            <div class="checkout-upsells-header">
                                <span><i class="fas fa-toolbox"></i> Oops, don't forget</span>
                                <small>Smart accessories for this build</small>
                            </div>
                            <div class="checkout-upsell-list" id="checkoutUpsellList"></div>
                        </div>
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span id="orderSubtotal">0.00 MAD</span>
                            </div>
                            <div class="total-row">
                                <span>Shipping</span>
                                <span id="orderShipping">100 MAD</span>
                            </div>
                            <div class="total-row">
                                <span>Tax</span>
                                <span id="orderTax">0.00 MAD</span>
                            </div>
                            <div class="total-row discount" id="orderDiscountRow" style="display: none;">
                                <span>Discount</span>
                                <span id="orderDiscount">-0.00 MAD</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total</span>
                                <span id="orderTotal">0.00 MAD</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block place-order-btn" id="placeOrderBtn"
                            form="shippingForm">
                            <i class="fas fa-lock"></i> Place Order
                        </button>

                        <div class="checkout-badges">
                            <div class="badge">
                                <i class="fas fa-shield-alt"></i>
                                <span>SSL Secured</span>
                            </div>
                            <div class="badge">
                                <i class="fas fa-undo"></i>
                                <span>30-Day Returns</span>
                            </div>
                            <div class="badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified Merchant</span>
                            </div>
                        </div>
                    </div>

                    <!-- Need Help -->
                    <div class="need-help">
                        <h4>Need Help?</h4>
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
            <h3 class="processing-title" id="processingTitle">Processing Payment</h3>
            <p class="processing-subtitle" id="processingSubtitle">Please do not close this window</p>
            <div class="processing-steps">
                <div class="processing-step active" id="stepVerify">
                    <div class="step-dot"></div>
                    <span>Verifying card details</span>
                </div>
                <div class="processing-step" id="stepAuth">
                    <div class="step-dot"></div>
                    <span>Authorizing payment</span>
                </div>
                <div class="processing-step" id="stepConfirm">
                    <div class="step-dot"></div>
                    <span>Confirming transaction</span>
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
                <h2 style="font-size: 1.2rem; margin: 0;"><i class="fab fa-bitcoin" style="color: var(--amber); margin-right: 8px;"></i> Cryptocurrency Payment</h2>
                <button type="button" class="close-modal" id="closeCryptoModal" style="background: none; border: none; color: var(--text); font-size: 1.2rem; cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <p>Please send exactly <strong id="cryptoAmount" style="color: var(--cyan); font-size: 1.3rem;">0.00</strong> to the address below.</p>
                
                <div style="margin: 20px auto; padding: 16px; background: white; width: fit-content; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <img id="cryptoQrCode" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh" alt="QR Code" style="display: block;" />
                </div>
                
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label>Wallet Address</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="cryptoAddress" value="bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh" readonly style="font-family: var(--font-mono); font-size: 0.85rem;" />
                        <button type="button" class="btn btn-outline" id="copyCryptoBtn" style="padding: 0 16px; min-width: unset;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                
                <div style="margin-top: 16px; margin-bottom: 24px; font-size: 0.85rem; color: var(--text-soft); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-circle-notch fa-spin" style="color: var(--cyan);"></i> Waiting for network confirmation...
                </div>
            </div>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button type="button" class="btn btn-primary" id="confirmCryptoPaymentBtn" style="width: 100%;">
                    I Have Sent The Payment
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
                <h2>Order Confirmed!</h2>
                <p id="confirmationMessage">Thank you for your purchase. Your order has been placed successfully.</p>
                <div class="order-info">
                    <div class="info-row">
                        <span>Order Number:</span>
                        <strong id="orderNumber">#000000</strong>
                    </div>
                    <div class="info-row">
                        <span>Transaction ID:</span>
                        <strong id="transactionId">—</strong>
                    </div>
                    <div class="info-row">
                        <span>Payment Method:</span>
                        <strong id="confirmPaymentMethod">—</strong>
                    </div>
                    <div class="info-row">
                        <span>Amount Charged:</span>
                        <strong id="confirmAmount">—</strong>
                    </div>
                    <div class="info-row" id="pickupCodeRow" style="display:none;">
                        <span>Pickup Code:</span>
                        <strong id="confirmPickupCode">—</strong>
                    </div>
                    <div class="info-row">
                        <span>Confirmation Email:</span>
                        <span id="confirmEmail">sent to your email</span>
                    </div>
                </div>
                <div class="confirmation-actions">
                    <a href="index.html" class="btn btn-primary">Continue Shopping</a>
                    <button class="btn btn-outline" id="downloadPickupTicketBtn" type="button" style="display:none;">
                        <i class="fas fa-file-arrow-down"></i> Download Pickup Ticket
                    </button>
                    <button class="btn btn-outline" id="trackOrderBtn">Track Order</button>
                    <button class="btn btn-outline" id="closeConfirmationBtn" type="button">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer - Simplified -->
    <footer class="footer checkout-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Maroc PC. All rights reserved.</p>
                <div class="footer-links">
                    <a href="privacy-policy.php">Privacy Policy</a>
                    <a href="terms-of-service.php">Terms of Service</a>
                    <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank">Facebook</a>
                    <a href="https://x.com/Maroc_PC_PHP" target="_blank">X (Twitter)</a>
                    <a href="https://www.instagram.com/marocpc57" target="_blank">Instagram</a>
                    <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank">YouTube</a>
                    <a href="index.html#contact">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Item added to cart!</span>
    </div>


    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars(envString('PAYPAL_CLIENT_ID', 'sb')); ?>&currency=USD&intent=capture&disable-funding=credit,card" data-sdk-integration-source="button-factory"></script>

    <script>
        window.STRIPE_PUBLISHABLE_KEY = '';
    </script>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/checkout.js?v=<?= urlencode((string) filemtime(__DIR__ . '/assets/js/checkout.js')) ?>" defer></script>
    
    <script>
        // Plant footprint for session expiration detection
        localStorage.setItem('has_active_session', '1');
    </script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>
