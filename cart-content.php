<!-- Breadcrumb -->
  <section class="breadcrumb">
    <div class="container">
      <nav class="breadcrumb-nav">
        <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.home'); ?></a>
        <i class="fas fa-chevron-right"></i>
        <span><?php i18n_e('cart.heading'); ?></span>
      </nav>
    </div>
  </section>

  <!-- Cart Page -->
  <section class="cart-page">
    <div class="container">
      <h1 class="page-title"><?php i18n_e('cart.heading'); ?></h1>

      <!-- Empty Cart State -->
      <div class="empty-cart" id="emptyCart" style="display: none;">
        <div class="empty-cart-icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <h2><?php i18n_e('cart.empty_title'); ?></h2>
        <p><?php i18n_e('cart.empty_body'); ?></p>
        <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?php i18n_e('cart.start_shopping'); ?></a>
      </div>

      <!-- Price Lock Banner -->
      <div id="priceLockBanner" style="display: none; margin-bottom: 24px; padding: 16px 24px; background: rgba(0, 245, 212, 0.06); border: 1px solid var(--cyan); border-radius: 16px; align-items: center; gap: 16px;">
        <div style="font-size: 1.5rem; color: var(--cyan);"><i class="fas fa-lock"></i></div>
        <div style="flex: 1;">
          <h4 style="margin: 0 0 4px; font-family: 'Orbitron', sans-serif; font-size: 0.95rem; color: var(--text);"><?php i18n_e('cart.price_lock_title'); ?></h4>
          <p style="margin: 0; font-size: 0.85rem; color: var(--muted);"><?php i18n_e('cart.price_lock_body'); ?></p>
        </div>
        <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; font-weight: 700; color: var(--cyan); text-align: right;" id="priceLockTimer">
          00h 00m 00s
        </div>
      </div>

      <!-- Cart Content -->
      <div class="cart-layout" id="cartContent">
        <!-- Cart Items -->
        <div class="cart-items">
          <div class="cart-header-row">
            <div class="cart-col-product"><?php i18n_e('cart.product'); ?></div>
            <div class="cart-col-price"><?php i18n_e('cart.price'); ?></div>
            <div class="cart-col-quantity"><?php i18n_e('cart.quantity'); ?></div>
            <div class="cart-col-total"><?php i18n_e('cart.total'); ?></div>
            <div class="cart-col-action"></div>
          </div>
          <div class="cart-items-list" id="cartItemsList">
            <!-- Cart items will be loaded via JavaScript -->
          </div>
          <div class="cart-actions">
            <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i> <?php i18n_e('cart.continue_shopping'); ?>
            </a>
            <button class="btn btn-secondary" id="clearCart">
              <i class="fas fa-trash"></i> <?php i18n_e('cart.clear_cart'); ?>
            </button>
          </div>
        </div>

        <!-- Complete My Build Upsell (Injected via JS) -->
        <div id="completeBuildUpsell" style="display: none;"></div>

        <!-- Cart Summary -->
        <div class="cart-summary">
          <h3><?php i18n_e('cart.order_summary'); ?></h3>
          <div class="summary-row">
            <span><?php i18n_e('cart.subtotal'); ?> (<span id="cartItemsCount">0</span> <?php i18n_e('cart.items'); ?>)</span>
            <span id="subtotal">0.00 DH</span>
          </div>
          <div class="summary-row">
            <span><?php i18n_e('cart.shipping'); ?></span>
            <span id="shipping" class="free-shipping"><?php i18n_e('cart.calculated_checkout'); ?></span>
          </div>
          <div class="summary-row">
            <span><?php i18n_e('cart.estimated_tax'); ?></span>
            <span id="tax">0.00 DH</span>
          </div>
          <div class="promo-code">
            <input type="text" placeholder="<?php i18n_e('cart.promo_code'); ?>" id="promoCode">
            <button class="btn btn-secondary" id="applyPromo"><?php i18n_e('cart.apply'); ?></button>
          </div>
          <div class="summary-row discount" id="discountRow" style="display: none;">
            <span><?php i18n_e('cart.discount'); ?></span>
            <span id="discount">-0.00 DH</span>
          </div>
          <div class="summary-total">
            <span><?php i18n_e('cart.total'); ?></span>
            <span id="total">0.00 DH</span>
          </div>
          <div id="cartInstallmentWidget">
            <!-- Installment widget injected by JS -->
          </div>
          <a href="<?= htmlspecialchars(i18n_url('checkout.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-block" id="checkoutBtn">
            <?php i18n_e('cart.proceed_checkout'); ?>
          </a>

          <!-- Payment Methods -->
          <div class="payment-methods-small">
            <p><?php i18n_e('cart.we_accept'); ?></p>
            <div class="payment-icons">
              <i class="fab fa-cc-visa" title="Visa"></i>
              <i class="fab fa-cc-mastercard" title="Mastercard"></i>
              <i class="fab fa-cc-paypal" title="PayPal"></i>
              <i class="fab fa-bitcoin" title="Bitcoin"></i>
              <i class="fab fa-apple-pay" title="Apple Pay"></i>
              <i class="fab fa-google-pay" title="Google Pay"></i>
            </div>
          </div>

          <!-- Security Badges -->
          <div class="security-badges">
            <div class="badge">
              <i class="fas fa-lock"></i>
              <span><?php i18n_e('cart.secure_checkout'); ?></span>
            </div>
            <div class="badge">
              <i class="fas fa-shield-alt"></i>
              <span><?php i18n_e('cart.ssl_encrypted'); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recently Viewed -->
      <div class="recently-viewed" id="recentlyViewed" style="display: none;">
        <h2><?php i18n_e('cart.recently_viewed'); ?></h2>
        <div class="products-grid" id="recentlyViewedGrid">
          <!-- Products will be loaded via JavaScript -->
        </div>
      </div>
    </div>
  </section>

  
