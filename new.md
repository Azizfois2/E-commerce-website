Idea #2
Build Price History / Price Drop Alerts
Track component prices over time and show a small price history chart on each product page. Let users click "Alert me when price drops" and receive an email or WhatsApp notification when a component they're watching goes below a threshold they set.
Especially powerful for GPU and CPU pages where prices fluctuate frequently.



This is a solid, achievable project that touches on multiple important backend and frontend concepts. Here’s a realistic, step-by-step implementation plan that focuses on the frontend and a simplified backend integration.

Core Requirements
1. Product Page Enhancement
2. Price History Tracking (Server-Side)
3. Frontend Chart (Lightweight)
4. Email Notifications (Backend)
5. User Opt-in
Implementation Plan
Phase 1: Data Structure (Backend)
Update the components table to include pricing history.
1. Database Changes
Add a new table called product_price_history:

CREATE TABLE product_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_sku VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_sku) REFERENCES components(sku) ON DELETE CASCADE
);
2. Add Price Column to Components
Add a current_price column to the components table if it doesn't exist:

ALTER TABLE components ADD current_price DECIMAL(10, 2) NULL;
3. Price Update Job (Daily/Weekly)
Run a scheduled job (cron or equivalent) to update current_price with the latest price from external sources and log it to the history table.

Phase 2: Frontend Product Page
Modify product.php to include the price history chart and alert functionality.
1. Add Chart Container
In product.php, after the price display, add:

<div class="price-history-container">
    <h3>Price History</h3>
    <div id="priceChart" style="height: 150px; width: 100%;"></div>
    <button id="alertBtn" class="btn btn-primary">Notify me when price drops</button>
    <div id="alertStatus" class="alert-status"></div>
</div>
2. Price History Chart
Fetch the last 7 days of price data from the server and render a line chart.

// fetch price history
fetch('api/price-history.php?sku=CPU-Intel-i7-13700K')
    .then(res => res.json())
    .then(data => {
        renderPriceChart(data);
    });
3. Alert Functionality
Allow users to sign up for price drop alerts.

// when user clicks "Notify me"
fetch('api/alerts.php', {
    method: 'POST',
    body: JSON.stringify({
        sku: 'CPU-Intel-i7-13700K',
        type: 'price_drop',
        threshold: 350.00
    })
})



Phase 3: Backend API & Alert Logic
Create the necessary API endpoints and background job for price tracking and notifications.
1. API: price-history.php
Returns JSON data for the chart:

{
    labels: ['2023-11-10', '2023-11-11', ...],
    data: [420.99, 415.49, ...]
}
2. API: alerts.php
Handles user alerts: saving, deleting, and sending notifications.

3. Price Tracking Cron Job
Run this script daily:

Loop through all components

Call external APIs/scrape to get current prices

Update component current_price

Log price to product_price_history

Check if any users have alerts for this component with the current price

Send email notifications

Phase 4: User Notifications (Email & WhatsApp)
1. Email
Use your existing SMTP setup (PHPMailer):

Subject: Price Drop Alert: [Component Name]

Body:

Hi [User],

The price for [Component Name] has dropped to [New Price].

[Link to product]

MarocPC Team
2. WhatsApp Integration
Use Evolution API (as mentioned in your env file):

Setup webhook/polling to send WhatsApp messages to users who opted in.



Example: price-history.php (Backend)
<?php
// src/api/price-history.php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../bootstrap/application.php';

// Get last 7 days of price history for a component
$sku = $_GET['sku'] ?? '';

$stmt = db()->prepare("
    SELECT date_recorded, price 
    FROM product_price_history 
    WHERE product_sku = ? 
    ORDER BY date_recorded DESC 
    LIMIT 7
");
$stmt->execute([$sku]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];

foreach (array_reverse($history) as $item) {
    $labels[] = date('M d', strtotime($item['date_recorded']));
    $data[] = $item['price'];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);


PC Builder "Bottleneck Analyzer"
After a user selects their CPU and GPU combination, show a real-time bottleneck percentage indicator — like "Your CPU will bottleneck this GPU by ~12% at 1080p, ~4% at 1440p." With a recommendation to either upgrade the CPU or save money by picking a less powerful GPU.
Turns the builder from a catalog into a genuine engineering tool — directly aligned with your Glass Cockpit mandate.  



This is a fantastic, high-value feature that directly supports your “Glass Cockpit for PC Building” vision. It turns your builder from a simple catalog into an intelligent engineering tool.

Here’s a realistic, step-by-step implementation plan:

Phase 1: Data & Logic Backend
1. Bottleneck Data (Database/Constants)
Create a way to store CPU/GPU bottleneck data. You can start with a simple JSON file or a database table.

2. CPU/GPU Compatibility Matrix
For each CPU, store its supported GPU tier range (e.g., Intel i5-13600K: RTX 4060 - RTX 4070 Ti).

3. Bottleneck Calculator
Based on resolution (1080p, 1440p, 4K), calculate the bottleneck percentage.

Formula (simplified): (CPU_Performance / GPU_Performance) * 100

If result > 90%: Balanced system

If result < 85%: CPU bottleneck

If result > 110%: GPU bottleneck

Phase 2: Frontend PC Builder Integration
1. Real-Time Display
In your PC builder page, whenever a user selects a CPU and GPU, calculate and show the bottleneck.

Example Output:

Bottleneck Alert: Your Intel Core i5-13600K will bottleneck the RTX 4070 Ti by ~12% at 1080p.

Recommendation: Consider a Ryzen 7 7800X3D or upgrade to an RTX 4080 for a balanced build.

2. Visual Indicator
Use a colored badge:

🟢 Balanced

🔴 CPU Bottleneck

🔵 GPU Bottleneck

Phase 3: User Education & Alternatives
1. “Why This Matters” Explanation
Add a tooltip or link:

“What is CPU bottlenecking? When your CPU can’t keep up with your GPU, you lose potential FPS…”

2. Alternative Suggestions
Based on the bottleneck, suggest:

If CPU bottleneck: “For this GPU, consider Intel i7-14700K or Ryzen 7 7800X3D instead.”

If GPU bottleneck: “This GPU is overkill for your CPU. Consider saving €100 by choosing an RTX 4060 Ti instead.”

Phase 4: Advanced Features
1. Resolution-Based Analysis
Show how bottleneck changes at different resolutions:

1080p: 12% bottleneck

1440p: 4% bottleneck

4K: 1% bottleneck

2. Build Quality Score
Calculate an overall “System Balance Score” out of 100. Adjust as users add/change components.

3. Price Optimization
“This configuration saves you €150 compared to your original choice.”



Example: Frontend Bottleneck Alert Component
<div id="bottleneck-alert" class="bottleneck-alert" style="display: none;">
    <div class="alert-icon">⚠️</div>
    <div class="alert-content">
        <div class="alert-title" id="bottleneck-title"></div>
        <div class="alert-message" id="bottleneck-message"></div>
        <div class="alert-resolution" id="bottleneck-resolution"></div>
    </div>
    <div class="alert-visual" id="bottleneck-visual"></div>
</div>

// Add to your builder logic
function updateBottleneckAnalysis() {
    const cpu = selectedCPU;
    const gpu = selectedGPU;
    
    if (!cpu || !gpu) return;
    
    // Get bottleneck percentage
    const bottleneck = calculateBottleneck(cpu.sku, gpu.sku);
    const resolution = selectedResolution; // 1080p, 1440p, 4K
    
    // Show alert
    document.getElementById('bottleneck-alert').style.display = 'flex';
    
    document.getElementById('bottleneck-title').innerText = `Bottleneck Alert: ${cpu.name} vs ${gpu.name}`;
    
    document.getElementById('bottleneck-message').innerText = 
        `At ${resolution}, your CPU will bottleneck the GPU by ${bottleneck.percentage}%.`;
    
    // Update visual
    const visualBar = document.getElementById('bottleneck-visual');
    visualBar.style.width = `${bottleneck.percentage}%`;
    visualBar.style.backgroundColor = bottleneck.color;
    
    // Show recommendations
    const recommendations = getAlternativeSuggestions(cpu, gpu, resolution);
    document.getElementById('bottleneck-resolution').innerText = 
        `💡 Tip: ${recommendations.join(' ')}`;
}

Idea #4
Build Comparison Mode
Let users save two or three builds side by side in a comparison table — showing each component, total price, estimated wattage, and performance tier. Like a diff view for PC builds.
User picks "Advanced Build" vs their custom build, sees exactly where the differences are and whether the price delta is justified.



This is an excellent feature that directly supports the “Glass Cockpit” experience. It adds immense value by helping users make informed decisions and justify their spending.

Here’s a realistic, step-by-step implementation plan:

Phase 1: Backend & Data
1. Saved Builds Storage
Create a table to store user-saved builds:

CREATE TABLE saved_builds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    build_name VARCHAR(255) NOT NULL,
    build_data JSON NOT NULL,  // Stores component SKUs, settings, etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
2. Build Logic
Each build should include:

Components selected (CPU, GPU, RAM, storage, etc.)

Settings (resolution, refresh rate, overclocking preferences)

Performance metrics (estimated FPS, bottlenecks)

Estimated wattage

Total price

Phase 2: Frontend Builder Integration
1. Save Build Functionality
In your PC builder, add a "Save Build" button:

// When user clicks "Save Build"
const buildData = {
    cpu: selectedCPU.sku,
    gpu: selectedGPU.sku,
    resolution: selectedResolution,
    // ... other components
};

fetch('api/save-build.php', {
    method: 'POST',
    body: JSON.stringify({
        name: 'My Gaming Build',
        data: buildData
    })
})

2. Comparison Page (/builds/compare.php)
Users can select 2 or 3 saved builds to compare side by side.

Phase 3: Comparison View
Render a table showing:

Feature	Build A	Build B	Build C
Component	CPU Name	CPU Name
Price	€1200	€1500
Estimated FPS (1080p)	144 FPS	165 FPS
Bottleneck	12% CPU Bottleneck	Balanced
Estimated Wattage	450W	520W
3. Visual Enhancements
Use colored indicators for differences:

🟢 Better value

🔴 More expensive

🟡 Similar

Highlight the biggest differences (e.g., “Save €300 for 10% more FPS”).

Phase 4: Performance Analysis
1. Detailed Breakdown
Show exactly where the price difference comes from:

Why Build B costs €300 more:

RTX 4070 Ti ($600) vs RTX 4060 Ti ($400): +€200

850W PSU vs 750W PSU: +€50

Premium Case: +€50

2. ROI Calculation
“Is spending €300 more worth it?”

Build A: €1200 → 144 FPS

Build B: €1500 → 165 FPS (20% FPS increase for 25% price increase)

In this case, Build A offers better value.



Example: save-build.php (Backend)
<?php
// src/api/save-build.php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../bootstrap/application.php';

// Assuming user is authenticated
$userId = $_SESSION['user_id'];
$buildName = $_POST['name'];
$buildData = $_POST['data'];

// Save the build
$stmt = db()->prepare("
    INSERT INTO saved_builds (user_id, build_name, build_data)
    VALUES (?, ?, ?)
");
$stmt->execute([$userId, $buildName, json_encode($buildData)]);

echo json_encode(['success' => true]);




Example: build-comparison.php (Frontend)
<?php
// src/builds/compare.php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../bootstrap/application.php';
?>

<div class="build-comparison">
    <div class="comparison-header">
        <h2>Build Comparison</h2>
        <select id="build-a-select">...</select>
        <select id="build-b-select">...</select>
        <select id="build-c-select">...</select>
    </div>

    <div class="comparison-table">
        <table>
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Build A</th>
                    <th>Build B</th>
                    <th>Build C</th>
                </tr>
            </thead>
            <tbody id="comparison-body">
                <!-- Rows will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<script>
function compareBuilds() {
    const buildA = savedBuilds[document.getElementById('build-a-select').value];
    const buildB = savedBuilds[document.getElementById('build-b-select').value];
    const buildC = savedBuilds[document.getElementById('build-c-select').value] || null;

    document.getElementById('comparison-body').innerHTML = `
        <tr>
            <td>Price</td>
            <td class="${buildA.price > buildB.price ? 'worse' : 'better'}">${buildA.price}</td>
            <td class="${buildB.price > buildA.price ? 'worse' : 'better'}">${buildB.price}</td>
            ${buildC ? `<td class="${buildC.price > buildA.price && buildC.price > buildB.price ? 'worse' : 'better'}">${buildC.price}</td>` : ''}
        </tr>
        <!-- Add more rows for other features -->
    `;
}
</script>



So if someone already owns or configured a build with an RTX 4060 + i5-13600K, they'd input those specs and get back a list like:

- Cyberpunk 2077 → High settings, 1440p/60fps ✓
- Warzone → Ultra, 1440p/120fps ✓
- 4K gaming → Not recommended ✗

Two different tools, both valuable. Yours covers the discovery flow, this one covers the validation flow for people who already know their hardware.



**Idea #8**

**"Build for a Friend" Gift Mode**

A guided flow where someone buys a PC build as a gift — they set a budget, pick a use case (gaming, school, creative), and the system generates a build. At checkout they can add a gift message and choose delivery to a different address.

Very relevant for Moroccan market — parents buying for students, Eid gifts, back-to-school season.


Idea #9
Community Build Showcase
A page where customers who bought from Maroc PC can submit photos of their finished setup — component list, total spent, a short caption. Other users can like and save builds they like and click through to buy the same components.
Essentially user-generated social proof that doubles as a product discovery tool. Very powerful for a market where trust is a major purchase barrier


Idea #10
CPU / GPU Benchmark Database
A dedicated page with real benchmark data for every component you sell — gaming FPS at 1080p/1440p/4K, Cinebench scores, thermal performance under load. Sourced from reputable sites like TechPowerUp and Tom's Hardware.
Turns your product pages from a catalog into a reference tool. Users stop leaving your site to check benchmarks on YouTube or Google — everything they need to make a decision is already there.


Idea #11
"Upgrade My Current PC" Tool
User inputs what they currently own — CPU, GPU, RAM, motherboard — and the tool analyzes their existing build and recommends the single most impactful upgrade for their budget.
"Your biggest bottleneck is your GPU. Replacing your GTX 1060 with an RX 7600 for 2,800 MAD would double your framerates in most games."
Targets the massive segment of Moroccan users who already have a PC but want to improve it — not just new builders.


Idea #13
Component Compatibility Checker (Standalone Tool)
A dedicated tool separate from the Builder — user inputs any two or more components they're considering (from anywhere, not just your catalog) and gets a compatibility verdict. Socket match, DDR5 vs DDR4, PCIe version, case clearance, PSU wattage sufficiency.
The key differentiator: it works even for parts they didn't buy from you. Builds trust and positions Maroc PC as the go-to reference tool for the Moroccan PC community, not just a store.

Idea #15
"Is This a Good Deal?" Scanner
User pastes a product listing URL or types a component name and price they found on Jumia, Avito, or a Facebook Marketplace post — the tool compares it against your catalog and market price and returns a verdict: "Good deal — 15% below market", "Fair price", or "Overpriced — you can get this for X MAD less."
Positions Maroc PC as a trusted price authority for the Moroccan market. Even if they don't buy from you immediately, they come back because you gave them honest advice.


Idea #21
Live Stock Indicator on Product Cards
Show real-time stock levels directly on product listing cards — not just "In Stock / Out of Stock" but a diagnostic-style indicator like [STOCK: 3 UNITS] or [STOCK: CRITICAL — 1 LEFT] in JetBrains Mono.
Creates genuine urgency without fake countdown timers. Aligns perfectly with your Glass Cockpit aesthetic — it's honest data, not marketing manipulation. Users who see 1 unit left make decisions faster.


Idea #23
Component "In the Box" Checker
On each product page, a clear expandable section showing exactly what's included in the box — does the CPU come with a stock cooler or not, does the motherboard include SATA cables, does the case include fans. With a clear warning if something critical is missing and a direct link to the compatible accessory.
First-time Moroccan builders frequently get caught out by this — ordering a CPU with no cooler included and not realizing until assembly day.


Idea #25
Build "Health Score" Dashboard
After a user completes their build configuration, show an overall score out of 100 broken into diagnostic sub-scores — Balance (CPU/GPU ratio), Thermals (cooling vs TDP), Storage (speed + capacity), Future-proofing (PCIe gen, DDR5, upgrade headroom), Value (price vs performance tier).
Displayed as a Glass Cockpit style readout — monospace labels, cyan indicators, no fluff. Gives users instant confidence their build is solid or clear direction on what to fix before buying.

Idea #26
Seasonal Deal Campaigns Page
A dedicated deals page that activates around Moroccan key shopping moments — Ramadan, Eid Al Fitr, Eid Al Adha, back-to-school September, and Black Friday. Countdown timer to deal expiry, badge showing discount percentage, and a curated selection of components and builds on sale.
No Moroccan PC retailer is doing structured seasonal campaigns with proper landing pages. Most just post on Facebook and the deal disappears in the feed.

Component Availability Radar
A page showing which high-demand components are currently hard to find in Morocco — RTX 5090, specific CPUs, popular SSDs — with an estimated restock date and a "Notify Me" button. Sourced from your own inventory data.
Positions Maroc PC as the most transparent and informed retailer in the Moroccan market. Users bookmark it and check regularly — building a habit of visiting your site even when not actively buying.

Idea #28
Build Timeline / Assembly Guide
After a user finalizes their build in the Builder, generate a step-by-step assembly guide specific to their exact components — "Install your Noctua NH-D15 before the motherboard goes in the case because of clearance", "Your MSI B650 requires BIOS update before Ryzen 9800X3D will POST."
Not a generic YouTube tutorial link — a personalized guide based on the actual parts they bought from you. Reduces post-purchase anxiety and support tickets dramatically.


Idea #30
CPU / GPU Pairing Recommender
User selects a GPU they want to buy — the tool recommends the optimal CPU pairing at three budget levels: "Best match under 2,000 MAD", "Best match under 4,000 MAD", "No-compromise pairing." With a bottleneck score for each combination.
Works the other way too — select a CPU, get GPU recommendations. Helps users who know one component they want but are unsure what to pair it with. Very common situation for Moroccan buyers upgrading one part at a time.


Idea #32
"Complete My Build" Upsell Engine
When a user adds a CPU to cart, the system detects what's missing from a complete build and shows a smart prompt — "You have a CPU but no motherboard, cooler, or RAM. Complete your build from 8,500 MAD." With one-click add of a recommended compatible bundle.
Not random cross-sell — contextually aware of what they already have and what's genuinely missing. Increases average order value significantly without feeling pushy.


Yes exactly — you need two things:

**1. New product categories in data.js**
Add an `accessories` category with entries like:

- Thermal paste (Noctua NT-H1, Arctic MX-6, Thermal Grizzly Kryonaut)
- SATA data cables
- Cable ties / velcro straps
- Anti-static wrist strap
- PCIe power adapters
- M.2 heatsinks
- Case fans (120mm, 140mm budget options)

Prices range from 30 MAD to 200 MAD — high margin, low stock complexity.

**2. Checkout upsell logic**

A small rule engine that checks cart contents and surfaces relevant accessories:

```js
if (cart.has('cpu') || cart.has('cooling')) 
    → suggest thermal paste
if (cart.has('storage') && storage.interface === 'SATA')
    → suggest SATA cables
if (cart.has('case') || cart.has('gpu'))
    → suggest cable ties
if (cart.has('cpu') && user.isFirstBuild)
    → suggest anti-static strap
```

so you can drop them straight in? I can research accurate Moroccan market prices and realistic specs for each item.


Idea #34
PC Builder "Budget Slider" Starting Point
Instead of starting the builder with a blank slate, show a single budget slider as the very first interaction — user drags to their budget and instantly sees what tier of build is realistic: "At 8,000 MAD you can build a solid 1080p gaming PC" with a preview of the component tier they'd get.

Maroc PC Points Referral Program
Existing users get a unique referral link. When someone signs up and makes their first purchase through that link, both the referrer and the new user get bonus loyalty points — say 500 points each (worth 50 MAD discount).
Word of mouth is how Moroccan tech buyers discover new stores — Facebook groups, WhatsApp chats, friend recommendations. Formalizing that with a reward turns your existing customers into an acquisition channel at near-zero cost.


PWA:
1. manifest.json in your root directory:
json{
  "name": "Maroc PC",
  "short_name": "Maroc PC",
  "description": "Premium PC components delivered fast",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#050505",
  "theme_color": "#00f5d4",
  "icons": [
    { "src": "images/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "images/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
2. Register a service worker in your main JS:
jsif ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
3. Basic sw.js for offline caching and push support — start minimal, expand later.
4. Two app icons — 192×192 and 512×512 PNG versions of your Maroc PC logo.
5. Add to your <head>:
html<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00f5d4">
That's the minimum viable PWA. Once installed users get the home screen icon, standalone mode with no browser chrome, and the foundation for push notifications.
Want me to generate the full sw.js and manifest.json files ready to drop in?You said: i use evolution api for whatsaap verificationi use evolution api for whatsaap verification7:51 PMClaude responded: Perfect — you already have WhatsApp infrastructure in place.Perfect — you already have WhatsApp infrastructure in place. That means Option 3 is essentially free for you to add on top of Option 1.
Since you have Evolution API connected, you can reuse the same integration for:

Price drop alerts — wishlist item drops below threshold → send WhatsApp message
Stock alerts — out of stock item restocked → send WhatsApp message
Order confirmations — already doing this for verification presumably
Build share — "Share your build via WhatsApp" button already exists

The push notification flow would be:
User wishlists RTX 5070 → price drops → 
your backend detects change → 
Evolution API sends WhatsApp message to user's number →
"Sa7bi, had GPU naqsat f prix — 7,999 MAD daba 🔔"
You already have the user's phone number from WhatsApp verification, the Evolution API connection, and the wishlist. It's just connecting three things that already exist.
What's missing:

A price monitoring cron job that checks for changes
A user preference to opt into alerts
The message template in your Evolution API


Idea #44
Student / Back-to-School Bundle Page
A dedicated page targeting students — curated bundles under 8,000 MAD combining a laptop or budget desktop with a monitor, keyboard, and mouse. Filtered by use case: Engineering student, Medical student, Design student, General studies.
Moroccan university enrollment peaks every September. A landing page optimized for "PC étudiant Maroc" or "ordinateur pour étudiant Maroc" would capture significant organic search traffic from parents and students during back-to-school season — a completely uncontested keyword space right now.


Idea #45
Component "Generation Explainer" Tooltips
When a user hovers over spec tags like "PCIe 5.0", "DDR5", "Zen 5", or "Blackwell" — show a simple one-line explanation of what that means and why it matters. "PCIe 5.0 — doubles bandwidth over PCIe 4.0, future-proofs your build for next-gen GPUs."
Moroccan buyers at the budget and mid range often don't understand generation differences and make purchase mistakes as a result — buying a DDR4 board when they need DDR5, or not understanding why PCIe 5.0 matters for their NVMe SSD choice. This removes that knowledge gap without making the interface feel like a tutorial.


Idea #46
Order Status Page with Build Progress Tracking
Beyond standard "shipped / delivered" tracking — if a user ordered assembly services, show the actual build progress stages: "Components received → Assembly in progress → BIOS updated → Stress test running → Packaged → Shipped." With timestamps for each stage.
Moroccans are anxious about high-value orders. Showing granular progress reduces support inquiries dramatically and builds enormous trust — the user feels like they can see exactly what's happening to their 20,000 MAD build in real time.


Idea #47
"Is My PC Future-Proof?" Analyzer
User inputs their current or planned build and selects a time horizon — 2 years, 3 years, 5 years. The tool scores the build on future-proofing across key dimensions: PCIe generation headroom, VRAM sufficiency for upcoming games, DDR5 vs DDR4, socket longevity (AM5 has longer roadmap than LGA 1700), PSU wattage headroom for GPU upgrade.
Returns a diagnostic readout — "Your build scores 78/100 for 3-year longevity. Weak point: 12GB VRAM on the RTX 5070 may feel limiting by 2028 for 4K gaming."



"Seen It Cheaper?" Price Match Request
A simple button on every product page — "Seen it cheaper?" — where users input a competitor URL or screenshot and their contact info. You review and respond within 24 hours with either a price match or explanation.
Moroccan buyers comparison shop heavily on Facebook Marketplace, Jumia, and local stores before committing. Instead of losing them silently to a competitor, this gives them a reason to stay and complete the purchase with you — where they also get warranty, compatibility guarantee, and build services that no random seller offers.+ linked to admin dashboard


Builder Engine

Interactive Clearance Check ("Will it Fit?") — GPU length vs case, cooler height vs RAM clearance. Highly technical, perfectly on-brand, reduces returns
Hardware Synergy Perks — SAM badge when AMD CPU + AMD GPU paired. Small detail, big trust signal
"Oops I Forgot" Smart Checklist — detects missing Wi-Fi card, thermal paste, SATA cables. Directly feeds your accessories upsell idea
Visual PSU Overhead Gauge — arc gauge showing wattage buffer for future upgrades. Pure Glass Cockpit
"Downgrade to Budget" Quick Swap — one button to hit a lower price while keeping FPS. Genius for budget-sensitive Moroccan buyers

AI & Smart Features

Dynamic Out-of-Stock Alternative Engine — auto-suggests 3 compatible alternatives when item goes OOS. High conversion saver
FPS Gauge Simulator on Product Pages — estimated FPS per game per GPU/CPU page. Keeps users on your site instead of YouTube
Personalized Upgrade Advisor — "You bought a GTX 1660 two years ago, here's your upgrade path." Requires purchase history but extremely high value
Interactive Noise Level Estimator — dB estimate based on fans + cooler + GPU selection. Genuinely useful, no competitor does this
Interactive Bottleneck Heatmap — visual balance indicator. Better presentation of your bottleneck analyzer idea

Moroccan Market

Moroccan City Pickup Points Map — Casablanca, Rabat, Marrakech, Fez, Tangier click & collect. Massive trust builder for high-value orders
COD Security Deposit — small deposit via Cash Plus / Wafacash before assembly on high-value builds. Solves fake order problem directly
Local Bank Transfer Receipt Uploader — CIH / Attijariwafa receipt upload portal. Essential for Moroccan payment reality
Moroccan Tech Creator Curated Builds — partner with local YouTubers/streamers for "Influencer Builds" category. Zero-cost marketing channel

E-Commerce

"Build & Save" Bundle Discount Tiers — progress bar showing "add RAM + SSD to unlock 5% off." Increases AOV naturally
Abandoned Cart Locked Price Guarantee — lock prices for 48h when user abandons cart. Extremely effective for price-volatile components
Warranty & Health Dashboard — active warranty countdowns, serial numbers, maintenance request button. Builds long-term relationship
Instant Trade-in Valuation — select old hardware category + condition → get discount voucher. Captures upgrade buyers perfectly

















