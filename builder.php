<?php
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();

require_once 'config.php';
require_once __DIR__ . '/includes/store-sidebar.php';

$builderLocale = i18n_current_locale();
$builderDir = i18n_direction($builderLocale);
$builderCurrency = $builderLocale === 'ar' ? 'د.م.' : 'DH';
$builderMoney = static fn(string $amount): string => '<span class="notranslate money-token" translate="no">' . htmlspecialchars($amount . ' ' . $builderCurrency, ENT_QUOTES, 'UTF-8') . '</span>';
$builderPhraseMap = i18n_builder_phrase_map($builderLocale);
$builderAttr = static fn(string $text): string => htmlspecialchars($builderPhraseMap[$text] ?? $text, ENT_QUOTES, 'UTF-8');
$builderJsI18n = [
    'addToCart' => $builderPhraseMap['Add to Cart'] ?? 'Add to Cart',
    'cartAddedTemplate' => $builderPhraseMap['{name} added to cart!'] ?? '{name} added to cart!',
    'cartCouldNotAdd' => $builderPhraseMap['This product could not be added. Please refresh and try again.'] ?? 'This product could not be added. Please refresh and try again.',
    // Auth nav translations
    'account' => $builderPhraseMap['Account'] ?? 'Account',
    'accountAccess' => $builderPhraseMap['Account access'] ?? 'Account access',
    'customerLogin' => $builderPhraseMap['Customer login'] ?? 'Customer login',
    'customerLoginHint' => $builderPhraseMap['Orders, wishlist, purchases'] ?? 'Orders, wishlist, purchases',
    'createAccount' => $builderPhraseMap['Create account'] ?? 'Create account',
    'createAccountHint' => $builderPhraseMap['New Maroc PC profile'] ?? 'New Maroc PC profile',
    'adminAccess' => $builderPhraseMap['Admin access'] ?? 'Admin access',
    'adminAccessHint' => $builderPhraseMap['Inventory and order tools'] ?? 'Inventory and order tools',
    'moreSignInOptions' => $builderPhraseMap['More sign-in options'] ?? 'More sign-in options',
    'myAccount' => $builderPhraseMap['My Account'] ?? 'My Account',
    'myOrders' => $builderPhraseMap['My Orders'] ?? 'My Orders',
    'logout' => $builderPhraseMap['Logout'] ?? 'Logout',
    // Community builds translations
    'cbViews' => $builderPhraseMap['views'] ?? 'views',
    'cbUpvotes' => $builderPhraseMap['upvotes'] ?? 'upvotes',
    'cbFavorites' => $builderPhraseMap['favorites'] ?? 'favorites',
    'cbBuildComponents' => $builderPhraseMap['Build Components'] ?? 'Build Components',
    'cbTotalPrice' => $builderPhraseMap['Total Price:'] ?? 'Total Price:',
    'cbFailedLoad' => $builderPhraseMap['Failed to load build details.'] ?? 'Failed to load build details.',
    'cbNoComponents' => $builderPhraseMap['This build has no components.'] ?? 'This build has no components.',
    'cbPleaseLogin' => $builderPhraseMap['Please log in to publish your build.'] ?? 'Please log in to publish your build.',
    'cbPleaseLoginInteract' => $builderPhraseMap['Please log in to upvote or favorite community builds.'] ?? 'Please log in to upvote or favorite community builds.',
    'cbUpvoteAdded' => $builderPhraseMap['Upvote added!'] ?? 'Upvote added!',
    'cbUpvoteRemoved' => $builderPhraseMap['Upvote removed.'] ?? 'Upvote removed.',
    'cbFavoriteAdded' => $builderPhraseMap['Favorite added!'] ?? 'Favorite added!',
    'cbFavoriteRemoved' => $builderPhraseMap['Favorite removed.'] ?? 'Favorite removed.',
    'cbPleaseGiveName' => $builderPhraseMap['Please give your build a name.'] ?? 'Please give your build a name.',
    'cbSelectComponent' => $builderPhraseMap['Select at least one component before publishing.'] ?? 'Select at least one component before publishing.',
    'cbPublished' => $builderPhraseMap['Your build has been published! 🎉'] ?? 'Your build has been published! 🎉',
    'cbPublishFailed' => $builderPhraseMap['Failed to publish build.'] ?? 'Failed to publish build.',
    'cbLoading' => $builderPhraseMap['Loading community builds...'] ?? 'Loading community builds...',
    'cbNoDescription' => $builderPhraseMap['No description provided.'] ?? 'No description provided.',
    'cbAnonymous' => $builderPhraseMap['Anonymous'] ?? 'Anonymous',
    'cbUpvote' => $builderPhraseMap['Upvote'] ?? 'Upvote',
    'cbFavorite' => $builderPhraseMap['Favorite'] ?? 'Favorite',
    // Category translations
    'catCpu' => $builderPhraseMap['CPU'] ?? 'CPU',
    'catMotherboard' => $builderPhraseMap['MOTHERBOARD'] ?? 'MOTHERBOARD',
    'catGpu' => $builderPhraseMap['GPU'] ?? 'GPU',
    'catRam' => $builderPhraseMap['RAM'] ?? 'RAM',
    'catStorage' => $builderPhraseMap['STORAGE'] ?? 'STORAGE',
    'catPsu' => $builderPhraseMap['PSU'] ?? 'PSU',
    'catCase' => $builderPhraseMap['CASE'] ?? 'CASE',
    'catCooling' => $builderPhraseMap['COOLING'] ?? 'COOLING',
    'catMonitor' => $builderPhraseMap['MONITOR'] ?? 'MONITOR',
    'catAccessories' => $builderPhraseMap['ACCESSORIES'] ?? 'ACCESSORIES',
    // Case visualization
    'notInstalled' => $builderPhraseMap['Not Installed'] ?? 'Not Installed',
    'buildComponentSlots' => $builderPhraseMap['Build component slots'] ?? 'Build component slots',
    // Preset build labels
    'Base Build' => $builderPhraseMap['Base Build'] ?? 'Base Build',
    'Advanced Build' => $builderPhraseMap['Advanced Build'] ?? 'Advanced Build',
    'Power Build' => $builderPhraseMap['Power Build'] ?? 'Power Build',
    'Legacy Enthusiast' => $builderPhraseMap['Legacy Enthusiast'] ?? 'Legacy Enthusiast',
    'START WITH {label}' => $builderPhraseMap['START WITH {label}'] ?? 'START WITH {label}',
    'aiBuilder' => [
        'title' => $builderPhraseMap['BUILDER_COPILOT v3.0'] ?? 'BUILDER_COPILOT v3.0',
        'welcome' => $builderPhraseMap['Build assistant ready. I can inspect your selected parts, plan the next slot, check wattage, optimize budget, and recommend services.'] ?? 'Build assistant ready. I can inspect your selected parts, plan the next slot, check wattage, optimize budget, and recommend services.',
        'placeholder' => $builderPhraseMap['Ask about this build, compatibility, budget, or upgrades...'] ?? 'Ask about this build, compatibility, budget, or upgrades...',
        'liveContext' => $builderPhraseMap['Live build context'] ?? 'Live build context',
        'selected' => $builderPhraseMap['selected'] ?? 'selected',
        'total' => $builderPhraseMap['total'] ?? 'total',
        'draw' => $builderPhraseMap['draw'] ?? 'draw',
        'psu' => $builderPhraseMap['PSU'] ?? 'PSU',
        'missing' => $builderPhraseMap['Missing'] ?? 'Missing',
        'none' => $builderPhraseMap['none'] ?? 'none',
        'quickActions' => [
            [
                'prompt' => $builderPhraseMap['Analyze my current build and tell me the next best action.'] ?? 'Analyze my current build and tell me the next best action.',
                'label' => $builderPhraseMap['Analyze'] ?? 'Analyze',
                'icon' => 'fa-stethoscope',
            ],
            [
                'prompt' => $builderPhraseMap['What should I choose next for this build?'] ?? 'What should I choose next for this build?',
                'label' => $builderPhraseMap['Next part'] ?? 'Next part',
                'icon' => 'fa-forward-step',
            ],
            [
                'prompt' => $builderPhraseMap['Check compatibility, wattage, cooling, and missing parts.'] ?? 'Check compatibility, wattage, cooling, and missing parts.',
                'label' => $builderPhraseMap['Full check'] ?? 'Full check',
                'icon' => 'fa-shield-halved',
            ],
            [
                'prompt' => $builderPhraseMap['Optimize this build for my budget without wasting money.'] ?? 'Optimize this build for my budget without wasting money.',
                'label' => $builderPhraseMap['Optimize'] ?? 'Optimize',
                'icon' => 'fa-scale-balanced',
            ],
            [
                'prompt' => $builderPhraseMap['Recommend a balanced gaming PC build around 18000 {currency}.'] ?? 'Recommend a balanced gaming PC build around 18000 {currency}.',
                'label' => $builderPhraseMap['Gaming build'] ?? 'Gaming build',
                'icon' => 'fa-gamepad',
            ],
            [
                'prompt' => $builderPhraseMap['Which services should I add before checkout?'] ?? 'Which services should I add before checkout?',
                'label' => $builderPhraseMap['Services'] ?? 'Services',
                'icon' => 'fa-screwdriver-wrench',
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($builderLocale, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars($builderDir, ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $builderAttr('Builder — Maroc PC') ?></title>
    <meta name="description" content="<?= $builderAttr('Build your dream PC with our interactive configurator. Choose compatible components, check wattage, and share your build.') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css?v=builder-ai-send-1">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/builder.css?v=mobile-ux-1">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <script src="assets/js/page-transitions.js"></script>
</head>

<body>

    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="<?= $builderAttr('Open menu') ?>">
                <span></span><span></span><span></span>
            </button>

            <!-- Logo -->
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>

            <!-- Nav links -->
            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?= $builderAttr('Home') ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?= $builderAttr('Components') ?></a>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle active" aria-haspopup="true" aria-expanded="false">
                        <?= $builderAttr('Builder Tools') ?> <span class="chevron">▾</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="builder.php" class="dropdown-item"><?= $builderAttr('PC Build Wizard') ?></a>
                        <a href="builder.php?tab=gaming-finder" class="dropdown-item"><?= $builderAttr('Gaming PC Finder') ?></a>
                        <a href="laptop-finder.php" class="dropdown-item"><?= $builderAttr('Laptop Finder') ?></a>
                        <a href="builder.php?tab=psu-calculator" class="dropdown-item"><?= $builderAttr('Power Supply Calculator') ?></a>
                        <a href="builder.php?tab=memory-finder" class="dropdown-item"><?= $builderAttr('Memory Finder') ?></a>
                        <a href="tools.php" class="dropdown-item"><?= $builderAttr('Tools Cockpit') ?></a>
                    </div>
                </div>
                <a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?= $builderAttr('Deals') ?></a>
                <a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?= $builderAttr('Contact') ?></a>
            </nav>

            <div style="flex:1"></div>

            <!-- Theme toggle -->
            <button class="theme-toggle" id="themeToggle" aria-label="<?= $builderAttr('Toggle theme') ?>">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <?= i18n_language_switcher('nav-translate') ?>

            <!-- User icon -->
            <div class="cart-wrapper" id="userNav">
                <a href="login.php" class="cart-icon" aria-label="<?= $builderAttr('Account') ?>">
                    <i class="fas fa-user"></i>
                </a>
            </div>

            <!-- Cart icon -->
            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?= $builderAttr('Shopping cart') ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <main class="builder-page">
        <div class="builder-container">

            <!-- Hero -->
            <div class="builder-hero builder-hero-premium animate-on-scroll">
                <div class="builder-hero-copy">
                    <span class="eyebrow"><i class="fas fa-cogs"></i> <?= $builderAttr('Builder Tools') ?></span>
                    <h1><?= $builderAttr('Tools That Build Smarter') ?></h1>
                    <p><?= $builderAttr('Find the right parts, calculate power needs, match memory, and configure a complete PC in one place.') ?></p>
                    <div class="builder-hero-actions">
                        <a href="#buildStartChoice" class="btn btn-primary"><?= $builderAttr('Start a Build') ?></a>
                        <a href="#builderToolsTitle" class="btn btn-secondary"><?= $builderAttr('Explore Tools') ?></a>
                    </div>
                </div>
                <aside class="builder-hero-guide">
                    <span class="builder-guide-label"><?= $builderAttr('Best way to begin') ?></span>
                    <h2><?= $builderAttr('Choose a path, then let the tools narrow the complexity') ?></h2>
                    <ul class="builder-guide-list">
                        <li>
                            <strong><?= $builderAttr('New to building?') ?></strong>
                            <span><?= $builderAttr('Start with pre-built recommendations, then customize safely.') ?></span>
                        </li>
                        <li>
                            <strong><?= $builderAttr('Know your parts?') ?></strong>
                            <span><?= $builderAttr('Jump into the custom builder with compatibility and wattage guidance.') ?></span>
                        </li>
                        <li>
                            <strong><?= $builderAttr('Need quick validation?') ?></strong>
                            <span><?= $builderAttr('Use Gaming Finder, PSU Calculator, and Memory Finder without leaving the workspace.') ?></span>
                        </li>
                    </ul>
                </aside>
            </div>

            <section class="builder-trust-strip animate-on-scroll" aria-label="<?= $builderAttr('Builder trust highlights') ?>">
                <article>
                    <strong><?= $builderAttr('Compatibility-aware') ?></strong>
                    <span><?= $builderAttr('Guides selections with platform and power context.') ?></span>
                </article>
                <article>
                    <strong><?= $builderAttr('Budget visible') ?></strong>
                    <span><?= $builderAttr('Keep total cost, wattage, and missing parts in view.') ?></span>
                </article>
                <article>
                    <strong><?= $builderAttr('Multiple entry points') ?></strong>
                    <span><?= $builderAttr('Pick the shortest path instead of learning every tool at once.') ?></span>
                </article>
            </section>

            <section class="build-start-choice animate-on-scroll" id="buildStartChoice" aria-labelledby="buildStartTitle">
                <div class="build-start-head">
                    <span class="gf-kicker"><i class="fas fa-compass"></i> <?= $builderAttr('Choose your path') ?></span>
                    <h2 id="buildStartTitle"><?= $builderAttr('How do you want to start?') ?></h2>
                    <p><?= $builderAttr('Pick the simplest route for your confidence level. You can still switch tools later without losing direction.') ?></p>
                </div>
                <div class="start-choice-grid">
                    <button class="start-choice-card recommended" type="button" onclick="PCBuilder.chooseBuilderPath('prebuilt')">
                        <span class="start-choice-badge"><?= $builderAttr('Recommended for most people') ?></span>
                        <i class="fas fa-layer-group"></i>
                        <strong><?= $builderAttr('Pre-built recommendations') ?></strong>
                        <span><?= $builderAttr('Start from a balanced base, advanced, or power build and tweak it after.') ?></span>
                    </button>
                    <button class="start-choice-card" type="button" onclick="PCBuilder.chooseBuilderPath('custom')">
                        <span class="start-choice-badge"><?= $builderAttr('Best for confident builders') ?></span>
                        <i class="fas fa-screwdriver-wrench"></i>
                        <strong><?= $builderAttr('Custom build') ?></strong>
                        <span><?= $builderAttr('Pick every component yourself with compatibility and wattage guidance.') ?></span>
                    </button>
                </div>
            </section>

            <section class="builder-tools-hub animate-on-scroll" aria-labelledby="builderToolsTitle">
                <div class="bth-head">
                    <span class="gf-kicker"><i class="fas fa-toolbox"></i> <?= $builderAttr('Core tools') ?></span>
                    <h2 id="builderToolsTitle"><?= $builderAttr('Build, find, and configure') ?></h2>
                    <p><?= $builderAttr('Use the main builder for full control, or jump into a focused tool when you only need one answer fast.') ?></p>
                </div>
                <div class="bth-grid">
                    <button class="bth-card featured active" onclick="switchToolTab('tab-pc-builder', this)">
                        <span><?= $builderAttr('Most Popular') ?></span>
                        <i class="fas fa-screwdriver-wrench"></i>
                        <strong><?= $builderAttr('PC Builder') ?></strong>
                        <em><?= $builderAttr('Design your build from scratch with compatibility checks and saved quotes.') ?></em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-gaming-finder', this)">
                        <i class="fas fa-gamepad"></i>
                        <strong><?= $builderAttr('Gaming PC Finder') ?></strong>
                        <em><?= $builderAttr('Pick games, resolution, FPS target, and budget to get a matched build.') ?></em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-psu-calculator', this)">
                        <i class="fas fa-plug-circle-bolt"></i>
                        <strong><?= $builderAttr('Power Supply Calculator') ?></strong>
                        <em><?= $builderAttr('Estimate wattage with upgrade headroom and shop matching PSUs.') ?></em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-memory-finder', this)">
                        <i class="fas fa-memory"></i>
                        <strong><?= $builderAttr('Memory Finder') ?></strong>
                        <em><?= $builderAttr('Find RAM that matches your CPU platform, motherboard, and workload.') ?></em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-community-builds', this)">
                        <i class="fas fa-users"></i>
                        <strong><?= $builderAttr('Community Builds') ?></strong>
                        <em><?= $builderAttr('Browse builds shared by the community, upvote your favorites, or publish your own.') ?></em>
                    </button>
                </div>
            </section>

            <!-- Tabbed Content Areas -->
            <div id="tab-pc-builder" class="tool-tab-content active">
                <div id="pcConfigurator" class="configurator-anchor"></div>

                <div class="builder-workspace is-hidden" id="pcBuilderWorkspace">
                
                <!-- Focus / Expert Mode Toggle -->
                <div class="workspace-mode-selector animate-on-scroll">
                    <div class="mode-toggle-container">
                        <span class="mode-label active" id="modeLabelFocus" onclick="PCBuilder.setWorkspaceMode('focus')">
                            <i class="fas fa-eye"></i> <?= $builderAttr('Focus Mode') ?>
                        </span>
                        <label class="mode-switch">
                            <input type="checkbox" id="workspaceModeToggle" onchange="PCBuilder.toggleWorkspaceMode()">
                            <span class="mode-slider"></span>
                        </label>
                        <span class="mode-label" id="modeLabelExpert" onclick="PCBuilder.setWorkspaceMode('expert')">
                            <i class="fas fa-gauge-high"></i> <?= $builderAttr('Expert Mode') ?>
                        </span>
                    </div>
                    <div class="mode-description" id="modeDescription">
                        <?= $builderAttr('Guided step-by-step assistant tailored for your build path.') ?>
                    </div>
                </div>
                
                <!-- Platform Selector -->
                <div class="platform-selector animate-on-scroll">
                    <h4 class="ps-title"><?= $builderAttr('Start by selecting your platform') ?></h4>
                    <div class="ps-grid">
                        <button class="ps-card active" data-platform="intel" onclick="PCBuilder.setPlatform('intel')">
                            <div class="ps-logo"><i class="fab fa-intel"></i></div>
                            <div class="ps-info">
                                <strong>Intel Core</strong>
                                <span>LGA 1700 / 1851</span>
                            </div>
                        </button>
                        <button class="ps-card" data-platform="amd" onclick="PCBuilder.setPlatform('amd')">
                            <div class="ps-logo">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--cyan)" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 0L24 12L12 24L0 12L12 0Z"/>
                                </svg>
                            </div>
                            <div class="ps-info">
                                <strong>AMD Ryzen</strong>
                                <span>Socket AM4 / AM5</span>
                            </div>
                        </button>
                        <!-- Legacy platform removed to avoid novice traps -->
                    </div>
                </div>

                <div class="use-case-bar" id="useCaseBar"></div>

                <!-- Active Preset Banner -->
                <div class="active-preset-banner" id="activePresetBanner" style="display: none;"></div>

                <!-- Wizard Steps -->
                <div class="wizard-steps animate-on-scroll" id="wizardSteps"></div>
                <div class="build-guide-bar animate-on-scroll" id="buildGuideBar" aria-live="polite"></div>

                <!-- Main Grid -->
                <div class="builder-grid">

                    <!-- Left: Component Selection -->
                    <div class="component-panel animate-on-scroll" id="componentPanel">
                        <!-- Populated by JS -->
                    </div>
                    <!-- Right: Build Summary -->
                    <aside class="build-summary animate-on-scroll">
                        <input type="text" class="build-name-input" id="buildNameInput" placeholder="<?= $builderAttr('Name your build...') ?>" value="<?= $builderAttr('My Build') ?>">

                        <!-- Accordion 1: Build Summary -->
                        <details class="sidebar-accordion" id="accordion-summary" open>
                            <summary class="accordion-header">
                                <h3><i class="fas fa-list-check"></i> <?= $builderAttr('Build Summary') ?></h3>
                                <span class="accordion-chevron"><i class="fas fa-chevron-down"></i></span>
                            </summary>
                            <div class="accordion-content">
                                <div class="pc-case-viz" id="summaryItems" role="group" aria-label="<?= $builderAttr('Build component slots') ?>">
                                    <div class="case-shell">
                                        <div class="case-screws" aria-hidden="true"></div>
                                        <div class="case-slots" id="caseSlots"><!-- JS populated --></div>
                                    </div>
                                    <div class="case-services-strip" id="caseServicesStrip"></div>
                                </div>
                            </div>
                        </details>

                        <!-- Accordion 2: Power Supply & Thermals -->
                        <details class="sidebar-accordion" id="accordion-wattage" open>
                            <summary class="accordion-header">
                                <h3><i class="fas fa-bolt"></i> <?= $builderAttr('Power & Thermals') ?></h3>
                                <span class="accordion-chevron"><i class="fas fa-chevron-down"></i></span>
                            </summary>
                            <div class="accordion-content">
                                <div class="wattage-meter">
                                    <div class="wm-header">
                                        <span class="wm-label"><i class="fas fa-bolt"></i> <?= $builderAttr('Power Draw') ?></span>
                                        <span class="wm-value" id="wattageValue">0W / ???</span>
                                    </div>
                                    <div class="wattage-bar">
                                        <div class="wattage-fill" id="wattageFill" style="width: 0%"></div>
                                    </div>
                                    <div class="wm-recommendation" id="wattageRec"><?= $builderAttr('Select components to see recommendation') ?></div>
                                </div>
                            </div>
                        </details>

                        <!-- Accordion 3: Build Diagnostics -->
                        <details class="sidebar-accordion" id="accordion-diagnostics">
                            <summary class="accordion-header">
                                <h3><i class="fas fa-shield-halved"></i> <?= $builderAttr('Diagnostics') ?></h3>
                                <span class="accordion-chevron"><i class="fas fa-chevron-down"></i></span>
                            </summary>
                            <div class="accordion-content">
                                <div class="compatibility-panel" id="compatibilityPanel"><!-- Populated by JS --></div>
                                <div class="bottleneck-panel" id="bottleneckPanel"><!-- Populated by JS --></div>
                                <div class="health-panel" id="healthPanel"><!-- Populated by JS --></div>
                                <div class="smart-checklist-panel" id="smartChecklistPanel"><!-- Populated by JS --></div>
                                <div class="assembly-guide-panel" id="assemblyGuidePanel"><!-- Populated by JS --></div>
                            </div>
                        </details>

                        <!-- Accordion 4: Services -->
                        <details class="sidebar-accordion" id="accordion-services">
                            <summary class="accordion-header">
                                <h3><i class="fas fa-screwdriver-wrench"></i> <?= $builderAttr('Services') ?></h3>
                                <span class="accordion-chevron"><i class="fas fa-chevron-down"></i></span>
                            </summary>
                            <div class="accordion-content">
                                <div class="build-services">
                                    <label class="service-option">
                                        <input type="checkbox" class="service-checkbox" value="assembly">
                                        <span>
                                            <strong><?= $builderAttr('Professional assembly') ?></strong>
                                            <small><?= $builderAttr('Clean cable management and full installation') ?></small>
                                        </span>
                                        <em><?= $builderMoney('299') ?></em>
                                    </label>
                                    <label class="service-option">
                                        <input type="checkbox" class="service-checkbox" value="bios">
                                        <span>
                                            <strong><?= $builderAttr('BIOS update') ?></strong>
                                            <small><?= $builderAttr('Ready for latest CPUs and memory profiles') ?></small>
                                        </span>
                                        <em><?= $builderMoney('99') ?></em>
                                    </label>
                                    <label class="service-option">
                                        <input type="checkbox" class="service-checkbox" value="stress">
                                        <span>
                                            <strong><?= $builderAttr('Stress test report') ?></strong>
                                            <small><?= $builderAttr('Thermals, stability, and PSU load checked') ?></small>
                                        </span>
                                        <em><?= $builderMoney('149') ?></em>
                                    </label>
                                    <label class="service-option">
                                        <input type="checkbox" class="service-checkbox" value="windows">
                                        <span>
                                            <strong><?= $builderAttr('Windows install') ?></strong>
                                            <small><?= $builderAttr('Drivers and updates prepared') ?></small>
                                        </span>
                                        <em><?= $builderMoney('199') ?></em>
                                    </label>
                                    <label class="service-option">
                                        <input type="checkbox" class="service-checkbox" value="bazzite">
                                        <span>
                                            <strong><?= $builderAttr('Bazzite + Proton++ install') ?></strong>
                                            <small><?= $builderAttr('Gaming Linux setup with Steam, Proton, and controller support') ?></small>
                                        </span>
                                        <em><?= $builderMoney('249') ?></em>
                                    </label>
                                </div>
                            </div>
                        </details>

                        <!-- Total -->
                        <div class="build-total">
                            <span class="bt-label"><?= $builderAttr('Total') ?></span>
                            <span class="bt-price notranslate money-token" translate="no" id="totalPrice"><?= htmlspecialchars('0.00 ' . $builderCurrency, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <!-- Actions -->
                        <div class="build-actions">
                            <button class="btn-build btn-add-all" id="addAllBtn" disabled onclick="PCBuilder.addAllToCart()">
                                <i class="fas fa-cart-plus"></i> <?= $builderAttr('Add All to Cart') ?>
                            </button>
                            <button class="btn-build btn-save-build" onclick="PCBuilder.saveBuild()">
                                <i class="fas fa-save"></i> <?= $builderAttr('Save & Share Build') ?>
                            </button>
                            <a class="btn-build btn-share-build" href="builds-compare.php">
                                <i class="fas fa-code-compare"></i> <?= $builderAttr('Compare Saved Builds') ?>
                            </a>
                            <button class="btn-build btn-share-build" onclick="PCBuilder.shareWhatsApp()">
                                <i class="fab fa-whatsapp"></i> <?= $builderAttr('Send on WhatsApp') ?>
                            </button>
                            <button class="btn-build btn-share-build" onclick="PCBuilder.exportQuote()">
                                <i class="fas fa-file-lines"></i> <?= $builderAttr('Export Quote') ?>
                            </button>
                            <button class="btn-build btn-auto-build" onclick="PCBuilder.autoBuild()">
                                <i class="fas fa-magic"></i> <?= $builderAttr('Auto-Build for Me') ?>
                            </button>
                        </div>
                    </aside>
                    </div>
                </div>
                
                <!-- Sticky Bottom Dock -->
                <div class="sticky-build-dock" id="stickyBuildDock">
                    <div class="sbd-container">
                        <div class="sbd-info">
                            <span class="sbd-name" id="stickyBuildName"><?= $builderAttr('My Build') ?></span>
                            <div class="sbd-stats">
                                <span id="stickyPartsCount"><?= $builderAttr('0 parts') ?></span>
                                <span class="sbd-separator">|</span>
                                <span id="stickyWattage"><?= $builderAttr('0W / 650W') ?></span>
                            </div>
                        </div>
                        <div class="sbd-price-action">
                            <div class="sbd-price notranslate money-token" translate="no" id="stickyTotalPrice"><?= htmlspecialchars('0.00 ' . $builderCurrency, ENT_QUOTES, 'UTF-8') ?></div>
                            <button class="btn-sbd-primary" onclick="PCBuilder.addAllToCart()">
                                <i class="fas fa-cart-plus"></i> <?= $builderAttr('Buy Now') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-gaming-finder" class="tool-tab-content">
                <!-- Gaming PC Finder -->
                <section class="gaming-finder animate-on-scroll" id="gamingFinder" aria-labelledby="gamingFinderTitle">
                    <div class="gf-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-crosshairs"></i> <?= $builderAttr('Gaming PC Finder') ?></span>
                            <h2 id="gamingFinderTitle"><?= $builderAttr('Match a build to your games') ?></h2>
                        </div>
                        <div class="gf-status" id="finderStatus"><?= $builderAttr('Catalog ready') ?></div>
                    </div>

                    <div class="gf-layout">
                        <div class="gf-panel">
                            <div class="gf-control">
                                <label><?= $builderAttr('Games') ?></label>
                                <div class="gf-game-grid" id="finderGames"></div>
                            </div>

                            <div class="gf-control-row">
                                <div class="gf-control">
                                    <label><?= $builderAttr('Resolution') ?></label>
                                    <div class="gf-segment" id="finderResolution">
                                        <button class="active" data-resolution="1080p">1080p</button>
                                        <button data-resolution="1440p">1440p</button>
                                        <button data-resolution="4K">4K</button>
                                    </div>
                                </div>
                                <div class="gf-control">
                                    <label><?= $builderAttr('Target FPS') ?></label>
                                    <div class="gf-segment" id="finderFps">
                                        <button data-fps="60">60</button>
                                        <button class="active" data-fps="120">120</button>
                                        <button data-fps="165">165</button>
                                    </div>
                                </div>
                            </div>

                            <div class="gf-budget">
                                <div class="gf-budget-top">
                                    <label for="finderBudget"><?= $builderAttr('Budget') ?></label>
                                    <strong class="notranslate money-token" translate="no" id="finderBudgetValue"><?= htmlspecialchars('18,000.00 ' . $builderCurrency, ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <input type="range" id="finderBudget" min="8000" max="32000" step="500" value="18000">
                            </div>

                            <div class="gf-actions">
                                <button class="btn-build btn-add-all" onclick="PCBuilder.applyGamingFinder()">
                                    <i class="fas fa-wand-magic-sparkles"></i> <?= $builderAttr('Find My Build') ?>
                                </button>
                                <button class="btn-build btn-share-build" onclick="PCBuilder.resetGamingFinder()">
                                    <i class="fas fa-rotate-left"></i> <?= $builderAttr('Reset') ?>
                                </button>
                            </div>
                        </div>

                        <aside class="gf-result" id="finderResult">
                            <!-- Populated by JS -->
                        </aside>
                    </div>
                </section>

                <section class="finder-faq animate-on-scroll" aria-labelledby="finderFaqTitle">
                    <div class="ff-head">
                        <span class="gf-kicker"><i class="fas fa-circle-question"></i> <?= $builderAttr('Quick answers') ?></span>
                        <h2 id="finderFaqTitle"><?= $builderAttr('Before you choose a gaming build') ?></h2>
                    </div>

                    <div class="ff-grid">
                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('How do I use the finder?') ?></h3>
                                <ol>
                                    <li><?= $builderAttr('Select up to 4 games.') ?></li>
                                    <li><?= $builderAttr('Choose your resolution and FPS target.') ?></li>
                                    <li><?= $builderAttr('Set your budget in') ?> <span class="notranslate money-token" translate="no"><?= htmlspecialchars($builderCurrency, ENT_QUOTES, 'UTF-8') ?></span>.</li>
                                    <li><?= $builderAttr('Click Find My Build, then edit any part in the wizard.') ?></li>
                                </ol>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('Which resolution should I pick?') ?></h3>
                                <p><?= $builderAttr('1080p is best for budget and high refresh rates, 1440p is the sweet spot for most gaming PCs, and 4K needs a stronger GPU for smooth ultra settings.') ?></p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('Can I select more than one game?') ?></h3>
                                <p><?= $builderAttr('Yes. Choose up to 4 titles and the finder will size the build around the most demanding game in your selection.') ?></p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('What does the FPS estimate mean?') ?></h3>
                                <p><?= $builderAttr('It is a practical catalog-based estimate using the selected CPU, GPU, games, and resolution. Real FPS can vary by settings, drivers, patches, and thermals.') ?></p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('Can I compare with my current PC?') ?></h3>
                                <p><?= $builderAttr('Use the component wizard to swap in parts similar to your current CPU or GPU, then compare the FPS panel against the recommended build.') ?></p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3><?= $builderAttr('Do I need a new monitor?') ?></h3>
                                <p><?= $builderAttr('Not always. A 1440p or 4K build shines most when your monitor supports that resolution and refresh rate, so match the PC to the screen you actually use.') ?></p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div id="tab-psu-calculator" class="tool-tab-content">
                <section class="builder-tool-panel animate-on-scroll" id="powerSupplyCalculator" aria-labelledby="psuCalcTitle">
                    <div class="btp-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-plug-circle-bolt"></i> <?= $builderAttr('Power Supply Calculator') ?></span>
                            <h2 id="psuCalcTitle"><?= $builderAttr('Calculate the right PSU wattage') ?></h2>
                        </div>
                        <button class="btn-build btn-share-build" onclick="PCBuilder.useCurrentBuildForPsu()">
                            <i class="fas fa-link"></i> <?= $builderAttr('Use Current Build') ?>
                        </button>
                    </div>

                    <div class="btp-layout">
                        <div class="tool-form-grid">
                            <label class="tool-field">
                                <span><?= $builderAttr('CPU') ?></span>
                                <select id="psuCpuSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('GPU') ?></span>
                                <select id="psuGpuSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Motherboard') ?></span>
                                <select id="psuMotherboardSelect">
                                    <option value="0"><?= $builderAttr('Not selected') ?></option>
                                    <option value="45">Mini-ITX</option>
                                    <option value="55">Micro-ATX</option>
                                    <option value="65">ATX</option>
                                    <option value="80"><?= $builderAttr('E-ATX / workstation') ?></option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Memory') ?></span>
                                <select id="psuRamSelect">
                                    <option value="0"><?= $builderAttr('Not selected') ?></option>
                                    <option value="8">16GB DDR4</option>
                                    <option value="10">32GB DDR4</option>
                                    <option value="12">32GB DDR5</option>
                                    <option value="18">64GB DDR5</option>
                                    <option value="28">128GB DDR5</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('SSD count') ?></span>
                                <input type="number" id="psuSsdCount" min="0" max="8" value="1">
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('HDD count') ?></span>
                                <input type="number" id="psuHddCount" min="0" max="8" value="0">
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Case fans') ?></span>
                                <input type="number" id="psuFanCount" min="0" max="12" value="3">
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Upgrade headroom') ?></span>
                                <select id="psuHeadroomSelect">
                                    <option value="1.15">15%</option>
                                    <option value="1.25" selected>25%</option>
                                    <option value="1.35">35%</option>
                                </select>
                            </label>
                        </div>

                        <aside class="tool-result" id="psuCalculatorResult">
                            <!-- Populated by JS -->
                        </aside>
                    </div>
                </section>
            </div>

            <div id="tab-memory-finder" class="tool-tab-content">
                <section class="builder-tool-panel animate-on-scroll" id="memoryFinder" aria-labelledby="memoryFinderTitle">
                    <div class="btp-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-memory"></i> <?= $builderAttr('Memory Finder') ?></span>
                            <h2 id="memoryFinderTitle"><?= $builderAttr('Find compatible RAM') ?></h2>
                        </div>
                        <button class="btn-build btn-share-build" onclick="PCBuilder.useCurrentBuildForMemory()">
                            <i class="fas fa-link"></i> <?= $builderAttr('Use Current Build') ?>
                        </button>
                    </div>

                    <div class="btp-layout">
                        <div class="tool-form-grid memory-tool-form">
                            <label class="tool-field">
                                <span><?= $builderAttr('CPU platform') ?></span>
                                <select id="memoryPlatformSelect">
                                    <option value=""><?= $builderAttr('Auto / not sure') ?></option>
                                    <option value="AM5">AMD AM5</option>
                                    <option value="LGA 1700">Intel LGA 1700</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Motherboard') ?></span>
                                <select id="memoryMotherboardSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Workload') ?></span>
                                <select id="memoryUseSelect">
                                    <option value="gaming"><?= $builderAttr('Gaming') ?></option>
                                    <option value="streaming"><?= $builderAttr('Gaming + streaming') ?></option>
                                    <option value="creator"><?= $builderAttr('Creator / render') ?></option>
                                    <option value="office"><?= $builderAttr('Office / study') ?></option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span><?= $builderAttr('Capacity') ?></span>
                                <select id="memoryCapacitySelect">
                                    <option value=""><?= $builderAttr('Any capacity') ?></option>
                                    <option value="16">16GB+</option>
                                    <option value="32" selected>32GB+</option>
                                    <option value="64">64GB+</option>
                                </select>
                            </label>
                        </div>

                        <aside class="tool-result memory-result" id="memoryFinderResult">
                            <!-- Populated by JS -->
                        </aside>
                    </div>
                </section>
            </div>

            <div id="tab-community-builds" class="tool-tab-content">
                <section class="builder-tool-panel animate-on-scroll" aria-labelledby="communityBuildsTitle">
                    <div class="btp-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-users"></i> <?= $builderAttr('Community Builds') ?></span>
                            <h2 id="communityBuildsTitle"><?= $builderAttr('Explore builds from the community') ?></h2>
                        </div>
                        <div class="cb-sort-controls">
                            <select id="cbSortSelect" class="cb-sort-select">
                                <option value="newest"><?= $builderAttr('Newest') ?></option>
                                <option value="popular"><?= $builderAttr('Most Popular') ?></option>
                            </select>
                            <button class="btn-build btn-add-all" id="cbPublishBtn" onclick="CommunityBuilds.openPublishModal()">
                                <i class="fas fa-share-from-square"></i> <?= $builderAttr('Publish Your Build') ?>
                            </button>
                        </div>
                    </div>
                    <div class="cb-grid" id="communityBuildsGrid">
                        <div class="cb-loading"><i class="fas fa-spinner fa-spin"></i> <?= $builderAttr('Loading community builds...') ?></div>
                    </div>
                    <div class="cb-pagination" id="communityBuildsPagination"></div>
                </section>
            </div>

            <!-- Community Builds Publish Modal -->
            <div class="builder-modal-backdrop" id="cbPublishModal" style="display:none">
                <div class="builder-modal">
                    <div class="modal-glow-bg"></div>
                    <span class="modal-badge"><i class="fas fa-share-from-square"></i> <?= $builderAttr('PUBLISH') ?></span>
                    <h3><?= $builderAttr('Publish Your Build') ?></h3>
                    <p><?= $builderAttr('Share your current PC build configuration with the community.') ?></p>
                    <div style="display:flex;flex-direction:column;gap:12px;margin:16px 0">
                        <input type="text" id="cbPublishTitle" placeholder="<?= $builderAttr('Give your build a name...') ?>" class="build-name-input" style="width:100%">
                        <textarea id="cbPublishDesc" placeholder="<?= $builderAttr('Describe your build (optional)...') ?>" rows="3" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);font-family:inherit;resize:vertical"></textarea>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end">
                        <button class="btn-build btn-save-build" onclick="CommunityBuilds.closePublishModal()"><?= $builderAttr('Cancel') ?></button>
                        <button class="btn-build btn-add-all" onclick="CommunityBuilds.publish()"><i class="fas fa-paper-plane"></i> <?= $builderAttr('Publish') ?></button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <button class="mobile-build-dock" id="mobileBuildDock" type="button" onclick="PCBuilder.focusSummary()" aria-label="<?= $builderAttr('Review build summary') ?>">
        <span><i class="fas fa-list-check"></i> <?= $builderAttr('Build') ?></span>
        <strong class="notranslate money-token" translate="no"><?= htmlspecialchars('0.00 ' . $builderCurrency, ENT_QUOTES, 'UTF-8') ?></strong>
        <em><?= $builderAttr('Start selecting parts') ?></em>
    </button>

    <!-- Share Modal -->
    <div class="builder-modal-backdrop" id="shareModalBackdrop">
        <div class="builder-modal share-modal">
            <div class="modal-glow-bg"></div>
            <span class="modal-badge"><i class="fas fa-check"></i> <?= $builderAttr('SAVED') ?></span>
            <h3><?= $builderAttr('Share Your Build') ?></h3>
            <p><?= $builderAttr('Share your masterpiece with the community!') ?></p>
            
            <div class="share-actions-grid">
                <div class="share-qr-box">
                    <div class="qr-placeholder">
                        <i class="fas fa-qrcode"></i>
                        <span><?= $builderAttr('QR CODE') ?></span>
                    </div>
                </div>
                <div class="share-link-group">
                    <div class="share-url-box">
                        <i class="fas fa-link url-icon"></i>
                        <input type="text" id="shareUrlInput" readonly>
                        <button onclick="PCBuilder.copyShareUrl()" title="<?= $builderAttr('Copy Link') ?>"><i class="fas fa-copy"></i></button>
                    </div>
                    <div class="social-share-row">
                        <button class="social-btn fb" onclick="PCBuilder.shareFB()"><i class="fab fa-facebook-f"></i></button>
                        <button class="social-btn wa" onclick="PCBuilder.shareWA()"><i class="fab fa-whatsapp"></i></button>
                        <button class="social-btn tw" onclick="PCBuilder.shareTW()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor" style="vertical-align: middle;"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg></button>
                    </div>
                </div>
            </div>
            
            <button class="btn-close-modal" onclick="PCBuilder.closeShareModal()"><?= $builderAttr('Done') ?></button>
        </div>
    </div>

    <!-- Toast -->
    <output class="toast" id="toast" role="status" aria-live="polite">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"><?= $builderAttr('Item added to cart!') ?></span>
    </output>

    <!-- AI Build Assistant -->
    <div id="ai-terminal" class="ai-terminal hidden builder-ai-terminal">
        <div class="ai-header">
            <span><i class="fas fa-wand-magic-sparkles"></i> <?= $builderAttr('BUILDER_COPILOT v3.0') ?></span>
            <button id="close-ai" aria-label="<?= $builderAttr('Close AI assistant') ?>">&times;</button>
        </div>
        <div class="ai-messages" id="ai-messages">
            <div class="bot-msg"><?= $builderAttr('Build assistant ready. I can inspect your selected parts, plan the next slot, check wattage, optimize budget, and recommend services.') ?></div>
        </div>
        <div class="ai-quick-actions" aria-label="<?= $builderAttr('Build assistant quick prompts') ?>">
            <button type="button" data-ai-prompt="<?= $builderAttr('Analyze my current build and tell me the next best action.') ?>"><i class="fas fa-stethoscope"></i> <?= $builderAttr('Analyze') ?></button>
            <button type="button" data-ai-prompt="<?= $builderAttr('What should I choose next for this build?') ?>"><i class="fas fa-forward-step"></i> <?= $builderAttr('Next part') ?></button>
            <button type="button" data-ai-prompt="<?= $builderAttr('Check compatibility, wattage, cooling, and missing parts.') ?>"><i class="fas fa-shield-halved"></i> <?= $builderAttr('Full check') ?></button>
            <button type="button" data-ai-prompt="<?= $builderAttr('Optimize this build for my budget without wasting money.') ?>"><i class="fas fa-scale-balanced"></i> <?= $builderAttr('Optimize') ?></button>
            <button type="button" data-ai-prompt="<?= htmlspecialchars(str_replace('{currency}', $builderCurrency, $builderJsI18n['aiBuilder']['quickActions'][4]['prompt']), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-gamepad"></i> <?= $builderAttr('Gaming build') ?></button>
            <button type="button" data-ai-prompt="<?= $builderAttr('Which services should I add before checkout?') ?>"><i class="fas fa-screwdriver-wrench"></i> <?= $builderAttr('Services') ?></button>
        </div>
        <div class="ai-input-area">
            <textarea id="ai-input" class="ai-input" placeholder="<?= $builderAttr('Ask about this build, compatibility, budget, or upgrades...') ?>" rows="3"></textarea>
        </div>
    </div>

    <button id="open-ai" class="ai-trigger builder-ai-trigger" aria-label="<?= $builderAttr('Open AI build assistant') ?>">
        <i class="fas fa-robot"></i>
    </button>

    <!-- Footer -->
    <?php
    require_once __DIR__ . '/includes/store-footer.php';
    storeFooter();
    ?>

    <?php
    $builderTab = $_GET['tab'] ?? 'builder';
    $sidebarTool = match ($builderTab) {
        'gaming-finder' => 'gaming-finder',
        'psu-calculator' => 'psu-calculator',
        'memory-finder' => 'memory-finder',
        default => 'builder',
    };
    storeSidebar('builder', $sidebarTool);
    ?>

    <!-- Onboarding Questionnaire Modal -->
    <div id="onboardingWizardModal" class="builder-modal-overlay" style="display:none;">
        <div class="builder-modal animate-modal">
            <button class="modal-close-btn" onclick="PCBuilder.closeOnboardingWizard()"><i class="fas fa-times"></i></button>
            <div class="wizard-progress-bar">
                <div class="wizard-progress-fill" id="wizardProgressFill" style="width: 25%"></div>
            </div>
            
            <div class="wizard-steps-container">
                <!-- Step 1: Use Case -->
                <div class="wizard-modal-step active" data-step="1">
                    <h3><?= $builderAttr('What is the primary purpose of your PC?') ?></h3>
                    <p class="step-subtitle"><?= $builderAttr('We will customize our component balance based on your goals.') ?></p>
                    <div class="wizard-options-grid">
                        <button class="wizard-option-card" onclick="PCBuilder.selectWizardOption('useCase', 'gaming')">
                            <i class="fas fa-gamepad"></i>
                            <strong><?= $builderAttr('Gaming / Esports') ?></strong>
                            <span><?= $builderAttr('Optimized for highest frame rates and smooth gameplay.') ?></span>
                        </button>
                        <button class="wizard-option-card" onclick="PCBuilder.selectWizardOption('useCase', 'workstation')">
                            <i class="fas fa-microchip"></i>
                            <strong><?= $builderAttr('Content Creation / Work') ?></strong>
                            <span><?= $builderAttr('Extra CPU cores and RAM for editing, rendering, or coding.') ?></span>
                        </button>
                        <button class="wizard-option-card" onclick="PCBuilder.selectWizardOption('useCase', 'office')">
                            <i class="fas fa-briefcase"></i>
                            <strong><?= $builderAttr('Office / Everyday') ?></strong>
                            <span><?= $builderAttr('Affordable, snappy, and reliable for web, media, and office apps.') ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Budget -->
                <div class="wizard-modal-step" data-step="2" style="display:none;">
                    <h3><?= $builderAttr('What is your target budget?') ?></h3>
                    <p class="step-subtitle"><?= $builderAttr('We will design the absolute best value-for-money build within this range.') ?></p>
                    <div class="wizard-budget-selector">
                        <div class="budget-slider-wrapper">
                            <span class="budget-display-large notranslate money-token" translate="no" id="wizardBudgetValue"><?= htmlspecialchars('12,000 ' . $builderCurrency, ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="range" id="wizardBudgetRange" min="5000" max="40000" step="1000" value="12000" oninput="PCBuilder.updateWizardBudget(this.value)">
                            <div class="budget-hints">
                                <span><?= $builderAttr('Entry Level') ?></span>
                                <span><?= $builderAttr('Mid-Range') ?></span>
                                <span><?= $builderAttr('High-End Enthusiast') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-nav-buttons">
                        <button class="btn-wizard-nav prev" onclick="PCBuilder.prevWizardStep()"><i class="fas fa-chevron-left"></i> <?= $builderAttr('Back') ?></button>
                        <button class="btn-wizard-nav next" onclick="PCBuilder.nextWizardStep()"><?= $builderAttr('Next') ?> <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                
                <!-- Step 3: Style & Performance Prefs -->
                <div class="wizard-modal-step" data-step="3" style="display:none;">
                    <h3><?= $builderAttr('Do you prefer aesthetic flair or raw performance?') ?></h3>
                    <p class="step-subtitle"><?= $builderAttr('RGB & design vs. premium silent fans & pure frames.') ?></p>
                    <div class="wizard-options-grid">
                        <button class="wizard-option-card" onclick="PCBuilder.selectWizardOption('theme', 'performance')">
                            <i class="fas fa-gauge-high"></i>
                            <strong><?= $builderAttr('Raw Performance Focus') ?></strong>
                            <span><?= $builderAttr('Maximized component speeds, premium thermals, zero extra RGB costs.') ?></span>
                        </button>
                        <button class="wizard-option-card" onclick="PCBuilder.selectWizardOption('theme', 'rgb')">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <strong><?= $builderAttr('RGB Enthusiast') ?></strong>
                            <span><?= $builderAttr('Full-window chassis, customized synchronized lighting, premium vibes.') ?></span>
                        </button>
                    </div>
                    <div class="wizard-nav-buttons">
                        <button class="btn-wizard-nav prev" onclick="PCBuilder.prevWizardStep()"><i class="fas fa-chevron-left"></i> <?= $builderAttr('Back') ?></button>
                    </div>
                </div>
                
                <!-- Step 4: Loading / Finalizing -->
                <div class="wizard-modal-step" data-step="4" style="display:none;">
                    <div class="wizard-matching-loader">
                        <div class="loader-spinner"></div>
                        <h3><?= $builderAttr('Forging your custom setup...') ?></h3>
                        <p><?= $builderAttr('Finding compatible components, balancing motherboard sockets, and optimizing thermal clearance.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title"><?= $builderAttr('Sign In') ?></p>
            <p class="role-modal-subtitle"><?= $builderAttr('Select your account type to continue to the login page.') ?></p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon"><i class="fas fa-user"></i></span>
                <div>
                    <strong><?= $builderAttr('Customer Account') ?></strong>
                    <small><?= $builderAttr('Track orders, wishlists & purchases') ?></small>
                </div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon"><i class="fas fa-shield-alt"></i></span>
                <div>
                    <strong><?= $builderAttr('Admin Portal') ?></strong>
                    <small><?= $builderAttr('Inventory, orders & site management') ?></small>
                </div>
            </button>
            <button class="role-cancel" onclick="closeRoleModal()"><?= $builderAttr('Cancel') ?></button>
        </div>
    </div>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <?= i18n_language_switcher_assets() ?>
    <script>
        window.__marocPcI18n = <?= i18n_script_json($builderJsI18n) ?>;
        window.__marocPcPhraseMap = <?= i18n_script_json($builderPhraseMap) ?>;
    </script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/fps-data.js"></script>
    <script src="assets/js/fps-estimator.js"></script>
    <script src="assets/js/builder.js?v=i18n-sweep-1"></script>
    <script src="assets/js/community-builds.js?v=i18n-fix-11"></script>
    <script src="assets/js/app.js?v=builder-ai-copilot-4"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Common UI logic for Sidebar & Search
        document.addEventListener('DOMContentLoaded', function () {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (hamburgerBtn) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

            // Search logic
            const mobileSearchInput = document.getElementById('mobileSearchInput');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            if (mobileSearchInput && mobileSearchBtn) {
                const executeMobileSearch = () => {
                    if (mobileSearchInput.value.trim()) {
                        window.location.href = `products.html?search=${encodeURIComponent(mobileSearchInput.value.trim())}`;
                    }
                };
                mobileSearchBtn.addEventListener('click', executeMobileSearch);
                mobileSearchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        executeMobileSearch();
                    }
                });
            }
        });

        // Role Modal logic
        function selectRole(role) {
            closeRoleModal();
            if (role === 'user') window.location.href = 'login.php';
            else if (role === 'administrator') window.location.href = 'adminlogin.php';
        }
        function closeRoleModal() { document.getElementById('roleModal').style.display = 'none'; }
        document.getElementById('roleModal').addEventListener('click', function (e) { if (e.target === this) closeRoleModal(); });

        // Tab switching logic
        function switchToolTab(tabId, btn) {
            // Hide all tab contents
            document.querySelectorAll('.tool-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            const activeTab = document.getElementById(tabId);
            if (activeTab) {
                activeTab.classList.add('active');
                
                // Re-trigger scroll animations for the newly visible content
                if (typeof observer !== 'undefined') {
                    activeTab.querySelectorAll('.animate-on-scroll').forEach(el => {
                        observer.observe(el);
                    });
                }
            }
            
            // Update active state on buttons
            document.querySelectorAll('.bth-card').forEach(card => {
                card.classList.remove('active');
            });
            btn.classList.add('active');

            // Scroll to the content for better UX
            activeTab.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Active Tab Routing on Load
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                let tabId = '';
                let btnSelector = '';
                if (tabParam === 'gaming-finder') {
                    tabId = 'tab-gaming-finder';
                    btnSelector = 'button[onclick*="tab-gaming-finder"]';
                } else if (tabParam === 'psu-calculator') {
                    tabId = 'tab-psu-calculator';
                    btnSelector = 'button[onclick*="tab-psu-calculator"]';
                } else if (tabParam === 'memory-finder') {
                    tabId = 'tab-memory-finder';
                    btnSelector = 'button[onclick*="tab-memory-finder"]';
                } else if (tabParam === 'pc-builder') {
                    tabId = 'tab-pc-builder';
                    btnSelector = 'button[onclick*="tab-pc-builder"]';
                } else if (tabParam === 'community-builds') {
                    tabId = 'tab-community-builds';
                    btnSelector = 'button[onclick*="tab-community-builds"]';
                }

                if (tabId && btnSelector) {
                    const btn = document.querySelector(btnSelector);
                    if (btn) {
                        setTimeout(() => {
                            switchToolTab(tabId, btn);
                        }, 200);
                    }
                }
            }
        });
    </script>
</body>
</html>
