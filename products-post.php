    <!-- Legacy quick view modal kept as a safe fallback; product details now use the page surface above. -->
    <div class="modal" id="quickViewModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close"><i class="fas fa-times"></i></button>
            <div class="modal-body" id="quickViewContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
<div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title"><?php i18n_e('auth.sign_in', [], 'Sign In'); ?></p>
            <p class="role-modal-subtitle"><?php i18n_e('auth.more_signin_options', [], 'Select your account type to continue to the login page.'); ?></p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon"><i class="fas fa-user"></i></span>
                <div>
                    <strong><?php i18n_e('auth.customer_account', [], 'Customer Account'); ?></strong>
                    <small><?php i18n_e('auth.customer_account_hint', [], 'Track orders, wishlists & purchases'); ?></small>
                </div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon"><i class="fas fa-shield-alt"></i></span>
                <div>
                    <strong><?php i18n_e('auth.admin_portal', [], 'Admin Portal'); ?></strong>
                    <small><?php i18n_e('auth.admin_portal_hint', [], 'Inventory, orders & site management'); ?></small>
                </div>
            </button>
            <button class="role-cancel" onclick="closeRoleModal()"><?php i18n_e('account.cancel', [], 'Cancel'); ?></button>
        </div>
    </div>

    <script src="assets/js/data.js?v=outlier-products-1"></script>
    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <script src="assets/js/installment.js"></script>
    <script src="assets/js/reviews.js"></script>
    <script src="assets/js/prod.js?v=alternatives-layout-1"></script>
<script src="assets/js/auth-nav.js"></script>
    <script>
        document.addEventListener('click', function (e) {
            const accountLink = e.target.closest('a[aria-label="Account"]');
            if (accountLink) {
                const href = accountLink.getAttribute('href');
                if (href && href.indexOf('login.php') === -1) {
                    return;
                }
                e.preventDefault();
                const modal = document.getElementById('roleModal');
                if (modal) modal.style.display = 'flex';
            }
        });

        function selectRole(role) {
            closeRoleModal();
            window.location.href = role === 'user' ? 'login.php' : 'adminlogin.php';
        }

        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }

        document.getElementById('roleModal').addEventListener('click', function (e) {
            if (e.target === this) closeRoleModal();
        });
    </script>

    <!-- Compare Floating Bar -->
    <div class="compare-bar" id="compareBar">
        <div class="container">
            <div class="compare-bar-content">
                <div class="compare-items" id="compareItems">
                    <!-- Items will be injected here -->
                </div>
                <div class="compare-actions">
                    <button class="btn btn-outline" id="clearCompareBtn"><?php i18n_e('home.clear'); ?></button>
                    <button class="btn btn-primary" id="compareBtn" disabled><?php i18n_e('products.compare'); ?> (0)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Compare Modal -->
    <div class="modal" id="compareModal">
        <div class="modal-overlay"></div>
        <div class="modal-content compare-modal-content">
            <button class="modal-close" id="compareModalClose" aria-label="<?php i18n_e('products.close_compare', [], 'Close compare'); ?>"><i class="fas fa-times"></i></button>
            <div id="compareContent">
                <!-- Compare table will be injected here -->
            </div>
        </div>
    </div>
