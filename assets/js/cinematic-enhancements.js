/* ============================================
   CINEMATIC ENHANCEMENTS - JAVASCRIPT
   Advanced interactions and animations
   ============================================ */

(function() {
  'use strict';

  // ── SCROLL PROGRESS BAR ────────────────────────────────
  function initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
      const winScroll = document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + '%';
    });
  }

  // ── CUSTOM CURSOR (SONIC BOOM) ──────────────────────────────
  function initCustomCursor() {
    if (window.innerWidth <= 768) return;
    if (window.matchMedia('(hover: none) and (pointer: coarse)').matches) return;

    const cursorDot = document.createElement('div');
    const cursorRing = document.createElement('div');
    cursorDot.className = 'cursor-dot';
    cursorRing.className = 'cursor-ring';
    document.body.appendChild(cursorDot);
    document.body.appendChild(cursorRing);

    let mouseX = -100;
    let mouseY = -100;
    let ringX = -100;
    let ringY = -100;
    let ringScale = 1;
    let cursorReady = false;

    function animateRing() {
      ringX += (mouseX - ringX) * 0.25;
      ringY += (mouseY - ringY) * 0.25;
      cursorRing.style.transform = `translate3d(${ringX}px, ${ringY}px, 0) translate(-50%, -50%) scale(${ringScale})`;

      if (!cursorReady) {
        document.documentElement.classList.add('has-custom-cursor');
        cursorReady = true;
      }

      requestAnimationFrame(animateRing);
    }
    requestAnimationFrame(animateRing);

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      cursorDot.style.transform = `translate3d(${mouseX}px, ${mouseY}px, 0) translate(-50%, -50%)`;
    }, { passive: true });

    const hoverSelector = 'a, button, .product-card, .category-card, .nav-link, [role="button"], input[type="submit"]';
    document.addEventListener('mouseover', (e) => {
      if (e.target.closest(hoverSelector)) {
        ringScale = 1.35;
        cursorRing.classList.add('hover');
      }
    }, { passive: true });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest(hoverSelector)) {
        ringScale = 1;
        cursorRing.classList.remove('hover');
      }
    }, { passive: true });
  }

  // ── RIPPLE EFFECT ────────────────────────────────
  function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    
    const size = Math.max(rect.width, rect.height);
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = event.clientX - rect.left - size / 2 + 'px';
    ripple.style.top = event.clientY - rect.top - size / 2 + 'px';
    ripple.classList.add('ripple');
    
    button.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  }

  function initRippleEffect() {
    document.querySelectorAll('.btn-primary, .btn-secondary, button').forEach(btn => {
      if (btn.dataset.cinematicRipple === '1') return;
      btn.dataset.cinematicRipple = '1';
      btn.addEventListener('click', createRipple);
    });
  }

  // ── MAGNETIC BUTTONS ────────────────────────────────
  function initMagneticButtons() {
    document.querySelectorAll('.btn-primary, .btn-secondary, .magnetic-btn').forEach(btn => {
      if (btn.dataset.cinematicMagnetic === '1') return;
      if (btn.matches('.add-to-cart-btn, .add-to-cart-btn-modal, .detail-add-to-cart, .featured-add-to-cart, .flash-sale-btn')) {
        return;
      }
      btn.dataset.cinematicMagnetic = '1';

      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        
        btn.style.transform = `translate(${x * 0.15}px, ${y * 0.15}px) scale(1.05)`;
      });
      
      btn.addEventListener('mouseleave', () => {
        btn.style.transform = 'translate(0, 0) scale(1)';
      });
    });
  }

  // ── 3D TILT EFFECT (BALANCED) ────────────────────────────────
  function init3DTilt(root = document) {
    root.querySelectorAll('.product-card').forEach(card => {
      if (card.dataset.cinematicTilt === '1') return;
      card.dataset.cinematicTilt = '1';
      card.classList.add('product-card-3d');
      
      card.addEventListener('mousemove', (e) => {
        if (e.target.closest('button, a, input, select, textarea, [role="button"]')) {
          card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
          return;
        }

        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const rotateX = clamp((y - centerY) / 24, -4, 4);
        const rotateY = clamp((centerX - x) / 24, -4, 4);
        
        card.style.transform = `perspective(1200px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.012, 1.012, 1.012)`;
        card.style.setProperty('--mouse-x', `${(x / rect.width) * 100}%`);
        card.style.setProperty('--mouse-y', `${(y / rect.height) * 100}%`);
      });
      
      card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
      });
    });
  }

  function initDynamicCardEnhancements() {
    const observer = new MutationObserver((mutations) => {
      let shouldRefreshTilt = false;
      let shouldRefreshButtons = false;

      mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
          if (!(node instanceof HTMLElement)) return;
          if (node.matches('.product-card') || node.querySelector('.product-card')) {
            shouldRefreshTilt = true;
          }
          if (node.matches('button, .btn-primary, .btn-secondary, .magnetic-btn') || node.querySelector('button, .btn-primary, .btn-secondary, .magnetic-btn')) {
            shouldRefreshButtons = true;
          }
        });
      });

      if (shouldRefreshTilt) init3DTilt();
      if (shouldRefreshButtons) {
        initRippleEffect();
        initMagneticButtons();
        initShineEffects();
      }
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }


  // ── SMOOTH SCROLL ────────────────────────────────
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
          });
        }
      });
    });
  }

  // ── LAZY LOADING IMAGES ────────────────────────────────
  function initLazyLoading() {
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
            img.classList.add('loaded');
            imageObserver.unobserve(img);
          }
        }
      });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
      imageObserver.observe(img);
    });
  }

  // ── PARALLAX SCROLL ────────────────────────────────
  function initParallax() {
    let ticking = false;

    function updateParallax() {
      const scrolled = window.pageYOffset;
      
      document.querySelectorAll('[data-parallax]').forEach(el => {
        const speed = parseFloat(el.dataset.parallax) || 0.5;
        const yPos = -(scrolled * speed);
        el.style.setProperty('--parallax-y', `${yPos}px`);
      });

      ticking = false;
    }

    updateParallax();

    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(updateParallax);
        ticking = true;
      }
    }, { passive: true });
  }

  // ── HERO ENTRANCE (REMOVED) ────────────────────────────────
  // Handled by original CSS animations

  // ── ENHANCED SCROLL ANIMATIONS (REMOVED) ────────────────────────────────
  // Handled by script.js IntersectionObserver


  // ── HERO PARTICLES ────────────────────────────────
  function initHeroParticles() {
    const hero = document.querySelector('.hero-bg');
    if (!hero) return;


    const particles = document.createElement('div');
    particles.className = 'hero-particles';
    hero.insertBefore(particles, hero.firstChild);
  }

  // ── PROGRESSIVE IMAGE LOADING ────────────────────────────────
  function initProgressiveImages() {
    document.querySelectorAll('.progressive-image').forEach(img => {
      if (img.complete) {
        img.classList.add('loaded');
      } else {
        img.addEventListener('load', () => {
          img.classList.add('loaded');
        });
      }
    });
  }

  // ── ADD SHINE EFFECT TO BUTTONS ────────────────────────────────
  function initShineEffects() {
    document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
      btn.classList.add('shine-effect');
    });
  }

  // ── ENHANCED NAVBAR ON SCROLL ────────────────────────────────
  function initNavbarScroll() {
    const navbar = document.querySelector('.myDIV');
    if (!navbar) return;

    let lastScroll = 0;
    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;
      
      if (currentScroll > 100) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
      
      lastScroll = currentScroll;
    });
  }

  // ── INITIALIZE ALL ENHANCEMENTS ────────────────────────────────
  function init() {
    // Initialize all features
    initScrollProgress();
    initCustomCursor();
    initRippleEffect();
    initMagneticButtons();
    init3DTilt();
    initSmoothScroll();
    initLazyLoading();
    initParallax();
    // initHeroParticles(); // DISABLED: Causes massive lag when placed over video backgrounds
    initProgressiveImages();
    initShineEffects();
    initNavbarScroll();
    initDynamicCardEnhancements();

    window.CinematicEnhancements = {
      refresh: () => {
        init3DTilt();
        initRippleEffect();
        initMagneticButtons();
        initShineEffects();
      }
    };

    console.log('🎬 Cinematic enhancements loaded successfully!');
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
