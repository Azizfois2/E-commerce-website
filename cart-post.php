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

<script src="assets/js/data.js"></script>
<script src="assets/js/cart.js?v=notify-toast-2"></script>
<script src="assets/js/installment.js"></script>
<script src="assets/js/cart-page.js"></script>
<script src="assets/js/auth-nav.js"></script>

<script>
    document.addEventListener('click', function (e) {
        const accountLink = e.target.closest('a[aria-label="Account"]');
        if (!accountLink) return;

        const href = accountLink.getAttribute('href');
        if (href && href.indexOf('login.php') === -1) return;

        e.preventDefault();
        const modal = document.getElementById('roleModal');
        if (modal) modal.style.display = 'flex';
    });

    function selectRole(role) {
        closeRoleModal();
        window.location.href = role === 'user'
            ? <?= json_encode(i18n_url('login.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
            : <?= json_encode(i18n_url('adminlogin.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    }

    function closeRoleModal() {
        const modal = document.getElementById('roleModal');
        if (modal) modal.style.display = 'none';
    }

    const roleModal = document.getElementById('roleModal');
    if (roleModal) {
        roleModal.addEventListener('click', function (e) {
            if (e.target === this) closeRoleModal();
        });
    }
</script>
