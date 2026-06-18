<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin-helpers.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/visitor-tracker.php';
visitorTrackerBoot();

/**
 * Reusable store <head> component with performance optimizations.
 *
 * Usage:
 *   storeHead('Page Title', ['assets/css/extra.css'], ['assets/js/extra.js']);
 */
function storeHead(
    string $title = 'Maroc PC',
    array $extraCss = [],
    array $extraJs = [],
    ?string $description = null,
    ?string $ogImage = null
): void {
    $locale = i18n_current_locale();
    $direction = i18n_direction($locale);
    $desc = $description ?? i18n_t('meta.default_description');
    $img = $ogImage ?? 'https://marocpc.com/logo.png';
    $baseUrl = 'https://marocpc.com/';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars($direction, ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars(i18n_t('meta.keywords'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="Maroc PC">

    <!-- Preconnect to external origins -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <?php foreach (i18n_supported_locales() as $alternateLocale): ?>
        <link rel="alternate" hreflang="<?= htmlspecialchars($alternateLocale, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars(i18n_current_url_for($alternateLocale), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars(i18n_current_url_for(I18N_DEFAULT_LOCALE), ENT_QUOTES, 'UTF-8') ?>">

    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <?= i18n_preference_assets() ?>
    <script>
        window.__marocPcLocale = <?= json_encode($locale, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.__marocPcI18n = <?= json_encode([
            'account' => i18n_t('nav.account'),
            'accountAccess' => i18n_t('auth.sign_in'),
            'customerLogin' => i18n_t('auth.customer_account'),
            'customerLoginHint' => i18n_t('auth.customer_account_hint'),
            'createAccount' => i18n_t('auth.create_account', [], 'Create account'),
            'createAccountHint' => i18n_t('auth.create_account_hint', [], 'New Maroc PC profile'),
            'adminAccess' => i18n_t('auth.admin_portal'),
            'adminAccessHint' => i18n_t('auth.admin_portal_hint'),
            'moreSignInOptions' => i18n_t('auth.more_signin_options', [], 'More sign-in options'),
            'myAccount' => i18n_t('nav.my_account'),
            'myOrders' => i18n_t('footer.track_order'),
            'logout' => i18n_t('auth.logout', [], 'Logout'),
            'addToCart' => i18n_t('home.add_to_cart'),
            'addComboToCart' => i18n_t('cart.add_combo_to_cart', [], 'Add Combo to Cart'),
            'notifyAvailable' => i18n_t('home.notify_available'),
            'notifyMe' => i18n_t('products.notify_me', [], i18n_t('home.notify_available')),
            'grabDeal' => i18n_t('flash_sales.grab_deal', [], 'Grab Deal'),
            'flashOnlyLeft' => i18n_t('flash_sales.only_left', [], 'Only {count} left at this price!'),
            'added' => i18n_t('flash_sales.added', [], 'Added!'),
            'viewDetails' => i18n_t('products.view_details', [], 'View Details'),
            'compare' => i18n_t('products.compare', [], 'Compare'),
            'noProducts' => i18n_t('products.no_products', [], 'No products found matching your criteria.'),
            'general' => i18n_t('products.general', [], 'General'),
            'brand' => i18n_t('products.brand'),
            'rating' => i18n_t('products.rating'),
            'price' => i18n_t('products.price_range'),
            'specifications' => i18n_t('products.specifications', [], 'Specifications'),
            'specVram' => i18n_t('products.spec_vram', [], 'VRAM'),
            'specCudaCores' => i18n_t('products.spec_cuda_cores', [], 'CUDA Cores'),
            'specArchitecture' => i18n_t('products.spec_architecture', [], 'Architecture'),
            'specBoostClock' => i18n_t('products.spec_boost_clock', [], 'Boost Clock'),
            'specCoreClock' => i18n_t('products.spec_core_clock', [], 'Core Clock'),
            'specClockSpeed' => i18n_t('products.spec_clock_speed', [], 'Clock Speed'),
            'specOutputs' => i18n_t('products.spec_outputs', [], 'Outputs'),
            'specRecommendedPsu' => i18n_t('products.spec_recommended_psu', [], 'Recommended PSU'),
            'specTdp' => i18n_t('products.spec_tdp', [], 'TDP'),
            'specPower' => i18n_t('products.spec_power', [], 'Power'),
            'specCores' => i18n_t('products.spec_cores', [], 'Cores'),
            'specCoreCount' => i18n_t('products.spec_core_count', [], 'Core Count'),
            'specMemory' => i18n_t('products.spec_memory', [], 'Memory'),
            'specType' => i18n_t('products.spec_type', [], 'Type'),
            'specSocket' => i18n_t('products.spec_socket', [], 'Socket'),
            'specInterface' => i18n_t('products.spec_interface', [], 'Interface'),
            'specPcie' => i18n_t('products.spec_pcie', [], 'PCIe'),
            'specWifi' => i18n_t('products.spec_wifi', [], 'Wi-Fi'),
            'specWattage' => i18n_t('products.spec_wattage', [], 'Wattage'),
            'specCapacity' => i18n_t('products.spec_capacity', [], 'Capacity'),
            'specFormFactor' => i18n_t('products.spec_form_factor', [], 'Form Factor'),
            'specSpeed' => i18n_t('products.spec_speed', [], 'Speed'),
            'specLatency' => i18n_t('products.spec_latency', [], 'Latency'),
            'specThreads' => i18n_t('products.spec_threads', [], 'Threads'),
            'specBoost' => i18n_t('products.spec_boost', [], 'Boost'),
            'specBaseClock' => i18n_t('products.spec_base_clock', [], 'Base Clock'),
            'specM2Slots' => i18n_t('products.spec_m2_slots', [], 'M.2 Slots'),
            'specSataPorts' => i18n_t('products.spec_sata_ports', [], 'SATA Ports'),
            'specMaxMemory' => i18n_t('products.spec_max_memory', [], 'Max Memory'),
            'specPcieX16' => i18n_t('products.spec_pcie_x16', [], 'PCIe x16 Slots'),
            'specSize' => i18n_t('products.spec_size', [], 'Size'),
            'specResolution' => i18n_t('products.spec_resolution', [], 'Resolution'),
            'specRefreshRate' => i18n_t('products.spec_refresh_rate', [], 'Refresh Rate'),
            'specPanel' => i18n_t('products.spec_panel', [], 'Panel'),
            'specResponseTime' => i18n_t('products.spec_response_time', [], 'Response Time'),
            'specHdr' => i18n_t('products.spec_hdr', [], 'HDR'),
            'specAdaptiveSync' => i18n_t('products.spec_adaptive_sync', [], 'Adaptive Sync'),
            'specCurvature' => i18n_t('products.spec_curvature', [], 'Curvature'),
            'specChipset' => i18n_t('products.spec_chipset', [], 'Chipset'),
            'specMemorySlots' => i18n_t('products.spec_memory_slots', [], 'Memory Slots'),
            'specCable' => i18n_t('products.spec_cable', [], 'Cable'),
            'specColor' => i18n_t('products.spec_color', [], 'Color'),
            'specCompatibility' => i18n_t('products.spec_compatibility', [], 'Compatibility'),
            'specConductivity' => i18n_t('products.spec_conductivity', [], 'Conductivity'),
            'specConnector' => i18n_t('products.spec_connector', [], 'Connector'),
            'specConnectors' => i18n_t('products.spec_connectors', [], 'Connectors'),
            'specDisplay' => i18n_t('products.spec_display', [], 'Display'),
            'specEfficiency' => i18n_t('products.spec_efficiency', [], 'Efficiency'),
            'specFan' => i18n_t('products.spec_fan', [], 'Fan'),
            'specFanSize' => i18n_t('products.spec_fan_size', [], 'Fan Size'),
            'specFans' => i18n_t('products.spec_fans', [], 'Fans'),
            'specFit' => i18n_t('products.spec_fit', [], 'Fit'),
            'specL3Cache' => i18n_t('products.spec_l3_cache', [], 'L3 Cache'),
            'specLength' => i18n_t('products.spec_length', [], 'Length'),
            'specMaterial' => i18n_t('products.spec_material', [], 'Material'),
            'specMaxTdp' => i18n_t('products.spec_max_tdp', [], 'Max TDP'),
            'specModular' => i18n_t('products.spec_modular', [], 'Modular'),
            'specNoise' => i18n_t('products.spec_noise', [], 'Noise'),
            'specProfile' => i18n_t('products.spec_profile', [], 'Profile'),
            'specQuantity' => i18n_t('products.spec_quantity', [], 'Quantity'),
            'specRadiator' => i18n_t('products.spec_radiator', [], 'Radiator'),
            'specSeqRead' => i18n_t('products.spec_seq_read', [], 'Seq. Read'),
            'specSeqWrite' => i18n_t('products.spec_seq_write', [], 'Seq. Write'),
            'specSocketSupport' => i18n_t('products.spec_socket_support', [], 'Socket Support'),
            'specTbw' => i18n_t('products.spec_tbw', [], 'TBW'),
            'specUseCase' => i18n_t('products.spec_use_case', [], 'Use Case'),
            'specVoltage' => i18n_t('products.spec_voltage', [], 'Voltage'),
            'specWarning' => i18n_t('products.spec_warning', [], 'Warning'),
            'shareComparison' => i18n_t('products.share_comparison', [], 'Share Comparison'),
            'seeAlternatives' => i18n_t('products.see_alternatives', [], 'See Alternatives'),
            'purchaseConfidence' => i18n_t('products.purchase_confidence', [], 'Purchase Confidence'),
            'serviceSignals' => i18n_t('products.service_signals', [], 'Service Signals'),
            'buyingIntelligence' => i18n_t('products.buying_intelligence', [], 'Buying Intelligence'),
            'priceTools' => i18n_t('products.price_tools', [], 'Price Tools'),
            'competitorUrl' => i18n_t('products.competitor_url', [], 'Competitor URL'),
            'seenPrice' => i18n_t('products.seen_price', [], 'Seen price'),
            'requestPriceMatch' => i18n_t('products.request_price_match', [], 'Request price match'),
            'current' => i18n_t('products.current', [], 'Current'),
            'lowest' => i18n_t('products.lowest', [], 'Lowest'),
            'highest' => i18n_t('products.highest', [], 'Highest'),
            'average' => i18n_t('products.average', [], 'Average'),
            'averageShort' => i18n_t('products.average_short', [], 'AVG'),
            'priceHistory' => i18n_t('products.price_history', [], 'Price History'),
            'priceChartNote' => i18n_t('products.price_chart_note', [], 'Price in DH - Updated daily'),
            'noPriceHistory' => i18n_t('products.no_price_history', [], 'No price history available yet.'),
            'atLowestPrice' => i18n_t('products.at_lowest_price', [], 'At lowest price!'),
            'aboveLowestPrice' => i18n_t('products.above_lowest_price', [], '{percent}% above lowest'),
            'channel' => i18n_t('products.channel', [], 'Channel'),
            'useAccountEmail' => i18n_t('products.use_account_email', [], 'Use account email'),
            'competitorUrlPlaceholder' => i18n_t('products.competitor_url_placeholder', [], 'Jumia, Avito, store link'),
            'emailExamplePlaceholder' => i18n_t('products.email_example_placeholder', [], 'you@example.com'),
            'whatsapp' => i18n_t('products.whatsapp', [], 'WhatsApp'),
            'priceDropAlert' => i18n_t('products.price_drop_alert', [], 'Price Drop Alert'),
            'alertPriceDrops' => i18n_t('products.alert_price_drops', [], 'Alert me when price drops'),
            'installmentPayments' => i18n_t('products.installment_payments', [], 'Installment Payments'),
            'installmentMonth' => i18n_t('products.installment_month', [], '{count} Month'),
            'installmentMonths' => i18n_t('products.installment_months', [], '{count} Months'),
            'payInInstallments' => i18n_t('products.pay_in_installments', [], 'Pay in {count} monthly installments'),
            'installmentOr' => i18n_t('products.installment_or', [], 'or'),
            'month' => i18n_t('account.month', [], 'month'),
            'months' => i18n_t('account.months', [], 'months'),
            'monthShort' => i18n_t('account.month_short', [], 'mo'),
            'cashPrice' => i18n_t('products.cash_price', [], 'Cash Price'),
            'interestFee' => i18n_t('products.interest_fee', [], 'Interest Fee ({rate}%/yr)'),
            'totalCost' => i18n_t('products.total_cost', [], 'Total Cost'),
            'bestValue' => i18n_t('products.best_value', [], 'Best Value'),
            'bestPerformance' => i18n_t('products.best_performance', [], 'Best Performance'),
            'mostEfficient' => i18n_t('products.most_efficient', [], 'Most Efficient'),
            'cartQuantity' => i18n_t('cart.quantity', [], 'Quantity'),
            'cartDecreaseQuantity' => i18n_t('cart.decrease_quantity', [], 'Decrease quantity'),
            'cartIncreaseQuantity' => i18n_t('cart.increase_quantity', [], 'Increase quantity'),
            'cartRemoveItem' => i18n_t('cart.remove_item', [], 'Remove item'),
            'cartRemovedTemplate' => i18n_t('cart.removed_from_cart', [], '{name} removed from cart'),
            'cartClearConfirm' => i18n_t('cart.clear_confirm', [], 'Are you sure you want to clear your cart?'),
            'cartAlreadyEmpty' => i18n_t('cart.already_empty', [], 'Cart is already empty'),
            'cartCleared' => i18n_t('cart.cart_cleared', [], 'Cart cleared'),
            'cartCalculatedCheckout' => i18n_t('cart.calculated_checkout', [], 'Calculated at checkout'),
            'cartFree' => i18n_t('cart.free', [], 'FREE'),
            'cartPromoCode' => i18n_t('cart.promo_code', [], 'Promo Code'),
            'cartPromoEmpty' => i18n_t('cart.promo_empty', [], 'Please enter a promo code'),
            'cartPromoAppliedTemplate' => i18n_t('cart.promo_applied', [], 'Promo applied: {label}'),
            'cartPromoActiveTemplate' => i18n_t('cart.promo_active', [], 'Active: {label}'),
            'cartInvalidPromo' => i18n_t('cart.invalid_promo', [], 'Invalid promo code'),
            'cartPromoPercent10' => i18n_t('cart.promo_percent_10', [], '10% off'),
            'cartPromoPercent20' => i18n_t('cart.promo_percent_20', [], '20% off'),
            'cartPromoFixed50' => i18n_t('cart.promo_fixed_50', [], '50 DH off'),
            'cartPromoFreeShipping' => i18n_t('cart.promo_free_shipping', [], 'Free shipping'),
            'completeBuild' => i18n_t('cart.complete_build', [], 'Complete My Build'),
            'buildFromTemplate' => i18n_t('cart.build_from', [], ' from {price}'),
            'addCompatibleTemplate' => i18n_t('cart.add_compatible', [], '{message} Add a compatible {missing}{from}.'),
            'browseMissingTemplate' => i18n_t('cart.browse_missing', [], 'Browse {missing}'),
            'missingMotherboardName' => i18n_t('cart.missing_motherboard_name', [], 'motherboard'),
            'missingCpuName' => i18n_t('cart.missing_cpu_name', [], 'CPU'),
            'missingRamName' => i18n_t('cart.missing_ram_name', [], 'RAM'),
            'missingStorageName' => i18n_t('cart.missing_storage_name', [], 'storage'),
            'missingPsuName' => i18n_t('cart.missing_psu_name', [], 'power supply'),
            'missingCaseName' => i18n_t('cart.missing_case_name', [], 'case'),
            'missingMotherboardMessage' => i18n_t('cart.missing_motherboard_message', [], 'You have a CPU but no motherboard.'),
            'missingCpuMessage' => i18n_t('cart.missing_cpu_message', [], 'You have a motherboard but no processor.'),
            'missingRamMessage' => i18n_t('cart.missing_ram_message', [], "Don't forget the memory (RAM) for your system."),
            'missingStorageMessage' => i18n_t('cart.missing_storage_message', [], 'Your system needs storage to boot.'),
            'missingPsuMessage' => i18n_t('cart.missing_psu_message', [], 'Power up your components with a reliable PSU.'),
            'missingCaseMessage' => i18n_t('cart.missing_case_message', [], 'Give your components a home.'),
            'cartCouldNotAdd' => i18n_t('cart.could_not_add', [], 'This product could not be added. Please refresh and try again.'),
            'cartNotAvailableTemplate' => i18n_t('cart.not_available', [], '{name} is not available yet.'),
            'cartAddedTemplate' => i18n_t('cart.added_to_cart', [], '{name} added to cart!'),
            'cartAdded' => i18n_t('cart.added', [], 'Added!'),
            'buildComboAdded' => i18n_t('cart.build_combo_added', [], 'Build combo added to cart!'),
            'cartInStock' => i18n_t('cart.in_stock', [], 'In Stock'),
            'cartOutOfStock' => i18n_t('cart.out_of_stock', [], 'Out of Stock'),
            'restockTitle' => i18n_t('cart.restock_title', [], 'Restock signal armed'),
            'restockCopy' => i18n_t('cart.restock_copy', [], 'Drop your email and we will ping you when it returns.'),
            'restockEmailAddress' => i18n_t('cart.email_address', [], 'Email address'),
            'closeRestock' => i18n_t('cart.close_restock', [], 'Close restock notification'),
            'notifyMeProductTemplate' => i18n_t('cart.notify_me_product', [], 'Notify me: {name}'),
            'restockAvailableCopy' => i18n_t('cart.restock_available_copy', [], 'Enter your email and we will send a restock alert as soon as it is available.'),
            'validEmail' => i18n_t('cart.valid_email', [], 'Enter a valid email address.'),
            'saving' => i18n_t('cart.saving', [], 'Saving...'),
            'couldNotSaveRestock' => i18n_t('cart.could_not_save_restock', [], 'Could not save restock alert.'),
            'restockSetTemplate' => i18n_t('cart.restock_set', [], 'Restock alert set for {name}.'),
            'restockAlreadySubscribedTemplate' => i18n_t('cart.restock_already_subscribed', [], 'You are already subscribed to restock alerts for {name}.'),
            'networkError' => i18n_t('cart.network_error', [], 'Network error. Please try again.'),
            'email' => i18n_t('home.email'),
            'whatsappAdvice' => i18n_t('products.whatsapp_advice', [], 'WhatsApp advice before buying'),
            'monitors' => i18n_t('products.monitors', [], 'Monitors'),
            'quickView' => i18n_t('products.quick_view', [], 'Quick View'),
            'addToWishlist' => i18n_t('products.add_to_wishlist', [], 'Add to wishlist'),
            'addedToWishlist' => i18n_t('products.added_to_wishlist', [], 'Added to wishlist!'),
            'removedFromWishlist' => i18n_t('products.removed_from_wishlist', [], 'Removed from wishlist.'),
            'reviews' => i18n_t('products.reviews', [], 'reviews'),
            'reviewCustomerReviews' => i18n_t('products.review_customer_reviews', [], 'Customer Reviews'),
            'reviewWriteReview' => i18n_t('products.review_write_review', [], 'Write a Review'),
            'reviewRating' => i18n_t('products.review_rating', [], 'Rating'),
            'reviewStarsTemplate' => i18n_t('products.review_stars_template', [], '{count} stars'),
            'reviewLabel' => i18n_t('products.review_label', [], 'Review'),
            'reviewGuidance' => i18n_t('products.review_guidance', [], 'Useful pattern: Pros / Cons / Used with / Would you buy again?'),
            'reviewPlaceholder' => i18n_t('products.review_placeholder', [], "Pros:\nCons:\nUsed with: CPU, GPU, case or build type\nWould you recommend it?"),
            'reviewQnaPrompt' => i18n_t('products.review_qna_prompt', [], 'Questions about compatibility? Mention your CPU, GPU, motherboard, case, and PSU so Maroc PC can answer clearly.'),
            'reviewCancel' => i18n_t('products.review_cancel', [], 'Cancel'),
            'reviewSubmitReview' => i18n_t('products.review_submit_review', [], 'Submit Review'),
            'reviewLoadingReviews' => i18n_t('products.review_loading_reviews', [], 'Loading reviews...'),
            'reviewNoReviews' => i18n_t('products.review_no_reviews', [], 'No reviews yet. Be the first to review this product!'),
            'reviewVerifiedPurchase' => i18n_t('products.review_verified_purchase', [], 'Verified Purchase'),
            'reviewWasHelpful' => i18n_t('products.review_was_helpful', [], 'Was this helpful?'),
            'reviewHelpfulYes' => i18n_t('products.review_helpful_yes', [], 'Yes'),
            'reviewHelpfulNo' => i18n_t('products.review_helpful_no', [], 'No'),
            'reviewSubmitting' => i18n_t('products.review_submitting', [], 'Submitting...'),
            'reviewSubmitFailed' => i18n_t('products.review_submit_failed', [], 'Failed to submit review. You must be logged in.'),
            'reviewVoteFailed' => i18n_t('products.review_vote_failed', [], 'Failed to vote'),
            'reviewVoteLogin' => i18n_t('products.review_vote_login', [], 'Failed to vote. Please log in.'),
            'productDescriptionTemplate' => i18n_t('products.product_description', [], 'Premium {category} from {brand}. Built for enthusiasts who demand the best performance and reliability.'),
            'cartLoading' => i18n_t('products.cart_loading', [], 'Cart is still loading. Please try again.'),
            'sameCategoryCompare' => i18n_t('products.same_category_compare', [], 'You can only compare items of the same category.'),
            'compareLimit' => i18n_t('products.compare_limit', [], 'You can only compare up to 4 items.'),
            'best' => i18n_t('products.best', [], 'Best'),
            'product' => i18n_t('products.product', [], 'Product'),
            'comparisonLinkCopied' => i18n_t('products.comparison_link_copied', [], 'Comparison link copied to clipboard!'),
            'copyThisLink' => i18n_t('products.copy_this_link', [], 'Copy this link:'),
            'badgeNew' => i18n_t('products.new_badge', [], 'New'),
            'badgeHot' => i18n_t('products.hot_badge', [], 'Hot'),
            'badgeSale' => i18n_t('products.sale_badge', [], 'Sale'),
            'badgeLowStock' => i18n_t('products.low_stock_badge', [], 'Low Stock'),
            'badgeBestGaming' => i18n_t('products.best_gaming_badge', [], 'Best Gaming'),
            'badgeFlagship' => i18n_t('products.flagship_badge', [], 'Flagship'),
            'badgeAmdTop' => i18n_t('products.amd_top_badge', [], 'AMD Top'),
            'samReady' => i18n_t('products.sam_ready', [], 'SAM READY'),
            'samReadyTitle' => i18n_t('products.sam_ready_title', [], 'Smart Access Memory enabled with your selected AMD CPU'),
            'categoryCpu' => i18n_t('products.category_cpu', [], 'Processors'),
            'categoryGpu' => i18n_t('products.category_gpu', [], 'Graphics Cards'),
            'categoryRam' => i18n_t('products.category_ram', [], 'Memory / RAM'),
            'categoryMotherboard' => i18n_t('products.category_motherboard', [], 'Motherboards'),
            'categoryStorage' => i18n_t('products.category_storage', [], 'Storage'),
            'categoryCooling' => i18n_t('products.category_cooling', [], 'Cooling'),
            'categoryPsu' => i18n_t('products.category_psu', [], 'Power Supplies'),
            'categoryMonitor' => i18n_t('products.category_monitor', [], 'Monitors'),
            'categoryAccessories' => i18n_t('products.category_accessories', [], 'Accessories'),
            'categoryKeyboard' => i18n_t('products.category_keyboard', [], i18n_t('nav.keyboards', [], 'Keyboards')),
            'categoryMouse' => i18n_t('products.category_mouse', [], i18n_t('nav.mice', [], 'Mice')),
            'categoryVr' => i18n_t('products.category_vr', [], i18n_t('nav.vr_headsets', [], 'VR Headsets')),
            'categoryRouter' => i18n_t('products.category_router', [], i18n_t('nav.routers', [], 'Routers')),
            'categoryService' => i18n_t('products.category_service', [], 'Services'),
            'warranty3Year' => i18n_t('products.warranty_3_year', [], '3-year warranty'),
            'warrantyLifetime' => i18n_t('products.warranty_lifetime', [], 'Lifetime warranty'),
            'warranty5Year' => i18n_t('products.warranty_5_year', [], '5-year warranty'),
            'warranty7Year' => i18n_t('products.warranty_7_year', [], '7-year warranty'),
            'warranty2Year' => i18n_t('products.warranty_2_year', [], '2-year warranty'),
            'warrantyAccessory' => i18n_t('products.warranty_accessory', [], 'Accessory warranty'),
            'serviceGuarantee' => i18n_t('products.service_guarantee', [], 'Service guarantee'),
            'warrantyIncluded' => i18n_t('products.warranty_included', [], 'Warranty included'),
            'deliveryCasablanca' => i18n_t('products.delivery_casablanca', [], 'Casablanca 24-48h'),
            'restockAlert' => i18n_t('products.restock_alert', [], 'Restock alert'),
            'installmentsAvailable' => i18n_t('products.installments_available', [], 'Installments available'),
            'codCardTransfer' => i18n_t('products.cod_card_transfer', [], 'COD / card / transfer'),
            'assemblyEligible' => i18n_t('products.assembly_eligible', [], 'Assembly eligible'),
            'tagDeal' => i18n_t('products.tag_deal', [], 'Deal'),
            'tagCompareReady' => i18n_t('products.tag_compare_ready', [], 'Compare-ready'),
            'tagSocketMatch' => i18n_t('products.tag_socket_match', [], 'Socket match'),
            'tagWattageChecked' => i18n_t('products.tag_wattage_checked', [], 'Wattage checked'),
            'tagMemoryFinder' => i18n_t('products.tag_memory_finder', [], 'Memory finder'),
            'tagBuildHelper' => i18n_t('products.tag_build_helper', [], 'Build helper'),
            'tagPeripheral' => i18n_t('products.tag_peripheral', [], 'Peripheral'),
            'tagVrReady' => i18n_t('products.tag_vr_ready', [], 'VR ready'),
            'tagNetworkGear' => i18n_t('products.tag_network_gear', [], 'Network gear'),
            'diagnosticSheet' => i18n_t('products.diagnostic_sheet', [], 'Diagnostic Sheet'),
            'technicalSpecification' => i18n_t('products.technical_specification', [], 'Technical Specification'),
            'productDetailDescription' => i18n_t('products.product_detail_description', [], 'Engineering-grade {category} from {brand}, selected for performance, compatibility, and reliable build planning.'),
            'openBoxDeals' => i18n_t('products.open_box_deals', [], 'Open-box deals when available'),
            'assemblyPrep' => i18n_t('products.assembly_prep', [], 'Assembly Prep'),
            'inTheBox' => i18n_t('products.in_the_box', [], 'In The Box'),
            'included' => i18n_t('products.included', [], 'Included'),
            'checkBeforeAssembly' => i18n_t('products.check_before_assembly', [], 'Check before assembly'),
            'seenItCheaper' => i18n_t('products.seen_it_cheaper', [], 'Seen it cheaper?'),
            'sendToAdminReview' => i18n_t('products.send_to_admin_review', [], 'Send it to admin for review'),
            'alertBelow' => i18n_t('products.alert_below', [], 'Alert below'),
            'both' => i18n_t('products.both', [], 'Both'),
            'sameCategory' => i18n_t('products.same_category', [], 'Same category'),
            'relatedComponents' => i18n_t('products.related_components', [], 'Related Components'),
            'addCompetitorOrPrice' => i18n_t('products.add_competitor_or_price', [], 'Add a competitor link or the lower price you saw.'),
            'sending' => i18n_t('products.sending', [], 'Sending'),
            'couldNotSendRequest' => i18n_t('products.could_not_send_request', [], 'Could not send request.'),
            'priceMatchSent' => i18n_t('products.price_match_sent', [], 'Request sent to the admin price-match queue.'),
            'enterValidTargetPrice' => i18n_t('products.enter_valid_target_price', [], 'Enter a valid target price.'),
            'creatingAlert' => i18n_t('products.creating_alert', [], 'Creating alert'),
            'couldNotCreateAlert' => i18n_t('products.could_not_create_alert', [], 'Could not create alert.'),
            'alertArmedBelow' => i18n_t('products.alert_armed_below', [], 'Alert armed below {price}.'),
            'priceAlertCreated' => i18n_t('products.price_alert_created', [], 'Price alert created.'),
            'bundleItemsAdded' => i18n_t('products.bundle_items_added', [], 'Added {count} bundle items.'),
            'starsAndUp' => i18n_t('products.stars_and_up', [], '{rating}+ Stars'),
            'stockOut' => i18n_t('products.stock_out', [], '[STOCK: OUT]'),
            'stockCritical' => i18n_t('products.stock_critical', [], '[STOCK: CRITICAL - {count} LEFT]'),
            'stockLow' => i18n_t('products.stock_low', [], '[STOCK: LOW - {count} UNITS]'),
            'stockUnits' => i18n_t('products.stock_units', [], '[STOCK: {count} UNITS]'),
            'serviceAssembly' => i18n_t('products.service_assembly', [], 'Professional PC Assembly'),
            'serviceBios' => i18n_t('products.service_bios', [], 'BIOS Update'),
            'serviceStress' => i18n_t('products.service_stress', [], 'Stress Test Report'),
            'buildService' => i18n_t('products.build_service', [], 'Build service'),
            'boxCpuOnly' => i18n_t('products.box_cpu_only', [], 'CPU only'),
            'boxWarrantyCard' => i18n_t('products.box_warranty_card', [], 'Warranty card'),
            'boxStockCooler' => i18n_t('products.box_stock_cooler', [], 'Stock cooler'),
            'warnNoStockCooler' => i18n_t('products.warn_no_stock_cooler', [], 'No stock cooler expected. Add compatible cooling before checkout.'),
            'accessoryCpuCoolers' => i18n_t('products.accessory_cpu_coolers', [], 'CPU coolers'),
            'accessoryThermalPaste' => i18n_t('products.accessory_thermal_paste', [], 'Thermal paste'),
            'boxIoShield' => i18n_t('products.box_io_shield', [], 'I/O shield'),
            'boxM2Screws' => i18n_t('products.box_m2_screws', [], 'M.2 screws'),
            'boxQuickStart' => i18n_t('products.box_quick_start', [], 'Quick start guide'),
            'boxWifiAntenna' => i18n_t('products.box_wifi_antenna', [], 'Wi-Fi antenna'),
            'warnLimitedSata' => i18n_t('products.warn_limited_sata', [], 'Most boards include only a limited number of SATA cables.'),
            'accessorySataCable' => i18n_t('products.accessory_sata_cable', [], 'SATA cable'),
            'boxGraphicsCard' => i18n_t('products.box_graphics_card', [], 'Graphics card'),
            'boxSupportWarranty' => i18n_t('products.box_support_warranty', [], 'Support/warranty insert'),
            'warnPsuConnector' => i18n_t('products.warn_psu_connector', [], 'Check PSU connector support before assembly.'),
            'accessoryPcieAdapter' => i18n_t('products.accessory_pcie_adapter', [], 'PCIe power adapter'),
            'boxDriveOnly' => i18n_t('products.box_drive_only', [], 'Drive only'),
            'warnSataDataCable' => i18n_t('products.warn_sata_data_cable', [], 'SATA drives may not include a data cable.'),
            'accessoryM2Heatsink' => i18n_t('products.accessory_m2_heatsink', [], 'M.2 heatsink'),
            'boxCooler' => i18n_t('products.box_cooler', [], 'Cooler'),
            'boxMountingHardware' => i18n_t('products.box_mounting_hardware', [], 'Mounting hardware'),
            'boxPowerSupply' => i18n_t('products.box_power_supply', [], 'Power supply'),
            'boxAcCable' => i18n_t('products.box_ac_cable', [], 'AC cable'),
            'boxModularCableSet' => i18n_t('products.box_modular_cable_set', [], 'Modular cable set'),
            'warnGpuCableCount' => i18n_t('products.warn_gpu_cable_count', [], 'Verify GPU cable count before choosing high-end RTX cards.'),
            'boxRetailUnit' => i18n_t('products.box_retail_unit', [], 'Retail unit'),
            'boxBasicDocumentation' => i18n_t('products.box_basic_documentation', [], 'Basic documentation'),
            'specPcie5' => i18n_t('products.spec_pcie5', [], 'Doubles PCIe 4.0 bandwidth for next-gen GPUs and NVMe drives.'),
            'specPcie4' => i18n_t('products.spec_pcie4', [], 'Current mainstream high-speed link for GPUs and NVMe SSDs.'),
            'specDdr5' => i18n_t('products.spec_ddr5', [], 'Newer memory standard with higher bandwidth than DDR4.'),
            'specDdr4' => i18n_t('products.spec_ddr4', [], 'Older memory standard, good value but limited upgrade headroom.'),
            'specAm5' => i18n_t('products.spec_am5', [], 'AMD socket with stronger forward upgrade path than AM4.'),
            'specAm4' => i18n_t('products.spec_am4', [], 'Mature AMD socket with excellent budget value.'),
            'specLga1700' => i18n_t('products.spec_lga1700', [], 'Intel socket used by 12th, 13th, and 14th gen CPUs.'),
            'specLga1851' => i18n_t('products.spec_lga1851', [], 'Newer Intel desktop socket for Core Ultra processors.'),
            'specZen5' => i18n_t('products.spec_zen5', [], 'AMD architecture focused on efficiency and gaming/workstation gains.'),
            'specBlackwell' => i18n_t('products.spec_blackwell', [], 'NVIDIA RTX 50 generation architecture.'),
            'specGddr7' => i18n_t('products.spec_gddr7', [], 'Newest GPU memory generation with very high bandwidth.'),
            'specNvme' => i18n_t('products.spec_nvme', [], 'Fast SSD protocol that connects through PCIe lanes.'),
            'ptInitializing' => i18n_t('page_transitions.initializing', [], '> INITIALIZING...'),
            'ptRebooting' => i18n_t('page_transitions.rebooting', [], '> REBOOTING...'),
            'ptMountingKernel' => i18n_t('page_transitions.mounting_kernel', [], '> MOUNTING KERNEL...'),
            'ptAllocatingMemory' => i18n_t('page_transitions.allocating_memory', [], '> ALLOCATING MEMORY...'),
            'ptEstablishingUplink' => i18n_t('page_transitions.establishing_uplink', [], '> ESTABLISHING SECURE UPLINK...'),
            'ptLoadingProtocols' => i18n_t('page_transitions.loading_protocols', [], '> LOADING MAROC PC PROTOCOLS...'),
            'ptSystemReady' => i18n_t('page_transitions.system_ready', [], '> SYSTEM READY.'),
            'urls' => [
                'login' => i18n_url('login.php'),
                'signup' => i18n_url('signup.php'),
                'adminLogin' => i18n_url('adminlogin.php'),
                'account' => i18n_url('account.php'),
                'orders' => i18n_url('account.php?tab=orders'),
                'logout' => i18n_url('logout.php'),
                'products' => i18n_url('products.php'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.__storePageTitle = <?= json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.addEventListener('pageshow', () => {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('product')) document.title = window.__storePageTitle;
        });
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('product')) document.title = window.__storePageTitle;
        });
    </script>

    <!-- Fonts: preload critical font files -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&family=Space+Mono&family=Syne:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&family=Space+Mono&family=Syne:wght@400;700&display=swap"></noscript>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Common CSS -->
    <link rel="stylesheet" href="assets/css/index.css?v=mobile-dock-2">
    <link rel="stylesheet" href="assets/css/auth-nav.css?v=rtl-account-menu-2">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">

    <!-- Page-specific CSS -->
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>

    <!-- Theme initialization (render-blocking, must be early) -->
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>

    <!-- Currency formatter with i18n support (must load before other scripts) -->
    <script src="assets/js/currency.js?v=6"></script>
    <script src="assets/js/mobile-header.js?v=mobile-dock-2" defer></script>

    <!-- Page-specific head scripts (deferred) -->
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= htmlspecialchars($js, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>
<?php
}
