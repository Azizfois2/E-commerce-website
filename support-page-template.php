<?php
declare(strict_types=1);

function renderSupportPage(array $page): void
{
    require_once __DIR__ . '/includes/i18n.php';
    require_once __DIR__ . '/includes/support-page-i18n.php';
    i18n_start_page_translation();

    $supportPhraseMap = array_replace(
        i18n_page_phrase_map(i18n_current_locale()),
        support_page_phrase_map(i18n_current_locale())
    );
    $supportT = static function (string $text) use ($supportPhraseMap): string {
        return $supportPhraseMap[$text] ?? $text;
    };
    $supportE = static function (string $text) use ($supportT): string {
        return htmlspecialchars($supportT($text), ENT_QUOTES, 'UTF-8');
    };

    $title = $supportE((string) $page['title']);
    $eyebrow = $supportE((string) ($page['eyebrow'] ?? 'Customer Support'));
    $summary = $supportE((string) ($page['summary'] ?? ''));
    $updated = $supportE((string) ($page['updated'] ?? 'May 13, 2026'));
    $sections = $page['sections'] ?? [];
    $cards = $page['cards'] ?? [];
    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
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
    <?= i18n_preference_assets() ?>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu"><span></span><span></span><span></span></button>
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo"><img src="logo.png" alt="Maroc PC Logo" class="nav-logo"></a>
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
                <a href="<?= htmlspecialchars(i18n_url('account.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.account'); ?>"><i class="fas fa-user"></i></a>
            </div>
            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.shopping_cart'); ?>">
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
            <small><?= $supportE('Last updated'); ?>: <?= $updated; ?></small>
        </section>

        <?php if ($cards !== []): ?>
            <section class="info-cards" aria-label="<?= $title; ?> highlights">
                <?php foreach ($cards as $card): ?>
                    <article>
                        <i class="fas <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-check'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <strong><?= $supportE((string) $card['title']); ?></strong>
                        <span><?= $supportE((string) $card['text']); ?></span>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="info-layout">
            <aside class="info-nav">
                <strong><?= $supportE('On This Page'); ?></strong>
                <?php foreach ($sections as $index => $section): ?>
                    <a href="#section-<?= $index + 1; ?>"><?= $supportE((string) $section['title']); ?></a>
                <?php endforeach; ?>
            </aside>

            <div class="info-content">
                <?php foreach ($sections as $index => $section): ?>
                    <article class="info-section" id="section-<?= $index + 1; ?>">
                        <h2><?= $supportE((string) $section['title']); ?></h2>
                        <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                            <p><?= $supportE((string) $paragraph); ?></p>
                        <?php endforeach; ?>
                        <?php if (!empty($section['items'])): ?>
                            <ul>
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><?= $supportE((string) $item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($section['faq'])): ?>
                            <div class="faq-list">
                                <?php foreach ($section['faq'] as $faq): ?>
                                    <details>
                                        <summary><?= $supportE((string) $faq['q']); ?></summary>
                                        <p><?= $supportE((string) $faq['a']); ?></p>
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
                <span class="eyebrow"><?= $supportE('Need Help?'); ?></span>
                <h2><?= $supportE('Contact Maroc PC Support'); ?></h2>
                <p><?= $supportE('For account, order, privacy, shipping, or after-sales questions, include your order number when possible.'); ?></p>
            </div>
            <div class="info-contact-actions">
                <a class="btn btn-primary" href="mailto:support@marocpc.com"><i class="fas fa-envelope"></i> <?= $supportE('Email Support'); ?></a>
                <a class="btn btn-secondary" href="tel:+212618821949"><i class="fas fa-phone"></i> <?= $supportE('Call Support'); ?></a>
            </div>
        </section>
    </main>

    <?php
require_once __DIR__ . '/includes/store-footer.php';
storeFooter();
?>

    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <?= i18n_language_switcher_assets() ?>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
</body>
</html>
<?php
}
