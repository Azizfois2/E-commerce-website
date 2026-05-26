/* ============================================
   ULTRA-PREMIUM PAGE TRANSITIONS
   "Boot Sequence & Blast Doors"
   ============================================ */

(function() {
  'use strict';

  function initPageTransitions() {
    // 1. Inject Styles
    const style = document.createElement('style');
    style.textContent = `
      .pt-overlay {
        position: fixed;
        inset: 0;
        z-index: 999999;
        pointer-events: all;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        overflow: hidden;
      }
      .pt-overlay.hidden {
        pointer-events: none;
      }
      .pt-door {
        position: absolute;
        left: 0;
        width: 100%;
        height: 50vh;
        background: var(--page-bg, #050505);
        transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1);
        z-index: 1;
        border-bottom: 2px solid rgba(0, 245, 212, 0.1);
      }
      .pt-door-top {
        top: 0;
      }
      .pt-door-bottom {
        bottom: 0;
        border-bottom: none;
        border-top: 2px solid rgba(0, 245, 212, 0.1);
      }
      .pt-overlay.hidden .pt-door-top {
        transform: translateY(-100%);
      }
      .pt-overlay.hidden .pt-door-bottom {
        transform: translateY(100%);
      }
      
      .pt-terminal {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        transition: opacity 0.3s ease, transform 0.4s ease;
      }
      .pt-overlay.hidden .pt-terminal {
        opacity: 0;
        transform: scale(0.9);
      }
      
      .pt-core {
        width: 60px;
        height: 60px;
        border: 2px solid var(--cyan, #00f5d4);
        border-radius: 50%;
        position: relative;
        box-shadow: 0 0 20px rgba(0, 245, 212, 0.4), inset 0 0 20px rgba(0, 245, 212, 0.2);
        animation: pulseCore 2s infinite ease-in-out;
      }
      .pt-core::before {
        content: '';
        position: absolute;
        inset: 15px;
        background: var(--cyan, #00f5d4);
        border-radius: 50%;
        box-shadow: 0 0 30px var(--cyan, #00f5d4);
        animation: pulseInner 1s infinite alternate;
      }
      
      @keyframes pulseCore {
        0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(0, 245, 212, 0.4), inset 0 0 20px rgba(0, 245, 212, 0.2); }
        50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(0, 245, 212, 0.6), inset 0 0 30px rgba(0, 245, 212, 0.4); }
      }
      @keyframes pulseInner {
        from { transform: scale(0.8); opacity: 0.7; }
        to { transform: scale(1.1); opacity: 1; }
      }
      
      .pt-boot-text {
        color: var(--cyan, #00f5d4);
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.85rem;
        letter-spacing: 1px;
        min-height: 20px;
        text-transform: uppercase;
        font-weight: 700;
        text-shadow: 0 0 8px rgba(0, 245, 212, 0.5);
      }
    `;
    document.head.appendChild(style);

    // 2. Create Overlay HTML
    const overlay = document.createElement('div');
    overlay.className = 'pt-overlay';
    overlay.innerHTML = `
      <div class="pt-door pt-door-top"></div>
      <div class="pt-door pt-door-bottom"></div>
      <div class="pt-terminal">
        <div class="pt-core"></div>
        <div class="pt-boot-text">> INITIALIZING...</div>
      </div>
    `;
    document.body.appendChild(overlay);

    const bootTextEl = overlay.querySelector('.pt-boot-text');
    
    const bootMessages = [
      "> MOUNTING KERNEL...",
      "> ALLOCATING MEMORY...",
      "> ESTABLISHING SECURE UPLINK...",
      "> LOADING MAROC PC PROTOCOLS...",
      "> SYSTEM READY."
    ];

    // Show transition (close doors)
    function showTransition() {
      overlay.classList.remove('hidden');
      bootTextEl.textContent = "> REBOOTING...";
    }

    // Hide transition (open blast doors)
    function hideTransition() {
      // Run the boot sequence rapidly if it hasn't run
      let delay = 0;
      
      bootMessages.forEach((msg, index) => {
        setTimeout(() => {
          bootTextEl.textContent = msg;
        }, delay);
        delay += 150; // fast typing speed
      });

      // Open doors after sequence finishes
      setTimeout(() => {
        overlay.classList.add('hidden');
      }, delay + 200);
    }

    // Initial page load trigger
    if (document.readyState === 'complete') {
      hideTransition();
    } else {
      window.addEventListener('load', hideTransition, { once: true });
      // Failsafe timeout
      setTimeout(hideTransition, 2000);
    }

    // Intercept navigation clicks
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      
      // Only handle internal links that aren't anchors or downloads
      if (link && 
          link.href && 
          link.href.startsWith(window.location.origin) &&
          !link.href.includes('#') &&
          !link.target &&
          !link.download) {
        
        e.preventDefault();
        showTransition();
        
        // Wait for doors to close, then navigate
        setTimeout(() => {
          window.location.href = link.href;
        }, 600); // 600ms closing animation
      }
    });

    // Handle back/forward cache restore
    window.addEventListener('pageshow', (event) => {
      if (event.persisted) {
        hideTransition();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageTransitions);
  } else {
    initPageTransitions();
  }

})();
