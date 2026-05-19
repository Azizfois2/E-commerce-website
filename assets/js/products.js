function getSharedCart() {
  return typeof window !== "undefined" ? window.Cart : undefined;
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
  return `<span class="product-badge" style="background:${colour}">${badge}</span>`;
}


function formatMAD(value) {
  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' MAD';
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
    ? `<button class="btn btn-primary add-to-cart-btn" data-id="${product.id}">
         <i class="fas fa-cart-plus"></i> Add to Cart
       </button>`
    : `<button class="btn btn-secondary notify-restock-btn" data-id="${product.id}" data-name="${product.name}">
         <i class="fas fa-bell"></i> Notify Me
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
      <button class="product-wishlist ${isWished ? 'active' : ''}" aria-label="Add to wishlist" data-id="${product.id}">
        <i class="${isWished ? 'fas' : 'far'} fa-heart"></i>
      </button>
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

function renderProducts(products, container) {
  container.innerHTML = "";

  if (!products.length) {
    container.innerHTML = `
      <li class="no-products">
        <i class="fas fa-box-open"></i>
        <p>No products found.</p>
      </li>`;
    return;
  }

  const fragment = document.createDocumentFragment();
  products.forEach((p) => fragment.appendChild(createProductCard(p)));
  container.appendChild(fragment);

  container.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const id = parseInt(btn.dataset.id, 10);
      const cart = getSharedCart();
      const product = products.find((p) => p.id === id);
      if (!cart || !product || typeof cart.add !== "function") return;
      cart.add(product);
      showToast(`"${product.name}" added to cart!`);


      btn.classList.add("added");
      btn.innerHTML = `<i class="fas fa-check"></i> Added!`;
      setTimeout(() => {
        btn.classList.remove("added");
        btn.innerHTML = `<i class="fas fa-cart-plus"></i> Add to Cart`;
      }, 1500);
    });
  });

  container.querySelectorAll(".notify-restock-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const name = btn.dataset.name;
      const email = prompt(`Notify me when "${name}" is back in stock:\n\nPlease enter your email address:`);
      if (email && email.trim() !== '') {
          fetch('api/restock-notify.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ product_id: id, email: email.trim() })
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) showToast(data.message);
              else showToast(data.error || 'Failed to subscribe', 'error');
          })
          .catch(() => showToast('Network error', 'error'));
      }
    });
  });


  container.querySelectorAll(".product-wishlist").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = parseInt(btn.dataset.id, 10);
      if (typeof Wishlist !== 'undefined') {
          const isActive = await Wishlist.toggle(id);
          showToast(isActive ? "Added to wishlist!" : "Removed from wishlist.");
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

document.addEventListener("DOMContentLoaded", () => {
  const cart = getSharedCart();
  if (cart && typeof cart.updateUI === "function") {
    cart.updateUI();
  }


  const featuredGrid = document.getElementById("featuredProducts");
  if (featuredGrid) {
    const featured = products.filter((p) => p.featured);
    renderProducts(featured, featuredGrid);
  }


  const fullGrid = document.getElementById("productsGrid");
  if (fullGrid) {
    const params = new URLSearchParams(window.location.search);
    const searchQuery = params.get('search');
    let displaySet = products;

    if (searchQuery) {
      const q = searchQuery.toLowerCase().trim();
      displaySet = products.filter(p =>
        p.name.toLowerCase().includes(q) ||
        p.brand.toLowerCase().includes(q) ||
        p.category.toLowerCase().includes(q)
      );
    }

    renderProducts(displaySet, fullGrid);
    initFilterTabs(fullGrid);
  }
});