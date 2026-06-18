<?php
/**
 * Reusable store closing tags + deferred scripts.
 *
 * Usage:
 *   storeEnd(['assets/js/extra.js']);
 */
function storeEnd(array $extraScripts = [], bool $includeToast = true, bool $includeScrollTop = true): void
{
?>
    <?php if ($includeToast): ?>
    <!-- Toast -->
    <output class="toast" id="toast" role="status" aria-live="polite">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </output>
    <?php endif; ?>

    <?php if ($includeScrollTop): ?>
    <!-- Scroll to top -->
    <button class="button-nadi" id="scrollTop" type="button" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    <?php endif; ?>

    <!-- Common deferred scripts -->
    <script src="assets/js/theme.js" defer></script>
    <script src="assets/js/page-transitions.js" defer></script>
    <script>
        (function () {
            const initLanguageMenus = () => {
                document.querySelectorAll('[data-language-toggle]').forEach((button) => {
                    const container = button.closest('.custom-translate-container');
                    const menu = container ? container.querySelector('[data-language-menu]') : null;
                    if (!menu || button.dataset.languageReady === '1') return;
                    button.dataset.languageReady = '1';

                    const close = () => {
                        menu.hidden = true;
                        menu.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    };

                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const open = menu.hidden;
                        menu.hidden = !open;
                        menu.classList.toggle('show', open);
                        button.setAttribute('aria-expanded', String(open));
                    });
                    menu.addEventListener('click', (event) => event.stopPropagation());
                    document.addEventListener('click', close);
                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') close();
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLanguageMenus);
            } else {
                initLanguageMenus();
            }
        })();
    </script>

    <?php if ($includeScrollTop): ?>
    <script>
        (function () {
            const initScrollTop = () => {
                const btn = document.getElementById('scrollTop');
                if (!btn) return;

                const syncVisibility = () => {
                    btn.classList.toggle('visible', window.scrollY > 300);
                };

                btn.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                window.addEventListener('scroll', syncVisibility, { passive: true });
                syncVisibility();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initScrollTop);
            } else {
                initScrollTop();
            }
        })();
    </script>
    <?php endif; ?>

    <!-- Visitor fingerprint collector -->
    <script src="assets/js/visitor-fingerprint.js" defer></script>

    <!-- Page-specific deferred scripts -->
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>

</body>
</html>
<?php
}
