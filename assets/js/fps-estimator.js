/**
 * fps-estimator.js — Real-time gaming performance estimation
 */
const FPSEstimator = (() => {
    let currentResolution = '1080p';
    let selectedGames = null;

    function init() {
        renderContainer();
        update();
    }

    function renderContainer() {
        const summary = document.querySelector('.build-summary');
        if (!summary) return;

        // Create container before Total section
        const totalSection = summary.querySelector('.build-total');
        const container = document.createElement('div');
        container.className = 'fps-estimator';
        container.id = 'fpsEstimator';
        
        container.innerHTML = `
            <div class="fe-header">
                <span class="fe-label"><i class="fas fa-gamepad"></i> FPS Estimator</span>
                <div class="fe-res-toggles">
                    <button class="fe-res-btn active" data-res="1080p">1080p</button>
                    <button class="fe-res-btn" data-res="1440p">1440p</button>
                    <button class="fe-res-btn" data-res="4K">4K</button>
                </div>
            </div>
            <div class="fe-games" id="feGames">
                <!-- Populated by JS -->
                <div class="fe-empty">Select a GPU to see estimates</div>
            </div>
            <div class="fe-disclaimer">* Estimated FPS at Ultra settings</div>
        `;

        summary.insertBefore(container, totalSection);

        // Resolution toggle events
        container.querySelectorAll('.fe-res-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentResolution = btn.dataset.res;
                container.querySelectorAll('.fe-res-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                update();
            });
        });
    }

    function update() {
        const gamesContainer = document.getElementById('feGames');
        if (!gamesContainer) return;

        // Get selected GPU and CPU from PCBuilder state (global access)
        const selected = (typeof PCBuilder !== 'undefined') ? PCBuilder.getSelected() : {};
        const gpu = selected['gpu'];
        const cpu = selected['cpu'];

        if (!gpu) {
            gamesContainer.innerHTML = '<div class="fe-empty">Select a GPU to see estimates</div>';
            return;
        }

        const gpuBenchmarks = FPS_DATA.benchmarks[gpu.id] || FPS_DATA.benchmarks["3"]; // Default to 4070Ti specs if unknown
        const cpuMultiplier = FPS_DATA.cpuTiers[cpu?.id] || FPS_DATA.cpuTiers.default;

        let html = '';
        const games = selectedGames?.length
            ? FPS_DATA.games.filter(game => selectedGames.includes(game.id))
            : FPS_DATA.games;

        games.forEach(game => {
            const fallbackFPS = getFallbackGameFps(gpuBenchmarks, game);
            const baseFPS = gpuBenchmarks[game.id]?.[currentResolution] || fallbackFPS;
            const finalFPS = Math.round(baseFPS * cpuMultiplier);
            const barWidth = Math.min(100, (finalFPS / 200) * 100); // 200 FPS is 100%

            html += `
                <div class="fe-game-item">
                    <div class="feg-info">
                        <span class="feg-name"><i class="fas ${game.icon}"></i> ${game.name}</span>
                        <span class="feg-val">${finalFPS} FPS</span>
                    </div>
                    <div class="feg-bar-bg">
                        <div class="feg-bar-fill" style="width: ${barWidth}%"></div>
                    </div>
                </div>
            `;
        });

        gamesContainer.innerHTML = html;
    }

    function getFallbackGameFps(gpuBenchmarks, game) {
        const knownValues = Object.values(gpuBenchmarks || {})
            .map(gameBench => gameBench?.[currentResolution])
            .filter(value => Number.isFinite(value));
        if (!knownValues.length) return 45;
        const average = knownValues.reduce((sum, value) => sum + value, 0) / knownValues.length;
        return Math.max(30, Math.round(average * (game.demand || 0.9)));
    }

    function setResolution(resolution) {
        currentResolution = resolution || currentResolution;
        const container = document.getElementById('fpsEstimator');
        if (container) {
            container.querySelectorAll('.fe-res-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.res === currentResolution);
            });
        }
        update();
    }

    function setGames(gameIds) {
        selectedGames = Array.isArray(gameIds) ? [...gameIds] : null;
        update();
    }

    // Expose update for PCBuilder to call
    return {
        init,
        update,
        setResolution,
        setGames
    };
})();
