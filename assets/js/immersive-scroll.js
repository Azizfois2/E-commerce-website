/**
 * immersive-scroll.js — Scroll-driven animation engine for Maroc PC homepage.
 *
 * Features:
 *   • Scroll-progress reveals (fade, slide, scale, stagger)
 *   • Parallax depth layers
 *   • Animated counters for stat numbers
 *   • Horizontal scroll for category explorer
 *   • Respects prefers-reduced-motion
 *
 * Usage: add data-scroll="reveal" to any element. Options via data-scroll-* attrs.
 */
(function () {
    'use strict';

    /* ── Reduced Motion Guard ─────────────────────────────────── */
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Scroll Progress Tracker ──────────────────────────────── */
    function getScrollProgress(el) {
        const rect = el.getBoundingClientRect();
        const vh = window.innerHeight;
        // 0 = element just entered bottom, 1 = element top at viewport top
        return Math.min(1, Math.max(0, (vh - rect.top) / (vh + rect.height)));
    }

    /* ── Reveal Observer ──────────────────────────────────────── */
    const revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const delay = parseInt(el.dataset.scrollDelay || '0', 10);
                    setTimeout(() => {
                        el.classList.add('scroll-visible');
                    }, prefersReducedMotion ? 0 : delay);
                    revealObserver.unobserve(el);
                }
            });
        },
        { rootMargin: '0px 0px -60px 0px', threshold: 0.08 }
    );

    /* ── Stagger Children ─────────────────────────────────────── */
    const staggerObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const parent = entry.target;
                    const selector = parent.dataset.staggerChild || ':scope > *';
                    const children = parent.querySelectorAll(selector);
                    const baseDelay = parseInt(parent.dataset.staggerDelay || '80', 10);
                    children.forEach((child, i) => {
                        child.style.transitionDelay = prefersReducedMotion ? '0ms' : `${i * baseDelay}ms`;
                        child.classList.add('scroll-visible');
                    });
                    parent.classList.add('scroll-visible');
                    staggerObserver.unobserve(parent);
                }
            });
        },
        { rootMargin: '0px 0px -40px 0px', threshold: 0.05 }
    );

    /* ── Parallax Engine (rAF-driven) ─────────────────────────── */
    const parallaxElements = [];

    function tickParallax() {
        for (const item of parallaxElements) {
            const progress = getScrollProgress(item.el);
            const speed = item.speed;
            const yOffset = (progress - 0.5) * speed * 100;
            item.el.style.transform = `translate3d(0, ${yOffset}px, 0)`;
        }
        requestAnimationFrame(tickParallax);
    }

    /* ── Animated Counter ─────────────────────────────────────── */
    function animateCounter(el) {
        const target = parseFloat(el.dataset.countTo || el.textContent.replace(/[^0-9.]/g, ''));
        const suffix = el.dataset.countSuffix || '';
        const prefix = el.dataset.countPrefix || '';
        const duration = parseInt(el.dataset.countDuration || '1800', 10);
        const useComma = el.dataset.countComma !== 'false';

        if (isNaN(target)) return;

        const start = performance.now();
        const isFloat = target % 1 !== 0;

        function step(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = eased * target;
            const formatted = isFloat
                ? current.toFixed(1)
                : useComma
                    ? Math.floor(current).toLocaleString('en-US')
                    : Math.floor(current).toString();
            el.textContent = prefix + formatted + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    }

    const counterObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 }
    );

    /* ── Product Reveal Sequence (scroll-pinned cards) ─────── */
    function initProductReveal() {
        const container = document.querySelector('.product-reveal-sequence');
        if (!container) return;

        const cards = container.querySelectorAll('.reveal-card');
        const stepEl = container.querySelector('.reveal-showcase-step');
        const titleEl = container.querySelector('.reveal-showcase-title');
        const descEl = container.querySelector('.reveal-showcase-desc');
        const focusEl = container.querySelector('.reveal-showcase-focus');
        const outputEl = container.querySelector('.reveal-showcase-output');
        if (cards.length === 0) return;

        function setActiveCard(card) {
            cards.forEach((item) => item.classList.toggle('reveal-card-current', item === card));
            if (!card) return;

            card.classList.add('reveal-card-active');
            if (stepEl) stepEl.textContent = card.dataset.revealStep || '';
            if (titleEl) titleEl.textContent = card.dataset.revealTitle || '';
            if (descEl) descEl.textContent = card.dataset.revealDesc || '';
            if (focusEl) focusEl.textContent = card.dataset.revealFocus || '';
            if (outputEl) outputEl.textContent = card.dataset.revealOutput || '';
        }

        const revealCardObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-card-active');
                        setActiveCard(entry.target);
                    }
                });
            },
            { rootMargin: '-24% 0px -24% 0px', threshold: 0.45 }
        );

        cards.forEach((card, index) => {
            revealCardObserver.observe(card);
            card.addEventListener('mouseenter', () => setActiveCard(card));
            card.addEventListener('focusin', () => setActiveCard(card));
            card.addEventListener('click', () => setActiveCard(card));
            if (index === 0) {
                setActiveCard(card);
                if (prefersReducedMotion) card.classList.add('reveal-card-active');
            }
        });
    }

    /* ── Ecosystem Diagram Interactivity ── */
    function initEcosystem() {
        const eco = document.querySelector('.ecosystem-explorer');
        if (!eco) return;

        const nodes = eco.querySelectorAll('.eco-node');
        const infoPanel = eco.querySelector('.eco-info-panel');
        const ctaBtn = document.getElementById('eco-cta-btn');
        const ctaText = document.getElementById('eco-cta-text');
        const defaultComponent = eco.dataset.defaultComponent || '';

        let lockedNode = null;

        function applyNodeState(node) {
            nodes.forEach((n) => {
                n.classList.remove('eco-node-connected', 'eco-node-dim', 'eco-node-active');
            });

            if (!node) {
                if (infoPanel) infoPanel.classList.remove('eco-info-visible');
                if (ctaBtn && ctaText) {
                    ctaBtn.href = ctaBtn.dataset.defaultUrl || '#';
                    ctaText.textContent = ctaBtn.dataset.defaultText || '';
                }
                return;
            }

            const connections = (node.dataset.connects || '').split(',').map((s) => s.trim()).filter(Boolean);
            nodes.forEach((n) => {
                if (connections.includes(n.dataset.component)) {
                    n.classList.add('eco-node-connected');
                } else if (n !== node) {
                    n.classList.add('eco-node-dim');
                }
            });
            node.classList.add('eco-node-active');

            if (infoPanel) {
                infoPanel.querySelector('.eco-info-title').textContent = node.dataset.label || '';
                infoPanel.querySelector('.eco-info-desc').textContent = node.dataset.desc || '';
                infoPanel.classList.add('eco-info-visible');
            }

            if (ctaBtn && ctaText) {
                const url = node.dataset.ctaUrl;
                const text = node.dataset.ctaText;
                if (url && text) {
                    ctaBtn.href = url;
                    ctaText.textContent = text;
                    ctaBtn.style.transform = 'scale(1.05)';
                    setTimeout(() => { ctaBtn.style.transform = 'scale(1)'; }, 200);
                }
            }
        }

        nodes.forEach((node) => {
            node.addEventListener('mouseenter', () => {
                applyNodeState(node);
            });

            node.addEventListener('focusin', () => {
                applyNodeState(node);
            });

            node.addEventListener('mouseleave', () => {
                applyNodeState(lockedNode || eco.querySelector(`.eco-node[data-component="${defaultComponent}"]`));
            });

            node.addEventListener('click', (e) => {
                e.preventDefault();
                if (lockedNode === node) {
                    lockedNode = null;
                    applyNodeState(eco.querySelector(`.eco-node[data-component="${defaultComponent}"]`));
                } else {
                    lockedNode = node;
                    applyNodeState(node);
                }

                node.style.transform = 'scale(1.1)';
                setTimeout(() => { node.style.transform = ''; }, 200);
            });
        });

        document.addEventListener('click', (e) => {
            if (!eco.contains(e.target) && lockedNode) {
                lockedNode = null;
                applyNodeState(eco.querySelector(`.eco-node[data-component="${defaultComponent}"]`));
            }
        });

        const defaultNode = eco.querySelector(`.eco-node[data-component="${defaultComponent}"]`) || nodes[0];
        if (defaultNode) {
            applyNodeState(defaultNode);
        }
    }

    /* ── Category Horizontal Scroll ──────────────────────────── */
    function initCategoryScroll() {
        const track = document.querySelector('.category-scroll-track');
        if (!track) return;

        let isDown = false;
        let startX, scrollLeft;

        track.addEventListener('mousedown', (e) => {
            isDown = true;
            track.classList.add('category-scroll-grabbing');
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
        });

        track.addEventListener('mouseleave', () => {
            isDown = false;
            track.classList.remove('category-scroll-grabbing');
        });
        track.addEventListener('mouseup', () => {
            isDown = false;
            track.classList.remove('category-scroll-grabbing');
        });

        track.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            const walk = (x - startX) * 1.5;
            track.scrollLeft = scrollLeft - walk;
        });
    }

    /* ── Scroll Progress Bar (top of page) ───────────────────── */
    function initProgressBar() {
        const bar = document.getElementById('scrollProgressBar');
        if (!bar) return;

        function update() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            bar.style.width = progress + '%';
        }

        window.addEventListener('scroll', update, { passive: true });
        update();
    }

    /* ── Init ──────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => {
        // Scroll reveals
        document.querySelectorAll('[data-scroll="reveal"]').forEach((el) => {
            if (prefersReducedMotion) {
                el.classList.add('scroll-visible');
            } else {
                revealObserver.observe(el);
            }
        });

        // Stagger groups
        document.querySelectorAll('[data-scroll="stagger"]').forEach((el) => {
            if (prefersReducedMotion) {
                el.classList.add('scroll-visible');
                el.querySelectorAll(el.dataset.staggerChild || ':scope > *').forEach(c => c.classList.add('scroll-visible'));
            } else {
                staggerObserver.observe(el);
            }
        });

        // Parallax
        if (!prefersReducedMotion) {
            document.querySelectorAll('[data-scroll="parallax"]').forEach((el) => {
                parallaxElements.push({
                    el,
                    speed: parseFloat(el.dataset.parallaxSpeed || '0.15'),
                });
            });
            if (parallaxElements.length > 0) requestAnimationFrame(tickParallax);
        }

        // Counters
        document.querySelectorAll('[data-scroll="counter"]').forEach((el) => {
            if (prefersReducedMotion) {
                // Just show the final number instantly
                return;
            }
            counterObserver.observe(el);
        });

        // Modules
        initProductReveal();
        initEcosystem();
        initCategoryScroll();
        initProgressBar();
    });
})();
