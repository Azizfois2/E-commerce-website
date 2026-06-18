
    <!-- AI Assistant -->
    <div id="ai-terminal" class="ai-terminal hidden">
        <div class="ai-header">
            <span><i class="fas fa-wand-magic-sparkles"></i> MAROCPC_ASSISTANT v2.0</span>
            <button id="close-ai" aria-label="Close AI assistant">&times;</button>
        </div>
        <div class="ai-messages" id="ai-messages">
            <div class="bot-msg">System initialized. I can help with parts, full builds, laptop finder, orders, returns, and warranty.</div>
        </div>
        <div class="ai-input-area">
            <textarea id="ai-input" placeholder="Query hardware specs, orders, returns, or builds..." rows="3"></textarea>
        </div>
    </div>

    <button id="open-ai" class="ai-trigger" aria-label="Open AI assistant">
        <i class="fas fa-wand-magic-sparkles"></i>
    </button>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/212618821949?text=Hello%20Maroc%20PC!%20I%20need%20help%20with..." 
       class="whatsapp-float" 
       target="_blank" 
       rel="noopener noreferrer"
       aria-label="Contact us on WhatsApp"
       title="Chat with us on WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
    
    <style>
        .whatsapp-float {
            position: fixed;
            /* desktop: above ai-trigger (bottom:30px + height:50px + gap:10px = 90px) */
            bottom: 90px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: #25D366;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            z-index: 999;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }
        .whatsapp-float i {
            animation: waPulse 2s infinite;
        }
        
        /* Light mode styling - industrial aesthetic */
        [data-theme="light"] .whatsapp-float {
            background: #25D366;
            box-shadow: 
                0 4px 12px rgba(37, 211, 102, 0.25),
                0 2px 4px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(37, 211, 102, 0.3);
        }
        [data-theme="light"] .whatsapp-float:hover {
            box-shadow: 
                0 6px 20px rgba(37, 211, 102, 0.35),
                0 4px 8px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            border-color: rgba(37, 211, 102, 0.5);
        }
        
        @keyframes waPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        /* ≤480px — matches index.css mobile stack:
           scroll-to-top : bottom 20px, height 44px
           ai-trigger    : bottom 74px, height 44px
           whatsapp      : bottom 128px (74 + 44 + 10)
        */
        @media (max-width: 480px) {
            .whatsapp-float {
                bottom: 128px;
                right: 20px;
                width: 44px;
                height: 44px;
                font-size: 22px;
            }
        }
        /* 481–768px — slightly larger, same clear stacking */
        @media (min-width: 481px) and (max-width: 768px) {
            .whatsapp-float {
                bottom: 140px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
        }
        @media (max-width: 768px) {
            body.has-mobile-action-dock .whatsapp-float {
                bottom: calc(var(--mobile-dock-clearance, 100px) + 112px);
                right: 18px;
                z-index: 1901;
            }
        }
    </style>

    <script src="assets/js/app.js?v=builder-ai-copilot-1"></script>
    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/auth-nav.js"></script>
<script src="assets/js/typing.js"></script>
    <script>
        // Legacy fallback: auth-nav.js now owns the guest account popover.
        const legacyAccountLink = document.querySelector('a[aria-label="Account"]');
        if (legacyAccountLink) {
            legacyAccountLink.addEventListener('click', function (e) {
                const href = legacyAccountLink.getAttribute('href') || '';
                if (href.indexOf('login') === -1) return;
                e.preventDefault();
                const modal = document.getElementById('roleModal');
                if (modal) modal.style.display = 'flex';
            });
        }

        function selectRole(role) {
            closeRoleModal();

            if (role === 'user') {
                window.location.href = 'login.php';
            } else if (role === 'administrator') {
                window.location.href = 'adminlogin.php';
            }
        }

        // ── Search Redirection Logic ─────────────────────────────────
        const desktopSearchForm = document.querySelector('.search-box');
        if (desktopSearchForm) {
            desktopSearchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = desktopSearchForm.querySelector('input');
                if (input && input.value.trim()) {
                    window.location.href = `products.php?search=${encodeURIComponent(input.value.trim())}`;
                }
            });
        }

        function closeRoleModal() {
            const modal = document.getElementById('roleModal');
            if (modal) modal.style.display = 'none';
        }

        // Fermer en cliquant sur l'overlay
        const roleModal = document.getElementById('roleModal');
        if (roleModal) {
            roleModal.addEventListener('click', function (e) {
                if (e.target === this) closeRoleModal();
            });
        }

        // ── Newsletter subscription handler ──────────────────────
        (function () {
            const form = document.getElementById('newsletterForm');
            if (!form) return;
            const input = form.querySelector('input[type="email"]');
            const btn = form.querySelector('button');

            btn.addEventListener('click', async () => {
                const email = input.value.trim();
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showToast('Please enter a valid email address.', 'error');
                    input.focus();
                    return;
                }

                // Loading state
                const origText = btn.textContent;
                btn.textContent = 'Subscribing...';
                btn.disabled = true;
                btn.style.opacity = '0.7';

                try {
                    const res = await fetch('api/subscribe.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email })
                    });
                    const data = await res.json();

                    if (data.success) {
                        showToast(data.message || 'Subscribed successfully!', 'success');
                        input.value = '';
                    } else {
                        showToast(data.message || 'Subscription failed.', 'error');
                    }
                } catch (err) {
                    showToast('Network error. Please try again.', 'error');
                } finally {
                    btn.textContent = origText;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            });

            // Also submit on Enter key
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btn.click();
                }
            });

            // Toast helper (uses existing toast element)
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                const toastMsg = document.getElementById('toastMessage');
                const icon = toast.querySelector('i');
                if (toast && toastMsg) {
                    toastMsg.textContent = message;
                    if (icon) {
                        icon.className = type === 'success'
                            ? 'fas fa-check-circle'
                            : 'fas fa-exclamation-circle';
                    }
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 4000);
                }
            }
        })();

        // ── Feedback Form Submission ──────────────────────────────
        document.getElementById('feedbackForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = document.getElementById('submitFeedbackBtn');
            const statusDiv = document.getElementById('feedbackStatus');
            
            // Get form data
            const formData = {
                name: form.name.value.trim(),
                email: form.email.value.trim(),
                type: form.type.value,
                rating: form.rating.value,
                message: form.message.value.trim()
            };
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Submitting...';
            statusDiv.className = 'feedback-status';
            statusDiv.textContent = '';
            
            try {
                const response = await fetch('api/submit-feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const rawResponse = await response.text();
                let data;
                try {
                    data = rawResponse ? JSON.parse(rawResponse) : {};
                } catch (parseError) {
                    throw new Error('The feedback service returned an invalid response. Please try again later.');
                }
                
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to submit feedback');
                }

                if (data.success) {
                    statusDiv.className = 'feedback-status success';
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    form.reset();
                    
                    // Show toast notification
                    if (typeof showToast === 'function') {
                        showToast('Thank you for your feedback!', 'success');
                    }
                } else {
                    throw new Error(data.error || 'Failed to submit feedback');
                }
            } catch (error) {
                statusDiv.className = 'feedback-status error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + error.message;
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Feedback';
            }
        });

        // ── Theme toggle removed (handled via theme.js) ─────────




        // ── Theme toggle removed (handled via theme.js) ─────────

        // ── Scroll-to-top button visibility ───────────────────────
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('scrollTop');
            btn.style.opacity = window.scrollY > 300 ? '1' : '0';
            btn.style.pointerEvents = window.scrollY > 300 ? 'auto' : 'none';
        });

        document.addEventListener('DOMContentLoaded', function () {
            // ── Skeleton → real products handoff ─────────────────
            // Reveal products grid once JS populates it
            const skeleton = document.getElementById('skeletonGrid');
            const grid = document.getElementById('featuredProducts');

            function revealProducts() {
                if (grid && grid.children.length > 0) {
                    skeleton.classList.add('hidden');
                    grid.classList.remove('hidden');
                } else {
                    setTimeout(revealProducts, 100);
                }
            }
            revealProducts();

            // ── Flip countdown timer (fallback for when no flash sales are active) ──
            // flash-sales.js will override this with real end-date if API has data
            if (!window._flashSalesLoaded) {
                const deadline = new Date();
                deadline.setDate(deadline.getDate() + 2);
                deadline.setHours(deadline.getHours() + 18);
                deadline.setMinutes(deadline.getMinutes() + 45);

                function animateFlipFallback(el) {
                    el.classList.add('flip-animate');
                    setTimeout(() => el.classList.remove('flip-animate'), 400);
                }

                function updateTimerFallback() {
                    const now = new Date();
                    const diff = Math.max(0, deadline - now);

                    const d = Math.floor(diff / 86400000);
                    const h = Math.floor((diff % 86400000) / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);

                    const pad = n => String(n).padStart(2, '0');

                    const dEl = document.getElementById('days');
                    const hEl = document.getElementById('hours');
                    const mEl = document.getElementById('minutes');
                    const sEl = document.getElementById('seconds');

                    if (dEl && dEl.textContent !== pad(d)) { dEl.textContent = pad(d); animateFlipFallback(dEl.parentElement); }
                    if (hEl && hEl.textContent !== pad(h)) { hEl.textContent = pad(h); animateFlipFallback(hEl.parentElement); }
                    if (mEl && mEl.textContent !== pad(m)) { mEl.textContent = pad(m); animateFlipFallback(mEl.parentElement); }
                    if (sEl) { sEl.textContent = pad(s); animateFlipFallback(sEl.parentElement); }
                }

                updateTimerFallback();
                window._fallbackTimer = setInterval(updateTimerFallback, 1000);
            }

        });
    </script>


    <script src="assets/js/price-chart.js"></script>
    <script src="assets/js/prod.js"></script>
    <script src="assets/js/flash-sales.js?v=currency-fix-1"></script>

    <!-- Quick View Modal -->
    <div class="modal" id="quickViewModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close"><i class="fas fa-times"></i></button>
            <div class="modal-body" id="quickViewContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- ============================================================
         KEYBOARD SHORTCUTS OVERLAY
         Press ? to open · Esc to close
    ============================================================ -->
    <div id="kbShortcutsOverlay" role="dialog" aria-modal="true" aria-label="<?php i18n_e('shortcuts.keyboard_shortcuts', [], 'Keyboard shortcuts'); ?>" style="display:none;">
        <div id="kbShortcutsPanel">
            <div class="kb-header">
                <span class="kb-title"><i class="fas fa-keyboard"></i> <?php i18n_e('shortcuts.keyboard_shortcuts', [], 'Keyboard Shortcuts'); ?></span>
                <button id="kbClose" aria-label="<?php i18n_e('shortcuts.close_any_panel', [], 'Close shortcuts panel'); ?>"><i class="fas fa-times"></i></button>
            </div>
            <div class="kb-body">
                <div class="kb-group">
                    <h3 class="kb-group-label"><?php i18n_e('shortcuts.navigation', [], 'Navigation'); ?></h3>
                    <ul class="kb-list">
                        <li><kbd>G</kbd> <kbd>H</kbd> <span><?php i18n_e('shortcuts.go_to_home', [], 'Go to Home (top)'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>P</kbd> <span><?php i18n_e('shortcuts.go_to_products', [], 'Go to Products page'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>C</kbd> <span><?php i18n_e('shortcuts.jump_to_categories', [], 'Jump to Categories'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>D</kbd> <span><?php i18n_e('shortcuts.jump_to_deals', [], 'Jump to Deals'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>F</kbd> <span><?php i18n_e('shortcuts.jump_to_feedback', [], 'Jump to Feedback'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>N</kbd> <span><?php i18n_e('shortcuts.jump_to_newsletter', [], 'Jump to Newsletter'); ?></span></li>
                        <li><kbd>G</kbd> <kbd>E</kbd> <span><?php i18n_e('shortcuts.jump_to_contact', [], 'Jump to Contact / Footer'); ?></span></li>
                    </ul>
                </div>
                <div class="kb-group">
                    <h3 class="kb-group-label"><?php i18n_e('shortcuts.actions', [], 'Actions'); ?></h3>
                    <ul class="kb-list">
                        <li><kbd>/</kbd> <span><?php i18n_e('shortcuts.focus_search_bar', [], 'Focus search bar'); ?></span></li>
                        <li><kbd>B</kbd> <span><?php i18n_e('shortcuts.open_pc_builder', [], 'Open PC Builder'); ?></span></li>
                        <li><kbd>T</kbd> <span><?php i18n_e('shortcuts.open_tools_cockpit', [], 'Open Tools Cockpit'); ?></span></li>
                        <li><kbd>A</kbd> <span><?php i18n_e('shortcuts.toggle_ai_assistant', [], 'Toggle AI assistant'); ?></span></li>
                        <li><kbd>K</kbd> <span><?php i18n_e('shortcuts.toggle_theme', [], 'Toggle dark / light theme'); ?></span></li>
                        <li><kbd>↑</kbd> <span><?php i18n_e('shortcuts.scroll_to_top', [], 'Scroll to top'); ?></span></li>
                        <li><kbd>M</kbd> <span><?php i18n_e('shortcuts.open_mobile_menu', [], 'Open mobile menu'); ?></span></li>
                    </ul>
                </div>
                <div class="kb-group">
                    <h3 class="kb-group-label"><?php i18n_e('shortcuts.general', [], 'General'); ?></h3>
                    <ul class="kb-list">
                        <li><kbd>?</kbd> <span><?php i18n_e('shortcuts.show_hide_panel', [], 'Show / hide this panel'); ?></span></li>
                        <li><kbd>Esc</kbd> <span><?php i18n_e('shortcuts.close_any_panel', [], 'Close any open panel'); ?></span></li>
                    </ul>
                </div>
            </div>
            <p class="kb-footer-note"><?php i18n_e('shortcuts.disabled_while_typing', [], 'Shortcuts are disabled while typing in any input field.'); ?></p>
        </div>
    </div>

    <!-- Keyboard shortcut hint badge (bottom-left) -->
    <button id="kbHintBadge" aria-label="<?php i18n_e('shortcuts.show_keyboard_shortcuts', [], 'Show keyboard shortcuts'); ?>" title="<?php i18n_e('shortcuts.keyboard_shortcuts', [], 'Keyboard shortcuts'); ?> (?)">
        <i class="fas fa-keyboard"></i>
        <span>?</span>
    </button>

    <style>
        /* ── Overlay backdrop ── */
        #kbShortcutsOverlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(6px);
            z-index: 10000;
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: kbFadeIn 0.2s ease;
        }
        #kbShortcutsOverlay[style*="display:none"],
        #kbShortcutsOverlay[style*="display: none"] {
            display: none !important;
        }
        @keyframes kbFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── Panel ── */
        #kbShortcutsPanel {
            background: var(--page-bg-2, #0f1117);
            border: 1px solid var(--border, rgba(0,245,212,0.18));
            border-radius: 16px;
            width: min(680px, 100%);
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.6);
            animation: kbSlideUp 0.25s cubic-bezier(0.34,1.56,0.64,1);
            scrollbar-width: thin;
            scrollbar-color: var(--border, rgba(0,245,212,0.18)) transparent;
        }
        #kbShortcutsPanel::-webkit-scrollbar { width: 4px; }
        #kbShortcutsPanel::-webkit-scrollbar-thumb {
            background: var(--border, rgba(0,245,212,0.18));
            border-radius: 2px;
        }
        @keyframes kbSlideUp {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* ── Header ── */
        .kb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border, rgba(0,245,212,0.12));
            position: sticky;
            top: 0;
            background: var(--page-bg-2, #0f1117);
            z-index: 1;
        }
        .kb-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--cyan, #00f5d4);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #kbClose {
            width: 34px;
            height: 34px;
            background: var(--page-bg, #080b10);
            border: 1px solid var(--border, rgba(0,245,212,0.18));
            border-radius: 8px;
            color: var(--muted, #9aa5b5);
            cursor: pointer;
            display: grid;
            place-items: center;
            font-size: 14px;
            transition: all 0.2s;
        }
        #kbClose:hover {
            background: var(--cyan, #00f5d4);
            color: #000;
            border-color: var(--cyan, #00f5d4);
        }

        /* ── Body ── */
        .kb-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0;
            padding: 8px 0;
        }
        .kb-group {
            padding: 16px 24px;
        }
        .kb-group + .kb-group {
            border-left: 1px solid var(--border, rgba(0,245,212,0.08));
        }
        .kb-group-label {
            font-family: 'Space Mono', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--cyan, #00f5d4);
            margin: 0 0 14px;
        }
        .kb-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .kb-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--muted, #9aa5b5);
        }
        .kb-list li span {
            color: var(--white, #eef0f4);
        }

        /* ── kbd keys ── */
        kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 24px;
            padding: 0 6px;
            background: var(--page-bg, #080b10);
            border: 1px solid var(--border, rgba(0,245,212,0.25));
            border-bottom-width: 2px;
            border-radius: 5px;
            font-family: 'Space Mono', monospace;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--cyan, #00f5d4);
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ── Footer note ── */
        .kb-footer-note {
            text-align: center;
            font-size: 0.75rem;
            color: var(--muted, #9aa5b5);
            padding: 12px 24px 18px;
            border-top: 1px solid var(--border, rgba(0,245,212,0.08));
            margin: 0;
        }

        /* ── Hint badge (bottom-left) ── */
        #kbHintBadge {
            position: fixed;
            bottom: 30px;
            left: 24px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 12px;
            height: 36px;
            background: var(--page-bg-2, #0f1117);
            border: 1px solid var(--border, rgba(0,245,212,0.2));
            border-radius: 20px;
            color: var(--muted, #9aa5b5);
            font-family: 'Space Mono', monospace;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            z-index: 997;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        #kbHintBadge:hover {
            border-color: var(--cyan, #00f5d4);
            color: var(--cyan, #00f5d4);
            box-shadow: 0 4px 16px rgba(0,245,212,0.15);
        }
        #kbHintBadge i { font-size: 13px; }

        /* ── Light mode overrides ── */
        [data-theme="light"] #kbShortcutsPanel {
            background: #f8fafc;
            border-color: rgba(0,122,110,0.2);
        }
        [data-theme="light"] .kb-header {
            background: #f8fafc;
            border-color: rgba(0,122,110,0.12);
        }
        [data-theme="light"] #kbClose {
            background: #eef2f6;
            border-color: rgba(0,122,110,0.2);
            color: #526174;
        }
        [data-theme="light"] #kbClose:hover {
            background: var(--cyan, #007a6e);
            color: #fff;
        }
        [data-theme="light"] kbd {
            background: #eef2f6;
            border-color: rgba(0,122,110,0.3);
            color: var(--cyan, #007a6e);
        }
        [data-theme="light"] #kbHintBadge {
            background: #f8fafc;
            border-color: rgba(0,122,110,0.2);
            color: #526174;
        }
        [data-theme="light"] #kbHintBadge:hover {
            border-color: var(--cyan, #007a6e);
            color: var(--cyan, #007a6e);
        }

        /* ── Mobile ── */
        @media (max-width: 600px) {
            #kbShortcutsOverlay,
            #kbHintBadge {
                display: none !important;
            }
            .kb-body { grid-template-columns: 1fr; }
            .kb-group + .kb-group { border-left: none; border-top: 1px solid var(--border, rgba(0,245,212,0.08)); }
        }
    </style>

    <script>
    // ============================================================
    // KEYBOARD SHORTCUTS & NAVIGATION — Maroc PC index.php
    // ============================================================
    (function () {
        'use strict';

        // ── helpers ──────────────────────────────────────────────
        function isTyping() {
            const el = document.activeElement;
            if (!el) return false;
            const tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
        }

        function isMobileShortcutViewport() {
            return window.matchMedia('(max-width: 600px), (hover: none) and (pointer: coarse)').matches;
        }

        function scrollTo(id) {
            const el = document.getElementById(id);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function navigate(url) {
            window.location.href = url;
        }

        // ── overlay open / close ──────────────────────────────────
        const overlay = document.getElementById('kbShortcutsOverlay');

        function openShortcuts() {
            if (isMobileShortcutViewport()) return;
            overlay.style.display = '';   // removes inline display:none
            overlay.removeAttribute('style');
            document.getElementById('kbClose').focus();
            document.body.style.overflow = 'hidden';
        }

        function closeShortcuts() {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('kbClose').addEventListener('click', closeShortcuts);
        document.getElementById('kbHintBadge').addEventListener('click', openShortcuts);

        // Close on backdrop click
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeShortcuts();
        });

        // ── "G + key" chord state ─────────────────────────────────
        let gPressed = false;
        let gTimer   = null;

        function resetG() {
            gPressed = false;
            clearTimeout(gTimer);
        }

        // ── main keydown handler ──────────────────────────────────
        document.addEventListener('keydown', function (e) {
            if (isMobileShortcutViewport()) return;

            // Always allow Escape
            if (e.key === 'Escape') {
                if (overlay.style.display !== 'none') { closeShortcuts(); return; }
                // close sidebar if open
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                if (sidebar && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    sidebarOverlay && sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                return;
            }

            // Block all other shortcuts while typing
            if (isTyping()) return;

            // Block if modifier keys are held (except Shift for ?)
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            const key = e.key;

            // ── G-chord navigation ────────────────────────────────
            if (gPressed) {
                resetG();
                switch (key.toLowerCase()) {
                    case 'h': e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); return;
                    case 'p': e.preventDefault(); navigate('products.php'); return;
                    case 'c': e.preventDefault(); scrollTo('categories'); return;
                    case 'd': e.preventDefault(); scrollTo('deals'); return;
                    case 'f': e.preventDefault(); scrollTo('feedback'); return;
                    case 'n': e.preventDefault(); scrollTo('newsletterForm'); return;
                    case 'e': e.preventDefault(); scrollTo('contact'); return;
                }
                return;
            }

            // ── Single-key shortcuts ──────────────────────────────
            switch (key) {
                // Start G-chord
                case 'g':
                case 'G':
                    e.preventDefault();
                    gPressed = true;
                    gTimer = setTimeout(resetG, 1500); // 1.5 s window
                    return;

                // Focus search
                case '/':
                    e.preventDefault();
                    const searchInput = document.querySelector('.search-input');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                    return;

                // Open PC Builder
                case 'b':
                case 'B':
                    e.preventDefault();
                    navigate('builder.php');
                    return;

                // Open Tools Cockpit
                case 't':
                case 'T':
                    e.preventDefault();
                    navigate('tools.php');
                    return;

                // Toggle AI assistant
                case 'a':
                case 'A':
                    e.preventDefault();
                    const aiBtn = document.getElementById('open-ai');
                    if (aiBtn) aiBtn.click();
                    return;

                // Toggle theme
                case 'k':
                case 'K':
                    e.preventDefault();
                    const themeBtn = document.getElementById('themeToggle');
                    if (themeBtn) themeBtn.click();
                    return;

                // Scroll to top
                case 'ArrowUp':
                    if (e.shiftKey) {
                        e.preventDefault();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                    return;

                // Open mobile menu
                case 'm':
                case 'M':
                    e.preventDefault();
                    const hamburger = document.getElementById('hamburgerBtn');
                    if (hamburger) hamburger.click();
                    return;

                // Show shortcuts panel
                case '?':
                    e.preventDefault();
                    overlay.style.display === 'none' ? openShortcuts() : closeShortcuts();
                    return;
            }
        });

        // ── trap focus inside overlay when open ──────────────────
        overlay.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            const focusable = overlay.querySelectorAll('button, [href], input, [tabindex]:not([tabindex="-1"])');
            const first = focusable[0];
            const last  = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
            }
        });

    })();
    </script>
