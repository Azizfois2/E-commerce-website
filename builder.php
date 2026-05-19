<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder — Maroc PC</title>
    <meta name="description" content="Build your dream PC with our interactive configurator. Choose compatible components, check wattage, and share your build.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/builder.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>

<body>

    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>

            <!-- Logo -->
            <a href="index.html" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>

            <!-- Nav links -->
            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Components</a>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle active" aria-haspopup="true" aria-expanded="false">
                        Builder Tools <span class="chevron">▾</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="builder.php" class="dropdown-item">PC Build Wizard</a>
                        <a href="builder.php?tab=gaming-finder" class="dropdown-item">Gaming PC Finder</a>
                        <a href="laptop-finder.php" class="dropdown-item">Laptop Finder</a>
                        <a href="builder.php?tab=psu-calculator" class="dropdown-item">Power Supply Calculator</a>
                        <a href="builder.php?tab=memory-finder" class="dropdown-item">Memory Finder</a>
                        <a href="tools.php" class="dropdown-item">Tools Cockpit</a>
                    </div>
                </div>
                <a href="index.html#deals" class="nav-link">Deals</a>
                <a href="index.html#contact" class="nav-link">Contact</a>
            </nav>

            <div style="flex:1"></div>

            <!-- Theme toggle -->
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <div id="google_translate_element" class="nav-translate"></div>

            <!-- User icon -->
            <div class="cart-wrapper" id="userNav">
                <a href="login.php" class="cart-icon" aria-label="Account">
                    <i class="fas fa-user"></i>
                </a>
            </div>

            <!-- Cart icon -->
            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <main class="builder-page">
        <div class="builder-container">

            <!-- Hero -->
            <div class="builder-hero animate-on-scroll">
                <span class="eyebrow"><i class="fas fa-cogs"></i> Builder Tools</span>
                <h1>Tools That Build Smarter</h1>
                <p>Find the right parts, calculate power needs, match memory, and configure a complete PC in one place.</p>
            </div>

            <section class="build-start-choice animate-on-scroll" id="buildStartChoice" aria-labelledby="buildStartTitle">
                <span class="gf-kicker"><i class="fas fa-compass"></i> Choose your path</span>
                <h2 id="buildStartTitle">How do you want to start?</h2>
                <div class="start-choice-grid">
                    <!-- Legacy choice removed as it is redundant with pre-builts -->
                    <button class="start-choice-card" type="button" onclick="PCBuilder.chooseBuilderPath('prebuilt')">
                        <i class="fas fa-layer-group"></i>
                        <strong>Pre-built recommendations</strong>
                        <span>Start from a balanced base, advanced, or power build and tweak it after.</span>
                    </button>
                    <button class="start-choice-card" type="button" onclick="PCBuilder.chooseBuilderPath('custom')">
                        <i class="fas fa-screwdriver-wrench"></i>
                        <strong>Custom build</strong>
                        <span>Pick every component yourself with compatibility and wattage guidance.</span>
                    </button>
                </div>
            </section>

            <section class="builder-tools-hub animate-on-scroll" aria-labelledby="builderToolsTitle">
                <div class="bth-head">
                    <span class="gf-kicker"><i class="fas fa-toolbox"></i> Core tools</span>
                    <h2 id="builderToolsTitle">Build, find, and configure</h2>
                </div>
                <div class="bth-grid">
                    <button class="bth-card featured active" onclick="switchToolTab('tab-pc-builder', this)">
                        <span>Most Popular</span>
                        <i class="fas fa-screwdriver-wrench"></i>
                        <strong>PC Builder</strong>
                        <em>Design your build from scratch with compatibility checks and saved quotes.</em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-gaming-finder', this)">
                        <i class="fas fa-gamepad"></i>
                        <strong>Gaming PC Finder</strong>
                        <em>Pick games, resolution, FPS target, and budget to get a matched build.</em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-psu-calculator', this)">
                        <i class="fas fa-plug-circle-bolt"></i>
                        <strong>Power Supply Calculator</strong>
                        <em>Estimate wattage with upgrade headroom and shop matching PSUs.</em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-memory-finder', this)">
                        <i class="fas fa-memory"></i>
                        <strong>Memory Finder</strong>
                        <em>Find RAM that matches your CPU platform, motherboard, and workload.</em>
                    </button>
                    <button class="bth-card" onclick="switchToolTab('tab-community-builds', this)">
                        <i class="fas fa-users"></i>
                        <strong>Community Builds</strong>
                        <em>Browse builds shared by the community, upvote your favorites, or publish your own.</em>
                    </button>
                </div>
            </section>

            <!-- Tabbed Content Areas -->
            <div id="tab-pc-builder" class="tool-tab-content active">
                <div id="pcConfigurator" class="configurator-anchor"></div>

                <div class="builder-workspace is-hidden" id="pcBuilderWorkspace">
                
                <!-- Platform Selector -->
                <div class="platform-selector animate-on-scroll">
                    <h4 class="ps-title">Start by selecting your platform</h4>
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
                        <input type="text" class="build-name-input" id="buildNameInput" placeholder="Name your build..." value="My Build">

                        <h3><i class="fas fa-list-check"></i> Build Summary</h3>

                        <div class="summary-items" id="summaryItems">
                            <!-- Populated by JS -->
                        </div>

                        <!-- Wattage Meter -->
                        <div class="wattage-meter">
                            <div class="wm-header">
                                <span class="wm-label"><i class="fas fa-bolt"></i> Power Draw</span>
                                <span class="wm-value" id="wattageValue">0W / ???</span>
                            </div>
                            <div class="wattage-bar">
                                <div class="wattage-fill" id="wattageFill" style="width: 0%"></div>
                            </div>
                            <div class="wm-recommendation" id="wattageRec">Select components to see recommendation</div>
                        </div>

                        <div class="compatibility-panel" id="compatibilityPanel">
                            <!-- Populated by JS -->
                        </div>

                        <div class="bottleneck-panel" id="bottleneckPanel">
                            <!-- Populated by JS -->
                        </div>

                        <div class="health-panel" id="healthPanel">
                            <!-- Populated by JS -->
                        </div>

                        <div class="smart-checklist-panel" id="smartChecklistPanel">
                            <!-- Populated by JS -->
                        </div>

                        <div class="assembly-guide-panel" id="assemblyGuidePanel">
                            <!-- Populated by JS -->
                        </div>

                        <div class="build-services">
                            <h4><i class="fas fa-screwdriver-wrench"></i> Build Services</h4>
                            <label class="service-option">
                                <input type="checkbox" class="service-checkbox" value="assembly">
                                <span>
                                    <strong>Professional assembly</strong>
                                    <small>Clean cable management and full installation</small>
                                </span>
                                <em>299 MAD</em>
                            </label>
                            <label class="service-option">
                                <input type="checkbox" class="service-checkbox" value="bios">
                                <span>
                                    <strong>BIOS update</strong>
                                    <small>Ready for latest CPUs and memory profiles</small>
                                </span>
                                <em>99 MAD</em>
                            </label>
                            <label class="service-option">
                                <input type="checkbox" class="service-checkbox" value="stress">
                                <span>
                                    <strong>Stress test report</strong>
                                    <small>Thermals, stability, and PSU load checked</small>
                                </span>
                                <em>149 MAD</em>
                            </label>
                            <label class="service-option">
                                <input type="checkbox" class="service-checkbox" value="windows">
                                <span>
                                    <strong>Windows install</strong>
                                    <small>Drivers and updates prepared</small>
                                </span>
                                <em>199 MAD</em>
                            </label>
                            <label class="service-option">
                                <input type="checkbox" class="service-checkbox" value="bazzite">
                                <span>
                                    <strong>Bazzite + Proton++ install</strong>
                                    <small>Gaming Linux setup with Steam, Proton, and controller support</small>
                                </span>
                                <em>249 MAD</em>
                            </label>
                        </div>

                        <!-- Total -->
                        <div class="build-total">
                            <span class="bt-label">Total</span>
                            <span class="bt-price" id="totalPrice">0.00 MAD</span>
                        </div>

                        <!-- Actions -->
                        <div class="build-actions">
                            <button class="btn-build btn-add-all" id="addAllBtn" disabled onclick="PCBuilder.addAllToCart()">
                                <i class="fas fa-cart-plus"></i> Add All to Cart
                            </button>
                            <button class="btn-build btn-save-build" onclick="PCBuilder.saveBuild()">
                                <i class="fas fa-save"></i> Save & Share Build
                            </button>
                            <a class="btn-build btn-share-build" href="builds-compare.php">
                                <i class="fas fa-code-compare"></i> Compare Saved Builds
                            </a>
                            <button class="btn-build btn-share-build" onclick="PCBuilder.shareWhatsApp()">
                                <i class="fab fa-whatsapp"></i> Send on WhatsApp
                            </button>
                            <button class="btn-build btn-share-build" onclick="PCBuilder.exportQuote()">
                                <i class="fas fa-file-lines"></i> Export Quote
                            </button>
                            <button class="btn-build btn-auto-build" onclick="PCBuilder.autoBuild()">
                                <i class="fas fa-magic"></i> Auto-Build for Me
                            </button>
                        </div>
                        </aside>
                    </div>
                </div>
            </div>

            <div id="tab-gaming-finder" class="tool-tab-content">
                <!-- Gaming PC Finder -->
                <section class="gaming-finder animate-on-scroll" id="gamingFinder" aria-labelledby="gamingFinderTitle">
                    <div class="gf-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-crosshairs"></i> Gaming PC Finder</span>
                            <h2 id="gamingFinderTitle">Match a build to your games</h2>
                        </div>
                        <div class="gf-status" id="finderStatus">Catalog ready</div>
                    </div>

                    <div class="gf-layout">
                        <div class="gf-panel">
                            <div class="gf-control">
                                <label>Games</label>
                                <div class="gf-game-grid" id="finderGames"></div>
                            </div>

                            <div class="gf-control-row">
                                <div class="gf-control">
                                    <label>Resolution</label>
                                    <div class="gf-segment" id="finderResolution">
                                        <button class="active" data-resolution="1080p">1080p</button>
                                        <button data-resolution="1440p">1440p</button>
                                        <button data-resolution="4K">4K</button>
                                    </div>
                                </div>
                                <div class="gf-control">
                                    <label>Target FPS</label>
                                    <div class="gf-segment" id="finderFps">
                                        <button data-fps="60">60</button>
                                        <button class="active" data-fps="120">120</button>
                                        <button data-fps="165">165</button>
                                    </div>
                                </div>
                            </div>

                            <div class="gf-budget">
                                <div class="gf-budget-top">
                                    <label for="finderBudget">Budget</label>
                                    <strong id="finderBudgetValue">18,000.00 MAD</strong>
                                </div>
                                <input type="range" id="finderBudget" min="8000" max="32000" step="500" value="18000">
                            </div>

                            <div class="gf-actions">
                                <button class="btn-build btn-add-all" onclick="PCBuilder.applyGamingFinder()">
                                    <i class="fas fa-wand-magic-sparkles"></i> Find My Build
                                </button>
                                <button class="btn-build btn-share-build" onclick="PCBuilder.resetGamingFinder()">
                                    <i class="fas fa-rotate-left"></i> Reset
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
                        <span class="gf-kicker"><i class="fas fa-circle-question"></i> Quick answers</span>
                        <h2 id="finderFaqTitle">Before you choose a gaming build</h2>
                    </div>

                    <div class="ff-grid">
                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>How do I use the finder?</h3>
                                <ol>
                                    <li>Select up to 4 games.</li>
                                    <li>Choose your resolution and FPS target.</li>
                                    <li>Set your budget in MAD.</li>
                                    <li>Click Find My Build, then edit any part in the wizard.</li>
                                </ol>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>Which resolution should I pick?</h3>
                                <p>1080p is best for budget and high refresh rates, 1440p is the sweet spot for most gaming PCs, and 4K needs a stronger GPU for smooth ultra settings.</p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>Can I select more than one game?</h3>
                                <p>Yes. Choose up to 4 titles and the finder will size the build around the most demanding game in your selection.</p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>What does the FPS estimate mean?</h3>
                                <p>It is a practical catalog-based estimate using the selected CPU, GPU, games, and resolution. Real FPS can vary by settings, drivers, patches, and thermals.</p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>Can I compare with my current PC?</h3>
                                <p>Use the component wizard to swap in parts similar to your current CPU or GPU, then compare the FPS panel against the recommended build.</p>
                            </div>
                        </article>

                        <article class="ff-item">
                            <i class="fas fa-circle-question"></i>
                            <div>
                                <h3>Do I need a new monitor?</h3>
                                <p>Not always. A 1440p or 4K build shines most when your monitor supports that resolution and refresh rate, so match the PC to the screen you actually use.</p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div id="tab-psu-calculator" class="tool-tab-content">
                <section class="builder-tool-panel animate-on-scroll" id="powerSupplyCalculator" aria-labelledby="psuCalcTitle">
                    <div class="btp-head">
                        <div>
                            <span class="gf-kicker"><i class="fas fa-plug-circle-bolt"></i> Power Supply Calculator</span>
                            <h2 id="psuCalcTitle">Calculate the right PSU wattage</h2>
                        </div>
                        <button class="btn-build btn-share-build" onclick="PCBuilder.useCurrentBuildForPsu()">
                            <i class="fas fa-link"></i> Use Current Build
                        </button>
                    </div>

                    <div class="btp-layout">
                        <div class="tool-form-grid">
                            <label class="tool-field">
                                <span>CPU</span>
                                <select id="psuCpuSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span>GPU</span>
                                <select id="psuGpuSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span>Motherboard</span>
                                <select id="psuMotherboardSelect">
                                    <option value="0">Not selected</option>
                                    <option value="45">Mini-ITX</option>
                                    <option value="55">Micro-ATX</option>
                                    <option value="65">ATX</option>
                                    <option value="80">E-ATX / workstation</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span>Memory</span>
                                <select id="psuRamSelect">
                                    <option value="0">Not selected</option>
                                    <option value="8">16GB DDR4</option>
                                    <option value="10">32GB DDR4</option>
                                    <option value="12">32GB DDR5</option>
                                    <option value="18">64GB DDR5</option>
                                    <option value="28">128GB DDR5</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span>SSD count</span>
                                <input type="number" id="psuSsdCount" min="0" max="8" value="1">
                            </label>
                            <label class="tool-field">
                                <span>HDD count</span>
                                <input type="number" id="psuHddCount" min="0" max="8" value="0">
                            </label>
                            <label class="tool-field">
                                <span>Case fans</span>
                                <input type="number" id="psuFanCount" min="0" max="12" value="3">
                            </label>
                            <label class="tool-field">
                                <span>Upgrade headroom</span>
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
                            <span class="gf-kicker"><i class="fas fa-memory"></i> Memory Finder</span>
                            <h2 id="memoryFinderTitle">Find compatible RAM</h2>
                        </div>
                        <button class="btn-build btn-share-build" onclick="PCBuilder.useCurrentBuildForMemory()">
                            <i class="fas fa-link"></i> Use Current Build
                        </button>
                    </div>

                    <div class="btp-layout">
                        <div class="tool-form-grid memory-tool-form">
                            <label class="tool-field">
                                <span>CPU platform</span>
                                <select id="memoryPlatformSelect">
                                    <option value="">Auto / not sure</option>
                                    <option value="AM5">AMD AM5</option>
                                    <option value="LGA 1700">Intel LGA 1700</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span>Motherboard</span>
                                <select id="memoryMotherboardSelect"></select>
                            </label>
                            <label class="tool-field">
                                <span>Workload</span>
                                <select id="memoryUseSelect">
                                    <option value="gaming">Gaming</option>
                                    <option value="streaming">Gaming + streaming</option>
                                    <option value="creator">Creator / render</option>
                                    <option value="office">Office / study</option>
                                </select>
                            </label>
                            <label class="tool-field">
                                <span>Capacity</span>
                                <select id="memoryCapacitySelect">
                                    <option value="">Any capacity</option>
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
                            <span class="gf-kicker"><i class="fas fa-users"></i> Community Builds</span>
                            <h2 id="communityBuildsTitle">Explore builds from the community</h2>
                        </div>
                        <div class="cb-sort-controls">
                            <select id="cbSortSelect" class="cb-sort-select">
                                <option value="newest">Newest</option>
                                <option value="popular">Most Popular</option>
                            </select>
                            <button class="btn-build btn-add-all" id="cbPublishBtn" onclick="CommunityBuilds.openPublishModal()">
                                <i class="fas fa-share-from-square"></i> Publish Your Build
                            </button>
                        </div>
                    </div>
                    <div class="cb-grid" id="communityBuildsGrid">
                        <div class="cb-loading"><i class="fas fa-spinner fa-spin"></i> Loading community builds...</div>
                    </div>
                    <div class="cb-pagination" id="communityBuildsPagination"></div>
                </section>
            </div>

            <!-- Community Builds Publish Modal -->
            <div class="builder-modal-backdrop" id="cbPublishModal" style="display:none">
                <div class="builder-modal">
                    <div class="modal-glow-bg"></div>
                    <span class="modal-badge"><i class="fas fa-share-from-square"></i> PUBLISH</span>
                    <h3>Publish Your Build</h3>
                    <p>Share your current PC build configuration with the community.</p>
                    <div style="display:flex;flex-direction:column;gap:12px;margin:16px 0">
                        <input type="text" id="cbPublishTitle" placeholder="Give your build a name..." class="build-name-input" style="width:100%">
                        <textarea id="cbPublishDesc" placeholder="Describe your build (optional)..." rows="3" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);font-family:inherit;resize:vertical"></textarea>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end">
                        <button class="btn-build btn-save-build" onclick="CommunityBuilds.closePublishModal()">Cancel</button>
                        <button class="btn-build btn-add-all" onclick="CommunityBuilds.publish()"><i class="fas fa-paper-plane"></i> Publish</button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <button class="mobile-build-dock" id="mobileBuildDock" type="button" onclick="PCBuilder.focusSummary()" aria-label="Review build summary">
        <span><i class="fas fa-list-check"></i> Build</span>
        <strong>0.00 MAD</strong>
        <em>Start selecting parts</em>
    </button>

    <!-- Share Modal -->
    <div class="builder-modal-backdrop" id="shareModalBackdrop">
        <div class="builder-modal share-modal">
            <div class="modal-glow-bg"></div>
            <span class="modal-badge"><i class="fas fa-check"></i> SAVED</span>
            <h3>Share Your Build</h3>
            <p>Share your masterpiece with the community!</p>
            
            <div class="share-actions-grid">
                <div class="share-qr-box">
                    <div class="qr-placeholder">
                        <i class="fas fa-qrcode"></i>
                        <span>QR CODE</span>
                    </div>
                </div>
                <div class="share-link-group">
                    <div class="share-url-box">
                        <i class="fas fa-link url-icon"></i>
                        <input type="text" id="shareUrlInput" readonly>
                        <button onclick="PCBuilder.copyShareUrl()" title="Copy Link"><i class="fas fa-copy"></i></button>
                    </div>
                    <div class="social-share-row">
                        <button class="social-btn fb" onclick="PCBuilder.shareFB()"><i class="fab fa-facebook-f"></i></button>
                        <button class="social-btn wa" onclick="PCBuilder.shareWA()"><i class="fab fa-whatsapp"></i></button>
                        <button class="social-btn tw" onclick="PCBuilder.shareTW()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor" style="vertical-align: middle;"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg></button>
                    </div>
                </div>
            </div>
            
            <button class="btn-close-modal" onclick="PCBuilder.closeShareModal()">Done</button>
        </div>
    </div>

    <!-- Toast -->
    <output class="toast" id="toast" role="status" aria-live="polite">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Item added to cart!</span>
    </output>

    <!-- AI Build Assistant -->
    <div id="ai-terminal" class="ai-terminal hidden builder-ai-terminal">
        <div class="ai-header">
            <span><i class="fas fa-robot"></i> BUILD_ASSISTANT v2.0</span>
            <button id="close-ai" aria-label="Close AI assistant">&times;</button>
        </div>
        <div class="ai-messages" id="ai-messages">
            <div class="bot-msg">Build assistant ready. Ask me what to pick next, check your wattage, or request a gaming PC around your budget.</div>
        </div>
        <div class="ai-quick-actions" aria-label="Build assistant quick prompts">
            <button type="button" data-ai-prompt="What should I choose next for this build?">Next part</button>
            <button type="button" data-ai-prompt="Check my current build compatibility and wattage.">Check build</button>
            <button type="button" data-ai-prompt="Recommend a balanced gaming PC build around 18000 MAD.">Gaming build</button>
        </div>
        <div class="ai-input-area">
            <textarea id="ai-input" class="ai-input" placeholder="Ask about this build..." rows="3"></textarea>
        </div>
    </div>

    <button id="open-ai" class="ai-trigger builder-ai-trigger" aria-label="Open AI build assistant">
        <i class="fas fa-robot"></i>
    </button>

    <!-- Footer -->
    <footer style="background: var(--card-bg); border-top: 1px solid var(--border); padding: 32px 0; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 24px;">
            <p style="color: var(--muted);">&copy; 2026 Maroc PC. All rights reserved.</p>
            <div style="margin-top: 10px; display: flex; justify-content: center; gap: 15px;">
                <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="https://x.com/Maroc_PC_PHP" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-x-twitter"></i> X (Twitter)</a>
                <a href="https://www.instagram.com/marocpc57" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank" style="color: var(--cyan); text-decoration: none;"><i class="fab fa-youtube"></i> YouTube</a>
            </div>
        </div>
    </footer>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Panel -->
    <nav class="sidebar" id="sidebar" aria-label="Mobile navigation">
        <div class="sidebar-header">
            <a href="index.html" class="sidebar-logo-link">
                <i class="fas fa-microchip"></i> Maroc PC
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-search">
            <input type="text" id="mobileSearchInput" placeholder="Search components..." aria-label="Search products" />
            <button id="mobileSearchBtn" aria-label="Search">
                <i class="fas fa-search" style="color:#000;"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <li><a href="index.html" class="sidebar-link"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="products.html" class="sidebar-link"><i class="fas fa-box"></i> Products</a></li>
            <li class="sidebar-dropdown open">
                <button class="sidebar-link sidebar-toggle-btn active" aria-expanded="true">
                    <i class="fas fa-tools"></i>
                    Builder Tools
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="builder.php" class="sidebar-sublink active">PC Build Wizard</a></li>
                    <li><a href="builder.php?tab=gaming-finder" class="sidebar-sublink">Gaming PC Finder</a></li>
                    <li><a href="laptop-finder.php" class="sidebar-sublink">Laptop Finder</a></li>
                    <li><a href="builder.php?tab=psu-calculator" class="sidebar-sublink">Power Supply Calculator</a></li>
                    <li><a href="builder.php?tab=memory-finder" class="sidebar-sublink">Memory Finder</a></li>
                </ul>
            </li>
            <li><a href="index.html#categories" class="sidebar-link"><i class="fas fa-th"></i> Categories</a></li>
            <li><a href="index.html#deals" class="sidebar-link"><i class="fas fa-bolt"></i> Deals</a></li>

            <li><a href="cart.html" class="sidebar-link"><i class="fas fa-shopping-cart"></i> Cart</a></li>
        </ul>
    </nav>

    <!-- Role Modal -->
    <div id="roleModal" class="role-modal-overlay" style="display:none;">
        <div class="role-modal">
            <p class="role-modal-title">Continue as</p>
            <p class="role-modal-subtitle">Choose your access level to proceed.</p>
            <button class="role-btn" onclick="selectRole('user')">
                <span class="role-icon user-icon">👤</span>
                <div>
                    <strong>Continue as user</strong>
                    <small>Standard access</small>
                </div>
            </button>
            <button class="role-btn" onclick="selectRole('administrator')">
                <span class="role-icon admin-icon">🛡️</span>
                <div>
                    <strong>Continue as administrator</strong>
                    <small>Admin panel</small>
                </div>
            </button>
            <button class="role-cancel" onclick="closeRoleModal()">Cancel</button>
        </div>
    </div>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/translate.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script src="assets/js/fps-data.js"></script>
    <script src="assets/js/fps-estimator.js"></script>
    <script src="assets/js/builder.js"></script>
    <script src="assets/js/community-builds.js"></script>
    <script src="assets/js/app.js"></script>
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
