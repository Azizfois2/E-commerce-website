    <!-- Breadcrumb -->
    <section class="breadcrumb">
        <div class="container">
            <nav class="breadcrumb-nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.home'); ?></a>
                <i class="fas fa-chevron-right"></i>
                <span><?php i18n_e('nav.products'); ?></span>
            </nav>
        </div>
    </section>

    <!-- Products Page -->
    <section class="products-page">
        <div class="container">
            <div class="bundle-strip" aria-label="<?php i18n_e('products.recommended_bundles'); ?>">
                <div class="bundle-card">
                    <div>
                        <span class="bundle-eyebrow"><?php i18n_e('products.bundle_save'); ?></span>
                        <h3><?php i18n_e('products.am5_core'); ?></h3>
                        <p><?php i18n_e('products.am5_core_desc'); ?></p>
                    </div>
                    <button class="bundle-add-btn" data-bundle="am5-core"><i class="fas fa-cart-plus"></i> <?php i18n_e('products.add_bundle'); ?></button>
                </div>
                <div class="bundle-card">
                    <div>
                        <span class="bundle-eyebrow"><?php i18n_e('products.creator_pick'); ?></span>
                        <h3><?php i18n_e('products.render_kit'); ?></h3>
                        <p><?php i18n_e('products.render_kit_desc'); ?></p>
                    </div>
                    <button class="bundle-add-btn" data-bundle="creator-kit"><i class="fas fa-cart-plus"></i> <?php i18n_e('products.add_bundle'); ?></button>
                </div>
                <div class="bundle-card">
                    <div>
                        <span class="bundle-eyebrow"><?php i18n_e('products.local_service'); ?></span>
                        <h3><?php i18n_e('products.assembly_test'); ?></h3>
                        <p><?php i18n_e('products.assembly_test_desc'); ?></p>
                    </div>
                    <button class="bundle-add-btn" data-bundle="service-kit"><i class="fas fa-cart-plus"></i> <?php i18n_e('products.add_service'); ?></button>
                </div>
            </div>
            <div class="products-layout">
                <!-- Sidebar Filters -->
                <aside class="filters-sidebar">
                    <div class="filter-header">
                        <h3><i class="fas fa-filter"></i> <?php i18n_e('products.filters'); ?></h3>
                        <button class="clear-filters" id="clearFilters"><?php i18n_e('products.clear_all'); ?></button>
                    </div>

                    <!-- Category Filter -->
                    <!-- Values must match the `category` field in data.js exactly -->
                    <div class="filter-group">
                        <h4><?php i18n_e('products.category'); ?></h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="cpu">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.processors'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="gpu">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.graphics_cards'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="ram">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.memory_ram'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="motherboard">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.motherboards'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="storage">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.storage'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="cooling">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.cooling'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="psu">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.power_supplies'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="monitor">
                                <span class="checkmark"></span>
                                <?php i18n_e('products.monitors', [], 'Monitors'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="accessories">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.accessories'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="keyboard">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.keyboards', [], 'Keyboards'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="mouse">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.mice', [], 'Mice'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="vr">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.vr_headsets', [], 'VR Headsets'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="category" value="router">
                                <span class="checkmark"></span>
                                <?php i18n_e('nav.routers', [], 'Routers'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h4><?php i18n_e('products.price_range'); ?></h4>
                        <div class="price-range">
                            <div class="price-inputs">
                                <input type="number" id="minPrice" placeholder="<?php i18n_e('products.min'); ?>" min="0">
                                <span>-</span>
                                <input type="number" id="maxPrice" placeholder="<?php i18n_e('products.max'); ?>" min="0">
                            </div>
                            <input type="range" class="price-slider" id="priceSlider" min="0" max="30000" value="30000">
                            <div class="price-labels">
                                <span>0 DH</span>
                                <span>30,000+ DH</span>
                            </div>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <!-- Values must match the `brand` field in data.js exactly -->
                    <div class="filter-group">
                        <h4><?php i18n_e('products.brand'); ?></h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Intel">
                                <span class="checkmark"></span>
                                Intel
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="AMD">
                                <span class="checkmark"></span>
                                AMD
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="NVIDIA">
                                <span class="checkmark"></span>
                                NVIDIA
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="ASUS">
                                <span class="checkmark"></span>
                                ASUS
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="MSI">
                                <span class="checkmark"></span>
                                MSI
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Gigabyte">
                                <span class="checkmark"></span>
                                Gigabyte
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Corsair">
                                <span class="checkmark"></span>
                                Corsair
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Samsung">
                                <span class="checkmark"></span>
                                Samsung
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="G.Skill">
                                <span class="checkmark"></span>
                                G.Skill
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Western Digital">
                                <span class="checkmark"></span>
                                Western Digital
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Noctua">
                                <span class="checkmark"></span>
                                Noctua
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="NZXT">
                                <span class="checkmark"></span>
                                NZXT
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Seasonic">
                                <span class="checkmark"></span>
                                Seasonic
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Kingston">
                                <span class="checkmark"></span>
                                Kingston
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Crucial">
                                <span class="checkmark"></span>
                                Crucial
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="be quiet!">
                                <span class="checkmark"></span>
                                be quiet!
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Cooler Master">
                                <span class="checkmark"></span>
                                Cooler Master
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Meta">
                                <span class="checkmark"></span>
                                Meta
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="Sony">
                                <span class="checkmark"></span>
                                Sony
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="brand" value="TP-Link">
                                <span class="checkmark"></span>
                                TP-Link
                            </label>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h4><?php i18n_e('products.rating'); ?></h4>
                        <div class="filter-options">
                            <label class="filter-option rating-option">
                                <input type="radio" name="rating" value="4">
                                <span class="checkmark"></span>
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                & Up
                            </label>
                            <label class="filter-option rating-option">
                                <input type="radio" name="rating" value="3">
                                <span class="checkmark"></span>
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                & Up
                            </label>
                            <label class="filter-option rating-option">
                                <input type="radio" name="rating" value="2">
                                <span class="checkmark"></span>
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                & Up
                            </label>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="filter-group">
                        <h4><?php i18n_e('products.availability'); ?></h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="availability" value="instock" checked>
                                <span class="checkmark"></span>
                                <?php i18n_e('products.in_stock'); ?>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="availability" value="preorder">
                                <span class="checkmark"></span>
                                <?php i18n_e('products.out_of_stock'); ?>
                            </label>
                        </div>
                    </div>

                    <button class="btn btn-primary apply-filters" id="applyFilters"><?php i18n_e('products.apply_filters'); ?></button>
                </aside>

                <!-- Products Grid -->
                <main class="products-main">
                    <div class="products-header">
                        <div class="products-count">
                            <span id="productCount">0</span> <?php i18n_e('products.products_found'); ?>
                        </div>
                        <div class="products-sort">
                            <label><?php i18n_e('products.sort_by'); ?></label>
                            <select id="sortSelect">
                                <option value="featured"><?php i18n_e('products.featured'); ?></option>
                                <option value="price-low"><?php i18n_e('products.price_low'); ?></option>
                                <option value="price-high"><?php i18n_e('products.price_high'); ?></option>
                                <option value="rating"><?php i18n_e('products.highest_rated'); ?></option>
                                <option value="newest"><?php i18n_e('products.newest'); ?></option>
                            </select>
                        </div>
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                            <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <div class="active-filters" id="activeFilters">
                        <!-- Active filter tags will appear here -->
                    </div>

                    <!-- Products Grid -->
                    <div class="products-grid" id="productsGrid">
                        <!-- Products will be loaded via JavaScript -->
                    </div>

                    <!-- Pagination -->
                    <div class="pagination" id="pagination">
                        <!-- Pagination will be loaded via JavaScript -->
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- Product Detail Page Surface -->
    <section class="product-detail-page" id="productDetailPage" hidden>
        <div class="container">
            <div class="product-detail-nav">
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="product-detail-back" id="productDetailBack">
                    <i class="fas fa-arrow-left"></i> <?php i18n_e('products.back_products'); ?>
                </a>
            </div>
            <article class="product-detail-shell" id="productDetailContent" aria-live="polite">
                <!-- Product detail page content is loaded via JavaScript -->
            </article>
        </div>
    </section>
