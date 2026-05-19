/**
 * typing.js
 * Character-by-character typewriter for .effet-nadi
 * Works at any font size — zero CSS width math involved.
 *
 * Load this as the LAST script before </body>
 * or wrap in DOMContentLoaded.
 */

(function () {
  'use strict';

  function runTypewriter() {
    const el = document.querySelector('.effet-nadi');
    if (!el) return;

    // Grab the full intended text from the element
    const fullText = el.textContent.trim();

    // Clear the element — we'll write characters back one by one
    el.textContent = '';

    // Inject the blinking caret immediately after the span
    const caret = document.createElement('span');
    caret.className = 'type-caret';
    caret.setAttribute('aria-hidden', 'true');
    el.parentNode.insertBefore(caret, el.nextSibling);

    // Elements that fade in AFTER typing completes
    const afterEls = [
      document.getElementById('Lktba'),
      document.querySelector('.hero-buttons'),
      document.querySelector('.hero-stats'),
    ].filter(Boolean);

    // Pause their entrance animations until typing done
    afterEls.forEach(e => {
      e.style.animationPlayState = 'paused';
      e.style.opacity = '0';
    });

    let index = 0;
    const CHAR_DELAY = 72;   // ms per character — adjust for faster/slower

    function typeNext() {
      if (index < fullText.length) {
        el.textContent += fullText[index];
        index++;
        setTimeout(typeNext, CHAR_DELAY);
      } else {
        // Typing done — remove caret after a short pause, then fade in content
        setTimeout(function () {
          caret.style.animation = 'none';
          caret.style.opacity   = '0';

          // Release the hero content fade-ins
          afterEls.forEach(function (e, i) {
            setTimeout(function () {
              e.style.transition  = 'opacity 0.9s ease, transform 0.9s ease';
              e.style.transform   = 'translateY(0)';
              e.style.opacity     = '1';
            }, i * 180);   // stagger each element by 180ms
          });

        }, 500);
      }
    }

    // Small initial delay so page paint finishes first
    setTimeout(typeNext, 300);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runTypewriter);
  } else {
    runTypewriter();
  }

})();