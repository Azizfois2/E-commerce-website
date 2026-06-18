(() => {
function formatMAD(value, options = {}) {
    if (typeof window.formatMAD === 'function') {
        return window.formatMAD(value, options);
    }
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' DH';
}

function productImage(product) {
    const fallback = `images/products/placeholder-${product.category || 'storage'}.svg`;
    return product.image || fallback;
}

function productPageText(key, fallback) {
    return window.__marocPcI18n?.[key] || fallback;
}

const SPEC_KEY_MAP = {
    'VRAM': 'specVram',
    'CUDA Cores': 'specCudaCores',
    'Stream Processors': 'specCores',
    'Architecture': 'specArchitecture',
    'Boost Clock': 'specBoostClock',
    'Core Clock': 'specCoreClock',
    'Clock Speed': 'specClockSpeed',
    'Outputs': 'specOutputs',
    'Recommended PSU': 'specRecommendedPsu',
    'TDP': 'specTdp',
    'Power': 'specPower',
    'Cores': 'specCores',
    'Core Count': 'specCoreCount',
    'Memory': 'specMemory',
    'Type': 'specType',
    'Socket': 'specSocket',
    'Interface': 'specInterface',
    'PCIe': 'specPcie',
    'Wi-Fi': 'specWifi',
    'WiFi': 'specWifi',
    'Wattage': 'specWattage',
    'Capacity': 'specCapacity',
    'Form Factor': 'specFormFactor',
    'Speed': 'specSpeed',
    'Latency': 'specLatency',
    'Threads': 'specThreads',
    'Boost': 'specBoost',
    'Base Clock': 'specBaseClock',
    'M.2 Slots': 'specM2Slots',
    'SATA Ports': 'specSataPorts',
    'Max Memory': 'specMaxMemory',
    'PCIe x16': 'specPcieX16',
    'Size': 'specSize',
    'Resolution': 'specResolution',
    'Refresh Rate': 'specRefreshRate',
    'Panel': 'specPanel',
    'Response Time': 'specResponseTime',
    'HDR': 'specHdr',
    'Adaptive Sync': 'specAdaptiveSync',
    'Curvature': 'specCurvature',
  'Chipset': 'specChipset',
  'Memory Slots': 'specMemorySlots',
  'Cable': 'specCable',
  'Color': 'specColor',
  'Compatibility': 'specCompatibility',
  'Conductivity': 'specConductivity',
  'Connector': 'specConnector',
  'Connectors': 'specConnectors',
  'Display': 'specDisplay',
  'Efficiency': 'specEfficiency',
  'Fan': 'specFan',
  'Fan Size': 'specFanSize',
  'Fans': 'specFans',
  'Fit': 'specFit',
  'L3 Cache': 'specL3Cache',
  'Length': 'specLength',
  'Material': 'specMaterial',
  'Max TDP': 'specMaxTdp',
  'Modular': 'specModular',
  'Noise': 'specNoise',
  'Profile': 'specProfile',
  'Quantity': 'specQuantity',
  'Radiator': 'specRadiator',
  'Seq. Read': 'specSeqRead',
  'Seq. Write': 'specSeqWrite',
  'Socket Support': 'specSocketSupport',
  'TBW': 'specTbw',
  'Use Case': 'specUseCase',
  'Voltage': 'specVoltage',
  'Warning': 'specWarning'
};

function translateSpecKey(key) {
    const camel = SPEC_KEY_MAP[key];
    if (camel && window.__marocPcI18n?.[camel]) {
        return window.__marocPcI18n[camel];
    }
    return key;
}

function productPageTemplate(key, fallback, params = {}) {
    let value = productPageText(key, fallback);
    Object.entries(params).forEach(([name, replacement]) => {
        value = value.replaceAll(`{${name}}`, replacement);
    });
    return value;
}

function clampPurchaseQuantity(value, max = 99) {
    return Math.max(1, Math.min(max, Number.parseInt(value, 10) || 1));
}

function quantityControlMarkup(scopeId) {
    return `
        <div class="detail-quantity" data-quantity-scope="${scopeId}">
            <span class="detail-quantity-label">${productPageText('quantity', 'Quantity')}</span>
            <div class="detail-quantity-control">
                <button type="button" class="detail-qty-btn" data-qty-action="decrease" aria-label="${productPageText('decreaseQuantity', 'Decrease quantity')}">-</button>
                <input type="number" min="1" max="99" step="1" value="1" class="detail-qty-input" data-quantity-input inputmode="numeric">
                <button type="button" class="detail-qty-btn" data-qty-action="increase" aria-label="${productPageText('increaseQuantity', 'Increase quantity')}">+</button>
            </div>
        </div>
    `;
}

function reviewCountMarkup(count, loading = false) {
    if (loading) {
        return `(${productPageText('reviewsLoading', 'live reviews')})`;
    }
    const safeCount = Math.max(0, Number.parseInt(count, 10) || 0);
    return `(${safeCount} ${productPageText('reviews', 'reviews')})`;
}

async function fetchReviewSummary(productId) {
    const res = await fetch(`api/reviews.php?product_id=${encodeURIComponent(productId)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!res.ok || !data.success || !Array.isArray(data.reviews)) {
        throw new Error(data.error || 'Review summary unavailable');
    }

    const count = data.reviews.length;
    const avg = count
        ? data.reviews.reduce((sum, review) => sum + (Number(review.rating) || 0), 0) / count
        : 0;

    return { count, avg: Math.round(avg * 10) / 10 };
}

function productBadgeLabel(badge) {
    const labels = {
        New: productPageText('badgeNew', 'New'),
        Hot: productPageText('badgeHot', 'Hot'),
        Sale: productPageText('badgeSale', 'Sale'),
        'Low Stock': productPageText('badgeLowStock', 'Low Stock'),
        'Best Gaming': productPageText('badgeBestGaming', 'Best Gaming'),
        'Flagship': productPageText('badgeFlagship', 'Flagship'),
        'AMD Top': productPageText('badgeAmdTop', 'AMD Top'),
    };
    return labels[badge] || badge;
}

function productCategoryLabel(category) {
    const labels = {
        cpu: productPageText('categoryCpu', 'Processors'),
        gpu: productPageText('categoryGpu', 'Graphics Cards'),
        ram: productPageText('categoryRam', 'Memory / RAM'),
        motherboard: productPageText('categoryMotherboard', 'Motherboards'),
        storage: productPageText('categoryStorage', 'Storage'),
        cooling: productPageText('categoryCooling', 'Cooling'),
        psu: productPageText('categoryPsu', 'Power Supplies'),
        monitor: productPageText('categoryMonitor', 'Monitors'),
        accessories: productPageText('categoryAccessories', 'Accessories'),
        keyboard: productPageText('categoryKeyboard', 'Keyboards'),
        mouse: productPageText('categoryMouse', 'Mice'),
        vr: productPageText('categoryVr', 'VR Headsets'),
        router: productPageText('categoryRouter', 'Routers'),
        service: productPageText('categoryService', 'Services')
    };
    return labels[category] || category;
}

function normalizeProductSearchQuery(query) {
    const aliases = {
        processors: 'cpu',
        processor: 'cpu',
        graphics: 'gpu',
        'graphics cards': 'gpu',
        memory: 'ram',
        power: 'psu',
        'power supplies': 'psu',
        monitors: 'monitor',
        keyboards: 'keyboard',
        mice: 'mouse',
        mouses: 'mouse',
        vr: 'vr',
        'vr headset': 'vr',
        'vr headsets': 'vr',
        headset: 'vr',
        headsets: 'vr',
        router: 'router',
        routers: 'router',
        networking: 'router',
    };
    const normalized = String(query || '').toLowerCase().trim();
    return aliases[normalized] || normalized;
}

function productSearchText(product) {
    return [
        product.name,
        product.brand,
        product.category,
        productCategoryLabel(product.category),
        ...Object.values(product.specs || {}),
    ].join(' ').toLowerCase();
}

function productTrustMeta(product) {
    const warrantyByCategory = {
        cpu: productPageText('warranty3Year', '3-year warranty'),
        gpu: productPageText('warranty3Year', '3-year warranty'),
        ram: productPageText('warrantyLifetime', 'Lifetime warranty'),
        storage: productPageText('warranty5Year', '5-year warranty'),
        motherboard: productPageText('warranty3Year', '3-year warranty'),
        psu: productPageText('warranty7Year', '7-year warranty'),
        cooling: productPageText('warranty2Year', '2-year warranty'),
        monitor: productPageText('warranty3Year', '3-year warranty'),
        accessories: productPageText('warrantyAccessory', 'Accessory warranty'),
        keyboard: productPageText('warrantyAccessory', 'Accessory warranty'),
        mouse: productPageText('warrantyAccessory', 'Accessory warranty'),
        vr: productPageText('warrantyAccessory', 'Accessory warranty'),
        router: productPageText('warrantyAccessory', 'Accessory warranty'),
        service: productPageText('serviceGuarantee', 'Service guarantee')
    };
    const delivery = product.inStock ? productPageText('deliveryCasablanca', 'Casablanca 24-48h') : productPageText('restockAlert', 'Restock alert');
    const payment = product.price >= 3000 ? productPageText('installmentsAvailable', 'Installments available') : productPageText('codCardTransfer', 'COD / card / transfer');
    return [
        { icon: 'fa-shield-halved', label: warrantyByCategory[product.category] || productPageText('warrantyIncluded', 'Warranty included') },
        { icon: 'fa-truck-fast', label: delivery },
        { icon: 'fa-credit-card', label: payment },
        { icon: 'fa-screwdriver-wrench', label: productPageText('assemblyEligible', 'Assembly eligible') }
    ];
}

function productUseTags(product) {
    const tags = [];
    if (product.badge) tags.push(productBadgeLabel(product.badge));
    if (product.oldPrice) tags.push(productPageText('tagDeal', 'Deal'));
    if (['gpu', 'cpu'].includes(product.category)) tags.push(productPageText('tagCompareReady', 'Compare-ready'));
    if (product.category === 'motherboard') tags.push(productPageText('tagSocketMatch', 'Socket match'));
    if (product.category === 'psu') tags.push(productPageText('tagWattageChecked', 'Wattage checked'));
    if (product.category === 'ram') tags.push(productPageText('tagMemoryFinder', 'Memory finder'));
    if (product.category === 'accessories') tags.push(productPageText('tagBuildHelper', 'Build helper'));
    if (product.category === 'keyboard' || product.category === 'mouse') tags.push(productPageText('tagPeripheral', 'Peripheral'));
    if (product.category === 'vr') tags.push(productPageText('tagVrReady', 'VR ready'));
    if (product.category === 'router') tags.push(productPageText('tagNetworkGear', 'Network gear'));

    if (product.specs) {
        if (product.specs.Socket) tags.push(product.specs.Socket);
        const mem = String(product.specs.Memory || product.specs.Type || '');
        if (mem.includes('DDR5')) tags.push('DDR5');
        else if (mem.includes('DDR4')) tags.push('DDR4');
        else if (mem.includes('GDDR7')) tags.push('GDDR7');
        
        const pcie = String(product.specs.Interface || product.specs.PCIe || '');
        if (pcie.includes('5.0') || pcie.includes('Gen 5')) tags.push('PCIe 5.0');
        else if (pcie.includes('4.0') || pcie.includes('Gen 4')) tags.push('PCIe 4.0');
        
        if (product.specs.Architecture) tags.push(product.specs.Architecture);
    }

    return [...new Set(tags)].slice(0, 4);
}

const SPEC_EXPLAINERS = {
    'PCIe 5.0': ['specPcie5', 'Doubles PCIe 4.0 bandwidth for next-gen GPUs and NVMe drives.'],
    'PCIe 4.0': ['specPcie4', 'Current mainstream high-speed link for GPUs and NVMe SSDs.'],
    DDR5: ['specDdr5', 'Newer memory standard with higher bandwidth than DDR4.'],
    DDR4: ['specDdr4', 'Older memory standard, good value but limited upgrade headroom.'],
    AM5: ['specAm5', 'AMD socket with stronger forward upgrade path than AM4.'],
    AM4: ['specAm4', 'Mature AMD socket with excellent budget value.'],
    'LGA 1700': ['specLga1700', 'Intel socket used by 12th, 13th, and 14th gen CPUs.'],
    'LGA 1851': ['specLga1851', 'Newer Intel desktop socket for Core Ultra processors.'],
    'Zen 5': ['specZen5', 'AMD architecture focused on efficiency and gaming/workstation gains.'],
    Blackwell: ['specBlackwell', 'NVIDIA RTX 50 generation architecture.'],
    GDDR7: ['specGddr7', 'Newest GPU memory generation with very high bandwidth.'],
    NVMe: ['specNvme', 'Fast SSD protocol that connects through PCIe lanes.'],
};

function explainSpecValue(value) {
    const text = String(value || '');
    const key = Object.keys(SPEC_EXPLAINERS).find(token => text.toLowerCase().includes(token.toLowerCase()));
    if (!key) return '';
    const [i18nKey, fallback] = SPEC_EXPLAINERS[key];
    return productPageText(i18nKey, fallback);
}

function productStockFallback(product) {
    if (!product.inStock) return { in_stock: false, quantity: 0, reorder_level: 5, tone: 'out' };
    const quantity = ((Number(product.id) * 7) % 13) + 2;
    return {
        in_stock: true,
        quantity,
        reorder_level: 4,
        tone: quantity <= 3 ? 'critical' : quantity <= 7 ? 'low' : 'good',
    };
}

function stockLabel(stock) {
    if (!stock?.in_stock || stock.quantity <= 0) return productPageText('stockOut', '[STOCK: OUT]');
    if (stock.tone === 'critical') return productPageTemplate('stockCritical', '[STOCK: CRITICAL - {count} LEFT]', { count: stock.quantity });
    if (stock.tone === 'low') return productPageTemplate('stockLow', '[STOCK: LOW - {count} UNITS]', { count: stock.quantity });
    return productPageTemplate('stockUnits', '[STOCK: {count} UNITS]', { count: stock.quantity });
}

function inBoxChecklist(product) {
    const category = product.category;
    const name = String(product.name || '').toLowerCase();
    const specs = product.specs || {};
    const items = [];
    const warnings = [];
    const accessories = [];

    if (category === 'cpu') {
        items.push(productPageText('boxCpuOnly', 'CPU only'), productPageText('boxWarrantyCard', 'Warranty card'));
        const hasCooler = name.includes('9600') || name.includes('5600g') || name.includes('boxed cooler');
        if (hasCooler) items.push(productPageText('boxStockCooler', 'Stock cooler'));
        else {
            warnings.push(productPageText('warnNoStockCooler', 'No stock cooler expected. Add compatible cooling before checkout.'));
            accessories.push({ label: productPageText('accessoryCpuCoolers', 'CPU coolers'), href: 'products.php?category=cooling' });
        }
        accessories.push({ label: productPageText('accessoryThermalPaste', 'Thermal paste'), href: 'products.php?category=accessories&search=thermal' });
    } else if (category === 'motherboard') {
        items.push(productPageText('boxIoShield', 'I/O shield'), productPageText('boxM2Screws', 'M.2 screws'), productPageText('boxQuickStart', 'Quick start guide'));
        if (String(specs.WiFi || specs['Wi-Fi'] || '').toLowerCase().includes('yes') || name.includes('wifi')) {
            items.push(productPageText('boxWifiAntenna', 'Wi-Fi antenna'));
        }
        warnings.push(productPageText('warnLimitedSata', 'Most boards include only a limited number of SATA cables.'));
        accessories.push({ label: productPageText('accessorySataCable', 'SATA cable'), href: 'products.php?category=accessories&search=sata' });
    } else if (category === 'gpu') {
        items.push(productPageText('boxGraphicsCard', 'Graphics card'), productPageText('boxSupportWarranty', 'Support/warranty insert'));
        if (name.includes('4090') || name.includes('5080') || name.includes('5090')) {
            warnings.push(productPageText('warnPsuConnector', 'Check PSU connector support before assembly.'));
            accessories.push({ label: productPageText('accessoryPcieAdapter', 'PCIe power adapter'), href: 'products.php?category=accessories&search=pcie' });
        }
    } else if (category === 'storage') {
        items.push(productPageText('boxDriveOnly', 'Drive only'));
        if (name.includes('sata')) {
            warnings.push(productPageText('warnSataDataCable', 'SATA drives may not include a data cable.'));
            accessories.push({ label: productPageText('accessorySataCable', 'SATA cable'), href: 'products.php?category=accessories&search=sata' });
        } else {
            accessories.push({ label: productPageText('accessoryM2Heatsink', 'M.2 heatsink'), href: 'products.php?category=accessories&search=heatsink' });
        }
    } else if (category === 'cooling') {
        items.push(productPageText('boxCooler', 'Cooler'), productPageText('boxMountingHardware', 'Mounting hardware'));
        accessories.push({ label: productPageText('accessoryThermalPaste', 'Thermal paste'), href: 'products.php?category=accessories&search=thermal' });
    } else if (category === 'psu') {
        items.push(productPageText('boxPowerSupply', 'Power supply'), productPageText('boxAcCable', 'AC cable'), productPageText('boxModularCableSet', 'Modular cable set'));
        warnings.push(productPageText('warnGpuCableCount', 'Verify GPU cable count before choosing high-end RTX cards.'));
    } else {
        items.push(productPageText('boxRetailUnit', 'Retail unit'), productPageText('boxBasicDocumentation', 'Basic documentation'));
    }

    return { items, warnings, accessories };
}

const ProductsPage = {
    state: {
        products: [],
        filtered: [],
        filters: {
            categories: [],
            brands: [],
            minPrice: 0,
            maxPrice: 30000,
            rating: 0,
            availability: ['instock']
        },
        sort: 'featured',
        view: 'grid',
        page: 1,
        perPage: 9,
        stock: {},
        wishlist: new Set(),
        compareList: []
    },

    init() {
        this.state.products = [...products];
        this.state.filtered = [...products];
        this.bindEvents();
        if (this.applyInitialUrlFilters()) {
            this.applyFilters();
        } else {
            this.render();
        }
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchParam) {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = searchParam;
            this.handleSearch(searchParam);
        }
        this.renderActiveFilters();
        this.loadStock();
        this.handleProductRoute();
    },

    async loadStock() {
        try {
            const res = await fetch('api/products-stock.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.success && data.stock) {
                this.state.stock = data.stock;
                this.state.products = this.state.products.map(product => {
                    const stock = this.getStock(product);
                    return { ...product, inStock: Boolean(stock.in_stock) };
                });
                this.applyFilters();
                this.handleProductRoute();
            }
        } catch (error) {
            console.warn('Stock API unavailable, using catalog availability.', error);
        }
    },

    getStock(product) {
        return this.state.stock[String(product.id)] || productStockFallback(product);
    },

    applyInitialUrlFilters() {
        const params = new URLSearchParams(window.location.search);
        const categoryAliases = {
            processors: 'cpu',
            graphics: 'gpu',
            memory: 'ram',
            motherboards: 'motherboard',
            motherboard: 'motherboard',
            power: 'psu',
            cases: 'case',
            monitors: 'monitor',
            monitor: 'monitor',
            accessory: 'accessories',
            accessories: 'accessories',
            keyboard: 'keyboard',
            keyboards: 'keyboard',
            mouse: 'mouse',
            mice: 'mouse',
            mouses: 'mouse',
            vr: 'vr',
            'vr-headsets': 'vr',
            'vr headsets': 'vr',
            headset: 'vr',
            headsets: 'vr',
            router: 'router',
            routers: 'router',
            networking: 'router'
        };
        let changed = false;

        const categoryParam = params.get('category');
        if (categoryParam) {
            const categoryKey = categoryParam.toLowerCase().trim();
            const category = categoryAliases[categoryKey] || categoryKey;
            this.state.filters.categories = [category];
            const checkbox = document.querySelector(`input[name="category"][value="${category}"]`);
            if (checkbox) checkbox.checked = true;
            changed = true;
        }

        return changed;
    },

    bindEvents() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        const sidebarInput = document.querySelector('.sidebar-search input');
        const sidebarBtn = document.querySelector('.sidebar-search button');

        if (sidebarInput && sidebarBtn) {
            sidebarInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });

            sidebarInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') this.handleSearch(sidebarInput.value);
            });

            sidebarBtn.addEventListener('click', () => {
                this.handleSearch(sidebarInput.value);
            });
        }

        document.querySelectorAll('input[name="category"]').forEach(cb => {
            cb.addEventListener('change', () => this.updateFilters());
        });

        document.querySelectorAll('input[name="brand"]').forEach(cb => {
            cb.addEventListener('change', () => this.updateFilters());
        });

        document.querySelectorAll('input[name="rating"]').forEach(rb => {
            rb.addEventListener('change', () => this.updateFilters());
        });

        document.querySelectorAll('input[name="availability"]').forEach(cb => {
            cb.addEventListener('change', () => this.updateFilters());
        });

        // Prix
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        const priceSlider = document.getElementById('priceSlider');

        if (minPrice) minPrice.addEventListener('input', () => this.updateFilters());
        if (maxPrice) maxPrice.addEventListener('input', () => this.updateFilters());
        if (priceSlider) {
            priceSlider.addEventListener('input', (e) => {
                if (maxPrice) maxPrice.value = e.target.value;
                this.updateFilters();
            });
        }

        // classement
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.state.sort = e.target.value;
                this.applySort();
                this.render();
            });
        }

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.state.view = btn.dataset.view;
                this.render();
            });
        });

        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearFilters());
        }

        const applyBtn = document.getElementById('applyFilters');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                this.updateFilters();
                document.querySelector('.filters-sidebar')?.classList.remove('open');
                document.querySelector('.filters-overlay')?.classList.remove('active');
            });
        }

        const filtersToggle = document.querySelector('.filters-toggle');
        if (filtersToggle) {
            filtersToggle.addEventListener('click', () => {
                document.querySelector('.filters-sidebar')?.classList.add('open');
                document.querySelector('.filters-overlay')?.classList.add('active');
            });
        }

        document.querySelector('.modal-close')?.addEventListener('click', () => this.closeModal());
        document.querySelector('.modal-overlay')?.addEventListener('click', () => this.closeModal());
        document.getElementById('productDetailBack')?.addEventListener('click', (e) => {
            e.preventDefault();
            history.pushState({}, '', 'products.php');
            this.showCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        window.addEventListener('popstate', () => this.handleProductRoute());

        document.getElementById('clearCompareBtn')?.addEventListener('click', () => {
            this.state.compareList = [];
            this.render();
            this.renderCompareBar();
        });
        document.getElementById('compareBtn')?.addEventListener('click', () => {
            this.openCompareModal();
        });
        document.getElementById('compareModalClose')?.addEventListener('click', () => {
            document.getElementById('compareModal')?.classList.remove('active');
            document.body.style.overflow = '';
        });
        document.getElementById('compareModal')?.querySelector('.modal-overlay')?.addEventListener('click', () => {
            document.getElementById('compareModal')?.classList.remove('active');
            document.body.style.overflow = '';
        });

        document.querySelectorAll('.bundle-add-btn').forEach(btn => {
            btn.addEventListener('click', () => this.addBundle(btn.dataset.bundle));
        });
    },

    addBundle(bundleKey) {
        const bundles = {
            'am5-core': [16, 19, 14],
            'creator-kit': [5, 8, 9, 22],
            'service-kit': [
                { id: 'service-assembly', name: productPageText('serviceAssembly', 'Professional PC Assembly'), brand: 'Maroc PC', category: 'service', price: 299, image: 'logo.png', inStock: true, specs: { Type: productPageText('buildService', 'Build service') } },
                { id: 'service-bios', name: productPageText('serviceBios', 'BIOS Update'), brand: 'Maroc PC', category: 'service', price: 99, image: 'logo.png', inStock: true, specs: { Type: productPageText('buildService', 'Build service') } },
                { id: 'service-stress', name: productPageText('serviceStress', 'Stress Test Report'), brand: 'Maroc PC', category: 'service', price: 149, image: 'logo.png', inStock: true, specs: { Type: productPageText('buildService', 'Build service') } }
            ]
        };
        const bundle = bundles[bundleKey] || [];
        const items = bundle.map(item => typeof item === 'number' ? this.state.products.find(p => p.id === item) : item).filter(Boolean);
        items.forEach(item => Cart.add(item));
        if (typeof showToast === 'function') showToast(productPageTemplate('bundleItemsAdded', 'Added {count} bundle items.', { count: items.length }), 'success');
    },

    _searchLogTimer: null,

    handleSearch(query) {
        const q = normalizeProductSearchQuery(query);
        if (!q) { this.applyFilters(); return; }
        this.state.filtered = this.state.products.filter(p => productSearchText(p).includes(q));
        this.state.page = 1;
        this.render();
        clearTimeout(this._searchLogTimer);
        this._searchLogTimer = setTimeout(() => {
            try { fetch('api/search-log.php', { method: 'POST', headers: {'Content-Type':'application/json'}, credentials: 'same-origin', body: JSON.stringify({query: q, results_count: this.state.filtered.length}) }).catch(() => {}); } catch(e) {}
        }, 1500);
    },

    updateFilters() {
        this.state.filters.categories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value);
        this.state.filters.brands = Array.from(document.querySelectorAll('input[name="brand"]:checked')).map(cb => cb.value);
        const ratingEl = document.querySelector('input[name="rating"]:checked');
        this.state.filters.rating = ratingEl ? parseInt(ratingEl.value) : 0;
        this.state.filters.availability = Array.from(document.querySelectorAll('input[name="availability"]:checked')).map(cb => cb.value);
        const minP = document.getElementById('minPrice');
        const maxP = document.getElementById('maxPrice');
        this.state.filters.minPrice = minP?.value ? parseInt(minP.value) : 0;
        this.state.filters.maxPrice = maxP?.value ? parseInt(maxP.value) : 30000;
        this.applyFilters();
        this.renderActiveFilters();
    },

    applyFilters() {
        let result = [...this.state.products];
        if (this.state.filters.categories.length > 0) result = result.filter(p => this.state.filters.categories.includes(p.category));
        if (this.state.filters.brands.length > 0) result = result.filter(p => this.state.filters.brands.includes(p.brand));
        result = result.filter(p => p.price >= this.state.filters.minPrice && p.price <= this.state.filters.maxPrice);
        if (this.state.filters.rating > 0) result = result.filter(p => p.rating >= this.state.filters.rating);
        if (this.state.filters.availability.length > 0) {
            result = result.filter(p => {
                if (p.inStock && this.state.filters.availability.includes('instock')) return true;
                if (!p.inStock && this.state.filters.availability.includes('preorder')) return true;
                return false;
            });
        }
        this.state.filtered = result;
        this.state.page = 1;
        this.applySort();
        this.render();
    },

    applySort() {
        const sort = this.state.sort;
        this.state.filtered.sort((a, b) => {
            switch (sort) {
                case 'price-low': return a.price - b.price;
                case 'price-high': return b.price - a.price;
                case 'rating': return b.rating - a.rating;
                case 'newest': return b.id - a.id;
                default: return 0;
            }
        });
    },

    clearFilters() {
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('input[type="radio"]').forEach(rb => rb.checked = false);
        const minP = document.getElementById('minPrice');
        const maxP = document.getElementById('maxPrice');
        const slider = document.getElementById('priceSlider');
        if (minP) minP.value = '';
        if (maxP) maxP.value = '';
        if (slider) slider.value = 30000;
        document.querySelectorAll('input[name="availability"]').forEach(cb => { cb.checked = cb.value === 'instock'; });
        this.state.filters = { categories: [], brands: [], minPrice: 0, maxPrice: 30000, rating: 0, availability: ['instock'] };
        this.applyFilters();
        this.renderActiveFilters();
    },

    renderActiveFilters() {
        const container = document.getElementById('activeFilters');
        if (!container) return;
        const tags = [];
        const filters = this.state.filters;
        filters.categories.forEach(c => { tags.push({ type: 'category', value: c, label: productCategoryLabel(c) }); });
        filters.brands.forEach(b => { tags.push({ type: 'brand', value: b, label: b }); });
        if (filters.rating > 0) tags.push({ type: 'rating', value: filters.rating, label: productPageTemplate('starsAndUp', '{rating}+ Stars', { rating: filters.rating }) });
        if (filters.minPrice > 0 || filters.maxPrice < 3000) {
            const minFormatted = window.formatMAD ? window.formatMAD(filters.minPrice, {showSymbol: false}) : filters.minPrice.toLocaleString();
            const maxFormatted = window.formatMAD ? window.formatMAD(filters.maxPrice) : filters.maxPrice.toLocaleString() + ' DH';
            tags.push({ type: 'price', value: 'price', label: `${minFormatted} - ${maxFormatted}` });
        }
        container.innerHTML = tags.map(tag => `<span class="filter-tag" data-type="${tag.type}" data-value="${tag.value}">${tag.label} <i class="fas fa-times"></i></span>`).join('');
        container.querySelectorAll('.filter-tag').forEach(tag => { tag.addEventListener('click', () => { this.removeFilter(tag.dataset.type, tag.dataset.value); }); });
    },

    removeFilter(type, value) {
        switch (type) {
            case 'category':
                this.state.filters.categories = this.state.filters.categories.filter(c => c !== value);
                document.querySelector(`input[name="category"][value="${value}"]`).checked = false;
                break;
            case 'brand':
                this.state.filters.brands = this.state.filters.brands.filter(b => b !== value);
                document.querySelector(`input[name="brand"][value="${value}"]`).checked = false;
                break;
            case 'rating':
                this.state.filters.rating = 0;
                document.querySelectorAll('input[name="rating"]').forEach(rb => rb.checked = false);
                break;
            case 'price':
                this.state.filters.maxPrice = 30000;
                document.getElementById('minPrice').value = '';
                document.getElementById('maxPrice').value = '';
                document.getElementById('priceSlider').value = 30000;
                break;
        }
        this.applyFilters();
        this.renderActiveFilters();
    },

    render() {
        const grid = document.getElementById('productsGrid');
        const countEl = document.getElementById('productCount');
        if (!grid) return;

        if (countEl) countEl.textContent = this.state.filtered.length;

        grid.className = `products-grid ${this.state.view === 'list' ? 'list-view' : ''}`;

        const start = (this.state.page - 1) * this.state.perPage;
        const end = start + this.state.perPage;
        const pageItems = this.state.filtered.slice(start, end);

        if (pageItems.length === 0) {
            grid.innerHTML = `
                <div class="text-center" style="grid-column: 1/-1; padding: 60px;">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--muted); margin-bottom: 16px;"></i>
                    <p style="color: var(--muted);">${window.__marocPcI18n?.noProducts || 'No products found matching your criteria.'}</p>
                </div>
            `;
            this.renderPagination();
            return;
        }

        grid.innerHTML = pageItems.map(product => this.createProductCard(product)).join('');
        this.hydrateVisibleReviewSummaries(grid);

        // Add to cart
        grid.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                const product = this.state.products.find(p => p.id === id);
                if (product) {
                    Cart.add(product);
                    btn.classList.add('added');
                    btn.innerHTML = `<i class="fas fa-check"></i> ${productPageText('cartAdded', 'Added!')}`;
                    setTimeout(() => {
                        btn.classList.remove('added');
                        btn.innerHTML = `<i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addToCart || 'Add to Cart'}`;
                    }, 2000);
                }
            });
        });

        // Restock notifications are handled globally by cart.js as a toast form.

        grid.querySelectorAll('.see-alternatives-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const id = parseInt(e.currentTarget.dataset.id, 10);
                const product = this.state.products.find(p => p.id === id);
                if (product) this.openAlternativesModal(product, e.currentTarget);
            });
        });

        // Wishlist
        grid.querySelectorAll('.product-wishlist').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                if (typeof Wishlist !== 'undefined') {
                    const isActive = await Wishlist.toggle(id);
                    if (isActive === null) return;
                    if (typeof showToast === 'function') {
                        showToast(isActive ? productPageText('addedToWishlist', 'Added to wishlist!') : productPageText('removedFromWishlist', 'Removed from wishlist.'));
                    }
                }
            });
        });

        // Quick view
        grid.querySelectorAll('.product-quickview').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = parseInt(e.currentTarget.dataset.id);
                const product = this.state.products.find(p => p.id === id);
                if (product) this.openModal(product);
            });
        });

        // Compare
        grid.querySelectorAll('.compare-checkbox-input').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                this.handleCompareToggle(id, e.currentTarget.checked);
            });
        });

        this.renderPagination();
    },

    createProductCard(product) {
        const discount = product.oldPrice
            ? Math.round(((product.oldPrice - product.price) / product.oldPrice) * 100)
            : 0;
        const isWishlisted = typeof Wishlist !== 'undefined' ? Wishlist.has(product.id) : false;
        const trust = productTrustMeta(product);
        const useTags = productUseTags(product);
        const stock = this.getStock(product);
        const available = Boolean(stock.in_stock);
        let samBadge = '';
        if (product.category === 'gpu' && String(product.brand).toLowerCase() === 'amd') {
            try {
                const builderData = JSON.parse(localStorage.getItem('pcBuilderData') || '{}');
                if (builderData.components && builderData.components.cpu && String(builderData.components.cpu.brand).toLowerCase() === 'amd') {
                    samBadge = `<span class="product-badge badge-sam" style="background:#dc2626;color:white;left:auto;right:10px;top:10px;border-color:#b91c1c;" title="${productPageText('samReadyTitle', 'Smart Access Memory enabled with your selected AMD CPU')}"><i class="fas fa-bolt"></i> ${productPageText('samReady', 'SAM READY')}</span>`;
                }
            } catch(e) {}
        }

        return `
            <article class="product-card">
                <div class="product-img-wrap">
                    <img src="${productImage(product)}" alt="${product.name}" class="product-img" loading="lazy" onerror="this.src='images/products/generic-laptop.png'">
                    ${product.badge ? `<span class="product-badge badge-${product.badge.toLowerCase()}">${productBadgeLabel(product.badge)}</span>` : ''}
                    ${samBadge}
                    <button class="product-wishlist ${isWishlisted ? 'active' : ''}" data-id="${product.id}" aria-label="${productPageText('addToWishlist', 'Add to wishlist')}">
                        <i class="${isWishlisted ? 'fas' : 'far'} fa-heart"></i>
                    </button>
                    <a class="product-quickview" href="products.php?product=${product.id}" data-id="${product.id}">${window.__marocPcI18n?.viewDetails || 'View Details'}</a>
                </div>
                <div class="product-card-body">
                    <div class="product-category">${productCategoryLabel(product.category)}</div>
                    <h3 class="product-name">${product.name}</h3>
                    <div class="stock-readout ${stock.tone}">${stockLabel(stock)}</div>
                    <div class="product-rating" data-review-summary data-product-id="${product.id}">
                        <div class="stars">${this.renderStars(product.rating)}</div>
                        <span class="product-reviews">${reviewCountMarkup(product.reviews, true)}</span>
                    </div>
                    <div class="product-use-tags">
                        ${useTags.map(tag => {
                            const explainer = explainSpecValue(tag);
                            return `<span ${explainer ? `title="${explainer}" style="cursor: help;"` : ''}>${tag}${explainer ? ' <i class="fas fa-info-circle" style="font-size: 0.65rem; opacity: 0.7; margin-left: 2px;"></i>' : ''}</span>`;
                        }).join('')}
                    </div>
                    <div class="product-price-row">
                        <span class="product-price">${formatMAD(product.price)}</span>
                        ${product.oldPrice ? `<span class="product-old-price">${formatMAD(product.oldPrice)}</span>` : ''}
                        ${discount > 0 ? `<span class="product-discount">-${discount}%</span>` : ''}
                    </div>
                    ${typeof Installment !== 'undefined' ? Installment.badge(product.price) : ''}
                    <div class="product-trust-row">
                        ${trust.slice(0, 3).map(item => `<span><i class="fas ${item.icon}"></i>${item.label}</span>`).join('')}
                    </div>
                    <div class="product-actions">
                        <label class="compare-label" style="display:flex; align-items:center; gap:6px; font-size:0.8rem; cursor:pointer;">
                            <input type="checkbox" class="compare-checkbox-input" data-id="${product.id}" ${this.state.compareList.includes(product.id) ? 'checked' : ''}> ${window.__marocPcI18n?.compare || 'Compare'}
                        </label>
                        ${available
                            ? `<button class="add-to-cart-btn" data-id="${product.id}">
                                   <i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addToCart || 'Add to Cart'}
                               </button>`
                            : `<div style="display:flex;flex-direction:column;gap:6px;width:100%">
                                 <button class="notify-restock-btn" data-id="${product.id}" data-name="${product.name}" style="background:var(--page-bg-3);border-color:var(--border);color:var(--muted);width:100%;height:40px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:700;font-family:'Syne',sans-serif;">
                                   <i class="fas fa-bell"></i> ${window.__marocPcI18n?.notifyMe || 'Notify Me'}
                                 </button>
                                 <button class="see-alternatives-btn" data-id="${product.id}" data-category="${product.category}" style="background:rgba(0,245,212,0.08);border:1px solid rgba(0,245,212,0.25);color:var(--cyan);width:100%;height:36px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:0.8rem;font-weight:700;font-family:'Syne',sans-serif;">
                                   <i class="fas fa-shuffle"></i> ${window.__marocPcI18n?.seeAlternatives || 'See Alternatives'}
                                 </button>
                               </div>`
                        }
                    </div>
                </div>
            </article>
        `;
    },

    localAlternatives(product) {
        return this.state.products
            .filter(item => item.id !== product.id && item.category === product.category)
            .filter(item => this.getStock(item).in_stock)
            .sort((a, b) => (b.rating - a.rating) || (a.price - b.price))
            .slice(0, 6);
    },

    async openAlternativesModal(product, trigger = null) {
        const modal = document.getElementById('quickViewModal');
        const content = document.getElementById('quickViewContent');
        if (!modal || !content) return;

        const originalHtml = trigger?.innerHTML;
        if (trigger) {
            trigger.disabled = true;
            trigger.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${productPageText('loadingAlternatives', 'Loading alternatives')}`;
        }

        let alternatives = this.localAlternatives(product);

        try {
            const params = new URLSearchParams({
                category: product.category,
                exclude_id: String(product.id),
            });
            const res = await fetch(`api/alternatives.php?${params.toString()}`, { credentials: 'same-origin' });
            const data = await res.json();
            if (res.ok && data.success && Array.isArray(data.alternatives) && data.alternatives.length) {
                const byId = new Map(this.state.products.map(item => [String(item.id), item]));
                alternatives = data.alternatives.map(item => byId.get(String(item.id)) || item).slice(0, 6);
            }
        } catch (error) {
            console.warn('Alternatives API unavailable, using local catalog alternatives.', error);
        } finally {
            if (trigger) {
                trigger.disabled = false;
                trigger.innerHTML = originalHtml;
            }
        }

        content.innerHTML = `
            <div class="modal-details alternatives-modal">
                <div class="product-category">${productCategoryLabel(product.category)}</div>
                <h2>${productPageTemplate('alternativesFor', 'Alternatives for {name}', { name: product.name })}</h2>
                ${alternatives.length ? `
                    <div class="alternatives-grid">
                        ${alternatives.map(item => `
                            <article class="alternative-product">
                                <img src="${productImage(item)}" alt="${item.name}" onerror="this.src='images/products/placeholder-storage.svg'">
                                <div class="alternative-product-info">
                                    <span class="alternative-product-brand">${item.brand}</span>
                                    <strong>${item.name}</strong>
                                    <span class="alternative-product-price">${formatMAD(item.price)}</span>
                                </div>
                                <div class="alternative-product-actions">
                                    <button type="button" class="btn btn-primary alt-add-to-cart" data-id="${item.id}">
                                        <i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addToCart || 'Add to Cart'}
                                    </button>
                                    <a href="products.php?product=${item.id}" class="btn btn-outline">${window.__marocPcI18n?.viewDetails || 'View Details'}</a>
                                </div>
                            </article>
                        `).join('')}
                    </div>
                ` : `
                    <p class="description">${productPageText('noAlternativesFound', 'No in-stock alternatives found in this category yet.')}</p>
                `}
            </div>
        `;

        content.querySelectorAll('.alt-add-to-cart').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = this.state.products.find(p => p.id === parseInt(btn.dataset.id, 10));
                if (item) Cart.add(item);
            });
        });

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    renderStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= Math.floor(rating)) {
                html += '<i class="fas fa-star"></i>';
            } else if (i === Math.ceil(rating) && !Number.isInteger(rating)) {
                html += '<i class="fas fa-star-half-alt"></i>';
            } else {
                html += '<i class="far fa-star"></i>';
            }
        }
        return html;
    },

    handleProductRoute() {
        const params = new URLSearchParams(window.location.search);
        const id = parseInt(params.get('product'), 10);
        const product = this.state.products.find(p => p.id === id);

        if (product) {
            this.renderProductDetail(product);
        } else {
            this.showCatalog();
        }
    },

    showCatalog() {
        document.querySelector('.products-page')?.removeAttribute('hidden');
        document.getElementById('productDetailPage')?.setAttribute('hidden', '');
        document.body.style.overflow = '';
        document.title = window.__storePageTitle || 'Products - MarocPC';
    },

    openProductDetail(product) {
        const url = `${window.location.pathname}?product=${encodeURIComponent(product.id)}`;
        history.pushState({ productId: product.id }, '', url);
        this.renderProductDetail(product);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    renderProductDetail(product) {
        const page = document.getElementById('productDetailPage');
        const content = document.getElementById('productDetailContent');
        const catalog = document.querySelector('.products-page');
        if (!page || !content || !catalog) return;

        catalog.setAttribute('hidden', '');
        page.removeAttribute('hidden');
        content.innerHTML = this.createProductDetailMarkup(product);
        document.body.style.overflow = '';
        document.title = `${product.name} - MarocPC`;
        this.bindProductDetailActions(content, product);

        try {
            fetch('api/recommendations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'log', product_id: product.id, recommendation_score: product.rating || 0.5, context_trigger: 'detail_page' })
            }).catch(() => {});
        } catch (e) { /* non-blocking */ }
    },

    createProductDetailMarkup(product) {
        const discount = product.oldPrice
            ? Math.round(((product.oldPrice - product.price) / product.oldPrice) * 100)
            : 0;
        const trust = productTrustMeta(product);
        const useTags = productUseTags(product);
        const stock = this.getStock(product);
        const box = inBoxChecklist(product);
        const specs = product.specs || {};
        const related = this.state.products
            .filter(item => item.id !== product.id && item.category === product.category)
            .slice(0, 3);

        return `
            <div class="product-detail-hero">
                <div class="product-detail-media">
                    ${product.badge ? `<span class="product-badge badge-${String(product.badge).toLowerCase()}">${productBadgeLabel(product.badge)}</span>` : ''}
                    <img src="${productImage(product)}" alt="${product.name}" onerror="this.src='images/products/placeholder-storage.svg'">
                </div>

                <div class="product-detail-summary">
                    <div class="product-detail-kicker">${product.brand} / ${productCategoryLabel(product.category)}</div>
                    <h1>${product.name}</h1>
                    <div class="product-rating" data-review-summary data-product-id="${product.id}">
                        <div class="stars">${this.renderStars(product.rating)}</div>
                        <span class="product-reviews">${reviewCountMarkup(product.reviews, true)}</span>
                    </div>
                    <div class="stock-readout modal-stock ${stock.tone}">${stockLabel(stock)}</div>
                    <div class="product-detail-price-row">
                        <span class="product-price">${formatMAD(product.price)}</span>
                        ${product.oldPrice ? `<span class="product-old-price">${formatMAD(product.oldPrice)}</span>` : ''}
                        ${discount > 0 ? `<span class="product-discount">-${discount}%</span>` : ''}
                    </div>
                    <p class="product-detail-copy">${productPageTemplate('productDetailDescription', 'Engineering-grade {category} from {brand}, selected for performance, compatibility, and reliable build planning.', { category: productCategoryLabel(product.category), brand: product.brand })}</p>
                    <div class="product-use-tags modal-tags">
                        ${useTags.map(tag => `<span>${tag}</span>`).join('')}
                    </div>
                    ${quantityControlMarkup(`product-${product.id}`)}
                    <div class="product-detail-actions" data-quantity-scope="product-${product.id}">
                        ${stock.in_stock
                            ? `<button class="btn btn-primary detail-add-to-cart" data-id="${product.id}"><i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addToCart || 'Add to Cart'}</button>`
                            : `<button class="btn btn-secondary detail-notify-restock" data-id="${product.id}" data-name="${product.name}"><i class="fas fa-bell"></i> ${window.__marocPcI18n?.notifyMe || 'Notify Me'}</button>`
                        }
                        <button class="btn btn-outline detail-compare-toggle" data-id="${product.id}">
                            <i class="fas fa-code-compare"></i> ${window.__marocPcI18n?.compare || 'Compare'}
                        </button>
                    </div>
                    ${typeof Installment !== 'undefined' ? Installment.widget(product.price, 'detailInstallment') : ''}
                </div>
            </div>

            <div class="product-detail-grid">
                <section class="product-detail-panel product-detail-specs">
                    <header>
                        <span>${productPageText('diagnosticSheet', 'Diagnostic Sheet')}</span>
                        <h2>${productPageText('technicalSpecification', 'Technical Specification')}</h2>
                    </header>
                    <div class="specs">
                        ${Object.entries(specs).map(([key, val]) => `
                            <div class="spec-item ${explainSpecValue(val) ? 'has-explainer' : ''}" ${explainSpecValue(val) ? `data-explainer="${explainSpecValue(val)}"` : ''}>
                                <div class="spec-key">${translateSpecKey(key)}</div>
                                <div class="spec-val">${val}</div>
                            </div>
                        `).join('')}
                    </div>
                </section>

                <aside class="product-detail-panel">
                    <header>
                        <span>${window.__marocPcI18n?.purchaseConfidence || 'Purchase Confidence'}</span>
                        <h2>${window.__marocPcI18n?.serviceSignals || 'Service Signals'}</h2>
                    </header>
                    <div class="trust-grid">
                        ${trust.map(item => `
                            <div class="trust-item">
                                <i class="fas ${item.icon}"></i>
                                <span>${item.label}</span>
                            </div>
                        `).join('')}
                        <div class="trust-item">
                            <i class="fab fa-whatsapp"></i>
                            <span>${window.__marocPcI18n?.whatsappAdvice || 'WhatsApp advice before buying'}</span>
                        </div>
                        <div class="trust-item">
                            <i class="fas fa-box-open"></i>
                            <span>${productPageText('openBoxDeals', 'Open-box deals when available')}</span>
                        </div>
                    </div>
                </aside>

                <section class="product-detail-panel">
                    <header>
                        <span>${productPageText('assemblyPrep', 'Assembly Prep')}</span>
                        <h2>${productPageText('inTheBox', 'In The Box')}</h2>
                    </header>
                    <div class="in-box-body open">
                        <div>
                            <strong>${productPageText('included', 'Included')}</strong>
                            <ul>${box.items.map(item => `<li>${item}</li>`).join('')}</ul>
                        </div>
                        ${box.warnings.length ? `
                            <div>
                                <strong>${productPageText('checkBeforeAssembly', 'Check before assembly')}</strong>
                                <ul>${box.warnings.map(item => `<li>${item}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${box.accessories.length ? `
                            <div class="in-box-links">
                                ${box.accessories.map(item => `<a href="${item.href}">${item.label}</a>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </section>

                <section class="product-detail-panel product-detail-intel">
                    <header>
                        <span>${window.__marocPcI18n?.buyingIntelligence || 'Buying Intelligence'}</span>
                        <h2>${window.__marocPcI18n?.priceTools || 'Price Tools'}</h2>
                    </header>
                    <div id="detailPriceChart" class="modal-price-chart"></div>
                    <div class="price-match-box">
                        <div class="price-alert-head">
                            <span><i class="fas fa-scale-balanced"></i> ${productPageText('seenItCheaper', 'Seen it cheaper?')}</span>
                            <small>${productPageText('sendToAdminReview', 'Send it to admin for review')}</small>
                        </div>
                        <div class="price-alert-controls">
                            <label>
                                <span>${window.__marocPcI18n?.competitorUrl || 'Competitor URL'}</span>
                                <input type="url" id="detailPriceMatchUrl" placeholder="${productPageText('competitorUrlPlaceholder', 'Jumia, Avito, store link')}">
                            </label>
                            <label>
                                <span>${window.__marocPcI18n?.seenPrice || 'Seen price'}</span>
                                <input type="number" id="detailPriceMatchPrice" min="1" step="0.01" placeholder="${Math.round(product.price * 0.92)}">
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline price-alert-btn" id="detailSendPriceMatchBtn">
                            <i class="fas fa-paper-plane"></i> ${window.__marocPcI18n?.requestPriceMatch || 'Request price match'}
                        </button>
                        <div class="price-alert-status" id="detailPriceMatchStatus" aria-live="polite"></div>
                    </div>
                    <div class="price-alert-box">
                        <div class="price-alert-head">
                            <span><i class="fas fa-bell"></i> ${window.__marocPcI18n?.priceDropAlert || 'Price Drop Alert'}</span>
                            <small>${window.__marocPcI18n?.current || 'Current'}: ${formatMAD(product.price)}</small>
                        </div>
                        <div class="price-alert-controls">
                            <label>
                                <span>${productPageText('alertBelow', 'Alert below')}</span>
                                <input type="number" id="detailPriceAlertThreshold" min="1" step="0.01" value="${Math.max(1, Math.floor(product.price * 0.95))}">
                            </label>
                            <label>
                                <span>${window.__marocPcI18n?.channel || 'Channel'}</span>
                                <select id="detailPriceAlertChannel">
                                    <option value="email">${window.__marocPcI18n?.email || 'Email'}</option>
                                    <option value="whatsapp">${productPageText('whatsapp', 'WhatsApp')}</option>
                                    <option value="both">${productPageText('both', 'Both')}</option>
                                </select>
                            </label>
                        </div>
                        <div class="price-alert-controls">
                            <label>
                                <span>${window.__marocPcI18n?.email || 'Email'}</span>
                                <input type="email" id="detailPriceAlertEmail" placeholder="${window.__marocPcI18n?.useAccountEmail || 'Use account email'}">
                            </label>
                            <label>
                                <span>${productPageText('whatsapp', 'WhatsApp')}</span>
                                <input type="tel" id="detailPriceAlertPhone" placeholder="+212600000000">
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline price-alert-btn" id="detailCreatePriceAlertBtn">
                            <i class="fas fa-bell"></i> ${window.__marocPcI18n?.alertPriceDrops || 'Alert me when price drops'}
                        </button>
                        <div class="price-alert-status" id="detailPriceAlertStatus" aria-live="polite"></div>
                    </div>
                </section>
            </div>

            <section class="product-detail-panel product-detail-reviews" data-reviews-mount></section>

            ${related.length ? `
                <section class="product-detail-related">
                    <header>
                        <span>${productPageText('sameCategory', 'Same category')}</span>
                        <h2>${productPageText('relatedComponents', 'Related Components')}</h2>
                    </header>
                    <div class="product-detail-related-grid">
                        ${related.map(item => `
                            <a href="products.php?product=${item.id}" class="related-product" data-id="${item.id}">
                                <img src="${productImage(item)}" alt="${item.name}" onerror="this.src='images/products/placeholder-storage.svg'">
                                <strong>${item.name}</strong>
                                <span>${formatMAD(item.price)}</span>
                            </a>
                        `).join('')}
                    </div>
                </section>
            ` : ''}
        `;
    },

    async hydrateVisibleReviewSummaries(scope = document) {
        const nodes = Array.from(scope.querySelectorAll('[data-review-summary]'));
        await Promise.all(nodes.map(async node => {
            const productId = Number.parseInt(node.dataset.productId || '0', 10);
            if (!productId) return;
            try {
                const summary = await fetchReviewSummary(productId);
                const product = this.state.products.find(item => Number(item.id) === productId);
                if (product) {
                    product.reviews = summary.count;
                    if (summary.avg > 0) {
                        product.rating = summary.avg;
                    }
                }
                const stars = node.querySelector('.stars');
                const reviews = node.querySelector('.product-reviews');
                if (stars) stars.innerHTML = this.renderStars(summary.avg || product?.rating || 0);
                if (reviews) reviews.textContent = reviewCountMarkup(summary.count);
            } catch (_) {
                const reviews = node.querySelector('.product-reviews');
                if (reviews) reviews.textContent = reviewCountMarkup(0);
            }
        }));
    },

    bindProductDetailActions(content, product) {
        const quantityInput = content.querySelector('[data-quantity-input]');
        const applyQuantity = (nextValue) => {
            if (!quantityInput) return 1;
            const safeValue = clampPurchaseQuantity(nextValue);
            quantityInput.value = String(safeValue);
            return safeValue;
        };

        content.querySelectorAll('.detail-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const delta = btn.dataset.qtyAction === 'increase' ? 1 : -1;
                applyQuantity((Number.parseInt(quantityInput?.value || '1', 10) || 1) + delta);
            });
        });

        quantityInput?.addEventListener('input', () => {
            applyQuantity(quantityInput.value);
        });

        content.querySelector('.detail-add-to-cart')?.addEventListener('click', () => {
            const quantity = applyQuantity(quantityInput?.value || '1');
            const installmentSelection = typeof window.Installment?.getSelection === 'function'
                ? window.Installment.getSelection('detailInstallment')
                : null;
            const added = Cart.add({ ...product, installmentPlan: installmentSelection }, quantity);
            if (added && typeof showToast === 'function') {
                const message = (window.__marocPcI18n?.cartAddedTemplate || '{name} added to cart!').replace('{name}', `"${product.name}"`);
                showToast(message, 'success');
            }
        });

        // Restock notifications are handled globally by cart.js as a toast form.

        content.querySelector('.detail-compare-toggle')?.addEventListener('click', () => {
            const already = this.state.compareList.includes(product.id);
            this.handleCompareToggle(product.id, !already);
            this.render();
        });

        content.querySelectorAll('.related-product').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const next = this.state.products.find(item => item.id === parseInt(link.dataset.id, 10));
                if (next) this.openProductDetail(next);
            });
        });

        content.querySelector('#detailSendPriceMatchBtn')?.addEventListener('click', async () => {
            const status = content.querySelector('#detailPriceMatchStatus');
            const btn = content.querySelector('#detailSendPriceMatchBtn');
            const competitorUrl = content.querySelector('#detailPriceMatchUrl')?.value.trim() || '';
            const competitorPrice = parseFloat(content.querySelector('#detailPriceMatchPrice')?.value || '0');

            if (!competitorUrl && !competitorPrice) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = productPageText('addCompetitorOrPrice', 'Add a competitor link or the lower price you saw.');
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${productPageText('sending', 'Sending')}`;
            }

            try {
                const res = await fetch('api/feature-requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        action: 'price_match',
                        product_id: product.id,
                        product_name: product.name,
                        competitor_url: competitorUrl,
                        competitor_price: competitorPrice || null
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || productPageText('couldNotSendRequest', 'Could not send request.'));
                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = productPageText('priceMatchSent', 'Request sent to the admin price-match queue.');
                }
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${window.__marocPcI18n?.requestPriceMatch || 'Request price match'}`;
                }
            }
        });

        content.querySelector('#detailCreatePriceAlertBtn')?.addEventListener('click', async () => {
            const status = content.querySelector('#detailPriceAlertStatus');
            const btn = content.querySelector('#detailCreatePriceAlertBtn');
            const threshold = parseFloat(content.querySelector('#detailPriceAlertThreshold')?.value || '0');
            const channel = content.querySelector('#detailPriceAlertChannel')?.value || 'email';
            const email = content.querySelector('#detailPriceAlertEmail')?.value.trim() || '';
            const phone = content.querySelector('#detailPriceAlertPhone')?.value.trim() || '';

            if (!threshold || threshold <= 0) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = productPageText('enterValidTargetPrice', 'Enter a valid target price.');
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${productPageText('creatingAlert', 'Creating alert')}`;
            }

            try {
                const res = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        action: 'set_alert',
                        product_id: product.id,
                        target_price: threshold,
                        channel,
                        email,
                        phone
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || productPageText('couldNotCreateAlert', 'Could not create alert.'));
                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = productPageTemplate('alertArmedBelow', 'Alert armed below {price}.', { price: formatMAD(threshold) });
                }
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-bell"></i> ${window.__marocPcI18n?.alertPriceDrops || 'Alert me when price drops'}`;
                }
            }
        });

        if (typeof PriceChart !== 'undefined') {
            setTimeout(() => PriceChart.create('detailPriceChart', product.id), 100);
        }
        if (typeof Installment !== 'undefined') {
            Installment.bind('detailInstallment', product.price);
        }
        if (typeof Reviews !== 'undefined') {
            Reviews.loadForProduct(product.id, content.querySelector('[data-reviews-mount]'));
        }

        this.hydrateVisibleReviewSummaries(content).catch(() => {});
    },

    renderPagination() {
        const container = document.getElementById('pagination');
        if (!container) return;

        const totalPages = Math.ceil(this.state.filtered.length / this.state.perPage);
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        html += `<button class="page-btn" ${this.state.page === 1 ? 'disabled' : ''} data-page="${this.state.page - 1}">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.state.page - 1 && i <= this.state.page + 1)) {
                html += `<button class="page-btn ${i === this.state.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === this.state.page - 2 || i === this.state.page + 2) {
                html += `<span style="color: var(--muted); padding: 0 8px;">...</span>`;
            }
        }

        html += `<button class="page-btn" ${this.state.page === totalPages ? 'disabled' : ''} data-page="${this.state.page + 1}">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        container.innerHTML = html;

        container.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                this.state.page = parseInt(btn.dataset.page);
                this.render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    },

    openModal(product) {
        this.openProductDetail(product);
        return;

        // Wire: log product view to api/recommendations.php
        try {
            fetch('api/recommendations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'log', product_id: product.id, recommendation_score: product.rating || 0.5, context_trigger: 'quick_view' })
            }).catch(() => {});
        } catch (e) { /* non-blocking */ }

        const discount = product.oldPrice
            ? Math.round(((product.oldPrice - product.price) / product.oldPrice) * 100)
            : 0;
        const trust = productTrustMeta(product);
        const useTags = productUseTags(product);
        const stock = this.getStock(product);
        const box = inBoxChecklist(product);

        content.innerHTML = `
            <div class="modal-image">
                <img src="${productImage(product)}" alt="${product.name}" onerror="this.src='images/products/placeholder-storage.svg'">
            </div>
            <div class="modal-details">
                <div class="product-category">${productCategoryLabel(product.category)}</div>
                <h2>${product.name}</h2>
                <div class="product-rating">
                    <div class="stars">${this.renderStars(product.rating)}</div>
                        <span class="product-reviews">(${product.reviews} ${productPageText('reviews', 'reviews')})</span>
                </div>
                <div class="product-price-row">
                    <span class="product-price" style="font-size: 2rem;">${formatMAD(product.price)}</span>
                    ${product.oldPrice ? `<span class="product-old-price">${formatMAD(product.oldPrice)}</span>` : ''}
                    ${discount > 0 ? `<span class="product-discount">-${discount}%</span>` : ''}
                </div>
                <div class="product-use-tags modal-tags">
                    ${useTags.map(tag => `<span>${tag}</span>`).join('')}
                </div>
                <div class="stock-readout modal-stock ${stock.tone}">${stockLabel(stock)}</div>
                <p class="description">${productPageTemplate('productDescriptionTemplate', 'Premium {category} from {brand}. Built for enthusiasts who demand the best performance and reliability.', { category: productCategoryLabel(product.category), brand: product.brand })}</p>
                <div class="trust-grid">
                    ${trust.map(item => `
                        <div class="trust-item">
                            <i class="fas ${item.icon}"></i>
                            <span>${item.label}</span>
                        </div>
                    `).join('')}
                    <div class="trust-item">
                        <i class="fab fa-whatsapp"></i>
                        <span>${window.__marocPcI18n?.whatsappAdvice || 'WhatsApp advice before buying'}</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-box-open"></i>
                        <span>${productPageText('openBoxDeals', 'Open-box deals when available')}</span>
                    </div>
                </div>
                <div class="specs">
                    ${Object.entries(product.specs).map(([key, val]) => `
                        <div class="spec-item ${explainSpecValue(val) ? 'has-explainer' : ''}" ${explainSpecValue(val) ? `data-explainer="${explainSpecValue(val)}"` : ''}>
                            <div class="spec-key">${translateSpecKey(key)}</div>
                            <div class="spec-val">${val}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="in-box-panel">
                    <button type="button" class="in-box-toggle" id="inBoxToggle" aria-expanded="false">
                        <span><i class="fas fa-box-open"></i> ${productPageText('inTheBox', 'In the box')}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="in-box-body" id="inBoxBody">
                        <div>
                            <strong>${productPageText('included', 'Included')}</strong>
                            <ul>${box.items.map(item => `<li>${item}</li>`).join('')}</ul>
                        </div>
                        ${box.warnings.length ? `
                            <div>
                                <strong>${productPageText('checkBeforeAssembly', 'Check before assembly')}</strong>
                                <ul>${box.warnings.map(item => `<li>${item}</li>`).join('')}</ul>
                            </div>
                        ` : ''}
                        ${box.accessories.length ? `
                            <div class="in-box-links">
                                ${box.accessories.map(item => `<a href="${item.href}">${item.label}</a>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div id="modalPriceChart" class="modal-price-chart"></div>
                <div class="price-match-box">
                    <div class="price-alert-head">
                        <span><i class="fas fa-scale-balanced"></i> ${productPageText('seenItCheaper', 'Seen it cheaper?')}</span>
                        <small>${productPageText('sendToAdminReview', 'Send it to admin for review')}</small>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>${window.__marocPcI18n?.competitorUrl || 'Competitor URL'}</span>
                            <input type="url" id="priceMatchUrl" placeholder="${productPageText('competitorUrlPlaceholder', 'Jumia, Avito, store link')}">
                        </label>
                        <label>
                            <span>${window.__marocPcI18n?.seenPrice || 'Seen price'}</span>
                            <input type="number" id="priceMatchPrice" min="1" step="0.01" placeholder="${Math.round(product.price * 0.92)}">
                        </label>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>${window.__marocPcI18n?.email || 'Email'}</span>
                            <input type="email" id="priceMatchEmail" placeholder="${productPageText('emailExamplePlaceholder', 'you@example.com')}">
                        </label>
                        <label>
                            <span>${productPageText('whatsapp', 'WhatsApp')}</span>
                            <input type="tel" id="priceMatchPhone" placeholder="+212600000000">
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline price-alert-btn" id="sendPriceMatchBtn">
                        <i class="fas fa-paper-plane"></i> ${window.__marocPcI18n?.requestPriceMatch || 'Request price match'}
                    </button>
                    <div class="price-alert-status" id="priceMatchStatus" aria-live="polite"></div>
                </div>
                <div class="price-alert-box">
                    <div class="price-alert-head">
                        <span><i class="fas fa-bell"></i> ${window.__marocPcI18n?.priceDropAlert || 'Price Drop Alert'}</span>
                        <small>${window.__marocPcI18n?.current || 'Current'}: ${formatMAD(product.price)}</small>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>${productPageText('alertBelow', 'Alert below')}</span>
                            <input type="number" id="priceAlertThreshold" min="1" step="0.01" value="${Math.max(1, Math.floor(product.price * 0.95))}">
                        </label>
                        <label>
                            <span>${window.__marocPcI18n?.channel || 'Channel'}</span>
                            <select id="priceAlertChannel">
                                <option value="email">${window.__marocPcI18n?.email || 'Email'}</option>
                                <option value="whatsapp">${productPageText('whatsapp', 'WhatsApp')}</option>
                                <option value="both">${productPageText('both', 'Both')}</option>
                            </select>
                        </label>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>${window.__marocPcI18n?.email || 'Email'}</span>
                            <input type="email" id="priceAlertEmail" placeholder="${window.__marocPcI18n?.useAccountEmail || 'Use account email'}">
                        </label>
                        <label>
                            <span>${productPageText('whatsapp', 'WhatsApp')}</span>
                            <input type="tel" id="priceAlertPhone" placeholder="+212600000000">
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline price-alert-btn" id="createPriceAlertBtn">
                        <i class="fas fa-bell"></i> ${window.__marocPcI18n?.alertPriceDrops || 'Alert me when price drops'}
                    </button>
                    <div class="price-alert-status" id="priceAlertStatus" aria-live="polite"></div>
                </div>
                <button class="btn btn-primary add-to-cart-btn" data-id="${product.id}" style="margin-top: 16px; width: 100%;">
                    <i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addToCart || 'Add to Cart'}
                </button>
                ${typeof Installment !== 'undefined' ? Installment.widget(product.price, 'modalInstallment') : ''}
            </div>
        `;

        // Initialize Price Chart
        if (typeof PriceChart !== 'undefined') {
            setTimeout(() => {
                PriceChart.create('modalPriceChart', product.id);
            }, 100);
        }

        content.querySelector('.add-to-cart-btn').addEventListener('click', (e) => {
            const id = parseInt(e.currentTarget.dataset.id);
            const prod = this.state.products.find(p => p.id === id);
            if (prod) {
                Cart.add(prod);
                this.closeModal();
            }
        });

        content.querySelector('#inBoxToggle')?.addEventListener('click', () => {
            const body = content.querySelector('#inBoxBody');
            const toggle = content.querySelector('#inBoxToggle');
            const open = !body?.classList.contains('open');
            body?.classList.toggle('open', open);
            toggle?.setAttribute('aria-expanded', String(open));
        });

        content.querySelector('#sendPriceMatchBtn')?.addEventListener('click', async () => {
            const status = content.querySelector('#priceMatchStatus');
            const btn = content.querySelector('#sendPriceMatchBtn');
            const competitorUrl = content.querySelector('#priceMatchUrl')?.value.trim() || '';
            const competitorPrice = parseFloat(content.querySelector('#priceMatchPrice')?.value || '0');
            const email = content.querySelector('#priceMatchEmail')?.value.trim() || '';
            const phone = content.querySelector('#priceMatchPhone')?.value.trim() || '';

            if (!competitorUrl && !competitorPrice) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = productPageText('addCompetitorOrPrice', 'Add a competitor link or the lower price you saw.');
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${productPageText('sending', 'Sending')}`;
            }

            try {
                const res = await fetch('api/feature-requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        action: 'price_match',
                        product_id: product.id,
                        product_name: product.name,
                        competitor_url: competitorUrl,
                        competitor_price: competitorPrice || null,
                        contact_email: email,
                        contact_phone: phone
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || productPageText('couldNotSendRequest', 'Could not send request.'));
                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = productPageText('priceMatchSent', 'Request sent to the admin price-match queue.');
                }
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${window.__marocPcI18n?.requestPriceMatch || 'Request price match'}`;
                }
            }
        });

        content.querySelector('#createPriceAlertBtn')?.addEventListener('click', async () => {
            const status = content.querySelector('#priceAlertStatus');
            const btn = content.querySelector('#createPriceAlertBtn');
            const threshold = parseFloat(content.querySelector('#priceAlertThreshold')?.value || '0');
            const channel = content.querySelector('#priceAlertChannel')?.value || 'email';
            const email = content.querySelector('#priceAlertEmail')?.value.trim() || '';
            const phone = content.querySelector('#priceAlertPhone')?.value.trim() || '';

            if (!threshold || threshold <= 0) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = productPageText('enterValidTargetPrice', 'Enter a valid target price.');
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${productPageText('creatingAlert', 'Creating alert')}`;
            }
            if (status) {
                status.className = 'price-alert-status';
                status.textContent = '';
            }

            try {
                const res = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        action: 'set_alert',
                        product_id: product.id,
                        target_price: threshold,
                        channel,
                        email,
                        phone
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || productPageText('couldNotCreateAlert', 'Could not create alert.'));

                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = productPageTemplate('alertArmedBelow', 'Alert armed below {price}.', { price: formatMAD(threshold) });
                }
                if (typeof showToast === 'function') showToast(productPageText('priceAlertCreated', 'Price alert created.'), 'success');
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-bell"></i> ${window.__marocPcI18n?.alertPriceDrops || 'Alert me when price drops'}`;
                }
            }
        });

        // Bind installment widget interactions
        if (typeof Installment !== 'undefined') {
            Installment.bind('modalInstallment', product.price);
        }

        // Load reviews
        if (typeof Reviews !== 'undefined') {
            Reviews.loadForProduct(product.id);
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        const modal = document.getElementById('quickViewModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    handleCompareToggle(id, isChecked) {
        if (isChecked) {
            const product = this.state.products.find(p => p.id === id);
            if (this.state.compareList.length >= 4) {
                if (typeof showToast === 'function') showToast(productPageText('compareLimit', 'You can only compare up to 4 items.'), 'error');
                else alert(productPageText('compareLimit', 'You can only compare up to 4 items.'));
                this.render();
                return;
            }
            if (this.state.compareList.length > 0) {
                const firstId = this.state.compareList[0];
                const firstProduct = this.state.products.find(p => p.id === firstId);
                if (firstProduct && firstProduct.category !== product.category) {
                    if (typeof showToast === 'function') showToast(productPageText('sameCategoryCompare', 'You can only compare items of the same category.'), 'error');
                    else alert(productPageText('sameCategoryCompare', 'You can only compare items of the same category.'));
                    this.render();
                    return;
                }
            }
            this.state.compareList.push(id);
        } else {
            this.state.compareList = this.state.compareList.filter(pid => pid !== id);
        }
        this.renderCompareBar();
    },

    renderCompareBar() {
        const bar = document.getElementById('compareBar');
        if (!bar) return;

        if (this.state.compareList.length === 0) {
            bar.classList.remove('active');
            return;
        }

        const itemsContainer = document.getElementById('compareItems');
        const compareBtn = document.getElementById('compareBtn');

        let html = '';
        this.state.compareList.forEach(id => {
            const p = this.state.products.find(p => p.id === id);
            if (p) {
                html += `
                    <div class="compare-item-mini">
                        <img src="${productImage(p)}" alt="${p.name}" onerror="this.src='images/products/placeholder-storage.svg'">
                        <div class="compare-item-mini-info">
                            <span class="name">${p.name}</span>
                            <span class="price">${formatMAD(p.price)}</span>
                        </div>
                        <button class="remove-compare-item" data-id="${p.id}"><i class="fas fa-times"></i></button>
                    </div>
                `;
            }
        });

        for (let i = this.state.compareList.length; i < 4; i++) {
            html += `
                <div class="compare-item-mini empty">
                    <i class="fas fa-plus"></i>
                </div>
            `;
        }

        itemsContainer.innerHTML = html;
        compareBtn.textContent = `${window.__marocPcI18n?.compare || 'Compare'} (${this.state.compareList.length})`;
        compareBtn.disabled = this.state.compareList.length < 2;

        bar.classList.add('active');

        itemsContainer.querySelectorAll('.remove-compare-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                this.state.compareList = this.state.compareList.filter(pid => pid !== id);
                this.render();
                this.renderCompareBar();
            });
        });
    },

    // ── Helper: extract numeric value from spec string ────
    parseSpecNumber(val) {
        if (!val || val === '-') return null;
        // Extract the primary number (e.g. "24 GB GDDR6X" → 24, "2.52 GHz" → 2.52, "450 W" → 450)
        const match = String(val).match(/^([\d,.]+)/);
        return match ? parseFloat(match[1].replace(',', '')) : null;
    },

    // ── Whitelist of spec names that make sense to compare numerically ──
    isComparableSpec(specName) {
        const comparable = [
            'vram', 'core clock', 'clock speed', 'boost clock', 'base clock',
            'tdp', 'power', 'wattage', 'cores', 'core count', 'threads',
            'cache', 'memory', 'frequency', 'speed', 'storage', 'capacity',
            'bandwidth', 'rpm', 'size', 'weight', 'noise'
        ];
        const lower = specName.toLowerCase();
        return comparable.some(term => lower.includes(term));
    },

    // ── Helper: determine "Best For" tags ─────────────────
    getBestForTags(productsToCompare) {
        const tags = [];
        if (productsToCompare.length < 2) return tags;

        // Best value = lowest price
        const sorted = [...productsToCompare].sort((a, b) => a.price - b.price);
        if (sorted[0].price < sorted[sorted.length - 1].price) {
            tags.push({ product: sorted[0], type: 'value', label: window.__marocPcI18n?.bestValue || 'Best Value', icon: 'fa-tag' });
        }

        // Best performance = highest clock speed + VRAM (proxy for perf)
        const perfScores = productsToCompare.map(p => {
            let score = 0;
            if (p.specs) {
                const clock = this.parseSpecNumber(p.specs['Core Clock'] || p.specs['Clock Speed'] || p.specs['Boost Clock']);
                const vram = this.parseSpecNumber(p.specs['VRAM'] || p.specs['Memory']);
                const cores = this.parseSpecNumber(p.specs['Cores'] || p.specs['Core Count']);
                if (clock) score += clock * 10;
                if (vram) score += vram * 5;
                if (cores) score += cores * 0.5;
            }
            score += p.rating * 2;
            return { product: p, score };
        }).sort((a, b) => b.score - a.score);

        if (perfScores[0].score > 0 && perfScores[0].score > perfScores[perfScores.length - 1].score) {
            tags.push({ product: perfScores[0].product, type: 'gaming', label: window.__marocPcI18n?.bestPerformance || 'Best Performance', icon: 'fa-gamepad' });
        }

        // Most efficient = lowest TDP
        const tdpProducts = productsToCompare.filter(p => p.specs && (p.specs['TDP'] || p.specs['Power']));
        if (tdpProducts.length >= 2) {
            const byTdp = [...tdpProducts].sort((a, b) => {
                const tdpA = this.parseSpecNumber(a.specs['TDP'] || a.specs['Power']) || 999;
                const tdpB = this.parseSpecNumber(b.specs['TDP'] || b.specs['Power']) || 999;
                return tdpA - tdpB;
            });
            const lowestTdp = this.parseSpecNumber(byTdp[0].specs['TDP'] || byTdp[0].specs['Power']);
            const highestTdp = this.parseSpecNumber(byTdp[byTdp.length - 1].specs['TDP'] || byTdp[byTdp.length - 1].specs['Power']);
            if (lowestTdp !== highestTdp) {
                tags.push({ product: byTdp[0], type: 'efficiency', label: window.__marocPcI18n?.mostEfficient || 'Most Efficient', icon: 'fa-leaf' });
            }
        }

        return tags;
    },

    openCompareModal() {
        const modal = document.getElementById('compareModal');
        const content = document.getElementById('compareContent');
        if (!modal || !content || this.state.compareList.length < 2) return;

        const productsToCompare = this.state.compareList.map(id => this.state.products.find(p => p.id === id)).filter(Boolean);

        const allSpecs = new Set();
        productsToCompare.forEach(p => {
            if (p.specs) {
                Object.keys(p.specs).forEach(key => allSpecs.add(key));
            }
        });
        const specsArray = Array.from(allSpecs);

        // ── Best-For tags ────────────────────────────────
        const bestForTags = this.getBestForTags(productsToCompare);
        const tagsHtml = bestForTags.length > 0 ? `
            <div class="best-for-tags">
                ${bestForTags.map(t => `
                    <span class="best-for-tag ${t.type}">
                        <i class="fas ${t.icon}"></i>
                        ${t.label}: <strong>${t.product.name.split(' ').slice(0, 3).join(' ')}</strong>
                    </span>
                `).join('')}
            </div>` : '';

        // ── Build spec rows with progress bars + winners ─
        const specRowsHtml = specsArray.map(spec => {
            const values = productsToCompare.map(p => ({
                raw: p.specs && p.specs[spec] ? p.specs[spec] : '-',
                num: this.parseSpecNumber(p.specs ? p.specs[spec] : null)
            }));

            // Only use progress bars for whitelisted comparable specs
            const canCompare = this.isComparableSpec(spec);
            const numericValues = values.map(v => v.num).filter(n => n !== null);
            const isNumeric = canCompare && numericValues.length === values.length && numericValues.length >= 2;
            const isLowerBetter = spec.toLowerCase().includes('tdp') || spec.toLowerCase().includes('power') || spec.toLowerCase().includes('latency') || spec.toLowerCase().includes('noise');

            // Check if all values are equal (no winner in ties)
            const allEqual = isNumeric && numericValues.every(v => v === numericValues[0]);

            let winnerIdx = -1;
            if (isNumeric && !allEqual) {
                const best = isLowerBetter ? Math.min(...numericValues) : Math.max(...numericValues);
                winnerIdx = values.findIndex(v => v.num === best);
            }

            const maxVal = isNumeric ? Math.max(...numericValues) : 0;

            return `
                <tr>
                    <td class="spec-label-col">${translateSpecKey(spec)}</td>
                    ${values.map((v, i) => {
                        const isWinner = i === winnerIdx && !allEqual;
                        if (isNumeric && maxVal > 0) {
                            const pct = (v.num / maxVal) * 100;
                            return `<td class="${isWinner ? 'is-winner' : ''}">
                                <div class="spec-bar-wrap">
                                    <div class="spec-bar">
                                        <div class="spec-bar-fill ${allEqual ? 'winner' : (isWinner ? 'winner' : 'loser')}" style="width:${pct}%"></div>
                                    </div>
                                    <span class="spec-bar-value">${v.raw}</span>
                                </div>
                                ${isWinner ? `<span class="winner-badge"><i class="fas fa-crown"></i> ${productPageText('best', 'Best')}</span>` : ''}
                            </td>`;
                        }
                        return `<td>${v.raw}</td>`;
                    }).join('')}
                </tr>
            `;
        }).join('');

        // ── Price comparison row ─────────────────────────
        const prices = productsToCompare.map(p => p.price);
        const bestPrice = Math.min(...prices);
        const maxPrice = Math.max(...prices);

        let html = `
            ${tagsHtml}
            <div class="compare-table-wrapper">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th class="spec-label-col">${productPageText('product', 'Product')}</th>
                            ${productsToCompare.map(p => `
                                <th>
                                    <img src="${productImage(p)}" alt="${p.name}" class="compare-table-img" onerror="this.src='images/products/placeholder-storage.svg'">
                                    <div class="compare-table-name">${p.name}</div>
                                    <div class="compare-table-price">${formatMAD(p.price)}</div>
                                    <button class="btn btn-primary add-to-cart-btn btn-sm" data-id="${p.id}">${window.__marocPcI18n?.addToCart || 'Add to Cart'}</button>
                                </th>
                            `).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-row"><td colspan="${productsToCompare.length + 1}">${window.__marocPcI18n?.general || 'General'}</td></tr>
                        <tr>
                            <td class="spec-label-col">${window.__marocPcI18n?.brand || 'Brand'}</td>
                            ${productsToCompare.map(p => `<td>${p.brand}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="spec-label-col">${window.__marocPcI18n?.rating || 'Rating'}</td>
                            ${productsToCompare.map(p => `<td><div class="stars">${this.renderStars(p.rating)}</div></td>`).join('')}
                        </tr>
                        <tr>
                            <td class="spec-label-col">${window.__marocPcI18n?.price || 'Price'}</td>
                            ${productsToCompare.map(p => {
                                const pct = maxPrice > 0 ? (p.price / maxPrice) * 100 : 100;
                                const isWinner = p.price === bestPrice;
                                return `<td class="${isWinner ? 'is-winner' : ''}">
                                    <div class="spec-bar-wrap">
                                        <div class="spec-bar">
                                            <div class="spec-bar-fill ${isWinner ? 'winner' : 'loser'}" style="width:${pct}%"></div>
                                        </div>
                                        <span class="spec-bar-value">${formatMAD(p.price)}</span>
                                    </div>
                                    ${isWinner ? `<span class="winner-badge"><i class="fas fa-crown"></i> ${productPageText('best', 'Best')}</span>` : ''}
                                </td>`;
                            }).join('')}
                        </tr>
                        <tr class="section-row"><td colspan="${productsToCompare.length + 1}">${window.__marocPcI18n?.specifications || 'Specifications'}</td></tr>
                        ${specRowsHtml}
                    </tbody>
                </table>
            </div>
            <button class="compare-share-btn" id="shareCompareBtn">
                <i class="fas fa-share-alt"></i> ${window.__marocPcI18n?.shareComparison || 'Share Comparison'}
            </button>
        `;

        content.innerHTML = html;

        // ── Bind add-to-cart buttons ─────────────────────
        content.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                const prod = this.state.products.find(p => p.id === id);
                if (prod) Cart.add(prod);
            });
        });

        // ── Share via URL ────────────────────────────────
        document.getElementById('shareCompareBtn')?.addEventListener('click', () => {
            const ids = this.state.compareList.join(',');
            const url = `${window.location.origin}${window.location.pathname}?compare=${ids}`;
            navigator.clipboard.writeText(url).then(() => {
                if (typeof showToast === 'function') showToast(productPageText('comparisonLinkCopied', 'Comparison link copied to clipboard!'), 'success');
                else alert(productPageText('comparisonLinkCopied', 'Comparison link copied to clipboard!') + ' ' + url);
            }).catch(() => {
                prompt(productPageText('copyThisLink', 'Copy this link:'), url);
            });
        });

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
};

window.ProductsPage = ProductsPage;


document.addEventListener('DOMContentLoaded', () => {
    ProductsPage.init();

    // ── Auto-open comparison from URL params ─────────
    const urlParams = new URLSearchParams(window.location.search);
    const compareParam = urlParams.get('compare');
    if (compareParam) {
        const ids = compareParam.split(',').map(Number).filter(n => !isNaN(n));
        if (ids.length >= 2) {
            ProductsPage.state.compareList = ids.slice(0, 4);
            ProductsPage.render();
            ProductsPage.renderCompareBar();
            setTimeout(() => ProductsPage.openCompareModal(), 500);
        }
    }
});
})();
