<?php
require_once __DIR__ . '/i18n.php';

/**
 * Reusable store footer component.
 *
 * Usage:
 *   storeFooter();
 */
function storeFooter(): void
{
?>
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">
                        <i class="fas fa-microchip"></i>
                        <span>MarocPC</span>
                    </a>
                    <p><?php i18n_e('footer.tagline'); ?></p>
                    <nav class="social-links" aria-label="<?php i18n_e('footer.social_media'); ?>">
                        <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://x.com/Maroc_PC_PHP" target="_blank" aria-label="X"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor" style="vertical-align: middle;"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg></a>
                        <a href="https://www.instagram.com/marocpc57" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </nav>
                </div>
                <div class="footer-column">
                    <h4><?php i18n_e('footer.quick_links'); ?></h4>
                    <ul>
                        <li><a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.home'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.products'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('index.php#categories'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.categories'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.deals'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4><?php i18n_e('footer.customer_service'); ?></h4>
                    <ul>
                        <li><a href="<?= htmlspecialchars(i18n_url('account.php?tab=orders'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.track_order'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('returns-refunds.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.returns_refunds'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('shipping-info.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.shipping_info'); ?></a></li>
                        <li><a href="<?= htmlspecialchars(i18n_url('faq.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.faq'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4><?php i18n_e('footer.contact_us'); ?></h4>
                    <address>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-marker-alt"></i> <?php i18n_e('footer.address'); ?></li>
                            <li><i class="fas fa-phone"></i> <a href="tel:+212618821949">+212 618821949</a></li>
                            <li><i class="fas fa-envelope"></i> <a href="mailto:support@marocpc.com">support@marocpc.com</a></li>
                        </ul>
                    </address>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Maroc PC. <?php i18n_e('footer.all_rights'); ?></p>
                <nav class="footer-links" aria-label="<?php i18n_e('footer.legal_links'); ?>">
                    <a href="<?= htmlspecialchars(i18n_url('privacy-policy.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.privacy_policy'); ?></a>
                    <a href="<?= htmlspecialchars(i18n_url('terms-of-service.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.terms_of_service'); ?></a>
                    <a href="<?= htmlspecialchars(i18n_url('cookie-policy.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('footer.cookie_policy'); ?></a>
                </nav>
            </div>
        </div>
    </footer>
<?php
}
