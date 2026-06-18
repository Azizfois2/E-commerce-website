/**
 * Lightweight authentication enhancements.
 * Keep the auth pages polished without 3D tilt, animated particles, or effects
 * that compete with the form controls.
 */

document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.documentElement.classList.add('auth-effects-lite');

    if (!reduceMotion) {
        const ambientLayer = document.createElement('div');
        ambientLayer.className = 'auth-ambient-layer';
        ambientLayer.setAttribute('aria-hidden', 'true');

        for (let i = 0; i < 9; i++) {
            const trace = document.createElement('span');
            trace.style.setProperty('--x', `${8 + i * 11}%`);
            trace.style.setProperty('--delay', `${i * -1.4}s`);
            trace.style.setProperty('--height', `${80 + (i % 3) * 42}px`);
            ambientLayer.appendChild(trace);
        }

        document.body.prepend(ambientLayer);
    }

    document.querySelectorAll('.hero-overlay, .inscription form').forEach((element) => {
        element.classList.add('auth-title-ready');
    });

    const pendingDirection = sessionStorage.getItem('authTransitionDirection');
    if (pendingDirection) {
        document.body.dataset.authEnteredFrom = pendingDirection;
        sessionStorage.removeItem('authTransitionDirection');
    }

    const transitionLayer = document.createElement('div');
    transitionLayer.className = 'auth-page-transition';
    transitionLayer.setAttribute('aria-hidden', 'true');
    transitionLayer.innerHTML = `
        <span class="auth-transition-line"></span>
        <span class="auth-transition-core"></span>
    `;
    document.body.appendChild(transitionLayer);

    const authSwitchLinks = document.querySelectorAll(
        '[data-auth-transition], a[href$="login.php"], a[href$="signup.php"]'
    );

    authSwitchLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (
                event.defaultPrevented ||
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                link.target === '_blank' ||
                reduceMotion
            ) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href) return;

            event.preventDefault();
            const direction = href.includes('login.php') ? 'back' : 'forward';
            document.body.dataset.authTransition = direction;
            sessionStorage.setItem('authTransitionDirection', direction);

            window.requestAnimationFrame(() => {
                document.body.classList.add('auth-transitioning');
                transitionLayer.classList.add('is-active');

                window.setTimeout(() => {
                    window.location.href = href;
                }, 720);
            });
        });
    });
});
