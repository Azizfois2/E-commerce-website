function formatMAD(value) {
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' MAD';
}

function productImage(product) {
    const fallback = `images/products/placeholder-${product.category || 'storage'}.svg`;
    return product.image || fallback;
}

function productTrustMeta(product) {
    const warrantyByCategory = {
        cpu: '3-year warranty',
        gpu: '3-year warranty',
        ram: 'Lifetime warranty',
        storage: '5-year warranty',
        motherboard: '3-year warranty',
        psu: '7-year warranty',
        cooling: '2-year warranty',
        monitor: '3-year warranty',
        accessories: 'Accessory warranty',
        service: 'Service guarantee'
    };
    const delivery = product.inStock ? 'Casablanca 24-48h' : 'Restock alert';
    const payment = product.price >= 3000 ? 'Installments available' : 'COD / card / transfer';
    return [
        { icon: 'fa-shield-halved', label: warrantyByCategory[product.category] || 'Warranty included' },
        { icon: 'fa-truck-fast', label: delivery },
        { icon: 'fa-credit-card', label: payment },
        { icon: 'fa-screwdriver-wrench', label: 'Assembly eligible' }
    ];
}

function productUseTags(product) {
    const tags = [];
    if (product.badge) tags.push(product.badge);
    if (product.oldPrice) tags.push('Deal');
    if (['gpu', 'cpu'].includes(product.category)) tags.push('Compare-ready');
    if (product.category === 'motherboard') tags.push('Socket match');
    if (product.category === 'psu') tags.push('Wattage checked');
    if (product.category === 'ram') tags.push('Memory finder');
    if (product.category === 'accessories') tags.push('Build helper');

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
    'PCIe 5.0': 'Doubles PCIe 4.0 bandwidth for next-gen GPUs and NVMe drives.',
    'PCIe 4.0': 'Current mainstream high-speed link for GPUs and NVMe SSDs.',
    DDR5: 'Newer memory standard with higher bandwidth than DDR4.',
    DDR4: 'Older memory standard, good value but limited upgrade headroom.',
    AM5: 'AMD socket with stronger forward upgrade path than AM4.',
    AM4: 'Mature AMD socket with excellent budget value.',
    'LGA 1700': 'Intel socket used by 12th, 13th, and 14th gen CPUs.',
    'LGA 1851': 'Newer Intel desktop socket for Core Ultra processors.',
    'Zen 5': 'AMD architecture focused on efficiency and gaming/workstation gains.',
    Blackwell: 'NVIDIA RTX 50 generation architecture.',
    GDDR7: 'Newest GPU memory generation with very high bandwidth.',
    NVMe: 'Fast SSD protocol that connects through PCIe lanes.',
};

function explainSpecValue(value) {
    const text = String(value || '');
    const key = Object.keys(SPEC_EXPLAINERS).find(token => text.toLowerCase().includes(token.toLowerCase()));
    return key ? SPEC_EXPLAINERS[key] : '';
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
    if (!stock?.in_stock || stock.quantity <= 0) return '[STOCK: OUT]';
    if (stock.tone === 'critical') return `[STOCK: CRITICAL - ${stock.quantity} LEFT]`;
    if (stock.tone === 'low') return `[STOCK: LOW - ${stock.quantity} UNITS]`;
    return `[STOCK: ${stock.quantity} UNITS]`;
}

function inBoxChecklist(product) {
    const category = product.category;
    const name = String(product.name || '').toLowerCase();
    const specs = product.specs || {};
    const items = [];
    const warnings = [];
    const accessories = [];

    if (category === 'cpu') {
        items.push('CPU only', 'Warranty card');
        const hasCooler = name.includes('9600') || name.includes('5600g') || name.includes('boxed cooler');
        if (hasCooler) items.push('Stock cooler');
        else {
            warnings.push('No stock cooler expected. Add compatible cooling before checkout.');
            accessories.push({ label: 'CPU coolers', href: 'products.html?category=cooling' });
        }
        accessories.push({ label: 'Thermal paste', href: 'products.html?category=accessories&search=thermal' });
    } else if (category === 'motherboard') {
        items.push('I/O shield', 'M.2 screws', 'Quick start guide');
        if (String(specs.WiFi || specs['Wi-Fi'] || '').toLowerCase().includes('yes') || name.includes('wifi')) {
            items.push('Wi-Fi antenna');
        }
        warnings.push('Most boards include only a limited number of SATA cables.');
        accessories.push({ label: 'SATA cable', href: 'products.html?category=accessories&search=sata' });
    } else if (category === 'gpu') {
        items.push('Graphics card', 'Support/warranty insert');
        if (name.includes('4090') || name.includes('5080') || name.includes('5090')) {
            warnings.push('Check PSU connector support before assembly.');
            accessories.push({ label: 'PCIe power adapter', href: 'products.html?category=accessories&search=pcie' });
        }
    } else if (category === 'storage') {
        items.push('Drive only');
        if (name.includes('sata')) {
            warnings.push('SATA drives may not include a data cable.');
            accessories.push({ label: 'SATA cable', href: 'products.html?category=accessories&search=sata' });
        } else {
            accessories.push({ label: 'M.2 heatsink', href: 'products.html?category=accessories&search=heatsink' });
        }
    } else if (category === 'cooling') {
        items.push('Cooler', 'Mounting hardware');
        accessories.push({ label: 'Thermal paste', href: 'products.html?category=accessories&search=thermal' });
    } else if (category === 'psu') {
        items.push('Power supply', 'AC cable', 'Modular cable set');
        warnings.push('Verify GPU cable count before choosing high-end RTX cards.');
    } else {
        items.push('Retail unit', 'Basic documentation');
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
        wishlist: new Set(JSON.parse(localStorage.getItem('wishlist') || '[]')),
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
            accessories: 'accessories'
        };
        let changed = false;

        const categoryParam = params.get('category');
        if (categoryParam) {
            const category = categoryAliases[categoryParam] || categoryParam;
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
                { id: 'service-assembly', name: 'Professional PC Assembly', brand: 'Maroc PC', category: 'service', price: 299, image: 'logo.png', inStock: true, specs: { Type: 'Build service' } },
                { id: 'service-bios', name: 'BIOS Update', brand: 'Maroc PC', category: 'service', price: 99, image: 'logo.png', inStock: true, specs: { Type: 'Build service' } },
                { id: 'service-stress', name: 'Stress Test Report', brand: 'Maroc PC', category: 'service', price: 149, image: 'logo.png', inStock: true, specs: { Type: 'Build service' } }
            ]
        };
        const bundle = bundles[bundleKey] || [];
        const items = bundle.map(item => typeof item === 'number' ? this.state.products.find(p => p.id === item) : item).filter(Boolean);
        items.forEach(item => Cart.add(item));
        if (typeof showToast === 'function') showToast(`Added ${items.length} bundle items.`, 'success');
    },

    _searchLogTimer: null,

    handleSearch(query) {
        const q = query.toLowerCase().trim();
        if (!q) { this.applyFilters(); return; }
        this.state.filtered = this.state.products.filter(p =>
            p.name.toLowerCase().includes(q) || p.brand.toLowerCase().includes(q) || p.category.toLowerCase().includes(q)
        );
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
        filters.categories.forEach(c => { tags.push({ type: 'category', value: c, label: c.charAt(0).toUpperCase() + c.slice(1) }); });
        filters.brands.forEach(b => { tags.push({ type: 'brand', value: b, label: b }); });
        if (filters.rating > 0) tags.push({ type: 'rating', value: filters.rating, label: `${filters.rating}+ Stars` });
        if (filters.minPrice > 0 || filters.maxPrice < 3000) tags.push({ type: 'price', value: 'price', label: `${filters.minPrice.toLocaleString()} - ${filters.maxPrice.toLocaleString()} MAD` });
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
                    <p style="color: var(--muted);">No products found matching your criteria.</p>
                </div>
            `;
            this.renderPagination();
            return;
        }

        grid.innerHTML = pageItems.map(product => this.createProductCard(product)).join('');

        // Add to cart
        grid.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                const product = this.state.products.find(p => p.id === id);
                if (product) {
                    Cart.add(product);
                    btn.classList.add('added');
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    setTimeout(() => {
                        btn.classList.remove('added');
                        btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                    }, 2000);
                }
            });
        });

        // Notify restock
        grid.querySelectorAll('.notify-restock-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                const name = e.currentTarget.dataset.name;
                const email = prompt(`Notify me when "${name}" is back in stock:\n\nPlease enter your email address:`);
                if (email && email.trim() !== '') {
                    fetch('api/restock-notify.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: id, email: email.trim() })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast === 'function') showToast(data.message, 'success');
                            else alert(data.message);
                        } else {
                            if (typeof showToast === 'function') showToast(data.error || 'Failed to subscribe', 'error');
                            else alert(data.error || 'Failed to subscribe');
                        }
                    })
                    .catch(() => {
                        if (typeof showToast === 'function') showToast('Network error', 'error');
                        else alert('Network error');
                    });
                }
            });
        });

        // Wishlist
        grid.querySelectorAll('.product-wishlist').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = parseInt(e.currentTarget.dataset.id);
                if (typeof Wishlist !== 'undefined') {
                    const isActive = await Wishlist.toggle(id);
                    if (typeof showToast === 'function') {
                        showToast(isActive ? "Added to wishlist!" : "Removed from wishlist.");
                    }
                }
            });
        });

        // Quick view
        grid.querySelectorAll('.product-quickview').forEach(btn => {
            btn.addEventListener('click', (e) => {
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

        return `
            <article class="product-card">
                <div class="product-img-wrap">
                    <img src="${productImage(product)}" alt="${product.name}" class="product-img" loading="lazy" onerror="this.src='images/products/generic-laptop.png'">
                    ${product.badge ? `<span class="product-badge badge-${product.badge.toLowerCase()}">${product.badge}</span>` : ''}
                    <button class="product-wishlist ${isWishlisted ? 'active' : ''}" data-id="${product.id}" aria-label="Add to wishlist">
                        <i class="${isWishlisted ? 'fas' : 'far'} fa-heart"></i>
                    </button>
                    <button class="product-quickview" data-id="${product.id}">Quick View</button>
                </div>
                <div class="product-card-body">
                    <div class="product-category">${product.category}</div>
                    <h3 class="product-name">${product.name}</h3>
                    <div class="stock-readout ${stock.tone}">${stockLabel(stock)}</div>
                    <div class="product-rating">
                        <div class="stars">${this.renderStars(product.rating)}</div>
                        <span class="product-reviews">(${product.reviews})</span>
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
                            <input type="checkbox" class="compare-checkbox-input" data-id="${product.id}" ${this.state.compareList.includes(product.id) ? 'checked' : ''}> Compare
                        </label>
                        ${available 
                            ? `<button class="add-to-cart-btn" data-id="${product.id}">
                                   <i class="fas fa-cart-plus"></i> Add to Cart
                               </button>`
                            : `<button class="notify-restock-btn" data-id="${product.id}" data-name="${product.name}" style="background: var(--page-bg-3); border-color: var(--border); color: var(--muted); width: 100%; height: 44px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; font-family: 'Syne', sans-serif;">
                                   <i class="fas fa-bell"></i> Notify Me
                               </button>`
                        }
                    </div>
                </div>
            </article>
        `;
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
        const modal = document.getElementById('quickViewModal');
        const content = document.getElementById('quickViewContent');
        if (!modal || !content) return;

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
                <div class="product-category">${product.category}</div>
                <h2>${product.name}</h2>
                <div class="product-rating">
                    <div class="stars">${this.renderStars(product.rating)}</div>
                    <span class="product-reviews">(${product.reviews} reviews)</span>
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
                <p class="description">Premium ${product.category} from ${product.brand}. Built for enthusiasts who demand the best performance and reliability.</p>
                <div class="trust-grid">
                    ${trust.map(item => `
                        <div class="trust-item">
                            <i class="fas ${item.icon}"></i>
                            <span>${item.label}</span>
                        </div>
                    `).join('')}
                    <div class="trust-item">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp advice before buying</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-box-open"></i>
                        <span>Open-box deals when available</span>
                    </div>
                </div>
                <div class="specs">
                    ${Object.entries(product.specs).map(([key, val]) => `
                        <div class="spec-item ${explainSpecValue(val) ? 'has-explainer' : ''}" ${explainSpecValue(val) ? `data-explainer="${explainSpecValue(val)}"` : ''}>
                            <div class="spec-key">${key}</div>
                            <div class="spec-val">${val}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="in-box-panel">
                    <button type="button" class="in-box-toggle" id="inBoxToggle" aria-expanded="false">
                        <span><i class="fas fa-box-open"></i> In the box</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="in-box-body" id="inBoxBody">
                        <div>
                            <strong>Included</strong>
                            <ul>${box.items.map(item => `<li>${item}</li>`).join('')}</ul>
                        </div>
                        ${box.warnings.length ? `
                            <div>
                                <strong>Check before assembly</strong>
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
                        <span><i class="fas fa-scale-balanced"></i> Seen it cheaper?</span>
                        <small>Send it to admin for review</small>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>Competitor URL</span>
                            <input type="url" id="priceMatchUrl" placeholder="Jumia, Avito, store link">
                        </label>
                        <label>
                            <span>Seen price</span>
                            <input type="number" id="priceMatchPrice" min="1" step="0.01" placeholder="${Math.round(product.price * 0.92)}">
                        </label>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>Email</span>
                            <input type="email" id="priceMatchEmail" placeholder="you@example.com">
                        </label>
                        <label>
                            <span>WhatsApp</span>
                            <input type="tel" id="priceMatchPhone" placeholder="+212600000000">
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline price-alert-btn" id="sendPriceMatchBtn">
                        <i class="fas fa-paper-plane"></i> Request price match
                    </button>
                    <div class="price-alert-status" id="priceMatchStatus" aria-live="polite"></div>
                </div>
                <div class="price-alert-box">
                    <div class="price-alert-head">
                        <span><i class="fas fa-bell"></i> Price Drop Alert</span>
                        <small>Current: ${formatMAD(product.price)}</small>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>Alert below</span>
                            <input type="number" id="priceAlertThreshold" min="1" step="0.01" value="${Math.max(1, Math.floor(product.price * 0.95))}">
                        </label>
                        <label>
                            <span>Channel</span>
                            <select id="priceAlertChannel">
                                <option value="email">Email</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="both">Both</option>
                            </select>
                        </label>
                    </div>
                    <div class="price-alert-controls">
                        <label>
                            <span>Email</span>
                            <input type="email" id="priceAlertEmail" placeholder="Use account email">
                        </label>
                        <label>
                            <span>WhatsApp</span>
                            <input type="tel" id="priceAlertPhone" placeholder="+212600000000">
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline price-alert-btn" id="createPriceAlertBtn">
                        <i class="fas fa-bell"></i> Alert me when price drops
                    </button>
                    <div class="price-alert-status" id="priceAlertStatus" aria-live="polite"></div>
                </div>
                <button class="btn btn-primary add-to-cart-btn" data-id="${product.id}" style="margin-top: 16px; width: 100%;">
                    <i class="fas fa-cart-plus"></i> Add to Cart
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
                    status.textContent = 'Add a competitor link or the lower price you saw.';
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending';
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
                if (!res.ok || !data.success) throw new Error(data.message || 'Could not send request.');
                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = 'Request sent to the admin price-match queue.';
                }
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Request price match';
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
                    status.textContent = 'Enter a valid target price.';
                }
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Creating alert';
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
                if (!res.ok || !data.success) throw new Error(data.message || 'Could not create alert.');

                if (status) {
                    status.className = 'price-alert-status success';
                    status.textContent = `Alert armed below ${formatMAD(threshold)}.`;
                }
                if (typeof showToast === 'function') showToast('Price alert created.', 'success');
            } catch (error) {
                if (status) {
                    status.className = 'price-alert-status error';
                    status.textContent = error.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-bell"></i> Alert me when price drops';
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
                if (typeof showToast === 'function') showToast('You can only compare up to 4 items.', 'error');
                else alert('You can only compare up to 4 items.');
                this.render();
                return;
            }
            if (this.state.compareList.length > 0) {
                const firstId = this.state.compareList[0];
                const firstProduct = this.state.products.find(p => p.id === firstId);
                if (firstProduct && firstProduct.category !== product.category) {
                    if (typeof showToast === 'function') showToast('You can only compare items of the same category.', 'error');
                    else alert('You can only compare items of the same category.');
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
        compareBtn.textContent = `Compare (${this.state.compareList.length})`;
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
            tags.push({ product: sorted[0], type: 'value', label: 'Best Value', icon: 'fa-tag' });
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
            tags.push({ product: perfScores[0].product, type: 'gaming', label: 'Best Performance', icon: 'fa-gamepad' });
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
                tags.push({ product: byTdp[0], type: 'efficiency', label: 'Most Efficient', icon: 'fa-leaf' });
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
                    <td class="spec-label-col">${spec}</td>
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
                                ${isWinner ? '<span class="winner-badge"><i class="fas fa-crown"></i> Best</span>' : ''}
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
                            <th class="spec-label-col">Product</th>
                            ${productsToCompare.map(p => `
                                <th>
                                    <img src="${productImage(p)}" alt="${p.name}" class="compare-table-img" onerror="this.src='images/products/placeholder-storage.svg'">
                                    <div class="compare-table-name">${p.name}</div>
                                    <div class="compare-table-price">${formatMAD(p.price)}</div>
                                    <button class="btn btn-primary add-to-cart-btn btn-sm" data-id="${p.id}">Add to Cart</button>
                                </th>
                            `).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-row"><td colspan="${productsToCompare.length + 1}">General</td></tr>
                        <tr>
                            <td class="spec-label-col">Brand</td>
                            ${productsToCompare.map(p => `<td>${p.brand}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="spec-label-col">Rating</td>
                            ${productsToCompare.map(p => `<td><div class="stars">${this.renderStars(p.rating)}</div></td>`).join('')}
                        </tr>
                        <tr>
                            <td class="spec-label-col">Price</td>
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
                                    ${isWinner ? '<span class="winner-badge"><i class="fas fa-crown"></i> Best</span>' : ''}
                                </td>`;
                            }).join('')}
                        </tr>
                        <tr class="section-row"><td colspan="${productsToCompare.length + 1}">Specifications</td></tr>
                        ${specRowsHtml}
                    </tbody>
                </table>
            </div>
            <button class="compare-share-btn" id="shareCompareBtn">
                <i class="fas fa-share-alt"></i> Share Comparison
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
                if (typeof showToast === 'function') showToast('Comparison link copied to clipboard!', 'success');
                else alert('Link copied: ' + url);
            }).catch(() => {
                prompt('Copy this link:', url);
            });
        });

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
};


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
