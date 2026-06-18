<?php
require_once __DIR__ . '/includes/store-head.php';
require_once __DIR__ . '/includes/store-header.php';
require_once __DIR__ . '/includes/store-sidebar.php';
require_once __DIR__ . '/includes/store-footer.php';
require_once __DIR__ . '/includes/store-end.php';

storeHead(
    i18n_t('products.title'),
    [
        'assets/css/styles.css',
        'assets/css/products.css?v=mobile-dock-1',
        'assets/css/installment-compare.css',
        'assets/css/cinematic-enhancements.css?v=tilt-soft-1',
    ],
    ['assets/js/wishlist.js'],
    i18n_t('products.description'),
    'https://marocpc.com/logo.png'
);
?>
</head>
<?php storeHeader('products'); ?>

<?php require __DIR__ . '/products-content.php'; ?>

<?php storeFooter(); ?>

<?php storeSidebar('products'); ?>

<?php require __DIR__ . '/products-post.php'; ?>

<?php storeEnd([
    'assets/js/store-sidebar.js?v=shared-sidebar-1',
    'assets/js/cinematic-enhancements.js?v=tilt-soft-1',
]); ?>
