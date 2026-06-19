<?php
declare(strict_types=1);
require_once 'bootstrap.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(i18n_current_locale(), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction(), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(i18n_t('laptop_finder_page.page_title', [], 'Find Your Laptop | Maroc PC Curated Ecosystem'), ENT_QUOTES, 'UTF-8') ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Mono&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Base App Stylesheets -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <link rel="stylesheet" href="assets/css/products.css">
    <link rel="stylesheet" href="assets/css/installment-compare.css">
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <script src="assets/js/page-transitions.js"></script>
    <?= i18n_preference_assets() ?>
    
    <!-- Curated Finder Styles -->
    <style>
        :root {
            --cyan-glow: rgba(0, 245, 212, 0.15);
            --orange-glow: rgba(255, 107, 53, 0.15);
            --glass-bg: rgba(18, 18, 24, 0.8);
            --card-border-active: var(--cyan);
        }

        [data-theme="light"] {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --cyan-glow: rgba(0, 168, 143, 0.12);
            --orange-glow: rgba(224, 90, 32, 0.12);
        }

        body {
            background-color: var(--page-bg);
            color: var(--text);
            font-family: 'Syne', sans-serif;
            overflow-x: hidden;
        }

        .finder-container {
            max-width: 1400px;
            margin: 110px auto 40px;
            padding: 0 24px;
        }

        .finder-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .finder-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, var(--white) 30%, var(--cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .finder-header p {
            font-size: 1.15rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* The Golden Grid */
        .finder-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 32px;
            align-items: start;
        }

        /* Glass Cockpit Cockpit (Filters Panel) */
        .cockpit-panel {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--cyan) transparent;
        }
        .cockpit-panel::-webkit-scrollbar {
            width: 4px;
        }
        .cockpit-panel::-webkit-scrollbar-thumb {
            background: var(--cyan);
            border-radius: 2px;
        }

        .cockpit-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        .cockpit-title i {
            color: var(--cyan);
        }

        .filter-section {
            margin-bottom: 28px;
        }

        .filter-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 12px;
            display: block;
            font-family: 'Space Mono', monospace;
        }

        /* Card Selectors */
        .selector-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .selector-group.full-width {
            grid-template-columns: 1fr;
        }

        .selector-card {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .selector-card:hover {
            border-color: var(--cyan);
            background: var(--card-bg-hover);
            transform: translateY(-2px);
        }

        .selector-card.active {
            border-color: var(--cyan);
            background: transparent;
            box-shadow: none;
            color: var(--cyan-dim);
        }

        .selector-card i {
            font-size: 1.4rem;
            margin-bottom: 6px;
            display: block;
            color: var(--muted);
            transition: color 0.25s;
        }

        .selector-card.active i {
            color: var(--cyan-dim);
        }

        .selector-card span {
            font-size: 0.85rem;
            font-weight: 700;
            display: block;
        }

        /* Budget Range Slider styling */
        .budget-slider-container {
            padding: 10px 0;
        }

        .budget-range-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        .budget-range-label {
            display: grid;
            gap: 5px;
            font-family: 'Space Mono', monospace;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .budget-slider {
            width: 100%;
            -webkit-appearance: none;
            height: 6px;
            border-radius: 3px;
            background: var(--border);
            outline: none;
            margin: 12px 0;
        }

        .budget-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--cyan);
            cursor: pointer;
            box-shadow: 0 0 8px var(--cyan);
            transition: transform 0.1s;
        }

        .budget-slider::-webkit-slider-thumb:hover {
            transform: scale(1.25);
        }

        .budget-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border: 0;
            border-radius: 50%;
            background: var(--cyan);
            cursor: pointer;
            box-shadow: 0 0 8px var(--cyan);
        }

        .budget-values {
            display: flex;
            justify-content: space-between;
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .budget-current {
            color: var(--cyan);
            font-weight: 700;
            font-size: 1rem;
        }

        .finder-footer {
            display: block;
            margin-top: 70px;
            padding: 60px 5% 30px;
            border-top: 1px solid var(--border);
            background: var(--page-bg-2);
        }

        .finder-footer-inner {
            max-width: 1760px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            align-items: start;
        }

        .finder-footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--cyan) !important;
            font-family: 'Orbitron', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
        }

        .finder-footer-column > p {
            color: var(--muted);
            font-size: 0.875rem;
            line-height: 1.7;
            margin: 0 0 20px;
            max-width: 560px;
        }

        .finder-footer-column h4 {
            margin: 0 0 20px;
            color: var(--cyan);
            font-family: 'Orbitron', monospace;
            font-size: 0.8rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .finder-footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .finder-footer-column li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 0.875rem;
        }

        .finder-footer-column li i {
            color: var(--cyan);
            width: 18px;
            text-align: center;
        }

        .finder-footer address {
            font-style: normal;
        }

        .finder-footer a {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .finder-footer a:hover {
            color: var(--cyan);
        }

        .finder-social-links {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .finder-social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .finder-social-links a:hover {
            background: var(--cyan);
            border-color: var(--cyan);
            color: #000;
            transform: translateY(-3px);
        }

        .finder-footer-bottom {
            max-width: 1760px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 13px;
        }

        .finder-footer-legal {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        [data-theme="light"] .finder-footer {
            background: #0F172A;
            border-top-color: rgba(255, 255, 255, 0.08);
        }

        [data-theme="light"] .finder-footer-logo {
            color: var(--cyan) !important;
        }

        [data-theme="light"] .finder-footer-column > p {
            color: #94A3B8;
        }

        [data-theme="light"] .finder-footer-column h4 {
            color: #007A6E;
            font-family: 'JetBrains Mono', 'Space Mono', monospace;
            font-size: 0.68rem;
            letter-spacing: 3px;
        }

        [data-theme="light"] .finder-footer-column li,
        [data-theme="light"] .finder-footer a,
        [data-theme="light"] .finder-footer-bottom {
            color: #64748B;
        }

        [data-theme="light"] .finder-footer-column li i {
            color: var(--cyan);
        }

        [data-theme="light"] .finder-footer a:hover,
        [data-theme="light"] .finder-footer-legal a:hover {
            color: var(--cyan);
        }

        [data-theme="light"] .finder-social-links a {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 2px;
            color: #475569;
        }

        [data-theme="light"] .finder-social-links a:hover {
            background: var(--cyan);
            border-color: var(--cyan);
            color: #FFFFFF;
        }

        [data-theme="light"] .finder-footer-bottom {
            border-top-color: rgba(255, 255, 255, 0.08);
        }

        [data-theme="light"] .finder-footer-legal a {
            color: #475569;
        }

        /* Results Panel */
        .results-panel {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        #laptopsContainer {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
            font-family: 'Space Mono', monospace;
        }

        .results-count {
            font-size: 1rem;
            color: var(--muted);
        }

        .results-count span {
            color: var(--cyan);
            font-weight: 700;
        }

        /* Outcome Laptop Card */
        .laptop-card {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: grid;
            grid-template-columns: 240px 1fr 280px;
            gap: 28px;
            align-items: center;
            backdrop-filter: blur(12px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .laptop-card:hover {
            border-color: var(--cyan);
            box-shadow: 0 8px 30px rgba(0, 245, 212, 0.08);
            transform: translateY(-3px);
        }

        /* When any score tooltip inside a card is open (hover/focus/tap), lift the WHOLE
           card above its neighbours. Each card is its own stacking context (backdrop-filter
           + position:relative), so a tooltip overflowing the card's bottom edge is painted
           over by the card below unless the active card's z-index beats it. */
        .laptop-card:has(.metric-tip-icon:hover),
        .laptop-card:has(.metric-tip-icon:focus),
        .laptop-card:has(.metric-tip-icon.tapped) {
            z-index: 20;
        }

        /* Custom badge overlay on high match card */
        .match-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--cyan);
            color: var(--page-bg);
            font-family: 'Space Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0 10px var(--cyan-glow);
            z-index: 2;
        }

        .laptop-image-container {
            width: 100%;
            height: 180px;
            display: grid;
            place-items: center;
            background: var(--page-bg-2);
            border-radius: 12px;
            padding: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .laptop-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }

        .laptop-card:hover .laptop-image-container img {
            transform: scale(1.06);
        }

        .laptop-details h3 {
            font-size: 1.45rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--white);
        }

        .laptop-brand {
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
        }

        .verified-spec-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            border-top: 1px solid rgba(0, 245, 212, 0.12);
            padding-top: 14px;
            margin-top: 14px;
        }

        .verified-spec-chip {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            min-width: 0;
        }

        .verified-spec-chip span {
            display: block;
            font-family: 'Space Mono', monospace;
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text);
            margin-bottom: 4px;
        }

        .verified-spec-chip strong {
            display: block;
            color: var(--white);
            font-size: 0.88rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .spec-item {
            font-size: 0.85rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .spec-item i {
            color: var(--cyan);
            width: 14px;
        }

        /* Outcome Metric Bars */
        .metric-container {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }

        .metric-bar-group {
            display: grid;
            grid-template-columns: 140px 1fr 52px;
            align-items: center;
            gap: 12px;
            position: relative;
            /* Default z-index keeps DOM order; raised on hover/focus/tap below so the
               open tooltip clears the rows that come after it in source order. */
            z-index: 1;
        }

        /* When any tooltip in this row is open, lift the whole row above its siblings
           so the tooltip wins the stacking battle. Hover/focus live on the icon; tap
           toggles .tapped on the icon — both cascade up here via :has(). */
        .metric-bar-group:has(.metric-tip-icon:hover),
        .metric-bar-group:has(.metric-tip-icon:focus),
        .metric-bar-group:has(.metric-tip-icon.tapped) {
            z-index: 50;
        }

        .metric-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text);
            font-family: 'Space Mono', monospace;
            display: flex;
            align-items: center;
            gap: 5px;
            position: relative;
        }

        .metric-tip-icon {
            color: var(--cyan);
            font-size: 0.68rem;
            cursor: help;
            opacity: 0.7;
            transition: opacity 0.2s;
            flex-shrink: 0;
        }

        .metric-tip-icon:hover,
        .metric-tip-icon:focus {
            opacity: 1;
            outline: none;
        }

        .metric-tip {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            z-index: 50;
            width: max-content;
            max-width: 280px;
            padding: 10px 12px;
            background: var(--page-bg-3);
            border: 1px solid var(--cyan);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Syne', sans-serif;
            font-size: 0.72rem;
            line-height: 1.45;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
            pointer-events: none;
        }

        /* Hover (desktop) + focus (keyboard) reveal the tooltip */
        .metric-tip-icon:hover + .metric-tip,
        .metric-tip-icon:focus + .metric-tip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Tap reveal (mobile): toggle via JS class on the icon */
        .metric-tip-icon.tapped + .metric-tip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Tooltip content structure (bidi-safe: translated phrases flow with dir,
           formula lines are locked LTR via dir="ltr" on .tip-line). */
        .metric-tip .tip-intro {
            display: block;
            margin-bottom: 6px;
            color: var(--text);
            font-weight: 600;
        }
        .metric-tip .tip-lines {
            display: block;
            margin-bottom: 6px;
            padding-top: 4px;
            border-top: 1px solid var(--border);
        }
        .metric-tip .tip-line {
            display: block;
            color: var(--white);
            font-family: 'Space Mono', monospace;
            font-size: 0.7rem;
            line-height: 1.5;
        }
        .metric-tip .tip-result {
            display: block;
            padding-top: 4px;
            border-top: 1px solid var(--border);
            color: var(--cyan);
            font-weight: 700;
            font-size: 0.72rem;
        }

        .metric-track {
            height: 6px;
            background: var(--page-bg-2);
            border-radius: 3px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .metric-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease-out;
        }

        .metric-fill.performance { background: var(--orange); }
        .metric-fill.portability { background: var(--cyan); }
        .metric-fill.screen { background: var(--diagnostic-purple); }
        .metric-fill.value { background: #2ec4b6; }

        .metric-val {
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .metric-val-max {
            color: var(--muted);
            font-weight: 500;
            font-size: 0.68rem;
        }

        /* AI row when the machine has no NPU: show "N/A", no bar fill, muted. */
        .metric-bar-na .metric-label {
            opacity: 0.7;
        }

        .metric-track-na {
            background: transparent;
            border-style: dashed;
        }

        .metric-val-na {
            color: var(--muted);
            font-weight: 600;
            font-style: italic;
        }

        .review-measurements {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--page-bg-2);
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .review-measurements.compact {
            margin-top: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 10px;
        }

        .review-measurements-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .review-measurements-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.86rem;
            text-transform: uppercase;
            color: var(--white);
            margin: 0;
        }

        .review-source-link {
            color: var(--cyan);
            font-size: 0.75rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .review-measurements-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .review-measurement-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--page-bg-3);
            padding: 12px;
            min-width: 0;
        }

        .review-measurements.compact .review-measurement-card {
            padding: 8px;
        }

        .review-measurement-card span {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-family: 'Space Mono', monospace;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .review-measurement-card strong {
            display: block;
            color: var(--white);
            font-size: 1rem;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }

        .review-measurement-card small {
            display: block;
            color: var(--muted);
            font-size: 0.72rem;
            line-height: 1.35;
            margin-top: 4px;
        }

        /* Checkout Upsell & Action Panel */
        .action-panel {
            text-align: right;
            border-left: 1px solid var(--border);
            padding-left: 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .price-box {
            margin-bottom: 18px;
            text-align: right;
        }

        .laptop-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--white);
            line-height: 1.1;
        }

        .laptop-old-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            color: var(--muted);
            text-decoration: line-through;
            margin-top: 4px;
        }

        .laptop-installment-hint {
            margin-top: 8px;
            font-family: 'Space Mono', monospace;
            font-size: 0.78rem;
            color: var(--text);
        }

        .laptop-installment-hint strong {
            color: var(--cyan);
            font-weight: 700;
        }

        /* Primary action: dominant, full-width. The single most important click on the card. */
        .btn-select {
            background: var(--cyan);
            color: var(--page-bg);
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 0.5px;
            padding: 16px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.25s;
            box-shadow: 0 4px 18px rgba(0, 245, 212, 0.25);
        }

        .btn-select:hover {
            background: var(--white);
            box-shadow: 0 6px 24px rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
        }

        /* Action stack: primary on top, then a quiet secondary row (Details + Compare). */
        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-actions-secondary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        /* Secondary actions: deliberately quieter than the primary so the eye lands on Add to Cart first. */
        .btn-quickview,
        .compare-toggle {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 9px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-quickview i,
        .compare-toggle i {
            color: var(--cyan);
            font-size: 0.78rem;
        }

        .btn-quickview:hover,
        .compare-toggle:hover,
        .compare-toggle.active {
            border-color: var(--cyan);
            color: var(--white);
            background: rgba(0, 245, 212, 0.08);
        }

        /* Upsell: collapsed below the actions so it never competes with the primary CTA. */
        .upsell-box {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 6px;
            text-align: left;
            transition: all 0.25s;
            cursor: pointer;
            user-select: none;
        }

        .upsell-box:hover {
            border-color: var(--orange);
            background: var(--orange-glow);
        }

        .upsell-box.active {
            border-color: var(--orange);
            background: rgba(255, 107, 53, 0.12);
        }

        .upsell-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0;
        }

        .upsell-box.active .upsell-header,
        .upsell-box:hover .upsell-header {
            color: var(--white);
        }

        .upsell-header i {
            color: var(--orange);
        }

        .upsell-body {
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.4;
            margin-top: 6px;
        }

        .upsell-price {
            color: var(--orange);
            font-weight: 700;
            font-family: 'Space Mono', monospace;
        }

        .laptop-image-container {
            position: relative;
            cursor: pointer;
        }
        
        .laptop-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 245, 212, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
        }
        
        .laptop-image-container:hover .laptop-image-overlay {
            opacity: 1;
        }
        
        .laptop-image-overlay span {
            background: var(--page-bg);
            color: var(--cyan);
            border: 1px solid var(--cyan);
            padding: 6px 12px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .empty-match-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .empty-match-state i {
            font-size: 3rem;
            color: var(--orange);
            margin-bottom: 16px;
        }

        .empty-match-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--white);
        }

        .empty-match-state p {
            color: var(--muted);
            margin-bottom: 24px;
        }

        .alternative-headline {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--orange);
            margin-top: 32px;
            margin-bottom: 16px;
            text-align: center;
            position: relative;
        }

        .alternative-headline::before,
        .alternative-headline::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 80px;
            height: 1px;
            background: var(--border);
        }

        .alternative-headline::before { left: 20%; }
        .alternative-headline::after { right: 20%; }

        .laptop-detail-page .product-detail-media {
            min-height: 430px;
        }

        .laptop-detail-page .product-detail-media img {
            width: min(86%, 620px);
            max-height: 360px;
        }

        .laptop-detail-page .product-detail-summary {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .laptop-detail-page .product-detail-summary h1 {
            font-size: clamp(1.9rem, 3vw, 3rem);
        }

        .laptop-detail-page .review-measurements,
        .laptop-detail-page .fact-score-bars {
            margin-top: 0;
        }

        .laptop-detail-page .specs {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 0;
        }

        .laptop-detail-page .laptop-inline-panel {
            background: var(--page-bg-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }

        .laptop-detail-page .laptop-inline-panel h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.95rem;
            text-transform: uppercase;
            color: var(--white);
            margin: 0 0 12px;
            letter-spacing: 0;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .laptop-detail-page .laptop-detail-trust-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .laptop-detail-page .laptop-detail-trust-grid .trust-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--page-bg-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .laptop-detail-page .laptop-detail-trust-grid i {
            color: var(--cyan);
            width: 18px;
            text-align: center;
        }

        .laptop-detail-page .financing-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .laptop-detail-page .price-alert-row {
            display: flex;
            gap: 8px;
        }

        .laptop-detail-page .price-alert-row input {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--page-bg-3);
            color: var(--text);
            font-size: 0.82rem;
            min-width: 0;
        }

        .laptop-detail-page .price-alert-row button {
            background: var(--cyan);
            color: #000;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .finder-grid {
                grid-template-columns: 1fr;
            }
            .cockpit-panel {
                position: static;
            }
            .laptop-card {
                grid-template-columns: 1fr;
            }
            .action-panel {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--border);
                padding-top: 20px;
            }
            .finder-footer-inner {
                grid-template-columns: 1fr 1fr;
            }
            .finder-footer-bottom {
                grid-template-columns: 1fr;
            }
            .finder-footer-legal {
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .review-measurements.compact,
            .review-measurements-grid {
                grid-template-columns: 1fr;
            }

            .review-measurements-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .budget-range-fields {
                grid-template-columns: 1fr;
            }
            .finder-footer {
                padding: 40px 5% 20px;
            }
            .finder-footer-inner {
                grid-template-columns: 1fr;
            }
            .finder-footer-bottom {
                text-align: center;
            }
            .finder-footer-legal {
                justify-content: center;
            }

            .laptop-detail-page .laptop-detail-trust-grid,
            .laptop-detail-page .price-alert-row {
                grid-template-columns: 1fr;
            }

            .laptop-detail-page .price-alert-row {
                flex-direction: column;
            }
        }

        /* Toggle Switch */
        .toggle-switch input:checked + .slider {
            background: var(--cyan) !important;
        }
        .toggle-switch input:checked + .slider::before {
            transform: translateX(20px);
        }
        .toggle-switch .slider::before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }

        /* AI Badge */
        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: max-content;
            max-width: 100%;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: linear-gradient(135deg, var(--cyan) 0%, #00d4b8 100%);
            color: #000;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0 0 10px 0;
            line-height: 1.1;
            white-space: nowrap;
        }
        .ai-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 12px rgba(0, 245, 212, 0.4);
        }
        .ai-badge.workstation {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: #fff;
        }
        .ai-badge.basic {
            background: linear-gradient(135deg,#6c757d,#495057);
            color:#fff;
        }
        .ai-badge .badge-tops {
            font-size: 0.58rem;
            opacity: 0.9;
            padding: 1px 5px;
            background: rgba(0,0,0,0.15);
            border-radius: 8px;
        }

        /* Smart Tip Banner */
        .smart-tip-banner {
            background: linear-gradient(135deg, rgba(0,245,212,0.08), rgba(0,245,212,0.03));
            border: 1px solid rgba(0,245,212,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .smart-tip-banner:hover {
            border-color: var(--cyan);
            background: rgba(0,245,212,0.06);
        }
        .smart-tip-banner i { color: var(--cyan); font-size: 1rem; }
        .smart-tip-banner .tip-text { flex: 1; color: var(--text); }
        .smart-tip-banner .tip-cta { color: var(--cyan); font-weight: 700; white-space: nowrap; }

        /* Comparison Bar */
        .comparison-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            padding: 16px 24px;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.2);
        }
        .comparison-bar.visible { transform: translateY(0); }
        .comparison-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .comparison-cards {
            display: flex;
            gap: 12px;
            flex: 1;
            overflow-x: auto;
        }
        .compare-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg, var(--page-bg-2));
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 12px;
            min-width: 200px;
            position: relative;
        }
        .compare-mini-card img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 6px;
        }
        .compare-mini-card .remove-cmp {
            position: absolute;
            top: -6px;
            right: -6px;
            background: var(--danger, #ff3b5c);
            color: #fff;
            border: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.65rem;
            display: grid;
            place-items: center;
        }
        .compare-btn-detail {
            background: var(--cyan);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .compare-btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,245,212,0.4);
        }
        .compare-btn-clear {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .compare-btn-clear:hover { border-color: var(--danger, #ff3b5c); color: var(--danger, #ff3b5c); }


        /* Mobile Filter Drawer */
        .mobile-filter-trigger {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background: var(--cyan);
            color: #000;
            border: none;
            padding: 14px 22px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 20px rgba(0, 245, 212, 0.4);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mobile-filter-trigger:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .cockpit-panel.mobile-drawer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                top: auto;
                transform: translateY(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                max-height: 80vh;
                overflow-y: auto;
                border-radius: 20px 20px 0 0;
                z-index: 1000;
                box-shadow: 0 -10px 40px rgba(0,0,0,0.3);
            }
            .cockpit-panel.mobile-drawer.open {
                transform: translateY(0);
            }
            .cockpit-panel.mobile-drawer::before {
                content: '';
                display: block;
                width: 40px;
                height: 4px;
                background: var(--border);
                border-radius: 2px;
                margin: 0 auto 12px;
            }
            .finder-grid {
                grid-template-columns: 1fr !important;
            }
            body.drawer-open {
                overflow: hidden;
            }
            body.drawer-open .mobile-filter-trigger {
                display: none !important;
            }
            .comparison-bar {
                padding: 12px;
            }
            .comparison-inner {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }
            .comparison-cards {
                max-height: 84px;
            }
            .compare-mini-card {
                min-width: 220px;
            }
            #comparisonBar.visible ~ #mobileFilterTrigger {
                bottom: 148px;
            }
        }
    </style>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
</head>
<body>

    <!-- Shared Header Navigation -->
    <header>
        <span class="myDIV">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>

            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo">
                <img src="logo.png" alt="Maroc PC Logo" class="nav-logo">
            </a>

            <!-- Split Navigation -->
            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.components'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.pc_build_wizard'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link active"><?php i18n_e('laptop_finder_page.find_your_laptop'); ?></a>
            </nav>

            <div class="nav-spacer" aria-hidden="true"></div>

            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <?= i18n_language_switcher('nav-translate') ?>

            <div class="cart-wrapper" id="userNav">
                <a href="<?= htmlspecialchars(i18n_url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?= htmlspecialchars(i18n_t('nav.account', [], 'Account'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-user"></i>
                </a>
            </div>

            <div class="cart-wrapper">
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cart-icon" aria-label="<?php i18n_e('nav.shopping_cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </span>
    </header>

    <div class="finder-container" id="laptopFinderCatalog">
        
        <!-- Header Section -->
        <section class="finder-header">
            <h1><?php i18n_e('laptop_finder_page.advanced_laptop_finder', [], 'Advanced+ Laptop Finder'); ?></h1>
            <p><?php i18n_e('laptop_finder_page.hero_description', [], 'Outcome-Oriented Curation. Tell us what your laptop needs to accomplish, and let our curator map the perfect machine.'); ?></p>
        </section>

        <!-- Main Workspace Grid -->
        <div class="finder-grid">
            
            <!-- Cockpit (Filters Sidebar) -->
            <aside class="cockpit-panel">
                <div class="cockpit-title">
                    <i class="fas fa-sliders"></i>
                    <span><?php i18n_e('laptop_finder_page.golden_filters', [], 'Golden Filters'); ?></span>
                </div>

                <!-- 1. Primary Use -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.primary_outcome_target', [], '1. Primary Outcome Target'); ?></span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="usage" data-val="gaming">
                            <i class="fas fa-gamepad"></i>
                            <span><?php i18n_e('laptop_finder_page.gaming', [], 'Gaming'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="business">
                            <i class="fas fa-briefcase"></i>
                            <span><?php i18n_e('laptop_finder_page.business', [], 'Business'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="student">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php i18n_e('laptop_finder_page.student', [], 'Student'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="usage" data-val="creative">
                            <i class="fas fa-palette"></i>
                            <span><?php i18n_e('laptop_finder_page.creative', [], 'Creative'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Portability -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.portability_preference', [], '2. Portability Preference'); ?></span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="portability" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span><?php i18n_e('laptop_finder_page.any_class', [], 'Any Class'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="ultralight">
                            <i class="fas fa-feather-pointed"></i>
                            <span><?php i18n_e('laptop_finder_page.ultralight', [], 'Ultralight'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="standard">
                            <i class="fas fa-laptop"></i>
                            <span><?php i18n_e('laptop_finder_page.standard', [], 'Standard'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="portability" data-val="desktop_replacement">
                            <i class="fas fa-desktop"></i>
                            <span><?php i18n_e('laptop_finder_page.heavy_power', [], 'Heavy Power'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 3. Screen Quality -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.screen_excellence', [], '3. Screen Excellence'); ?></span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="screen" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span><?php i18n_e('laptop_finder_page.any_quality', [], 'Any Quality'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="oled">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <span><?php i18n_e('laptop_finder_page.oled_color', [], 'OLED Color'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="high_refresh">
                            <i class="fas fa-bolt"></i>
                            <span><?php i18n_e('laptop_finder_page.high_refresh', [], 'High Refresh'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="screen" data-val="standard">
                            <i class="fas fa-tv"></i>
                            <span><?php i18n_e('laptop_finder_page.standard_ips', [], 'Standard IPS'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 4. Graphics Tier -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.graphics_core_gpu', [], '4. Graphics Core GPU'); ?></span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="gpu" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span><?php i18n_e('laptop_finder_page.any_gpu', [], 'Any GPU'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="gpu" data-val="dedicated">
                            <i class="fas fa-server"></i>
                            <span><?php i18n_e('laptop_finder_page.dedicated_rtx', [], 'Dedicated RTX'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="gpu" data-val="integrated">
                            <i class="fas fa-microchip"></i>
                            <span><?php i18n_e('laptop_finder_page.integrated', [], 'Integrated'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 5. AI Performance -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.ai_performance', [], '5. AI Performance'); ?></span>
                    <div class="selector-group">
                        <div class="selector-card active" data-filter="ai" data-val="any">
                            <i class="fas fa-border-all"></i>
                            <span><?php i18n_e('laptop_finder_page.ai_any', [], 'Any'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="ai" data-val="basic">
                            <i class="fas fa-microchip"></i>
                            <span><?php i18n_e('laptop_finder_page.ai_ready', [], 'AI-Ready'); ?></span>
                            <small>10-39 TOPS</small>
                        </div>
                        <div class="selector-card" data-filter="ai" data-val="copilot">
                            <i class="fas fa-brain"></i>
                            <span><?php i18n_e('laptop_finder_page.ai_copilot_plus', [], 'Copilot+ Ready'); ?></span>
                            <small>40-79 TOPS</small>
                            <span class="badge-hot" style="background:var(--orange);color:#fff;font-size:0.6rem;padding:2px 6px;border-radius:4px;font-weight:800;margin-left:4px;"><?php i18n_e('laptop_finder_page.ai_trending', [], 'Trending'); ?></span>
                        </div>
                        <div class="selector-card" data-filter="ai" data-val="workstation">
                            <i class="fas fa-rocket"></i>
                            <span><?php i18n_e('laptop_finder_page.ai_workstation', [], 'AI Workstation'); ?></span>
                            <small>80+ TOPS</small>
                        </div>
                    </div>
                </div>

                <!-- 6. In-Stock Toggle -->
                <div class="filter-section">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <label class="toggle-switch" style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;">
                            <input type="checkbox" id="inStockOnly" checked style="opacity:0;width:0;height:0;">
                            <span class="slider" style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:var(--border);border-radius:24px;transition:0.3s;" onclick="this.previousElementSibling.click()"></span>
                        </label>
                        <span class="filter-label" style="margin:0;flex:1;"><?php i18n_e('laptop_finder_page.in_stock_only', [], 'In Stock Only'); ?></span>
                        <span class="stock-count" id="stockCount" style="font-size:0.8rem;color:var(--muted);font-family:'Space Mono',monospace;"></span>
                    </div>
                </div>

                <!-- 7. Budget Range -->
                <div class="filter-section">
                    <span class="filter-label"><?php i18n_e('laptop_finder_page.budget_window', [], '7. Budget Window'); ?></span>
                    <div class="budget-slider-container">
                        <div class="budget-range-fields">
                            <label class="budget-range-label" for="budgetMin">
                                <?php i18n_e('laptop_finder_page.minimum', [], 'Minimum'); ?>
                                <input type="range" min="7000" max="45000" step="1000" value="7000" class="budget-slider" id="budgetMin">
                            </label>
                            <label class="budget-range-label" for="budgetMax">
                                <?php i18n_e('laptop_finder_page.maximum', [], 'Maximum'); ?>
                                <input type="range" min="7000" max="45000" step="1000" value="45000" class="budget-slider" id="budgetMax">
                            </label>
                        </div>
                        <div class="budget-values">
                            <span>7k DH</span>
                            <span class="budget-current" id="budgetCurrent">7,000 - <?php i18n_e('laptop_finder_page.no_limit', [], 'No Limit'); ?></span>
                            <span>45k+ DH</span>
                        </div>
                    </div>
                </div>

            </aside>

            <!-- Results Output -->
            <main class="results-panel">
                
                <div class="results-header">
                    <span class="results-count"><?php i18n_e('laptop_finder_page.matches_located', [], 'Matches Located:'); ?> <span id="matchCount">0</span></span>
                    <span>Maroc PC ✅ <?php i18n_e('laptop_finder_page.certified_label', [], 'Quality Checked'); ?></span>
                </div>

                <!-- Laptops Cards Container -->
                <div id="laptopsContainer"></div>

            </main>

        </div>

    </div>

    <!-- Laptop Detail Page Surface -->
    <section class="product-detail-page laptop-detail-page" id="laptopDetailPage" hidden>
        <div class="container">
            <div class="product-detail-nav">
                <a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="product-detail-back" id="laptopDetailBack">
                    <i class="fas fa-arrow-left"></i> <?php i18n_e('laptop_finder_page.back_to_finder', [], 'Back to laptop finder'); ?>
                </a>
            </div>
            <article class="product-detail-shell" id="laptopDetailContent" aria-live="polite">
                <!-- Laptop detail page content is loaded via JavaScript -->
            </article>
        </div>
    </section>

    <footer class="finder-footer">
        <div class="finder-footer-inner">
            <section class="finder-footer-column">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="finder-footer-logo">
                    <i class="fas fa-microchip"></i>
                    <span>MarocPC</span>
                </a>
                <p><?php i18n_e('footer.tagline', [], 'Your trusted source for premium computer hardware. Building dreams, one component at a time.'); ?></p>
                <nav class="finder-social-links" aria-label="Social media">
                    <a href="https://www.facebook.com/profile.php?id=61589634966821" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/Maroc_PC_PHP" target="_blank" aria-label="X"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" fill="currentColor" style="vertical-align: middle;"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg></a>
                    <a href="https://www.instagram.com/marocpc57" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/channel/UCUsNULLfizuDROl04RESTtw" target="_blank" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </nav>
            </section>

            <section class="finder-footer-column">
                <h4><?php i18n_e('footer.shop', [], 'Shop'); ?></h4>
                <ul>
                    <li><a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.home'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.products'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.pc_build_wizard'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.tools_cockpit'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>"><?php i18n_e('nav.deals'); ?></a></li>
                </ul>
            </section>

            <section class="finder-footer-column">
                <h4><?php i18n_e('footer.customer_service', [], 'Customer Service'); ?></h4>
                <ul>
                    <li><a href="account.php?tab=orders"><?php i18n_e('footer.track_order', [], 'Track Order'); ?></a></li>
                    <li><a href="returns-refunds.php"><?php i18n_e('footer.returns_refunds', [], 'Returns & Refunds'); ?></a></li>
                    <li><a href="shipping-info.php"><?php i18n_e('footer.shipping_info', [], 'Shipping Info'); ?></a></li>
                    <li><a href="faq.php"><?php i18n_e('footer.faq', [], 'FAQ'); ?></a></li>
                </ul>
            </section>

            <section class="finder-footer-column">
                <h4><?php i18n_e('footer.contact_us', [], 'Contact Us'); ?></h4>
                <address>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Boulevard Zerktouni, Maarif</li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+212618821949">+212 618821949</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:support@marocpc.com">support@marocpc.com</a></li>
                    </ul>
                </address>
            </section>
        </div>

        <div class="finder-footer-bottom">
            <small>&copy; 2026 Maroc PC. <?php i18n_e('footer.all_rights', [], 'All rights reserved.'); ?></small>
            <nav class="finder-footer-legal" aria-label="<?php i18n_e('footer.legal_links', [], 'Legal links'); ?>">
                <a href="privacy-policy.php"><?php i18n_e('footer.privacy_policy', [], 'Privacy Policy'); ?></a>
                <a href="terms-of-service.php"><?php i18n_e('footer.terms_of_service', [], 'Terms of Service'); ?></a>
                <a href="cookie-policy.php"><?php i18n_e('footer.cookie_policy', [], 'Cookie Policy'); ?></a>
            </nav>
        </div>
    </footer>

    <?php /*
    Legacy quick view modal kept as a safe reference; laptop details now use the page surface above.
    <div class="modal" id="quickViewModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close" id="modalCloseBtn"><i class="fas fa-times"></i></button>
            <div class="modal-body" id="quickViewContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
    */ ?>

    <!-- Toast elements -->
    <div id="toast" class="toast">
        <span id="toastMessage"></span>
    </div>

    <!-- Comparison Bar -->
    <div id="comparisonBar" class="comparison-bar">
        <div class="comparison-inner">
            <div class="comparison-cards" id="comparisonCards"></div>
            <button class="compare-btn-detail" onclick="goToComparison()"><?php i18n_e('laptop_finder_page.compare_in_detail', [], 'Compare in Detail'); ?> <i class="fas fa-arrow-right"></i></button>
            <button class="compare-btn-clear" onclick="clearComparison()"><i class="fas fa-times"></i> <?php i18n_e('laptop_finder_page.clear', [], 'Clear'); ?></button>
        </div>
    </div>

    <!-- AI Explainer Modal -->
    <div class="modal" id="aiExplainerModal">
        <div class="modal-overlay" onclick="closeAIExplainer()"></div>
        <div class="modal-content" style="max-width:700px;">
            <button class="modal-close" onclick="closeAIExplainer()"><i class="fas fa-times"></i></button>
            <div class="modal-body" id="aiExplainerContent"></div>
        </div>
    </div>

    <!-- Mobile Filter Trigger -->
    <button id="mobileFilterTrigger" class="mobile-filter-trigger" style="display:none;">
        <i class="fas fa-sliders"></i> <?php i18n_e('laptop_finder_page.filters_btn', [], 'Filters'); ?>
    </button>

    <!-- Standard footer scripts -->
    <script src="assets/js/currency.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/cart.js?v=notify-toast-2"></script>
    <script src="assets/js/installment.js?v=i18n-fix"></script>
    <script src="assets/js/reviews.js"></script>
    <?= i18n_language_switcher_assets() ?>
    <script src="assets/js/auth-nav.js"></script>
    
    <!-- i18n Translations for JavaScript -->
    <script>
    <?php $GLOBALS['I18N_JS_CONTEXT'] = true; ?>
    window.__i18n = {
        viewDetails: '<?= htmlspecialchars(i18n_t('laptop_finder_page.view_details', [], 'View Details'), ENT_QUOTES, 'UTF-8') ?>',
        comparing: '<?= htmlspecialchars(i18n_t('laptop_finder_page.comparing', [], 'Comparing'), ENT_QUOTES, 'UTF-8') ?>',
        compare: '<?= htmlspecialchars(i18n_t('laptop_finder_page.compare_btn', [], 'Compare'), ENT_QUOTES, 'UTF-8') ?>',
        selectLaptop: '<?= htmlspecialchars(i18n_t('laptop_finder_page.select_laptop_btn', [], 'Select Laptop'), ENT_QUOTES, 'UTF-8') ?>',
        maxCompare: '<?= htmlspecialchars(i18n_t('laptop_finder_page.max_compare', [], 'Maximum 3 laptops for comparison'), ENT_QUOTES, 'UTF-8') ?>',
        surfacingClosest: '<?= htmlspecialchars(i18n_t('laptop_finder_page.surfacing_closest', [], 'Surfacing Closest Alternatives'), ENT_QUOTES, 'UTF-8') ?>',
        noResultsTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.no_results_title', [], 'No suitable laptops found'), ENT_QUOTES, 'UTF-8') ?>',
        noResultsBody: '<?= htmlspecialchars(i18n_t('laptop_finder_page.no_results_body', [], 'Try expanding your maximum budget or clearing some filter constraints.'), ENT_QUOTES, 'UTF-8') ?>',
        databaseNotLoaded: '<?= htmlspecialchars(i18n_t('laptop_finder_page.database_not_loaded', [], 'Laptop database is not loaded.'), ENT_QUOTES, 'UTF-8') ?>',
        available: '<?= htmlspecialchars(i18n_t('laptop_finder_page.available', [], 'available'), ENT_QUOTES, 'UTF-8') ?>',
        noLimit: '<?= htmlspecialchars(i18n_t('laptop_finder_page.no_limit', [], 'No Limit'), ENT_QUOTES, 'UTF-8') ?>',
        noLaptopsFound: '<?= htmlspecialchars(i18n_t('laptop_finder_page.no_laptops_found', [], 'No laptops match the current filters.'), ENT_QUOTES, 'UTF-8') ?>',
        optimizationPack: '<?= htmlspecialchars(i18n_t('laptop_finder_page.maroc_optimization_pack', [], 'Maroc Optimization Pack'), ENT_QUOTES, 'UTF-8') ?>',
        matchesLabel: '<?= htmlspecialchars(i18n_t('laptop_finder_page.matches_located', [], 'Matches Located:') . ' 0', ENT_QUOTES, 'UTF-8') ?>',
        
        // Badge texts
        badgeAiWorkstation: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_ai_workstation', [], 'AI Workstation'), ENT_QUOTES, 'UTF-8') ?>',
        badgeMiniPc: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_mini_pc', [], 'Mini PC'), ENT_QUOTES, 'UTF-8') ?>',
        badgeCopilotPlus: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_copilot_plus', [], 'Copilot+ Certified'), ENT_QUOTES, 'UTF-8') ?>',
        badgeDedicatedGpu: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_dedicated_gpu', [], 'Dedicated Graphics'), ENT_QUOTES, 'UTF-8') ?>',
        badgeUltralight: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_ultralight', [], 'Ultra Lightweight'), ENT_QUOTES, 'UTF-8') ?>',
        badgeCatalogVerified: '<?= htmlspecialchars(i18n_t('laptop_finder_page.badge_catalog_verified', [], 'Catalog Verified'), ENT_QUOTES, 'UTF-8') ?>',
        
        // Score labels
        scorePerformance: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_performance', [], 'Catalog Performance'), ENT_QUOTES, 'UTF-8') ?>',
        scorePortability: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_portability', [], 'Portability'), ENT_QUOTES, 'UTF-8') ?>',
        scoreScreen: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_screen', [], 'Screen Quality'), ENT_QUOTES, 'UTF-8') ?>',
        scoreAiProcessor: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_ai_processor', [], 'AI Processor'), ENT_QUOTES, 'UTF-8') ?>',
        scoreValue: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_value', [], 'Value / Price'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_title', [], 'Catalog Fact Scores'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTooltip: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tooltip', [], 'Results use only stored catalog facts: no FPS, battery runtime, or temperature estimates.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTooltipFallback: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tooltip_fallback', [], 'Catalog fact score'), ENT_QUOTES, 'UTF-8') ?>',
        scoreHowCalc: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_how_calc', [], 'How is this score calculated?'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipPerformance: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_performance', [], 'Blended 1-10: 45% GPU tier, 25% AI/NPU score, 20% RAM, 10% storage (from catalog specs).'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipPortability: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_portability', [], '1-10 from weight, battery Wh, and screen size. Lighter weight and longer battery raise the score.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipScreen: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_screen', [], '1-10 by stored panel class: OLED 9.5, high refresh 8.7, standard 7.0.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipAi: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_ai', [], 'Scored out of 10 based on total NPU TOPS (Trillion Operations Per Second). 1.0 at 0 TOPS, 3.5 at 16 TOPS, 10 requires a 50+ TOPS processor.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipValue: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_value', [], 'Calculated by dividing the aggregate hardware performance score by the current retail price.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreNotApplicable: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_not_applicable', [], 'N/A'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipPerfFormula: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_perf_formula', [], 'Blended score = weighted sum of four sub-scores:'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipPortFormula: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_port_formula', [], 'Score = weight basis + battery bonus - large-screen penalty:'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipScreenFormula: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_screen_formula', [], 'Fixed by panel class:'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipAiFormula: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_ai_formula', [], 'Piecewise-linear on NPU TOPS (0->1.0, 16->3.5, 50->10):'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipAiNone: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_ai_none', [], 'No dedicated NPU in this machine - AI score is not applicable. Gaming/productivity performance is unaffected.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipValueFormula: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_value_formula', [], 'Score = (catalog hardware score x 1.25) / (price / 10,000):'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipValueHint: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_value_hint', [], 'higher means more spec per dirham.'), ENT_QUOTES, 'UTF-8') ?>',
        scoreTipResult: '<?= htmlspecialchars(i18n_t('laptop_finder_page.score_tip_result', [], 'Result'), ENT_QUOTES, 'UTF-8') ?>',
        specSize: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_size', [], 'Size'), ENT_QUOTES, 'UTF-8') ?>',
        specDisplays: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_displays', [], 'Displays'), ENT_QUOTES, 'UTF-8') ?>',
        specNetwork: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_network', [], 'Network'), ENT_QUOTES, 'UTF-8') ?>',
        specDisplay: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_display', [], 'Display'), ENT_QUOTES, 'UTF-8') ?>',
        specCpu: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_cpu', [], 'CPU'), ENT_QUOTES, 'UTF-8') ?>',
        specRam: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_ram', [], 'RAM'), ENT_QUOTES, 'UTF-8') ?>',
        specStorage: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_storage', [], 'Storage'), ENT_QUOTES, 'UTF-8') ?>',
        specGpu: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_gpu', [], 'GPU'), ENT_QUOTES, 'UTF-8') ?>',
        // Humanized screen-quality + product-type labels (previously rendered as raw enum values)
        screenOled: '<?= htmlspecialchars(i18n_t('laptop_finder_page.oled_color', [], 'OLED'), ENT_QUOTES, 'UTF-8') ?>',
        screenHighRefresh: '<?= htmlspecialchars(i18n_t('laptop_finder_page.high_refresh', [], 'High Refresh'), ENT_QUOTES, 'UTF-8') ?>',
        screenStandardIps: '<?= htmlspecialchars(i18n_t('laptop_finder_page.standard_ips', [], 'Standard IPS'), ENT_QUOTES, 'UTF-8') ?>',
        specProductType: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_product_type', [], 'Product Type'), ENT_QUOTES, 'UTF-8') ?>',
        specNpu: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_npu', [], 'NPU'), ENT_QUOTES, 'UTF-8') ?>',
        specTotalAiTops: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_total_ai_tops', [], 'Total AI TOPS'), ENT_QUOTES, 'UTF-8') ?>',
        specCooling: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_cooling', [], 'Cooling'), ENT_QUOTES, 'UTF-8') ?>',
        specMaxDisplays: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_max_displays', [], 'Max Displays'), ENT_QUOTES, 'UTF-8') ?>',
        specSource: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_source', [], 'Source'), ENT_QUOTES, 'UTF-8') ?>',
        specBattery: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_battery', [], 'Battery'), ENT_QUOTES, 'UTF-8') ?>',
        specWeightLabel: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_weight', [], 'Weight'), ENT_QUOTES, 'UTF-8') ?>',
        specDimensions: '<?= htmlspecialchars(i18n_t('laptop_finder_page.spec_dimensions', [], 'Dimensions'), ENT_QUOTES, 'UTF-8') ?>',
        priceAlertDescription: '<?= htmlspecialchars(i18n_t('laptop_finder_page.price_alert_desc', [], 'Currently {current} (was {old}). Set an alert for further drops.'), ENT_QUOTES, 'UTF-8') ?>',
        perMonth: '<?= htmlspecialchars(i18n_t('laptop_finder_page.per_month', [], '/mo'), ENT_QUOTES, 'UTF-8') ?>',
        certifiedBadge: '<?= htmlspecialchars(i18n_t('laptop_finder_page.certified_badge', [], 'Verified Catalog Data'), ENT_QUOTES, 'UTF-8') ?>',
        months24: '<?= htmlspecialchars(i18n_t('laptop_finder_page.months_24', [], '24 months'), ENT_QUOTES, 'UTF-8') ?>',
        months3: '<?= htmlspecialchars(i18n_t('laptop_finder_page.months_3', [], '3 months'), ENT_QUOTES, 'UTF-8') ?>',
        months6: '<?= htmlspecialchars(i18n_t('laptop_finder_page.months_6', [], '6 months'), ENT_QUOTES, 'UTF-8') ?>',
        months12: '<?= htmlspecialchars(i18n_t('laptop_finder_page.months_12', [], '12 months'), ENT_QUOTES, 'UTF-8') ?>',
        laptopsBreadcrumb: '<?= htmlspecialchars(i18n_t('laptop_finder_page.laptops_breadcrumb', [], 'Laptops / '), ENT_QUOTES, 'UTF-8') ?>',
        productTypeLaptop: '<?= htmlspecialchars(i18n_t('laptop_finder_page.product_type_laptop', [], 'Laptop'), ENT_QUOTES, 'UTF-8') ?>',
        productTypeMiniPc: '<?= htmlspecialchars(i18n_t('laptop_finder_page.product_type_mini_pc', [], 'Mini PC'), ENT_QUOTES, 'UTF-8') ?>',
        productTypeWorkstation: '<?= htmlspecialchars(i18n_t('laptop_finder_page.product_type_workstation', [], 'Workstation'), ENT_QUOTES, 'UTF-8') ?>',
        affordable: '<?= htmlspecialchars(i18n_t('laptop_finder_page.affordable', [], 'Affordable'), ENT_QUOTES, 'UTF-8') ?>',
        stretch: '<?= htmlspecialchars(i18n_t('laptop_finder_page.stretch', [], 'Stretch'), ENT_QUOTES, 'UTF-8') ?>',
        decisionSupport: '<?= htmlspecialchars(i18n_t('laptop_finder_page.decision_support', [], 'Decision Support'), ENT_QUOTES, 'UTF-8') ?>',
        financingOptions: '<?= htmlspecialchars(i18n_t('laptop_finder_page.financing_options', [], 'Financing Options'), ENT_QUOTES, 'UTF-8') ?>',
        regretPrevention: '<?= htmlspecialchars(i18n_t('laptop_finder_page.regret_prevention', [], 'Regret Prevention'), ENT_QUOTES, 'UTF-8') ?>',
        beforeYouDecide: '<?= htmlspecialchars(i18n_t('laptop_finder_page.before_you_decide', [], 'Before You Decide'), ENT_QUOTES, 'UTF-8') ?>',
        priceAlertAvailable: '<?= htmlspecialchars(i18n_t('laptop_finder_page.price_alert_available', [], 'Price Alert Available'), ENT_QUOTES, 'UTF-8') ?>',
        setAlert: '<?= htmlspecialchars(i18n_t('laptop_finder_page.set_alert', [], 'Set Alert'), ENT_QUOTES, 'UTF-8') ?>',
        yourEmail: '<?= htmlspecialchars(i18n_t('laptop_finder_page.your_email', [], 'your@email.com'), ENT_QUOTES, 'UTF-8') ?>',
        quantity: '<?= htmlspecialchars(i18n_t('products.quantity', [], 'Quantity'), ENT_QUOTES, 'UTF-8') ?>',
        decreaseQuantity: '<?= htmlspecialchars(i18n_t('products.decrease_quantity', [], 'Decrease quantity'), ENT_QUOTES, 'UTF-8') ?>',
        increaseQuantity: '<?= htmlspecialchars(i18n_t('products.increase_quantity', [], 'Increase quantity'), ENT_QUOTES, 'UTF-8') ?>',
        financingOptionsBody: '<?= htmlspecialchars(i18n_t('laptop_finder_page.financing_options_body', [], 'Installment selection is now tied to the item you add to cart so the chosen plan can follow the order flow instead of living only in the UI.'), ENT_QUOTES, 'UTF-8') ?>',
        catalogBackedView: '<?= htmlspecialchars(i18n_t('laptop_finder_page.catalog_backed_view', [], 'Catalog-backed product view. Shown specs come from stored product records, vetted field sources, and public review measurements for the same model where available.'), ENT_QUOTES, 'UTF-8') ?>',
        diagnosticSheet: '<?= htmlspecialchars(i18n_t('laptop_finder_page.diagnostic_sheet', [], 'Diagnostic Sheet'), ENT_QUOTES, 'UTF-8') ?>',
        specifications: '<?= htmlspecialchars(i18n_t('laptop_finder_page.specifications', [], 'Specifications'), ENT_QUOTES, 'UTF-8') ?>',
        screenSize: '<?= htmlspecialchars(i18n_t('laptop_finder_page.screen_size', [], 'Screen Size'), ENT_QUOTES, 'UTF-8') ?>',
        batteryCapacity: '<?= htmlspecialchars(i18n_t('laptop_finder_page.battery_capacity', [], 'Battery Capacity'), ENT_QUOTES, 'UTF-8') ?>',
        physicalWeight: '<?= htmlspecialchars(i18n_t('laptop_finder_page.physical_weight', [], 'Physical Weight'), ENT_QUOTES, 'UTF-8') ?>',
        warranty1Year: '<?= htmlspecialchars(i18n_t('laptop_finder_page.warranty_1_year', [], 'Maroc PC 1-Year Warranty'), ENT_QUOTES, 'UTF-8') ?>',
        whatsappExpert: '<?= htmlspecialchars(i18n_t('laptop_finder_page.whatsapp_expert', [], 'Expert Consultation via WhatsApp'), ENT_QUOTES, 'UTF-8') ?>',
        freeExpress: '<?= htmlspecialchars(i18n_t('laptop_finder_page.free_express', [], 'Fast & Free Shipping'), ENT_QUOTES, 'UTF-8') ?>',
        return7Day: '<?= htmlspecialchars(i18n_t('laptop_finder_page.return_7_day', [], '7-Day Return Guarantee'), ENT_QUOTES, 'UTF-8') ?>',
        budgetThreshold: '<?= htmlspecialchars(i18n_t('laptop_finder_page.budget_threshold', [], 'Based on 3,000 DH/month budget threshold'), ENT_QUOTES, 'UTF-8') ?>',
        installmentMonth: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_month', [], '{count} Month'), ENT_QUOTES, 'UTF-8') ?>',
        installmentMonths: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_months', [], '{count} Months'), ENT_QUOTES, 'UTF-8') ?>',
        payInInstallments: '<?= htmlspecialchars(i18n_t('laptop_finder_page.pay_in_installments', [], 'Pay in {count} monthly installments'), ENT_QUOTES, 'UTF-8') ?>',
        installmentOr: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_or', [], 'or'), ENT_QUOTES, 'UTF-8') ?>',
        monthShort: '<?= htmlspecialchars(i18n_t('laptop_finder_page.month_short', [], 'mo'), ENT_QUOTES, 'UTF-8') ?>',
        installmentPayments: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_payments', [], 'Installment Payments'), ENT_QUOTES, 'UTF-8') ?>',
        cashPrice: '<?= htmlspecialchars(i18n_t('laptop_finder_page.cash_price', [], 'Cash Price'), ENT_QUOTES, 'UTF-8') ?>',
        interestFee: '<?= htmlspecialchars(i18n_t('laptop_finder_page.interest_fee', [], 'Interest Fee ({rate}%/yr)'), ENT_QUOTES, 'UTF-8') ?>',
        totalCost: '<?= htmlspecialchars(i18n_t('laptop_finder_page.total_cost', [], 'Total Cost'), ENT_QUOTES, 'UTF-8') ?>',
        installmentMonthLabel: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_month_label', [], 'month'), ENT_QUOTES, 'UTF-8') ?>',
        month: '<?= htmlspecialchars(i18n_t('laptop_finder_page.month', [], 'month'), ENT_QUOTES, 'UTF-8') ?>',
        months: '<?= htmlspecialchars(i18n_t('laptop_finder_page.months', [], 'months'), ENT_QUOTES, 'UTF-8') ?>',
        installments: '<?= htmlspecialchars(i18n_t('laptop_finder_page.installment_months_label', [], 'months'), ENT_QUOTES, 'UTF-8') ?>',



        
        // Review measurements
        reviewTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_title', [], 'Review Measurements'), ENT_QUOTES, 'UTF-8') ?>',
        reviewNoData: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_no_data', [], 'No public FPS, battery, or thermal measurements are loaded for this item yet.'), ENT_QUOTES, 'UTF-8') ?>',
        reviewCompactTooltip: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_compact_tooltip', [], 'Public review measurements for the same model/GPU config.'), ENT_QUOTES, 'UTF-8') ?>',
        reviewSourceLabel: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_source_label', [], 'Source:'), ENT_QUOTES, 'UTF-8') ?>',
        reviewFpsTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_fps_title', [], 'Real FPS Measurements'), ENT_QUOTES, 'UTF-8') ?>',
        reviewBatteryTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_battery_title', [], 'Battery Life Measurements'), ENT_QUOTES, 'UTF-8') ?>',
        reviewThermalTitle: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_thermal_title', [], 'Temperature Measurements'), ENT_QUOTES, 'UTF-8') ?>',
        reviewFpsEmpty: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_fps_empty', [], 'FPS not loaded'), ENT_QUOTES, 'UTF-8') ?>',
        reviewBatteryEmpty: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_battery_empty', [], 'Battery not loaded'), ENT_QUOTES, 'UTF-8') ?>',
        reviewThermalEmpty: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_thermal_empty', [], 'Thermal not loaded'), ENT_QUOTES, 'UTF-8') ?>',
        reviewMeasurementEmpty: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_measurement_empty', [], 'Measurement not loaded'), ENT_QUOTES, 'UTF-8') ?>',
        reviewNotLoaded: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_not_loaded', [], 'Not loaded'), ENT_QUOTES, 'UTF-8') ?>',
        reviewNoMeasurementDetail: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_no_measurement_detail', [], 'No same-model numeric public measurement is stored yet.'), ENT_QUOTES, 'UTF-8') ?>',
        reviewMeasurementLabel: '<?= htmlspecialchars(i18n_t('laptop_finder_page.review_measurement_label', [], 'Measurement'), ENT_QUOTES, 'UTF-8') ?>'
    };
    <?php $GLOBALS['I18N_JS_CONTEXT'] = false; ?>
    </script>
    
    <!-- Outcome database -->
    <script src="assets/js/laptop_data.js"></script>

    <!-- Curator Logic -->
    <script>
        // CRITICAL: Preserve i18n before DOMContentLoaded
        const PRESERVED_I18N = window.__i18n || {};
        
        document.addEventListener('DOMContentLoaded', () => {
            // Restore i18n if it was cleared
            if (!window.__i18n || Object.keys(window.__i18n).length === 0) {
                window.__i18n = PRESERVED_I18N;
            }
            
            // Selected filter state
            const state = {
                usage: 'gaming',
                portability: 'any',
                screen: 'any',
                gpu: 'any',
                ai: 'any',
                inStockOnly: true,
                minBudget: 7000,
                maxBudget: 45000
            };

            // Comparison state
            let compareList = JSON.parse(localStorage.getItem('laptopCompare') || '[]');

            const els = {
                budgetMin: document.getElementById('budgetMin'),
                budgetMax: document.getElementById('budgetMax'),
                budgetCurrent: document.getElementById('budgetCurrent'),
                container: document.getElementById('laptopsContainer'),
                matchCount: document.getElementById('matchCount'),
                catalog: document.getElementById('laptopFinderCatalog'),
                detailPage: document.getElementById('laptopDetailPage'),
                detailContent: document.getElementById('laptopDetailContent')
            };
            const defaultPageTitle = document.title;

            const filterKeys = ['usage', 'portability', 'screen', 'gpu', 'ai'];
            const allowedFilters = {
                usage: ['gaming', 'business', 'student', 'creative'],
                portability: ['any', 'ultralight', 'standard', 'desktop_replacement'],
                screen: ['any', 'oled', 'high_refresh', 'standard'],
                gpu: ['any', 'dedicated', 'integrated'],
                ai: ['any', 'basic', 'copilot', 'workstation']
            };

            const clampBudget = (value) => Math.min(45000, Math.max(7000, Math.round(Number(value || 0) / 1000) * 1000));
            const formatMoney = (value) => window.formatMAD ? window.formatMAD(value) : Number(value || 0).toLocaleString() + ' DH';
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
            const escapeAttr = escapeHtml;
            const escapeUrl = (value) => {
                const url = String(value ?? '');
                return /^(?:https?:|data:)/i.test(url) ? '' : escapeAttr(url);
            };
            const escapeLinkUrl = (value) => {
                const url = String(value ?? '').trim();
                return /^(?:https?:)\/\//i.test(url) ? escapeAttr(url) : '#';
            };
            const scoreWidth = (value) => Math.max(0, Math.min(100, Number(value || 0) * 10));
            const scoreText = (value) => Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 1 });

            const REVIEW_MEASUREMENTS = [
                {
                    id: 'scar18-2024-rtx4090',
                    source: 'TechRadar / Notebookcheck / Tom\'s Hardware',
                    sourceUrl: 'https://www.techradar.com/reviews/asus-rog-strix-scar-18-2023',
                    match: laptop => /strix\s+scar\s+18/i.test(laptop.name || '') && /rtx\s*4090/i.test((laptop.specs || {}).GPU || ''),
                    fps: [
                        { game: 'Cyberpunk 2077', value: '115 FPS', detail: '1080p Ultra, TechRadar' },
                        { game: 'Total War: Warhammer III', value: '128 FPS', detail: '1080p Ultra, TechRadar' },
                        { game: 'Metro Exodus loop', value: '127 FPS', detail: 'RTX stress loop, Tom\'s Hardware 2024' }
                    ],
                    battery: [
                        { label: 'Web / media', value: '4h 23m', detail: '150 nits, Tom\'s Hardware 2024' },
                        { label: 'WLAN', value: '7h 08m', detail: 'Optimus, 150 cd/m2, Notebookcheck 2023' },
                        { label: 'Gaming unplugged', value: '0h 53m', detail: 'Witcher 3 Ultra, Notebookcheck 2023' }
                    ],
                    thermals: [
                        { label: 'GPU average', value: '64.7 C', detail: 'Metro Exodus RTX loop, Tom\'s Hardware 2024' },
                        { label: 'CPU P-core average', value: '73.2 C', detail: 'Metro Exodus RTX loop, Tom\'s Hardware 2024' },
                        { label: 'Underside max', value: '102 F', detail: 'Surface temperature, Tom\'s Hardware 2024' }
                    ]
                },
                {
                    id: 'zephyrus-g16-rtx4070',
                    source: 'Ultrabookreview',
                    sourceUrl: 'https://www.ultrabookreview.com/67333-asus-rog-zephyrus-g16-rtx4070-review/',
                    match: laptop => /zephyrus\s+g16/i.test(laptop.name || '') && /rtx\s*4070/i.test((laptop.specs || {}).GPU || ''),
                    fps: [
                        { game: 'Cyberpunk 2077', value: '54 FPS', detail: 'QHD+ Ultra, RTX off, Turbo' },
                        { game: 'Far Cry 6', value: '80 FPS', detail: 'QHD+ Ultra, TAA, Turbo' },
                        { game: 'Witcher 3', value: '101 FPS', detail: 'QHD+ Ultra, Turbo' }
                    ],
                    battery: [],
                    thermals: [
                        { label: 'CPU gaming', value: '85-90 C', detail: 'Turbo, desk, around 24 C ambient' },
                        { label: 'GPU gaming', value: '~85 C', detail: 'Turbo, desk, around 24 C ambient' },
                        { label: 'Raised stand', value: '-3 to -8 C', detail: 'Improvement reported across games' }
                    ]
                },
                {
                    id: 'tuf-a14-rtx4060',
                    source: 'Tom\'s Hardware',
                    sourceUrl: 'https://www.tomshardware.com/laptops/gaming-laptops/asus-tuf-gaming-a14-review',
                    match: laptop => /tuf\s+gaming\s+a14/i.test(laptop.name || '') && /rtx\s*4060/i.test((laptop.specs || {}).GPU || ''),
                    fps: [],
                    battery: [
                        { label: 'Mixed battery test', value: '10h 14m', detail: '150 nits web/video/OpenGL workload' }
                    ],
                    thermals: []
                },
                {
                    id: 'proart-p16-rtx4070',
                    source: 'Notebookcheck',
                    sourceUrl: 'https://www.notebookcheck.net/Asus-ProArt-P16-laptop-review-AMD-Zen-5-meets-RTX-4070-laptop-and-4K-OLED.871739.0.html',
                    match: laptop => /proart\s+p16/i.test(laptop.name || '') && /rtx\s*4070/i.test((laptop.specs || {}).GPU || ''),
                    fps: [
                        { game: 'Cyberpunk 2077', value: '157.7 FPS', detail: 'FHD Ultra, no FSR, Notebookcheck' }
                    ],
                    battery: [],
                    thermals: []
                }
            ];

            function updateBudgetLabel() {
                els.budgetCurrent.textContent = `${formatMoney(state.minBudget)} - ${state.maxBudget >= 45000 ? (window.__i18n?.noLimit || 'No Limit') : formatMoney(state.maxBudget)}`;
            }

            function syncControlsFromState() {
                filterKeys.forEach(key => {
                    document.querySelectorAll(`.selector-card[data-filter="${key}"]`).forEach(card => {
                        card.classList.toggle('active', card.getAttribute('data-val') === state[key]);
                    });
                });
                els.budgetMin.value = state.minBudget;
                els.budgetMax.value = state.maxBudget;
                updateBudgetLabel();
            }

            function applyStateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const usageParam = params.get('usage') || params.get('use');
                const incoming = {
                    usage: usageParam,
                    portability: params.get('portability'),
                    screen: params.get('screen'),
                    gpu: params.get('gpu'),
                    ai: params.get('ai')
                };

                Object.entries(incoming).forEach(([key, value]) => {
                    if (value && allowedFilters[key].includes(value)) {
                        state[key] = value;
                    }
                });

                const minParam = params.get('min') || params.get('minBudget');
                const maxParam = params.get('max') || params.get('maxBudget') || params.get('budget');
                if (minParam) state.minBudget = clampBudget(minParam);
                if (maxParam) state.maxBudget = clampBudget(maxParam);
                if (state.minBudget > state.maxBudget) {
                    [state.minBudget, state.maxBudget] = [state.maxBudget, state.minBudget];
                }
                // Stock param
                if (params.get('stock') === 'all') state.inStockOnly = false;
            }

            function updateUrlState() {
                const currentParams = new URLSearchParams(window.location.search);
                if (currentParams.has('laptop')) return;

                const params = new URLSearchParams();
                if (state.usage !== 'gaming') params.set('use', state.usage);
                if (state.portability !== 'any') params.set('portability', state.portability);
                if (state.screen !== 'any') params.set('screen', state.screen);
                if (state.gpu !== 'any') params.set('gpu', state.gpu);
                if (state.ai !== 'any') params.set('ai', state.ai);
                if (!state.inStockOnly) params.set('stock', 'all');
                if (state.minBudget !== 7000) params.set('min', state.minBudget);
                if (state.maxBudget !== 45000) params.set('max', state.maxBudget);

                const nextUrl = params.toString()
                    ? `${window.location.pathname}?${params.toString()}`
                    : window.location.pathname;
                window.history.replaceState(null, '', nextUrl);
            }

            applyStateFromUrl();
            syncControlsFromState();

            // Bind selectors
            document.querySelectorAll('.selector-card').forEach(card => {
                card.addEventListener('click', () => {
                    const filterName = card.getAttribute('data-filter');
                    const value = card.getAttribute('data-val');

                    if (!allowedFilters[filterName] || !allowedFilters[filterName].includes(value)) return;
                    state[filterName] = value;
                    syncControlsFromState();
                    render();
                });
            });

            // Bind budget range
            [els.budgetMin, els.budgetMax].forEach(slider => {
                slider.addEventListener('input', () => {
                    state.minBudget = clampBudget(els.budgetMin.value);
                    state.maxBudget = clampBudget(els.budgetMax.value);
                    if (state.minBudget > state.maxBudget) {
                        if (slider === els.budgetMin) state.maxBudget = state.minBudget;
                        else state.minBudget = state.maxBudget;
                    }
                    syncControlsFromState();
                    render();
                });
            });

            // In-stock toggle
            const inStockCheckbox = document.getElementById('inStockOnly');
            if (inStockCheckbox) {
                inStockCheckbox.checked = state.inStockOnly;
                inStockCheckbox.addEventListener('change', () => {
                    state.inStockOnly = inStockCheckbox.checked;
                    render();
                });
            }

            // Update stock count label
            function updateStockCount() {
                if (typeof laptops === 'undefined') return;
                const inStock = laptops.filter(l => l.inStock && l.stockQuantity > 0).length;
                const el = document.getElementById('stockCount');
                if (el) el.textContent = `(${inStock} ${window.__i18n?.available || 'available'})`;
            }
            updateStockCount();

            function getAiBadgeHtml(laptop, laptopId) {
                const tops = Number(laptop.npuTops || 0);
                const tier = laptop.aiTier || 'none';
                const isMiniPc = laptop.category === 'mini_pc';
                let label = '';
                let icon = 'fa-brain';
                let tierClass = '';

                if (tier === 'workstation') {
                    label = 'AI Workstation';
                    tierClass = 'workstation';
                } else if (laptop.isCopilotPlus) {
                    label = isMiniPc ? (window.__i18n?.aiCopilotPlusMiniPcLabel || 'Copilot+ Mini PC') : (window.__i18n?.aiCopilotPlusLabel || 'Copilot+');
                } else if (tops >= 10 || tier === 'basic') {
                    label = isMiniPc ? 'AI-Ready Mini PC' : 'AI-Ready';
                    icon = 'fa-microchip';
                    tierClass = 'basic';
                }

                if (!label) return '';
                const topsLabel = tops > 0 ? `${tops.toLocaleString(undefined, { maximumFractionDigits: 0 })} TOPS NPU` : 'NPU';
                return `<div class="ai-badge ${tierClass}" onclick="openAIExplainer(${laptopId})" title="${escapeAttr(laptop.npuModel || 'NPU')} - ${topsLabel}">
                    <i class="fas ${icon}"></i>
                    <span class="badge-text">${escapeHtml(label)}</span>
                    <span class="badge-tops">${escapeHtml(topsLabel)}</span>
                </div>`;
            }

            // Humanize raw catalog enum values into customer-facing labels.
            // The data file stores snake_case tokens (high_refresh, mini_pc, ...);
            // these map them to the same i18n keys the filter cards already use.
            const humanizeScreenQuality = (quality) => ({
                oled: window.__i18n?.screenOled || 'OLED',
                high_refresh: window.__i18n?.screenHighRefresh || 'High Refresh',
                standard: window.__i18n?.screenStandardIps || 'Standard IPS'
            }[quality] || '');

            const humanizeProductType = (laptop) => {
                const type = (laptop.formFactor || laptop.category || 'laptop').toLowerCase();
                const typeMap = {
                    'laptop': window.__i18n?.productTypeLaptop || 'Laptop',
                    'mini_pc': window.__i18n?.productTypeMiniPc || 'Mini PC',
                    'workstation': window.__i18n?.productTypeWorkstation || 'Workstation'
                };
                return typeMap[type] || type.replace(/_/g, ' ');
            };

            function getVerifiedHighlights(laptop) {
                const specs = laptop.specs || {};
                const chips = [];
                const seenLabels = new Set();
                const addChip = (label, value) => {
                    const clean = String(value ?? '').trim();
                    if (!clean || clean === '0' || clean === '0 Wh' || clean === '0 kg' || seenLabels.has(label)) return;
                    seenLabels.add(label);
                    chips.push({ label, value: clean });
                };

                const npuTops = Number(laptop.npuTops || 0);

                if (laptop.category === 'mini_pc' || laptop.category === 'workstation') {
                    // Mini PC / workstation: lead with the form factor identity.
                    addChip(window.__i18n?.specProductType || 'Type', humanizeProductType(laptop));
                    addChip(window.__i18n?.specCpu || 'CPU', specs.CPU);
                    addChip(window.__i18n?.specRam || 'RAM', specs.RAM);
                    addChip(window.__i18n?.specStorage || 'Storage', specs.Storage);
                } else {
                    // Standard laptop: the 4 facts a buyer scans first, each shown exactly once.
                    addChip(window.__i18n?.specCpu || 'CPU', specs.CPU);
                    addChip(window.__i18n?.specDisplay || 'Display', laptop.screenSize
                        ? `${Number(laptop.screenSize).toLocaleString(undefined, { maximumFractionDigits: 1 })}" ${humanizeScreenQuality(laptop.screenQuality)}`.trim()
                        : specs.Display);
                    addChip(window.__i18n?.specRam || 'RAM', specs.RAM);
                    // Slot 4: GPU for gaming/creative builds, storage for business/student (where it matters more).
                    const wantsGpu = laptop.usageCategory === 'gaming' || laptop.usageCategory === 'creative' || laptop.gpuTier === 'dedicated';
                    if (wantsGpu && specs.GPU) {
                        addChip(window.__i18n?.specGpu || 'GPU', specs.GPU);
                    } else {
                        addChip(window.__i18n?.specStorage || 'Storage', specs.Storage);
                    }
                }

                // NPU badge only when present and we still have a free slot — never duplicate.
                if (npuTops > 0 && chips.length < 4) {
                    addChip(window.__i18n?.specNpu || 'NPU', `${npuTops.toLocaleString(undefined, { maximumFractionDigits: 0 })} TOPS`);
                }

                return chips.slice(0, 4).map(chip => `
                    <div class="verified-spec-chip">
                        <span>${escapeHtml(chip.label)}</span>
                        <strong class="notranslate" translate="no">${escapeHtml(chip.value)}</strong>
                    </div>
                `).join('');
            }

            function getVerifiedModalData(laptop) {
                const specs = laptop.specs || {};
                const rows = [];
                const addRow = (label, value) => {
                    const clean = String(value ?? '').trim();
                    if (!clean || clean === '0' || clean === '0 Wh' || clean === '0 kg') return;
                    rows.push({ label, value: clean });
                };

                addRow(window.__i18n?.specProductType || 'Product Type', humanizeProductType(laptop));
                addRow(window.__i18n?.specNpu || 'NPU', laptop.npuTops ? `${Number(laptop.npuTops).toLocaleString(undefined, { maximumFractionDigits: 0 })} TOPS ${laptop.npuModel || ''}` : specs.NPU);
                addRow(window.__i18n?.specTotalAiTops || 'Total AI TOPS', specs['Total AI TOPS']);
                addRow(window.__i18n?.specDimensions || 'Dimensions', laptop.dimensions || specs.Dimensions);
                addRow(window.__i18n?.specCooling || 'Cooling', laptop.coolingType || specs.Cooling);
                addRow(window.__i18n?.specMaxDisplays || 'Max Displays', laptop.maxDisplays ? `${laptop.maxDisplays}` : specs['Max Displays']);
                addRow(window.__i18n?.specSource || 'Source', specs.Source);

                if (!rows.length) return '';
                return `
                    <div style="background:var(--page-bg-2);border:1px solid var(--border);border-radius:12px;padding:16px;margin:10px 0;">
                        <h4 style="font-family:'Orbitron',sans-serif;font-size:0.9rem;margin:0 0 12px 0;border-bottom:1px solid var(--border);padding-bottom:8px;">
                            <i class="fas fa-database" style="color:var(--cyan);margin-right:6px;"></i><span style="text-transform:uppercase;">${window.__i18n?.certifiedBadge || 'Verified Catalog Data'}</span>
                        </h4>
                        ${rows.map(row => `
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:8px 0;border-bottom:1px solid var(--border);font-size:0.82rem;">
                                <span style="color:var(--muted);font-weight:700;">${escapeHtml(row.label)}</span>
                                <span class="notranslate" translate="no" style="color:var(--white);font-weight:500;text-align:right;">${escapeHtml(row.value)}</span>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            function getReviewMeasurement(laptop) {
                return REVIEW_MEASUREMENTS.find(entry => entry.match(laptop)) || null;
            }

            function firstMeasurement(list) {
                return Array.isArray(list) && list.length ? list[0] : null;
            }

            function measurementRows(items, emptyLabel) {
                const label = emptyLabel || window.__i18n?.reviewMeasurementEmpty || 'Measurement not loaded';
                if (!Array.isArray(items) || !items.length) {
                    return `
                        <div class="review-measurement-card">
                            <span><i class="fas fa-circle-info"></i>${escapeHtml(label)}</span>
                            <strong>${window.__i18n?.reviewNotLoaded || 'Not loaded'}</strong>
                            <small>${window.__i18n?.reviewNoMeasurementDetail || 'No same-model numeric public measurement is stored yet.'}</small>
                        </div>
                    `;
                }
                return items.map(item => `
                    <div class="review-measurement-card">
                        <span><i class="fas fa-chart-line"></i>${escapeHtml(item.game || item.label || (window.__i18n?.reviewMeasurementLabel || 'Measurement'))}</span>
                        <strong class="notranslate" translate="no">${escapeHtml(item.value)}</strong>
                        <small>${escapeHtml(item.detail || '')}</small>
                    </div>
                `).join('');
            }

            function getReviewMeasurementsPanel(laptop, compact = false) {
                const benchmark = getReviewMeasurement(laptop);
                if (!benchmark) {
                    if (compact) return '';
                    return `
                        <div class="review-measurements">
                            <div class="review-measurements-head">
                                <h4 class="review-measurements-title"><i class="fas fa-gauge-high" style="color:var(--cyan);margin-right:6px;"></i>${window.__i18n?.reviewTitle || 'Review Measurements'}</h4>
                            </div>
                            <p style="margin:0;color:var(--muted);font-size:0.85rem;line-height:1.5;">${window.__i18n?.reviewNoData || 'No public FPS, battery, or thermal measurements are loaded for this item yet.'}</p>
                        </div>
                    `;
                }

                if (compact) {
                    const fps = firstMeasurement(benchmark.fps);
                    const battery = firstMeasurement(benchmark.battery);
                    const thermal = firstMeasurement(benchmark.thermals);
                    return `
                        <div class="review-measurements compact" title="${escapeAttr(window.__i18n?.reviewCompactTooltip || 'Public review measurements for the same model/GPU config.')}">
                            ${measurementRows([
                                fps ? { label: 'FPS', value: fps.value, detail: fps.game } : null,
                                battery ? { label: window.__i18n?.specBattery || 'Battery', value: battery.value, detail: battery.label } : null,
                                thermal ? { label: 'Thermal', value: thermal.value, detail: thermal.label } : null
                            ].filter(Boolean))}
                        </div>
                    `;
                }

                return `
                    <div class="review-measurements">
                        <div class="review-measurements-head">
                            <h4 class="review-measurements-title"><i class="fas fa-gauge-high" style="color:var(--cyan);margin-right:6px;"></i>${window.__i18n?.reviewTitle || 'Review Measurements'}</h4>
                            <a class="review-source-link" href="${escapeLinkUrl(benchmark.sourceUrl)}" target="_blank" rel="noopener">${window.__i18n?.reviewSourceLabel || 'Source:'} ${escapeHtml(benchmark.source)}</a>
                        </div>
                        <div>
                            <h5 style="font-family:'Space Mono',monospace;color:var(--muted);font-size:0.72rem;text-transform:uppercase;margin:0 0 8px 0;">${window.__i18n?.reviewFpsTitle || 'Real FPS Measurements'}</h5>
                            <div class="review-measurements-grid">${measurementRows(benchmark.fps, window.__i18n?.reviewFpsEmpty || 'FPS not loaded')}</div>
                        </div>
                        <div>
                            <h5 style="font-family:'Space Mono',monospace;color:var(--muted);font-size:0.72rem;text-transform:uppercase;margin:0 0 8px 0;">${window.__i18n?.reviewBatteryTitle || 'Battery Life Measurements'}</h5>
                            <div class="review-measurements-grid">${measurementRows(benchmark.battery, window.__i18n?.reviewBatteryEmpty || 'Battery not loaded')}</div>
                        </div>
                        <div>
                            <h5 style="font-family:'Space Mono',monospace;color:var(--muted);font-size:0.72rem;text-transform:uppercase;margin:0 0 8px 0;">${window.__i18n?.reviewThermalTitle || 'Temperature Measurements'}</h5>
                            <div class="review-measurements-grid">${measurementRows(benchmark.thermals, window.__i18n?.reviewThermalEmpty || 'Thermal not loaded')}</div>
                        </div>
                    </div>
                `;
            }

            // Reproduces the exact scoring math from export-laptops.php so each tooltip
            // can show HOW a score was built (real inputs + weights), not just list variables.
            // Any change to the PHP formulas must be mirrored here.
            function buildScoreInputs(laptop) {
                const specs = laptop.specs || {};
                const ramMatch = specs.RAM ? String(specs.RAM).match(/(\d+)\s*GB/i) : null;
                const ramGb = ramMatch ? parseInt(ramMatch[1], 10) : 0;
                const storageMatch = specs.Storage ? String(specs.Storage).match(/(\d+(?:\.\d+)?)\s*(TB|GB)/i) : null;
                const storageGb = storageMatch ? parseFloat(storageMatch[1]) * (storageMatch[2].toUpperCase() === 'TB' ? 1024 : 1) : 0;
                const npuTops = Number(laptop.npuTops || 0);
                const clamp = (v) => Math.round(Math.max(1.0, Math.min(10.0, v)) * 10) / 10;

                const gpuScore = laptop.gpuTier === 'dedicated' ? 9.0
                    : laptop.gpuTier === 'integrated' ? 6.5 : 5.0;
                const memoryScore = ramGb > 0 ? clamp((ramGb / 32) * 8 + 2) : 5.0;
                const storageScore = storageGb > 0 ? clamp((storageGb / 1024) * 6 + 4) : 5.0;
                const hasNpu = npuTops > 0;
                const aiScore = !hasNpu ? null  // N/A — see buildScoreBreakdown('ai')
                    : npuTops < 16 ? clamp(1 + (npuTops / 16) * 2.5)
                    : npuTops < 50 ? clamp(3.5 + ((npuTops - 16) / 34) * 6.5)
                    : 10.0;
                // For the blended performance calc, a missing NPU contributes the 1.0 floor
                // (matches export), but the *displayed* AI value is N/A (Fix A).
                const aiForBlend = hasNpu ? aiScore : 1.0;

                return {
                    gpuTier: laptop.gpuTier, gpuScore,
                    ramGb, memoryScore,
                    storageGb, storageScore,
                    npuTops, hasNpu, aiScore, aiForBlend,
                    weightKg: Number(laptop.weightKg || 0),
                    batteryWh: Number(laptop.batteryWh || 0),
                    screenSize: Number(laptop.screenSize || 0),
                    screenQuality: laptop.screenQuality,
                    price: Number(laptop.price || 0)
                };
            }

            // Returns a transparent, per-metric explanation as { intro, lines, result }.
            // `intro` and `result` are translated phrases (flow with page direction).
            // `lines` are pure LTR formula rows (numbers + operators) — rendered inside
            // <bdi dir="ltr"> so they stay coherent in RTL languages like Arabic.
            function buildScoreBreakdown(metric, laptop) {
                const inp = buildScoreInputs(laptop);
                const scores = laptop.scores || {};
                const na = window.__i18n?.scoreNotApplicable || 'N/A';

                switch (metric) {
                    case 'performance': {
                        const aiPart = inp.hasNpu ? inp.aiScore : 1.0;
                        return {
                            intro: window.__i18n?.scoreTipPerfFormula || 'Blended score = weighted sum of four sub-scores:',
                            lines: [
                                `GPU (${inp.gpuTier || 'unknown'}, ${inp.gpuScore.toFixed(1)}) × 45% = ${(inp.gpuScore * 0.45).toFixed(2)}`,
                                `AI ${inp.hasNpu ? '(' + inp.npuTops + ' TOPS, ' + inp.aiScore.toFixed(1) + ')' : '(' + na + ', floor 1.0)'} × 25% = ${(aiPart * 0.25).toFixed(2)}`,
                                `RAM (${inp.ramGb || '?'}GB, ${inp.memoryScore.toFixed(1)}) × 20% = ${(inp.memoryScore * 0.20).toFixed(2)}`,
                                `Storage (${inp.storageGb ? Math.round(inp.storageGb) + 'GB' : '?'}, ${inp.storageScore.toFixed(1)}) × 10% = ${(inp.storageScore * 0.10).toFixed(2)}`
                            ],
                            result: `${(scores.performance || 0).toFixed(1)} / 10`
                        };
                    }
                    case 'portability': {
                        const weightPart = inp.weightKg > 0 ? Math.max(0, 10 - (inp.weightKg - 1) * 4) : 4.0;
                        const batteryPart = inp.batteryWh > 0 ? Math.min(2, inp.batteryWh / 50) : 0;
                        return {
                            intro: window.__i18n?.scoreTipPortFormula || 'Score = weight basis + battery bonus − large-screen penalty:',
                            lines: [
                                `${inp.weightKg ? inp.weightKg + 'kg' : '?'} → ${weightPart.toFixed(1)}`,
                                `${inp.batteryWh ? inp.batteryWh + 'Wh' : 'no battery'} → +${batteryPart.toFixed(1)}`,
                                inp.screenSize >= 17 ? `${inp.screenSize}" screen → −1.0` : 'no penalty'
                            ],
                            result: `${(scores.portability || 0).toFixed(1)} / 10`
                        };
                    }
                    case 'screen': {
                        const map = { oled: 9.5, high_refresh: 8.7 };
                        const base = map[inp.screenQuality] || (inp.screenSize > 0 ? 7.0 : 1.0);
                        return {
                            intro: window.__i18n?.scoreTipScreenFormula || 'Fixed by panel class:',
                            lines: [`${inp.screenQuality || 'standard'} ${inp.screenSize ? '(' + inp.screenSize + '")' : ''} → ${base.toFixed(1)} / 10`],
                            result: null
                        };
                    }
                    case 'ai': {
                        if (!inp.hasNpu) {
                            return { intro: window.__i18n?.scoreTipAiNone || 'No dedicated NPU in this machine — AI score is not applicable. Gaming/productivity performance is unaffected.', lines: [], result: null };
                        }
                        return {
                            intro: window.__i18n?.scoreTipAiFormula || 'Piecewise-linear on NPU TOPS (0→1.0, 16→3.5, 50→10):',
                            lines: [`${inp.npuTops} TOPS → ${(inp.aiScore || 0).toFixed(1)} / 10`],
                            result: null
                        };
                    }
                    case 'value': {
                        return {
                            intro: window.__i18n?.scoreTipValueFormula || 'Score = (catalog hardware score × 1.25) ÷ (price ÷ 10,000):',
                            lines: [],
                            result: `${(scores.value || 0).toFixed(1)} / 10`,
                            hint: window.__i18n?.scoreTipValueHint || 'higher means more spec per dirham.'
                        };
                    }
                    default:
                        return { intro: window.__i18n?.scoreTooltipFallback || 'Catalog fact score', lines: [], result: null };
                }
            }

            // Render a breakdown object into tooltip HTML with proper bidi isolation:
            // translated phrases flow with the page dir; formula lines are locked LTR.
            function renderBreakdownTip(bd) {
                const resultLabel = window.__i18n?.scoreTipResult || 'Result';
                let html = `<div class="tip-intro">${escapeHtml(bd.intro)}</div>`;
                if (bd.lines && bd.lines.length) {
                    html += `<div class="tip-lines">` + bd.lines.map(line =>
                        `<div class="tip-line" dir="ltr">${escapeHtml(line)}</div>`
                    ).join('') + `</div>`;
                }
                if (bd.result) {
                    html += `<div class="tip-result">${escapeHtml(resultLabel)}: <bdi dir="ltr">${escapeHtml(bd.result)}</bdi>${bd.hint ? ' — ' + escapeHtml(bd.hint) : ''}</div>`;
                }
                return html;
            }

            function getFactScoreBars(laptop, compact = false) {
                const scores = laptop.scores || {};
                const inp = buildScoreInputs(laptop);
                // [metricKey, labelKey, fillClass]
                const rows = [
                    ['performance', 'scorePerformance', 'performance'],
                    ['portability', 'scorePortability', 'portability'],
                    ['screen', 'scoreScreen', 'screen'],
                    ['ai', 'scoreAiProcessor', 'value'],
                    ['value', 'scoreValue', 'value']
                ];
                return `
                    <div class="metric-container">
                        ${!compact ? `<h4 style="font-family:'Orbitron',sans-serif;font-size:0.85rem;text-transform:uppercase;color:var(--white);margin:0 0 4px 0;">${window.__i18n?.scoreTitle || 'Catalog Fact Scores'}</h4>` : ''}
                        ${rows.map(([metric, labelKey, fillClass]) => {
                            const breakdown = buildScoreBreakdown(metric, laptop);
                            const tipId = `tip-${metric}-${laptop.id || Math.random().toString(36).slice(2, 8)}`;
                            // Structured breakdown → bidi-safe HTML (formula lines locked LTR).
                            const tipHtml = renderBreakdownTip(breakdown);
                            const isAiNa = metric === 'ai' && !inp.hasNpu;
                            // Fix A: no-NPU machines show "N/A" — not a 1/10 failing grade — and no bar.
                            const valHtml = isAiNa
                                ? `<span class="metric-val metric-val-na">${escapeHtml(window.__i18n?.scoreNotApplicable || 'N/A')}</span>`
                                : `<span class="metric-val">${scoreText(scores[metric])} <span class="metric-val-max">/ 10</span></span>`;
                            const trackHtml = isAiNa
                                ? `<div class="metric-track metric-track-na" aria-hidden="true"></div>`
                                : `<div class="metric-track" aria-hidden="true"><div class="metric-fill ${fillClass}" style="width: ${scoreWidth(scores[metric])}%"></div></div>`;
                            return `
                            <div class="metric-bar-group${isAiNa ? ' metric-bar-na' : ''}">
                                <span class="metric-label">
                                    ${escapeHtml(window.__i18n?.[labelKey] || metric)}
                                    <i class="fas fa-circle-info metric-tip-icon"
                                       tabindex="0"
                                       role="button"
                                       aria-label="${escapeAttr(window.__i18n?.scoreHowCalc || 'How is this score calculated?')}"
                                       data-tip="${tipId}"
                                       aria-describedby="${tipId}"></i>
                                    <span class="metric-tip" id="${tipId}" role="tooltip">${tipHtml}</span>
                                </span>
                                ${trackHtml}
                                ${valHtml}
                            </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            function getBadgeText(laptop) {
                if (laptop.aiTier === 'workstation') {
                    return window.__i18n?.badgeAiWorkstation || 'AI Workstation';
                }
                if (laptop.category === 'mini_pc') {
                    return window.__i18n?.badgeMiniPc || 'Mini PC';
                }
                if (laptop.isCopilotPlus) {
                    return window.__i18n?.badgeCopilotPlus || 'Copilot+ Certified';
                }
                if (laptop.usageCategory === 'gaming' && laptop.gpuTier === 'dedicated') {
                    return window.__i18n?.badgeDedicatedGpu || 'Dedicated Graphics';
                }
                if (laptop.portabilityTier === 'ultralight') {
                    return window.__i18n?.badgeUltralight || 'Ultra Lightweight';
                }
                return window.__i18n?.badgeCatalogVerified || 'Catalog Verified';
            }

            function render() {
                updateUrlState();

                // Ensure laptops is defined
                if (typeof laptops === 'undefined') {
                    els.container.innerHTML = '<p class="text-center">' + (window.__i18n?.databaseNotLoaded || 'Laptop database is not loaded.') + '</p>';
                    return;
                }

                // Filter items
                let matches = laptops.filter(laptop => {
                    const price = Number(laptop.price || 0);
                    // Check budget window
                    if (price < state.minBudget || (state.maxBudget < 45000 && price > state.maxBudget)) {
                        return false;
                    }
                    // Check primary usage. AI tier filters intentionally search across personas
                    // so Copilot+/mini PC categories are not hidden by the default Gaming persona.
                    if (state.ai === 'any' && laptop.usageCategory !== state.usage) {
                        return false;
                    }
                    // Check Portability
                    if (state.portability !== 'any' && laptop.portabilityTier !== state.portability) {
                        return false;
                    }
                    // Check Screen
                    if (state.screen !== 'any' && laptop.screenQuality !== state.screen) {
                        return false;
                    }
                    // Check GPU
                    if (state.gpu !== 'any' && laptop.gpuTier !== state.gpu) {
                        return false;
                    }
                    // Check AI Performance
                    if (state.ai !== 'any') {
                        if (state.ai === 'basic' && laptop.aiTier !== 'basic') return false;
                        if (state.ai === 'copilot' && (!laptop.isCopilotPlus || laptop.aiTier === 'workstation')) return false;
                        if (state.ai === 'workstation' && laptop.aiTier !== 'workstation') return false;
                    }
                    // Check In-Stock
                    if (state.inStockOnly && (!laptop.inStock || laptop.stockQuantity <= 0)) {
                        return false;
                    }
                    return true;
                });

                const screenRank = (quality) => ({ oled: 3, high_refresh: 2, standard: 1 }[quality] || 0);
                const gpuRank = (tier) => tier === 'dedicated' ? 2 : tier === 'integrated' ? 1 : 0;
                matches.sort((a, b) => {
                    if (state.ai !== 'any') {
                        const npuDiff = Number(b.npuTops || 0) - Number(a.npuTops || 0);
                        if (npuDiff !== 0) return npuDiff;
                    }
                    if (state.usage === 'gaming' || state.usage === 'creative') {
                        const gpuDiff = gpuRank(b.gpuTier) - gpuRank(a.gpuTier);
                        if (gpuDiff !== 0) return gpuDiff;
                        const screenDiff = screenRank(b.screenQuality) - screenRank(a.screenQuality);
                        if (screenDiff !== 0) return screenDiff;
                    }
                    if (state.usage === 'business' || state.usage === 'student') {
                        const weightDiff = Number(a.weightKg || 99) - Number(b.weightKg || 99);
                        if (weightDiff !== 0) return weightDiff;
                        const batteryDiff = Number(b.batteryWh || 0) - Number(a.batteryWh || 0);
                        if (batteryDiff !== 0) return batteryDiff;
                    }
                    return Number(a.price || 0) - Number(b.price || 0);
                });

                let showingAlternatives = false;

                // Alternate search: if no exact matches found, find closest outcome matches
                if (matches.length === 0) {
                    showingAlternatives = true;
                    // Sort all laptops by how close they match usage and price
                    matches = laptops
                        .filter(l => {
                            const price = Number(l.price || 0);
                            return price >= state.minBudget - 3000 && (state.maxBudget >= 45000 || price <= state.maxBudget + 3000);
                        }) // soft budget check
                        .map(l => {
                            // Calculate match score
                            let score = 0;
                            if (l.usageCategory === state.usage) score += 5;
                            if (state.portability === 'any' || l.portabilityTier === state.portability) score += 2;
                            if (state.screen === 'any' || l.screenQuality === state.screen) score += 2;
                            if (state.gpu === 'any' || l.gpuTier === state.gpu) score += 2;
                            
                            // price closeness
                            const targetBudget = state.maxBudget >= 45000 ? Math.max(state.minBudget, Number(l.price || 0)) : state.maxBudget;
                            const priceDiff = Math.abs(Number(l.price || 0) - targetBudget);
                            score += Math.max(0, 3 - (priceDiff / 5000));

                            return { laptop: l, matchScore: score };
                        })
                        .sort((a, b) => b.matchScore - a.matchScore)
                        .slice(0, 3)
                        .map(item => item.laptop);
                }

                els.matchCount.textContent = showingAlternatives ? `0 (${window.__i18n?.surfacingClosest || 'Showing closest alternatives'})` : matches.length.toString();

                if (matches.length === 0) {
                    els.container.innerHTML = `
                        <div class="empty-match-state">
                            <i class="fas fa-triangle-exclamation"></i>
                            <h3>${window.__i18n?.noResultsTitle || 'No suitable laptops found'}</h3>
                            <p>${window.__i18n?.noResultsBody || 'Try expanding your maximum budget or clearing some filter constraints.'}</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                if (showingAlternatives) {
                    html += `<div class="alternative-headline"><i class="fas fa-compass"></i> ${window.__i18n?.surfacingClosest || 'Surfacing Closest Alternatives'}</div>`;
                }

                matches.forEach(laptop => {
                    const laptopId = Number(laptop.id || 0);
                    const priceFormatted = formatMoney(laptop.price);
                    const oldPriceHtml = laptop.oldPrice 
                        ? `<div class="laptop-old-price">${formatMoney(laptop.oldPrice)}</div>` 
                        : '';
                    const imageSrc = escapeUrl(laptop.image);
                    const laptopName = escapeHtml(laptop.name);
                    const laptopBrand = escapeHtml(laptop.brand);

                    const aiBadgeHtml = getAiBadgeHtml(laptop, laptopId);

                    // Compare checkbox state
                    const isCompared = compareList.includes(laptopId);

                    // Per-month installment hint for the price block (12-month plan, 5% annual).
                    // Keeps price as the value anchor without a full installment widget on the card.
                    const installmentMonthly = (() => {
                        const price = Number(laptop.price || 0);
                        if (price <= 0) return null;
                        const total = price + price * 0.05 * (12 / 12);
                        return Math.ceil(total / 12);
                    })();
                    const installmentHint = installmentMonthly
                        ? `<div class="laptop-installment-hint">${window.__i18n?.installmentOr || 'or'} <strong class="notranslate" translate="no">${formatMoney(installmentMonthly)}</strong> ${window.__i18n?.perMonth || '/mo'}</div>`
                        : '';

                    html += `
                        <div class="laptop-card" data-laptop-id="${laptopId}">
                            <div class="match-badge">${getBadgeText(laptop)}</div>
                            <div class="laptop-image-container" onclick="openLaptopDetail(${laptopId})">
                                <img src="${imageSrc}" alt="${escapeAttr(laptop.name)}" onerror="this.src='images/products/generic-laptop.png'">
                                <div class="laptop-image-overlay">
                                    <span><i class="fas fa-eye"></i> ${window.__i18n?.viewDetails || 'View Details'}</span>
                                </div>
                            </div>

                            <div class="laptop-details">
                                <span class="laptop-brand notranslate" translate="no">${laptopBrand}</span>
                                ${aiBadgeHtml}
                                <h3 class="notranslate" translate="no" style="cursor: pointer;" onclick="openLaptopDetail(${laptopId})">${laptopName}</h3>

                                <div class="verified-spec-strip">
                                    ${getVerifiedHighlights(laptop)}
                                </div>
                                ${getReviewMeasurementsPanel(laptop, true)}
                                ${getFactScoreBars(laptop, true)}
                            </div>

                            <div class="action-panel">
                                <div class="price-box">
                                    <div class="laptop-price">${priceFormatted}</div>
                                    ${oldPriceHtml}
                                    ${installmentHint}
                                </div>

                                <div class="card-actions">
                                    <button class="btn-select" onclick="buyLaptop(${laptopId})">
                                        <i class="fas fa-cart-plus"></i>
                                        <span>${window.__i18n?.selectLaptop || 'Select Laptop'}</span>
                                    </button>

                                    <div class="card-actions-secondary">
                                        <button class="btn-quickview" onclick="openLaptopDetail(${laptopId})">
                                            <i class="fas fa-eye"></i>
                                            <span>${window.__i18n?.viewDetails || 'Details'}</span>
                                        </button>

                                        <button type="button" class="compare-toggle ${isCompared ? 'active' : ''}" data-compare-id="${laptopId}" aria-pressed="${isCompared ? 'true' : 'false'}" onclick="toggleCompare(${laptopId})">
                                            <i class="fas fa-balance-scale"></i>
                                            <span>${isCompared ? (window.__i18n?.comparing || 'Comparing') : (window.__i18n?.compare || 'Compare')}</span>
                                        </button>
                                    </div>

                                    <!-- Optimization Pack Upsell (collapsed by default, lives below the actions) -->
                                    <div class="upsell-box" onclick="toggleUpsell(this, ${laptopId})">
                                        <div class="upsell-header">
                                            <input type="checkbox" class="upsell-checkbox" id="upsell-${laptopId}" style="pointer-events: none;">
                                            <i class="fas fa-fire"></i>
                                            <span>${window.__i18n?.optimizationPack || 'Maroc Optimization Pack'}</span>
                                        </div>
                                        <div class="upsell-body">
                                            ${window.__i18n?.optimizationPackBody || 'Clean Windows install, thermal repaste, zero bloatware'} (+<span class="upsell-price">${window.formatMAD ? window.formatMAD(499) : '499 DH'}</span>).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                els.container.innerHTML = html;
                updateComparisonBar();
                initMetricTipTaps();
            }

            // Tooltip tap behavior: on touch devices there is no hover, so the (i) icon
            // needs a tap to toggle its explanation. Delegated so it survives re-renders.
            function initMetricTipTaps() {
                const container = els.container;
                if (!container) return;

                container.querySelectorAll('.metric-tip-icon').forEach(icon => {
                    icon.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const wasTapped = icon.classList.contains('tapped');
                        // Close every other open tooltip, then toggle this one.
                        container.querySelectorAll('.metric-tip-icon.tapped').forEach(other => {
                            if (other !== icon) other.classList.remove('tapped');
                        });
                        icon.classList.toggle('tapped', !wasTapped);
                    });
                });
            }

            // Close tooltips when tapping anywhere outside (delegated, once).
            if (!window.__metricTipDocBound) {
                window.__metricTipDocBound = true;
                document.addEventListener('click', (event) => {
                    if (!event.target.closest('.metric-tip-icon')) {
                        document.querySelectorAll('.metric-tip-icon.tapped').forEach(icon => icon.classList.remove('tapped'));
                    }
                }, { passive: true });
            }

            // Expose render globally for upsell checkbox clicks
            window.toggleUpsell = (box, laptopId) => {
                const chk = document.getElementById('upsell-' + laptopId);
                if (!chk) return;
                chk.checked = !chk.checked;
                if (chk.checked) {
                    box.classList.add('active');
                } else {
                    box.classList.remove('active');
                }
            };

            window.buyLaptop = (laptopId, quantity = 1) => {
                const laptop = laptops.find(l => l.id === laptopId);
                if (!laptop) return;

                const cart = window.Cart;
                if (!cart) {
                    console.error("Cart system is missing.");
                    return;
                }

                const safeQuantity = Math.max(1, Math.min(99, parseInt(quantity, 10) || 1));
                const installmentSelection = typeof window.Installment?.getSelection === 'function'
                    ? window.Installment.getSelection(`detailLaptopInstallment-${laptop.id}`)
                    : null;

                const productItem = {
                    id: 'laptop-' + laptop.id,
                    name: laptop.name,
                    brand: laptop.brand,
                    category: 'Laptops',
                    price: laptop.price,
                    image: laptop.image,
                    inStock: true,
                    installmentPlan: installmentSelection
                };

                cart.add(productItem, safeQuantity);

                const chk = document.getElementById('upsell-' + laptopId);
                if (chk && chk.checked) {
                    const packItem = {
                        id: 'laptop-opt-pack',
                        name: 'Laptop Optimization Pack (Clean Setup, Repaste, Support)',
                        brand: 'Maroc PC Services',
                        category: 'Services',
                        price: 499.00,
                        image: 'images/products/placeholder-service.svg',
                        inStock: true
                    };
                    cart.add(packItem, safeQuantity);
                }
            };

            // === Comparison Bar ===
            window.toggleCompare = (laptopId, checked = null) => {
                const isAlreadySelected = compareList.includes(laptopId);
                const shouldAdd = checked === null ? !isAlreadySelected : Boolean(checked);

                if (shouldAdd) {
                    if (isAlreadySelected) {
                        syncCompareButtons();
                        return;
                    }
                    if (compareList.length >= 3) {
                        showToastMsg(window.__i18n?.maxCompare || 'Maximum 3 laptops for comparison');
                        syncCompareButtons();
                        return;
                    }
                    compareList.push(laptopId);
                } else {
                    compareList = compareList.filter(id => id !== laptopId);
                }
                localStorage.setItem('laptopCompare', JSON.stringify(compareList));
                updateComparisonBar();
            };

            function syncCompareButtons() {
                document.querySelectorAll('.compare-toggle[data-compare-id]').forEach(btn => {
                    const id = Number(btn.getAttribute('data-compare-id'));
                    const active = compareList.includes(id);
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                    const label = btn.querySelector('span');
                    if (label) label.textContent = active ? (window.__i18n?.comparing || 'Comparing') : (window.__i18n?.compare || 'Compare');
                });
            }

            function updateComparisonBar() {
                const bar = document.getElementById('comparisonBar');
                const cards = document.getElementById('comparisonCards');
                if (!bar || !cards) return;
                bar.classList.toggle('visible', compareList.length >= 2);
                let html = '';
                compareList.forEach(id => {
                    const l = (typeof laptops !== 'undefined') ? laptops.find(x => x.id === id) : null;
                    if (!l) return;
                    html += `<div class="compare-mini-card">
                        <button class="remove-cmp" onclick="toggleCompare(${id}, false)"><i class="fas fa-times"></i></button>
                        <img src="${escapeUrl(l.image)}" alt="${escapeAttr(l.name)}" onerror="this.src='images/products/generic-laptop.png'">
                        <div><div style="font-size:0.75rem;font-weight:700;">${escapeHtml(l.name)}</div><div style="font-size:0.7rem;color:var(--muted);">${formatMoney(l.price)}</div></div>
                    </div>`;
                });
                cards.innerHTML = html;
                syncCompareButtons();
            }

            window.clearComparison = () => {
                compareList = [];
                localStorage.setItem('laptopCompare', '[]');
                syncCompareButtons();
                updateComparisonBar();
            };

            window.goToComparison = () => {
                if (compareList.length < 2) return;
                window.location.href = 'laptop-compare.php?ids=' + compareList.join(',');
            };

            function showToastMsg(msg) {
                const toast = document.getElementById('toast');
                const toastMsg = document.getElementById('toastMessage');
                if (!toast || !toastMsg) return;
                toastMsg.textContent = msg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }

            updateComparisonBar();

            // === AI Explainer Modal ===
            window.openAIExplainer = (laptopId) => {
                const laptop = laptops.find(l => l.id === laptopId);
                if (!laptop) return;
                const modal = document.getElementById('aiExplainerModal');
                const content = document.getElementById('aiExplainerContent');
                if (!modal || !content) return;

                const specs = laptop.specs || {};
                const npuTops = Number(laptop.npuTops || 0);
                const totalAi = specs['Total AI TOPS'] ? `${escapeHtml(specs['Total AI TOPS'])} total AI TOPS` : '';
                const isMiniPc = laptop.category === 'mini_pc';
                const isWorkstation = laptop.aiTier === 'workstation' || laptop.category === 'workstation';
                const isCopilot = Boolean(laptop.isCopilotPlus);
                const statusHtml = npuTops > 0
                    ? `<span style="color:var(--cyan);font-weight:700;">${npuTops.toLocaleString(undefined, { maximumFractionDigits: 0 })} NPU TOPS${totalAi ? ` / ${totalAi}` : ''}</span>`
                    : `<span style="color:var(--muted);">${window.__i18n?.noNpuData || 'No dedicated NPU data loaded'}</span>`;

                let title = window.__i18n?.aiReadyPc || 'AI-Ready PC';
                let intro = window.__i18n?.aiExplainerDefaultIntro || 'This system includes a dedicated NPU for local AI acceleration, based on the catalog specs loaded for this product.';
                let requirementTitle = window.__i18n?.aiCapability || 'AI Capability';
                let requirements = [
                    `${statusHtml}`,
                    `${escapeHtml(laptop.npuModel || window.__i18n?.npuModelNotListed || 'NPU model not listed')}`,
                    `${escapeHtml(specs.Source || window.__i18n?.catalogSourceNotListed || 'Catalog source not listed')}`
                ];
                let features = [
                    ['fa-microchip', window.__i18n?.localAiAcceleration || 'Local AI Acceleration', 'Runs compatible AI workloads on the NPU instead of only CPU/GPU.'],
                    ['fa-video', window.__i18n?.studioEffects || 'Studio Effects', 'Camera and audio effects depend on Windows and vendor driver support.'],
                    ['fa-shield-halved', window.__i18n?.onDeviceProcessing || 'On-device Processing', 'Supported AI tasks can run locally without sending the workload to cloud services.'],
                    ['fa-circle-info', window.__i18n?.notCopilotPlusShort || 'Not Copilot+', 'This model is not marked as Copilot+ in the verified catalog data.']
                ];

                if (isWorkstation) {
                    title = window.__i18n?.aiWorkstationTitle || 'AI Workstation';
                    intro = window.__i18n?.aiWorkstationIntro || 'This is classified as an AI workstation because its catalog data targets high-throughput local AI, creator, and engineering workloads.';
                    requirementTitle = window.__i18n?.verifiedWorkstationData || 'Verified Workstation Data';
                    features = [
                        ['fa-brain', window.__i18n?.largeLocalAiWorkloads || window.__i18n?.largeLocalAiWorkloads || 'Large Local AI Workloads', window.__i18n?.largeLocalAiDesc || 'Built for heavier inference, creator, and development workloads than thin Copilot+ laptops.'],
                        ['fa-memory', window.__i18n?.highMemoryCeiling || 'High Memory Ceiling', 'Workstation-class configs prioritize large RAM and storage pools.'],
                        ['fa-display', window.__i18n?.multiDisplayDeskSetup || 'Multi-display Desk Setup', 'Mini workstation systems can drive several monitors from a compact chassis.'],
                        ['fa-database', window.__i18n?.catalogBackedSpecs || 'Catalog-backed Specs', 'NPU and total AI throughput are shown only when present in the product data.']
                    ];
                } else if (isCopilot) {
                    title = isMiniPc ? (window.__i18n?.aiCopilotPlusMiniPcLabel || 'Copilot+ Mini PC') : (window.__i18n?.copilotPlusPc || 'Copilot+ PC');
                    intro = window.__i18n?.copilotPlusIntro || 'This product is marked Copilot+ because the catalog has a 40+ TOPS NPU and Copilot+ readiness data for it.';
                    requirementTitle = window.__i18n?.copilotPlusReqs || 'Copilot+ Requirements';
                    requirements = [
                        (window.__i18n?.tops40Npu || (window.__i18n?.tops40Npu || '40+ TOPS NPU')),
                        (window.__i18n?.win1124h2 || (window.__i18n?.win1124h2 || 'Windows 11 24H2+')),
                        (window.__i18n?.ram16gbMin || (window.__i18n?.ram16gbMin || '16GB RAM minimum')),
                        `${statusHtml}`
                    ];
                    features = [
                        ['fa-video', window.__i18n?.betterVideoCalls || 'Better Video Calls', 'Windows Studio Effects such as background blur and auto-framing.'],
                        ['fa-language', window.__i18n?.liveCaptions || 'Live Captions', 'Real-time captions and translation where supported by Windows.'],
                        ['fa-palette', window.__i18n?.aiCreativity || 'AI Creativity', 'Local creative AI features such as Cocreator on supported builds.'],
                        ['fa-search', window.__i18n?.semanticSearch || 'Semantic Search', 'Find files, settings, and content with natural language where supported.']
                    ];
                } else if (isMiniPc) {
                    title = window.__i18n?.aiReadyMiniPcTitle || 'AI-Ready Mini PC';
                    intro = window.__i18n?.aiReadyMiniPcIntro || 'This mini PC has AI hardware data in the catalog, but it is not marked as Copilot+.';
                    features = [
                        ['fa-compress', window.__i18n?.compactDeskSystem || 'Compact Desk System', 'Small chassis for office, kiosk, or lab setups.'],
                        ['fa-network-wired', window.__i18n?.connectivityFirst || 'Connectivity First', 'Mini PCs often prioritize LAN, Wi-Fi, and port density.'],
                        ['fa-microchip', window.__i18n?.basicNpuAcceleration || 'Basic NPU Acceleration', 'Useful for compatible local AI tasks, below Copilot+ class.'],
                        ['fa-circle-info', window.__i18n?.notCopilotPlusShort || 'Not Copilot+', 'The catalog does not mark this model as Copilot+ ready.']
                    ];
                }

                content.innerHTML = `
                    <h2 style="font-family:'Orbitron',sans-serif;font-size:1.6rem;margin:0 0 8px 0;">${escapeHtml(title)}</h2>
                    <p style="color:var(--muted);margin:0 0 20px 0;">${escapeHtml(intro)}</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
                        ${features.map(feature => `
                            <div style="background:var(--page-bg-2);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;">
                                <i class="fas ${feature[0]}" style="font-size:1.4rem;color:var(--cyan);margin-bottom:8px;"></i>
                                <h4 style="margin:0 0 4px;font-size:0.85rem;">${escapeHtml(feature[1])}</h4>
                                <p style="margin:0;font-size:0.75rem;color:var(--muted);">${escapeHtml(feature[2])}</p>
                            </div>
                        `).join('')}
                    </div>
                    <div style="background:var(--page-bg-2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;">
                        <h4 style="margin:0 0 8px;font-size:0.9rem;">${escapeHtml(requirementTitle)}</h4>
                        <ul style="margin:0;padding-left:20px;font-size:0.85rem;color:var(--muted);list-style:none;">
                            ${requirements.map(item => `<li><i class="fas fa-check" style="color:var(--cyan);margin-right:8px;"></i>${item}</li>`).join('')}
                        </ul>
                        ${specs.Source ? `<div style="margin-top:10px;font-size:0.78rem;color:var(--muted);"><strong>Source:</strong> ${escapeHtml(specs.Source)}</div>` : ''}
                    </div>
                    <button onclick="closeAIExplainer()" style="width:100%;background:var(--cyan);color:#000;border:none;padding:12px;border-radius:8px;font-weight:700;cursor:pointer;">Got it!</button>
                `;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };

            window.closeAIExplainer = () => {
                const modal = document.getElementById('aiExplainerModal');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            };

            // === Mobile Filter Drawer ===
            function initMobileDrawer() {
                const cockpit = document.querySelector('.cockpit-panel');
                const trigger = document.getElementById('mobileFilterTrigger');
                if (!cockpit || !trigger) return;

                function checkMobile() {
                    if (window.innerWidth < 768) {
                        cockpit.classList.add('mobile-drawer');
                        trigger.style.display = 'flex';
                    } else {
                        cockpit.classList.remove('mobile-drawer', 'open');
                        trigger.style.display = 'none';
                        document.body.classList.remove('drawer-open');
                    }
                }

                trigger.addEventListener('click', () => {
                    cockpit.classList.toggle('open');
                    document.body.classList.toggle('drawer-open');
                });

                window.addEventListener('resize', checkMobile);
                checkMobile();
            }
            initMobileDrawer();

            function getLaptopById(laptopId) {
                if (typeof laptops === 'undefined') return null;
                return laptops.find(l => Number(l.id) === Number(laptopId)) || null;
            }

            function getLaptopRouteId() {
                const id = parseInt(new URLSearchParams(window.location.search).get('laptop'), 10);
                return Number.isFinite(id) && id > 0 ? id : null;
            }

            function getSpecsHtml(specs) {
                if (!specs) return '';
                return Object.entries(specs).map(([key, val]) => `
                    <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                        <div class="spec-key" style="color: var(--muted); font-weight: 700;">${escapeHtml(key)}</div>
                        <div class="spec-val notranslate" translate="no" style="color: var(--white); font-weight: 500;">${escapeHtml(val)}</div>
                    </div>
                `).join('');
            }

            function createLaptopDetailMarkup(laptop) {
                const discount = laptop.oldPrice
                    ? Math.round(((laptop.oldPrice - laptop.price) / laptop.oldPrice) * 100)
                    : 0;
                const modalLaptopId = Number(laptop.id || 0);
                const laptopName = escapeHtml(laptop.name);
                const laptopBrand = escapeHtml(laptop.brand);
                const imageSrc = escapeUrl(laptop.image);
                const screenSize = Number(laptop.screenSize || 0).toLocaleString(undefined, { maximumFractionDigits: 1 });
                const batteryWh = Number(laptop.batteryWh || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
                const weightKg = Number(laptop.weightKg || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });

                return `
                    <div class="product-detail-hero">
                    <div class="product-detail-media">
                        <img src="${imageSrc}" alt="${escapeAttr(laptop.name)}" onerror="this.src='images/products/generic-laptop.png'">
                    </div>
                    <div class="product-detail-summary">
                        <div class="product-detail-kicker">${window.__i18n?.laptopsBreadcrumb || 'Laptops / '}<span class="notranslate" translate="no">${laptopBrand}</span></div>
                        <h1 class="notranslate" translate="no">${laptopName}</h1>
                        
                        <div class="product-detail-price-row">
                            <span class="product-price">${formatMoney(laptop.price)}</span>
                            ${laptop.oldPrice ? `<span class="product-old-price" style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; color: var(--muted); text-decoration: line-through;">${formatMoney(laptop.oldPrice)}</span>` : ''}
                            ${discount > 0 ? `<span class="product-discount" style="background: var(--orange); color: var(--page-bg); font-weight: 900; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-family: 'Orbitron', sans-serif;">-${discount}%</span>` : ''}
                        </div>

                        ${getVerifiedModalData(laptop)}
                        ${getReviewMeasurementsPanel(laptop)}
                        ${getFactScoreBars(laptop)}

                        <p class="description" style="font-size: 0.95rem; color: var(--muted); line-height: 1.6; margin: 0;">${window.__i18n?.catalogBackedView || 'Catalog-backed product view. Specs shown here come from stored product records, researched source fields, and same-model public review measurements where available.'}</p>
                        
                        <div class="laptop-detail-trust-grid">
                            <div class="trust-item">
                                <i class="fas fa-shield-halved"></i>
                                <span>${window.__i18n?.warranty1Year || '1 Year Maroc PC Warranty'}</span>
                            </div>
                            <div class="trust-item">
                                <i class="fab fa-whatsapp"></i>
                                <span>${window.__i18n?.whatsappExpert || 'WhatsApp Expert Advice'}</span>
                            </div>
                            <div class="trust-item">
                                <i class="fas fa-truck-fast"></i>
                                <span>${window.__i18n?.freeExpress || 'Free Express Delivery'}</span>
                            </div>
                            <div class="trust-item">
                                <i class="fas fa-arrows-spin"></i>
                                <span>${window.__i18n?.return7Day || '7-day Return Guarantee'}</span>
                            </div>
                        </div>

                        <div class="detail-quantity" data-quantity-scope="laptop-${modalLaptopId}">
                            <span class="detail-quantity-label">${window.__i18n?.quantity || 'Quantity'}</span>
                            <div class="detail-quantity-control">
                                <button type="button" class="detail-qty-btn" data-qty-action="decrease" aria-label="${window.__i18n?.decreaseQuantity || 'Decrease quantity'}">-</button>
                                <input type="number" min="1" max="99" step="1" value="1" class="detail-qty-input" data-quantity-input inputmode="numeric">
                                <button type="button" class="detail-qty-btn" data-qty-action="increase" aria-label="${window.__i18n?.increaseQuantity || 'Increase quantity'}">+</button>
                            </div>
                        </div>
                        <div class="product-detail-actions" data-quantity-scope="laptop-${modalLaptopId}">
                            <button class="btn btn-primary add-to-cart-btn detail-add-to-cart" data-id="${modalLaptopId}">
                                <i class="fas fa-cart-plus"></i> ${window.__i18n?.selectAddCart || window.__i18n?.selectLaptop || 'Select & Add to Cart'}
                            </button>
                            <button type="button" class="btn btn-outline detail-compare-toggle" data-id="${modalLaptopId}">
                                <i class="fas fa-balance-scale"></i> ${compareList.includes(modalLaptopId) ? (window.__i18n?.comparing || 'Comparing') : (window.__i18n?.compare || 'Compare')}
                            </button>
                        </div>
                        
                        ${typeof Installment !== 'undefined' ? Installment.widget(laptop.price, `detailLaptopInstallment-${modalLaptopId}`) : ''}
                    </div>
                    </div>

                    <div class="product-detail-grid">
                        <section class="product-detail-panel product-detail-specs">
                            <header>
                                <span>${window.__i18n?.diagnosticSheet || 'Diagnostic Sheet'}</span>
                                <h2>${window.__i18n?.specifications || 'Specifications'}</h2>
                            </header>
                            <div class="specs">
                            ${getSpecsHtml(laptop.specs)}
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">${window.__i18n?.screenSize || 'Screen Size'}</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${screenSize}"</div>
                            </div>
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">${window.__i18n?.batteryCapacity || 'Battery Capacity'}</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${batteryWh} Wh</div>
                            </div>
                            <div class="spec-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div class="spec-key" style="color: var(--muted); font-weight: 700;">${window.__i18n?.physicalWeight || 'Physical Weight'}</div>
                                <div class="spec-val" style="color: var(--white); font-weight: 500;">${weightKg} kg</div>
                            </div>
                            </div>
                        </section>

                        <section class="product-detail-panel">
                            <header>
                                <span>${window.__i18n?.decisionSupport || 'Decision Support'}</span>
                                <h2>${window.__i18n?.financingOptions || 'Financing Options'}</h2>
                            </header>
                            <div style="font-size:0.82rem;color:var(--muted);line-height:1.6;">
                                ${window.__i18n?.financingOptionsBody || 'Installment selection is now tied to the item you add to cart so the chosen plan can follow the order flow instead of living only in the UI.'}
                            </div>
                            <div style="font-size:0.72rem;color:var(--muted);margin-top:8px;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>${window.__i18n?.budgetThreshold || 'Based on 3,000 DH/month budget threshold'}</div>
                        </section>

                        <section class="product-detail-panel">
                            <header>
                                <span>${window.__i18n?.regretPrevention || 'Regret Prevention'}</span>
                                <h2>${window.__i18n?.beforeYouDecide || 'Before You Decide'}</h2>
                            </header>
                            <details open style="background:var(--page-bg-2);border:1px solid var(--border);border-radius:12px;padding:16px;margin:0;">
                            <summary style="cursor:pointer;font-family:'Orbitron',sans-serif;font-size:0.9rem;text-transform:uppercase;list-style:none;display:flex;align-items:center;gap:8px;">
                                <i class="fas fa-clipboard-question" style="color:var(--cyan);"></i>${window.__i18n?.beforeYouDecide || 'Before You Decide'}
                                <i class="fas fa-chevron-down" style="margin-left:auto;font-size:0.7rem;color:var(--muted);"></i>
                            </summary>
                            <div id="regretQuiz-${modalLaptopId}" style="margin-top:12px;">
                                ${(() => {
                                    const ram = (laptop.specs && laptop.specs.RAM) ? laptop.specs.RAM : '';
                                    const storage = (laptop.specs && laptop.specs.Storage) ? laptop.specs.Storage : '';
                                    const ramGB = parseInt(ram) || 16;
                                    const storageGB = parseInt(storage) || 512;
                                    const tips = [];
                                    if (ramGB <= 8) tips.push({icon:'fa-triangle-exclamation',tone:'var(--orange)',text:window.__i18n?.ramBottleneck || '8GB RAM might bottleneck in 2 years. Consider 16GB+.'});
                                    else if (ramGB >= 32) tips.push({icon:'fa-check',tone:'var(--cyan)',text:`${ramGB}GB RAM is future-proof for years to come.`});
                                    else tips.push({icon:'fa-check',tone:'var(--cyan)',text:`${ramGB}GB RAM should be sufficient for most tasks.`});
                                    if (storageGB <= 256) tips.push({icon:'fa-triangle-exclamation',tone:'var(--orange)',text:window.__i18n?.storageFillsFast || '256GB storage fills fast. Consider 512GB+.'});
                                    else tips.push({icon:'fa-check',tone:'var(--cyan)',text:`${storageGB} storage should be sufficient.`});
                                    if (laptop.gpuTier === 'integrated' && laptop.usageCategory === 'gaming') tips.push({icon:'fa-triangle-exclamation',tone:'var(--orange)',text:window.__i18n?.gpuStruggle || 'Integrated GPU may struggle with modern AAA games.'});
                                    if (laptop.batteryWh < 50) tips.push({icon:'fa-triangle-exclamation',tone:'var(--orange)',text:window.__i18n?.batterySmall || 'Small battery may limit all-day use without charger.'});
                                    else if (laptop.batteryWh >= 70) tips.push({icon:'fa-check',tone:'var(--cyan)',text:window.__i18n?.batteryLarge || 'Large battery supports all-day productivity.'});
                                    if (laptop.weightKg > 2.2) tips.push({icon:'fa-triangle-exclamation',tone:'var(--orange)',text:`At ${laptop.weightKg}kg, this is heavy for daily commuting.`});
                                    return tips.map(t => `<div style="display:flex;align-items:start;gap:8px;padding:6px 0;font-size:0.82rem;"><i class="fas ${t.icon}" style="color:${t.tone};width:18px;margin-top:2px;"></i><span style="color:var(--text);">${escapeHtml(t.text)}</span></div>`).join('');
                                })()}
                            </div>
                        </details>
                        </section>

                        <!-- Deal Alert -->
                        ${laptop.oldPrice ? `
                        <section class="product-detail-panel">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <i class="fas fa-bell" style="color:var(--cyan);font-size:1.1rem;"></i>
                                <span style="font-weight:700;font-size:0.9rem;">${window.__i18n?.priceAlertAvailable || 'Price Alert Available'}</span>
                            </div>
                            <p style="font-size:0.8rem;color:var(--muted);margin:0 0 8px 0;">${window.__i18n?.priceAlertDescription ? window.__i18n.priceAlertDescription.replace('{current}',formatMoney(laptop.price)).replace('{old}',formatMoney(laptop.oldPrice)) : 'Currently '+formatMoney(laptop.price)+' (was '+formatMoney(laptop.oldPrice)+'). Set an alert for further drops.'}</p>
                            <div class="price-alert-row">
                                <input type="email" placeholder="${window.__i18n?.yourEmail || 'your@email.com'}" id="alertEmail-${modalLaptopId}">
                                <button type="button" onclick="setPriceAlert(${modalLaptopId})">${window.__i18n?.setAlert || 'Set Alert'}</button>
                            </div>
                        </section>
                        ` : ''}

                        <section class="product-detail-panel product-detail-reviews" data-reviews-mount></section>
                    </div>
                `;
            }

            function bindLaptopDetailActions(content, laptop) {
                const quantityInput = content.querySelector('[data-quantity-input]');
                const applyQuantity = (nextValue) => {
                    if (!quantityInput) return 1;
                    const safeValue = Math.max(1, Math.min(99, parseInt(nextValue, 10) || 1));
                    quantityInput.value = String(safeValue);
                    return safeValue;
                };

                content.querySelectorAll('.detail-qty-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const delta = btn.dataset.qtyAction === 'increase' ? 1 : -1;
                        applyQuantity((parseInt(quantityInput?.value || '1', 10) || 1) + delta);
                    });
                });

                quantityInput?.addEventListener('input', () => {
                    applyQuantity(quantityInput.value);
                });

                content.querySelector('.add-to-cart-btn')?.addEventListener('click', (e) => {
                    const id = parseInt(e.currentTarget.dataset.id);
                    const quantity = applyQuantity(quantityInput?.value || '1');
                    buyLaptop(id, quantity);
                });

                content.querySelector('.detail-compare-toggle')?.addEventListener('click', () => {
                    toggleCompare(Number(laptop.id || 0));
                    const btn = content.querySelector('.detail-compare-toggle');
                    if (btn) {
                        const active = compareList.includes(Number(laptop.id || 0));
                        btn.innerHTML = `<i class="fas fa-balance-scale"></i> ${active ? (window.__i18n?.comparing || 'Comparing') : (window.__i18n?.compare || 'Compare')}`;
                    }
                });

                if (typeof Installment !== 'undefined') {
                    Installment.bind(`detailLaptopInstallment-${Number(laptop.id || 0)}`, laptop.price);
                }

                if (typeof Reviews !== 'undefined') {
                    Reviews.loadForProduct(100000 + laptop.id, content.querySelector('[data-reviews-mount]'));
                }
            }

            function renderLaptopDetail(laptop) {
                if (!els.detailPage || !els.detailContent || !els.catalog) return;
                els.catalog.setAttribute('hidden', '');
                els.detailPage.removeAttribute('hidden');
                document.getElementById('mobileFilterTrigger')?.setAttribute('hidden', '');
                els.detailContent.innerHTML = createLaptopDetailMarkup(laptop);
                document.body.style.overflow = '';
                document.title = `${laptop.name} - MarocPC`;
                bindLaptopDetailActions(els.detailContent, laptop);
            }

            function showLaptopCatalog() {
                els.detailPage?.setAttribute('hidden', '');
                els.catalog?.removeAttribute('hidden');
                document.getElementById('mobileFilterTrigger')?.removeAttribute('hidden');
                document.body.style.overflow = '';
                document.title = defaultPageTitle;
                render();
            }

            function handleLaptopRoute() {
                const laptopId = getLaptopRouteId();
                const laptop = laptopId ? getLaptopById(laptopId) : null;
                if (laptop) {
                    renderLaptopDetail(laptop);
                } else {
                    showLaptopCatalog();
                }
            }

            window.openLaptopDetail = (laptopId) => {
                const laptop = getLaptopById(laptopId);
                if (!laptop) return;
                history.pushState({ laptopId: laptop.id }, '', `${window.location.pathname}?laptop=${encodeURIComponent(laptop.id)}`);
                renderLaptopDetail(laptop);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            window.openLaptopModal = window.openLaptopDetail;

            window.setPriceAlert = (laptopId) => {
                const emailInput = document.getElementById('alertEmail-' + laptopId);
                const email = emailInput ? emailInput.value.trim() : '';
                if (!email || !email.includes('@')) {
                    showToastMsg('Please enter a valid email address');
                    return;
                }
                const laptop = laptops.find(l => l.id === laptopId);
                const targetPrice = laptop ? Math.round(laptop.price * 0.9) : 0;
                fetch('api/laptop-price-alerts.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        laptop_id: laptopId,
                        threshold: targetPrice,
                        email: email,
                        channel: 'email'
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        showToastMsg('Alert set! We\'ll notify you when price drops');
                    } else {
                        showToastMsg(data.error || data.message || 'Failed to set alert');
                    }
                }).catch(() => {
                    showToastMsg('Alert saved locally (offline mode)');
                    const alerts = JSON.parse(localStorage.getItem('priceAlerts') || '[]');
                    alerts.push({ laptop_id: laptopId, target_price: targetPrice, email: email });
                    localStorage.setItem('priceAlerts', JSON.stringify(alerts));
                });
            };

            window.closeLaptopModal = () => {
                history.pushState(null, '', window.location.pathname);
                showLaptopCatalog();
            };

            document.getElementById('laptopDetailBack')?.addEventListener('click', (event) => {
                event.preventDefault();
                closeLaptopModal();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            window.addEventListener('popstate', handleLaptopRoute);

            // Sidebar open / close
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (hamburgerBtn && sidebar && sidebarClose && sidebarOverlay) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });

                function closeSidebar() {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }

                sidebarClose.addEventListener('click', closeSidebar);
                sidebarOverlay.addEventListener('click', closeSidebar);

                document.querySelectorAll('.sidebar-toggle-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const parent = btn.closest('.sidebar-dropdown');
                        const isOpen = parent.classList.contains('open');
                        document.querySelectorAll('.sidebar-dropdown.open').forEach(d => d.classList.remove('open'));
                        if (!isOpen) parent.classList.add('open');
                    });
                });
            }

            // Init rendering
            render();
            if (getLaptopRouteId()) handleLaptopRoute();
        });
    </script>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Panel -->
    <nav class="sidebar" id="sidebar" aria-label="Mobile navigation">
        <div class="sidebar-header">
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-logo-link">
                <i class="fas fa-microchip"></i> Maroc PC
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-search">
            <input type="text" placeholder="Search components..." aria-label="Search products" />
            <button aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <li><a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-home"></i> <?php i18n_e('nav.home'); ?></a></li>
            <li><a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-box"></i> <?php i18n_e('nav.products'); ?></a></li>
            <li class="sidebar-dropdown open">
                <button class="sidebar-link sidebar-toggle-btn active" aria-expanded="true">
                    <i class="fas fa-tools"></i>
                    Builder Tools
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.pc_build_wizard'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=gaming-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.gaming_pc_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink active"><?php i18n_e('nav.laptop_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=psu-calculator'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.psu_calculator'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=memory-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.memory_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.tools_cockpit'); ?></a></li>
                </ul>
            </li>
            <li><a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-bolt"></i> <?php i18n_e('nav.deals'); ?></a></li>
            <li>
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link">
                    <i class="fas fa-shopping-cart"></i> <?php i18n_e('nav.cart'); ?>
                    <span class="sidebar-cart-badge" id="sidebarCartCount">0</span>
                </a>
            </li>
            <li><a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-envelope"></i> <?php i18n_e('nav.contact'); ?></a></li>
        </ul>
    </nav>
</body>
</html>
