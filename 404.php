<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Maroc PC</title>
    <meta name="description" content="The page you're looking for doesn't exist. Navigate back to Maroc PC to find premium hardware components.">
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
            --border: #1e2229;
            --cyan-glow: rgba(0, 245, 212, 0.08);
            --orange-glow: rgba(255, 107, 53, 0.08);
        }
        html[data-theme="light"] {
            --bg: #f0f2f5;
            --bg2: #ffffff;
            --white: #1a1a2e;
            --muted: #6b7280;
            --border: #d1d5db;
            --cyan-glow: rgba(0, 245, 212, 0.12);
            --orange-glow: rgba(255, 107, 53, 0.12);
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

        /* Animated grid background */
        .grid-bg {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,245,212,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,245,212,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridShift 20s linear infinite;
            z-index: 0;
        }
        @keyframes gridShift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        /* Floating glitch particles */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: var(--cyan);
            border-radius: 50%;
            opacity: 0;
            animation: float 8s ease-in-out infinite;
            z-index: 0;
        }
        .particle:nth-child(2) { left: 15%; top: 20%; animation-delay: 1s; animation-duration: 10s; background: var(--orange); }
        .particle:nth-child(3) { left: 75%; top: 60%; animation-delay: 2s; animation-duration: 7s; }
        .particle:nth-child(4) { left: 40%; top: 80%; animation-delay: 3s; animation-duration: 9s; background: var(--orange); }
        .particle:nth-child(5) { left: 85%; top: 15%; animation-delay: 0.5s; animation-duration: 11s; }
        .particle:nth-child(6) { left: 25%; top: 50%; animation-delay: 4s; animation-duration: 6s; background: var(--orange); }
        .particle:nth-child(7) { left: 60%; top: 30%; animation-delay: 2.5s; animation-duration: 8s; }
        .particle:nth-child(8) { left: 90%; top: 75%; animation-delay: 1.5s; animation-duration: 12s; background: var(--orange); }

        @keyframes float {
            0%, 100% { opacity: 0; transform: translateY(0) scale(1); }
            25% { opacity: 0.8; }
            50% { opacity: 0.4; transform: translateY(-80px) scale(1.5); }
            75% { opacity: 0.6; }
        }

        /* Main content */
        .error-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 40px 24px;
            max-width: 680px;
        }

        /* Glitch 404 */
        .error-code {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(8rem, 20vw, 14rem);
            font-weight: 900;
            line-height: 1;
            position: relative;
            color: transparent;
            background: linear-gradient(135deg, var(--cyan), var(--orange));
            -webkit-background-clip: text;
            background-clip: text;
            margin-bottom: 8px;
            animation: glitchPulse 4s ease-in-out infinite;
        }
        .error-code::before,
        .error-code::after {
            content: '404';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--cyan), var(--orange));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .error-code::before {
            animation: glitchLeft 3s ease-in-out infinite;
            clip-path: polygon(0 0, 100% 0, 100% 33%, 0 33%);
        }
        .error-code::after {
            animation: glitchRight 3s ease-in-out infinite;
            clip-path: polygon(0 66%, 100% 66%, 100% 100%, 0 100%);
        }

        @keyframes glitchPulse {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.15); }
        }
        @keyframes glitchLeft {
            0%, 90%, 100% { transform: translate(0); }
            92% { transform: translate(-4px, -2px); }
            94% { transform: translate(3px, 1px); }
            96% { transform: translate(-2px, 0); }
        }
        @keyframes glitchRight {
            0%, 90%, 100% { transform: translate(0); }
            91% { transform: translate(3px, 2px); }
            93% { transform: translate(-4px, -1px); }
            95% { transform: translate(2px, 0); }
        }

        /* Subtitle */
        .error-label {
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: var(--cyan);
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
            max-width: 500px;
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
        .btn-404 {
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
        .btn-primary-404 {
            background: linear-gradient(135deg, var(--cyan), #00c9a7);
            color: #0a0b0e;
            box-shadow: 0 0 30px rgba(0, 245, 212, 0.15);
        }
        .btn-primary-404:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(0, 245, 212, 0.3);
        }
        .btn-outline-404 {
            background: transparent;
            color: var(--white);
            border: 1.5px solid var(--border);
        }
        .btn-outline-404:hover {
            border-color: var(--orange);
            color: var(--orange);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 107, 53, 0.1);
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
            background: var(--cyan);
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
            background: var(--cyan);
            opacity: 0.15;
        }
        .corner-tl { top: 20px; left: 20px; }
        .corner-tl::before { width: 40px; height: 2px; top: 0; left: 0; }
        .corner-tl::after { width: 2px; height: 40px; top: 0; left: 0; }
        .corner-br { bottom: 20px; right: 20px; }
        .corner-br::before { width: 40px; height: 2px; bottom: 0; right: 0; }
        .corner-br::after { width: 2px; height: 40px; bottom: 0; right: 0; }

        /* Responsive */
        @media (max-width: 480px) {
            .error-actions { flex-direction: column; align-items: center; }
            .btn-404 { width: 100%; max-width: 280px; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="grid-bg"></div>

    <!-- Floating particles -->
    <div class="particle" style="left:10%; top:40%;"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <!-- Corner HUD accents -->
    <div class="corner corner-tl"></div>
    <div class="corner corner-br"></div>

    <div class="error-container">
        <div class="error-code" aria-hidden="true">404</div>
        <p class="error-label">System // Page Not Found</p>
        <h1 class="error-title">This Route Doesn't Exist</h1>
        <p class="error-desc">
            The page you're looking for has been moved, deleted, or never existed.
            Let's get you back to engineering-grade hardware.
        </p>
        <nav class="error-actions">
            <a href="index.html" class="btn-404 btn-primary-404">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="products.html" class="btn-404 btn-outline-404">
                <i class="fas fa-microchip"></i> Browse Components
            </a>
        </nav>
        <p class="terminal-line">
            maroc-pc:~$ route not found <span class="cursor-blink"></span>
        </p>
    </div>

    <script>
        // Respect saved theme
        const theme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
    </script>
</body>
</html>
