<?php
declare(strict_types=1);

function renderSupportPage(array $page): void
{
    $title = htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8');
    $eyebrow = htmlspecialchars((string) ($page['eyebrow'] ?? 'Customer Support'), ENT_QUOTES, 'UTF-8');
    $summary = htmlspecialchars((string) ($page['summary'] ?? ''), ENT_QUOTES, 'UTF-8');
    $updated = htmlspecialchars((string) ($page['updated'] ?? 'May 13, 2026'), ENT_QUOTES, 'UTF-8');
    $sections = $page['sections'] ?? [];
    $cards = $page['cards'] ?? [];
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?> - Maroc PC</title>
    <meta name="description" content="<?= $summary; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/info-pages.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu"><span></span><span></span><span></span></button>
            <a href="index.html" class="logo"><img src="logo.png" alt="Maroc PC Logo" class="nav-logo"></a>
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
                <a href="login.php" class="cart-icon" aria-label="Account"><i class="fas fa-user"></i></a>
            </div>
            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <main class="info-page">
        <section class="info-hero">
            <span class="eyebrow"><i class="fas fa-circle-info"></i> <?= $eyebrow; ?></span>
            <h1><?= $title; ?></h1>
            <p><?= $summary; ?></p>
            <small>Last updated: <?= $updated; ?></small>
        </section>

        <?php if ($cards !== []): ?>
            <section class="info-cards" aria-label="<?= $title; ?> highlights">
                <?php foreach ($cards as $card): ?>
                    <article>
                        <i class="fas <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-check'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <strong><?= htmlspecialchars((string) $card['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?= htmlspecialchars((string) $card['text'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="info-layout">
            <aside class="info-nav">
                <strong>On This Page</strong>
                <?php foreach ($sections as $index => $section): ?>
                    <a href="#section-<?= $index + 1; ?>"><?= htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </aside>

            <div class="info-content">
                <?php foreach ($sections as $index => $section): ?>
                    <article class="info-section" id="section-<?= $index + 1; ?>">
                        <h2><?= htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                            <p><?= htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                        <?php if (!empty($section['items'])): ?>
                            <ul>
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($section['faq'])): ?>
                            <div class="faq-list">
                                <?php foreach ($section['faq'] as $faq): ?>
                                    <details>
                                        <summary><?= htmlspecialchars((string) $faq['q'], ENT_QUOTES, 'UTF-8'); ?></summary>
                                        <p><?= htmlspecialchars((string) $faq['a'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="info-contact">
            <div>
                <span class="eyebrow">Need Help?</span>
                <h2>Contact Maroc PC Support</h2>
                <p>For account, order, privacy, shipping, or after-sales questions, include your order number when possible.</p>
            </div>
            <div class="info-contact-actions">
                <a class="btn btn-primary" href="mailto:support@marocpc.com"><i class="fas fa-envelope"></i> Email Support</a>
                <a class="btn btn-secondary" href="tel:+212618821949"><i class="fas fa-phone"></i> Call Support</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <a href="index.html" class="footer-logo"><i class="fas fa-microchip"></i><span>MarocPC</span></a>
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
                        <li><a href="shipping-info.php">Shipping Info</a></li>
                        <li><a href="faq.php">FAQ</a></li>
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
                    <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank">Facebook</a>
                    <a href="https://x.com/Maroc_PC_PHP" target="_blank">X (Twitter)</a>
                    <a href="https://www.instagram.com/marocpc57" target="_blank">Instagram</a>
                    <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank">YouTube</a>
                    <a href="cookie-policy.php">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/cart.js"></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
</body>
</html>
<?php
}
