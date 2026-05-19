<?php
require_once 'bootstrap.php';
$pdo = db();

// Fetch active deals
$stmt = $pdo->prepare("
    SELECT 
        fs.id AS fs_id,
        fs.product_id,
        fs.sale_price,
        fs.original_price,
        fs.max_quantity,
        fs.sold_count,
        fs.starts_at,
        fs.ends_at,
        p.name AS product_name,
        p.image AS product_image,
        p.category AS product_category,
        p.brand AS product_brand
    FROM flash_sales fs
    JOIN products p ON p.id = fs.product_id
    WHERE fs.starts_at <= NOW()
      AND fs.ends_at > NOW()
      AND (fs.max_quantity IS NULL OR fs.sold_count < fs.max_quantity)
    ORDER BY fs.ends_at ASC
");
$stmt->execute();
$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($deals as &$deal) {
    $deal['discount_pct'] = round((($deal['original_price'] - $deal['sale_price']) / $deal['original_price']) * 100);
}
unset($deal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seasonal Deal Campaigns — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/cart.js"></script>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <style>
        body {
            font-family: 'Syne', sans-serif;
            background: var(--page-bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        .deals-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(0, 245, 212, 0.08) 0%, transparent 60%);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 48px;
            backdrop-filter: blur(12px);
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 245, 212, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-banner h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text);
            margin: 0 0 12px;
            letter-spacing: 0.05em;
        }
        .hero-banner p {
            font-size: 1.1rem;
            color: var(--muted);
            margin: 0 auto 24px;
            max-width: 600px;
        }
        .global-timer {
            display: inline-flex;
            gap: 16px;
            background: rgba(0, 245, 212, 0.05);
            border: 1px solid rgba(0, 245, 212, 0.2);
            padding: 12px 28px;
            border-radius: 16px;
            font-family: 'JetBrains Mono', monospace;
        }
        .timer-segment {
            text-align: center;
        }
        .timer-val {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--cyan);
        }
        .timer-lbl {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 2px;
        }
        .deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .deal-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        .deal-card:hover {
            border-color: rgba(0, 245, 212, 0.25);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }
        .discount-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--cyan);
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 10px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 245, 212, 0.25);
        }
        .deal-image-container {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .deal-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .deal-card:hover .deal-image {
            transform: scale(1.05);
        }
        .deal-cat {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 6px;
        }
        .deal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 12px;
            line-height: 1.4;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .deal-prices {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 16px;
        }
        .sale-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--cyan);
        }
        .orig-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            color: var(--muted);
            text-decoration: line-through;
        }
        .claim-bar-container {
            margin-bottom: 20px;
        }
        .claim-bar-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .claim-bar-outer {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 99px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .claim-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, var(--cyan), #00e676);
            border-radius: 99px;
        }
        .card-timer {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 8px 12px;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: var(--text-dim);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .card-timer i {
            color: var(--cyan);
        }
        .buy-now-btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: var(--cyan);
            color: #000;
            border: none;
            font-weight: 800;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: auto;
        }
        .buy-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 245, 212, 0.3);
        }
        .empty-deals {
            text-align: center;
            padding: 80px 20px;
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--page-bg-2);
        }
        .empty-deals i {
            font-size: 4rem;
            color: var(--muted);
            margin-bottom: 20px;
            opacity: 0.4;
        }
        .empty-deals h3 {
            font-size: 1.3rem;
            margin: 0 0 8px;
        }
        .empty-deals p {
            color: var(--muted);
            margin: 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="index.html" class="logo">
                <i class="fas fa-microchip"></i>
                <span>Maroc PC</span>
            </a>
            <nav class="nav">
                <a href="index.html" class="nav-link">Home</a>
                <a href="products.html" class="nav-link">Products</a>
                <a href="deals.php" class="nav-link active">Deals</a>
            </nav>
            <div class="nav-spacer"></div>
            <div class="cart-wrapper">
                <a href="cart.html" class="cart-icon" aria-label="Shopping cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </div>
    </header>

    <div class="deals-container">
        <div class="hero-banner">
            <span class="eyebrow" style="color: var(--cyan); text-transform: uppercase; font-weight: 700; letter-spacing: 0.08em; display: block; margin-bottom: 12px;">Limited Time Campaign</span>
            <h1>Seasonal Deal Campaigns</h1>
            <p>Grab premium PC hardware components at absolute rock-bottom prices. Every deal is locked-in for a brief window or until stock clears out!</p>
            
            <div class="global-timer" id="globalTimer">
                <div class="timer-segment">
                    <div class="timer-val" id="g-days">00</div>
                    <div class="timer-lbl">Days</div>
                </div>
                <div class="timer-segment"><div class="timer-val">:</div></div>
                <div class="timer-segment">
                    <div class="timer-val" id="g-hours">00</div>
                    <div class="timer-lbl">Hrs</div>
                </div>
                <div class="timer-segment"><div class="timer-val">:</div></div>
                <div class="timer-segment">
                    <div class="timer-val" id="g-minutes">00</div>
                    <div class="timer-lbl">Min</div>
                </div>
                <div class="timer-segment"><div class="timer-val">:</div></div>
                <div class="timer-segment">
                    <div class="timer-val" id="g-seconds">00</div>
                    <div class="timer-lbl">Sec</div>
                </div>
            </div>
        </div>

        <?php if (empty($deals)): ?>
            <div class="empty-deals">
                <i class="fas fa-fire-extinguisher"></i>
                <h3>No Deals Currently Active</h3>
                <p>Check back soon! Our seasonal discount campaigns rotate every few days.</p>
            </div>
        <?php else: ?>
            <div class="deals-grid">
                <?php foreach ($deals as $deal): ?>
                    <?php
                    $claimedPct = $deal['max_quantity'] ? round(($deal['sold_count'] / $deal['max_quantity']) * 100) : 0;
                    ?>
                    <article class="deal-card" data-ends="<?= h($deal['ends_at']) ?>">
                        <span class="discount-badge">−<?= $deal['discount_pct'] ?>%</span>
                        <div class="deal-image-container">
                            <img class="deal-image" src="<?= h($deal['product_image']) ?>" alt="<?= h($deal['product_name']) ?>" onerror="this.src='Images/products/placeholder-storage.svg'">
                        </div>
                        <div class="deal-cat"><?= h($deal['product_category']) ?></div>
                        <h3 class="deal-title" title="<?= h($deal['product_name']) ?>"><?= h($deal['product_name']) ?></h3>
                        
                        <div class="deal-prices">
                            <span class="sale-price"><?= number_format($deal['sale_price'], 2) ?> MAD</span>
                            <span class="orig-price"><?= number_format($deal['original_price'], 2) ?> MAD</span>
                        </div>

                        <?php if ($deal['max_quantity']): ?>
                            <div class="claim-bar-container">
                                <div class="claim-bar-labels">
                                    <span>Claimed</span>
                                    <span><?= $claimedPct ?>%</span>
                                </div>
                                <div class="claim-bar-outer">
                                    <div class="claim-bar-inner" style="width: <?= $claimedPct ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card-timer" data-ends="<?= h($deal['ends_at']) ?>">
                            <i class="fas fa-hourglass-half"></i>
                            <span class="countdown-span">00h 00m 00s left</span>
                        </div>

                        <button type="button" class="buy-now-btn" onclick="addDealToCart(<?= htmlspecialchars(json_encode([
                            'id' => (int)$deal['product_id'],
                            'name' => $deal['product_name'],
                            'price' => (float)$deal['sale_price'],
                            'image' => $deal['product_image'],
                            'inStock' => true
                        ])) ?>)">
                            <i class="fas fa-bolt"></i> Claim Deal
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        function addDealToCart(prod) {
            if (typeof Cart !== 'undefined') {
                Cart.add(prod);
            } else {
                alert("Cart system not loaded.");
            }
        }

        // Live timers logic
        function updateTimers() {
            const now = new Date().getTime();
            let nearestEnd = null;

            document.querySelectorAll('[data-ends]').forEach(el => {
                const endsAtStr = el.getAttribute('data-ends');
                if (!endsAtStr) return;

                const endsAt = new Date(endsAtStr).getTime();
                const diff = endsAt - now;

                if (diff <= 0) {
                    // Deal expired
                    const span = el.querySelector('.countdown-span');
                    if (span) span.innerText = "Deal Expired";
                    return;
                }

                if (nearestEnd === null || endsAt < nearestEnd) {
                    nearestEnd = endsAt;
                }

                // Format countdown
                const hrs = Math.floor(diff / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);

                const span = el.querySelector('.countdown-span');
                if (span) {
                    span.innerText = `${hrs}h ${mins}m ${secs}s left`;
                }
            });

            // Update global hero timer
            if (nearestEnd) {
                const gDiff = nearestEnd - now;
                if (gDiff > 0) {
                    const days = Math.floor(gDiff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((gDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((gDiff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((gDiff % (1000 * 60)) / 1000);

                    document.getElementById('g-days').innerText = String(days).padStart(2, '0');
                    document.getElementById('g-hours').innerText = String(hours).padStart(2, '0');
                    document.getElementById('g-minutes').innerText = String(minutes).padStart(2, '0');
                    document.getElementById('g-seconds').innerText = String(seconds).padStart(2, '0');
                }
            }
        }

        setInterval(updateTimers, 1000);
        updateTimers();
    </script>
</body>
</html>
