/**
 * price-chart.js — Canvas-based price history chart
 * Renders an interactive line chart with hover tooltips
 */
const PriceChart = (() => {
    const CHART_COLORS = {
        line: '#00f5d4',
        fill: 'rgba(0,245,212,0.08)',
        grid: 'rgba(255,255,255,0.06)',
        text: '#7a8399',
        highlight: '#00f5d4',
        lowest: '#00e676',
        highest: '#ff3d5a',
        avg: '#ffcf4d',
        dot: '#00f5d4',
        crosshair: 'rgba(0,245,212,0.3)',
    };

    const PADDING = { top: 30, right: 20, bottom: 40, left: 70 };

    function create(containerId, productId, days = 90) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="price-chart-wrapper">
                <div class="price-chart-header">
                    <h4><i class="fas fa-chart-line"></i> Price History</h4>
                    <div class="price-chart-range">
                        <button class="pcr-btn" data-days="30">30D</button>
                        <button class="pcr-btn active" data-days="90">90D</button>
                        <button class="pcr-btn" data-days="180">180D</button>
                        <button class="pcr-btn" data-days="365">1Y</button>
                    </div>
                </div>
                <div class="price-chart-stats" id="${containerId}-stats"></div>
                <div class="price-chart-canvas-wrap">
                    <canvas id="${containerId}-canvas"></canvas>
                    <div class="price-chart-tooltip" id="${containerId}-tooltip"></div>
                </div>
                <p class="price-chart-note">Price in MAD · Updated daily</p>
            </div>
        `;

        // Range button handlers
        container.querySelectorAll('.pcr-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.pcr-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadAndRender(containerId, productId, parseInt(btn.dataset.days));
            });
        });

        loadAndRender(containerId, productId, days);
    }

    async function loadAndRender(containerId, productId, days) {
        try {
            const res = await fetch(`api/price-history.php?product_id=${productId}&days=${days}`);
            const data = await res.json();

            if (!data.success || !data.history?.length) {
                showEmpty(containerId);
                return;
            }

            renderStats(containerId, data.stats);
            renderChart(containerId, data.history, data.stats);
        } catch (e) {
            console.error('Price chart error:', e);
            showEmpty(containerId);
        }
    }

    function showEmpty(containerId) {
        const statsEl = document.getElementById(`${containerId}-stats`);
        if (statsEl) statsEl.innerHTML = '<p style="color:var(--muted);text-align:center;padding:20px;">No price history available yet.</p>';
    }

    function renderStats(containerId, stats) {
        const el = document.getElementById(`${containerId}-stats`);
        if (!el) return;

        const diff = stats.current - stats.lowest;
        const pctAbove = stats.lowest > 0 ? ((diff / stats.lowest) * 100).toFixed(1) : '0';
        const isAtLowest = diff <= 0.01;

        el.innerHTML = `
            <div class="pcs-item">
                <span class="pcs-label">Current</span>
                <span class="pcs-value">${formatMAD(stats.current)}</span>
            </div>
            <div class="pcs-item pcs-lowest">
                <span class="pcs-label">Lowest</span>
                <span class="pcs-value">${formatMAD(stats.lowest)}</span>
            </div>
            <div class="pcs-item pcs-highest">
                <span class="pcs-label">Highest</span>
                <span class="pcs-value">${formatMAD(stats.highest)}</span>
            </div>
            <div class="pcs-item">
                <span class="pcs-label">Average</span>
                <span class="pcs-value">${formatMAD(stats.average)}</span>
            </div>
            ${isAtLowest
                ? '<div class="pcs-badge pcs-good"><i class="fas fa-arrow-down"></i> At lowest price!</div>'
                : `<div class="pcs-badge pcs-neutral"><i class="fas fa-arrow-up"></i> ${pctAbove}% above lowest</div>`
            }
        `;
    }

    function renderChart(containerId, history, stats) {
        const canvas = document.getElementById(`${containerId}-canvas`);
        const tooltip = document.getElementById(`${containerId}-tooltip`);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.parentElement.getBoundingClientRect();
        const w = rect.width;
        const h = 220;

        canvas.width = w * dpr;
        canvas.height = h * dpr;
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        ctx.scale(dpr, dpr);

        const prices = history.map(h => parseFloat(h.price));
        const dates = history.map(h => h.recorded_at);

        const minP = Math.min(...prices) * 0.97;
        const maxP = Math.max(...prices) * 1.03;

        const chartW = w - PADDING.left - PADDING.right;
        const chartH = h - PADDING.top - PADDING.bottom;

        function xPos(i) { return PADDING.left + (i / (prices.length - 1 || 1)) * chartW; }
        function yPos(p) { return PADDING.top + chartH - ((p - minP) / (maxP - minP || 1)) * chartH; }

        // Clear
        ctx.clearRect(0, 0, w, h);

        // Grid lines
        ctx.strokeStyle = CHART_COLORS.grid;
        ctx.lineWidth = 1;
        const gridLines = 5;
        for (let i = 0; i <= gridLines; i++) {
            const y = PADDING.top + (i / gridLines) * chartH;
            ctx.beginPath();
            ctx.moveTo(PADDING.left, y);
            ctx.lineTo(w - PADDING.right, y);
            ctx.stroke();

            // Price labels
            const price = maxP - (i / gridLines) * (maxP - minP);
            ctx.fillStyle = CHART_COLORS.text;
            ctx.font = '11px JetBrains Mono, monospace';
            ctx.textAlign = 'right';
            ctx.fillText(formatMADShort(price), PADDING.left - 8, y + 4);
        }

        // Date labels
        ctx.textAlign = 'center';
        const labelCount = Math.min(6, dates.length);
        const step = Math.max(1, Math.floor(dates.length / labelCount));
        for (let i = 0; i < dates.length; i += step) {
            const x = xPos(i);
            const d = new Date(dates[i]);
            ctx.fillStyle = CHART_COLORS.text;
            ctx.font = '10px JetBrains Mono, monospace';
            ctx.fillText(d.toLocaleDateString('en', { month: 'short', day: 'numeric' }), x, h - 8);
        }

        // Fill area under line
        ctx.beginPath();
        ctx.moveTo(xPos(0), yPos(prices[0]));
        for (let i = 1; i < prices.length; i++) {
            ctx.lineTo(xPos(i), yPos(prices[i]));
        }
        ctx.lineTo(xPos(prices.length - 1), PADDING.top + chartH);
        ctx.lineTo(xPos(0), PADDING.top + chartH);
        ctx.closePath();

        const gradient = ctx.createLinearGradient(0, PADDING.top, 0, PADDING.top + chartH);
        gradient.addColorStop(0, 'rgba(0,245,212,0.15)');
        gradient.addColorStop(1, 'rgba(0,245,212,0)');
        ctx.fillStyle = gradient;
        ctx.fill();

        // Line
        ctx.beginPath();
        ctx.moveTo(xPos(0), yPos(prices[0]));
        for (let i = 1; i < prices.length; i++) {
            ctx.lineTo(xPos(i), yPos(prices[i]));
        }
        ctx.strokeStyle = CHART_COLORS.line;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.stroke();

        // Average line (dashed)
        if (stats.average) {
            const avgY = yPos(stats.average);
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(PADDING.left, avgY);
            ctx.lineTo(w - PADDING.right, avgY);
            ctx.strokeStyle = CHART_COLORS.avg;
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.setLineDash([]);

            ctx.fillStyle = CHART_COLORS.avg;
            ctx.font = '10px JetBrains Mono, monospace';
            ctx.textAlign = 'left';
            ctx.fillText('AVG', w - PADDING.right + 4, avgY + 3);
        }

        // Dots at endpoints
        [0, prices.length - 1].forEach(i => {
            ctx.beginPath();
            ctx.arc(xPos(i), yPos(prices[i]), 4, 0, Math.PI * 2);
            ctx.fillStyle = CHART_COLORS.dot;
            ctx.fill();
            ctx.strokeStyle = 'rgba(0,0,0,0.4)';
            ctx.lineWidth = 2;
            ctx.stroke();
        });

        // Hover interaction
        canvas.onmousemove = (e) => {
            const bound = canvas.getBoundingClientRect();
            const mx = e.clientX - bound.left;
            const my = e.clientY - bound.top;

            if (mx < PADDING.left || mx > w - PADDING.right) {
                tooltip.style.opacity = '0';
                return;
            }

            const idx = Math.round(((mx - PADDING.left) / chartW) * (prices.length - 1));
            const clamped = Math.max(0, Math.min(prices.length - 1, idx));

            const px = xPos(clamped);
            const py = yPos(prices[clamped]);

            // Redraw without tooltip artifacts
            renderChart(containerId, history, stats);

            // Crosshair
            ctx.strokeStyle = CHART_COLORS.crosshair;
            ctx.lineWidth = 1;
            ctx.setLineDash([3, 3]);
            ctx.beginPath();
            ctx.moveTo(px, PADDING.top);
            ctx.lineTo(px, PADDING.top + chartH);
            ctx.stroke();
            ctx.setLineDash([]);

            // Highlight dot
            ctx.beginPath();
            ctx.arc(px, py, 6, 0, Math.PI * 2);
            ctx.fillStyle = CHART_COLORS.dot;
            ctx.fill();
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Tooltip
            const d = new Date(dates[clamped]);
            tooltip.innerHTML = `
                <strong>${formatMAD(prices[clamped])}</strong>
                <span>${d.toLocaleDateString('en', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
            `;
            tooltip.style.opacity = '1';
            tooltip.style.left = Math.min(px, w - 140) + 'px';
            tooltip.style.top = (py - 55) + 'px';
        };

        canvas.onmouseleave = () => {
            tooltip.style.opacity = '0';
            renderChart(containerId, history, stats);
        };
    }

    function formatMAD(n) {
        return Number(n).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
    }
    function formatMADShort(n) {
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toFixed(0);
    }

    return { create };
})();
