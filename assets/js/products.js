(() => {
function getSharedCart() {
  return typeof window !== "undefined" ? window.Cart : undefined;
}

function productText(key, fallback) {
  return window.__marocPcI18n?.[key] || fallback;
}

function productTemplate(key, fallback, params = {}) {
  let value = productText(key, fallback);
  Object.entries(params).forEach(([name, replacement]) => {
    value = value.replaceAll(`{${name}}`, replacement);
  });
  return value;
}

function escapeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  }[char]));
}

function cssEscapeValue(value) {
  return window.CSS?.escape ? window.CSS.escape(value) : String(value).replace(/["\\]/g, "\\$&");
}

// Spec key translation map
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

function showToast(message) {
  const toast = document.getElementById("toast");
  const toastMsg = document.getElementById("toastMessage");
  if (!toast || !toastMsg) return;

  toastMsg.textContent = message;
  toast.classList.add("show");

  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove("show"), 3000);
}


function renderStars(rating) {
  let html = "";
  for (let i = 1; i <= 5; i++) {
    if (rating >= i) {
      html += `<i class="fas fa-star"></i>`;
    } else if (rating >= i - 0.5) {
      html += `<i class="fas fa-star-half-alt"></i>`;
    } else {
      html += `<i class="far fa-star"></i>`;
    }
  }
  return html;
}

const BADGE_COLOURS = {
  New: "var(--cyan)",
  Hot: "var(--orange)",
  Sale: "#a855f7",
  "Low Stock": "var(--red)",
};

function renderBadge(badge) {
  if (!badge) return "";
  const colour = BADGE_COLOURS[badge] || "var(--cyan)";
  const labelMap = {
    New: productText("badgeNew", "New"),
    Hot: productText("badgeHot", "Hot"),
    Sale: productText("badgeSale", "Sale"),
    "Low Stock": productText("badgeLowStock", "Low Stock"),
    "Best Gaming": productText("badgeBestGaming", "Best Gaming"),
    "Flagship": productText("badgeFlagship", "Flagship"),
    "AMD Top": productText("badgeAmdTop", "AMD Top"),
  };
  return `<span class="product-badge" style="background:${colour}">${labelMap[badge] || badge}</span>`;
}


function formatMAD(value, options = {}) {
  if (typeof window.formatMAD === 'function') {
    return window.formatMAD(value, options);
  }
  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' DH';
}

function renderPrice(product) {
  const current = formatMAD(product.price);
  if (!product.oldPrice) return `<span class="product-price">${current}</span>`;

  const discount = Math.round(
    ((product.oldPrice - product.price) / product.oldPrice) * 100
  );
  return `
    <span class="product-price">${current}</span>
    <span class="product-old-price">${formatMAD(product.oldPrice)}</span>
    <span class="product-discount">−${discount}%</span>
  `;
}

function normalizeProductImageUrl(src) {
  if (!src) return 'Images/products/placeholder-gpu.svg';
  if (src.startsWith('images/')) {
    return 'Images/' + src.slice('images/'.length);
  }
  return src;
}

function createProductCard(product) {
  const li = document.createElement("li");
  li.className = "product-card";
  li.dataset.id = product.id;
  li.dataset.category = product.category;

  const addBtn = product.inStock
    ? `<button type="button" class="btn btn-primary add-to-cart-btn" data-id="${product.id}">
         <i class="fas fa-cart-plus"></i> ${productText("addToCart", "Add to Cart")}
       </button>`
    : `<button type="button" class="btn btn-secondary notify-restock-btn" data-id="${product.id}" data-name="${product.name}">
         <i class="fas fa-bell"></i> ${productText("notifyMe", "Notify Me")}
       </button>`;

  const isWished = typeof Wishlist !== 'undefined' ? Wishlist.has(product.id) : false;
  const imageUrl = normalizeProductImageUrl(product.image);

  li.innerHTML = `
    <div class="product-img-wrap">
      ${renderBadge(product.badge)}
      <img
        src="${imageUrl}"
        alt="${product.name}"
        class="product-img"
        loading="lazy"
        onerror="this.onerror=null;this.src='Images/products/placeholder-gpu.svg'"
      />
      <button class="product-wishlist ${isWished ? 'active' : ''}" aria-label="${productText("addToWishlist", "Add to wishlist")}" data-id="${product.id}">
        <i class="${isWished ? 'fas' : 'far'} fa-heart"></i>
      </button>
      <button class="product-quickview" data-id="${product.id}">${productText("quickView", "Quick View")}</button>
    </div>

    <div class="product-card-body">
      <p class="product-category">${product.category.toUpperCase()}</p>
      <h3 class="product-card-name">${product.name}</h3>

      <div class="product-rating">
        <span class="stars">${renderStars(product.rating)}</span>
        <span class="product-reviews">(${product.reviews.toLocaleString()})</span>
      </div>

      <div class="product-price-row">
        ${renderPrice(product)}
      </div>

      <div class="product-card-actions">
        ${addBtn}
      </div>
    </div>
  `;

  return li;
}

function handleAddToCartButton(btn) {
  const id = parseInt(btn.dataset.id, 10);
  const cart = getSharedCart();
  const product = products.find((p) => p.id === id);

  if (!cart || !product || typeof cart.add !== "function") {
    showToast(productText("cartLoading", "Cart is still loading. Please try again."));
    return;
  }

  const added = cart.add(product);
  if (!added) return;

  btn.classList.add("added");
  btn.innerHTML = `<i class="fas fa-check"></i> ${productText("cartAdded", "Added!")}`;
  setTimeout(() => {
    btn.classList.remove("added");
    btn.innerHTML = `<i class="fas fa-cart-plus"></i> ${productText("addToCart", "Add to Cart")}`;
  }, 1500);
}

function renderProducts(products, container) {
  container.innerHTML = "";

  if (!products.length) {
    container.innerHTML = `
      <li class="no-products">
        <i class="fas fa-box-open"></i>
        <p>${productText("noProducts", "No products found matching your criteria.")}</p>
      </li>`;
    return;
  }

  const fragment = document.createDocumentFragment();
  products.forEach((p) => fragment.appendChild(createProductCard(p)));
  container.appendChild(fragment);

  container.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      handleAddToCartButton(btn);
    });
  });

  // Restock notifications are handled globally by cart.js as a toast form.


  container.querySelectorAll(".product-wishlist").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = parseInt(btn.dataset.id, 10);
      if (typeof Wishlist !== 'undefined') {
          const isActive = await Wishlist.toggle(id);
          if (isActive === null) return;
          showToast(isActive ? productText("addedToWishlist", "Added to wishlist!") : productText("removedFromWishlist", "Removed from wishlist."));
      }
    });
  });

  // Quick View functionality
  container.querySelectorAll(".product-quickview").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const id = parseInt(btn.dataset.id, 10);
      const product = products.find((p) => p.id === id);
      if (product) {
        openQuickViewModal(product);
      }
    });
  });
}


function initFilterTabs(grid) {
  const tabs = document.querySelectorAll(".filter-tab");
  if (!tabs.length) return;

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");

      const cat = tab.dataset.cat;
      const filtered =
        cat === "all"
          ? products
          : products.filter((p) => p.category === cat);

      renderProducts(filtered, grid);
    });
  });
}

const CATEGORY_ALIASES = {
  processors: "cpu",
  processor: "cpu",
  graphics: "gpu",
  "graphics-cards": "gpu",
  memory: "ram",
  motherboard: "motherboard",
  motherboards: "motherboard",
  power: "psu",
  "power-supplies": "psu",
  monitors: "monitor",
  keyboards: "keyboard",
  mice: "mouse",
  mouses: "mouse",
  vr: "vr",
  "vr headset": "vr",
  "vr headsets": "vr",
  headset: "vr",
  headsets: "vr",
  router: "router",
  routers: "router",
  networking: "router",
};

function normalizeCategory(value) {
  const normalized = String(value || "").trim().toLowerCase();
  return CATEGORY_ALIASES[normalized] || normalized;
}

function getCheckedValues(name) {
  return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map((input) => input.value);
}

function getProductSearchText(product) {
  return [
    product.name,
    product.brand,
    product.category,
    ...Object.values(product.specs || {}),
  ].join(" ").toLowerCase();
}

function matchesSearch(product, query) {
  if (!query) return true;
  return getProductSearchText(product).includes(query);
}

function getFilterState() {
  const slider = document.getElementById("priceSlider");
  const minInput = document.getElementById("minPrice");
  const maxInput = document.getElementById("maxPrice");
  const ratingInput = document.querySelector('input[name="rating"]:checked');
  const sortSelect = document.getElementById("sortSelect");

  const sliderMax = slider ? Number(slider.max) : 0;
  const sliderValue = slider ? Number(slider.value) : sliderMax;
  const explicitMax = maxInput?.value !== "" ? Number(maxInput.value) : null;

  return {
    query: new URLSearchParams(window.location.search).get("search")?.trim().toLowerCase() || "",
    categories: getCheckedValues("category").map(normalizeCategory),
    brands: getCheckedValues("brand"),
    availability: getCheckedValues("availability"),
    minPrice: minInput?.value !== "" ? Number(minInput.value) : null,
    maxPrice: explicitMax ?? (sliderValue < sliderMax ? sliderValue : null),
    rating: ratingInput ? Number(ratingInput.value) : null,
    sort: sortSelect?.value || "featured",
  };
}

function sortProductsForView(items, sort) {
  const sorted = [...items];

  switch (sort) {
    case "price-low":
      sorted.sort((a, b) => a.price - b.price);
      break;
    case "price-high":
      sorted.sort((a, b) => b.price - a.price);
      break;
    case "rating":
      sorted.sort((a, b) => (b.rating - a.rating) || (b.reviews - a.reviews));
      break;
    case "newest":
      sorted.sort((a, b) => b.id - a.id);
      break;
    case "featured":
    default:
      sorted.sort((a, b) => Number(Boolean(b.featured)) - Number(Boolean(a.featured)) || (b.rating - a.rating));
      break;
  }

  return sorted;
}

function filterProductsForView() {
  const state = getFilterState();

  return sortProductsForView(products.filter((product) => {
    const category = normalizeCategory(product.category);

    if (!matchesSearch(product, state.query)) return false;
    if (state.categories.length && !state.categories.includes(category)) return false;
    if (state.brands.length && !state.brands.includes(product.brand)) return false;
    if (state.minPrice !== null && product.price < state.minPrice) return false;
    if (state.maxPrice !== null && product.price > state.maxPrice) return false;
    if (state.rating !== null && product.rating < state.rating) return false;

    if (state.availability.length) {
      const wantsInStock = state.availability.includes("instock");
      const wantsOutOfStock = state.availability.includes("preorder");
      if (wantsInStock && !wantsOutOfStock && !product.inStock) return false;
      if (!wantsInStock && wantsOutOfStock && product.inStock) return false;
    } else {
      return false;
    }

    return true;
  }), state.sort);
}

function getInputLabel(input) {
  return input?.closest("label")?.textContent?.replace(/\s+/g, " ").trim() || input?.value || "";
}

function renderActiveFilters() {
  const container = document.getElementById("activeFilters");
  if (!container) return;

  const tags = [];
  const params = new URLSearchParams(window.location.search);
  const searchQuery = params.get("search")?.trim();

  if (searchQuery) {
    tags.push({ type: "search", value: searchQuery, label: `${productText("search", "Search")}: ${searchQuery}` });
  }

  ["category", "brand", "availability"].forEach((name) => {
    document.querySelectorAll(`input[name="${name}"]:checked`).forEach((input) => {
      tags.push({ type: name, value: input.value, label: getInputLabel(input) });
    });
  });

  const rating = document.querySelector('input[name="rating"]:checked');
  if (rating) tags.push({ type: "rating", value: rating.value, label: `${rating.value}+ ${productText("stars", "stars")}` });

  const minPrice = document.getElementById("minPrice")?.value;
  const maxPrice = document.getElementById("maxPrice")?.value;
  if (minPrice || maxPrice) {
    tags.push({
      type: "price",
      value: "price",
      label: `${minPrice || 0} - ${maxPrice || productText("anyPrice", "Any")} DH`,
    });
  }

  container.innerHTML = tags.map((tag) => `
    <button type="button" class="filter-tag" data-filter-type="${escapeHtml(tag.type)}" data-filter-value="${escapeHtml(tag.value)}">
      ${escapeHtml(tag.label)} <i class="fas fa-times"></i>
    </button>
  `).join("");

  container.querySelectorAll(".filter-tag").forEach((button) => {
    button.addEventListener("click", () => removeFilterTag(button.dataset.filterType, button.dataset.filterValue));
  });
}

function removeFilterTag(type, value) {
  if (type === "search") {
    const url = new URL(window.location.href);
    url.searchParams.delete("search");
    window.history.replaceState({}, "", url);
  } else if (type === "rating") {
    const rating = document.querySelector('input[name="rating"]:checked');
    if (rating) rating.checked = false;
  } else if (type === "price") {
    const minPrice = document.getElementById("minPrice");
    const maxPrice = document.getElementById("maxPrice");
    const slider = document.getElementById("priceSlider");
    if (minPrice) minPrice.value = "";
    if (maxPrice) maxPrice.value = "";
    if (slider) slider.value = slider.max;
  } else {
    const input = document.querySelector(`input[name="${type}"][value="${cssEscapeValue(value)}"]`);
    if (input) input.checked = false;
  }

  applyProductFilters();
}

function syncCategoryFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const category = normalizeCategory(params.get("category"));
  if (!category) return;

  const input = document.querySelector(`input[name="category"][value="${cssEscapeValue(category)}"]`);
  if (input) input.checked = true;
}

function applyProductFilters() {
  const grid = document.getElementById("productsGrid");
  if (!grid) return;

  const filtered = filterProductsForView();
  renderProducts(filtered, grid);
  renderActiveFilters();

  const count = document.getElementById("productCount");
  if (count) count.textContent = filtered.length.toLocaleString();
}

function resetProductFilters() {
  document.querySelectorAll('input[name="category"], input[name="brand"], input[name="rating"], input[name="availability"]').forEach((input) => {
    input.checked = input.name === "availability" && input.value === "instock";
  });

  const minPrice = document.getElementById("minPrice");
  const maxPrice = document.getElementById("maxPrice");
  const slider = document.getElementById("priceSlider");
  const sortSelect = document.getElementById("sortSelect");

  if (minPrice) minPrice.value = "";
  if (maxPrice) maxPrice.value = "";
  if (slider) slider.value = slider.max;
  if (sortSelect) sortSelect.value = "featured";

  const url = new URL(window.location.href);
  url.searchParams.delete("search");
  url.searchParams.delete("category");
  window.history.replaceState({}, "", url);
  applyProductFilters();
}

function initProductsPageFilters() {
  syncCategoryFromUrl();

  const slider = document.getElementById("priceSlider");
  const maxPrice = document.getElementById("maxPrice");

  if (slider && maxPrice) {
    slider.addEventListener("input", () => {
      maxPrice.value = slider.value;
      applyProductFilters();
    });
  }

  document.getElementById("applyFilters")?.addEventListener("click", applyProductFilters);
  document.getElementById("clearFilters")?.addEventListener("click", resetProductFilters);
  document.getElementById("sortSelect")?.addEventListener("change", applyProductFilters);

  document.querySelectorAll('input[name="category"], input[name="brand"], input[name="rating"], input[name="availability"], #minPrice, #maxPrice').forEach((control) => {
    control.addEventListener("change", applyProductFilters);
  });

  document.querySelectorAll(".view-btn").forEach((button) => {
    button.addEventListener("click", () => {
      document.querySelectorAll(".view-btn").forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
      document.getElementById("productsGrid")?.classList.toggle("list-view", button.dataset.view === "list");
    });
  });

  applyProductFilters();
}

// Quick View Modal Functions
function openQuickViewModal(product) {
  const modal = document.getElementById('quickViewModal');
  const content = document.getElementById('quickViewContent');
  
  if (!modal || !content) {
    return;
  }

  const discount = product.oldPrice
    ? Math.round(((product.oldPrice - product.price) / product.oldPrice) * 100)
    : 0;

  const imageUrl = normalizeProductImageUrl(product.image);

  content.innerHTML = `
    <div class="modal-image">
      <img src="${imageUrl}" alt="${product.name}" onerror="this.src='Images/products/placeholder-gpu.svg'">
    </div>
    <div class="modal-details">
      <div class="product-category">${product.category.toUpperCase()}</div>
      <h2>${product.name}</h2>
      <div class="product-rating">
        <div class="stars">${renderStars(product.rating)}</div>
        <span class="product-reviews">(${product.reviews} ${productText("reviews", "reviews")})</span>
      </div>
      <div class="product-price-row">
        <span class="product-price" style="font-size: 2rem;">${formatMAD(product.price)}</span>
        ${product.oldPrice ? `<span class="product-old-price">${formatMAD(product.oldPrice)}</span>` : ''}
        ${discount > 0 ? `<span class="product-discount">-${discount}%</span>` : ''}
      </div>
      <p class="description">${productTemplate("productDescriptionTemplate", "Premium {category} from {brand}. Built for enthusiasts who demand the best performance and reliability.", { category: product.category, brand: product.brand })}</p>
      <div class="specs">
        ${Object.entries(product.specs || {}).map(([key, val]) => `
          <div class="spec-item">
            <div class="spec-key">${translateSpecKey(key)}</div>
            <div class="spec-val">${val}</div>
          </div>
        `).join('')}
      </div>
      ${product.inStock 
        ? `<button type="button" class="btn btn-primary add-to-cart-btn-modal" data-id="${product.id}" style="margin-top: 16px; width: 100%;">
             <i class="fas fa-cart-plus"></i> ${productText("addToCart", "Add to Cart")}
           </button>`
        : `<button type="button" class="btn btn-secondary" style="margin-top: 16px; width: 100%; opacity: 0.6;" disabled>
             <i class="fas fa-times-circle"></i> ${productText("cartOutOfStock", "Out of Stock")}
           </button>`
      }
    </div>
  `;

  // Add to cart handler
  const addBtn = content.querySelector('.add-to-cart-btn-modal');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const cart = getSharedCart();
      if (cart && typeof cart.add === 'function') {
        const added = cart.add(product);
        if (added) closeQuickViewModal();
      }
    });
  }

  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
  
  // Ensure modal is visible
  modal.style.display = 'flex';
}

function closeQuickViewModal() {
  const modal = document.getElementById('quickViewModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    modal.style.display = 'none';
  }
}

window.renderProducts = renderProducts;
window.openQuickViewModal = openQuickViewModal;
window.closeQuickViewModal = closeQuickViewModal;

document.addEventListener("DOMContentLoaded", () => {
  const cart = getSharedCart();
  if (cart && typeof cart.updateUI === "function") {
    cart.updateUI();
  }


  const featuredGrid = document.getElementById("featuredProducts");
  if (featuredGrid) {
    const featured = products.filter((p) => p.featured);
    renderProducts(featured, featuredGrid);

    // Swap skeleton loaders for real products
    const skeletonGrid = document.getElementById("skeletonGrid");
    if (skeletonGrid) skeletonGrid.style.display = "none";
    featuredGrid.classList.remove("hidden");
  }


  const fullGrid = document.getElementById("productsGrid");
  if (fullGrid) {
    initProductsPageFilters();
    initFilterTabs(fullGrid);
  }

  // Quick View Modal close handlers
  const modal = document.getElementById('quickViewModal');
  if (modal) {
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    
    if (closeBtn) {
      closeBtn.addEventListener('click', closeQuickViewModal);
    }
    
    if (overlay) {
      overlay.addEventListener('click', closeQuickViewModal);
    }
    
    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeQuickViewModal();
      }
    });
  }
});
})();

