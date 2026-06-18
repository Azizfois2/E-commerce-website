<?php
require_once __DIR__ . '/includes/store-head.php';
require_once __DIR__ . '/includes/store-header.php';
require_once __DIR__ . '/includes/store-sidebar.php';
require_once __DIR__ . '/includes/store-footer.php';
require_once __DIR__ . '/includes/store-end.php';

storeHead(
    i18n_t('cart.title'),
    [
        'assets/css/cart.css?v=search-padding-1',
        'assets/css/installment-compare.css',
        'assets/css/cinematic-enhancements.css?v=cinematic-rebind-1',
    ],
    [],
    i18n_t('cart.description'),
    'https://marocpc.com/logo.png'
);
?>
</head>
<?php storeHeader('cart'); ?>

<?php require __DIR__ . '/cart-content.php'; ?>

<?php storeFooter(); ?>

<?php storeSidebar('cart'); ?>

<?php require __DIR__ . '/cart-post.php'; ?>

<?php storeEnd([
    'assets/js/store-sidebar.js?v=shared-sidebar-1',
    'assets/js/cinematic-enhancements.js?v=cinematic-rebind-1',
]); ?>
