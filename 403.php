<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied | Maroc PC</title>
    <meta name="description" content="You don't have permission to access this resource on Maroc PC.">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Mono&family=Syne:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <style>
        :root {
            --bg: #0a0b0e;
            --bg2: #12131a;
            --white: #e8eaed;
            --muted: #6b7280;
            --cyan: #00f5d4;
            --orange: #ff6b35;
            --red: #ff3b5c;
            --border: #1e2229;
            --red-glow: rgba(255, 59, 92, 0.08);
        }
        html[data-theme="light"] {
            --bg: #f0f2f5;
            --bg2: #ffffff;
            --white: #1a1a2e;
            --muted: #6b7280;
            --border: #d1d5db;
            --red-glow: rgba(255, 59, 92, 0.12);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Syne', sans-serif;
            background: var(--bg);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Scanning grid background */
        .grid-bg {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,59,92,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,59,92,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridShift 20s linear infinite;
            z-index: 0;
        }
        @keyframes gridShift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        /* Scan line overlay */
        .scanline {
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(255,59,92,0.015) 2px,
                rgba(255,59,92,0.015) 4px
            );
            pointer-events: none;
            z-index: 2;
        }

        /* Red warning pulse */
        .warning-pulse {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,59,92,0.06) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
            z-index: 0;
        }
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
        }

        /* Main content */
        .error-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 40px 24px;
            max-width: 680px;
        }

        /* Shield icon */
        .shield-icon {
            font-size: 3.5rem;
            color: var(--red);
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px rgba(255,59,92,0.3));
            animation: shieldPulse 3s ease-in-out infinite;
        }
        @keyframes shieldPulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(255,59,92,0.3)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 35px rgba(255,59,92,0.5)); }
        }

        /* Error code */
        .error-code {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(6rem, 16vw, 11rem);
            font-weight: 900;
            line-height: 1;
            position: relative;
            color: transparent;
            background: linear-gradient(135deg, var(--red), var(--orange));
            -webkit-background-clip: text;
            background-clip: text;
            margin-bottom: 8px;
        }
        .error-code::before,
        .error-code::after {
            content: '403';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--red), var(--orange));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .error-code::before {
            animation: glitchLeft 4s ease-in-out infinite;
            clip-path: polygon(0 0, 100% 0, 100% 33%, 0 33%);
        }
        .error-code::after {
            animation: glitchRight 4s ease-in-out infinite;
            clip-path: polygon(0 66%, 100% 66%, 100% 100%, 0 100%);
        }
        @keyframes glitchLeft {
            0%, 92%, 100% { transform: translate(0); }
            93% { transform: translate(-3px, -1px); }
            95% { transform: translate(2px, 1px); }
        }
        @keyframes glitchRight {
            0%, 91%, 100% { transform: translate(0); }
            92% { transform: translate(2px, 1px); }
            94% { transform: translate(-3px, -1px); }
        }

        /* Subtitle */
        .error-label {
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 28px;
            opacity: 0.8;
        }

        .error-title {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 16px;
            letter-spacing: 1px;
        }

        .error-desc {
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 40px;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Buttons */
        .error-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-403 {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary-403 {
            background: linear-gradient(135deg, var(--cyan), #00c9a7);
            color: #0a0b0e;
            box-shadow: 0 0 30px rgba(0, 245, 212, 0.15);
        }
        .btn-primary-403:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(0, 245, 212, 0.3);
        }
        .btn-outline-403 {
            background: transparent;
            color: var(--white);
            border: 1.5px solid var(--border);
        }
        .btn-outline-403:hover {
            border-color: var(--red);
            color: var(--red);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 59, 92, 0.1);
        }

        /* Terminal decoration */
        .terminal-line {
            font-family: 'Space Mono', monospace;
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 48px;
            opacity: 0.5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .terminal-line .cursor-blink {
            display: inline-block;
            width: 8px;
            height: 15px;
            background: var(--red);
            animation: blink 1s step-end infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Corner accents */
        .corner { position: fixed; z-index: 0; }
        .corner::before, .corner::after {
            content: '';
            position: absolute;
            background: var(--red);
            opacity: 0.15;
        }
        .corner-tl { top: 20px; left: 20px; }
        .corner-tl::before { width: 40px; height: 2px; top: 0; left: 0; }
        .corner-tl::after { width: 2px; height: 40px; top: 0; left: 0; }
        .corner-tr { top: 20px; right: 20px; }
        .corner-tr::before { width: 40px; height: 2px; top: 0; right: 0; }
        .corner-tr::after { width: 2px; height: 40px; top: 0; right: 0; }
        .corner-bl { bottom: 20px; left: 20px; }
        .corner-bl::before { width: 40px; height: 2px; bottom: 0; left: 0; }
        .corner-bl::after { width: 2px; height: 40px; bottom: 0; left: 0; }
        .corner-br { bottom: 20px; right: 20px; }
        .corner-br::before { width: 40px; height: 2px; bottom: 0; right: 0; }
        .corner-br::after { width: 2px; height: 40px; bottom: 0; right: 0; }

        /* Responsive */
        @media (max-width: 480px) {
            .error-actions { flex-direction: column; align-items: center; }
            .btn-403 { width: 100%; max-width: 280px; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="grid-bg"></div>
    <div class="scanline"></div>
    <div class="warning-pulse"></div>

    <!-- Corner HUD accents -->
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    <div class="error-container">
        <div class="shield-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="error-code" aria-hidden="true">403</div>
        <p class="error-label">System // Access Denied</p>
        <h1 class="error-title">Restricted Zone</h1>
        <p class="error-desc">
            You don't have the required clearance to access this resource.
            If you believe this is an error, please contact our support team.
        </p>
        <nav class="error-actions">
            <a href="index.html" class="btn-403 btn-primary-403">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="login.php" class="btn-403 btn-outline-403">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </a>
        </nav>
        <p class="terminal-line">
            maroc-pc:~$ permission denied <span class="cursor-blink"></span>
        </p>
    </div>

    <script>
        const theme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
    </script>
</body>
</html>
