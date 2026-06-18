<?php
require_once __DIR__ . '/i18n.php';

/**
 * Shared mobile sidebar used by the store hamburger menu.
 */
function storeSidebar(string $activePage = '', string $activeTool = ''): void
{
    $isActive = fn(string $page): string => $activePage === $page ? ' active' : '';
    $isToolActive = fn(string $tool): string => $activeTool === $tool ? ' active' : '';
    $builderOpen = $activeTool !== '';
?>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <nav class="sidebar" id="sidebar" aria-label="Mobile navigation" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="sidebar-header">
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-logo-link">
                <i class="fas fa-microchip"></i> Maroc PC
            </a>
            <button class="sidebar-close" id="sidebarClose" type="button" aria-label="<?php i18n_e('nav.close_menu'); ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-search">
            <input type="text" placeholder="<?php i18n_e('nav.search_components'); ?>" aria-label="<?php i18n_e('nav.search_products'); ?>" />
            <button type="button" aria-label="<?php i18n_e('nav.search_products'); ?>">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <li><a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('home') ?>"><i class="fas fa-home"></i> <?php i18n_e('nav.home'); ?></a></li>
            <li><a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('products') ?>"><i class="fas fa-box"></i> <?php i18n_e('nav.products'); ?></a></li>
            <li class="sidebar-dropdown<?= $builderOpen ? ' open' : '' ?>">
                <button class="sidebar-link sidebar-toggle-btn<?= $builderOpen ? ' active' : '' ?>" type="button" aria-expanded="<?= $builderOpen ? 'true' : 'false' ?>">
                    <i class="fas fa-tools"></i>
                    <?php i18n_e('nav.builder_tools'); ?>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('builder') ?>"><?php i18n_e('nav.pc_build_wizard'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=gaming-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('gaming-finder') ?>"><?php i18n_e('nav.gaming_pc_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('laptop-finder') ?>"><?php i18n_e('nav.laptop_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=psu-calculator'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('psu-calculator') ?>"><?php i18n_e('nav.psu_calculator'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=memory-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('memory-finder') ?>"><?php i18n_e('nav.memory_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink<?= $isToolActive('tools') ?>"><?php i18n_e('nav.tools_cockpit'); ?></a></li>
                </ul>
            </li>
            <li class="sidebar-dropdown">
                <button class="sidebar-link sidebar-toggle-btn" type="button" aria-expanded="false">
                    <i class="fas fa-th"></i>
                    <?php i18n_e('nav.categories'); ?>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=processors'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.processors'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=graphics'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.graphics_cards'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=memory'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.memory_ram'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=storage'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.storage'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=motherboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.motherboards'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=cooling'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.cooling'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=power'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.power_supplies'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=cases'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.pc_cases'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=accessories'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.accessories'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=keyboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.keyboards', [], 'Keyboards'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=mouse'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.mice', [], 'Mice'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=vr'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.vr_headsets', [], 'VR Headsets'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=router'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.routers', [], 'Routers'); ?></a></li>
                </ul>
            </li>
            <li><a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('deals') ?>"><i class="fas fa-percent"></i> <?php i18n_e('nav.special_deals'); ?></a></li>
            <li><a href="<?= htmlspecialchars(i18n_url('account.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('account') ?>"><i class="fas fa-user-circle"></i> <?php i18n_e('nav.my_account'); ?></a></li>
            <li>
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('cart') ?>">
                    <i class="fas fa-shopping-cart"></i> <?php i18n_e('nav.cart'); ?>
                    <span class="sidebar-cart-badge" id="sidebarCartCount">0</span>
                </a>
            </li>
            <li><a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $isActive('contact') ?>"><i class="fas fa-envelope"></i> <?php i18n_e('nav.contact'); ?></a></li>
        </ul>
    </nav>
<?php
}
