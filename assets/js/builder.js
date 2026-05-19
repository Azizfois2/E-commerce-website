/**
 * builder.js — Interactive PC Builder / Configurator
 * Step-by-step wizard with compatibility engine & wattage calculator
 */
const PCBuilder = (() => {
    // ── Component Steps ──────────────────────────────────────
    const STEPS = [
        { key: 'cpu', label: 'CPU', icon: 'fa-microchip', category: 'cpu' },
        { key: 'motherboard', label: 'Motherboard', icon: 'fa-diagram-project', category: 'motherboard' },
        { key: 'gpu', label: 'GPU', icon: 'fa-gamepad', category: 'gpu' },
        { key: 'ram', label: 'RAM', icon: 'fa-memory', category: 'ram' },
        { key: 'storage', label: 'Storage', icon: 'fa-hdd', category: 'storage' },
        { key: 'psu', label: 'PSU', icon: 'fa-bolt', category: 'psu' },
        { key: 'cooling', label: 'Cooling', icon: 'fa-fan', category: 'cooling' },
        { key: 'monitor', label: 'Monitor', icon: 'fa-display', category: 'monitor' },
        { key: 'accessories', label: 'Accessories', icon: 'fa-keyboard', category: 'accessories' },
    ];

    const DEFAULT_WATTAGE = {
        cpu: 125, motherboard: 50, gpu: 300, ram: 10, storage: 10, psu: 0, cooling: 15, monitor: 0, accessories: 0,
    };

    const BUILD_SERVICES = {
        assembly: { id: 'service-assembly', name: 'Professional PC Assembly', price: 299, icon: 'fa-screwdriver-wrench' },
        bios: { id: 'service-bios', name: 'BIOS Update', price: 99, icon: 'fa-microchip' },
        stress: { id: 'service-stress', name: 'Stress Test Report', price: 149, icon: 'fa-gauge-high' },
        windows: { id: 'service-windows', name: 'Windows Install', price: 199, icon: 'fa-window-maximize' },
        bazzite: { id: 'service-bazzite', name: 'Bazzite + Proton++ Install', price: 249, icon: 'fa-linux' },
    };

    let PRESETS = []; /* ORIGINAL CODE:
    const PRESETS = [
        { 
            key: 'esports', 
            label: 'Base Build', 
            useCase: 'gaming', 
            budget: 12500, 
            description: 'A solid entry level setup featuring an AMD or Intel processor, ideal for everyday computing tasks, light multitasking, and casual gaming.',
            image: 'https://images.unsplash.com/photo-1587202376732-8309058b70b4?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'aaa1440', 
            label: 'Advanced Build', 
            useCase: 'gaming', 
            budget: 18000, 
            description: 'A versatile mid range build powered by high-performance components, designed for seamless multitasking, gaming at higher settings, and content creation.',
            image: 'https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'creator', 
            label: 'Power Build', 
            useCase: 'editing', 
            budget: 26000, 
            description: 'A high performance system optimized for demanding workloads like 4K gaming, video editing, and advanced simulations, offering top-tier speed and efficiency.',
            image: 'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=400&auto=format&fit=crop'
        },
        { 
            key: 'legacy', 
            label: 'Legacy Enthusiast', 
            useCase: 'legacy', 
            budget: 4200, 
            description: 'A specialized build using used server-grade components and legacy X99 architecture. Recommended for enthusiasts comfortable with tinkering.',
            image: 'https://images.unsplash.com/photo-1555680202-c86f0e12f086?q=80&w=400&auto=format&fit=crop'
        }
    ]; */

    const FINDER_GAMES = [
        { id: 'cyberpunk', name: 'Cyberpunk 2077', icon: 'fa-robot', demand: 1.12, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1091500/header.jpg' },
        { id: 'rdr2', name: 'Red Dead Redemption 2', icon: 'fa-hat-cowboy', demand: 1.02, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1174180/header.jpg' },
        { id: 'warzone', name: 'Warzone', icon: 'fa-person-rifle', demand: 0.98, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1938090/header.jpg' },
        { id: 'wukong', name: 'Black Myth: Wukong', icon: 'fa-dragon', demand: 1.14, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/2358720/header.jpg' },
        { id: 'bg3', name: "Baldur's Gate 3", icon: 'fa-dice-d20', demand: 0.86, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1086940/header.jpg' },
        { id: 'starfield', name: 'Starfield', icon: 'fa-shuttle-space', demand: 1.08, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1716740/header.jpg' },
        { id: 'valorant', name: 'Valorant', icon: 'fa-crosshairs', demand: 0.62, image: 'https://upload.wikimedia.org/wikipedia/en/b/ba/Valorant_cover.jpg' },
        { id: 'forza5', name: 'Forza Horizon 5', icon: 'fa-flag-checkered', demand: 0.92, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1551360/header.jpg' },
        { id: 'fortnite', name: 'Fortnite', icon: 'fa-bolt', demand: 0.78, image: 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Fortnite_at_E3_2018_%2842781993231%29.jpg/960px-Fortnite_at_E3_2018_%2842781993231%29.jpg' },
        { id: 'gta5', name: 'GTA V', icon: 'fa-car', demand: 0.74, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/271590/header.jpg' },
        { id: 'helldivers2', name: 'Helldivers 2', icon: 'fa-skull', demand: 0.94, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/553850/header.jpg' },
        { id: 'eldenring', name: 'Elden Ring', icon: 'fa-ring', demand: 0.88, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/1245620/header.jpg' },
        { id: 'fc25', name: 'EA SPORTS FC 25', icon: 'fa-futbol', demand: 0.70, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/2669320/header.jpg' },
        { id: 'minecraft', name: 'Minecraft', icon: 'fa-cube', demand: 0.40, image: 'https://upload.wikimedia.org/wikipedia/en/5/51/Minecraft_cover.png' },
        { id: 'rocketleague', name: 'Rocket League', icon: 'fa-car-burst', demand: 0.50, image: 'https://cdn.cloudflare.steamstatic.com/steam/apps/252950/header.jpg' },
    ];

    const FINDER_RECOMMENDATION = {
        resolution: { '1080p': 0, '1440p': 3600, '4K': 9000 },
        fps: { 60: 0, 120: 2600, 165: 4500 },
        baseBudget: 9800,
        minBudget: 8000,
        maxBudget: 32000,
    };

    // ── State ────────────────────────────────────────────────
    let currentStep = 0;
    let selectedComponents = {};
    let selectedServices = {};
    let allProducts = [];
    let buildName = 'My Build';
    let useCase = 'gaming';
    let activePreset = 'aaa1440';
    let targetBudget = 18000;
    let selectedPlatform = 'intel';
    let builderPath = '';
    let bottleneckResolution = '1440p';
    let componentFilters = {
        query: '',
        sort: 'recommended',
        stockOnly: true,
    };
    let finderState = {
        games: ['cyberpunk', 'gta5'],
        resolution: '1440p',
        targetFps: 120,
        budget: 18000,
    };

    function productImage(product) {
        return product.image || `images/products/placeholder-${product.category || 'storage'}.svg`;
    }

    // ── Init ─────────────────────────────────────────────────
    async function init() {
        // Load products from global data.js
        if (typeof products !== 'undefined') {
            allProducts = products;
        }

        // Fetch dynamic presets
        try {
            const res = await fetch('api/custom-builds.php');
            const data = await res.json();
            if (data.success && data.presets && data.presets.length > 0) {
                PRESETS = data.presets.map((p, index) => {
                    const fallbackImages = [
                        'https://images.unsplash.com/photo-1587202376732-8309058b70b4?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=400&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1555680202-c86f0e12f086?q=80&w=400&auto=format&fit=crop'
                    ];
                    return {
                        key: 'preset_' + p.id,
                        label: p.name,
                        useCase: p.target_category,
                        budget: parseFloat(p.base_price),
                        description: p.description || 'Custom preset curated by our experts.',
                        image: fallbackImages[index % fallbackImages.length],
                        productsData: p.products_json
                    };
                });
            }
        } catch (e) {
            console.error('Failed to load dynamic presets', e);
        }

        // Check for shared build in URL
        const params = new URLSearchParams(window.location.search);
        const shareCode = params.get('build');
        if (shareCode) {
            loadSharedBuild(shareCode);
        }

        renderWizardSteps();
        renderUseCaseBar();
        renderGamingFinder();
        renderBuilderToolPanels();
        renderCurrentStep();
        updateSummary();
        bindServiceOptions();

        // Initialize FPS Estimator if available
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.init();
            syncFinderEstimator();
        }
    }

    // ── Render Wizard Steps ──────────────────────────────────
    function renderWizardSteps() {
        const container = document.getElementById('wizardSteps');
        if (!container) return;

        container.innerHTML = STEPS.map((step, i) => {
            let cls = 'wizard-step';
            if (i === currentStep) cls += ' active';
            if (selectedComponents[step.key]) cls += ' completed';
            return `<button class="${cls}" data-step="${i}">
                <i class="fas ${step.icon}"></i>
                ${step.label}
            </button>`;
        }).join('');

        container.querySelectorAll('.wizard-step').forEach(btn => {
            btn.addEventListener('click', () => {
                currentStep = parseInt(btn.dataset.step);
                renderWizardSteps();
                renderCurrentStep();
            });
        });
    }

    // ── Use Case Bar ─────────────────────────────────────────
    function renderUseCaseBar() {
        const container = document.getElementById('useCaseBar');
        if (!container) return;

        container.innerHTML = `
            <div class="preset-grid">
                ${PRESETS.map(c => `
                    <div class="preset-card ${c.key === activePreset ? 'active' : ''}" data-case="${c.key}">
                        <div class="preset-image">
                            <img src="${c.image}" alt="${c.label}" onerror="this.src='logo.png'">
                        </div>
                        <div class="preset-content">
                            <h3>${c.label}</h3>
                            <p>${c.description}</p>
                            <div class="preset-footer">
                                <span class="preset-budget">${formatMAD(c.budget)} Target</span>
                                <button class="btn-start-build" 
                                    data-case="${c.key}" 
                                    data-use-case="${c.useCase}" 
                                    data-budget="${c.budget}">
                                    START WITH ${c.label.toUpperCase()}
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        container.querySelectorAll('.btn-start-build').forEach(btn => {
            btn.addEventListener('click', () => {
                const selectedKey = btn.dataset.case;
                if (selectedKey === 'legacy') {
                    showLegacyWarningModal(() => {
                        applyPreset(selectedKey, btn);
                    });
                } else {
                    applyPreset(selectedKey, btn);
                }

            });
        });
    }

    function applyPreset(selectedKey, btn) {
        activePreset = selectedKey;
        useCase = btn.dataset.useCase || 'general';
        targetBudget = parseInt(btn.dataset.budget, 10) || targetBudget;
        
        const container = document.getElementById('useCaseBar');
        if (container) {
            container.querySelectorAll('.preset-card').forEach(card => card.classList.remove('active'));
            const card = btn.closest('.preset-card');
            if (card) card.classList.add('active');
        }
        
        autoBuild(btn.dataset.useCase || useCase, targetBudget);
        
        if (selectedKey === 'legacy') {
            selectedServices['bios'] = BUILD_SERVICES['bios'];
            selectedServices['stress'] = BUILD_SERVICES['stress'];
            
            const biosCb = document.querySelector('.service-checkbox[value="bios"]');
            if (biosCb) biosCb.checked = true;
            
            const stressCb = document.querySelector('.service-checkbox[value="stress"]');
            if (stressCb) stressCb.checked = true;
            
            updateSummary();
            showToast('BIOS Update & Stress Test auto-recommended for legacy hardware.', 'warn');
        }
        
        // Scroll to wizard steps
        document.getElementById('wizardSteps')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showLegacyWarningModal(onConfirm) {
        let modal = document.getElementById('legacyWarningModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'legacyWarningModal';
            modal.className = 'custom-modal-backdrop';
            modal.innerHTML = `
                <div class="custom-modal-box">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <h3>Legacy Hardware Warning</h3>
                    <p>This build uses used server-grade components from 2016 (X99 platform). These parts lack modern features, have higher failure rates, and are NOT recommended for first-time builders.</p>
                    <p>You are choosing a <strong>3-month limited warranty</strong> over a standard 2-year modern warranty. Are you a technical enthusiast prepared to troubleshoot this hardware?</p>
                    <div class="modal-actions">
                        <button class="btn-cancel" id="btnLegacyCancel">I want a modern PC</button>
                        <button class="btn-proceed" id="btnLegacyProceed">I accept the risks</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Use a short timeout to allow DOM to register the element before animating
        setTimeout(() => modal.classList.add('show'), 10);
        
        modal.querySelector('#btnLegacyCancel').onclick = () => {
            modal.classList.remove('show');
            const baseBuildBtn = document.querySelector('.btn-start-build[data-case="esports"]');
            if (baseBuildBtn) {
                applyPreset('esports', baseBuildBtn);
                setTimeout(() => {
                    showToast('Welcome to Reliability: Base Build selected with full 2-year warranty.', 'success');
                }, 500);
            }
        };
        
        modal.querySelector('#btnLegacyProceed').onclick = () => {
            modal.classList.remove('show');
            onConfirm();
        };
    }

    function renderGamingFinder() {
        const gamesContainer = document.getElementById('finderGames');
        const budgetInput = document.getElementById('finderBudget');
        if (!gamesContainer || !budgetInput) return;

        gamesContainer.innerHTML = FINDER_GAMES.map(game => `
            <button class="gf-game ${finderState.games.includes(game.id) ? 'active' : ''}" data-game="${game.id}" type="button" style="--game-art:url('${game.image}')">
                <span class="gf-game-shade"></span>
                <span class="gf-game-content">
                    <i class="fas ${game.icon}"></i>
                    <span>${game.name}</span>
                </span>
            </button>
        `).join('');

        gamesContainer.querySelectorAll('.gf-game').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.game;
                if (finderState.games.includes(id)) {
                    finderState.games = finderState.games.filter(gameId => gameId !== id);
                } else if (finderState.games.length < 4) {
                    finderState.games = [...finderState.games, id];
                } else {
                    showToast('Pick up to 4 games.', 'error');
                    return;
                }
                btn.classList.toggle('active', finderState.games.includes(id));
                syncFinderEstimator();
                updateFinderPreview();
            });
        });

        bindFinderSegment('finderResolution', 'resolution', 'resolution');
        bindFinderSegment('finderFps', 'targetFps', 'fps');

        budgetInput.value = finderState.budget;
        budgetInput.addEventListener('input', () => {
            finderState.budget = parseInt(budgetInput.value, 10) || targetBudget;
            updateFinderPreview();
        });

        updateFinderPreview();
    }

    function bindFinderSegment(containerId, stateKey, dataKey) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('button').forEach(btn => {
            const rawValue = btn.dataset[dataKey];
            const value = stateKey === 'targetFps' ? parseInt(rawValue, 10) : rawValue;
            btn.classList.toggle('active', finderState[stateKey] === value);
            btn.addEventListener('click', () => {
                finderState[stateKey] = value;
                container.querySelectorAll('button').forEach(button => button.classList.remove('active'));
                btn.classList.add('active');
                syncFinderEstimator();
                updateFinderPreview();
            });
        });
    }

    function getFinderDemand() {
        const selectedGames = FINDER_GAMES.filter(game => finderState.games.includes(game.id));
        if (!selectedGames.length) return 0.82;
        return Math.max(...selectedGames.map(game => game.demand));
    }

    function getFinderRecommendedBudget() {
        const demand = getFinderDemand();
        const demandCost = Math.max(0, (demand - 0.72) * 7000);
        const recommended = FINDER_RECOMMENDATION.baseBudget
            + (FINDER_RECOMMENDATION.resolution[finderState.resolution] || 0)
            + (FINDER_RECOMMENDATION.fps[finderState.targetFps] || 0)
            + demandCost;

        return Math.min(
            FINDER_RECOMMENDATION.maxBudget,
            Math.max(FINDER_RECOMMENDATION.minBudget, Math.round(recommended / 500) * 500)
        );
    }

    function getFinderTier(recommendedBudget) {
        if (finderState.budget >= recommendedBudget + 3500) {
            return { label: 'Performance headroom', tone: 'great', icon: 'fa-circle-check' };
        }
        if (finderState.budget >= recommendedBudget) {
            return { label: 'Good match', tone: 'good', icon: 'fa-circle-check' };
        }
        if (finderState.budget >= recommendedBudget * 0.82) {
            return { label: 'Balanced with settings tweaks', tone: 'warn', icon: 'fa-gauge-high' };
        }
        return { label: 'Budget is tight', tone: 'tight', icon: 'fa-triangle-exclamation' };
    }

    function getFinderEstimatedFps() {
        const gpu = selectedComponents.gpu;
        const cpu = selectedComponents.cpu;
        if (!gpu || typeof FPS_DATA === 'undefined') return null;

        const selectedGameIds = finderState.games.length
            ? finderState.games
            : FINDER_GAMES.slice(0, 3).map(game => game.id);
        const gpuBenchmarks = FPS_DATA.benchmarks[String(gpu.id)] || getFallbackGpuBenchmark(gpu);
        const cpuMultiplier = FPS_DATA.cpuTiers?.[String(cpu?.id)] || FPS_DATA.cpuTiers?.default || 0.85;

        const fpsValues = selectedGameIds.map(gameId => {
            const fallback = getFallbackGameFps(gpu, finderState.resolution);
            const base = gpuBenchmarks?.[gameId]?.[finderState.resolution] || fallback;
            return Math.max(25, Math.round(base * cpuMultiplier));
        });

        if (!fpsValues.length) return null;
        const average = Math.round(fpsValues.reduce((sum, fps) => sum + fps, 0) / fpsValues.length);
        return {
            average,
            low: Math.min(...fpsValues),
            meetsTarget: average >= finderState.targetFps,
        };
    }

    function getFallbackGpuBenchmark(gpu) {
        const price = Number(gpu?.price || 0);
        const tdp = extractWattage(gpu || {}, 'gpu');
        let scale = 0.75;
        if (price >= 14000 || tdp >= 400) scale = 1.18;
        else if (price >= 11000 || tdp >= 315) scale = 1.04;
        else if (price >= 7600 || tdp >= 280) scale = 0.88;
        else if (price >= 5400 || tdp >= 240) scale = 0.72;

        const base = {
            cyberpunk: { '1080p': 128, '1440p': 92, '4K': 55 },
            rdr2: { '1080p': 148, '1440p': 112, '4K': 72 },
            warzone: { '1080p': 154, '1440p': 118, '4K': 74 },
            fortnite: { '1080p': 205, '1440p': 168, '4K': 108 },
            valorant: { '1080p': 310, '1440p': 260, '4K': 185 },
            gta5: { '1080p': 185, '1440p': 165, '4K': 95 },
        };

        return Object.fromEntries(Object.entries(base).map(([game, values]) => [
            game,
            Object.fromEntries(Object.entries(values).map(([resolution, fps]) => [resolution, Math.round(fps * scale)]))
        ]));
    }

    function getFallbackGameFps(gpu, resolution) {
        const price = Number(gpu?.price || 0);
        const resolutionPenalty = resolution === '4K' ? 0.48 : resolution === '1440p' ? 0.72 : 1;
        return Math.round(Math.max(45, price / 65) * resolutionPenalty);
    }

    function updateFinderPreview() {
        const result = document.getElementById('finderResult');
        const budgetValue = document.getElementById('finderBudgetValue');
        const status = document.getElementById('finderStatus');
        if (!result) return;

        const recommendedBudget = getFinderRecommendedBudget();
        const tier = getFinderTier(recommendedBudget);
        const fps = getFinderEstimatedFps();
        const selectedGameNames = FINDER_GAMES
            .filter(game => finderState.games.includes(game.id))
            .map(game => game.name);

        if (budgetValue) budgetValue.textContent = formatMAD(finderState.budget);
        if (status) status.textContent = `${finderState.resolution} / ${finderState.targetFps} FPS`;

        const budgetDelta = finderState.budget - recommendedBudget;
        const budgetText = budgetDelta >= 0
            ? `${formatMAD(budgetDelta)} above target`
            : `${formatMAD(Math.abs(budgetDelta))} below target`;

        result.innerHTML = `
            <div class="gf-result-top">
                <span class="gf-pill ${tier.tone}"><i class="fas ${tier.icon}"></i> ${tier.label}</span>
                <strong>${formatMAD(recommendedBudget)}</strong>
            </div>
            <div class="gf-result-title">${selectedGameNames.length ? selectedGameNames.join(' + ') : 'Gaming profile'}</div>
            <div class="gf-metrics">
                <div>
                    <span>Budget fit</span>
                    <strong>${budgetText}</strong>
                </div>
                <div>
                    <span>FPS estimate</span>
                    <strong>${fps ? `${fps.average} FPS avg` : 'Build pending'}</strong>
                </div>
                <div>
                    <span>1% style low</span>
                    <strong>${fps ? `${fps.low} FPS` : 'Select build'}</strong>
                </div>
            </div>
            <div class="gf-note ${fps && !fps.meetsTarget ? 'warn' : ''}">
                ${fps
                    ? (fps.meetsTarget
                        ? `The current build is on pace for ${finderState.resolution} at ${finderState.targetFps} FPS.`
                        : `The current build may need a stronger GPU or lower settings for ${finderState.targetFps} FPS.`)
                    : 'Run the finder to auto-select compatible parts from your catalog.'}
            </div>
        `;
    }

    function syncFinderEstimator() {
        if (typeof FPSEstimator !== 'undefined') {
            if (FPSEstimator.setResolution) FPSEstimator.setResolution(finderState.resolution);
            if (FPSEstimator.setGames) FPSEstimator.setGames(finderState.games);
        }
    }

    function applyGamingFinder() {
        useCase = 'gaming';
        activePreset = 'finder';
        targetBudget = finderState.budget;

        const recommendedBudget = getFinderRecommendedBudget();
        const buildBudget = Math.max(finderState.budget, Math.round(recommendedBudget * 0.9 / 500) * 500);
        autoBuild('gaming', Math.min(FINDER_RECOMMENDATION.maxBudget, buildBudget), false);

        buildName = `${finderState.resolution} Gaming Build`;
        const nameInput = document.getElementById('buildNameInput');
        if (nameInput) nameInput.value = buildName;

        syncFinderEstimator();
        updateFinderPreview();
        showToast(`Finder matched a ${finderState.resolution} gaming build.`, 'success');

        // Automatically switch back to PC Builder and open the workspace
        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function resetGamingFinder() {
        finderState = {
            games: ['cyberpunk', 'gta5'],
            resolution: '1440p',
            targetFps: 120,
            budget: 18000,
        };
        renderGamingFinder();
        syncFinderEstimator();
        showToast('Finder reset.', 'success');
    }

    function renderBuilderToolPanels() {
        populateProductSelect('psuCpuSelect', 'cpu', 'Select CPU');
        populateProductSelect('psuGpuSelect', 'gpu', 'Select GPU');
        populateProductSelect('memoryMotherboardSelect', 'motherboard', 'Auto / not sure');

        [
            'psuCpuSelect', 'psuGpuSelect', 'psuMotherboardSelect', 'psuRamSelect',
            'psuSsdCount', 'psuHddCount', 'psuFanCount', 'psuHeadroomSelect'
        ].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', updatePowerSupplyCalculator);
        });

        ['memoryPlatformSelect', 'memoryMotherboardSelect', 'memoryUseSelect', 'memoryCapacitySelect'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', updateMemoryFinder);
        });

        updatePowerSupplyCalculator();
        updateMemoryFinder();
    }

    function populateProductSelect(selectId, category, placeholder) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const options = allProducts
            .filter(product => product.category === category && product.inStock)
            .sort((a, b) => a.price - b.price)
            .map(product => `<option value="${product.id}">${product.name}</option>`)
            .join('');
        select.innerHTML = `<option value="">${placeholder}</option>${options}`;
    }

    function selectedProductFrom(selectId) {
        const value = parseInt(document.getElementById(selectId)?.value || '', 10);
        return value ? allProducts.find(product => product.id === value) || null : null;
    }

    async function updatePowerSupplyCalculator() {
        const result = document.getElementById('psuCalculatorResult');
        if (!result) return;
        const cpu = selectedProductFrom('psuCpuSelect');
        const gpu = selectedProductFrom('psuGpuSelect');
        const motherboardW = parseInt(document.getElementById('psuMotherboardSelect')?.value || '0', 10);
        const ramW = parseInt(document.getElementById('psuRamSelect')?.value || '0', 10);
        const ssdCount = clampNumber(document.getElementById('psuSsdCount')?.value, 0, 8);
        const hddCount = clampNumber(document.getElementById('psuHddCount')?.value, 0, 8);
        const fanCount = clampNumber(document.getElementById('psuFanCount')?.value, 0, 12);
        const headroom = parseFloat(document.getElementById('psuHeadroomSelect')?.value || '1.25');
        
        let componentLoad = motherboardW + ramW + (ssdCount * 8) + (hddCount * 12) + (fanCount * 4);
        let recommended = 0;
        let clearanceIssues = [];

        // Fetch precise wattage and clearance from backend engine
        const productIds = [];
        if (cpu) productIds.push(cpu.id);
        if (gpu) productIds.push(gpu.id);
        if (selectedComponents.motherboard) productIds.push(selectedComponents.motherboard.id);
        if (selectedComponents.cooling) productIds.push(selectedComponents.cooling.id);
        if (selectedComponents.case) productIds.push(selectedComponents.case.id);
        
        if (productIds.length > 0) {
            try {
                const res = await fetch('api/builder-logic.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'validate', products: productIds })
                });
                const data = await res.json();
                
                if (data.success && data.wattage && data.wattage.total_tdp > 0) {
                    componentLoad += data.wattage.total_tdp;
                    const backendRecommended = data.wattage.recommended_psu || 0;
                    recommended = Math.max(backendRecommended, getNearestPsuTier(componentLoad * headroom));
                    clearanceIssues = data.clearance_issues || [];
                } else {
                    // DB not populated yet or validation failed silently -> fallback to client math
                    componentLoad += (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0);
                    recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
                }
            } catch (e) {
                console.error("Backend validation failed", e);
                // Fallback to client math
                componentLoad += (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0);
                recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
            }
        } else {
            recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
        }

        /* ORIGINAL CODE:
        const componentLoad = (cpu ? extractWattage(cpu, 'cpu') : 0) + (gpu ? extractWattage(gpu, 'gpu') : 0) + motherboardW + ramW + (ssdCount * 8) + (hddCount * 12) + (fanCount * 4);
        const recommended = componentLoad > 0 ? getNearestPsuTier(componentLoad * headroom) : 0;
        */

        const amps = recommended ? Math.ceil(recommended / 12) : 0;
        const matchingPsus = recommended ? allProducts
            .filter(product => product.category === 'psu' && product.inStock && extractWattage(product, 'psu') >= recommended)
            .sort((a, b) => a.price - b.price || (b.rating || 0) - (a.rating || 0))
            .slice(0, 3) : [];

        let clearanceHtml = '';
        if (clearanceIssues.length > 0) {
            clearanceHtml = `<div class="tool-note" style="color:var(--danger); border-left:3px solid var(--danger); padding-left:10px;">
                <strong><i class="fas fa-exclamation-triangle"></i> Compatibility Issues Detected:</strong><br>
                ${clearanceIssues.join('<br>')}
            </div>`;
        }

        result.innerHTML = `
            <div class="tool-result-top">
                <span class="gf-pill ${recommended ? 'good' : 'warn'}"><i class="fas fa-bolt"></i> Suggested PSU</span>
                <strong>${recommended ? `${recommended}W+` : 'Select parts'}</strong>
            </div>
            ${clearanceHtml}
            <div class="tool-meter"><span style="width:${recommended ? Math.min(100, (componentLoad / recommended) * 100) : 0}%"></span></div>
            <div class="tool-metrics">
                <div><span>Estimated load</span><strong>${componentLoad}W</strong></div>
                <div><span>12V rail target</span><strong>${amps ? `${amps}A+` : '-'}</strong></div>
                <div><span>Headroom</span><strong>${Math.round((headroom - 1) * 100)}%</strong></div>
            </div>
            <div class="tool-suggestions">
                <h4>Matching PSUs</h4>
                ${matchingPsus.length ? matchingPsus.map(product => `
                    <div class="tool-product-card">
                        <button class="tool-product" onclick="PCBuilder.applyPsuChoice(${product.id})" title="Select as PSU for current Build">
                            <img src="${productImage(product)}" alt="${product.name}">
                            <span><strong>${product.name}</strong><em>${formatMAD(product.price)} - ${extractWattage(product, 'psu')}W</em></span>
                        </button>
                        <button class="tool-product-cart-btn" onclick="PCBuilder.addSingleToCart(${product.id})" title="Add directly to cart" aria-label="Add to cart">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                `).join('') : '<p>Select CPU/GPU details to see compatible PSUs.</p>'}
            </div>
        `;
    }

    function useCurrentBuildForPsu() {
        setSelectValue('psuCpuSelect', selectedComponents.cpu?.id || '');
        setSelectValue('psuGpuSelect', selectedComponents.gpu?.id || '');
        const boardFactor = String(selectedComponents.motherboard?.specs?.['Form Factor'] || '').toUpperCase();
        const boardWatts = boardFactor.includes('MINI') ? '45' : boardFactor.includes('MICRO') ? '55' : boardFactor.includes('E-ATX') ? '80' : selectedComponents.motherboard ? '65' : '0';
        setSelectValue('psuMotherboardSelect', boardWatts);
        const ramCapacity = getCapacityGB(selectedComponents.ram);
        const ramType = getMemoryType(selectedComponents.ram);
        const ramWatts = ramCapacity >= 128 ? '28' : ramCapacity >= 64 ? '18' : ramType === 'DDR5' ? '12' : ramCapacity ? '10' : '0';
        setSelectValue('psuRamSelect', ramWatts);
        setInputValue('psuSsdCount', selectedComponents.storage ? 1 : 0);
        updatePowerSupplyCalculator();
        showToast('Power calculator synced with your current build.', 'success');
    }

    function addSingleToCart(productId) {
        const product = allProducts.find(item => item.id === productId);
        if (!product) return;
        if (typeof Cart !== 'undefined' && Cart.add) {
            Cart.add(product);
            showToast(`${product.name} added to cart!`, 'success');
        } else {
            showToast('Unable to add item to cart.', 'error');
        }
    }

    function applyPsuChoice(productId) {
        const product = allProducts.find(item => item.id === productId && item.category === 'psu');
        if (!product) return;
        selectedComponents.psu = product;
        currentStep = STEPS.findIndex(step => step.key === 'psu');
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(`${product.name} added as your PSU.`, 'success');

        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function updateMemoryFinder() {
        const result = document.getElementById('memoryFinderResult');
        if (!result) return;
        const platform = document.getElementById('memoryPlatformSelect')?.value || '';
        const board = selectedProductFrom('memoryMotherboardSelect');
        const workload = document.getElementById('memoryUseSelect')?.value || 'gaming';
        const minCapacity = parseInt(document.getElementById('memoryCapacitySelect')?.value || '0', 10);
        const requiredType = getRequiredMemoryType(platform, board);
        const workloadCapacity = workload === 'creator' ? 64 : workload === 'streaming' ? 32 : workload === 'office' ? 16 : 32;
        const capacityTarget = Math.max(minCapacity, workloadCapacity);
        const matches = allProducts
            .filter(product => product.category === 'ram' && product.inStock)
            .filter(product => !requiredType || getMemoryType(product) === requiredType)
            .filter(product => !capacityTarget || getCapacityGB(product) >= capacityTarget)
            .sort((a, b) => {
                const aCapacity = getCapacityGB(a);
                const bCapacity = getCapacityGB(b);
                if (aCapacity !== bCapacity) return aCapacity - bCapacity;
                return a.price - b.price || (b.rating || 0) - (a.rating || 0);
            })
            .slice(0, 4);

        result.innerHTML = `
            <div class="tool-result-top">
                <span class="gf-pill ${requiredType ? 'good' : 'warn'}"><i class="fas fa-memory"></i> ${requiredType || 'DDR4 / DDR5'}</span>
                <strong>${capacityTarget || 16}GB+</strong>
            </div>
            <div class="tool-note">${requiredType ? `${requiredType} memory is recommended for this platform.` : 'Select a platform or motherboard to narrow compatibility.'}</div>
            <div class="tool-suggestions memory-picks">
                <h4>Compatible RAM</h4>
                ${matches.length ? matches.map(product => `
                    <div class="tool-product-card">
                        <button class="tool-product" onclick="PCBuilder.applyMemoryChoice(${product.id})" title="Select as RAM for current Build">
                            <img src="${productImage(product)}" alt="${product.name}">
                            <span><strong>${product.name}</strong><em>${getCapacityGB(product)}GB - ${getMemoryType(product) || 'Memory'} - ${formatMAD(product.price)}</em></span>
                        </button>
                        <button class="tool-product-cart-btn" onclick="PCBuilder.addSingleToCart(${product.id})" title="Add directly to cart" aria-label="Add to cart">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                `).join('') : '<p>No RAM in catalog matches those filters yet.</p>'}
            </div>
        `;
    }

    function useCurrentBuildForMemory() {
        const cpuSocket = getSocket(selectedComponents.cpu);
        setSelectValue('memoryPlatformSelect', cpuSocket.includes('AM5') ? 'AM5' : cpuSocket.includes('LGA 1700') ? 'LGA 1700' : '');
        setSelectValue('memoryMotherboardSelect', selectedComponents.motherboard?.id || '');
        updateMemoryFinder();
        showToast('Memory finder synced with your current build.', 'success');
    }

    function applyMemoryChoice(productId) {
        const product = allProducts.find(item => item.id === productId && item.category === 'ram');
        if (!product) return;
        selectedComponents.ram = product;
        currentStep = STEPS.findIndex(step => step.key === 'ram');
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(`${product.name} added as your RAM.`, 'success');

        chooseBuilderPath('custom', false);
        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    function getRequiredMemoryType(platform, board) {
        const boardMemory = getMemoryType(board);
        if (boardMemory) return boardMemory;
        if (platform === 'AM5') return 'DDR5';
        return '';
    }

    function getCapacityGB(product) {
        const text = String(product?.specs?.Capacity || product?.name || '');
        const match = text.match(/(\d+)\s*GB/i);
        return match ? parseInt(match[1], 10) : 0;
    }

    function getNearestPsuTier(watts) {
        const tiers = [450, 550, 650, 750, 850, 1000, 1200, 1500];
        return tiers.find(tier => tier >= watts) || Math.ceil(watts / 100) * 100;
    }

    function clampNumber(value, min, max) {
        const parsed = parseInt(value || min, 10);
        return Math.min(max, Math.max(min, Number.isFinite(parsed) ? parsed : min));
    }

    function setSelectValue(id, value) {
        const input = document.getElementById(id);
        if (input) input.value = String(value);
    }

    function setInputValue(id, value) {
        const input = document.getElementById(id);
        if (input) input.value = String(value);
    }

    function getStepBudgetWeight(stepKey) {
        const weights = {
            cpu: useCase === 'budget' ? 0.12 : useCase === 'editing' ? 0.28 : 0.22,
            motherboard: useCase === 'budget' ? 0.17 : 0.12,
            gpu: useCase === 'budget' ? 0.32 : useCase === 'office' ? 0.18 : useCase === 'editing' ? 0.30 : 0.42,
            ram: useCase === 'budget' ? 0.11 : useCase === 'editing' || useCase === 'streaming' ? 0.14 : 0.10,
            storage: useCase === 'budget' ? 0.09 : 0.10,
            psu: useCase === 'budget' ? 0.12 : 0.09,
            cooling: useCase === 'budget' ? 0.04 : 0.07,
            monitor: 0.20, // Baseline weight relative to the PC budget
        };
        return weights[stepKey] || 0.12;
    }

    function getProductSearchText(product) {
        return [
            product.name,
            product.brand,
            product.category,
            ...Object.values(product.specs || {}),
        ].join(' ').toLowerCase();
    }

    function getRecommendedScore(product, stepKey, target) {
        const compat = checkCompatibility(stepKey, product);
        const selectedBoost = selectedComponents[stepKey]?.id === product.id ? 5000 : 0;
        const stockBoost = product.inStock ? 2500 : -2500;
        const compatBoost = compat.compatible ? 2500 : -4000;
        const priceFit = -Math.abs(Number(product.price || 0) - target) / 10;
        const ratingBoost = Number(product.rating || 0) * 350;
        return selectedBoost + stockBoost + compatBoost + ratingBoost + priceFit;
    }

    function getFilteredProducts(products, stepKey) {
        const query = componentFilters.query.trim().toLowerCase();
        const target = targetBudget * getStepBudgetWeight(stepKey);
        return products
            .filter(product => !componentFilters.stockOnly || product.inStock)
            .filter(product => !query || getProductSearchText(product).includes(query))
            .sort((a, b) => {
                if (componentFilters.sort === 'price-asc') return a.price - b.price;
                if (componentFilters.sort === 'price-desc') return b.price - a.price;
                if (componentFilters.sort === 'wattage') return extractWattage(a, stepKey) - extractWattage(b, stepKey);
                return getRecommendedScore(b, stepKey, target) - getRecommendedScore(a, stepKey, target);
            });
    }

    function getComponentFilterLabel(totalCount, visibleCount, stepLabel) {
        if (componentFilters.query) return `${visibleCount} of ${totalCount} ${stepLabel} matches`;
        return `${visibleCount} ${stepLabel} options`;
    }

    // ── Render Current Step Components ────────────────────────
    function renderCurrentStep() {
        const panel = document.getElementById('componentPanel');
        if (!panel) return;

        const step = STEPS[currentStep];
        const allForStep = allProducts.filter(p => p.category === step.category);
        const filtered = getFilteredProducts(allForStep, step.key);
        const visibleLabel = getComponentFilterLabel(allForStep.length, filtered.length, step.label);
        const stepTarget = targetBudget * getStepBudgetWeight(step.key);

        let panelHTML = `
            <div class="component-panel-head">
                <div>
                    <h2><i class="fas ${step.icon}"></i> Select ${step.label}</h2>
                    <p class="panel-desc">Choose a ${step.label.toLowerCase()} for your build. ${getStepHint(step.key)}</p>
                </div>
                <div class="step-budget-chip">
                    <span>Target</span>
                    <strong>${formatMAD(stepTarget)}</strong>
                </div>
            </div>
            <div class="component-toolbar">
                <label class="component-search">
                    <i class="fas fa-search"></i>
                    <input type="search" id="componentSearchInput" placeholder="Search ${step.label.toLowerCase()}..." value="${escapeHTML(componentFilters.query)}">
                </label>
                <label class="component-select">
                    <span>Sort</span>
                    <select id="componentSortSelect">
                        <option value="recommended" ${componentFilters.sort === 'recommended' ? 'selected' : ''}>Recommended</option>
                        <option value="price-asc" ${componentFilters.sort === 'price-asc' ? 'selected' : ''}>Price: low to high</option>
                        <option value="price-desc" ${componentFilters.sort === 'price-desc' ? 'selected' : ''}>Price: high to low</option>
                        <option value="wattage" ${componentFilters.sort === 'wattage' ? 'selected' : ''}>Lowest wattage</option>
                    </select>
                </label>
                <label class="stock-toggle">
                    <input type="checkbox" id="stockOnlyToggle" ${componentFilters.stockOnly ? 'checked' : ''}>
                    <span>In stock only</span>
                </label>
                <span class="component-count">${visibleLabel}</span>
            </div>
        `;

        if (allForStep.length === 0) {
            panelHTML += `
                <div class="empty-step">
                    <i class="fas ${step.icon}"></i>
                    <p>No ${step.label} products available in catalog.</p>
                </div>
            `;
        } else if (filtered.length === 0) {
            panelHTML += `
                <div class="empty-step">
                    <i class="fas fa-filter-circle-xmark"></i>
                    <p>No ${step.label} products match the current filters.</p>
                    <button class="btn-build btn-save-build" id="clearComponentFiltersBtn" type="button">Clear filters</button>
                </div>
            `;
        } else {
            panelHTML += '<div class="component-grid">';
            filtered.forEach(product => {
                const isSelected = selectedComponents[step.key]?.id === product.id;
                const compat = checkCompatibility(step.key, product);
                let cls = 'component-card';
                if (isSelected) cls += ' selected';
                if (!compat.compatible) cls += ' incompatible';

                const wattage = extractWattage(product, step.key);
                const specTags = Object.entries(product.specs || {}).slice(0, 3).map(([k, v]) =>
                    `<span class="cc-spec-tag">${v}</span>`
                ).join('');
                const recommended = compat.compatible && product.inStock && Math.abs(product.price - stepTarget) <= stepTarget * 0.35;

                panelHTML += `
                    <div class="${cls}" data-product-id="${product.id}" role="button" tabindex="${compat.compatible && product.inStock ? '0' : '-1'}" aria-pressed="${isSelected}">
                        ${recommended ? '<div class="cc-badge">Recommended fit</div>' : ''}
                        <img src="${productImage(product)}" alt="${product.name}" class="cc-image" onerror="this.src='images/products/placeholder-storage.svg'">
                        <div class="cc-brand">${product.brand || ''}</div>
                        <div class="cc-name">${product.name}</div>
                        <div class="cc-specs">${specTags}</div>
                        ${!product.inStock ? '<div class="cc-out-of-stock"><i class="fas fa-ban"></i> Out of stock</div>' : ''}
                        <div class="cc-price">${formatMAD(product.price)}</div>
                        ${wattage > 0 ? `<div class="cc-wattage"><i class="fas fa-bolt"></i> ~${wattage}W</div>` : ''}
                        ${!compat.compatible ? `<div class="cc-compat-warn"><i class="fas fa-exclamation-triangle"></i> ${compat.reason}</div>` : ''}
                        <span class="cc-action">${isSelected ? 'Selected' : compat.compatible && product.inStock ? 'Select part' : 'Unavailable'}</span>
                    </div>
                `;
            });
            panelHTML += '</div>';
        }

        panelHTML += `
            <div class="step-nav-actions">
                <button class="btn-build btn-save-build" id="prevStepBtn" ${currentStep === 0 ? 'disabled style="opacity:0.3"' : ''}>
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button class="btn-build btn-add-all" id="nextStepBtn" ${currentStep === STEPS.length - 1 ? 'disabled style="opacity:0.3"' : ''}>
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        `;

        panel.innerHTML = panelHTML;

        const searchInput = document.getElementById('componentSearchInput');
        const sortSelect = document.getElementById('componentSortSelect');
        const stockToggle = document.getElementById('stockOnlyToggle');
        const clearFiltersBtn = document.getElementById('clearComponentFiltersBtn');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                componentFilters.query = searchInput.value;
                renderCurrentStep();
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                componentFilters.sort = sortSelect.value;
                renderCurrentStep();
            });
        }
        if (stockToggle) {
            stockToggle.addEventListener('change', () => {
                componentFilters.stockOnly = stockToggle.checked;
                renderCurrentStep();
            });
        }
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                componentFilters = { query: '', sort: 'recommended', stockOnly: true };
                renderCurrentStep();
            });
        }

        function handleProductSelect(card) {
            const prodId = parseInt(card.dataset.productId);
            const product = allProducts.find(p => p.id === prodId);
            if (!product || !product.inStock) return;

            const compat = checkCompatibility(step.key, product);
            if (!compat.compatible) return;

            if (selectedComponents[step.key]?.id === prodId) {
                delete selectedComponents[step.key];
                showToast(`${step.label} removed.`, 'success');
            } else {
                selectedComponents[step.key] = product;
                showToast(`${product.name} selected.`, 'success');
            }

            renderWizardSteps();
            renderCurrentStep();
            updateSummary();
        }

        panel.querySelectorAll('.component-card').forEach(card => {
            card.addEventListener('click', () => handleProductSelect(card));
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    handleProductSelect(card);
                }
            });
        });

        const prevBtn = document.getElementById('prevStepBtn');
        const nextBtn = document.getElementById('nextStepBtn');
        if (prevBtn) prevBtn.addEventListener('click', () => { if (currentStep > 0) { currentStep--; renderWizardSteps(); renderCurrentStep(); updateBuildGuide(); } });
        if (nextBtn) nextBtn.addEventListener('click', () => { if (currentStep < STEPS.length - 1) { currentStep++; renderWizardSteps(); renderCurrentStep(); updateBuildGuide(); } });

        updateBuildGuide();
    }

    // ── Step Hints ───────────────────────────────────────────
    function getStepHint(key) {
        const hints = {
            cpu: 'The brain of your PC. Choose based on your workload.',
            motherboard: 'Match the board socket and memory type to your CPU and RAM.',
            gpu: 'Critical for gaming and creative work.',
            ram: 'More RAM means better multitasking.',
            storage: 'Fast NVMe SSDs for quick boot and load times.',
            psu: 'Ensure enough wattage for all components.',
            cooling: 'Keep your system cool and quiet.',
            monitor: 'The window to your PC. Match resolution and refresh rate to your GPU.',
        };
        return hints[key] || '';
    }

    // ── Compatibility Check ──────────────────────────────────
    function checkCompatibility(stepKey, product) {
        const result = { compatible: true, reason: '' };

        // Basic out-of-stock check
        if (!product.inStock) {
            return { compatible: false, reason: 'Out of stock' };
        }

        if (stepKey === 'cpu' && selectedComponents['motherboard']) {
            const cpuSocket = getSocket(product);
            const boardSocket = getSocket(selectedComponents['motherboard']);
            if (cpuSocket && boardSocket && cpuSocket !== boardSocket) {
                return { compatible: false, reason: `Requires ${boardSocket}` };
            }
        }

        if (stepKey === 'motherboard') {
            const boardSocket = getSocket(product);
            const boardMemory = getMemoryType(product);

            if (selectedComponents['cpu']) {
                const cpuSocket = getSocket(selectedComponents['cpu']);
                if (cpuSocket && boardSocket && cpuSocket !== boardSocket) {
                    return { compatible: false, reason: `${cpuSocket} CPU needs ${cpuSocket} board` };
                }
            }

            if (selectedComponents['ram']) {
                const ramType = getMemoryType(selectedComponents['ram']);
                if (ramType && boardMemory && ramType !== boardMemory) {
                    return { compatible: false, reason: `${boardMemory} board needs ${boardMemory} RAM` };
                }
            }
        }

        // CPU + RAM socket compatibility (simplified)
        if (stepKey === 'cpu' && selectedComponents['ram']) {
            const cpuSocket = product.specs?.Socket || '';
            const ramSpeed = selectedComponents['ram'].specs?.Speed || '';
            if (cpuSocket.includes('AM5') && ramSpeed.includes('DDR4')) {
                return { compatible: false, reason: 'AM5 requires DDR5' };
            }
        }

        if (stepKey === 'ram' && selectedComponents['cpu']) {
            const cpuSocket = selectedComponents['cpu'].specs?.Socket || '';
            const ramSpeed = product.specs?.Speed || '';
            // AM5 requires DDR5, LGA 1700 supports DDR4/DDR5
            if (cpuSocket.includes('AM5') && ramSpeed.includes('DDR4')) {
                return { compatible: false, reason: 'AM5 requires DDR5' };
            }
        }

        if (stepKey === 'ram' && selectedComponents['motherboard']) {
            const boardMemory = getMemoryType(selectedComponents['motherboard']);
            const ramType = getMemoryType(product);
            if (ramType && boardMemory && ramType !== boardMemory) {
                return { compatible: false, reason: `${boardMemory} motherboard requires ${boardMemory} RAM` };
            }
        }

        // PSU wattage check
        if (stepKey === 'psu') {
            const totalWattage = calculateTotalWattage(true); // exclude PSU
            const psuWattage = extractWattageFromSpec(product.specs?.Wattage || '');
            if (psuWattage > 0 && psuWattage < totalWattage * 1.1) {
                return { compatible: false, reason: `Need ${Math.ceil(totalWattage * 1.2)}W+ PSU` };
            }
        }

        if (stepKey === 'cooling' && selectedComponents['cpu']) {
            const cpuTdp = extractWattage(selectedComponents['cpu'], 'cpu');
            const coolerTdp = extractWattageFromSpec(product.specs?.['Max TDP'] || '');
            if (coolerTdp > 0 && coolerTdp < cpuTdp) {
                return { compatible: false, reason: `Need ${cpuTdp}W+ cooling` };
            }
        }

        return result;
    }

    // ── Wattage Extraction ───────────────────────────────────
    function getSocket(product) {
        return String(product?.specs?.Socket || '').trim().toUpperCase();
    }

    function getMemoryType(product) {
        const specs = product?.specs || {};
        const memoryText = `${specs.Memory || ''} ${specs.Speed || ''}`.toUpperCase();
        if (memoryText.includes('DDR5')) return 'DDR5';
        if (memoryText.includes('DDR4')) return 'DDR4';
        return '';
    }

    function extractWattage(product, stepKey) {
        // Try extracting from specs
        const specs = product.specs || {};
        const tdp = specs.TDP || specs.Wattage || '';
        const val = extractWattageFromSpec(tdp);
        return val || DEFAULT_WATTAGE[stepKey] || 0;
    }

    function extractWattageFromSpec(specStr) {
        if (!specStr) return 0;
        const match = String(specStr).replace(/[,\s]/g, '').match(/(\d+)\s*W/i);
        return match ? parseInt(match[1]) : 0;
    }

    // ── Calculate Total Wattage ──────────────────────────────
    function calculateTotalWattage(excludePSU = false) {
        let total = 0;
        for (const [key, product] of Object.entries(selectedComponents)) {
            if (excludePSU && key === 'psu') continue;
            total += extractWattage(product, key);
        }
        return total;
    }

    function calculateServiceTotal() {
        return Object.keys(selectedServices).reduce((sum, key) => {
            return sum + (BUILD_SERVICES[key]?.price || 0);
        }, 0);
    }

    function bindServiceOptions() {
        document.querySelectorAll('.service-checkbox').forEach(input => {
            input.addEventListener('change', () => {
                const service = BUILD_SERVICES[input.value];
                if (!service) return;
                if (input.checked) selectedServices[input.value] = service;
                else delete selectedServices[input.value];
                updateSummary();
            });
        });
    }

    function chooseBuilderPath(path, notify = true) {
        builderPath = path === 'china' ? 'china' : path === 'prebuilt' ? 'prebuilt' : 'custom';
        const workspace = document.getElementById('pcBuilderWorkspace');
        const startChoice = document.getElementById('buildStartChoice');
        if (workspace) {
            workspace.classList.remove('is-hidden', 'path-prebuilt', 'path-custom', 'path-china');
            workspace.classList.add(`path-${builderPath}`);
        }
        if (startChoice) startChoice.classList.add('is-minimized');

        if (builderPath === 'china') {
            selectedPlatform = 'x99';
            useCase = 'budget';
            activePreset = 'cnultra';
            targetBudget = 4200;
            document.querySelectorAll('.ps-card').forEach(card => {
                card.classList.toggle('active', card.dataset.platform === 'x99');
            });
            renderUseCaseBar();
            autoBuild('budget', targetBudget, false);
        }

        const target = builderPath === 'prebuilt'
            ? document.getElementById('useCaseBar')
            : builderPath === 'china'
                ? document.getElementById('buildGuideBar')
            : document.querySelector('.platform-selector');
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        updateBuildGuide();
        if (notify) {
            const message = builderPath === 'china'
                ? 'Ultra cheap CN value build prepared.'
                : builderPath === 'prebuilt'
                    ? 'Choose a recommended build to start.'
                    : 'Custom builder unlocked.';
            showToast(message, 'success');
        }
    }

    function getComponentsTotal() {
        return Object.values(selectedComponents).reduce((sum, product) => sum + Number(product.price || 0), 0);
    }

    function getBuildTotal() {
        return getComponentsTotal() + calculateServiceTotal();
    }

    function getSelectedCount() {
        return STEPS.filter(step => selectedComponents[step.key]).length;
    }

    function getNextMissingStep() {
        return STEPS.find(step => !selectedComponents[step.key]) || null;
    }

    function getStepIndex(key) {
        return STEPS.findIndex(step => step.key === key);
    }

    function jumpToStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= STEPS.length) return;
        currentStep = stepIndex;
        renderWizardSteps();
        renderCurrentStep();
        document.getElementById('componentPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function updateBuildGuide() {
        const guide = document.getElementById('buildGuideBar');
        const dock = document.getElementById('mobileBuildDock');
        const selectedCount = getSelectedCount();
        const progress = Math.round((selectedCount / STEPS.length) * 100);
        const nextMissing = getNextMissingStep();
        const nextLabel = nextMissing ? nextMissing.label : 'Review build';
        const current = STEPS[currentStep];
        const total = getBuildTotal();
        const budgetDelta = targetBudget - total;
        const budgetText = budgetDelta >= 0
            ? `${formatMAD(budgetDelta)} under target`
            : `${formatMAD(Math.abs(budgetDelta))} over target`;

        if (guide) {
            guide.innerHTML = `
                <div class="bgb-progress" aria-label="Build completion ${progress}%">
                    <span style="width:${progress}%"></span>
                </div>
                <div class="bgb-copy">
                    <span class="gf-kicker"><i class="fas fa-route"></i> Step ${currentStep + 1} of ${STEPS.length}</span>
                    <strong>${selectedCount ? `${selectedCount} parts selected` : 'Start with your first part'}</strong>
                    <p>${nextMissing ? `Next best step: choose ${nextLabel}.` : 'All required steps are filled. Review compatibility and add services if needed.'}</p>
                </div>
                <div class="bgb-stats">
                    <div><span>Total</span><strong>${formatMAD(total)}</strong></div>
                    <div><span>Budget</span><strong class="${budgetDelta < 0 ? 'over' : ''}">${budgetText}</strong></div>
                    <div><span>Current</span><strong>${current.label}</strong></div>
                </div>
                <button class="guide-action" type="button" data-jump-step="${nextMissing ? getStepIndex(nextMissing.key) : currentStep}">
                    ${nextMissing ? `Choose ${nextLabel}` : 'Review Summary'} <i class="fas fa-arrow-right"></i>
                </button>
            `;

            guide.querySelector('.guide-action')?.addEventListener('click', () => {
                if (nextMissing) jumpToStep(getStepIndex(nextMissing.key));
                else focusSummary();
            });
        }

        if (dock) {
            dock.innerHTML = `
                <span><i class="fas fa-list-check"></i> ${selectedCount}/${STEPS.length} parts</span>
                <strong>${formatMAD(total)}</strong>
                <em>${nextMissing ? `Next: ${nextLabel}` : 'Ready to review'}</em>
            `;
        }
    }

    function focusSummary() {
        document.querySelector('.build-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function getAssistantContext() {
        const selected = {};
        STEPS.forEach(step => {
            const product = selectedComponents[step.key];
            selected[step.key] = product ? {
                id: product.id,
                name: product.name,
                brand: product.brand,
                category: product.category,
                price: Number(product.price || 0),
                wattage: extractWattage(product, step.key),
                specs: product.specs || {},
            } : null;
        });

        const missing = STEPS.filter(step => !selectedComponents[step.key]).map(step => step.label);
        return {
            page: 'builder',
            builderPath,
            platform: selectedPlatform,
            useCase,
            activePreset,
            targetBudget,
            selected,
            missing,
            selectedCount: getSelectedCount(),
            totalPrice: getBuildTotal(),
            totalWattage: calculateTotalWattage(true),
            recommendedPsu: getPSURecommendation(calculateTotalWattage(true)),
            services: Object.values(selectedServices).map(service => ({
                id: service.id,
                name: service.name,
                price: service.price,
            })),
        };
    }

    function applyAssistantProducts(products = []) {
        if (!Array.isArray(products) || !products.length) return false;
        let applied = 0;

        products.forEach(productLike => {
            const product = allProducts.find(item => Number(item.id) === Number(productLike.id));
            if (!product || !product.inStock) return;
            const step = STEPS.find(item => item.category === product.category || item.key === product.category);
            if (!step) return;
            const compat = checkCompatibility(step.key, product);
            if (!compat.compatible) return;
            selectedComponents[step.key] = product;
            applied++;
        });

        if (!applied) {
            showToast('No compatible AI picks could be applied.', 'error');
            return false;
        }

        chooseBuilderPath('custom', false);
        currentStep = Math.max(0, STEPS.findIndex(step => !selectedComponents[step.key]));
        if (currentStep < 0) currentStep = 0;
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(`Applied ${applied} AI pick${applied > 1 ? 's' : ''} to your build.`, 'success');
        return true;
    }

    function getCompatibilityReport() {
        const report = [];
        const cpu = selectedComponents.cpu;
        const ram = selectedComponents.ram;
        const psu = selectedComponents.psu;
        const cooling = selectedComponents.cooling;
        const gpu = selectedComponents.gpu;
        const motherboard = selectedComponents.motherboard;
        const totalWatt = calculateTotalWattage(true);
        const recommendedPsu = getPSURecommendation(totalWatt);

        if (cpu && motherboard) {
            const cpuSocket = getSocket(cpu);
            const boardSocket = getSocket(motherboard);
            report.push({
                status: cpuSocket && boardSocket && cpuSocket !== boardSocket ? 'bad' : 'ok',
                title: 'CPU / motherboard',
                text: cpuSocket && boardSocket && cpuSocket !== boardSocket
                    ? `${cpu.name} needs ${cpuSocket}, but ${motherboard.name} is ${boardSocket}.`
                    : `${motherboard.name} matches ${cpuSocket || 'the selected CPU socket'}.`
            });
        } else {
            report.push({ status: 'warn', title: 'CPU / motherboard', text: 'Select a CPU and motherboard to validate socket support.' });
        }

        if (cpu && ram) {
            const cpuSocket = cpu.specs?.Socket || '';
            const ramSpeed = ram.specs?.Speed || '';
            const needsDdr5 = cpuSocket.includes('AM5');
            report.push({
                status: needsDdr5 && ramSpeed.includes('DDR4') ? 'bad' : 'ok',
                title: 'CPU / RAM match',
                text: needsDdr5 && ramSpeed.includes('DDR4')
                    ? 'AM5 CPUs require DDR5 memory.'
                    : `${cpuSocket || 'Selected CPU'} works with ${ramSpeed || 'selected memory'}.`
            });
        } else {
            report.push({ status: 'warn', title: 'CPU / RAM match', text: 'Select a CPU and RAM kit to validate memory type.' });
        }

        if (motherboard && ram) {
            const boardMemory = getMemoryType(motherboard);
            const ramType = getMemoryType(ram);
            report.push({
                status: boardMemory && ramType && boardMemory !== ramType ? 'bad' : 'ok',
                title: 'Motherboard / RAM',
                text: boardMemory && ramType && boardMemory !== ramType
                    ? `${motherboard.name} uses ${boardMemory}, but ${ram.name} is ${ramType}.`
                    : `${motherboard.name} supports ${ramType || 'the selected memory kit'}.`
            });
        } else {
            report.push({ status: 'warn', title: 'Motherboard / RAM', text: 'Select a motherboard and RAM kit to validate memory type.' });
        }

        if (cpu && cooling) {
            const cpuTdp = extractWattage(cpu, 'cpu');
            const coolerTdp = extractWattageFromSpec(cooling.specs?.['Max TDP'] || '');
            report.push({
                status: coolerTdp && coolerTdp < cpuTdp ? 'bad' : 'ok',
                title: 'Cooling headroom',
                text: coolerTdp && coolerTdp < cpuTdp
                    ? `Cooler is rated below the CPU load. Pick ${cpuTdp}W+ cooling.`
                    : `${cooling.name} has enough thermal headroom.`
            });
        } else {
            report.push({ status: 'warn', title: 'Cooling headroom', text: 'Select CPU and cooling to check thermal headroom.' });
        }

        if (psu) {
            const psuWattage = extractWattage(psu, 'psu');
            report.push({
                status: psuWattage < recommendedPsu ? 'bad' : 'ok',
                title: 'PSU sizing',
                text: psuWattage < recommendedPsu
                    ? `Current load suggests a ${recommendedPsu}W+ PSU.`
                    : `${psuWattage}W PSU covers the estimated ${totalWatt}W load.`
            });
        } else {
            report.push({ status: 'warn', title: 'PSU sizing', text: `Recommended PSU: ${recommendedPsu}W+.` });
        }

        if (gpu && psu) {
            report.push({ status: 'ok', title: 'GPU power planning', text: `${gpu.name} included in the ${totalWatt}W load estimate.` });
        }

        const hasCaseSelection = STEPS.some(step => step.key === 'case')
            || allProducts.some(product => product.category === 'case' && product.inStock);
        const selectedCase = selectedComponents.case;

        if (selectedCase) {
            report.push({
                status: 'ok',
                title: 'Case clearance',
                text: `${selectedCase.name} is included for final GPU and cooler clearance review.`
            });
        } else {
            report.push({
                status: hasCaseSelection ? 'warn' : 'ok',
                title: 'Case clearance',
                text: hasCaseSelection
                    ? 'Select a case to validate GPU length, motherboard size, and radiator fit.'
                    : 'No case step is required for this preset; enclosure clearance is handled during final build review.'
            });
        }

        return report;
    }

    function updateCompatibilityPanel() {
        const panel = document.getElementById('compatibilityPanel');
        if (!panel) return;

        const report = getCompatibilityReport();
        const icon = { ok: 'fa-circle-check', warn: 'fa-triangle-exclamation', bad: 'fa-circle-xmark' };
        panel.innerHTML = `
            <h4><i class="fas fa-shield-halved"></i> Compatibility Check</h4>
            <div class="compat-list">
                ${report.map(item => `
                    <div class="compat-item ${item.status}">
                        <i class="fas ${icon[item.status]}"></i>
                        <span><strong>${item.title}</strong>${item.text}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // ── PSU Recommendation ───────────────────────────────────
    function getArchitectureIPC(arch) {
        if (!arch) return 1.0;
        const a = arch.toLowerCase();
        if (a.includes('zen 5')) return 1.35;
        if (a.includes('zen 4')) return 1.20;
        if (a.includes('arrow lake')) return 1.30;
        if (a.includes('raptor lake')) return 1.25;
        if (a.includes('alder lake')) return 1.15;
        if (a.includes('zen 3')) return 1.05;
        if (a.includes('broadwell')) return 0.75;
        return 1.0;
    }

    function inferCpuScore(cpu) {
        if (!cpu || !cpu.specs) return 50;
        
        // Extract threads
        let threads = 8;
        const coresStr = String(cpu.specs.Cores || '');
        const threadMatch = coresStr.match(/(\d+)\s*threads?/i);
        if (threadMatch) {
            threads = parseInt(threadMatch[1]);
        } else {
            const coreMatch = coresStr.match(/^(\d+)/);
            if (coreMatch) threads = parseInt(coreMatch[1]) * 2;
        }

        // Extract clock
        const clockStr = String(cpu.specs['Boost Clock'] || '');
        const clockMatch = clockStr.match(/(\d+(?:\.\d+)?)/);
        const clock = clockMatch ? parseFloat(clockMatch[1]) : 4.0;

        // Extract cache
        const cacheStr = String(cpu.specs['L3 Cache'] || '');
        const cacheMatch = cacheStr.match(/(\d+)\s*MB/i);
        let cache = cacheMatch ? parseInt(cacheMatch[1]) : 16;
        if (cacheStr.toLowerCase().includes('3d v-cache')) cache *= 1.5; // X3D bonus

        const ipc = getArchitectureIPC(cpu.specs.Architecture);

        // Score formula based on real specs
        // (Base threads + clock speed multiplier) * IPC * Cache bonus
        const threadScore = Math.min(threads, 16) * 1.5 + Math.max(0, threads - 16) * 0.5;
        const rawScore = (threadScore * clock * ipc) + (cache * 0.2);
        
        // Normalize to ~0-120 scale
        return Math.max(20, Math.min(130, rawScore * 0.85));
    }

    function inferGpuScore(gpu) {
        if (!gpu || !gpu.specs) return 50;
        
        // Extract cores
        let cores = 1000;
        const coreStr = String(gpu.specs['CUDA Cores'] || gpu.specs['Stream Processors'] || '');
        const coreMatch = coreStr.replace(/[^\d.]/g, '');
        if (coreMatch) cores = parseInt(coreMatch);

        // Extract clock
        const clockStr = String(gpu.specs['Boost Clock'] || '');
        const clockMatch = clockStr.match(/(\d+(?:\.\d+)?)/);
        let clock = clockMatch ? parseFloat(clockMatch[1]) : 2.0;
        if (clock > 100) clock = clock / 1000; // normalize to GHz if in MHz

        // Extract VRAM
        const vramStr = String(gpu.specs.VRAM || '');
        const vramMatch = vramStr.match(/(\d+)\s*GB/i);
        const vram = vramMatch ? parseInt(vramMatch[1]) : 8;

        // TFLOPs calculation = Cores * 2 * Clock GHz / 1000
        // Note: AMD and NVIDIA cores aren't 1:1, AMD RDNA3 needs an IPC multiplier vs Ada Lovelace
        let tflops = (cores * 2 * clock) / 1000;
        
        const arch = String(gpu.specs.Architecture || '').toLowerCase();
        if (arch.includes('rdna 3')) tflops *= 1.25; // RDNA 3 stream processors dual issue
        else if (arch.includes('rdna 2')) tflops *= 1.0;
        else if (arch.includes('polaris')) tflops *= 0.6; // older arch penalty

        // Add VRAM bonus
        const vramBonus = vram >= 16 ? 10 : (vram >= 12 ? 5 : (vram <= 8 ? -5 : 0));

        // Normalize score based on TFLOPs (e.g. RTX 4090 ~ 82 TFLOPs -> ~120 score)
        return Math.max(20, Math.min(130, (tflops * 1.3) + vramBonus + 15));
    }

    function calculateBottleneck(cpu, gpu, resolution) {
        if (!cpu || !gpu) return null;
        const cpuScore = inferCpuScore(cpu);
        const gpuScore = inferGpuScore(gpu);
        const gpuPressure = { '1080p': 0.78, '1440p': 0.94, '4K': 1.15 }[resolution] || 0.94;
        const ratio = cpuScore / Math.max(1, gpuScore * gpuPressure);

        if (ratio < 0.88) {
            const percentage = Math.round((1 - ratio) * 100);
            return {
                type: 'cpu',
                label: 'CPU bottleneck',
                percentage,
                score: Math.max(0, 100 - percentage * 2),
                color: 'var(--diagnostic-red)',
                text: `${cpu.name} may hold back ${gpu.name} by about ${percentage}% at ${resolution}.`
            };
        }

        if (ratio > 1.16) {
            const percentage = Math.round(Math.min(35, (ratio - 1) * 70));
            return {
                type: 'gpu',
                label: 'GPU bottleneck',
                percentage,
                score: Math.max(0, 100 - percentage * 1.6),
                color: '#4da3ff',
                text: `${gpu.name} is the limiting part by about ${percentage}% at ${resolution}.`
            };
        }

        return {
            type: 'balanced',
            label: 'Balanced',
            percentage: Math.round(Math.abs(1 - ratio) * 100),
            score: 96,
            color: 'var(--diagnostic-green)',
            text: `${cpu.name} and ${gpu.name} are well matched at ${resolution}.`
        };
    }

    function bottleneckTips(result) {
        if (!result) return 'Select a CPU and GPU to calculate balance at 1080p, 1440p, and 4K.';
        if (result.type === 'cpu') {
            return 'Upgrade the CPU for high-refresh gaming, or save money by choosing a less powerful GPU.';
        }
        if (result.type === 'gpu') {
            return 'Upgrade the GPU for this CPU, or keep the current GPU if the goal is budget efficiency.';
        }
        return 'This pairing is healthy. Spend the next upgrade budget on cooling, storage, or monitor quality.';
    }

    function updateBottleneckPanel() {
        const panel = document.getElementById('bottleneckPanel');
        if (!panel) return;

        const cpu = selectedComponents.cpu;
        const gpu = selectedComponents.gpu;
        const result = calculateBottleneck(cpu, gpu, bottleneckResolution);

        if (!cpu || !gpu) {
            panel.innerHTML = `
                <h4><i class="fas fa-gauge-high"></i> Bottleneck Analyzer</h4>
                <div class="bottleneck-empty">
                    <span>CPU + GPU required</span>
                    <small>Select both parts for real-time balance analysis.</small>
                </div>
            `;
            return;
        }

        const score = Math.round(result.score);
        panel.innerHTML = `
            <h4><i class="fas fa-gauge-high"></i> Bottleneck Analyzer</h4>
            <div class="bottleneck-tabs" role="tablist" aria-label="Resolution">
                ${['1080p', '1440p', '4K'].map(res => `
                    <button type="button" class="${res === bottleneckResolution ? 'active' : ''}" data-bottleneck-res="${res}">${res}</button>
                `).join('')}
            </div>
            <div class="bottleneck-meter">
                <div class="bottleneck-meter-head">
                    <strong>${result.label}</strong>
                    <span>${score}/100</span>
                </div>
                <div class="bottleneck-bar" aria-label="System balance score ${score}">
                    <span style="width:${score}%; background:${result.color};"></span>
                </div>
            </div>
            <p class="bottleneck-message">${result.text}</p>
            <p class="bottleneck-tip"><i class="fas fa-lightbulb"></i> ${bottleneckTips(result)}</p>
        `;

        panel.querySelectorAll('[data-bottleneck-res]').forEach(btn => {
            btn.addEventListener('click', () => {
                bottleneckResolution = btn.dataset.bottleneckRes || '1440p';
                updateBottleneckPanel();
            });
        });
    }



    function getSelectedProductsByBudget(stepKey, maxPrice) {
        const step = STEPS.find(item => item.key === stepKey);
        if (!step) return [];
        return allProducts
            .filter(product => product.category === step.category && product.inStock && Number(product.price || 0) <= maxPrice)
            .filter(product => checkCompatibility(stepKey, product).compatible)
            .sort((a, b) => b.rating - a.rating || b.price - a.price);
    }

    function getHealthScore() {
        const report = getCompatibilityReport();
        const bad = report.filter(item => item.status === 'bad').length;
        const warn = report.filter(item => item.status === 'warn').length;
        const totalWatt = calculateTotalWattage(true);
        const psuWatt = selectedComponents.psu ? extractWattage(selectedComponents.psu, 'psu') : 0;
        const psuHeadroom = psuWatt ? Math.max(0, Math.min(100, Math.round(((psuWatt - totalWatt) / Math.max(1, psuWatt)) * 100))) : 0;
        const balanceResult = calculateBottleneck(selectedComponents.cpu, selectedComponents.gpu, '1440p');
        const balance = balanceResult ? Math.round(balanceResult.score) : 55;
        const storageText = `${selectedComponents.storage?.name || ''} ${Object.values(selectedComponents.storage?.specs || {}).join(' ')}`.toLowerCase();
        const storage = selectedComponents.storage ? (storageText.includes('nvme') || storageText.includes('m.2') ? 92 : 68) : 45;
        const memoryType = getMemoryType(selectedComponents.ram) || getMemoryType(selectedComponents.motherboard);
        const vramStr = String(selectedComponents.gpu?.specs?.VRAM || '');
        const vramMatch = vramStr.replace(',', '.').match(/(\d+(?:\.\d+)?)/);
        const vram = vramMatch ? parseFloat(vramMatch[1]) : 8;
        const future = Math.round((
            (memoryType === 'DDR5' ? 30 : 16) +
            (vram >= 16 ? 25 : vram >= 12 ? 18 : 10) +
            (psuHeadroom >= 30 ? 20 : psuHeadroom >= 18 ? 14 : 8) +
            (selectedComponents.motherboard && getSocket(selectedComponents.motherboard).includes('AM5') ? 25 : 16)
        ));
        const thermals = selectedComponents.cooling ? Math.max(45, Math.min(100, 70 + psuHeadroom - warn * 4)) : 42;
        const value = Math.max(30, Math.min(100, Math.round(96 - Math.max(0, getBuildTotal() - targetBudget) / 180 - bad * 18)));
        const overall = Math.max(0, Math.min(100, Math.round((balance + thermals + storage + future + value) / 5 - bad * 12 - warn * 3)));
        return {
            overall,
            metrics: [
                { label: 'Balance', value: balance },
                { label: 'Thermals', value: Math.round(thermals) },
                { label: 'Storage', value: Math.round(storage) },
                { label: 'Future', value: Math.round(future) },
                { label: 'Value', value: Math.round(value) },
            ],
            psuHeadroom,
        };
    }

    function getNoiseEstimate() {
        let db = 25; // Base case noise floor with idle fans
        let desc = 'Whisper quiet';

        if (selectedComponents.cooling) {
            const coolerName = String(selectedComponents.cooling.name).toLowerCase();
            if (coolerName.includes('water') || coolerName.includes('liquid') || coolerName.includes('aio')) {
                db += 5; // Pump noise + radiator fans
            } else if (coolerName.includes('noctua') || coolerName.includes('be quiet')) {
                db += 3; // Premium air coolers
            } else {
                db += 8; // Standard air coolers
            }
        } else if (selectedComponents.cpu) {
            db += 12; // Assuming stock cooler
        }

        if (selectedComponents.gpu) {
            const gpuName = String(selectedComponents.gpu.name).toLowerCase();
            const gpuWatts = extractWattage(selectedComponents.gpu, 'gpu');
            if (gpuWatts > 300) {
                db += 15; // High end GPUs get loud
            } else if (gpuWatts > 200) {
                db += 10;
            } else {
                db += 6;
            }
            if (gpuName.includes('blower') || gpuName.includes('turbo')) db += 8;
        }

        if (db > 45) desc = 'Loud under load';
        else if (db > 35) desc = 'Audible hum';
        else desc = 'Quiet under load';

        return { db, desc };
    }

    function updateHealthPanel() {
        const panel = document.getElementById('healthPanel');
        if (!panel) return;
        const health = getHealthScore();
        const noise = getNoiseEstimate();
        const selectedCount = getSelectedCount();
        panel.innerHTML = `
            <h4><i class="fas fa-heart-pulse"></i> Build Health Score</h4>
            <div class="health-score-line">
                <strong>${selectedCount ? health.overall : '--'}</strong>
                <span>/100</span>
                <em>${selectedCount ? 'diagnostic confidence' : 'select parts to score'}</em>
            </div>
            <div class="health-metrics">
                ${health.metrics.map(item => `
                    <div>
                        <span>${item.label}</span>
                        <b>${selectedCount ? item.value : '--'}</b>
                        <i style="width:${selectedCount ? item.value : 0}%"></i>
                    </div>
                `).join('')}
            </div>
            <div class="psu-overhead-gauge" style="margin-bottom: 8px;">
                <span>PSU overhead</span>
                <strong>${selectedComponents.psu ? `${health.psuHeadroom}%` : 'Pick PSU'}</strong>
            </div>
            <div class="psu-overhead-gauge">
                <span><i class="fas fa-volume-low"></i> Noise estimate</span>
                <strong>${selectedCount ? `${noise.db} dB <span style="font-size: 0.65rem; color: var(--muted); font-weight: normal; margin-left: 4px;">(${noise.desc})</span>` : '--'}</strong>
            </div>
            <button type="button" class="health-action" id="downgradeBudgetBtn">
                <i class="fas fa-arrow-trend-down"></i> Downgrade to budget
            </button>
        `;

        panel.querySelector('#downgradeBudgetBtn')?.addEventListener('click', downgradeToBudget);
    }

    function getSmartChecklistItems() {
        const items = [];
        const selectedKeys = Object.keys(selectedComponents);
        STEPS.forEach(step => {
            if (!selectedComponents[step.key]) {
                items.push({ tone: 'warn', text: `Missing ${step.label}.` });
            }
        });

        const storageName = String(selectedComponents.storage?.name || '').toLowerCase();
        if (storageName.includes('sata')) items.push({ tone: 'warn', text: 'SATA storage selected, add a SATA data cable if your motherboard bundle is limited.' });
        if (selectedComponents.cpu && !selectedComponents.cooling) items.push({ tone: 'bad', text: 'CPU selected without cooling.' });
        if (selectedComponents.cpu) items.push({ tone: 'ok', text: 'Thermal paste should be in the cart or included with the cooler.' });
        if (selectedComponents.gpu || selectedComponents.psu) items.push({ tone: 'ok', text: 'Confirm GPU power cable count before assembly.' });
        if (selectedComponents.motherboard && !String(selectedComponents.motherboard.name || '').toLowerCase().includes('wifi')) {
            items.push({ tone: 'warn', text: 'No Wi-Fi signal detected in motherboard name. Add a Wi-Fi card if needed.' });
        }
        if (selectedKeys.length >= 6) items.push({ tone: 'ok', text: 'Core build is nearly complete.' });
        return items.slice(0, 8);
    }

    function updateSmartChecklistPanel() {
        const panel = document.getElementById('smartChecklistPanel');
        if (!panel) return;
        const items = getSmartChecklistItems();
        panel.innerHTML = `
            <h4><i class="fas fa-clipboard-check"></i> Oops I Forgot</h4>
            <div class="smart-checklist">
                ${items.map(item => `
                    <div class="${item.tone}">
                        <i class="fas ${item.tone === 'ok' ? 'fa-circle-check' : item.tone === 'bad' ? 'fa-circle-xmark' : 'fa-triangle-exclamation'}"></i>
                        <span>${item.text}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function getAssemblyGuideSteps() {
        const steps = [];
        if (selectedComponents.motherboard) steps.push('Bench-test motherboard, CPU, one RAM stick, and PSU before mounting.');
        if (selectedComponents.cpu) steps.push('Install CPU and check socket orientation before locking the retention arm.');
        if (selectedComponents.storage && String(selectedComponents.storage.name || '').toLowerCase().includes('m.2')) {
            steps.push('Install M.2 SSD before the board goes into the case; add a heatsink if the slot has no shield.');
        }
        if (selectedComponents.cooling && /nh-d15|dark rock|tower/i.test(selectedComponents.cooling.name || '')) {
            steps.push('Mount the tower cooler before final cable routing because RAM clearance gets tight.');
        }
        if (selectedComponents.gpu) steps.push('Install GPU after front-panel and PSU cables are routed.');
        if (selectedComponents.psu) steps.push('Leave at least 25 percent PSU headroom for transient GPU spikes and future upgrades.');
        if (selectedServices.stress) steps.push('Run memory, CPU, and GPU stress tests before packing.');
        if (!steps.length) steps.push('Select parts to generate a component-specific assembly path.');
        return steps.slice(0, 6);
    }

    function updateAssemblyGuidePanel() {
        const panel = document.getElementById('assemblyGuidePanel');
        if (!panel) return;
        panel.innerHTML = `
            <h4><i class="fas fa-timeline"></i> Assembly Timeline</h4>
            <ol class="assembly-steps">
                ${getAssemblyGuideSteps().map(item => `<li>${item}</li>`).join('')}
            </ol>
        `;
    }

    function downgradeToBudget() {
        if (Object.keys(selectedComponents).length === 0) {
            autoBuild('budget', Math.max(4500, Math.round(targetBudget * 0.75)), true);
            return;
        }

        let changed = 0;
        ['gpu', 'cpu', 'motherboard', 'cooling', 'psu'].forEach(key => {
            const current = selectedComponents[key];
            if (!current) return;
            const maxPrice = Number(current.price || 0) * 0.82;
            const options = getSelectedProductsByBudget(key, maxPrice);
            if (options[0] && options[0].id !== current.id) {
                selectedComponents[key] = options[0];
                changed++;
            }
        });

        if (!changed) {
            showToast('No cheaper compatible swaps found in stock.', 'error');
            return;
        }
        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        showToast(`Applied ${changed} budget-focused swap${changed > 1 ? 's' : ''}.`, 'success');
    }

    function getPSURecommendation(totalWattage) {
        const recommended = Math.ceil(totalWattage * 1.25 / 50) * 50; // Round up to nearest 50W with 25% headroom
        const tiers = [450, 550, 650, 750, 850, 1000, 1200];
        for (const t of tiers) {
            if (t >= recommended) return t;
        }
        return recommended;
    }

    // ── Update Summary Sidebar ───────────────────────────────
    function updateSummary() {
        const summaryItems = document.getElementById('summaryItems');
        const wattageValue = document.getElementById('wattageValue');
        const wattageFill = document.getElementById('wattageFill');
        const wattageRec = document.getElementById('wattageRec');
        const totalPrice = document.getElementById('totalPrice');
        const addAllBtn = document.getElementById('addAllBtn');

        if (!summaryItems) return;

        let total = 0;
        let itemCount = 0;
        const serviceTotal = calculateServiceTotal();

        summaryItems.innerHTML = STEPS.map(step => {
            const comp = selectedComponents[step.key];
            if (comp) {
                total += comp.price;
                itemCount++;
            }
            return `
                <div class="summary-item ${comp ? 'has-component' : ''}" data-summary-step="${step.key}" role="button" tabindex="0">
                    <div class="si-icon"><i class="fas ${step.icon}"></i></div>
                    <div class="si-info">
                        <div class="si-label">${step.label}</div>
                        <div class="si-value">${comp ? comp.name : 'Not selected'}</div>
                    </div>
                    ${comp ? `
                        <span class="si-price">${formatMAD(comp.price)}</span>
                        <button class="si-remove" data-key="${step.key}" title="Remove"><i class="fas fa-times"></i></button>
                    ` : ''}
                </div>
            `;
        }).join('') + Object.values(selectedServices).map(service => `
            <div class="summary-item has-component">
                <div class="si-icon"><i class="fas ${service.icon}"></i></div>
                <div class="si-info">
                    <div class="si-label">Service</div>
                    <div class="si-value">${service.name}</div>
                </div>
                <span class="si-price">${formatMAD(service.price)}</span>
            </div>
        `).join('');

        // Remove button handlers
        summaryItems.querySelectorAll('.si-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                delete selectedComponents[btn.dataset.key];
                renderWizardSteps();
                renderCurrentStep();
                updateSummary();
            });
        });

        summaryItems.querySelectorAll('[data-summary-step]').forEach(item => {
            const navigate = () => jumpToStep(getStepIndex(item.dataset.summaryStep));
            item.addEventListener('click', (event) => {
                if (event.target.closest('.si-remove')) return;
                navigate();
            });
            item.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    navigate();
                }
            });
        });

        // Total price
        if (totalPrice) totalPrice.textContent = formatMAD(total + serviceTotal);

        // Wattage meter
        const totalWatt = calculateTotalWattage(true);
        const psuWatt = selectedComponents['psu'] ? extractWattage(selectedComponents['psu'], 'psu') : 0;
        const psuCapacity = psuWatt || 850; // Default PSU for display
        const pct = Math.min(100, (totalWatt / psuCapacity) * 100);

        if (wattageValue) wattageValue.textContent = `${totalWatt}W / ${psuWatt ? psuWatt + 'W' : '???'}`;
        if (wattageFill) {
            wattageFill.style.width = pct + '%';
            wattageFill.className = 'wattage-fill' + (pct > 90 ? ' danger' : pct > 75 ? ' warn' : '');
        }
        if (wattageRec) {
            const rec = getPSURecommendation(totalWatt);
            wattageRec.innerHTML = `Recommended PSU: <strong>${rec}W+</strong>`;
        }

        // Add all to cart button
        if (addAllBtn) addAllBtn.disabled = itemCount === 0;

        updateCompatibilityPanel();
        updateBottleneckPanel();
        updateHealthPanel();
        updateSmartChecklistPanel();
        updateAssemblyGuidePanel();
        updateBuildGuide();

        // Update FPS Estimator if available
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.update();
        }
        updateFinderPreview();
    }

    // ── Add All to Cart ──────────────────────────────────────
    function addAllToCart() {
        const items = Object.values(selectedComponents);
        const services = Object.values(selectedServices);
        if (items.length === 0 && services.length === 0) return;

        items.forEach(product => {
            if (typeof Cart !== 'undefined' && Cart.add) {
                Cart.add(product);
            }
        });

        services.forEach(service => {
            if (typeof Cart !== 'undefined' && Cart.add) {
                Cart.add({
                    id: service.id,
                    name: service.name,
                    brand: 'Maroc PC',
                    category: 'service',
                    price: service.price,
                    image: 'logo.png',
                    inStock: true,
                    specs: { Type: 'Build service' }
                });
            }
        });

        showToast(`Added ${items.length} components${services.length ? ` and ${services.length} services` : ''} to cart!`, 'success');
    }

    // ── Save Build ───────────────────────────────────────────
    async function saveBuild() {
        const components = {};
        for (const [key, prod] of Object.entries(selectedComponents)) {
            components[key] = { id: prod.id, name: prod.name, price: prod.price, brand: prod.brand };
        }
        const services = {};
        for (const [key, service] of Object.entries(selectedServices)) {
            services[key] = { id: service.id, name: service.name, price: service.price };
        }

        if (Object.keys(components).length === 0) {
            showToast('Select at least one component first.', 'error');
            return;
        }

        const nameInput = document.getElementById('buildNameInput');
        if (nameInput) buildName = nameInput.value.trim() || 'My Build';

        try {
            const res = await fetch('api/builder-save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save',
                    build_name: buildName,
                    use_case: useCase,
                    components: { ...components, services },
                    total_price: Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal(),
                    total_wattage: calculateTotalWattage(true),
                })
            });
            const data = await res.json();

            if (data.success) {
                showShareModal(data.share_code);
            } else {
                showToast(data.message || 'Failed to save build.', 'error');
            }
        } catch (e) {
            showToast('Network error. Try again.', 'error');
        }
    }

    // ── Load Shared Build ────────────────────────────────────
    async function loadSharedBuild(code) {
        try {
            const res = await fetch(`api/builder-save.php?code=${encodeURIComponent(code)}`);
            const data = await res.json();

            if (data.success && data.build) {
                const build = data.build;
                buildName = build.build_name || 'Shared Build';
                useCase = build.use_case || 'general';
                activePreset = PRESETS.find(p => p.useCase === useCase)?.key || 'aaa1440';

                // Map saved component IDs back to products
                const comps = build.components || {};
                for (const [key, comp] of Object.entries(comps)) {
                    if (key === 'services') {
                        selectedServices = {};
                        Object.keys(comp || {}).forEach(serviceKey => {
                            if (BUILD_SERVICES[serviceKey]) selectedServices[serviceKey] = BUILD_SERVICES[serviceKey];
                        });
                        continue;
                    }
                    const product = allProducts.find(p => p.id === comp.id);
                    if (product) {
                        selectedComponents[key] = product;
                    }
                }

                document.querySelectorAll('.service-checkbox').forEach(input => {
                    input.checked = Boolean(selectedServices[input.value]);
                });

                const nameInput = document.getElementById('buildNameInput');
                if (nameInput) nameInput.value = buildName;

                chooseBuilderPath('custom', false);
                renderWizardSteps();
                renderUseCaseBar();
                renderCurrentStep();
                updateSummary();
                showToast(`Loaded build: "${buildName}"`, 'success');
            }
        } catch (e) {
            console.error('Failed to load shared build:', e);
        }
    }

    // ── Auto Build ───────────────────────────────────────────
    function autoBuild(presetUseCase = useCase, budget = targetBudget, notify = true) {
        selectedComponents = {};

        const budgetWeights = {
            cpu: presetUseCase === 'budget' ? 0.12 : presetUseCase === 'editing' ? 0.28 : 0.22,
            motherboard: presetUseCase === 'budget' ? 0.17 : 0.12,
            gpu: presetUseCase === 'budget' ? 0.32 : presetUseCase === 'office' ? 0.18 : presetUseCase === 'editing' ? 0.30 : 0.42,
            ram: presetUseCase === 'budget' ? 0.11 : presetUseCase === 'editing' || presetUseCase === 'streaming' ? 0.14 : 0.10,
            storage: presetUseCase === 'budget' ? 0.09 : 0.10,
            psu: presetUseCase === 'budget' ? 0.12 : 0.09,
            cooling: presetUseCase === 'budget' ? 0.04 : 0.07,
            accessories: 0.05,
        };

        STEPS.forEach(step => {
            const categoryBudget = budget * (budgetWeights[step.key] || 0.12);
            const options = allProducts
                .filter(p => p.category === step.category && p.inStock)
                .filter(p => checkCompatibility(step.key, p).compatible)
                .filter(p => {
                    if (step.key !== 'cpu' || !selectedPlatform) return true;
                    if (selectedPlatform === 'intel') return String(p.brand).toLowerCase().includes('intel');
                    if (selectedPlatform === 'amd') return String(p.brand).toLowerCase().includes('amd');
                    return true;
                })
                .sort((a, b) => {
                    const aOver = a.price > categoryBudget ? 1 : 0;
                    const bOver = b.price > categoryBudget ? 1 : 0;
                    if (aOver !== bOver) return aOver - bOver;
                    const aScore = (a.rating || 0) * 1000 - Math.abs(categoryBudget - a.price) / 20;
                    const bScore = (b.rating || 0) * 1000 - Math.abs(categoryBudget - b.price) / 20;
                    return bScore - aScore;
                });

            if (options.length > 0) {
                selectedComponents[step.key] = options[0];
            }
        });

        const recommended = getPSURecommendation(calculateTotalWattage(true));
        const psuOptions = allProducts
            .filter(p => p.category === 'psu' && p.inStock && extractWattage(p, 'psu') >= recommended)
            .sort((a, b) => a.price - b.price || b.rating - a.rating);
        if (psuOptions[0]) selectedComponents.psu = psuOptions[0];

        renderWizardSteps();
        renderCurrentStep();
        updateSummary();
        if (notify) showToast(`Auto-built ${presetUseCase} PC around ${formatMAD(budget)}.`, 'success');

        // Update FPS Estimator
        if (typeof FPSEstimator !== 'undefined') {
            FPSEstimator.update();
        }

        const pcBuilderBtn = document.querySelector('.bth-grid .bth-card');
        if (typeof window.switchToolTab === 'function' && pcBuilderBtn) {
            setTimeout(() => { window.switchToolTab('tab-pc-builder', pcBuilderBtn); }, 500);
        }
    }

    // ── Share Modal ──────────────────────────────────────────
    function showShareModal(shareCode) {
        const backdrop = document.getElementById('shareModalBackdrop');
        const urlInput = document.getElementById('shareUrlInput');
        if (!backdrop) return;

        const shareUrl = `${window.location.origin}${window.location.pathname}?build=${shareCode}`;
        if (urlInput) urlInput.value = shareUrl;

        // Generate real branded QR code on the fly (neon cyan foreground on custom dark backdrop)
        const qrBox = document.querySelector('.share-qr-box');
        if (qrBox) {
            qrBox.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(shareUrl)}&color=00f5d4&bgcolor=1a1d24" alt="Build QR Code" style="width: 100%; height: auto; display: block; border-radius: 12px; border: 1px solid rgba(0,245,212,0.15);" />`;
        }

        backdrop.classList.add('is-open');
    }

    function closeShareModal() {
        const backdrop = document.getElementById('shareModalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('is-open');
            // Revert to placeholder when closed for next invocation
            const qrBox = document.querySelector('.share-qr-box');
            if (qrBox) {
                qrBox.innerHTML = `
                    <div class="qr-placeholder">
                        <i class="fas fa-qrcode"></i>
                        <span>QR CODE</span>
                    </div>
                `;
            }
        }
    }

    function copyShareUrl() {
        const input = document.getElementById('shareUrlInput');
        if (!input) return;
        navigator.clipboard.writeText(input.value).then(() => {
            showToast('Build URL copied!', 'success');
        });
    }

    function buildQuoteText() {
        const lines = [`${buildName || 'My Build'} - Maroc PC`];
        Object.entries(selectedComponents).forEach(([key, product]) => {
            const step = STEPS.find(s => s.key === key);
            lines.push(`${step?.label || key}: ${product.name} - ${formatMAD(product.price)}`);
        });
        Object.values(selectedServices).forEach(service => {
            lines.push(`Service: ${service.name} - ${formatMAD(service.price)}`);
        });
        lines.push(`Estimated power draw: ${calculateTotalWattage(true)}W`);
        lines.push(`Total: ${formatMAD(Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal())}`);
        return lines.join('\n');
    }

    function shareWhatsApp() {
        if (Object.keys(selectedComponents).length === 0) {
            showToast('Select components before sharing.', 'error');
            return;
        }
        const text = encodeURIComponent(`${buildQuoteText()}\n\nCan you confirm availability and compatibility?`);
        window.open(`https://wa.me/212618821949?text=${text}`, '_blank', 'noopener');
    }

    function exportQuote() {
        if (Object.keys(selectedComponents).length === 0) {
            showToast('Select components before exporting.', 'error');
            return;
        }
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            showToast('Popup blocked. Allow popups to export the quote.', 'error');
            return;
        }
        const html = `
            <html>
            <head>
                <title>${buildName || 'Maroc PC Build Quote'}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 32px; color: #111; }
                    h1 { margin: 0 0 8px; }
                    .muted { color: #666; margin-bottom: 24px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
                    th { background: #f4f6f8; }
                    .total { font-size: 20px; font-weight: 700; text-align: right; }
                </style>
            </head>
            <body>
                <h1>${buildName || 'Maroc PC Build Quote'}</h1>
                <div class="muted">Generated ${new Date().toLocaleString()} - Estimated wattage ${calculateTotalWattage(true)}W</div>
                <table>
                    <thead><tr><th>Type</th><th>Item</th><th>Price</th></tr></thead>
                    <tbody>
                        ${Object.entries(selectedComponents).map(([key, product]) => {
                            const step = STEPS.find(s => s.key === key);
                            return `<tr><td>${step?.label || key}</td><td>${product.name}</td><td>${formatMAD(product.price)}</td></tr>`;
                        }).join('')}
                        ${Object.values(selectedServices).map(service => `<tr><td>Service</td><td>${service.name}</td><td>${formatMAD(service.price)}</td></tr>`).join('')}
                    </tbody>
                </table>
                <div class="total">Total: ${formatMAD(Object.values(selectedComponents).reduce((s, p) => s + p.price, 0) + calculateServiceTotal())}</div>
                <p class="muted">Prices and stock are estimates until confirmed by Maroc PC.</p>
            </body>
            </html>
        `;
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    // ── Toast Helper ─────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) { alert(message); return; }

        const icon = toast.querySelector('i');
        if (icon) {
            icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        }
        toast.style.borderColor = type === 'success' ? '#00f5d4' : '#ff3d5a';
        toastMsg.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Format ───────────────────────────────────────────────
    function formatMAD(n) {
        return Number(n).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    function setPlatform(platform) {
        selectedPlatform = platform;
        document.querySelectorAll('.ps-card').forEach(card => {
            card.classList.toggle('active', card.dataset.platform === platform);
        });
        showToast(`Building ${platform.toUpperCase()} Combo...`, 'success');
        
        chooseBuilderPath('custom', false);
        autoBuild(useCase, targetBudget, false);
    }

    function shareFB() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
    }

    function shareWA() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent('Check out my PC build: ' + url)}`, '_blank');
    }

    function shareTW() {
        const url = document.getElementById('shareUrlInput').value;
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out my PC build: ' + url)}`, '_blank');
    }

    // ── Public API ───────────────────────────────────────────
    return {
        init,
        addAllToCart,
        addSingleToCart,
        saveBuild,
        autoBuild,
        chooseBuilderPath,
        shareWhatsApp, // Correct mapping to itemized whatsapp quote share
        shareFB,
        shareWA,
        shareTW,
        setPlatform,
        exportQuote,
        applyGamingFinder,
        resetGamingFinder,
        useCurrentBuildForPsu,
        applyPsuChoice,
        useCurrentBuildForMemory,
        applyMemoryChoice,
        closeShareModal,
        copyShareUrl,
        focusSummary,
        getAssistantContext,
        applyAssistantProducts,
        getSelected: () => selectedComponents
    };
})();

document.addEventListener('DOMContentLoaded', PCBuilder.init);
