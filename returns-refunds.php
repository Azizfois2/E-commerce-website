<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns, Refunds & After-Sales Service - Maroc PC</title>
    <meta name="description" content="Start a return, refund, warranty, repair, missing item, or damaged package request with Maroc PC after-sales support.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/after-sales.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
            <a href="index.html" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>
            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Products</a>
                <a href="builder.php" class="nav-link">Builder</a>
                <a href="index.html#categories" class="nav-link">Categories</a>
                <a href="index.html#deals" class="nav-link">Deals</a>
            </nav>
            <div style="flex:1"></div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div id="google_translate_element" class="nav-translate"></div>
            <div class="cart-wrapper" id="userNav">
                <a href="login.php" class="cart-icon" aria-label="Account">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <main class="after-sales-page">
        <section class="after-hero">
            <div class="after-hero-copy">
                <span class="eyebrow"><i class="fas fa-headset"></i> After-Sales Desk</span>
                <h1>Returns, Refunds & Warranty Service</h1>
                <p>Open a service ticket for returns, refunds, exchanges, warranty diagnostics, damaged parcels, or missing items. We triage hardware cases with order checks, serial verification, and clear next steps.</p>
                <div class="after-actions">
                    <a href="#service-request" class="btn btn-primary">Start a Request</a>
                    <a href="account.php?tab=orders" class="btn btn-secondary">Track My Orders</a>
                </div>
            </div>
            <div class="after-hero-panel" aria-label="Service promise">
                <strong>RMA response</strong>
                <span>1-2 business days</span>
                <small>Urgent triage for damaged or missing items.</small>
            </div>
        </section>

        <section class="service-metrics" aria-label="After-sales commitments">
            <article>
                <i class="fas fa-rotate-left"></i>
                <strong>14 days</strong>
                <span>Return or exchange window for eligible complete items.</span>
            </article>
            <article>
                <i class="fas fa-screwdriver-wrench"></i>
                <strong>48h</strong>
                <span>Initial diagnostic plan for warranty and repair cases.</span>
            </article>
            <article>
                <i class="fas fa-money-bill-transfer"></i>
                <strong>3-10 days</strong>
                <span>Refund processing after inspection approval.</span>
            </article>
            <article>
                <i class="fas fa-box-open"></i>
                <strong>24h</strong>
                <span>Damaged parcel or missing-item priority review.</span>
            </article>
        </section>

        <section class="after-grid">
            <article class="policy-panel">
                <span class="eyebrow">Policy</span>
                <h2>What We Can Handle</h2>
                <div class="policy-list">
                    <div>
                        <strong>Returns & exchanges</strong>
                        <p>Accepted for complete products with accessories, manuals, packaging, and no physical damage. Opened items may need inspection before approval.</p>
                    </div>
                    <div>
                        <strong>Refunds</strong>
                        <p>Issued to the original payment method after service approval and product inspection. COD refunds may require bank or wallet details.</p>
                    </div>
                    <div>
                        <strong>Warranty & repairs</strong>
                        <p>We validate serial numbers, symptoms, purchase date, and manufacturer coverage before routing to repair, replacement, or brand service.</p>
                    </div>
                    <div>
                        <strong>Damaged or missing items</strong>
                        <p>Report within 24 hours of delivery. Keep all packaging and send photos of the parcel, labels, and product condition.</p>
                    </div>
                </div>
            </article>

            <article class="timeline-panel">
                <span class="eyebrow">Flow</span>
                <h2>Service Timeline</h2>
                <ol class="service-timeline">
                    <li><strong>Submit ticket</strong><span>Order number, product, issue type, and preferred resolution.</span></li>
                    <li><strong>Eligibility check</strong><span>We verify order, payment, delivery status, return window, and warranty path.</span></li>
                    <li><strong>Return intake</strong><span>Drop-off or courier instructions are sent after approval.</span></li>
                    <li><strong>Inspection</strong><span>Technicians check completeness, damage, serials, and fault symptoms.</span></li>
                    <li><strong>Resolution</strong><span>Refund, replacement, store credit, repair, or diagnostic report.</span></li>
                </ol>
            </article>
        </section>

        <section class="request-section" id="service-request">
            <div class="request-copy">
                <span class="eyebrow">RMA Form</span>
                <h2>Start an After-Sales Request</h2>
                <p>Use the same email as your order. If you are signed in, the ticket will also attach to your account. Add the product serial number for warranty or repair cases when available.</p>
                <div class="support-card">
                    <strong>Need help now?</strong>
                    <a href="tel:+212618821949"><i class="fas fa-phone"></i> +212 618821949</a>
                    <a href="mailto:support@marocpc.com"><i class="fas fa-envelope"></i> support@marocpc.com</a>
                </div>
            </div>

            <form class="after-form" id="afterSalesForm">
                <div class="form-row">
                    <label>Order number
                        <input type="number" name="order_id" min="1" placeholder="Example: 1004" required>
                    </label>
                    <label>Full name
                        <input type="text" name="customer_name" placeholder="Your name" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Email used on order
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </label>
                    <label>Phone
                        <input type="tel" name="phone" placeholder="+212 ...">
                    </label>
                </div>
                <div class="form-row">
                    <label>Request type
                        <select name="request_type" required>
                            <option value="">Choose...</option>
                            <option value="return">Return</option>
                            <option value="refund">Refund</option>
                            <option value="exchange">Exchange</option>
                            <option value="warranty">Warranty claim</option>
                            <option value="repair">Repair / diagnostic</option>
                            <option value="missing">Missing item</option>
                            <option value="damaged">Damaged on arrival</option>
                        </select>
                    </label>
                    <label>Preferred resolution
                        <select name="preferred_resolution" required>
                            <option value="">Choose...</option>
                            <option value="refund">Refund</option>
                            <option value="replacement">Replacement</option>
                            <option value="store_credit">Store credit</option>
                            <option value="repair">Repair</option>
                            <option value="diagnostic">Diagnostic report</option>
                        </select>
                    </label>
                </div>
                <label>Product concerned
                    <input type="text" name="product_name" placeholder="Example: NVIDIA RTX 4080 Super" required>
                </label>
                <div class="form-row">
                    <label>Product condition
                        <select name="product_condition" required>
                            <option value="">Choose...</option>
                            <option value="sealed">Sealed / unopened</option>
                            <option value="opened_unused">Opened but unused</option>
                            <option value="used">Used / installed</option>
                            <option value="defective">Defective</option>
                            <option value="damaged_package">Damaged packaging</option>
                            <option value="missing_item">Missing item/accessory</option>
                        </select>
                    </label>
                    <label>Serial number
                        <input type="text" name="serial_number" placeholder="Optional, recommended for warranty">
                    </label>
                </div>
                <label class="checkbox-line">
                    <input type="checkbox" name="package_opened" value="1">
                    <span>The retail package has been opened.</span>
                </label>
                <label>Describe the issue
                    <textarea name="reason" rows="6" minlength="20" placeholder="Tell us what happened, when you noticed it, and what resolution you expect." required></textarea>
                </label>
                <button class="btn btn-primary after-submit" type="submit">
                    <i class="fas fa-paper-plane"></i> Submit Service Ticket
                </button>
                <div class="after-form-result" id="afterSalesResult" role="status" aria-live="polite"></div>
            </form>
        </section>

        <section class="fine-print">
            <h2>Important Conditions</h2>
            <ul>
                <li>Data on storage devices should be backed up before any return, repair, or warranty intake.</li>
                <li>Physical damage, missing accessories, liquid damage, burned components, or modified firmware can affect eligibility.</li>
                <li>Refund approval depends on inspection result, payment status, and return completeness.</li>
                <li>Manufacturer warranty timelines vary by brand and product category.</li>
            </ul>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <a href="index.html" class="footer-logo">
                        <i class="fas fa-microchip"></i>
                        <span>MarocPC</span>
                    </a>
                    <p>Your trusted source for premium computer hardware. Building dreams, one component at a time.</p>
                </div>
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="products.html">Products</a></li>
                        <li><a href="builder.php">Builder</a></li>
                        <li><a href="index.html#deals">Deals</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="account.php?tab=orders">Track Order</a></li>
                        <li><a href="returns-refunds.php">Returns & Refunds</a></li>
                        <li><a href="returns-refunds.php#service-request">Open RMA Ticket</a></li>
                        <li><a href="mailto:support@marocpc.com">Support Email</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Contact Us</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Boulevard Zerktouni, Maarif</li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+212618821949">+212 618821949</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:support@marocpc.com">support@marocpc.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Maroc PC. All rights reserved.</p>
                <div class="footer-links">
                    <a href="privacy-policy.php">Privacy Policy</a>
                    <a href="terms-of-service.php">Terms of Service</a>
                    <a href="cookie-policy.php">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title">Continue as</p>
            <p class="role-modal-subtitle">Choose your access level to proceed.</p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon">U</span>
                <div><strong>Continue as user</strong><small>Standard access</small></div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon">A</span>
                <div><strong>Continue as administrator</strong><small>Full access</small></div>
            </button>
            <button class="role-cancel" onclick="closeRoleModal()">Cancel</button>
        </div>
    </div>

    <script src="assets/js/cart.js"></script>
    <script src="assets/js/translate.js"></script>
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
