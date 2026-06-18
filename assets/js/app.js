console.log("App started");
const API_URL = 'ai-handler.php';

document.addEventListener('DOMContentLoaded', () => {
    console.log("Initializing application...");

    const aiTerminal       = document.getElementById('ai-terminal');
    const openBtn          = document.getElementById('open-ai');
    const closeBtn         = document.getElementById('close-ai');
    const aiInput          = document.getElementById('ai-input');
    const messageContainer = document.getElementById('ai-messages');
    const quickActions     = document.querySelectorAll('[data-ai-prompt]');
    const isBuilderPage    = () => typeof PCBuilder !== 'undefined' && typeof PCBuilder.getAssistantContext === 'function';

    if (!aiTerminal || !openBtn || !closeBtn || !aiInput || !messageContainer) {
        console.error("AI Terminal: One or more elements not found in DOM.");
        return;
    }

    const inputArea = aiInput.closest('.ai-input-area');
    let sendBtn = document.getElementById('send-ai');
    if (inputArea && !sendBtn) {
        sendBtn = document.createElement('button');
        sendBtn.id = 'send-ai';
        sendBtn.type = 'button';
        sendBtn.className = 'ai-send-btn';
        sendBtn.setAttribute('aria-label', 'Send AI message');
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        inputArea.appendChild(sendBtn);
    }

    // Dynamic Reset & Language Selector Injection in Chatbot Header
    const header = aiTerminal.querySelector('.ai-header');
    if (header) {
        // Enforce compact, non-wrapping style on header title
        const titleSpan = header.querySelector('span');
        if (titleSpan) {
            titleSpan.setAttribute('style', 'font-size: 0.78rem; white-space: nowrap; display: flex; align-items: center; gap: 6px; flex-shrink: 0;');
        }

        const resetBtn = document.createElement('button');
        resetBtn.id = 'reset-ai';
        resetBtn.className = 'ai-header-btn ai-reset-btn';
        resetBtn.title = 'Reset Chat';
        resetBtn.setAttribute('aria-label', 'Reset chat');
        resetBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        resetBtn.setAttribute('style', 'margin-right: 12px; background: none; border: none; color: #000; opacity: 0.7; cursor: pointer; font-size: 0.95em; transition: opacity 0.2s;');
        
        resetBtn.onmouseover = () => { resetBtn.style.opacity = '1'; };
        resetBtn.onmouseout = () => { resetBtn.style.opacity = '0.7'; };
        
        // Insert reset button before close button
        header.insertBefore(resetBtn, closeBtn);

        // Create language selector container
        const langContainer = document.createElement('div');
        langContainer.className = 'ai-language-wrap';
        langContainer.setAttribute('style', 'margin-left: auto; margin-right: 12px; display: flex; align-items: center;');

        const langSelect = document.createElement('select');
        langSelect.id = 'ai-lang-select';
        langSelect.innerHTML = `
            <option value="auto" style="background: #111318; color: #e0e6ed;">Auto</option>
            <option value="english" style="background: #111318; color: #e0e6ed;">EN English</option>
            <option value="french" style="background: #111318; color: #e0e6ed;">FR Français</option>
            <option value="arabic" style="background: #111318; color: #e0e6ed;">AR \u0627\u0644\u0639\u0631\u0628\u064a\u0629</option>
        `;
        langSelect.className = 'ai-language-select';
        langSelect.onmouseover = () => { langSelect.style.background = 'rgba(0,0,0,0.12)'; };
        langSelect.onmouseout = () => { langSelect.style.background = 'rgba(0,0,0,0.06)'; };
        
        const pageLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
        const pageChatLang = pageLang.startsWith('ar')
            ? 'arabic'
            : pageLang.startsWith('fr')
                ? 'french'
                : pageLang.startsWith('es')
                    ? 'english'
                    : 'english';
        const storedLang = localStorage.getItem('ai_chat_language');
        const savedLang = storedLang && storedLang !== 'auto' ? storedLang : pageChatLang;
        if (savedLang === 'darija') {
            localStorage.setItem('ai_chat_language', 'arabic');
        }
        langSelect.value = savedLang === 'darija' ? 'arabic' : savedLang;
        langSelect.value = normalizeChatLanguage(langSelect.value);
        localStorage.setItem('ai_chat_language', langSelect.value);

        langSelect.onchange = () => {
            localStorage.setItem('ai_chat_language', langSelect.value);
            if (typeof showToast !== 'undefined') {
                showToast(`Chatbot language set to: ${langSelect.options[langSelect.selectedIndex].text}`, 'success');
            }
            updateChatLanguageUI();
            resetChatMessages();
        };

        langContainer.appendChild(langSelect);
        // Insert language selector before reset button to maintain correct order
        header.insertBefore(langContainer, resetBtn);
        
        resetBtn.onclick = () => {
            resetChatMessages();
        };
    }

    // Render language-correct welcome + guided questions on startup
    updateChatLanguageUI();
    resetChatMessages();

    openBtn.addEventListener('click', () => {
        aiTerminal.classList.toggle('hidden');
        aiInput.focus();
    });

    closeBtn.addEventListener('click', () => {
        aiTerminal.classList.add('hidden');
    });

    quickActions.forEach(btn => {
        btn.addEventListener('click', () => {
            aiInput.value = btn.dataset.aiPrompt || '';
            sendCurrentPrompt();
        });
    });

    if (sendBtn) {
        sendBtn.addEventListener('click', () => {
            sendCurrentPrompt();
        });
    }

    aiInput.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter' && !e.shiftKey && aiInput.value.trim() !== "") {
            e.preventDefault();
            sendCurrentPrompt();
        }
    });

    async function sendCurrentPrompt() {
        if (aiInput.value.trim() === "") return;
        const userText = aiInput.value.trim();
        aiInput.value = '';
        aiInput.disabled = true;
        if (sendBtn) sendBtn.disabled = true;

        addMessage(userText, 'user-msg');

        const localBuilderAnswer = getLocalBuilderAnswer(userText);
        if (localBuilderAnswer) {
            const loadingMsg = addTypingIndicator();
            await sleep(350);
            loadingMsg.remove();
            const msgEl = addMessage(localBuilderAnswer, 'bot-msg');
            
            // Local responses don't submit SQL feedback, but we still add feedback triggers
            addFeedbackActions(msgEl, userText, localBuilderAnswer);
            
            aiInput.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            aiInput.focus();
            return;
        }

        // Show animated typing indicator
        const loadingMsg = addTypingIndicator();

        const data = await getAIResponse(userText);

        // Honour the delay hint from the server (simulates typing pace)
        const delay = data.delay_ms ?? 600;
        await sleep(delay);

        loadingMsg.remove();
        const msgEl = addMessage(data.response ?? "Sorry, I didn't get a response. Try again!", 'bot-msg');

        // Add dynamic feedback actions (Likes/Dislikes)
        addFeedbackActions(msgEl, userText, data.response ?? "");

        if (data.is_build && data.products && data.products.length > 0) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-primary ai-result-action';
            btn.innerHTML = isBuilderPage()
                ? '<i class="fas fa-wand-magic-sparkles"></i> Apply Picks to Builder'
                : `<i class="fas fa-cart-plus"></i> ${window.__marocPcI18n?.addComboToCart || 'Add Combo to Cart'}`;
            btn.dataset.products = JSON.stringify(data.products);
            btn.onclick = function() {
                if (isBuilderPage() && typeof PCBuilder.applyAssistantProducts === 'function') {
                    PCBuilder.applyAssistantProducts(JSON.parse(this.dataset.products));
                    this.innerHTML = '<i class="fas fa-check"></i> Applied to Builder';
                    this.disabled = true;
                    return;
                }
                window.aiAddAllToCart(this);
            };
            msgEl.appendChild(btn);
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }

        aiInput.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        aiInput.focus();
    }

    // ----------------------------------------
    // Helpers
    // ----------------------------------------

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function formatMADLocal(value) {
        if (typeof window.formatMADLocal === 'function') {
            return window.formatMADLocal(value);
        }
        if (typeof window.formatMAD === 'function') {
            return window.formatMAD(value);
        }
        const lang = (document.documentElement.lang || 'en').slice(0, 2);
        const currency = lang === 'ar' ? 'د.م.' : 'DH';
        return Number(value || 0).toLocaleString('en', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' ' + currency;
    }

    function normalizeChatLanguage(lang) {
        const normalized = String(lang || 'auto').toLowerCase();
        if (normalized === 'darija' || normalized === 'ar' || normalized === 'arabic-ma') {
            return 'arabic';
        }
        if (normalized === 'fr') return 'french';
        if (normalized === 'en') return 'english';
        return ['auto', 'english', 'french', 'arabic'].includes(normalized) ? normalized : 'auto';
    }

    function getSelectedChatLanguage() {
        const langSelect = document.getElementById('ai-lang-select');
        return normalizeChatLanguage(langSelect ? langSelect.value : 'auto');
    }

    function repairMojibake(text) {
        if (typeof text !== 'string' || !/[ÃÂØÙðâ]/.test(text)) return text;

        const win1252 = {
            8364: 0x80, 8218: 0x82, 402: 0x83, 8222: 0x84, 8230: 0x85,
            8224: 0x86, 8225: 0x87, 710: 0x88, 8240: 0x89, 352: 0x8A,
            8249: 0x8B, 338: 0x8C, 381: 0x8E, 8216: 0x91, 8217: 0x92,
            8220: 0x93, 8221: 0x94, 8226: 0x95, 8211: 0x96, 8212: 0x97,
            732: 0x98, 8482: 0x99, 353: 0x9A, 8250: 0x9B, 339: 0x9C,
            382: 0x9E, 376: 0x9F
        };

        const score = (value) => (value.match(/[ÃÂØÙðâ�]/g) || []).length;
        let best = text;
        let bestScore = score(best);

        for (let pass = 0; pass < 2; pass += 1) {
            try {
                const bytes = Uint8Array.from(Array.from(best, (char) => {
                    const code = char.charCodeAt(0);
                    return win1252[code] ?? (code <= 255 ? code : 63);
                }));
                const decoded = new TextDecoder('utf-8', { fatal: true }).decode(bytes);
                const decodedScore = score(decoded);
                if (decodedScore >= bestScore) break;
                best = decoded;
                bestScore = decodedScore;
            } catch (error) {
                break;
            }
        }

        return best;
    }

    function containsArabic(text) {
        return /[\u0600-\u06FF]/.test(text);
    }

    function getPageChatLanguage() {
        const pageLang = (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
        if (pageLang.startsWith('ar')) return 'arabic';
        if (pageLang.startsWith('fr')) return 'french';
        return 'english';
    }

    function localizeRuntimePrompt(prompt) {
        const lang = getSelectedChatLanguage();
        const currency = lang === 'arabic' ? 'د.م.' : 'DH';
        return String(prompt || '').replace(/\{currency\}/g, currency);
    }

    function getPageBuilderAiCopy() {
        const pageCopy = window.__marocPcI18n?.aiBuilder;
        if (!isBuilderPage() || !pageCopy || getSelectedChatLanguage() !== getPageChatLanguage()) {
            return null;
        }

        return {
            header: pageCopy.title || 'Builder Copilot',
            welcome: pageCopy.welcome || '',
            placeholder: pageCopy.placeholder || 'Ask about this build...',
            items: Array.isArray(pageCopy.quickActions)
                ? pageCopy.quickActions.map(item => ({
                    prompt: localizeRuntimePrompt(item.prompt),
                    label: item.label,
                    icon: item.icon
                }))
                : []
        };
    }

    function updateChatLanguageUI() {
        const lang = getSelectedChatLanguage();
        const placeholders = {
            auto: 'Query hardware specs...',
            english: 'Query hardware specs...',
            french: 'Posez une question sur le materiel...',
            arabic: 'اسأل عن التجميعة أو التوافق أو الميزانية...'
        };

        aiInput.placeholder = placeholders[lang] || placeholders.auto;
        aiInput.setAttribute('dir', lang === 'arabic' ? 'rtl' : 'auto');
        aiTerminal.classList.toggle('ai-rtl', lang === 'arabic');
        applyBuilderQuickActionsLocalization();
    }

    function builderUiLabels() {
        const lang = getSelectedChatLanguage();
        const labels = {
            english: {
                liveContext: 'Live build context',
                selected: 'selected',
                total: 'total',
                draw: 'draw',
                psu: 'PSU',
                missing: 'Missing',
                none: 'none',
                partNames: {
                    CPU: 'CPU',
                    MOTHERBOARD: 'Motherboard',
                    GPU: 'GPU',
                    RAM: 'RAM',
                    STORAGE: 'Storage',
                    PSU: 'PSU',
                    CASE: 'Case',
                    COOLING: 'Cooling',
                    MONITOR: 'Monitor',
                    ACCESSORIES: 'Accessories'
                }
            },
            french: {
                liveContext: 'Contexte du build',
                selected: 'selectionnees',
                total: 'total',
                draw: 'conso.',
                psu: 'alim.',
                missing: 'Manquant',
                none: 'aucun',
                partNames: {
                    CPU: 'Processeur',
                    MOTHERBOARD: 'Carte mere',
                    GPU: 'Carte graphique',
                    RAM: 'Memoire',
                    STORAGE: 'Stockage',
                    PSU: 'Alimentation',
                    CASE: 'Boitier',
                    COOLING: 'Refroidissement',
                    MONITOR: 'Ecran',
                    ACCESSORIES: 'Accessoires'
                }
            },
            arabic: {
                liveContext: 'حالة التجميعة الحالية',
                selected: 'مختارة',
                total: 'المجموع',
                draw: 'الاستهلاك',
                psu: 'مزود الطاقة',
                missing: 'الناقص',
                none: 'لا شيء',
                partNames: {
                    CPU: 'المعالج',
                    MOTHERBOARD: 'اللوحة الأم',
                    GPU: 'البطاقة الرسومية',
                    RAM: 'الذاكرة',
                    STORAGE: 'التخزين',
                    PSU: 'مزود الطاقة',
                    CASE: 'الصندوق',
                    COOLING: 'التبريد',
                    MONITOR: 'الشاشة',
                    ACCESSORIES: 'الإكسسوارات'
                }
            }
        };
        const selected = labels[lang] || labels.english;
        const pageCopy = window.__marocPcI18n?.aiBuilder;
        if (isBuilderPage() && pageCopy && lang === getPageChatLanguage()) {
            return {
                ...selected,
                liveContext: pageCopy.liveContext || selected.liveContext,
                selected: pageCopy.selected || selected.selected,
                total: pageCopy.total || selected.total,
                draw: pageCopy.draw || selected.draw,
                psu: pageCopy.psu || selected.psu,
                missing: pageCopy.missing || selected.missing,
                none: pageCopy.none || selected.none
            };
        }
        return selected;
    }

    function translateBuilderPartName(name) {
        const labels = builderUiLabels();
        const key = String(name || '').trim().toUpperCase();
        return labels.partNames[key] || name;
    }

    function applyBuilderQuickActionsLocalization() {
        if (!isBuilderPage()) return;
        const actions = document.querySelectorAll('.ai-quick-actions [data-ai-prompt]');
        if (!actions.length) return;
        const copy = getAssistantCopy();
        actions.forEach((btn, index) => {
            const item = copy.items[index];
            if (!item) return;
            btn.dataset.aiPrompt = item.prompt;
            const icon = btn.querySelector('i');
            btn.textContent = '';
            if (icon) btn.appendChild(icon);
            btn.appendChild(document.createTextNode(item.label));
            btn.setAttribute('dir', getSelectedChatLanguage() === 'arabic' ? 'rtl' : 'auto');
        });
    }

    function addMessage(text, className) {
        text = repairMojibake(String(text ?? ''));
        const msg = document.createElement('div');
        msg.className = className;
        // Support basic **bold** markdown in bot messages
        if (className === 'bot-msg') {
            msg.classList.add('notranslate');
            msg.setAttribute('translate', 'no');
            msg.setAttribute('dir', containsArabic(text) ? 'rtl' : 'auto');
            msg.innerHTML = markdownLite(text);
        } else {
            msg.setAttribute('dir', containsArabic(text) ? 'rtl' : 'auto');
            msg.textContent = text;
        }
        messageContainer.appendChild(msg);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        return msg;
    }

    function getAssistantCopy() {
        const lang = getSelectedChatLanguage();
        const pageBuilderCopy = getPageBuilderAiCopy();
        if (pageBuilderCopy) return pageBuilderCopy;

        const builder = {
            english: {
                header: 'Builder Copilot',
                welcome: 'Build assistant ready. I can inspect your current parts, plan the next slot, sanity-check wattage, and propose budget-aware upgrades.',
                placeholder: 'Ask about this build, compatibility, budget, or upgrades...',
                items: [
                    { prompt: 'Analyze my current build and tell me the next best action.', label: 'Analyze build', icon: 'fa-stethoscope' },
                    { prompt: 'What should I choose next for this build?', label: 'Next part', icon: 'fa-forward-step' },
                    { prompt: 'Check compatibility, wattage, cooling, and missing parts.', label: 'Full check', icon: 'fa-shield-halved' },
                    { prompt: 'Optimize this build for my budget without wasting money.', label: 'Optimize budget', icon: 'fa-scale-balanced' },
                    { prompt: 'Recommend a balanced gaming PC build around 18000 DH.', label: 'Gaming build', icon: 'fa-gamepad' },
                    { prompt: 'Which services should I add before checkout?', label: 'Services', icon: 'fa-screwdriver-wrench' }
                ]
            },
            french: {
                header: 'Copilote Builder',
                welcome: 'Assistant de configuration pret. Je peux analyser les composants, proposer la prochaine piece, verifier la puissance et optimiser le budget.',
                placeholder: 'Posez une question sur ce build, la compatibilite ou le budget...',
                items: [
                    { prompt: 'Analyse ma configuration actuelle et donne la prochaine action.', label: 'Analyser', icon: 'fa-stethoscope' },
                    { prompt: 'Quelle piece dois-je choisir ensuite ?', label: 'Prochaine piece', icon: 'fa-forward-step' },
                    { prompt: 'Verifie compatibilite, puissance, refroidissement et pieces manquantes.', label: 'Verification', icon: 'fa-shield-halved' },
                    { prompt: 'Optimise cette configuration selon mon budget.', label: 'Optimiser', icon: 'fa-scale-balanced' },
                    { prompt: 'Recommande un PC gaming equilibre autour de 18000 DH.', label: 'PC gaming', icon: 'fa-gamepad' },
                    { prompt: 'Quels services dois-je ajouter avant achat ?', label: 'Services', icon: 'fa-screwdriver-wrench' }
                ]
            },
            arabic: {
                header: '\u0645\u0633\u0627\u0639\u062f \u0627\u0644\u062a\u062c\u0645\u064a\u0639',
                welcome: '\u0627\u0644\u0645\u0633\u0627\u0639\u062f \u062c\u0627\u0647\u0632. \u064a\u0645\u0643\u0646\u0646\u064a \u0641\u062d\u0635 \u0627\u0644\u0642\u0637\u0639\u060c \u0627\u0642\u062a\u0631\u0627\u062d \u0627\u0644\u062e\u0637\u0648\u0629 \u0627\u0644\u062a\u0627\u0644\u064a\u0629\u060c \u0648\u0645\u0631\u0627\u062c\u0639\u0629 \u0627\u0644\u0637\u0627\u0642\u0629 \u0648\u0627\u0644\u0645\u064a\u0632\u0627\u0646\u064a\u0629.',
                placeholder: '\u0627\u0633\u0623\u0644 \u0639\u0646 \u0627\u0644\u062a\u062c\u0645\u064a\u0639\u0629\u060c \u0627\u0644\u062a\u0648\u0627\u0641\u0642\u060c \u0623\u0648 \u0627\u0644\u0645\u064a\u0632\u0627\u0646\u064a\u0629...',
                items: [
                    { prompt: '\u062d\u0644\u0644 \u062a\u062c\u0645\u064a\u0639\u062a\u064a \u0648\u0623\u062e\u0628\u0631\u0646\u064a \u0628\u0623\u0641\u0636\u0644 \u062e\u0637\u0648\u0629 \u062a\u0627\u0644\u064a\u0629', label: '\u062a\u062d\u0644\u064a\u0644', icon: 'fa-stethoscope' },
                    { prompt: '\u0645\u0627 \u0647\u064a \u0627\u0644\u0642\u0637\u0639\u0629 \u0627\u0644\u062a\u0627\u0644\u064a\u0629 \u0627\u0644\u062a\u064a \u0623\u062e\u062a\u0627\u0631\u0647\u0627\u061f', label: '\u0627\u0644\u062e\u0637\u0648\u0629 \u0627\u0644\u062a\u0627\u0644\u064a\u0629', icon: 'fa-forward-step' },
                    { prompt: '\u0627\u0641\u062d\u0635 \u0627\u0644\u062a\u0648\u0627\u0641\u0642 \u0648\u0627\u0644\u0637\u0627\u0642\u0629 \u0648\u0627\u0644\u062a\u0628\u0631\u064a\u062f', label: '\u0641\u062d\u0635 \u0643\u0627\u0645\u0644', icon: 'fa-shield-halved' },
                    { prompt: '\u062d\u0633\u0646 \u0627\u0644\u062a\u062c\u0645\u064a\u0639\u0629 \u062d\u0633\u0628 \u0627\u0644\u0645\u064a\u0632\u0627\u0646\u064a\u0629', label: '\u062a\u062d\u0633\u064a\u0646', icon: 'fa-scale-balanced' },
                    { prompt: '\u0627\u0642\u062a\u0631\u062d \u062a\u062c\u0645\u064a\u0639\u0629 \u0623\u0644\u0639\u0627\u0628 \u0645\u062a\u0648\u0627\u0632\u0646\u0629 \u062d\u0648\u0644 18000 DH', label: '\u0623\u0644\u0639\u0627\u0628', icon: 'fa-gamepad' },
                    { prompt: '\u0645\u0627 \u0647\u064a \u0627\u0644\u062e\u062f\u0645\u0627\u062a \u0627\u0644\u0645\u0646\u0627\u0633\u0628\u0629 \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639\u061f', label: '\u062e\u062f\u0645\u0627\u062a', icon: 'fa-screwdriver-wrench' }
                ]
            }
        };
        const store = {
            english: {
                header: 'Popular Questions',
                welcome: 'System initialized. I can help with parts, complete builds, laptop finder, order status, returns, and warranty.',
                placeholder: 'Query hardware specs, orders, returns, or builds...',
                items: [
                    { prompt: 'Suggest a balanced gaming PC setup', label: 'Gaming PC Setup', icon: 'fa-desktop' },
                    { prompt: 'Track my order status', label: 'Track Order Status', icon: 'fa-box-open' },
                    { prompt: 'Help with a return or SAV warranty RMA', label: 'Returns & RMA Desk', icon: 'fa-rotate-left' },
                    { prompt: 'Help me find a laptop using Laptop Finder', label: 'Laptop Finder Curate', icon: 'fa-laptop' }
                ]
            },
            french: {
                header: 'Questions populaires',
                welcome: 'Systeme initialise. Je peux aider avec les composants, builds complets, laptop finder, commandes, retours et garantie.',
                placeholder: 'Posez une question sur composants, commandes, retours ou builds...',
                items: [
                    { prompt: 'Suggere une configuration PC gaming equilibree', label: 'PC gaming equilibre', icon: 'fa-desktop' },
                    { prompt: 'Suivre le statut de ma commande', label: 'Suivi commande', icon: 'fa-box-open' },
                    { prompt: 'Aide pour un retour ou une garantie SAV', label: 'Retours et SAV', icon: 'fa-rotate-left' },
                    { prompt: 'Aidez-moi a choisir un ordinateur portable adapte', label: 'Choisir un portable', icon: 'fa-laptop' }
                ]
            },
            arabic: {
                header: '\u0623\u0633\u0626\u0644\u0629 \u0634\u0627\u0626\u0639\u0629',
                welcome: '\u062a\u0645 \u062a\u0634\u063a\u064a\u0644 \u0627\u0644\u0645\u0633\u0627\u0639\u062f. \u064a\u0645\u0643\u0646\u0646\u064a \u0645\u0633\u0627\u0639\u062f\u062a\u0643 \u0641\u064a \u0627\u0644\u0642\u0637\u0639\u060c \u0627\u0644\u062a\u062c\u0645\u064a\u0639\u0627\u062a\u060c \u0627\u0644\u0637\u0644\u0628\u0627\u062a\u060c \u0627\u0644\u0625\u0631\u062c\u0627\u0639 \u0648\u0627\u0644\u0636\u0645\u0627\u0646.',
                placeholder: '\u0627\u0633\u0623\u0644 \u0639\u0646 \u0627\u0644\u0642\u0637\u0639\u060c \u0627\u0644\u0637\u0644\u0628\u0627\u062a\u060c \u0627\u0644\u0625\u0631\u062c\u0627\u0639\u060c \u0623\u0648 \u0627\u0644\u062a\u062c\u0645\u064a\u0639\u0627\u062a...',
                items: [
                    { prompt: '\u0627\u0642\u062a\u0631\u062d \u0639\u0644\u064a \u062a\u062c\u0645\u064a\u0639\u0629 \u062d\u0627\u0633\u0648\u0628 \u0623\u0644\u0639\u0627\u0628 \u0645\u062a\u0648\u0627\u0632\u0646\u0629', label: '\u062a\u062c\u0645\u064a\u0639\u0629 \u0623\u0644\u0639\u0627\u0628', icon: 'fa-desktop' },
                    { prompt: '\u062a\u062a\u0628\u0639 \u062d\u0627\u0644\u0629 \u0637\u0644\u0628\u064a', label: '\u062a\u062a\u0628\u0639 \u0627\u0644\u0637\u0644\u0628', icon: 'fa-box-open' },
                    { prompt: '\u0623\u062d\u062a\u0627\u062c \u0645\u0633\u0627\u0639\u062f\u0629 \u0641\u064a \u0625\u0631\u062c\u0627\u0639 \u0645\u0646\u062a\u062c \u0623\u0648 \u0627\u0644\u0636\u0645\u0627\u0646', label: '\u0627\u0644\u0625\u0631\u062c\u0627\u0639 \u0648\u0627\u0644\u0636\u0645\u0627\u0646', icon: 'fa-rotate-left' },
                    { prompt: '\u0633\u0627\u0639\u062f\u0646\u064a \u0641\u064a \u0627\u062e\u062a\u064a\u0627\u0631 \u062d\u0627\u0633\u0648\u0628 \u0645\u062d\u0645\u0648\u0644 \u0645\u0646\u0627\u0633\u0628', label: '\u062d\u0627\u0633\u0648\u0628 \u0645\u062d\u0645\u0648\u0644', icon: 'fa-laptop' }
                ]
            }
        };

        const group = isBuilderPage() ? builder : store;
        return group[lang] || group.english;
    }

    function escapeForHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderBuilderSnapshot() {
        const context = getBuilderContext();
        if (!context) return;

        const labels = builderUiLabels();
        const missing = context.missing.length
            ? context.missing.slice(0, 3).map(translateBuilderPartName).join(', ')
            : labels.none;
        const snapshot = document.createElement('div');
        snapshot.className = 'ai-guided-questions-card ai-build-snapshot notranslate';
        snapshot.setAttribute('translate', 'no');
        snapshot.setAttribute('dir', getSelectedChatLanguage() === 'arabic' ? 'rtl' : 'auto');
        snapshot.innerHTML = `
            <div class="guided-card-header">
                <i class="fas fa-chart-simple"></i> ${escapeForHtml(labels.liveContext)}
            </div>
            <div class="ai-context-grid">
                <span><b>${context.selectedCount}/7</b><small>${escapeForHtml(labels.selected)}</small></span>
                <span><b>${formatMADLocal(context.totalPrice)}</b><small>${escapeForHtml(labels.total)}</small></span>
                <span><b>${context.totalWattage}W</b><small>${escapeForHtml(labels.draw)}</small></span>
                <span><b>${context.recommendedPsu || 0}W+</b><small>${escapeForHtml(labels.psu)}</small></span>
            </div>
            <p>${escapeForHtml(labels.missing)}: ${escapeForHtml(missing)}</p>
        `;
        messageContainer.appendChild(snapshot);
    }

    function renderGuidedQuestions() {
        const lang = getSelectedChatLanguage();
        const copy = getAssistantCopy();
        aiInput.placeholder = copy.placeholder;

        const guided = document.createElement('div');
        guided.className = 'ai-guided-questions-card notranslate';
        guided.setAttribute('translate', 'no');
        guided.setAttribute('dir', lang === 'arabic' ? 'rtl' : 'auto');

        guided.innerHTML = `
            <div class="guided-card-header">
                <i class="fas fa-compass"></i> ${copy.header}
            </div>
            <div class="guided-card-buttons">
                ${copy.items.map(item => `
                    <button type="button" class="guided-btn" data-prompt="${escapeForHtml(item.prompt)}">
                        <i class="fas ${item.icon}"></i> ${escapeForHtml(item.label)}
                    </button>
                `).join('')}
            </div>
        `;
        messageContainer.appendChild(guided);

        guided.querySelectorAll('.guided-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                aiInput.value = btn.dataset.prompt;
                sendCurrentPrompt();
            });
        });

        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    function resetChatMessages() {
        messageContainer.innerHTML = '';
        const lang = getSelectedChatLanguage();
        const copy = getAssistantCopy();

        const initMsg = document.createElement('div');
        initMsg.className = 'bot-msg notranslate';
        initMsg.setAttribute('translate', 'no');
        initMsg.setAttribute('dir', lang === 'arabic' ? 'rtl' : 'auto');
        initMsg.textContent = copy.welcome;
        messageContainer.appendChild(initMsg);

        if (isBuilderPage()) {
            try {
                renderBuilderSnapshot();
            } catch (error) {
                console.warn('Builder AI snapshot failed:', error);
            }
        }
        renderGuidedQuestions();
    }

    function addFeedbackActions(msgEl, query, response) {
        const feedbackBar = document.createElement('div');
        feedbackBar.className = 'ai-feedback-bar';
        feedbackBar.innerHTML = `
            <button type="button" class="feedback-btn like-btn" title="Like response">
                <i class="far fa-thumbs-up"></i>
            </button>
            <button type="button" class="feedback-btn dislike-btn" title="Dislike response">
                <i class="far fa-thumbs-down"></i>
            </button>
        `;

        const likeBtn = feedbackBar.querySelector('.like-btn');
        const dislikeBtn = feedbackBar.querySelector('.dislike-btn');
        let currentRating = 0; // 0 = unrated, 1 = liked, -1 = disliked

        const submitFeedback = async (rating) => {
            try {
                const res = await fetch('api/save-chatbot-feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query, response, rating })
                });
                if (res.ok) {
                    if (typeof showToast !== 'undefined') {
                        showToast(rating === 1 ? 'Glad you liked it.' : 'Feedback recorded, thanks.', 'success');
                    }
                }
            } catch (err) {
                console.error("Failed to save feedback:", err);
            }
        };

        likeBtn.onclick = () => {
            if (currentRating === 1) {
                currentRating = 0;
                likeBtn.classList.remove('active');
            } else {
                currentRating = 1;
                likeBtn.classList.add('active');
                dislikeBtn.classList.remove('active');
                submitFeedback(1);
            }
        };

        dislikeBtn.onclick = () => {
            if (currentRating === -1) {
                currentRating = 0;
                dislikeBtn.classList.remove('active');
            } else {
                currentRating = -1;
                dislikeBtn.classList.add('active');
                likeBtn.classList.remove('active');
                submitFeedback(-1);
            }
        };

        msgEl.appendChild(feedbackBar);
    }

    function addTypingIndicator() {
        const wrap = document.createElement('div');
        wrap.className = 'bot-msg typing-indicator';
        wrap.innerHTML = '<span></span><span></span><span></span>';
        messageContainer.appendChild(wrap);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        return wrap;
    }

    function getBuilderContext() {
        if (!isBuilderPage()) return null;
        try {
            return PCBuilder.getAssistantContext();
        } catch (error) {
            console.warn('Builder context unavailable:', error);
            return null;
        }
    }

    function getLocalBuilderAnswer(userText) {
        const context = getBuilderContext();
        if (!context) return '';

        const text = userText.toLowerCase();
        const selectedEntries = Object.entries(context.selected).filter(([, product]) => product);
        if (!selectedEntries.length && /recommend|suggest|build.*around|balanced.*build|gaming pc|pc build/.test(text)) {
            return '';
        }
        const selectedNames = selectedEntries.map(([key, product]) => `${key.toUpperCase()}: ${product.name}`);
        const selectedNamesLocalized = selectedEntries.map(([key, product]) => `${translateBuilderPartName(key)}: ${product.name}`);
        const missingText = context.missing.length ? context.missing.join(', ') : 'none';
        const missingTextLocalized = context.missing.length ? context.missing.map(translateBuilderPartName).join('، ') : builderUiLabels().none;
        const selected = context.selected || {};
        const cpu = selected.cpu;
        const gpu = selected.gpu;
        const psu = selected.psu;
        const cooling = selected.cooling;
        const ram = selected.ram;
        const storage = selected.storage;
        const total = Number(context.totalPrice || 0);
        const target = Number(context.targetBudget || 0);
        const overBudget = target > 0 && total > target;
        const budgetLine = target > 0
            ? `${overBudget ? 'Over target by' : 'Budget headroom'}: **${formatMADLocal(Math.abs(total - target))}**.`
            : 'No target budget is set yet.';
        const budgetLineLocalized = target > 0
            ? `${overBudget ? 'فوق الميزانية بـ' : 'المتبقي من الميزانية'}: **${formatMADLocal(Math.abs(total - target))}**.`
            : 'لم يتم تحديد ميزانية مستهدفة بعد.';

        const partsList = selectedNames.length
            ? selectedNames.map(item => `- ${item}`).join('\n')
            : '- No parts selected yet';
        const partsListLocalized = selectedNamesLocalized.length
            ? selectedNamesLocalized.map(item => `- ${item}`).join('\n')
            : '- لا توجد قطع مختارة بعد';

        if (getSelectedChatLanguage() === 'arabic') {
            if (/التالي|الناقصة|ماذا|اختار|أختار|الخطوة/.test(text)) {
                const next = context.missing[0];
                if (!next) {
                    return `كل القطع الأساسية مكتملة.\n\n**المجموع:** ${formatMADLocal(total)}\n**الاستهلاك التقريبي:** ${context.totalWattage}W\n**مزود الطاقة المقترح:** ${context.recommendedPsu}W+\n\nالخطوة التالية: راجع الفحص، أضف الخدمات المناسبة، ثم أضف التجميعة إلى السلة أو صدّر العرض.`;
                }
                return `الخطوة الأفضل الآن: اختر **${translateBuilderPartName(next)}**.\n\nالتجميعة الحالية:\n${partsListLocalized}\n\nالناقص: ${missingTextLocalized}.`;
            }

            if (/توافق|طاقة|مزود|تبريد|فحص|افحص|تشخيص/.test(text)) {
                const issues = [];
                if (!cpu) issues.push('المعالج غير مختار، لذلك فحص المقبس والتبريد غير مكتمل.');
                if (!gpu) issues.push('البطاقة الرسومية غير مختارة، لذلك لا يمكن تقييم أداء الألعاب بعد.');
                if (!psu) issues.push(`مزود الطاقة غير مختار. الاقتراح الحالي: ${context.recommendedPsu}W+.`);
                if (cpu && !cooling) issues.push('المعالج مختار بدون تبريد.');

                return `فحص التجميعة الحالي:\n\n**القطع المختارة:** ${context.selectedCount}/7\n**المجموع:** ${formatMADLocal(total)}\n**الاستهلاك التقريبي:** ${context.totalWattage}W\n**مزود الطاقة المقترح:** ${context.recommendedPsu}W+\n**الناقص:** ${missingTextLocalized}\n\n${issues.length ? `ملاحظات:\n${issues.map(item => `- ${item}`).join('\n')}` : 'لا توجد مشاكل واضحة في القطع المختارة حاليا. اترك هامشا جيدا للطاقة والتبريد.'}`;
            }

            if (/ميزانية|حسن|تحسين|ارخص|أرخص|وفر|قيمة/.test(text)) {
                const suggestions = [];
                if (overBudget) suggestions.push('ابدأ بمراجعة تكلفة البطاقة الرسومية واللوحة الأم، فهما غالبا أسرع مكان لتقليل السعر.');
                if (!gpu) suggestions.push('اختر البطاقة الرسومية بعد تحديد المنصة حتى تذهب الميزانية لما يرفع الأداء فعلا.');
                if (cpu && gpu) suggestions.push('للألعاب، حافظ على ميزانية البطاقة الرسومية ووفّر في الصندوق أو اللوحة الزائدة أو RAM المبالغ فيها.');
                if (!suggestions.length) suggestions.push('أنفق حيث يظهر الفرق: GPU للألعاب، CPU/RAM للأعمال الثقيلة، والتخزين بعد تثبيت الأداء الأساسي.');

                return `تحسين الميزانية:\n\n**المجموع:** ${formatMADLocal(total)}\n${budgetLineLocalized}\n\n${suggestions.map(item => `- ${item}`).join('\n')}`;
            }

            if (/خدمات|الدفع|تركيب|تجميع|ويندوز|بيوس|اختبار/.test(text)) {
                const serviceNames = context.services.map(service => service.name);
                return `اقتراح الخدمات:\n\nالخدمات المختارة: ${serviceNames.length ? serviceNames.join('، ') : 'لا شيء'}.\n\n- خدمة التجميع مفيدة للكابل مانجمنت وتجهيز الجهاز عند التسليم.\n- تقرير اختبار الضغط مهم بعد اختيار المعالج والبطاقة الرسومية.\n- تحديث BIOS مفيد مع المنصات الجديدة أو القطع المختلطة.`;
            }

            if (/حلل|تحليل|تجميعتي|التجميعة|ملخص/.test(text)) {
                return `ملخص التجميعة الحالية:\n\n${partsListLocalized}\n\n**المجموع:** ${formatMADLocal(total)}\n**الاستهلاك التقريبي:** ${context.totalWattage}W\n**مزود الطاقة المقترح:** ${context.recommendedPsu}W+\n**الناقص:** ${missingTextLocalized}\n${target ? `**الميزانية المستهدفة:** ${formatMADLocal(target)}\n${budgetLineLocalized}` : ''}`;
            }
        }

        if (/(what|which).*(next|missing)|next part|continue|next best/.test(text)) {
            const next = context.missing[0];
            if (!next) {
                return `Your required component list is complete.\n\n**Total:** ${formatMADLocal(total)}\n**Estimated draw:** ${context.totalWattage}W\n**Recommended PSU:** ${context.recommendedPsu}W+\n\nNext: review diagnostics, add services if needed, then export the quote or add everything to cart.`;
            }

            let advice = `Next best step: choose **${next}**.\n\nCurrent build:\n${partsList}\n\nMissing: ${missingText}.`;
            if (/gpu/i.test(next)) advice += '\n\nFor gaming, prioritize the GPU after CPU/platform. Match it to your target resolution before spending more on extras.';
            if (/psu/i.test(next)) advice += `\n\nCurrent estimated draw is ${context.totalWattage}W, so aim for **${context.recommendedPsu}W+** with upgrade headroom.`;
            if (/cooling/i.test(next)) advice += '\n\nCooling should match CPU class: air cooling for midrange, stronger tower/AIO for high wattage chips.';
            return advice;
        }

        if (/compat|watt|power|psu|check|diagnostic|thermal|cooling/.test(text)) {
            const issues = [];
            if (!cpu) issues.push('CPU missing, so socket and cooling checks are incomplete.');
            if (!gpu) issues.push('GPU missing, so gaming balance cannot be judged yet.');
            if (!psu) issues.push(`PSU missing. Current recommendation: ${context.recommendedPsu}W+.`);
            if (cpu && !cooling) issues.push('CPU selected without cooling.');
            if (psu && context.totalWattage && psu.wattage && psu.wattage < context.recommendedPsu) {
                issues.push(`${psu.name} may be undersized. Use ${context.recommendedPsu}W+.`);
            }

            return `Current builder check:\n\n**Selected parts:** ${context.selectedCount}/7\n**Total:** ${formatMADLocal(total)}\n**Estimated power draw:** ${context.totalWattage}W\n**Recommended PSU:** ${context.recommendedPsu}W+\n**Missing:** ${missingText}\n\n${issues.length ? `Watch-outs:\n${issues.map(item => `- ${item}`).join('\n')}` : 'No obvious red flags from the selected components. Keep enough PSU and cooling headroom for future upgrades.'}`;
        }

        if (/budget|optimi[sz]e|save|cheaper|downgrade|value|waste/.test(text)) {
            const suggestions = [];
            if (overBudget) suggestions.push('Use the builder budget swap action first, then review GPU and motherboard spend.');
            if (gpu && !cpu) suggestions.push('Pick the CPU before over-investing in the GPU, otherwise balance is unknown.');
            if (cpu && gpu) suggestions.push('If this is a gaming build, keep GPU spend high and save on case aesthetics, oversized motherboard, or excess RAM.');
            if (ram && /64|128/.test(String(ram.name + ' ' + JSON.stringify(ram.specs || {})))) suggestions.push('64GB+ RAM is only worth it for workstation/video/AI workloads; gaming usually prefers 32GB.');
            if (storage && /4tb|8tb/i.test(storage.name)) suggestions.push('Large SSD capacity is convenient, but it is an easy place to save budget.');
            if (!suggestions.length) suggestions.push('Start with CPU + motherboard + RAM as the platform, then choose GPU based on resolution and FPS target.');

            return `Budget optimization pass:\n\n**Total:** ${formatMADLocal(total)}\n${budgetLine}\n\n${suggestions.map(item => `- ${item}`).join('\n')}\n\nBest rule: spend where the workload feels it first, not where the spec sheet looks flashiest.`;
        }

        if (/gaming|fps|1440|4k|1080|esport|aaa/.test(text)) {
            if (!cpu || !gpu) {
                return `For gaming advice I need both **CPU** and **GPU** selected.\n\nCurrent state:\n${partsList}\n\nNext: ${!cpu ? 'choose a CPU/platform first' : 'choose a GPU matched to your resolution'}.`;
            }

            const gpuName = gpu.name || 'selected GPU';
            const cpuName = cpu.name || 'selected CPU';
            return `Gaming readout:\n\n- CPU: **${cpuName}**\n- GPU: **${gpuName}**\n- Power draw estimate: **${context.totalWattage}W**\n- PSU target: **${context.recommendedPsu}W+**\n\nFor 1080p high refresh, CPU balance matters more. For 1440p and 4K, protect GPU budget first. If you tell me your target games and resolution, I can tune the build more tightly.`;
        }

        if (/service|assembly|bios|stress|windows|install|checkout/.test(text)) {
            const serviceNames = context.services.map(service => service.name);
            const recommendations = [];
            if (cpu || gpu) recommendations.push('Stress test report is worth it for CPU/GPU builds.');
            if (cpu && !/am4/i.test(String(cpu.specs?.Socket || ''))) recommendations.push('BIOS update is useful for newer platforms or mixed inventory.');
            recommendations.push('Professional assembly saves time and catches cable/thermal issues before delivery.');
            if (!serviceNames.some(name => /windows/i.test(name))) recommendations.push('Windows install is useful if you want the machine ready on arrival.');

            return `Service recommendation:\n\nSelected services: ${serviceNames.length ? serviceNames.join(', ') : 'none'}.\n\n${recommendations.map(item => `- ${item}`).join('\n')}`;
        }

        if (/summary|current build|my build|analy[sz]e/.test(text)) {
            return `Current build summary:\n\n${partsList}\n\n**Total:** ${formatMADLocal(total)}\n**Estimated draw:** ${context.totalWattage}W\n**Recommended PSU:** ${context.recommendedPsu}W+\n**Missing:** ${missingText}\n${target ? `**Target budget:** ${formatMADLocal(target)}\n${budgetLine}` : ''}`;
        }

        return '';
    }
    // formatMAD and formatMADLocal are now provided globally by currency.js


    /**
     * Minimal markdown renderer: **bold**, bullet lines (🔹), line breaks, and simple tables.
     */
    function markdownLite(text) {
        let parsed = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');

        // Render simple markdown tables
        if (parsed.includes('|')) {
            const lines = parsed.split('\n');
            let inTable = false;
            let tableHtml = '';
            const newLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i].trim();
                if (line.startsWith('|') && line.endsWith('|')) {
                    if (!inTable) {
                        inTable = true;
                        tableHtml = '<table class="ai-table" style="width:100%; border-collapse: collapse; margin-top:10px; font-size: 0.85em;">\n';
                    }
                    if (line.includes('|---')) continue; // Skip separator row
                    
                    const cells = line.split('|').slice(1, -1).map(c => c.trim());
                    const tag = i > 0 && lines[i-1].includes('|---') ? 'td' : 'th';
                    tableHtml += '<tr>' + cells.map(c => `<${tag} style="border:1px solid var(--border); padding:5px;">${c}</${tag}>`).join('') + '</tr>\n';
                } else {
                    if (inTable) {
                        newLines.push(tableHtml + '</table>');
                        inTable = false;
                        tableHtml = '';
                    }
                    newLines.push(line);
                }
            }
            if (inTable) newLines.push(tableHtml + '</table>');
            parsed = newLines.join('\n');
        } else {
            parsed = parsed.replace(/\n/g, '<br>');
        }
        
        // Handle remaining newlines outside of tables
        return parsed.replace(/\n/g, '<br>').replace(/<br><table/g, '<table').replace(/<\/table><br>/g, '</table>');
    }

    // Attach Add All to Cart globally so innerHTML buttons can call it
    window.aiAddAllToCart = function(btn) {
        try {
            const products = JSON.parse(btn.dataset.products);
            const cart = (typeof Cart !== 'undefined') ? Cart : (window.parent && window.parent.Cart);
            if (!cart) {
                alert("Cart system not available.");
                return;
            }
            products.forEach(p => {
                cart.add({
                    id: p.id,
                    name: p.name,
                    price: p.price,
                    image: p.image,
                    inStock: p.inStock ?? true
                });
            });
            btn.innerHTML = '<i class="fas fa-check"></i> Added!';
            btn.disabled = true;
            btn.style.background = 'var(--green)';
            btn.style.color = '#000';
            if (typeof showToast !== 'undefined') showToast(window.__marocPcI18n?.buildComboAdded || 'Build combo added to cart!', 'success');
        } catch (e) {
            console.error('Failed to add combo to cart', e);
        }
    };

    async function getAIResponse(userText) {
        try {
            const builderContext = getBuilderContext();
            const langSelect = document.getElementById('ai-lang-select');
            const selectedLanguage = langSelect ? langSelect.value : 'auto';
            const payload = { 
                message: userText,
                language: selectedLanguage
            };
            if (builderContext) {
                payload.builder_context = builderContext;
                payload.message = `${userText}\n\nBuilder context: selected ${builderContext.selectedCount}/7 parts, total ${builderContext.totalPrice} DH, estimated ${builderContext.totalWattage}W, recommended PSU ${builderContext.recommendedPsu}W, missing ${builderContext.missing.join(', ') || 'none'}.`;
            }
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            const response = await fetch(API_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            if (!response.ok) {
                return { response: `Server error (HTTP ${response.status}). Please try again.` };
            }

            const data = await response.json();
            if (typeof data.response === 'string') {
                data.response = repairMojibake(data.response);
            }
            return data;

        } catch (error) {
            console.error("Fetch failed:", error);
            if (error && error.name === 'AbortError') {
                return { response: "The assistant took too long to answer. I can still help locally with the current builder state: try Analyze, Next part, Full check, or Optimize." };
            }
            return { response: "Couldn't reach the server. Check your connection and try again." };
        }
    }
});
