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
    const isBuilderPage    = typeof PCBuilder !== 'undefined' && typeof PCBuilder.getAssistantContext === 'function';

    if (!aiTerminal || !openBtn || !closeBtn || !aiInput || !messageContainer) {
        console.error("AI Terminal: One or more elements not found in DOM.");
        return;
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
        resetBtn.className = 'ai-header-btn';
        resetBtn.title = 'Reset Chat';
        resetBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        resetBtn.setAttribute('style', 'margin-right: 12px; background: none; border: none; color: #000; opacity: 0.7; cursor: pointer; font-size: 0.95em; transition: opacity 0.2s;');
        
        resetBtn.onmouseover = () => { resetBtn.style.opacity = '1'; };
        resetBtn.onmouseout = () => { resetBtn.style.opacity = '0.7'; };
        
        // Insert reset button before close button
        header.insertBefore(resetBtn, closeBtn);

        // Create language selector container
        const langContainer = document.createElement('div');
        langContainer.setAttribute('style', 'margin-left: auto; margin-right: 12px; display: flex; align-items: center;');

        const langSelect = document.createElement('select');
        langSelect.id = 'ai-lang-select';
        langSelect.innerHTML = `
            <option value="auto" style="background: #111318; color: #e0e6ed;">🌐 Auto</option>
            <option value="english" style="background: #111318; color: #e0e6ed;">🇬🇧 EN</option>
            <option value="french" style="background: #111318; color: #e0e6ed;">🇫🇷 FR</option>
            <option value="darija" style="background: #111318; color: #e0e6ed;">🇲🇦 AR</option>
        `;
        langSelect.setAttribute('style', 'background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.15); color: #000; font-weight: 700; border-radius: 6px; padding: 2px 4px; font-size: 0.72rem; outline: none; cursor: pointer; font-family: "Syne", sans-serif; height: 24px; min-height: 24px; max-height: 24px; line-height: 1; width: auto; box-sizing: border-box; transition: all 0.2s;');
        
        langSelect.onmouseover = () => { langSelect.style.background = 'rgba(0,0,0,0.12)'; };
        langSelect.onmouseout = () => { langSelect.style.background = 'rgba(0,0,0,0.06)'; };
        
        const savedLang = localStorage.getItem('ai_chat_language') || 'auto';
        langSelect.value = savedLang;

        langSelect.onchange = () => {
            localStorage.setItem('ai_chat_language', langSelect.value);
            if (typeof showToast !== 'undefined') {
                showToast(`Chatbot language set to: ${langSelect.options[langSelect.selectedIndex].text}`, 'success');
            }
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
            btn.innerHTML = isBuilderPage
                ? '<i class="fas fa-wand-magic-sparkles"></i> Apply Picks to Builder'
                : '<i class="fas fa-cart-plus"></i> Add Combo to Cart';
            btn.dataset.products = JSON.stringify(data.products);
            btn.onclick = function() {
                if (isBuilderPage && typeof PCBuilder.applyAssistantProducts === 'function') {
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
        aiInput.focus();
    }

    // ----------------------------------------
    // Helpers
    // ----------------------------------------

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function addMessage(text, className) {
        const msg = document.createElement('div');
        msg.className = className;
        // Support basic **bold** markdown in bot messages
        if (className === 'bot-msg') {
            msg.classList.add('notranslate');
            msg.setAttribute('translate', 'no');
            msg.innerHTML = markdownLite(text);
        } else {
            msg.textContent = text;
        }
        messageContainer.appendChild(msg);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        return msg;
    }

    function renderGuidedQuestions() {
        const langSelect = document.getElementById('ai-lang-select');
        const lang = langSelect ? langSelect.value : 'auto';

        let headerText = 'Popular Questions';
        let items = [
            { prompt: 'Suggest a balanced gaming PC setup', label: 'Gaming PC Setup', icon: 'fa-desktop' },
            { prompt: 'Track my order status', label: 'Track Order Status', icon: 'fa-box-open' },
            { prompt: 'Help with a return or SAV warranty RMA', label: 'Returns & RMA Desk', icon: 'fa-rotate-left' },
            { prompt: 'Help me find a laptop using Laptop Finder', label: 'Laptop Finder Curate', icon: 'fa-laptop' }
        ];

        if (lang === 'french') {
            headerText = 'Questions Populaires';
            items = [
                { prompt: 'Suggérer une configuration PC de jeu équilibrée', label: 'Configuration PC Gamer', icon: 'fa-desktop' },
                { prompt: 'Suivre l’état de ma commande', label: 'Statut de Commande', icon: 'fa-box-open' },
                { prompt: 'Aide pour un retour ou une garantie SAV RMA', label: 'Retours & SAV RMA', icon: 'fa-rotate-left' },
                { prompt: 'Aidez-moi à trouver un PC portable avec le Laptop Finder', label: 'Trouver un PC Portable', icon: 'fa-laptop' }
            ];
        } else if (lang === 'darija') {
            headerText = 'Asila Châi3a';
            items = [
                { prompt: 'Qte7 3liya config gaming PC', label: 'Gaming PC Setup', icon: 'fa-desktop' },
                { prompt: 'Tbe3 l-commande diali', label: 'Suivi l-Commande', icon: 'fa-box-open' },
                { prompt: 'N3awnak f chi retour wla dman dial SAV RMA', label: 'Retours & SAV RMA', icon: 'fa-rotate-left' },
                { prompt: 'Help me find a laptop using Laptop Finder', label: 'Laptop Finder', icon: 'fa-laptop' }
            ];
        }

        const guided = document.createElement('div');
        guided.className = 'ai-guided-questions-card notranslate';
        guided.setAttribute('translate', 'no');

        let buttonsHtml = '';
        items.forEach(item => {
            buttonsHtml += `
                <button type="button" class="guided-btn" data-prompt="${item.prompt}">
                    <i class="fas ${item.icon}"></i> ${item.label}
                </button>
            `;
        });

        guided.innerHTML = `
            <div class="guided-card-header">
                <i class="fas fa-compass"></i> ${headerText}
            </div>
            <div class="guided-card-buttons">
                ${buttonsHtml}
            </div>
        `;
        messageContainer.appendChild(guided);

        // Bind clicks
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
        
        const langSelect = document.getElementById('ai-lang-select');
        const lang = langSelect ? langSelect.value : 'auto';

        let initText = '';
        if (lang === 'french') {
            initText = isBuilderPage
                ? "Assistant de configuration prêt. Demandez-moi quel composant choisir ensuite, vérifiez votre consommation en watts, ou demandez un PC gamer selon votre budget."
                : "Système initialisé. Comment puis-je vous aider dans votre configuration aujourd'hui ?";
        } else if (lang === 'darija') {
            initText = isBuilderPage
                ? "Assistant ready. Swlni chno n-choisir mn b3d, check wattage dial config, wla swlni 3la config 3la hsab budget dialk."
                : "Mrahba! Chno khassak t-monter wla n3awnak lyouma f PC dialk?";
        } else {
            initText = isBuilderPage
                ? "Build assistant ready. Ask me what to pick next, check your wattage, or request a gaming PC around your budget."
                : "System initialized. How can I assist your build today?";
        }

        const initMsg = document.createElement('div');
        initMsg.className = 'bot-msg notranslate';
        initMsg.setAttribute('translate', 'no');
        initMsg.textContent = initText;
        messageContainer.appendChild(initMsg);
        
        // Re-render Guided Questions Card
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
                        showToast(rating === 1 ? 'Glad you liked it! 👍' : 'Feedback recorded, thanks! 👎', 'success');
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
        if (!isBuilderPage) return null;
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
        const selectedNames = Object.entries(context.selected)
            .filter(([, product]) => product)
            .map(([key, product]) => `${key.toUpperCase()}: ${product.name}`);
        const missingText = context.missing.length ? context.missing.join(', ') : 'none';

        if (/(what|which).*(next|missing)|next part|continue/.test(text)) {
            const next = context.missing[0];
            if (!next) {
                return `Your main component list is complete.\n\n**Total:** ${formatMADLocal(context.totalPrice)}\n**Estimated draw:** ${context.totalWattage}W\n**Recommended PSU:** ${context.recommendedPsu}W+\n\nNext: review compatibility, add build services if needed, then export or add everything to cart.`;
            }
            return `Next best step: choose **${next}**.\n\nCurrent build:\n${selectedNames.length ? selectedNames.map(item => `• ${item}`).join('\n') : '• No parts selected yet'}\n\nMissing: ${missingText}.`;
        }

        if (/compat|watt|power|psu|check/.test(text)) {
            return `Current builder check:\n\n**Selected parts:** ${context.selectedCount}/7\n**Total:** ${formatMADLocal(context.totalPrice)}\n**Estimated power draw:** ${context.totalWattage}W\n**Recommended PSU:** ${context.recommendedPsu}W+\n**Missing:** ${missingText}\n\nFor safest PSU sizing, pick a PSU at or above the recommendation with some upgrade headroom.`;
        }

        if (/summary|current build|my build/.test(text)) {
            return `Here is your current build:\n\n${selectedNames.length ? selectedNames.map(item => `• ${item}`).join('\n') : '• No parts selected yet'}\n\n**Total:** ${formatMADLocal(context.totalPrice)}\n**Estimated draw:** ${context.totalWattage}W\n**Missing:** ${missingText}.`;
        }

        return '';
    }

    function formatMADLocal(value) {
        return Number(value || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    }

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
            if (typeof showToast !== 'undefined') showToast('Build combo added to cart!', 'success');
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
                payload.message = `${userText}\n\nBuilder context: selected ${builderContext.selectedCount}/7 parts, total ${builderContext.totalPrice} MAD, estimated ${builderContext.totalWattage}W, recommended PSU ${builderContext.recommendedPsu}W, missing ${builderContext.missing.join(', ') || 'none'}.`;
            }
            const response = await fetch(API_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            });

            if (!response.ok) {
                return { response: `Server error (HTTP ${response.status}). Please try again.` };
            }

            return await response.json();

        } catch (error) {
            console.error("Fetch failed:", error);
            return { response: "Couldn't reach the server. Check your connection and try again." };
        }
    }
});
