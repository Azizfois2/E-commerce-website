<?php
require_once 'admin-helpers.php';

adminRequireAuth();

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$hasOrders = adminTableExists($pdo, 'orders');

$salesSeries = $hasOrders ? adminFetchAll($pdo, "
    SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
") : [];
$salesByDay = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $salesByDay[$day] = ['day' => $day, 'orders_count' => 0, 'revenue' => 0.0];
}
foreach ($salesSeries as $row) {
    $salesByDay[(string) $row['day']] = [
        'day' => (string) $row['day'],
        'orders_count' => (int) $row['orders_count'],
        'revenue' => (float) $row['revenue'],
    ];
}
$salesChartData = array_values($salesByDay);

$categorySales = ($hasOrders && adminTableExists($pdo, 'order_items'))
    ? adminFetchAll($pdo, '
        SELECT COALESCE(p.category, "Uncategorized") AS category,
               COALESCE(SUM(oi.quantity * oi.price_at_time), 0) AS revenue,
               COALESCE(SUM(oi.quantity), 0) AS units
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        LEFT JOIN products p ON p.id = oi.product_id
        GROUP BY COALESCE(p.category, "Uncategorized")
        ORDER BY revenue DESC
        LIMIT 6
    ')
    : [];
$topCategoryRevenue = (float) max(array_map(static fn($row) => (float) $row['revenue'], $categorySales ?: [['revenue' => 0]]));
$flashRevenue = ($hasOrders && adminTableExists($pdo, 'order_items'))
    ? (float) adminFetchValue($pdo, '
        SELECT COALESCE(SUM(oi.quantity * oi.price_at_time), 0)
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN flash_sales fs ON fs.product_id = p.id
    ')
    : 0.0;

$hasLaptops = adminTableExists($pdo, 'laptops');
$laptopTotalRevenue = ($hasOrders && $hasLaptops && adminTableExists($pdo, 'order_items'))
    ? (float) adminFetchValue($pdo, '
        SELECT COALESCE(SUM(oi.quantity * oi.price_at_time), 0)
        FROM order_items oi
        JOIN laptops l ON oi.name_at_time = l.name
    ')
    : 0.0;

$laptopBrandSales = ($hasOrders && $hasLaptops && adminTableExists($pdo, 'order_items'))
    ? adminFetchAll($pdo, '
        SELECT l.brand, 
               COALESCE(SUM(oi.quantity), 0) AS units, 
               COALESCE(SUM(oi.quantity * oi.price_at_time), 0) AS revenue
        FROM order_items oi
        JOIN laptops l ON oi.name_at_time = l.name
        GROUP BY l.brand
        ORDER BY revenue DESC
    ')
    : [];
$topLaptopBrandRevenue = (float) max(array_map(static fn($row) => (float) $row['revenue'], $laptopBrandSales ?: [['revenue' => 0]]));

$avgLaptopPrice = $hasLaptops ? (float) adminFetchValue($pdo, 'SELECT COALESCE(AVG(price), 0) FROM laptops') : 0.0;
$avgComponentPrice = (float) adminFetchValue($pdo, 'SELECT COALESCE(AVG(price), 0) FROM products');

$laptopStockValue = $hasLaptops ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(price * stock_quantity), 0) FROM laptops') : 0.0;
$componentStockValue = (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(price * stock_quantity), 0) FROM products');

$laptopStockQty = $hasLaptops ? (int) adminFetchValue($pdo, 'SELECT COALESCE(SUM(stock_quantity), 0) FROM laptops') : 0;
$componentStockQty = (int) adminFetchValue($pdo, 'SELECT COALESCE(SUM(stock_quantity), 0) FROM products');

// Advanced Chart Data
$orderStatusCounts = $hasOrders ? adminFetchAll($pdo, '
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
') : [];

$revenueByDayOfWeek = $hasOrders ? adminFetchAll($pdo, '
    SELECT DAYNAME(created_at) as day_name, COALESCE(SUM(total), 0) as revenue 
    FROM orders 
    GROUP BY DAYNAME(created_at)
    ORDER BY FIELD(day_name, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
') : [];

$todayRevenue = $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = CURDATE()') : 0.0;
$avgOrder = $hasOrders ? (float) adminFetchValue($pdo, 'SELECT COALESCE(AVG(total), 0) FROM orders') : 0.0;

adminPageStart('Analytics', 'analytics');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="section-heading">
    <div>
        <span class="eyebrow">Reports & Stats</span>
        <h1>Analytics</h1>
        <p class="section-copy">Deep dive into store performance, sales trends, and customer behavior.</p>
    </div>
    <div class="heading-actions">
        <a class="button button-light" href="dashboard.php">Dashboard</a>
        <a class="button button-light" href="api/export-orders.php?format=csv"><i class="fas fa-file-csv"></i> Orders CSV</a>
        <a class="button button-light" href="api/export-customers.php?format=csv"><i class="fas fa-file-csv"></i> Customers CSV</a>
    </div>
</section>

<section class="analytics-grid">
    <article class="table-card analytics-card">
        <div class="card-head">
            <div>
                <h2>Sales Analytics</h2>
                <p class="card-copy">Revenue trend across the last 14 days.</p>
            </div>
            <strong class="analytics-kpi"><?= adminMoney($todayRevenue) ?><span>Today</span></strong>
        </div>
        <canvas id="salesChart" class="sales-chart" height="220"></canvas>
    </article>
    <article class="table-card analytics-card">
        <div class="card-head">
            <div>
                <h2>Category Revenue</h2>
                <p class="card-copy">Best-selling categories by order item revenue.</p>
            </div>
            <strong class="analytics-kpi"><?= adminMoney($avgOrder) ?><span>Avg order</span></strong>
        </div>
        <div class="category-bars">
            <?php if ($categorySales === []): ?>
                <p class="empty-copy">No sales by category yet.</p>
            <?php endif; ?>
            <?php foreach ($categorySales as $row): ?>
                <?php $pct = $topCategoryRevenue > 0 ? max(3, ((float) $row['revenue'] / $topCategoryRevenue) * 100) : 0; ?>
                <div class="category-bar">
                    <span><strong><?= adminH($row['category']) ?></strong><small><?= (int) $row['units'] ?> units</small></span>
                    <div><i style="width: <?= adminH((string) $pct) ?>%"></i></div>
                    <em><?= adminMoney((float) $row['revenue']) ?></em>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flash-conversion">
            <span>Flash sale influenced revenue</span>
            <strong><?= adminMoney($flashRevenue) ?></strong>
        </div>
    </article>
</section>

<!-- Laptop Performance & Catalog Analytics -->
<section class="analytics-grid" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
    <!-- Card 1: Laptop Brand Market Share -->
    <article class="table-card analytics-card">
        <div class="card-head">
            <div>
                <h2>Laptop Brand Sales Curation</h2>
                <p class="card-copy">Sales volume and gross revenue split by laptop brand.</p>
            </div>
            <strong class="analytics-kpi"><?= adminMoney($laptopTotalRevenue) ?><span>Laptop gross</span></strong>
        </div>
        <div class="category-bars" style="padding: 20px;">
            <?php if ($laptopBrandSales === []): ?>
                <p class="empty-copy">No laptop brand sales recorded yet.</p>
            <?php else: ?>
                <?php foreach ($laptopBrandSales as $row): ?>
                    <?php $pct = $topLaptopBrandRevenue > 0 ? max(3, ((float) $row['revenue'] / $topLaptopBrandRevenue) * 100) : 0; ?>
                    <div class="category-bar" style="margin-bottom: 16px;">
                        <span style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                            <strong><?= adminH($row['brand']) ?></strong>
                            <small style="color: var(--muted);"><?= (int) $row['units'] ?> units sold</small>
                        </span>
                        <div style="height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; margin-bottom: 4px;">
                            <i style="display: block; height: 100%; width: <?= adminH((string) $pct) ?>%; background: var(--cyan);"></i>
                        </div>
                        <em style="display: block; font-family: 'Space Mono', monospace; font-size: 0.8rem; text-align: right; color: var(--white); font-weight: 700;"><?= adminMoney((float) $row['revenue']) ?></em>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <!-- Card 2: Catalog Comparison Suite (Laptops vs Components) -->
    <article class="table-card analytics-card" style="display: flex; flex-direction: column;">
        <div class="card-head">
            <div>
                <h2>Catalog Curation Comparison</h2>
                <p class="card-copy">Visualizing stock pressure and pricing ratios.</p>
            </div>
        </div>
        
        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-around; gap: 16px;">
            <!-- Comparison 1: Average List Price -->
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 6px;">
                    <span style="color: var(--muted); font-weight: 700;">Average Laptop Price</span>
                    <span style="color: var(--white); font-weight: 800;"><?= adminMoney($avgLaptopPrice) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 8px;">
                    <span style="color: var(--muted); font-weight: 700;">Average Component Price</span>
                    <span style="color: var(--white); font-weight: 800;"><?= adminMoney($avgComponentPrice) ?></span>
                </div>
                <?php 
                $maxAvgPrice = max(1.0, $avgLaptopPrice, $avgComponentPrice);
                $laptopPricePct = ($avgLaptopPrice / $maxAvgPrice) * 100;
                $compPricePct = ($avgComponentPrice / $maxAvgPrice) * 100;
                ?>
                <div style="height: 6px; background: var(--border); border-radius: 3px; display: flex; overflow: hidden;">
                    <div style="width: <?= $laptopPricePct ?>%; background: var(--cyan);" title="Laptops"></div>
                    <div style="width: <?= $compPricePct ?>%; background: var(--orange);" title="Components"></div>
                </div>
            </div>

            <!-- Comparison 2: Inventory Value Investment -->
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 6px;">
                    <span style="color: var(--muted); font-weight: 700;">Laptop Stock Value</span>
                    <span style="color: var(--white); font-weight: 800;"><?= adminMoney($laptopStockValue) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 8px;">
                    <span style="color: var(--muted); font-weight: 700;">Component Stock Value</span>
                    <span style="color: var(--white); font-weight: 800;"><?= adminMoney($componentStockValue) ?></span>
                </div>
                <?php 
                $totalStockVal = max(1.0, $laptopStockValue + $componentStockValue);
                $laptopStockValPct = ($laptopStockValue / $totalStockVal) * 100;
                $compStockValPct = ($componentStockValue / $totalStockVal) * 100;
                ?>
                <div style="height: 6px; background: var(--border); border-radius: 3px; display: flex; overflow: hidden;">
                    <div style="width: <?= $laptopStockValPct ?>%; background: var(--cyan);" title="Laptops"></div>
                    <div style="width: <?= $compStockValPct ?>%; background: var(--orange);" title="Components"></div>
                </div>
            </div>

            <!-- Comparison 3: Unit Stock Count Share -->
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 6px;">
                    <span style="color: var(--muted); font-weight: 700;">Laptops in Stock</span>
                    <span style="color: var(--white); font-weight: 800;"><?= $laptopStockQty ?> units</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 8px;">
                    <span style="color: var(--muted); font-weight: 700;">Components in Stock</span>
                    <span style="color: var(--white); font-weight: 800;"><?= $componentStockQty ?> units</span>
                </div>
                <?php 
                $totalUnits = max(1, $laptopStockQty + $componentStockQty);
                $laptopUnitsPct = ($laptopStockQty / $totalUnits) * 100;
                $compUnitsPct = ($componentStockQty / $totalUnits) * 100;
                ?>
                <div style="height: 6px; background: var(--border); border-radius: 3px; display: flex; overflow: hidden;">
                    <div style="width: <?= $laptopUnitsPct ?>%; background: var(--cyan);" title="Laptops"></div>
                    <div style="width: <?= $compUnitsPct ?>%; background: var(--orange);" title="Components"></div>
                </div>
            </div>
        </div>
        <div style="padding: 12px 20px; background: rgba(0, 245, 212, 0.05); border-top: 1px solid var(--border); font-size: 0.8rem; color: var(--muted); display: flex; justify-content: space-between; align-items: center; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <span><i class="fas fa-circle" style="color: var(--cyan); margin-right: 4px; font-size: 0.65rem;"></i> Laptops Curation</span>
            <span><i class="fas fa-circle" style="color: var(--orange); margin-right: 4px; font-size: 0.65rem;"></i> Components Curation</span>
        </div>
    </article>
</section>

<!-- Advanced Analytics Section -->
<section class="table-card" style="margin-top: 30px;">
    <div class="card-head">
        <div>
            <h2>Advanced Analytics Explorer</h2>
            <p class="card-copy">Deeper visual insights into store performance.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="button button-light button-small" onclick="exportChartAsPNG('statusChart', 'order_status_distribution.png')">
                <i class="fas fa-download"></i> Status
            </button>
            <button class="button button-light button-small" onclick="exportChartAsPNG('dowChart', 'revenue_by_day.png')">
                <i class="fas fa-download"></i> Days
            </button>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; padding: 20px;">
        <!-- Order Status Chart -->
        <div style="background: var(--input-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
            <h3 style="margin-top: 0; color: var(--text); font-size: 1.1rem; text-align: center;">Order Status Distribution</h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Day of Week -->
        <div style="background: var(--input-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
            <h3 style="margin-top: 0; color: var(--text); font-size: 1.1rem; text-align: center;">Revenue by Day of Week</h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="dowChart"></canvas>
            </div>
        </div>
    </div>
</section>

<script>
window.adminSalesChartData = <?= json_encode($salesChartData, JSON_UNESCAPED_SLASHES) ?>;

(function() {
    const canvas = document.getElementById('salesChart');
    const data = window.adminSalesChartData || [];
    if (!canvas || !data.length) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = Math.max(320, rect.width) * dpr;
    canvas.height = 220 * dpr;
    ctx.scale(dpr, dpr);
    const w = canvas.width / dpr;
    const h = canvas.height / dpr;
    const pad = { left: 48, right: 18, top: 18, bottom: 34 };
    const maxRevenue = Math.max(1, ...data.map(row => Number(row.revenue || 0)));
    const x = i => pad.left + (i / Math.max(1, data.length - 1)) * (w - pad.left - pad.right);
    const y = v => pad.top + (h - pad.top - pad.bottom) - (Number(v || 0) / maxRevenue) * (h - pad.top - pad.bottom);

    ctx.clearRect(0, 0, w, h);
    ctx.strokeStyle = 'rgba(255,255,255,0.08)';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const gy = pad.top + i * ((h - pad.top - pad.bottom) / 4);
        ctx.beginPath();
        ctx.moveTo(pad.left, gy);
        ctx.lineTo(w - pad.right, gy);
        ctx.stroke();
    }

    const gradient = ctx.createLinearGradient(0, pad.top, 0, h - pad.bottom);
    gradient.addColorStop(0, 'rgba(0,245,212,0.32)');
    gradient.addColorStop(1, 'rgba(0,245,212,0.02)');
    ctx.beginPath();
    data.forEach((row, i) => {
        const px = x(i);
        const py = y(row.revenue);
        if (i === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
    });
    ctx.lineTo(x(data.length - 1), h - pad.bottom);
    ctx.lineTo(x(0), h - pad.bottom);
    ctx.closePath();
    ctx.fillStyle = gradient;
    ctx.fill();

    ctx.beginPath();
    data.forEach((row, i) => {
        const px = x(i);
        const py = y(row.revenue);
        if (i === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
    });
    ctx.strokeStyle = '#00f5d4';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = '#9aa4b5';
    ctx.font = '11px JetBrains Mono, monospace';
    data.forEach((row, i) => {
        if (i % 3 !== 0 && i !== data.length - 1) return;
        ctx.fillText(String(row.day).slice(5), x(i) - 16, h - 12);
    });
})();

(function() {
    // Shared chart styling overrides for dark theme
    Chart.defaults.color = '#9aa4b5';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(10, 11, 14, 0.9)';
    Chart.defaults.plugins.tooltip.titleColor = '#00f5d4';
    Chart.defaults.plugins.tooltip.borderColor = '#2d333b';
    Chart.defaults.plugins.tooltip.borderWidth = 1;

    // 1. Order Status Doughnut Chart
    const statusData = <?= json_encode($orderStatusCounts) ?>;
    const labels = statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1));
    const counts = statusData.map(d => d.count);
    
    // Aesthetic colors
    const colors = {
        'Pending': '#ff9800',
        'Processing': '#00bcd4',
        'Shipped': '#8a2be2',
        'Delivered': '#00e676',
        'Cancelled': '#ff3d5a'
    };
    const bgColors = labels.map(l => colors[l] || '#4fc3f7');

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: bgColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { position: 'right' }
            }
        }
    });

    // 2. Revenue by Day of Week Bar Chart
    const dowData = <?= json_encode($revenueByDayOfWeek) ?>;
    const dowLabels = dowData.map(d => d.day_name);
    const dowRevenues = dowData.map(d => parseFloat(d.revenue));

    new Chart(document.getElementById('dowChart'), {
        type: 'bar',
        data: {
            labels: dowLabels,
            datasets: [{
                label: 'Revenue (MAD)',
                data: dowRevenues,
                backgroundColor: 'rgba(0, 245, 212, 0.2)',
                borderColor: '#00f5d4',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(0, 245, 212, 0.4)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { callback: function(val) { return val + ' MAD'; } }
                },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();

// PNG Export function
function exportChartAsPNG(canvasId, filename) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Create a temporary canvas with an opaque background (default is transparent)
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const ctx = tempCanvas.getContext('2d');
    
    // Fill with theme background color
    ctx.fillStyle = '#1e2229'; // var(--input-bg) approximate
    ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    // Draw the chart on top
    ctx.drawImage(canvas, 0, 0);

    // Trigger download
    const link = document.createElement('a');
    link.download = filename;
    link.href = tempCanvas.toDataURL('image/png');
    link.click();
}
</script>

<?php adminPageEnd(); ?>
