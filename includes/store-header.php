<?php
require_once __DIR__ . '/i18n.php';

/**
 * Reusable store header/nav component.
 *
 * Usage:
 *   storeHeader('home'); // 'home', 'products', 'builder', 'deals', 'contact'
 */
function storeHeader(string $activeNav = '', bool $includeRoleModal = false): void
{
    $isActive = fn(string $page): string => $activeNav === $page ? 'active' : '';
    $currentLocale = i18n_current_locale();
    $localeLabels = i18n_locale_labels();
?>
<body>
    <header>
        <span class="myDIV">
            <button class="hamburger-btn mobile-menu-btn" id="hamburgerBtn" aria-label="<?php i18n_e('nav.open_menu'); ?>" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>

            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $isActive('home') ?>"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $isActive('products') ?>"><?php i18n_e('nav.components'); ?></a>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                        <?php i18n_e('nav.builder_tools'); ?> <span class="chevron">&#9662;</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.pc_build_wizard'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=gaming-finder'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.gaming_pc_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.laptop_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=psu-calculator'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.psu_calculator'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=memory-finder'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.memory_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.tools_cockpit'); ?></a>
                    </div>
                </div>
                <a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $isActive('deals') ?>"><?php i18n_e('nav.deals'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $isActive('contact') ?>"><?php i18n_e('nav.contact'); ?></a>
            </nav>

            <form class="search-box" role="search" action="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" method="get">
                <?php if ($currentLocale !== I18N_DEFAULT_LOCALE): ?>
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($currentLocale, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon-inside"></i>
                    <input type="text" id="searchInput" name="search" placeholder="<?php i18n_e('nav.search_components'); ?>" class="search-input" aria-label="<?php i18n_e('nav.search_products'); ?>">
                </div>
            </form>

            <div class="nav-spacer" aria-hidden="true"></div>

            <button class="theme-toggle" id="themeToggle" aria-label="<?php i18n_e('nav.toggle_theme'); ?>">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div class="custom-translate-container nav-translate" aria-label="<?php i18n_e('nav.select_language'); ?>">
                <button class="custom-translate-btn notranslate" type="button" aria-label="<?php i18n_e('nav.select_language'); ?>" aria-haspopup="true" aria-expanded="false" data-language-toggle>
                    <?= htmlspecialchars(strtoupper($currentLocale), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="custom-translate-dropdown" hidden data-language-menu>
                    <?php foreach ($localeLabels as $locale => $label): ?>
                        <a class="custom-translate-option notranslate<?= $locale === $currentLocale ? ' active' : '' ?>" href="<?= htmlspecialchars(i18n_current_url_for($locale), ENT_QUOTES, 'UTF-8') ?>" lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="flag-icon"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="lang-name"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cart-wrapper" id="userNav">
                <a href="<?= htmlspecialchars(i18n_url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.account'); ?>">
                    <i class="fas fa-user"></i>
                </a>
            </div>

            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon <?= $isActive('cart') ?>" aria-label="<?php i18n_e('nav.shopping_cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>
    <?php if ($includeRoleModal): ?>
    <div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title"><?php i18n_e('auth.sign_in'); ?></p>
            <p class="role-modal-subtitle"><?php i18n_e('auth.select_account_type'); ?></p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon"><i class="fas fa-user"></i></span>
                <div>
                    <strong><?php i18n_e('auth.customer_account'); ?></strong>
                    <small><?php i18n_e('auth.customer_account_hint'); ?></small>
                </div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon"><i class="fas fa-shield-alt"></i></span>
                <div>
                    <strong><?php i18n_e('auth.admin_portal'); ?></strong>
                    <small><?php i18n_e('auth.admin_portal_hint'); ?></small>
                </div>
            </button>
            <button class="role-cancel" onclick="closeRoleModal()"><?php i18n_e('auth.cancel'); ?></button>
        </div>
    </div>
    <?php endif; ?>
<?php
}
