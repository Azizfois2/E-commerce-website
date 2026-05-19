<?php
require_once 'bootstrap.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    die("Invalid Order ID.");
}

$clientId = (int)$_SESSION['client_id'];
$pdo = db();

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND client_id = ?");
$stmt->execute([$orderId, $clientId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied.");
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.category, p.description as prod_desc
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categorize items
$components = [
    'cpu' => null,
    'gpu' => null,
    'mobo' => null,
    'ram' => null,
    'cooler' => null,
    'storage' => null,
    'psu' => null,
    'case' => null,
    'accessories' => []
];

foreach ($items as $item) {
    $cat = strtolower($item['category'] ?? '');
    $name = $item['name_at_time'];
    
    if (strpos($cat, 'processor') !== false || strpos($cat, 'cpu') !== false) {
        $components['cpu'] = $name;
    } elseif (strpos($cat, 'graphics') !== false || strpos($cat, 'gpu') !== false || strpos($cat, 'carte graphique') !== false) {
        $components['gpu'] = $name;
    } elseif (strpos($cat, 'motherboard') !== false || strpos($cat, 'carte mere') !== false) {
        $components['mobo'] = $name;
    } elseif (strpos($cat, 'memory') !== false || strpos($cat, 'ram') !== false) {
        $components['ram'] = $name;
    } elseif (strpos($cat, 'cooler') !== false || strpos($cat, 'refroidissement') !== false || strpos($cat, 'ventilateur') !== false) {
        $components['cooler'] = $name;
    } elseif (strpos($cat, 'storage') !== false || strpos($cat, 'ssd') !== false || strpos($cat, 'hdd') !== false || strpos($cat, 'disque') !== false) {
        $components['storage'] = $name;
    } elseif (strpos($cat, 'power') !== false || strpos($cat, 'psu') !== false || strpos($cat, 'alimentation') !== false) {
        $components['psu'] = $name;
    } elseif (strpos($cat, 'case') !== false || strpos($cat, 'boitier') !== false) {
        $components['case'] = $name;
    } else {
        $components['accessories'][] = $name;
    }
}

// Generate dynamic steps based on components
$steps = [];

// Step 1: Prep
$caseName = $components['case'] ?? "your PC Case";
$steps[] = [
    'title' => 'Workspace Preparation',
    'icon' => 'fa-screwdriver-wrench',
    'desc' => "Unbox your brand new <strong>$caseName</strong>. Remove both side panels (tempered glass and metal backside cover) and lay them flat in a safe place. Prepare your anti-static workspace, gather your screwdriver (magnetic tip highly recommended), and organize the screws bundled with your case.",
    'tips' => ['Keep the case screws separate; Motherboard screws are usually smaller than PSU and Fan screws.', 'A clean non-carpeted floor or large wooden table works best to avoid static discharge.']
];

// Step 2: CPU
$cpuName = $components['cpu'] ?? "Processor (CPU)";
$moboName = $components['mobo'] ?? "Motherboard";
$cpuBrand = (strpos(strtolower($cpuName), 'amd') !== false || strpos(strtolower($cpuName), 'ryzen') !== false) ? 'AMD' : 'Intel';

$cpuInstallDesc = "";
$cpuTips = [];
if ($cpuBrand === 'AMD') {
    $cpuInstallDesc = "Take your <strong>$moboName</strong> out of its anti-static bag and place it on top of its cardboard box (never on a conductive surface). Lift the CPU socket retention arm. Carefully align the gold triangle on the corner of your <strong>$cpuName</strong> with the triangle mark on the motherboard socket. Lower the CPU flat into the socket with zero pressure (it should drop in naturally), then close the arm firmly.";
    $cpuTips = ['AMD AM5/AM4 sockets have direct keyed orientation notches. Ensure they line up perfectly.', 'Do not press the CPU down! If it does not drop in, check for bent pins (AM4) or socket damage (AM5).'];
} else {
    $cpuInstallDesc = "Take your <strong>$moboName</strong> out of its anti-static bag and place it on top of its cardboard box. Open the CPU socket load lever and lift the load plate. Carefully place your <strong>$cpuName</strong> into the socket, matching the orientation notches. Lower the load plate, then push down and lock the lever. The plastic protective cover will pop off automatically—keep it for warranty purposes.";
    $cpuTips = ['Never touch the golden contact pins inside the Intel motherboard socket.', 'Store the plastic socket cover safely; motherboards cannot be returned/RMA\'d without it.'];
}

$steps[] = [
    'title' => 'Installing the Processor (CPU)',
    'icon' => 'fa-microchip',
    'desc' => $cpuInstallDesc,
    'tips' => $cpuTips
];

// Step 3: RAM
$ramName = $components['ram'] ?? "DDR Memory (RAM)";
$steps[] = [
    'title' => 'Installing the Memory (RAM)',
    'icon' => 'fa-memory',
    'desc' => "Locate the RAM slots on your motherboard. If you have a single kit of 2 RAM sticks (e.g. <strong>$ramName</strong>), push down the retention clips on slots <strong>A2 and B2</strong> (the 2nd and 4th slots starting from the CPU socket). Align the notch on your RAM stick with the key in the slot, and push down firmly on both sides simultaneously until you hear a solid *click*.",
    'tips' => ['Installing dual-channel RAM in slots 2 and 4 ensures optimum performance and high-speed stability (XMP/EXPO).', 'Ensure the notch aligns perfectly. RAM is keyed and only fits one way.']
];

// Step 4: M.2 SSD
$storageName = $components['storage'] ?? "M.2 SSD Storage";
$steps[] = [
    'title' => 'Installing the M.2 SSD Storage',
    'icon' => 'fa-hard-drive',
    'desc' => "Unscrew the primary M.2 heatsink shield on your motherboard (usually directly below the CPU socket). Insert your <strong>$storageName</strong> into the M.2 slot at a 30-degree angle until it is fully seated. Press the SSD flat and secure it using the motherboard's pre-installed toolless M.2 latch or tiny M.2 screw. Peel the protective plastic film off the thermal pad on the heatsink, and screw the heatsink shield back in place.",
    'tips' => ['Always remove the blue/clear plastic film from the thermal pad behind the M.2 heatsink to avoid overheating.', 'The primary M.2 slot is directly wired to the CPU for maximum read/write speeds.']
];

// Step 5: CPU Cooler
$coolerName = $components['cooler'] ?? "CPU Cooler";
$isAio = (strpos(strtolower($coolerName), 'liquid') !== false || strpos(strtolower($coolerName), 'water') !== false || strpos(strtolower($coolerName), 'aio') !== false || strpos(strtolower($coolerName), 'corsair icue') !== false || strpos(strtolower($coolerName), 'kraken') !== false || strpos(strtolower($coolerName), 'castle') !== false || strpos(strtolower($coolerName), 'refroidissement liquide') !== false);

$coolerDesc = "";
$coolerTips = [];
if ($isAio) {
    $coolerDesc = "Prepare your liquid cooling loop (<strong>$coolerName</strong>). Screw the cooling fans onto the radiator in a 'push' or 'pull' intake/exhaust setup. Attach the corresponding mounting bracket to the pump head based on your CPU socket type ($cpuBrand). Apply a pea-sized dot of thermal paste to the CPU (if not pre-applied), seat the pump block onto the CPU securely, and tighten the thumb nuts in a crosswise pattern. Mount the radiator to the top or front panel of your case.";
    $coolerTips = ['Radiator mounted at the top of the case is ideal for optimal bubble removal and pump longevity.', 'Double-check if the pump head has a protective transparent plastic sticker on the copper plate. Peel it off first!'];
} else {
    $coolerDesc = "Prepare your air cooler (<strong>$coolerName</strong>). Install the corresponding Intel or AMD backplate/brackets onto your motherboard. Apply a small, pea-sized dot of thermal paste to the center of the CPU (unless pre-applied on the cooler base). Line up the cooler's mounting screws with the brackets, tighten them evenly in an alternating fashion, and attach the fan(s) to the heatsink. Plug the fan wire into the `CPU_FAN` header on the motherboard.",
    $coolerTips = ['Always connect the cooler fan/pump cable to the `CPU_FAN` or `AIO_PUMP` header so the motherboard can dynamically regulate speeds.', 'Ensure the fan direction pushes air towards the back exhaust of the case.'];
}

$steps[] = [
    'title' => 'Mounting the CPU Cooler',
    'icon' => 'fa-fan',
    'desc' => $coolerDesc,
    'tips' => $coolerTips
];

// Step 6: Motherboard Seating
$steps[] = [
    'title' => 'Seating the Motherboard in the Case',
    'icon' => 'fa-align-center',
    'desc' => "Verify that the case standoff screws align perfectly with the mounting holes of your motherboard. Position the case flat. Align your motherboard with the rear I/O shield opening on the back of the case. Carefully lower the motherboard onto the standoffs, ensuring rear ports pass cleanly through the I/O shield. Secure the motherboard using the matching screws in a cross pattern without overtightening.",
    'tips' => ['Modern motherboards have pre-integrated I/O shields, while budget ones have separate steel shields. Remember to pop the metal shield into the case frame before putting the motherboard in!', 'Do not overtighten motherboard screws to avoid cracking the multi-layered PCB traces.']
];

// Step 7: PSU
$psuName = $components['psu'] ?? "Power Supply Unit (PSU)";
$steps[] = [
    'title' => 'Installing the Power Supply (PSU)',
    'icon' => 'fa-plug',
    'desc' => "Slide your <strong>$psuName</strong> into the bottom shroud compartment of your case, with the intake fan facing downwards (allowing it to pull fresh air through the bottom dust filter). Secure it firmly from the rear of the case using four hex screws. Route the 24-pin motherboard cable and the 8-pin CPU EPS power cables through the back rubber grommets to their respective headers.",
    'tips' => ['If using a modular PSU, plug in only the cables you need (24-pin, CPU EPS, PCIe) BEFORE mounting it inside the cramped shroud.', 'Orienting the PSU fan downwards isolates its airflow from the hot internal chamber of your PC.']
];

// Step 8: GPU
$gpuName = $components['gpu'] ?? "Graphics Card (GPU)";
$isNvidia40 = (strpos(strtolower($gpuName), 'rtx 40') !== false || strpos(strtolower($gpuName), 'rtx 50') !== false || strpos(strtolower($gpuName), '16pin') !== false || strpos(strtolower($gpuName), '12vhpwr') !== false);

$gpuDesc = "Locate the primary PCIe x16 slot on your motherboard (the top reinforced slot). Unscrew and remove the matching metal PCI expansion slot covers on the back of the case. Push down the plastic locking tab at the end of the PCIe slot. Align your <strong>$gpuName</strong> with the slot and push it down firmly until you hear the click. Secure the GPU bracket to the case with thumb screws. Connect the PCIe power cables.";
if ($isNvidia40) {
    $gpuDesc .= " **CRITICAL**: Use the dedicated high-power 12VHPWR (16-pin) cable or the adapter. Make sure it is pushed fully flush into the GPU power port until it clicks to avoid any high-resistance heat buildup.";
} else {
    $gpuDesc .= " Connect the standard 8-pin (6+2) PCIe power cables from your PSU, making sure they lock securely.";
}

$steps[] = [
    'title' => 'Installing the Graphics Card (GPU)',
    'icon' => 'fa-fill-drip',
    'desc' => $gpuDesc,
    'tips' => [
        'Always install the GPU in the top slot as it has the highest PCIe lane bandwidth (direct link to the CPU).',
        $isNvidia40 ? 'Ensure the 16-pin cable has at least 3-4 cm of straight clearance before bending it to prevent connector fatigue.' : 'Ensure power connectors click firmly in place.'
    ]
];

// Step 9: Front Panel & Cable Management
$steps[] = [
    'title' => 'Front Panel Headers & Cabling',
    'icon' => 'fa-network-wired',
    'desc' => "Locate the small front panel pins on the bottom-right corner of the motherboard (`JFP1`). Plug in the tiny connectors for Power Switch (`PW_SW`), Reset Switch (`RES_SW`), Power LED, and HDD LED (match '+' and '-' symbols!). Connect the thicker USB 3.0 header, USB-C header, and HD Audio connector. Use zip ties to neatly tidy up the backside cables so the metal side panel closes effortlessly.",
    'tips' => ['Front panel pinouts are extremely small. Refer to your motherboard manual or the tiny print on the board for the exact + / - layout.', 'A tidy back-chamber ensures unobstructed air routing and makes future upgrades a breeze.']
];

// Step 10: Boot
$steps[] = [
    'title' => 'First Boot & UEFI BIOS Setup',
    'icon' => 'fa-desktop',
    'desc' => "Connect your power cable and turn on the PSU switch. **IMPORTANT**: Plug your monitor's HDMI/DisplayPort cable directly into the **Graphics Card (GPU) ports**, NOT the motherboard outputs. Press your case power button. Keep tapping the `DEL` or `F2` key to enter the UEFI BIOS. Verify all RAM is detected, and enable **XMP (Intel)** or **EXPO (AMD)** to run your <strong>$ramName</strong> at its advertised high speeds.",
    'tips' => ['Connecting the monitor to the motherboard will bypass your high-performance GPU, giving you black screens or sluggish integrated graphics.', 'Install Windows using a bootable USB drive, then load up latest motherboard chipset and GPU drivers.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Assembly Guide — Maroc PC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <style>
        body {
            font-family: 'Syne', sans-serif;
            background: var(--page-bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        .guide-container {
            max-width: 900px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }
        .guide-header {
            background: linear-gradient(135deg, rgba(0,245,212,0.06) 0%, transparent 60%);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 32px;
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .guide-header h1 {
            font-family: 'Orbitron', monospace;
            font-size: 1.8rem;
            margin: 0 0 8px;
            color: var(--text);
        }
        .guide-header p {
            color: var(--muted);
            margin: 0;
            font-size: 0.9rem;
        }
        .progress-indicator {
            text-align: right;
        }
        .progress-pct {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--cyan);
        }
        .progress-bar-outer {
            width: 160px;
            height: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 99px;
            margin-top: 6px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .progress-bar-inner {
            height: 100%;
            background: var(--cyan);
            width: 0%;
            transition: width 0.4s ease;
        }
        .step-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 24px;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        .step-card.completed {
            border-color: rgba(0, 230, 118, 0.25);
            background: rgba(0, 230, 118, 0.01);
            opacity: 0.85;
        }
        .step-card.active {
            border-color: var(--cyan);
            box-shadow: 0 0 20px rgba(0, 245, 212, 0.05);
        }
        .step-top {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .step-checkbox {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--card-bg);
            flex-shrink: 0;
            color: transparent;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .step-card.completed .step-checkbox {
            background: var(--green);
            border-color: var(--green);
            color: #000;
        }
        .step-checkbox:hover {
            border-color: var(--cyan);
        }
        .step-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(0,245,212,0.08);
            color: var(--cyan);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
            border: 1px solid rgba(0,245,212,0.15);
        }
        .step-card.completed .step-icon {
            background: rgba(0, 230, 118, 0.08);
            color: var(--green);
            border-color: rgba(0, 230, 118, 0.15);
        }
        .step-content {
            flex: 1;
        }
        .step-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--cyan);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }
        .step-card.completed .step-number {
            color: var(--green);
        }
        .step-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 12px;
            color: var(--text);
        }
        .step-desc {
            font-size: 0.92rem;
            line-height: 1.6;
            color: var(--text-dim);
            margin: 0 0 16px;
        }
        .step-desc strong {
            color: var(--text);
        }
        .step-tips {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 20px;
        }
        .step-tips-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--cyan);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .step-card.completed .step-tips-title {
            color: var(--green);
        }
        .step-tips-list {
            margin: 0;
            padding-left: 18px;
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.5;
        }
        .step-tips-list li {
            margin-bottom: 6px;
        }
        .step-tips-list li:last-child {
            margin-bottom: 0;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 24px;
            transition: color 0.2s;
        }
        .back-btn:hover {
            color: var(--cyan);
        }
        @media (max-width: 768px) {
            .guide-header { padding: 24px; }
            .progress-indicator { text-align: left; margin-top: 12px; }
            .step-card { padding: 20px; }
            .step-top { gap: 14px; }
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
                <a href="account.php" class="nav-link active">Account</a>
            </nav>
        </div>
    </header>

    <div class="guide-container">
        <a href="account.php?tab=orders" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        
        <div class="guide-header">
            <div>
                <span class="eyebrow" style="color: var(--cyan);">Order #<?= $orderId ?></span>
                <h1>Interactive PC Assembly Guide</h1>
                <p>Follow this custom-tailored step-by-step checklist to build your absolute dream setup.</p>
            </div>
            <div class="progress-indicator">
                <div class="progress-pct" id="progressPct">0%</div>
                <div style="font-size:0.75rem; color: var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">COMPLETED</div>
                <div class="progress-bar-outer">
                    <div class="progress-bar-inner" id="progressBarInner"></div>
                </div>
            </div>
        </div>

        <div class="steps-list">
            <?php foreach ($steps as $index => $step): ?>
                <div class="step-card" id="step-card-<?= $index ?>">
                    <div class="step-top">
                        <div class="step-checkbox" onclick="toggleStep(<?= $index ?>)" id="chk-<?= $index ?>"><i class="fas fa-check"></i></div>
                        <div class="step-icon"><i class="fas <?= $step['icon'] ?>"></i></div>
                        <div class="step-content">
                            <div class="step-number">Step <?= $index + 1 ?> of <?= count($steps) ?></div>
                            <h3 class="step-title"><?= $step['title'] ?></h3>
                            <p class="step-desc"><?= $step['desc'] ?></p>
                            
                            <?php if (!empty($step['tips'])): ?>
                                <div class="step-tips">
                                    <div class="step-tips-title"><i class="fas fa-lightbulb"></i> PRO TIPS</div>
                                    <ul class="step-tips-list">
                                        <?php foreach ($step['tips'] as $tip): ?>
                                            <li><?= $tip ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const totalSteps = <?= count($steps) ?>;
        const orderId = <?= $orderId ?>;
        let completedSteps = [];

        // Load progress from localStorage
        const stored = localStorage.getItem(`pc_build_progress_${orderId}`);
        if (stored) {
            completedSteps = JSON.parse(stored);
        }

        function updateProgress() {
            const pct = Math.round((completedSteps.length / totalSteps) * 100);
            document.getElementById('progressPct').innerText = `${pct}%`;
            document.getElementById('progressBarInner').style.width = `${pct}%`;
            
            // Manage cards states
            for (let i = 0; i < totalSteps; i++) {
                const card = document.getElementById(`step-card-${i}`);
                if (!card) continue;
                card.classList.remove('completed', 'active');
                
                if (completedSteps.includes(i)) {
                    card.classList.add('completed');
                } else if (completedSteps.length === i || (completedSteps.length === 0 && i === 0)) {
                    card.classList.add('active');
                }
            }
        }

        function toggleStep(index) {
            const idx = completedSteps.indexOf(index);
            if (idx === -1) {
                completedSteps.push(index);
                // Play subtle success click if audio allowed later
            } else {
                completedSteps.splice(idx, 1);
            }
            localStorage.setItem(`pc_build_progress_${orderId}`, JSON.stringify(completedSteps));
            updateProgress();
        }

        // Initialize UI
        updateProgress();
    </script>
</body>
</html>
