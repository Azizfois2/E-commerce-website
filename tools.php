<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/i18n.php';
i18n_start_page_translation();

$currentLocale = i18n_current_locale();
$localeLabels = i18n_locale_labels();

$clientEmail = '';
$clientPhone = '';
$clientIsLoggedIn = false;

if (!empty($_SESSION['client_id'])) {
    $clientIsLoggedIn = true;
    try {
        $db = db();
        $stmt = $db->prepare("SELECT email, telephone FROM Client WHERE id_client = ?");
        $stmt->execute([(int) $_SESSION['client_id']]);
        $clientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($clientInfo) {
            $clientEmail = trim((string)($clientInfo['email'] ?? ''));
            $clientPhone = trim((string)($clientInfo['telephone'] ?? ''));
        }
    } catch (PDOException $e) {
        // Silently continue if database has issues
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars(i18n_direction($currentLocale), ENT_QUOTES, 'UTF-8') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php i18n_e('tools.page_title'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Syne:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth-nav.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <?= i18n_preference_assets() ?>
    <link rel="stylesheet" href="assets/css/light-mode-industrial.css">
    <script src="assets/js/page-transitions.js"></script>
    <style>
        :root {
            --tool-bg: #05070a;
            --tool-panel: #0a0d12;
            --tool-panel-2: #111722;
            --tool-input: #080b10;
            --tool-border: rgba(120, 255, 236, 0.16);
            --tool-text: #eef0f4;
            --tool-muted: #9aa5b5;
            --tool-cyan: #00f5d4;
            --tool-green: #00e676;
            --tool-red: #ff4444;
            --tool-amber: #ffcf4d;
            --tool-orange: #ff6b35;
            --font-mono: 'JetBrains Mono', monospace;
        }
        [data-theme="light"] {
            --tool-bg: #eef2f6;
            --tool-panel: #f8fafc;
            --tool-panel-2: #e7edf4;
            --tool-input: #fdfefe;
            --tool-border: rgba(0, 122, 110, 0.22);
            --tool-text: #101827;
            --tool-muted: #526174;
            --tool-cyan: #007a6e;
            --tool-green: #008a4f;
            --tool-red: #c62828;
            --tool-amber: #9a6500;
            --tool-orange: #d95f0a;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--tool-bg); color: var(--tool-text); font-family: 'Syne', system-ui, sans-serif; }
        .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            min-height: 76px;
            border-bottom: 1px solid rgba(120, 255, 236, 0.12);
            background: color-mix(in srgb, var(--tool-bg) 94%, transparent);
            backdrop-filter: blur(16px);
        }
        .nav-container {
            width: min(1200px, calc(100% - 32px));
            min-height: 76px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            flex: 0 0 178px;
            width: 178px;
            height: 52px;
            text-decoration: none;
            overflow: hidden;
        }
        .nav-logo {
            width: 178px;
            height: 52px;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .nav {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        .nav-link {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            padding: 0 11px;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--tool-muted);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 800;
        }
        .nav-link:hover,
        .nav-link.active {
            border-color: var(--tool-border);
            background: rgba(0, 245, 212, 0.07);
            color: var(--tool-cyan);
        }
        .nav-spacer { flex: 1; min-width: 12px; }
        .theme-toggle {
            width: 44px;
            height: 44px;
            border: 1px solid var(--tool-border);
            border-radius: 10px;
            background: var(--tool-panel-2);
            color: var(--tool-text);
            display: inline-grid;
            place-items: center;
            cursor: pointer;
        }
        .theme-toggle .icon-moon { display: none; }
        .custom-translate-container {
            position: relative;
            z-index: 60;
            display: inline-flex;
            flex-shrink: 0;
        }
        .custom-translate-btn {
            width: 44px;
            height: 44px;
            padding: 0;
            border: 1px solid var(--tool-border);
            border-radius: 10px;
            background: var(--tool-panel-2);
            color: var(--tool-cyan);
            display: inline-grid;
            place-items: center;
            font-family: var(--font-mono);
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0;
            cursor: pointer;
        }
        .custom-translate-btn:hover,
        .custom-translate-container:focus-within .custom-translate-btn {
            border-color: var(--tool-cyan);
            background: rgba(0, 245, 212, 0.08);
            color: var(--tool-text);
            outline: none;
        }
        .custom-translate-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 168px;
            padding: 6px;
            border: 1px solid var(--tool-border);
            border-radius: 10px;
            background: var(--tool-panel);
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.34);
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }
        .custom-translate-dropdown[hidden] { display: none !important; }
        .custom-translate-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .custom-translate-option {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            color: var(--tool-muted);
            text-decoration: none;
            font-weight: 800;
        }
        .custom-translate-option:hover,
        .custom-translate-option.active {
            background: rgba(0, 245, 212, 0.08);
            color: var(--tool-cyan);
        }
        .custom-translate-option .flag-icon {
            min-width: 28px;
            color: var(--tool-cyan);
            font-family: var(--font-mono);
            font-size: 0.72rem;
            font-weight: 900;
        }
        [data-theme="light"] .custom-translate-btn {
            background: var(--tool-panel);
            color: var(--tool-cyan);
        }
        [data-theme="light"] .custom-translate-dropdown {
            background: var(--tool-panel);
            box-shadow: 0 20px 42px rgba(16, 24, 39, 0.14);
        }
        [dir="rtl"] .custom-translate-dropdown {
            right: auto;
            left: 0;
        }
        .tools-shell { padding: 118px 0 72px; }
        .tools-hero { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr); gap: 24px; align-items: end; margin-bottom: 28px; }
        .tools-kicker { display: inline-flex; gap: 8px; align-items: center; color: var(--tool-cyan); font-family: var(--font-mono); font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        .tools-hero h1 { margin: 12px 0 10px; font-family: 'Orbitron', sans-serif; font-size: 2.4rem; line-height: 1.08; }
        .tools-hero p { max-width: 68ch; margin: 0; color: var(--tool-muted); line-height: 1.65; }
        .tools-status { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 14px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-panel); }
        .tools-status div { padding: 10px; border-radius: 8px; background: var(--tool-panel-2); }
        .tools-status span { display: block; color: var(--tool-muted); font-size: 0.68rem; font-family: var(--font-mono); text-transform: uppercase; }
        .tools-status strong { display: block; margin-top: 4px; color: var(--tool-text); font-family: var(--font-mono); font-size: 0.95rem; }
        .tools-start { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: -8px 0 24px; }
        .start-lane { min-height: 86px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-panel); color: var(--tool-text); display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: start; padding: 14px; text-align: left; cursor: pointer; }
        .start-lane:hover, .start-lane:focus-visible { border-color: var(--tool-cyan); background: rgba(0, 245, 212, 0.06); outline: none; }
        .start-lane i { color: var(--tool-cyan); margin-top: 3px; }
        .start-lane strong, .start-lane span { display: block; }
        .start-lane strong { font-size: 0.92rem; }
        .start-lane span { margin-top: 5px; color: var(--tool-muted); line-height: 1.45; font-size: 0.82rem; }
        .tool-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 22px; }
        .tool-nav { position: sticky; top: 92px; align-self: start; display: grid; gap: 8px; padding: 12px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-panel); }
        .tool-nav-group { margin: 6px 10px 0; color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.62rem; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; }
        .tool-nav button { min-height: 42px; border: 1px solid transparent; border-radius: 8px; background: transparent; color: var(--tool-muted); display: flex; gap: 10px; align-items: center; padding: 0 12px; cursor: pointer; font-weight: 800; text-align: left; }
        .tool-nav button:hover, .tool-nav button.active { border-color: var(--tool-border); background: rgba(0, 245, 212, 0.06); color: var(--tool-cyan); }
        .tool-panel { display: none; border: 1px solid var(--tool-border); border-radius: 12px; background: var(--tool-panel); overflow: hidden; }
        .tool-panel.active { display: block; }
        .tool-head { padding: 20px; border-bottom: 1px solid var(--tool-border); display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; }
        .tool-head h2 { margin: 0; font-size: 1.2rem; font-family: 'Orbitron', sans-serif; }
        .tool-head p { max-width: 70ch; margin: 6px 0 0; color: var(--tool-muted); line-height: 1.55; }
        .tool-body { padding: 20px; display: grid; gap: 18px; }
        .tool-form { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); column-gap: 18px; row-gap: 14px; }
        .tool-form.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tool-field { display: grid; gap: 7px; min-width: 0; }
        .tool-field span { color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.68rem; font-weight: 900; text-transform: uppercase; }
        .tool-field input, .tool-field select, .tool-field textarea { width: 100%; max-width: 100%; min-width: 0; min-height: 42px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-input); color: var(--tool-text); padding: 0 11px; font-family: 'Syne', sans-serif; }
        .tool-field textarea { min-height: 86px; padding-top: 10px; resize: vertical; }
        .tool-field input:focus, .tool-field select:focus, .tool-field textarea:focus { outline: none; border-color: var(--tool-cyan); box-shadow: 0 0 0 3px rgba(0,245,212,0.1); }
        .tool-field input[type="file"] {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            min-height: 48px;
            padding: 8px 10px;
            border: 1px dashed var(--tool-border);
            border-radius: 8px;
            background: var(--tool-input);
            color: var(--tool-text);
            font-family: 'Syne', sans-serif;
            font-size: 0.86rem;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease;
        }
        .tool-field input[type="file"]:hover,
        .tool-field input[type="file"]:focus {
            border-color: var(--tool-cyan);
            border-style: solid;
            background: rgba(0, 245, 212, 0.04);
            outline: none;
        }
        .tool-field input[type="file"]::file-selector-button {
            margin: 0 12px 0 0;
            padding: 7px 14px;
            border: 1px solid var(--tool-cyan);
            border-radius: 6px;
            background: var(--tool-cyan);
            color: #03110f;
            font-family: 'Syne', sans-serif;
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: background 0.18s ease, transform 0.18s ease;
        }
        .tool-field input[type="file"]::file-selector-button:hover {
            background: #00ffd9;
        }
        .tool-field input[type="file"]::-webkit-file-upload-button {
            margin: 0 12px 0 0;
            padding: 7px 14px;
            border: 1px solid var(--tool-cyan);
            border-radius: 6px;
            background: var(--tool-cyan);
            color: #03110f;
            font-family: 'Syne', sans-serif;
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            cursor: pointer;
        }
        .tool-field input[type="file"]::placeholder { color: var(--tool-muted); }
        [data-theme="light"] .tool-field input[type="file"]::file-selector-button { color: #eefaf8; }
        
        /* Custom File Input */
        .custom-file-input { position: relative; }
        .custom-file-input input[type="file"] { position: absolute; width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; z-index: -1; }
        .custom-file-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 48px;
            padding: 8px 10px;
            border: 1px dashed var(--tool-border);
            border-radius: 8px;
            background: var(--tool-input);
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease;
        }
        .custom-file-label:hover { border-color: var(--tool-cyan); border-style: solid; background: rgba(0, 245, 212, 0.04); }
        .file-chosen { flex: 1; color: var(--tool-muted); font-size: 0.86rem; }
        .file-chosen.has-file { color: var(--tool-text); }
        .file-button {
            padding: 7px 14px;
            border: 1px solid var(--tool-cyan);
            border-radius: 6px;
            background: var(--tool-cyan);
            color: #03110f;
            font-family: 'Syne', sans-serif;
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .custom-file-label:hover .file-button { background: #00ffd9; }
        
        .tool-action { width: fit-content; min-height: 42px; padding: 0 16px; border: 1px solid var(--tool-cyan); border-radius: 8px; background: var(--tool-cyan); color: #03110f; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .tool-action.secondary { background: transparent; color: var(--tool-cyan); }
        .tool-action:disabled, .tool-action.is-loading { opacity: 0.68; cursor: wait; transform: none; }
        .tool-result { min-height: 110px; border: 1px solid var(--tool-border); border-radius: 10px; background: var(--tool-input); padding: 16px; color: var(--tool-muted); }
        .empty-state { display: grid; gap: 8px; align-content: start; min-height: 86px; }
        .empty-state strong { color: var(--tool-text); }
        .empty-state span { color: var(--tool-muted); line-height: 1.5; }
        .empty-state i { color: var(--tool-cyan); }
        .result-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .readout { border: 1px solid var(--tool-border); border-radius: 8px; padding: 12px; background: var(--tool-panel-2); }
        .readout span { display: block; color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.66rem; text-transform: uppercase; }
        .readout strong { display: block; margin-top: 5px; color: var(--tool-text); font-size: 1rem; }
        .readout.good strong { color: var(--tool-green); }
        .readout.warn strong { color: var(--tool-amber); }
        .readout.bad strong { color: var(--tool-red); }
        .product-row { display: grid; grid-template-columns: 56px minmax(0, 1fr) auto auto; gap: 12px; align-items: center; padding: 10px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-panel-2); }
        .product-row + .product-row { margin-top: 8px; }
        .product-row img { width: 56px; height: 46px; object-fit: contain; border-radius: 6px; background: var(--tool-input); }
        .product-row strong { display: block; color: var(--tool-text); }
        .product-row span { color: var(--tool-muted); font-size: 0.8rem; }
        .product-row em { color: var(--tool-cyan); font-family: var(--font-mono); font-style: normal; font-weight: 900; white-space: nowrap; }
        .product-cart-btn { width: 38px; height: 38px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-input); color: var(--tool-cyan); cursor: pointer; display: inline-grid; place-items: center; }
        .product-cart-btn:hover, .product-cart-btn:focus-visible { border-color: var(--tool-cyan); background: rgba(0,245,212,0.08); outline: none; }
        .bundle-actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 12px 0; }
        .status-line { display: flex; gap: 8px; align-items: center; margin-top: 10px; font-family: var(--font-mono); font-size: 0.75rem; }
        .status-line.good { color: var(--tool-green); }
        .status-line.warn { color: var(--tool-amber); }
        .status-line.bad { color: var(--tool-red); }
        .request-picker { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .request-picker button { min-height: 44px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-input); color: var(--tool-muted); cursor: pointer; font-weight: 900; text-align: left; padding: 0 12px; }
        .request-picker button:hover, .request-picker button.active { border-color: var(--tool-cyan); color: var(--tool-cyan); background: rgba(0,245,212,0.06); }
        .request-stack { display: grid; gap: 14px; }
        .request-card { border: 1px solid var(--tool-border); border-radius: 10px; padding: 14px; background: var(--tool-input); display: grid; gap: 10px; }
        .request-card[hidden] { display: none; }
        [data-theme="light"] .tool-action { color: #eefaf8; }
        [data-theme="light"] .tool-action.secondary { color: var(--tool-cyan); background: transparent; }
        [data-theme="light"] .theme-toggle { background: var(--tool-panel); }
        .request-card h3 { margin: 0; font-size: 0.95rem; }
        .radar-list { display: grid; gap: 8px; }
        .radar-item { display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 10px 12px; border: 1px solid var(--tool-border); border-radius: 8px; background: var(--tool-panel-2); }
        .radar-item strong { color: var(--tool-text); }
        .radar-item span { color: var(--tool-muted); font-family: var(--font-mono); font-size: 0.72rem; }
        @media (max-width: 920px) {
            .nav-container { align-items: flex-start; padding: 12px 0; }
            .nav { gap: 2px; }
            .nav-link { min-height: 34px; font-size: 0.78rem; padding: 0 8px; }
            .tools-shell { padding-top: 148px; }
            .tools-hero, .tools-start, .tool-grid, .tool-form, .tool-form.two, .request-picker, .request-stack { grid-template-columns: 1fr; }
            .tool-nav { position: static; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tool-nav-group { grid-column: 1 / -1; }
            .result-grid, .tools-status { grid-template-columns: 1fr; }
            .product-row { grid-template-columns: 56px minmax(0, 1fr) auto; }
            .product-row em { grid-column: 2 / 3; }
        }
        /* --- Builder Tools Dropdown --- */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }
        .nav-dropdown .dropdown-toggle {
            background: transparent;
            border: 1px solid transparent;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 800;
            color: var(--tool-muted);
            min-height: 38px;
            padding: 0 11px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .nav-dropdown:hover .dropdown-toggle,
        .nav-dropdown .dropdown-toggle:hover,
        .nav-dropdown .dropdown-toggle.active {
            border-color: var(--tool-border);
            background: rgba(0, 245, 212, 0.07);
            color: var(--tool-cyan);
        }
        .nav-dropdown .dropdown-menu {
            display: block;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 220px;
            background: var(--tool-panel);
            border: 1px solid var(--tool-border);
            border-radius: 8px;
            padding: 8px 0;
            z-index: 1000;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .nav-dropdown:hover .dropdown-menu,
        .nav-dropdown:focus-within .dropdown-menu,
        .nav-dropdown.open .dropdown-menu {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
        }
        .nav-dropdown .dropdown-item {
            display: block;
            padding: 10px 18px;
            color: var(--tool-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 800;
            transition: all 0.2s ease;
        }
        .nav-dropdown .dropdown-item:hover {
            color: var(--tool-cyan);
            background: var(--tool-panel-2);
        }
        @media (max-width: 920px) {
            .nav-dropdown { display: inline-block; }
            .nav-dropdown .dropdown-menu {
                left: auto;
                right: 0;
                max-width: min(280px, calc(100vw - 32px));
            }
        }

        /* --- Mobile Navigation Drawer (Hamburger + Sidebar) --- */
        .hamburger-btn {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            width: 45px;
            height: 45px;
            background: var(--tool-panel);
            border: 1px solid var(--tool-border);
            border-radius: 12px;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .hamburger-btn span {
            display: block;
            width: 20px;
            height: 2px;
            background: var(--tool-text);
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .hamburger-btn:hover {
            background: var(--tool-cyan);
            border-color: var(--tool-cyan);
        }
        .hamburger-btn:hover span {
            background: #000;
        }
        @media (max-width: 1200px) {
            .hamburger-btn { display: flex; }
            .header .nav { display: none !important; }
        }
        
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            z-index: 1999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--tool-panel);
            border-right: 1px solid var(--tool-border);
            z-index: 2000;
            transform: translateX(-100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            scrollbar-width: thin;
            scrollbar-color: var(--tool-border) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb {
            background: var(--tool-border);
            border-radius: 2px;
        }
        .sidebar.open {
            transform: translateX(0);
            box-shadow: 8px 0 40px rgba(0, 0, 0, 0.6), 2px 0 0 var(--tool-border);
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            min-height: 70px;
            border-bottom: 1px solid var(--tool-border);
            flex-shrink: 0;
            background: var(--tool-panel);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .sidebar-logo-link {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--tool-cyan) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }
        .sidebar-logo-link i { font-size: 1.1rem; }
        .sidebar-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--tool-panel-2);
            border: 1px solid var(--tool-border);
            border-radius: 8px;
            color: var(--tool-text);
            cursor: pointer;
            font-size: 15px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .sidebar-close:hover {
            background: var(--tool-red);
            border-color: var(--tool-red);
            color: #fff;
        }
        .sidebar-search {
            display: flex;
            gap: 8px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--tool-border);
        }
        .sidebar-search input {
            flex: 1;
            height: 40px;
            background: var(--tool-input);
            border: 1px solid var(--tool-border);
            border-radius: 8px;
            padding: 0 14px;
            color: var(--tool-text);
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem;
            outline: none;
        }
        .sidebar-search input::placeholder { color: var(--tool-muted); }
        .sidebar-search input:focus { border-color: var(--tool-cyan); }
        .sidebar-search button {
            width: 40px;
            height: 40px;
            background: var(--tool-cyan);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .sidebar-search button:hover { background: var(--tool-cyan); opacity: 0.85; }
        .sidebar-nav {
            list-style: none;
            padding: 12px 0;
            margin: 0;
            flex: 1;
        }
        .sidebar-nav li { margin: 0; }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 13px 20px;
            color: var(--tool-text);
            font-family: 'Syne', sans-serif;
            font-size: 0.925rem;
            font-weight: 600;
            text-decoration: none;
            background: none;
            border: none;
            border-left: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            white-space: nowrap;
        }
        .sidebar-link i:first-child {
            color: var(--tool-cyan);
            width: 18px;
            font-size: 15px;
            flex-shrink: 0;
            text-align: center;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: var(--tool-text);
            background: var(--tool-panel-2);
            border-left-color: var(--tool-cyan);
        }
        .sidebar-divider {
            height: 1px;
            background: var(--tool-border);
            margin: 8px 20px;
        }
        .sidebar-cart-badge {
            margin-left: auto;
            background: var(--tool-red);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            border: 2px solid var(--tool-panel);
            flex-shrink: 0;
        }
        .chevron {
            margin-left: auto; 
            font-size: 12px;
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }
        .sidebar-dropdown.open .chevron {
            transform: rotate(180deg);
        }
        .sidebar-submenu {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0, 0, 0, 0.15);
        }
        .sidebar-dropdown.open .sidebar-submenu {
            max-height: 400px; 
        }
        .sidebar-sublink {
            display: block;
            padding: 10px 20px 10px 50px; 
            color: var(--tool-muted);
            font-family: 'Syne', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        .sidebar-sublink:hover {
            color: var(--tool-text);
            border-left-color: var(--tool-cyan);
            background: var(--tool-panel-2);
        }

        /* ============================================
           TOAST NOTIFICATIONS
           ============================================ */
        .tools-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .tools-toast {
            position: relative;
            right: auto;
            bottom: auto;
            transform: none;
            opacity: 1;
            min-width: 320px;
            max-width: 420px;
            padding: 16px 20px;
            background: var(--tool-panel);
            border: 1px solid var(--tool-border);
            border-left: 4px solid var(--tool-cyan);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            display: flex;
            align-items: start;
            gap: 12px;
            pointer-events: all;
            animation: toastSlide 0.3s ease-out;
        }
        .tools-toast.success { border-left-color: var(--tool-green); }
        .tools-toast.error { border-left-color: var(--tool-red); }
        .tools-toast.warning { border-left-color: var(--tool-amber); }
        .tools-toast.info { border-left-color: var(--tool-cyan); }
        .toast-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
        }
        .tools-toast.success .toast-icon { color: var(--tool-green); }
        .tools-toast.error .toast-icon { color: var(--tool-red); }
        .tools-toast.warning .toast-icon { color: var(--tool-amber); }
        .tools-toast.info .toast-icon { color: var(--tool-cyan); }
        .toast-content {
            flex: 1;
            min-width: 0;
        }
        .toast-title {
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--tool-text);
            margin-bottom: 4px;
        }
        .toast-message {
            font-size: 0.82rem;
            color: var(--tool-muted);
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .toast-close {
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: var(--tool-muted);
            cursor: pointer;
            border-radius: 4px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .toast-close:hover {
            background: var(--tool-panel-2);
            color: var(--tool-text);
        }
        @keyframes toastSlide {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .tools-toast.removing {
            animation: toastSlideOut 0.3s ease-out forwards;
        }
        @keyframes toastSlideOut {
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        /* ============================================
           LOADING STATES & SKELETONS
           ============================================ */
        .skeleton {
            background: linear-gradient(90deg, var(--tool-panel-2) 25%, var(--tool-border) 50%, var(--tool-panel-2) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 8px;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-text {
            height: 14px;
            margin: 8px 0;
        }
        .skeleton-title {
            height: 20px;
            width: 60%;
            margin: 12px 0;
        }
        .skeleton-card {
            height: 120px;
            border-radius: 10px;
        }
        .tool-panel.active {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .is-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }
        .is-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 3px solid var(--tool-border);
            border-top-color: var(--tool-cyan);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           PROGRESS BARS
           ============================================ */
        .progress-bar {
            height: 4px;
            background: var(--tool-border);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 12px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--tool-cyan), var(--tool-green));
            background-size: 200% 100%;
            transition: width 0.3s ease;
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* ============================================
           ENHANCED EMPTY STATES
           ============================================ */
        .empty-state-enhanced {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 48px 24px;
            text-align: center;
        }
        .empty-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(0,245,212,0.08);
            display: grid;
            place-items: center;
            margin-bottom: 8px;
        }
        .empty-icon i {
            font-size: 32px;
            color: var(--tool-cyan);
            opacity: 0.5;
        }

        /* ============================================
           TOOLTIPS
           ============================================ */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }
        [data-tooltip]::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) scale(0.9);
            padding: 8px 12px;
            background: var(--tool-panel-2);
            border: 1px solid var(--tool-border);
            border-radius: 6px;
            color: var(--tool-text);
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease;
            z-index: 1000;
        }
        [data-tooltip]:hover::before {
            opacity: 1;
            transform: translateX(-50%) scale(1);
        }

        /* ============================================
           HELP BUTTON
           ============================================ */
        .help-trigger {
            width: 34px !important;
            height: 34px !important;
            border: 1px solid var(--tool-border);
            border-radius: 8px;
            background: var(--tool-panel-2);
            color: var(--tool-cyan);
            cursor: pointer;
            display: inline-grid;
            place-items: center;
            transition: all 0.2s;
            flex-shrink: 0;
            font-size: 14px;
            margin-top: 2px;
            padding: 0;
            min-width: 34px;
            max-width: 34px;
            min-height: 34px;
            max-height: 34px;
        }
        .help-trigger:hover, .help-trigger:focus-visible {
            background: var(--tool-cyan);
            color: #000;
            border-color: var(--tool-cyan);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 245, 212, 0.14);
        }
        .help-trigger i {
            font-size: 13px !important;
            line-height: 1;
            pointer-events: none;
        }
        .help-trigger[data-tooltip]::before {
            top: 50%;
            right: calc(100% + 10px);
            bottom: auto;
            left: auto;
            transform: translateY(-50%) scale(0.96);
            transform-origin: right center;
        }
        .help-trigger[data-tooltip]:hover::before,
        .help-trigger[data-tooltip]:focus-visible::before {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        /* ============================================
           KEYBOARD SHORTCUT HINTS
           ============================================ */
        .kbd {
            display: inline-block;
            padding: 2px 6px;
            background: var(--tool-panel-2);
            border: 1px solid var(--tool-border);
            border-radius: 4px;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--tool-muted);
        }
        .keyboard-shortcuts-info {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 16px 0 24px;
            flex-wrap: wrap;
            font-size: 0.8rem;
            color: var(--tool-muted);
        }

        @media (max-width: 768px) {
            .keyboard-shortcuts-info { display: none; }
            .container { width: min(100% - 24px, 1200px); }
            .tools-shell { padding-top: 112px; }
            .tool-grid { gap: 14px; }
            .tool-panel { border-radius: 10px; }
            .tool-head { padding: 16px; gap: 12px; }
            .tool-head h2 { font-size: 1rem; line-height: 1.25; }
            .tool-head p { font-size: 0.86rem; line-height: 1.45; }
            .tool-body { padding: 14px; gap: 14px; overflow: hidden; }
            .tool-form, .tool-form.two { grid-template-columns: minmax(0, 1fr); gap: 12px; width: 100%; }
            .tool-field { width: 100%; }
            .tools-toast-container {
                bottom: 16px;
                right: 16px;
                left: 16px;
            }
            .tools-toast {
                min-width: 0;
                max-width: 100%;
            }
        }

        @media (max-width: 430px) {
            .container { width: min(100% - 18px, 1200px); }
            .tool-head { padding: 14px; }
            .tool-body { padding: 12px; }
            .tool-field input, .tool-field select, .tool-field textarea { min-height: 40px; padding-inline: 10px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="logo"><img src="logo.png" alt="Maroc PC Logo" class="nav-logo"></a>
            <nav class="nav">
                <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.home'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.components'); ?></a>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle active" aria-haspopup="true" aria-expanded="false">
                        <?php i18n_e('nav.builder_tools'); ?> <span class="chevron">▾</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.pc_build_wizard'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=gaming-finder'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.gaming_pc_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.laptop_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=psu-calculator'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.psu_calculator'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('builder.php?tab=memory-finder'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.memory_finder'); ?></a>
                        <a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item"><?php i18n_e('nav.tools_cockpit'); ?></a>
                    </div>
                </div>
                <a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.deals'); ?></a>
                <a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link"><?php i18n_e('nav.contact'); ?></a>
            </nav>
            <div class="nav-spacer"></div>
            <div class="custom-translate-container nav-translate" aria-label="<?php i18n_e('nav.select_language'); ?>">
                <button class="custom-translate-btn notranslate" type="button" aria-label="<?php i18n_e('nav.select_language'); ?>" aria-haspopup="true" aria-expanded="false" data-language-toggle>
                    <?= htmlspecialchars(strtoupper($currentLocale), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="custom-translate-dropdown" hidden data-language-menu>
                    <?php foreach ($localeLabels as $locale => $label): ?>
                        <a class="custom-translate-option notranslate<?= $locale === $currentLocale ? ' active' : '' ?>" href="<?= htmlspecialchars(i18n_current_url_for($locale), ENT_QUOTES, 'UTF-8') ?>" lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="flag-icon"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="lang-name"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme"><i class="fas fa-sun icon-sun"></i><i class="fas fa-moon icon-moon"></i></button>
        </div>
    </header>

    <script>
        (function () {
            const initLanguageMenu = () => {
                document.querySelectorAll('[data-language-toggle]').forEach((button) => {
                    const container = button.closest('.custom-translate-container');
                    const menu = container ? container.querySelector('[data-language-menu]') : null;
                    if (!menu || button.dataset.languageReady === '1') return;
                    button.dataset.languageReady = '1';

                    const close = () => {
                        menu.hidden = true;
                        menu.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    };

                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const open = menu.hidden;
                        menu.hidden = !open;
                        menu.classList.toggle('show', open);
                        button.setAttribute('aria-expanded', String(open));
                    });
                    menu.addEventListener('click', (event) => event.stopPropagation());
                    document.addEventListener('click', close);
                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') close();
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLanguageMenu);
            } else {
                initLanguageMenu();
            }
        })();
    </script>

    <main class="tools-shell">
        <div class="container">
            <section class="tools-hero">
                <div>
                    <span class="tools-kicker"><i class="fas fa-gauge-high"></i> <?php i18n_e('tools.diagnostic_tools', [], 'Diagnostic tools'); ?></span>
                    <h1><?php i18n_e('tools.tools_cockpit_title', [], 'Maroc PC Tools Cockpit'); ?></h1>
                    <p><?php i18n_e('tools.tools_cockpit_desc', [], 'Choose the problem first, then run the diagnostic. The cockpit is grouped for build checks, buying decisions, and service requests.'); ?></p>
                </div>
                <aside class="tools-status" id="toolsStatus">
                    <div><span><?php i18n_e('tools.catalog_label', [], 'Catalog'); ?></span><strong id="statusProducts">--</strong></div>
                    <div><span><?php i18n_e('tools.in_stock_label', [], 'In stock'); ?></span><strong id="statusStock">--</strong></div>
                    <div><span><?php i18n_e('tools.stock_pressure_label', [], 'Stock pressure'); ?></span><strong id="statusAlerts"><?php i18n_e('tools.scanning', [], 'Scanning'); ?></strong></div>
                </aside>
            </section>

            <section class="tools-start" aria-label="<?php i18n_e('tools.start_by_task', [], 'Start by task'); ?>">
                <button class="start-lane" type="button" data-tool-jump="compat">
                    <i class="fas fa-screwdriver-wrench"></i>
                    <span><strong><?php i18n_e('tools.check_a_build', [], 'Check a build'); ?></strong><span><?php i18n_e('tools.check_a_build_desc', [], 'Validate compatibility, power headroom, pairings, and future runway.'); ?></span></span>
                </button>
                <button class="start-lane" type="button" data-tool-jump="deal">
                    <i class="fas fa-cart-shopping"></i>
                    <span><strong><?php i18n_e('tools.decide_what_to_buy', [], 'Decide what to buy'); ?></strong><span><?php i18n_e('tools.decide_what_to_buy_desc', [], 'Scan deals, estimate performance, or generate a student/gift bundle.'); ?></span></span>
                </button>
                <button class="start-lane" type="button" data-tool-jump="requests">
                    <i class="fas fa-inbox"></i>
                    <span><strong><?php i18n_e('tools.send_a_request', [], 'Send a request'); ?></strong><span><?php i18n_e('tools.send_a_request_desc', [], 'Submit a build, trade-in, receipt, referral, or price-match workflow.'); ?></span></span>
                </button>
            </section>

            <!-- Keyboard Shortcuts Info -->
            <div class="keyboard-shortcuts-info">
                <span><kbd class="kbd">Ctrl</kbd> + <kbd class="kbd">K</kbd> <?php i18n_e('tools.search_shortcut', [], 'Search'); ?></span>
                <span><kbd class="kbd">1</kbd>-<kbd class="kbd">9</kbd> <?php i18n_e('tools.switch_tools_shortcut', [], 'Switch Tools'); ?></span>
                <span><kbd class="kbd">Esc</kbd> <?php i18n_e('tools.close_sidebar_shortcut', [], 'Close Sidebar'); ?></span>
            </div>

            <div class="tool-grid">
                <nav class="tool-nav" aria-label="<?php i18n_e('tools.tool_sections', [], 'Tool sections'); ?>">
                    <span class="tool-nav-group"><?php i18n_e('tools.validation'); ?></span>
                    <button class="active" data-tool="compat"><i class="fas fa-puzzle-piece"></i> <?php i18n_e('tools.compatibility_check'); ?></button>
                    <button data-tool="pair"><i class="fas fa-link"></i> <?php i18n_e('tools.cpu_gpu_pairing'); ?></button>
                    <button data-tool="future"><i class="fas fa-shield-halved"></i> <?php i18n_e('tools.future_proof'); ?></button>
                    <span class="tool-nav-group"><?php i18n_e('tools.upgrade'); ?></span>
                    <button data-tool="upgrade"><i class="fas fa-arrow-up-right-dots"></i> <?php i18n_e('tools.upgrade_my_pc'); ?></button>
                    <button data-tool="deal"><i class="fas fa-scale-balanced"></i> <?php i18n_e('tools.is_good_deal'); ?></button>
                    <button data-tool="bench"><i class="fas fa-chart-line"></i> <?php i18n_e('tools.benchmark'); ?></button>
                    <button data-tool="radar"><i class="fas fa-satellite-dish"></i> <?php i18n_e('tools.stock_radar'); ?></button>
                    <button data-tool="student"><i class="fas fa-graduation-cap"></i> <?php i18n_e('tools.student_bundle'); ?></button>
                    <span class="tool-nav-group"><?php i18n_e('tools.requests'); ?></span>
                    <button data-tool="requests"><i class="fas fa-inbox"></i> <?php i18n_e('tools.request_console'); ?></button>
                </nav>

                <section class="tool-panel active" id="tool-compat">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.compatibility_check_title'); ?></h2>
                            <p><?php i18n_e('tools.compatibility_check_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_compatibility'); ?>" data-help-message="<?php i18n_e('tools.help_compatibility_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span><?php i18n_e('tools.cpu'); ?></span><select id="compatCpu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.motherboard'); ?></span><select id="compatBoard"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.ram'); ?></span><select id="compatRam"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.gpu'); ?></span><select id="compatGpu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.psu'); ?></span><select id="compatPsu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.cooling'); ?></span><select id="compatCooling"></select></label>
                        </div>
                        <button class="tool-action" data-run="compat"><i class="fas fa-play"></i> <?php i18n_e('tools.run_check'); ?></button>
                        <div class="tool-result" id="compatResult">
                            <div class="empty-state"><i class="fas fa-puzzle-piece"></i><strong><?php i18n_e('tools.ready_to_validate'); ?></strong><span><?php i18n_e('tools.ready_to_validate_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-upgrade">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.upgrade_my_pc_title'); ?></h2>
                            <p><?php i18n_e('tools.upgrade_my_pc_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_upgrade'); ?>" data-help-message="<?php i18n_e('tools.help_upgrade_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span><?php i18n_e('tools.current_cpu'); ?></span><input id="upgradeCpu" placeholder="i5-13600K, Ryzen 5 5600..."></label>
                            <label class="tool-field"><span><?php i18n_e('tools.current_gpu'); ?></span><input id="upgradeGpu" placeholder="GTX 1060, RTX 4060..."></label>
                            <label class="tool-field"><span><?php i18n_e('tools.budget_dh'); ?></span><input id="upgradeBudget" type="number" value="3500"></label>
                        </div>
                        <button class="tool-action" data-run="upgrade"><i class="fas fa-magnifying-glass"></i> <?php i18n_e('tools.find_upgrade'); ?></button>
                        <div class="tool-result" id="upgradeResult">
                            <div class="empty-state"><i class="fas fa-arrow-up-right-dots"></i><strong><?php i18n_e('tools.describe_bottleneck'); ?></strong><span><?php i18n_e('tools.describe_bottleneck_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-deal">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.good_deal_title'); ?></h2>
                            <p><?php i18n_e('tools.good_deal_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_deal'); ?>" data-help-message="<?php i18n_e('tools.help_deal_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span><?php i18n_e('tools.component_name'); ?></span><input id="dealName" placeholder="RTX 4070 Ti, Ryzen 7..."></label>
                            <label class="tool-field"><span><?php i18n_e('tools.seen_price_dh'); ?></span><input id="dealPrice" type="number" value="3000"></label>
                            <label class="tool-field"><span><?php i18n_e('tools.listing_url'); ?></span><input id="dealUrl" placeholder="https://..."></label>
                        </div>
                        <button class="tool-action" data-run="deal"><i class="fas fa-scale-balanced"></i> <?php i18n_e('tools.scan_deal'); ?></button>
                        <div class="tool-result" id="dealResult">
                            <div class="empty-state"><i class="fas fa-scale-balanced"></i><strong><?php i18n_e('tools.check_before_buying'); ?></strong><span><?php i18n_e('tools.check_before_buying_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-bench">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.benchmark_db_title'); ?></h2>
                            <p><?php i18n_e('tools.benchmark_db_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_benchmark'); ?>" data-help-message="<?php i18n_e('tools.help_benchmark_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form two">
                            <label class="tool-field"><span><?php i18n_e('tools.cpu'); ?></span><select id="benchCpu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.gpu'); ?></span><select id="benchGpu"></select></label>
                        </div>
                        <button class="tool-action" data-run="bench"><i class="fas fa-chart-simple"></i> <?php i18n_e('tools.estimate'); ?></button>
                        <div class="tool-result" id="benchResult">
                            <div class="empty-state"><i class="fas fa-chart-line"></i><strong><?php i18n_e('tools.estimate_performance'); ?></strong><span><?php i18n_e('tools.estimate_performance_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-pair">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.pairing_title'); ?></h2>
                            <p><?php i18n_e('tools.pairing_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_pairing'); ?>" data-help-message="<?php i18n_e('tools.help_pairing_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form two">
                            <label class="tool-field"><span><?php i18n_e('tools.known_part'); ?></span><select id="pairPart"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.pairing_direction'); ?></span><select id="pairDirection"><option value="auto"><?php i18n_e('tools.auto_detect'); ?></option><option value="cpu"><?php i18n_e('tools.recommend_cpu'); ?></option><option value="gpu"><?php i18n_e('tools.recommend_gpu'); ?></option></select></label>
                        </div>
                        <button class="tool-action" data-run="pair"><i class="fas fa-link"></i> <?php i18n_e('tools.recommend_pairings'); ?></button>
                        <div class="tool-result" id="pairResult">
                            <div class="empty-state"><i class="fas fa-link"></i><strong><?php i18n_e('tools.start_known_part'); ?></strong><span><?php i18n_e('tools.start_known_part_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-future">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.future_proof_title'); ?></h2>
                            <p><?php i18n_e('tools.future_proof_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_future_proof'); ?>" data-help-message="<?php i18n_e('tools.help_future_proof_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span><?php i18n_e('tools.cpu'); ?></span><select id="futureCpu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.gpu'); ?></span><select id="futureGpu"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.horizon'); ?></span><select id="futureYears"><option><?php i18n_e('tools.2_years'); ?></option><option selected><?php i18n_e('tools.3_years'); ?></option><option><?php i18n_e('tools.5_years'); ?></option></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.motherboard'); ?></span><select id="futureBoard"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.ram'); ?></span><select id="futureRam"></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.psu'); ?></span><select id="futurePsu"></select></label>
                        </div>
                        <button class="tool-action" data-run="future"><i class="fas fa-shield"></i> <?php i18n_e('tools.score_build'); ?></button>
                        <div class="tool-result" id="futureResult">
                            <div class="empty-state"><i class="fas fa-shield-halved"></i><strong><?php i18n_e('tools.score_upgrade_runway'); ?></strong><span><?php i18n_e('tools.score_upgrade_runway_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-radar">
                    <div class="tool-head"><div><h2><?php i18n_e('tools.radar_title'); ?></h2><p><?php i18n_e('tools.radar_desc'); ?></p></div><button class="tool-action secondary" data-run="radar"><i class="fas fa-rotate"></i> <?php i18n_e('tools.refresh'); ?></button></div>
                    <div class="tool-body"><div class="tool-result radar-list" id="radarResult">
                        <div class="empty-state-enhanced">
                            <div class="empty-icon">
                                <i class="fas fa-satellite-dish"></i>
                            </div>
                            <strong><?php i18n_e('tools.scanning_stock'); ?></strong>
                            <span><?php i18n_e('tools.scanning_stock_desc'); ?></span>
                            <div class="progress-bar" style="width: 200px; margin-top: 16px;">
                                <div class="progress-fill" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div></div>
                </section>

                <section class="tool-panel" id="tool-student">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.student_bundle_title'); ?></h2>
                            <p><?php i18n_e('tools.student_bundle_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_student_bundle'); ?>" data-help-message="<?php i18n_e('tools.help_student_bundle_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="tool-form">
                            <label class="tool-field"><span><?php i18n_e('tools.budget_dh'); ?></span><input id="bundleBudget" type="number" value="8000"></label>
                            <label class="tool-field"><span><?php i18n_e('tools.use_case'); ?></span><select id="bundleUse"><option value="student"><?php i18n_e('tools.engineering_student'); ?></option><option value="general"><?php i18n_e('tools.general_studies'); ?></option><option value="design"><?php i18n_e('tools.design_student'); ?></option><option value="gaming"><?php i18n_e('tools.gaming_gift'); ?></option></select></label>
                            <label class="tool-field"><span><?php i18n_e('tools.mode'); ?></span><select id="bundleMode"><option value="student"><?php i18n_e('tools.student'); ?></option><option value="gift"><?php i18n_e('tools.build_for_friend'); ?></option></select></label>
                        </div>
                        <button class="tool-action" data-run="bundle"><i class="fas fa-wand-magic-sparkles"></i> <?php i18n_e('tools.generate_bundle'); ?></button>
                        <div class="tool-result" id="bundleResult">
                            <div class="empty-state"><i class="fas fa-graduation-cap"></i><strong><?php i18n_e('tools.budget_aware_bundle'); ?></strong><span><?php i18n_e('tools.budget_aware_bundle_desc'); ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="tool-panel" id="tool-requests">
                    <div class="tool-head">
                        <div>
                            <h2><?php i18n_e('tools.request_console_title'); ?></h2>
                            <p><?php i18n_e('tools.request_console_desc'); ?></p>
                        </div>
                        <button type="button" class="help-trigger" data-tooltip="<?php i18n_e('tools.help_requests'); ?>" data-help-message="<?php i18n_e('tools.help_requests_msg'); ?>">
                            <i class="fas fa-question"></i>
                        </button>
                    </div>
                    <div class="tool-body">
                        <div class="request-picker" role="tablist" aria-label="Request workflows">
                            <button class="active" type="button" role="tab" aria-selected="true" data-request-view="community"><?php i18n_e('tools.build_showcase'); ?></button>
                            <button type="button" role="tab" aria-selected="false" data-request-view="trade"><?php i18n_e('tools.trade_in'); ?></button>
                            <button type="button" role="tab" aria-selected="false" data-request-view="repair"><?php i18n_e('tools.repair_service'); ?></button>
                            <button type="button" role="tab" aria-selected="false" data-request-view="referral"><?php i18n_e('tools.referral'); ?></button>
                        </div>
                        <div class="request-stack">
                            <form class="request-card" data-request="community" data-request-panel="community"><h3><?php i18n_e('tools.community_showcase_title'); ?></h3><label class="tool-field"><span><?php i18n_e('tools.build_name'); ?></span><input name="build_name" required></label><label class="tool-field"><span><?php i18n_e('tools.caption'); ?></span><textarea name="caption"></textarea></label><label class="tool-field"><span><?php i18n_e('tools.image_url'); ?></span><input name="image_url"></label><button class="tool-action" type="submit"><?php i18n_e('tools.submit_build'); ?></button><div class="status-line"></div></form>
                            <form class="request-card" data-request="trade" data-request-panel="trade" hidden>
                                <h3><?php i18n_e('tools.trade_in_title'); ?></h3>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.hardware_type'); ?></span>
                                    <select name="hardware_type">
                                        <option value="gpu"><?php i18n_e('tools.hardware_type_gpu', [], 'GPU'); ?></option>
                                        <option value="cpu"><?php i18n_e('tools.hardware_type_cpu', [], 'CPU'); ?></option>
                                        <option value="ram"><?php i18n_e('tools.hardware_type_ram', [], 'RAM'); ?></option>
                                        <option value="storage"><?php i18n_e('tools.hardware_type_storage', [], 'Storage'); ?></option>
                                        <option value="motherboard"><?php i18n_e('tools.hardware_type_motherboard', [], 'Motherboard'); ?></option>
                                        <option value="laptop"><?php i18n_e('tools.hardware_type_laptop', [], 'Laptop'); ?></option>
                                    </select>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.hardware_name'); ?></span>
                                    <input name="hardware_name" required>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.condition_grade'); ?></span>
                                    <select name="condition_grade">
                                        <option><?php i18n_e('tools.excellent'); ?></option>
                                        <option><?php i18n_e('tools.good'); ?></option>
                                        <option><?php i18n_e('tools.fair'); ?></option>
                                        <option><?php i18n_e('tools.parts'); ?></option>
                                    </select>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.product_image_required'); ?></span>
                                    <div class="custom-file-input">
                                        <input type="file" name="product_image" id="productImageInput" accept="image/*" required>
                                        <label for="productImageInput" class="custom-file-label">
                                            <span class="file-chosen"><?php i18n_e('tools.no_file_chosen'); ?></span>
                                            <span class="file-button"><?php i18n_e('tools.choose_file'); ?></span>
                                        </label>
                                    </div>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.contact_email'); ?></span>
                                    <input type="email" name="contact_email" value="<?= htmlspecialchars($clientEmail) ?>" placeholder="<?php i18n_e('tools.contact_email_placeholder', [], 'email@example.com'); ?>" required>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.contact_phone'); ?></span>
                                    <input type="tel" name="contact_phone" value="<?= htmlspecialchars($clientPhone) ?>" placeholder="<?php i18n_e('tools.contact_phone_placeholder', [], '+212 600-000000'); ?>" required>
                                </label>
                                <button class="tool-action" type="submit"><?php i18n_e('tools.estimate'); ?></button>
                                <div class="status-line"></div>
                            </form>
                            <form class="request-card" data-request="repair" data-request-panel="repair" hidden>
                                <h3><i class="fas fa-screwdriver-wrench" style="color:var(--tool-cyan);margin-right:8px"></i><?php i18n_e('tools.repair_service_title'); ?></h3>
                                <p style="color:var(--tool-muted);font-size:0.85rem;line-height:1.5;margin:0 0 8px"><?php i18n_e('tools.repair_service_desc'); ?></p>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.device_type'); ?></span>
                                    <select name="device_type" required>
                                        <option value=""><?php i18n_e('tools.select_device_type'); ?></option>
                                        <option value="desktop"><?php i18n_e('tools.desktop_pc'); ?></option>
                                        <option value="laptop"><?php i18n_e('tools.laptop'); ?></option>
                                        <option value="component"><?php i18n_e('tools.component_peripheral'); ?></option>
                                    </select>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.device_make_model'); ?></span>
                                    <input name="device_name" placeholder="<?php i18n_e('tools.device_placeholder'); ?>" required>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.issue_description'); ?></span>
                                    <textarea name="issue_description" rows="4" placeholder="<?php i18n_e('tools.issue_placeholder'); ?>" required></textarea>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.contact_email'); ?></span>
                                    <input type="email" name="contact_email" value="<?= htmlspecialchars($clientEmail) ?>" placeholder="<?php i18n_e('tools.contact_email_placeholder', [], 'email@example.com'); ?>" required>
                                </label>
                                <label class="tool-field">
                                    <span><?php i18n_e('tools.contact_phone'); ?></span>
                                    <input type="tel" name="contact_phone" value="<?= htmlspecialchars($clientPhone) ?>" placeholder="<?php i18n_e('tools.contact_phone_placeholder', [], '+212 600-000000'); ?>" required>
                                </label>
                                <button class="tool-action" type="submit"><i class="fas fa-paper-plane"></i> <?php i18n_e('tools.submit_repair_request'); ?></button>
                                <div class="status-line"></div>
                            </form>
                            <div class="request-card" data-request-panel="referral" hidden>
                                <h3><?php i18n_e('tools.referral_title'); ?></h3>
                                <button class="tool-action" type="button" id="referralBtn"><?php i18n_e('tools.generate_referral_link'); ?></button>
                                <div class="tool-result" id="referralResult">
                                    <div class="empty-state">
                                        <i class="fas fa-link"></i>
                                        <strong><?php i18n_e('tools.referral_empty_title'); ?></strong>
                                        <span><?php i18n_e('tools.referral_empty_desc'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth-nav.js"></script>
    <script>
        // Translation helpers for JS toasts and status messages
        const TOOLS_T = <?= json_encode([
            'added_to_cart' => i18n_t('tools.added_to_cart', [], '{name} added to cart.'),
            'bundle_added' => i18n_t('tools.bundle_added', [], '{count} bundle items added to cart.'),
            'switched_tool' => i18n_t('tools.switched_tool', [], 'Switched to {tool}'),
            'exported' => i18n_t('tools.exported', [], 'Results exported successfully!'),
            'printing' => i18n_t('tools.printing', [], 'Opening print dialog...'),
            'tip_shortcuts' => i18n_t('tools.tip_shortcuts', [], 'Tip: Press Ctrl+K to search, or use number keys 1-9 to switch tools'),
            'copied' => i18n_t('tools.copied', [], 'Copied to clipboard!'),
            'copy_failed' => i18n_t('tools.copy_failed', [], 'Failed to copy to clipboard'),
            'compat_pass' => i18n_t('tools.compat_pass', [], 'No blocking compatibility issue detected.'),
            'compat_fail' => i18n_t('tools.compat_fail', [], 'Compatibility issues found.'),
            'upgrade_lane' => i18n_t('tools.upgrade_lane', [], 'Biggest upgrade lane: {category}'),
            'no_upgrade' => i18n_t('tools.no_upgrade', [], 'No in-stock {category} upgrade found under {budget}.'),
            'no_match' => i18n_t('tools.no_match', [], 'No close catalog match found.'),
            'send_price_match' => i18n_t('tools.send_price_match', [], 'Send price-match request'),
            'select_cpu_gpu' => i18n_t('tools.select_cpu_gpu', [], 'Select CPU and GPU.'),
            'select_known_part' => i18n_t('tools.select_known_part', [], 'Select a known CPU or GPU.'),
            'balanced_match' => i18n_t('tools.balanced_match', [], 'Balanced match'),
            'practical_match' => i18n_t('tools.practical_match', [], 'Practical match'),
            'no_pairing' => i18n_t('tools.no_pairing', [], 'No sensible in-stock pairing found.'),
            'strong_upgrade' => i18n_t('tools.strong_upgrade', [], 'Strong upgrade runway.'),
            'weak_upgrade' => i18n_t('tools.weak_upgrade', [], 'Usable, with one weak point.'),
            'constrained' => i18n_t('tools.constrained', [], 'Likely to feel constrained.'),
            'no_stock_pressure' => i18n_t('tools.no_stock_pressure', [], 'No stock pressure detected.'),
            'out_of_stock' => i18n_t('tools.out_of_stock', [], 'OUT OF STOCK'),
            'stock_label' => i18n_t('tools.stock_label', [], 'STOCK: {quantity}'),
            'gift_build' => i18n_t('tools.gift_build', [], 'Gift build'),
            'student_bundle_label' => i18n_t('tools.student_bundle_label', [], 'Student bundle'),
            'add_bundle_cart' => i18n_t('tools.add_bundle_cart', [], 'Add bundle to cart'),
            'sending' => i18n_t('tools.sending', [], 'Sending'),
            'sending_request' => i18n_t('tools.sending_request', [], 'Sending request...'),
            'done' => i18n_t('tools.done', [], 'Done'),
            'request_failed' => i18n_t('tools.request_failed', [], 'Request failed. Try again.'),
            'running' => i18n_t('tools.running', [], 'Running'),
            'generating' => i18n_t('tools.generating', [], 'Generating'),
            'generating_referral' => i18n_t('tools.generating_referral', [], 'Generating referral...'),
            'could_not_generate' => i18n_t('tools.could_not_generate', [], 'Could not generate referral.'),
            'bottleneck_severe' => i18n_t('tools.bottleneck_severe', [], 'Severe CPU bottleneck: {cpu} cannot feed {gpu} in CPU-bound games.'),
            'bottleneck_moderate' => i18n_t('tools.bottleneck_moderate', [], 'Moderate CPU bottleneck detected at lower resolutions.'),
            'bottleneck_none' => i18n_t('tools.bottleneck_none', [], 'CPU/GPU pairing is within a healthy estimator range.'),
            'sourced_gpu' => i18n_t('tools.sourced_gpu', [], 'Sourced GPU baseline: {source}. CPU-adjusted for this pairing.'),
            'no_sourced_gpu' => i18n_t('tools.no_sourced_gpu', [], 'No sourced GPU row for this card yet. Showing estimator output only.'),
            'good_deal' => i18n_t('tools.good_deal', [], 'Good deal'),
            'overpriced' => i18n_t('tools.overpriced', [], 'Overpriced'),
            'fair_price' => i18n_t('tools.fair_price', [], 'Fair price'),
            'percent_below' => i18n_t('tools.percent_below', [], '{percent}% below Maroc PC catalog reference'),
            'percent_above' => i18n_t('tools.percent_above', [], '{percent}% above Maroc PC catalog reference'),
            'known_recommending' => i18n_t('tools.known_recommending', [], 'Known {category}: recommending {matches} matches only.'),
            'longevity' => i18n_t('tools.longevity', [], '{years} longevity'),
            'around_price' => i18n_t('tools.around_price', [], 'around {price}'),
            'add_to_cart_title' => i18n_t('tools.add_to_cart_title', [], 'Add {name} to cart'),
            'select_option' => i18n_t('tools.select_option', [], 'Select'),
            'success_title' => i18n_t('tools.success_title', [], 'Success'),
            'export_page_title' => i18n_t('tools.export_page_title', [], 'Maroc PC - {tool} Results'),
            'export_heading' => i18n_t('tools.export_heading', [], 'Maroc PC Tools - {tool}'),
            'generated_label' => i18n_t('tools.generated_label', [], 'Generated:'),
            'fail' => i18n_t('tools.fail', [], 'Fail'),
            'pass' => i18n_t('tools.pass', [], 'Pass'),
            'waiting' => i18n_t('tools.waiting', [], 'Waiting'),
            'watch_count' => i18n_t('tools.watch_count', [], '{count} watch'),
            'clear' => i18n_t('tools.clear', [], 'Clear'),
        ], JSON_UNESCAPED_UNICODE) ?>;
        const toolsT = (key, params, fallback) => {
            let str = TOOLS_T[key];
            if (str === undefined || str === null) return fallback || key;
            if (params) {
                Object.entries(params).forEach(([k, v]) => { str = str.replace(new RegExp('\\{' + k + '\\}', 'g'), v); });
            }
            return str;
        };

        const catalog = typeof products !== 'undefined' && Array.isArray(products) ? products : [];
        const stockState = {};
        const $ = id => document.getElementById(id);
        const value = id => $(id)?.value || '';
        const setHtml = (id, html) => { const node = $(id); if (node) node.innerHTML = html; };
        const setText = (id, text) => { const node = $(id); if (node) node.textContent = text; };
        const byCat = cat => catalog.filter(p => p.category === cat);
        const money = n => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' DH';
        const img = p => p?.image || `images/products/placeholder-${p?.category || 'storage'}.svg`;
        const specText = p => Object.values(p?.specs || {}).join(' ');
        const num = v => Number(String(v || '').match(/\d+/)?.[0] || 0);
        const scoreCpu = p => /9800X3D/i.test(p?.name) ? 98 : /7800X3D/i.test(p?.name) ? 94 : /9950|7950|285K/i.test(p?.name) ? 91 : /14900|265K/i.test(p?.name) ? 88 : /9700|7700/i.test(p?.name) ? 82 : /14600|9600/i.test(p?.name) ? 78 : /Xeon/i.test(p?.name) ? 48 : 62;
        const scoreGpu = p => /5090/i.test(p?.name) ? 115 : /5080/i.test(p?.name) ? 104 : /4090/i.test(p?.name) ? 100 : /5070 Ti|4080/i.test(p?.name) ? 92 : /7900 XTX/i.test(p?.name) ? 90 : /4070 Ti/i.test(p?.name) ? 82 : /5070/i.test(p?.name) ? 80 : /7800 XT/i.test(p?.name) ? 74 : /5060/i.test(p?.name) ? 68 : /580/i.test(p?.name) ? 42 : /550/i.test(p?.name) ? 26 : 58;
        const itemScore = p => p?.category === 'cpu' ? scoreCpu(p) : scoreGpu(p);
        const isLegacyCpu = p => /Xeon|E5-|X99|i5-7|i5-8|i7-7|i7-8/i.test(`${p?.name || ''} ${specText(p)}`);
        const REAL_GPU_BASELINES = [
            {
                match: /RTX 5080/i,
                source: 'NanoReview RTX 5080 game table',
                url: 'https://nanoreview.net/en/gpu/geforce-rtx-5080',
                average: { '1080p High': 239, '1080p Ultra': 203, '1440p Ultra': 164, '4K Ultra': 97 },
                games: [
                    ['Forza Horizon 5', 259, 185, 176, 141],
                    ['The Witcher 3', 274, 237, 170, 89],
                    ['Counter-Strike 2', 314, 237, 175, 89],
                    ['Far Cry 6', 193, 176, 160, 96],
                    ['Hogwarts Legacy', 183, 154, 120, 70],
                    ['Call of Duty: MWIII', 249, 241, 193, 132],
                    ['Ghost of Tsushima', 171, 137, 127, 83],
                    ['Cyberpunk 2077', 216, 190, 136, 63],
                    ['Shadow of the Tomb Raider', 293, 270, 216, 113],
                ],
            },
            {
                match: /RTX 4090/i,
                source: "Tom's Hardware GPU hierarchy",
                url: 'https://www.tomshardware.com/reviews/gpu-hierarchy,4388.html',
                average: { '1080p High': 196, '1080p Ultra': 150, '1440p Ultra': 127, '4K Ultra': 85 },
                games: [],
            },
            {
                match: /RX 7900 XTX/i,
                source: "Tom's Hardware GPU hierarchy",
                url: 'https://www.tomshardware.com/reviews/gpu-hierarchy,4388.html',
                average: { '1080p High': 174, '1080p Ultra': 125, '1440p Ultra': 103, '4K Ultra': 64 },
                games: [],
            },
        ];
        const gpuBaseline = gpu => REAL_GPU_BASELINES.find(row => row.match.test(gpu?.name || ''));
        const productRow = p => `<div class="product-row"><img src="${img(p)}" onerror="this.src='logo.png'" alt=""><div><strong>${p.name}</strong><span>${p.brand} · ${p.category}</span></div><em>${money(p.price)}</em></div>`;
        const productCard = p => `<div class="product-row"><img src="${img(p)}" onerror="this.src='logo.png'" alt=""><div><strong>${p.name}</strong><span>${p.brand} - ${p.category}</span></div><em>${money(p.price)}</em><button class="product-cart-btn" type="button" data-add-product="${p.id}" title="${toolsT('add_to_cart_title', { name: p.name }, 'Add ' + p.name + ' to cart')}" aria-label="${toolsT('add_to_cart_title', { name: p.name }, 'Add ' + p.name + ' to cart')}"><i class="fas fa-cart-plus"></i></button></div>`;
        const selectHtml = (items, label = toolsT('select_option', null, 'Select')) => `<option value="">${label}</option>${items.map(p => `<option value="${p.id}">${p.name} - ${money(p.price)}</option>`).join('')}`;
        const productById = id => catalog.find(p => Number(p.id) === Number(id));
        function addProductToCart(product) {
            if (!product) return;
            console.log('Adding to cart:', product);
            
            if (window.Cart?.add) {
                window.Cart.add(product);
                showCartFeedback(product.name);
                return;
            }
            
            const current = JSON.parse(localStorage.getItem('cart') || '[]');
            const existing = current.find(item => Number(item.id) === Number(product.id));
            if (existing) existing.quantity = Number(existing.quantity || 1) + 1;
            else current.push({ ...product, quantity: 1 });
            localStorage.setItem('cart', JSON.stringify(current));
            
            // Update cart badge
            updateCartBadge();
            
            // Show feedback
            showCartFeedback(product.name);
        }
        
        function updateCartBadge() {
            const cartBadge = document.getElementById('sidebarCartCount');
            if (cartBadge) {
                const cartItems = JSON.parse(localStorage.getItem('cart') || '[]');
                const totalItems = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
                cartBadge.textContent = totalItems;
            }
        }
        
        function showCartFeedback(productName) {
            // Try Toast first
            if (window.Toast && typeof Toast.success === 'function') {
                try {
                    Toast.success(toolsT('added_to_cart', { name: productName }, productName + ' added to cart.'), 3000);
                    return;
                } catch (e) {
                    console.error('Toast failed:', e);
                }
            }
            
            // Fallback: Create a simple custom toast
            const message = toolsT('added_to_cart', { name: productName }, productName + ' added to cart.');
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: var(--tool-panel, #0a0d12);
                border: 1px solid var(--tool-cyan, #00f5d4);
                border-left: 4px solid var(--tool-green, #00e676);
                border-radius: 10px;
                padding: 16px 20px;
                color: var(--tool-text, #eef0f4);
                font-family: 'Syne', sans-serif;
                font-size: 0.9rem;
                box-shadow: 0 8px 24px rgba(0,0,0,0.4);
                z-index: 9999;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-check-circle" style="color: var(--tool-green, #00e676); font-size: 20px;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; margin-bottom: 4px;">${toolsT('success_title', null, 'Success')}</div>
                        <div style="color: var(--tool-muted, #9aa5b5); font-size: 0.85rem;">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: var(--tool-muted, #9aa5b5); cursor: pointer; font-size: 18px; padding: 4px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        const memoryType = p => /DDR5/i.test(specText(p)) ? 'DDR5' : /DDR4/i.test(specText(p)) ? 'DDR4' : '';
        const socket = p => String(p?.specs?.Socket || '').toUpperCase();
        const watts = p => num(p?.specs?.TDP || p?.specs?.Wattage || p?.specs?.Power) || ({ cpu: 125, gpu: 300, motherboard: 60, ram: 12, storage: 10, cooling: 15, psu: 0 }[p?.category] || 0);

        function activateTool(tool) {
            const btn = document.querySelector(`.tool-nav button[data-tool="${tool}"]`);
            const panel = document.getElementById(`tool-${tool}`);
            if (!btn || !panel) return;
            document.querySelectorAll('.tool-nav button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tool-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            panel.classList.add('active');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function bindToolNavigation() {
            document.querySelectorAll('.tool-nav button').forEach(btn => btn.addEventListener('click', () => activateTool(btn.dataset.tool)));
            document.querySelectorAll('[data-tool-jump]').forEach(btn => btn.addEventListener('click', () => activateTool(btn.dataset.toolJump)));
        }

        function bindHeaderDropdown() {
            const dropdown = document.querySelector('.nav-dropdown');
            const toggle = dropdown?.querySelector('.dropdown-toggle');
            if (!dropdown || !toggle) return;
            const setOpen = open => {
                dropdown.classList.toggle('open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            dropdown.addEventListener('mouseenter', () => setOpen(true));
            dropdown.addEventListener('mouseleave', () => setOpen(false));
            toggle.addEventListener('click', event => {
                event.preventDefault();
                setOpen(toggle.getAttribute('aria-expanded') !== 'true');
            });
            toggle.addEventListener('keydown', event => {
                if (event.key === 'Escape') setOpen(false);
            });
        }

        function bindRequestPicker() {
            const buttons = document.querySelectorAll('[data-request-view]');
            const panels = document.querySelectorAll('[data-request-panel]');
            buttons.forEach(btn => btn.addEventListener('click', () => {
                buttons.forEach(item => {
                    const selected = item === btn;
                    item.classList.toggle('active', selected);
                    item.setAttribute('aria-selected', selected ? 'true' : 'false');
                });
                panels.forEach(panel => { panel.hidden = panel.dataset.requestPanel !== btn.dataset.requestView; });
            }));
        }

        function fillSelects() {
            const sets = {
                Cpu: byCat('cpu'), Gpu: byCat('gpu'), Board: byCat('motherboard'), Ram: byCat('ram'), Psu: byCat('psu'), Cooling: byCat('cooling')
            };
            ['compat','bench','future'].forEach(prefix => {
                Object.entries(sets).forEach(([key, items]) => {
                    const el = document.getElementById(prefix + key);
                    if (el) el.innerHTML = selectHtml(items, `Select ${key}`);
                });
            });
            setHtml('pairPart', selectHtml([...byCat('cpu'), ...byCat('gpu')], 'Select CPU or GPU'));
            setText('statusProducts', catalog.length);
            setText('statusStock', catalog.filter(p => p.inStock).length);
        }

        async function loadStock() {
            try {
                const res = await fetch('api/products-stock.php');
                const data = await res.json();
                Object.assign(stockState, data.stock || {});
                setText('statusStock', Object.values(stockState).filter(s => s.in_stock).length);
                renderRadar();
            } catch (e) {
                renderRadar();
            }
        }

        function renderCompat() {
            const cpu = productById(value('compatCpu')), board = productById(value('compatBoard')), ram = productById(value('compatRam')), gpu = productById(value('compatGpu')), psu = productById(value('compatPsu')), cooling = productById(value('compatCooling'));
            const load = [cpu, board, ram, gpu, cooling].filter(Boolean).reduce((s,p) => s + watts(p), 0);
            const psuWatts = watts(psu);
            const checks = [];
            checks.push({ ok: cpu && board ? socket(cpu) === socket(board) : null, label: cpu && board ? `Socket: ${socket(cpu) || 'unknown'} vs ${socket(board) || 'unknown'}` : 'Select CPU and motherboard' });
            checks.push({ ok: board && ram ? !memoryType(board) || !memoryType(ram) || memoryType(board) === memoryType(ram) : null, label: board && ram ? `Memory: ${memoryType(board) || 'unknown'} / ${memoryType(ram) || 'unknown'}` : 'Select board and RAM' });
            checks.push({ ok: psu ? psuWatts >= Math.ceil(load * 1.25) : null, label: psu ? `PSU: ${psuWatts}W for ~${load}W load` : 'Select PSU' });
            checks.push({ ok: cpu && cooling ? watts(cooling) >= Math.min(250, watts(cpu)) : null, label: cpu && cooling ? `Cooling load: CPU ~${watts(cpu)}W` : 'Select CPU and cooling' });
            const bad = checks.filter(c => c.ok === false).length;
            setHtml('compatResult', `<div class="result-grid">${checks.map(c => `<div class="readout ${c.ok === false ? 'bad' : c.ok ? 'good' : 'warn'}"><span>${c.ok === false ? toolsT('fail', null, 'Fail') : c.ok ? toolsT('pass', null, 'Pass') : toolsT('waiting', null, 'Waiting')}</span><strong>${c.label}</strong></div>`).join('')}</div><div class="status-line ${bad ? 'bad' : 'good'}"><i class="fas ${bad ? 'fa-circle-xmark' : 'fa-circle-check'}"></i>${bad ? toolsT('compat_fail', null, 'Compatibility issues found.') : toolsT('compat_pass', null, 'No blocking compatibility issue detected.')}</div>`);
        }

        function renderUpgrade() {
            const budget = Number(value('upgradeBudget') || 0);
            const gpuWeak = /gtx|rx 5|rx 4|1050|1060|1650|1660|580|550/i.test(value('upgradeGpu'));
            const cpuWeak = /i3|i5-7|i5-8|ryzen 3|xeon/i.test(value('upgradeCpu'));
            const category = gpuWeak || !cpuWeak ? 'gpu' : 'cpu';
            const options = byCat(category).filter(p => p.inStock && p.price <= budget).sort((a,b) => (category === 'gpu' ? scoreGpu(b)-scoreGpu(a) : scoreCpu(b)-scoreCpu(a)) || b.price-a.price).slice(0,3);
            setHtml('upgradeResult', options.length ? `<div class="status-line good"><i class="fas fa-arrow-up"></i>${toolsT("upgrade_lane", { category: category.toUpperCase() }, "Biggest upgrade lane: " + category.toUpperCase())}</div>${options.map(productCard).join('')}` : `<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>${toolsT("no_upgrade", { category: category.toUpperCase(), budget: money(budget) }, "No in-stock " + category.toUpperCase() + " upgrade found under " + money(budget) + ".")}</div>`);
        }

        function renderDeal() {
            const q = value('dealName').toLowerCase().trim();
            const seen = Number(value('dealPrice') || 0);
            const match = catalog.filter(p => (`${p.name} ${p.brand}`).toLowerCase().includes(q) || q.split(/\s+/).some(token => token.length > 2 && p.name.toLowerCase().includes(token))).sort((a,b) => Math.abs(a.price-seen)-Math.abs(b.price-seen))[0];
            if (!match) { setHtml('dealResult', '<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>No close catalog match found.</div>'); return; }
            const delta = Math.round(((seen - match.price) / match.price) * 100);
            const tone = delta <= -12 ? 'good' : delta >= 8 ? 'bad' : 'warn';
            const label = tone === 'good' ? toolsT('good_deal', null, 'Good deal') : tone === 'bad' ? toolsT('overpriced', null, 'Overpriced') : toolsT('fair_price', null, 'Fair price');
            setHtml('dealResult', `<div class="readout ${tone}"><span>${label}</span><strong>${toolsT(delta < 0 ? "percent_below" : "percent_above", { percent: Math.abs(delta) }, Math.abs(delta) + "% " + (delta < 0 ? "below" : "above") + " Maroc PC catalog reference")}</strong></div>${productCard(match)}<button class="tool-action secondary" id="dealPriceMatch"><i class="fas fa-paper-plane"></i> ${toolsT("send_price_match", null, "Send price-match request")}</button>`);
            $('dealPriceMatch').onclick = event => postRequest({ action: 'price_match', product_id: match.id, product_name: match.name, competitor_url: value('dealUrl'), competitor_price: seen }, $('dealResult'), event.currentTarget);
        }

        function renderBench() {
            const cpu = productById(value('benchCpu')), gpu = productById(value('benchGpu'));
            if (!cpu || !gpu) { setHtml('benchResult', toolsT('select_cpu_gpu', null, 'Select CPU and GPU.')); return; }
            const cpuScore = scoreCpu(cpu);
            const gpuScore = scoreGpu(gpu);
            const ratio = cpuScore / Math.max(1, gpuScore);
            const real = gpuBaseline(gpu);
            const cpuCap = res => cpuScore * ({ '1080p': 1.18, '1440p': 1.36, '4K': 1.62 }[res] || 1.3);
            const fallbackGpu = res => gpuScore * ({ '1080p': 1.72, '1440p': 1.25, '4K': 0.72 }[res] || 1.2);
            const rawFor = res => {
                if (!real) return fallbackGpu(res);
                if (res === '1080p') return real.average['1080p Ultra'] || real.average['1080p High'];
                if (res === '1440p') return real.average['1440p Ultra'];
                return real.average['4K Ultra'];
            };
            const adjusted = (raw, res, cpuDemand = 1) => {
                const cap = cpuCap(res) / cpuDemand;
                const severePenalty = ratio < 0.62 ? 0.78 : ratio < 0.74 ? 0.86 : ratio < 0.86 ? 0.94 : 1;
                return Math.max(20, Math.round(Math.min(raw, cap) * severePenalty));
            };
            const games = real?.games?.length
                ? real.games.map(([name, high1080, ultra1080, ultra1440, ultra4k]) => [name, ultra1440, ultra1080, ultra4k, name.includes('Counter') ? 1.42 : name.includes('Call of Duty') ? 1.22 : name.includes('Cyberpunk') ? 1.06 : 1])
                : [
                    ['Cyberpunk 2077', fallbackGpu('1440p') * .86, fallbackGpu('1080p') * .86, fallbackGpu('4K') * .86, 1.06],
                    ['Warzone', fallbackGpu('1440p') * 1.05, fallbackGpu('1080p') * 1.05, fallbackGpu('4K') * 1.05, 1.22],
                    ['Valorant', fallbackGpu('1440p') * 1.55, fallbackGpu('1080p') * 1.55, fallbackGpu('4K') * 1.55, 1.48],
                    ['Forza Horizon 5', fallbackGpu('1440p') * 1.08, fallbackGpu('1080p') * 1.08, fallbackGpu('4K') * 1.08, .92],
                ];
            const warning = ratio < 0.72
                ? `<div class="status-line bad"><i class="fas fa-triangle-exclamation"></i>${toolsT("bottleneck_severe", { cpu: cpu.name, gpu: gpu.name }, "Severe CPU bottleneck: " + cpu.name + " cannot feed " + gpu.name + " in CPU-bound games.")}</div>`
                : ratio < 0.86
                    ? `<div class="status-line warn"><i class="fas fa-triangle-exclamation"></i>${toolsT('bottleneck_moderate', null, 'Moderate CPU bottleneck detected at lower resolutions.')}</div>`
                    : `<div class="status-line good"><i class="fas fa-circle-check"></i>${toolsT('bottleneck_none', null, 'CPU/GPU pairing is within a healthy estimator range.')}</div>`;
            const source = real
                ? `<div class="status-line"><i class="fas fa-database"></i>${toolsT("sourced_gpu", { source: "<a href='" + real.url + "' target='_blank' rel='noopener'>" + real.source + "</a>" }, "Sourced GPU baseline: " + real.source + ". CPU-adjusted for this pairing.")}</div>`
                : `<div class="status-line warn"><i class="fas fa-database"></i>${toolsT('no_sourced_gpu', null, 'No sourced GPU row for this card yet. Showing estimator output only.')}</div>`;
            setHtml('benchResult', `${source}${warning}<div class="result-grid">${[
                ['1080p', rawFor('1080p')],
                ['1440p', rawFor('1440p')],
                ['4K', rawFor('4K')]
            ].map(([res, raw]) => `<div class="readout"><span>${res} AVG</span><strong>${adjusted(raw, res)} FPS</strong></div>`).join('')}</div>${games.map(([g, raw1440, raw1080, raw4k, cpuDemand]) => `<div class="radar-item"><strong>${g}</strong><span>1080p: ${adjusted(raw1080, '1080p', cpuDemand)} FPS · 1440p: ${adjusted(raw1440, '1440p', cpuDemand)} FPS · 4K: ${adjusted(raw4k, '4K', cpuDemand)} FPS</span></div>`).join('')}`);
        }

        function renderPair() {
            const part = productById(value('pairPart'));
            if (!part) { setHtml('pairResult', toolsT('select_known_part', null, 'Select a known CPU or GPU.')); return; }
            const direction = value('pairDirection');
            let targetCat = direction === 'auto' ? (part.category === 'cpu' ? 'gpu' : 'cpu') : direction;
            if (targetCat === part.category) targetCat = part.category === 'cpu' ? 'gpu' : 'cpu';

            const score = itemScore(part);
            const minScore = score >= 100 ? 74 : score >= 88 ? 68 : score >= 74 ? 56 : 0;
            const maxGap = score >= 90 ? 34 : 42;
            const candidates = byCat(targetCat)
                .filter(p => p.inStock)
                .filter(p => targetCat !== 'cpu' || !isLegacyCpu(p) || score < 70)
                .map(p => ({ p, score: itemScore(p), gap: Math.abs(itemScore(p) - score) }))
                .filter(row => row.score >= minScore && row.gap <= maxGap)
                .sort((a,b) => a.gap - b.gap || a.p.price - b.p.price);

            const budgetCeiling = targetCat === 'cpu' ? (score >= 100 ? 3800 : 2800) : (score >= 88 ? 8000 : 5000);
            const balancedFloor = targetCat === 'cpu' ? (score >= 100 ? 78 : 70) : (score >= 88 ? 78 : 64);
            const budget = candidates.filter(row => row.p.price <= budgetCeiling).sort((a,b) => b.score - a.score || a.p.price - b.p.price)[0] || candidates[0];
            const balanced = candidates.filter(row => row.score >= balancedFloor).sort((a,b) => a.gap - b.gap || a.p.price - b.p.price)[0] || candidates[0];
            const noCompromise = candidates.slice().sort((a,b) => b.score - a.score || b.p.price - a.p.price)[0];
            const tiers = [['Budget', budget], ['Balanced', balanced], ['No compromise', noCompromise]];
            const note = `<div class="status-line ${candidates.length ? 'good' : 'warn'}"><i class="fas ${candidates.length ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i>${toolsT('known_recommending', { category: part.category.toUpperCase(), matches: targetCat.toUpperCase() }, 'Known ' + part.category.toUpperCase() + ': recommending ' + targetCat.toUpperCase() + ' matches only.')}</div>`;
            setHtml('pairResult', note + (candidates.length ? tiers.filter((x, index, arr) => x[1] && arr.findIndex(y => y[1]?.p.id === x[1].p.id) === index).map(([label, row]) => `<div class="readout"><span>${label}</span><strong>${row.gap <= 10 ? toolsT('balanced_match', null, 'Balanced match') : toolsT('practical_match', null, 'Practical match')}</strong></div>${productCard(row.p)}`).join('') : toolsT('no_pairing', null, 'No sensible in-stock pairing found.')));
        }

        function renderFuture() {
            const cpu = productById(value('futureCpu')), gpu = productById(value('futureGpu')), board = productById(value('futureBoard')), ram = productById(value('futureRam')), psu = productById(value('futurePsu'));
            const years = num(value('futureYears')) || 3;
            const vram = num(gpu?.specs?.VRAM);
            let score = 35;
            if (/AM5|LGA 1851/i.test(socket(cpu) + socket(board))) score += 18;
            if (memoryType(ram) === 'DDR5' || memoryType(board) === 'DDR5') score += 17;
            if (vram >= 16) score += 18; else if (vram >= 12) score += 12; else score += 5;
            if (/PCIe 5/i.test(specText(board))) score += 12;
            if (watts(psu) >= 850) score += 10;
            score = Math.max(0, Math.min(100, score - Math.max(0, years - 3) * 7));
            const longevityLabel = toolsT('longevity', { years: value('futureYears') }, value('futureYears') + ' longevity');
            setHtml('futureResult', `<div class="readout ${score >= 78 ? 'good' : score >= 58 ? 'warn' : 'bad'}"><span>${longevityLabel}</span><strong>${score}/100</strong></div><div class="status-line ${score >= 78 ? 'good' : score >= 58 ? 'warn' : 'bad'}"><i class="fas fa-shield"></i>${score >= 78 ? toolsT('strong_upgrade', null, 'Strong upgrade runway.') : score >= 58 ? toolsT('weak_upgrade', null, 'Usable, with one weak point.') : toolsT('constrained', null, 'Likely to feel constrained.')}</div>`);
        }

        function renderRadar() {
            const rows = catalog.map(p => ({ p, s: stockState[String(p.id)] || { in_stock: p.inStock, quantity: p.inStock ? 10 : 0, tone: p.inStock ? 'good' : 'out' } }))
                .filter(row => !row.s.in_stock || row.s.quantity <= 10)
                .sort((a,b) => a.s.quantity - b.s.quantity).slice(0,20);
            setText('statusAlerts', rows.length ? toolsT('watch_count', { count: rows.length }, rows.length + ' watch') : toolsT('clear', null, 'Clear'));
            setHtml('radarResult', rows.map(({p,s}) => `<div class="radar-item"><strong>${p.name}</strong><span>${!s.in_stock ? toolsT('out_of_stock', null, 'OUT OF STOCK') : toolsT('stock_label', { quantity: s.quantity }, 'STOCK: ' + s.quantity)}</span></div>`).join('') || toolsT('no_stock_pressure', null, 'No stock pressure detected.'));
        }

        function renderBundle() {
            const budget = Number(value('bundleBudget') || 0);
            const use = value('bundleUse');
            const weights = use === 'gaming' ? { cpu:.2,gpu:.42,ram:.1,storage:.1,monitor:.18 } : use === 'design' ? { cpu:.27,gpu:.25,ram:.16,storage:.16,monitor:.16 } : { cpu:.24,gpu:.18,ram:.14,storage:.14,monitor:.3 };
            const picks = Object.entries(weights).map(([cat,w]) => byCat(cat).filter(p => p.inStock && p.price <= budget*w*1.35).sort((a,b)=>b.rating-a.rating || b.price-a.price)[0]).filter(Boolean);
            const totalPrice = picks.reduce((s,p)=>s+p.price,0);
            const bundleLabel = value('bundleMode') === 'gift' ? toolsT('gift_build', null, 'Gift build') : toolsT('student_bundle_label', null, 'Student bundle');
            const priceAround = toolsT('around_price', { price: money(totalPrice) }, 'around ' + money(totalPrice));
            setHtml('bundleResult', `<div class="status-line good"><i class="fas fa-gift"></i>${bundleLabel} ${priceAround}</div><div class="bundle-actions"><button class="tool-action" type="button" data-add-bundle="${picks.map(p => p.id).join(',')}"><i class="fas fa-cart-plus"></i> ${toolsT('add_bundle_cart', null, 'Add bundle to cart')}</button></div>${picks.map(productCard).join('')}`);
        }

         async function postRequest(payload, host, button = null) {
            const target = host.querySelector?.('.status-line') || host;
            const original = button?.innerHTML;
            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending';
            }
            target.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>Sending request...';
            target.className = 'status-line warn';
            try {
                const isFormData = payload instanceof FormData;
                const fetchOpts = {
                    method: 'POST',
                    body: isFormData ? payload : JSON.stringify(payload)
                };
                if (!isFormData) {
                    fetchOpts.headers = { 'Content-Type': 'application/json' };
                }
                const res = await fetch('api/feature-requests.php', fetchOpts);
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || data.error || 'Request failed.');
                target.innerHTML = `<i class="fas ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${data.message || 'Done'}`;
                target.className = `status-line ${data.success ? 'good' : 'bad'}`;
                if (data.estimated_value) target.innerHTML += ` Estimated: ${money(data.estimated_value)}.`;
            } catch (error) {
                target.innerHTML = `<i class="fas fa-circle-xmark"></i>${error.message || 'Request failed. Try again.'}`;
                target.className = 'status-line bad';
            } finally {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                    button.innerHTML = original;
                }
            }
        }

        document.querySelectorAll('[data-run]').forEach(btn => btn.addEventListener('click', async () => {
            const action = { compat: renderCompat, upgrade: renderUpgrade, deal: renderDeal, bench: renderBench, pair: renderPair, future: renderFuture, radar: loadStock, bundle: renderBundle }[btn.dataset.run];
            if (!action) return;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running';
            try {
                await action();
            } finally {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.innerHTML = original;
            }
        }));
        document.addEventListener('click', event => {
            const productBtn = event.target.closest('[data-add-product]');
            if (productBtn) {
                const productId = productBtn.dataset.addProduct;
                const product = productById(productId);
                console.log('Cart button clicked:', productId, product); // Debug log
                if (product) {
                    addProductToCart(product);
                } else {
                    console.error('Product not found:', productId);
                }
                return;
            }
            const bundleBtn = event.target.closest('[data-add-bundle]');
            if (bundleBtn) {
                const products = String(bundleBtn.dataset.addBundle || '').split(',').map(productById).filter(Boolean);
                products.forEach(addProductToCart);
                Toast.success(toolsT('bundle_added', { count: products.length }, products.length + ' bundle items added to cart.'), 3000);
            }
        });
        document.querySelectorAll('[data-request]').forEach(form => form.addEventListener('submit', e => {
            e.preventDefault();
            const fd = new FormData(form);
            const type = form.dataset.request;
            const actionMap = { community: 'community_build', trade: 'trade_in', repair: 'repair_service' };
            const action = actionMap[type] || type;
            
            if (type === 'trade') {
                fd.append('action', 'trade_in');
                fd.append('components', '[]');
                postRequest(fd, form.querySelector('.status-line'), form.querySelector('button[type="submit"]'));
            } else {
                postRequest({ action, ...Object.fromEntries(fd.entries()), components: [] }, form.querySelector('.status-line'), form.querySelector('button[type="submit"]'));
            }
        }));
        document.getElementById('referralBtn').addEventListener('click', async () => {
            const btn = document.getElementById('referralBtn');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating';
            setHtml('referralResult', '<div class="status-line warn"><i class="fas fa-circle-notch fa-spin"></i>Generating referral...</div>');
            try {
                const res = await fetch('api/feature-requests.php?action=referral');
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || data.error || 'Referral request failed.');
                setHtml('referralResult', data.success ? `<strong>${data.code}</strong><br><span>${data.url}</span><br><span>${data.bonus_points} points for both accounts after first purchase.</span>` : data.message);
            } catch (error) {
                setHtml('referralResult', `<div class="status-line bad"><i class="fas fa-circle-xmark"></i>${error.message || toolsT('could_not_generate', null, 'Could not generate referral.')}</div>`);
            } finally {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.innerHTML = original;
            }
        });

        function bindSidebarNavigation() {
            const hamburgerBtn = $('hamburgerBtn');
            const sidebar = $('sidebar');
            const sidebarClose = $('sidebarClose');
            const sidebarOverlay = $('sidebarOverlay');

            if (hamburgerBtn && sidebar && sidebarClose && sidebarOverlay) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });

                const closeSidebar = () => {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                };

                sidebarClose.addEventListener('click', closeSidebar);
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.querySelectorAll('.sidebar-toggle-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const parent = btn.closest('.sidebar-dropdown');
                    const isOpen = parent.classList.contains('open');
                    document.querySelectorAll('.sidebar-dropdown.open').forEach(d => d.classList.remove('open'));
                    if (!isOpen) parent.classList.add('open');
                });
            });

            // Mobile search redirection
            const mobileSearchInput = document.querySelector('.sidebar-search input');
            const mobileSearchBtn = document.querySelector('.sidebar-search button');
            if (mobileSearchInput && mobileSearchBtn) {
                const executeMobileSearch = () => {
                    if (mobileSearchInput.value.trim()) {
                        const productsUrl = <?= json_encode(i18n_url('products.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                        const separator = productsUrl.includes('?') ? '&' : '?';
                        window.location.href = `${productsUrl}${separator}search=${encodeURIComponent(mobileSearchInput.value.trim())}`;
                    }
                };
                mobileSearchBtn.addEventListener('click', executeMobileSearch);
                mobileSearchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        executeMobileSearch();
                    }
                });
            }
        }

        bindToolNavigation();
        bindHeaderDropdown();
        bindRequestPicker();
        fillSelects();
        loadStock();
        renderRadar();
        
        // Ensure sidebar elements are loaded before binding
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindSidebarNavigation);
        } else {
            bindSidebarNavigation();
        }
        
        if (window.Cart) {
            window.Cart.updateUI();
        }
    </script>

    <!-- Toast Container -->
    <div class="tools-toast-container" id="toastContainer"></div>

    <script>
        // ============================================
        // TOAST NOTIFICATION SYSTEM
        // ============================================
        window.Toast = {
            container: null,
            lastMessage: '',
            lastMessageTime: 0,
            messageDebounceDelay: 1000, // 1 second debounce for identical messages
            
            init() {
                this.container = document.getElementById('toastContainer');
                if (!this.container) {
                    this.container = document.createElement('div');
                    this.container.id = 'toastContainer';
                    this.container.className = 'tools-toast-container';
                    document.body.appendChild(this.container);
                }
            },
            
            show(message, type = 'info', duration = 8000) {
                try {
                    const displayDuration = Number.isFinite(Number(duration)) ? Number(duration) : 8000;

                    // Debounce only identical messages to prevent spam
                    const now = Date.now();
                    if (message === this.lastMessage && now - this.lastMessageTime < this.messageDebounceDelay) {
                        return null;
                    }
                    
                    this.lastMessage = message;
                    this.lastMessageTime = now;
                    
                    this.init();
                
                    const icons = {
                    success: 'fa-circle-check',
                    error: 'fa-circle-xmark',
                    warning: 'fa-triangle-exclamation',
                    info: 'fa-circle-info'
                };
                
                const titles = {
                    success: 'Success',
                    error: 'Error',
                    warning: 'Warning',
                    info: 'Info'
                };
                
                const toast = document.createElement('div');
                toast.className = `tools-toast ${type}`;
                
                // Create toast structure safely
                const toastIcon = document.createElement('div');
                toastIcon.className = 'toast-icon';
                const icon = document.createElement('i');
                icon.className = `fas ${icons[type] || icons.info}`;
                toastIcon.appendChild(icon);
                
                const toastContent = document.createElement('div');
                toastContent.className = 'toast-content';
                
                const toastTitle = document.createElement('div');
                toastTitle.className = 'toast-title';
                toastTitle.textContent = titles[type] || titles.info;
                
                const toastMessage = document.createElement('div');
                toastMessage.className = 'toast-message';
                toastMessage.textContent = message;
                
                toastContent.appendChild(toastTitle);
                toastContent.appendChild(toastMessage);
                
                const toastClose = document.createElement('button');
                toastClose.className = 'toast-close';
                toastClose.setAttribute('aria-label', 'Close');
                const closeIcon = document.createElement('i');
                closeIcon.className = 'fas fa-times';
                toastClose.appendChild(closeIcon);
                
                toast.appendChild(toastIcon);
                toast.appendChild(toastContent);
                toast.appendChild(toastClose);
                
                toastClose.addEventListener('click', () => this.remove(toast));
                
                this.container.appendChild(toast);
                
                if (displayDuration > 0) {
                    toast.dismissTimer = setTimeout(() => this.remove(toast), displayDuration);
                }
                
                return toast;
                } catch (error) {
                    console.error('Toast error:', error);
                    return null;
                }
            },
            
            remove(toast) {
                if (!toast || toast.classList.contains('removing')) return;
                if (toast.dismissTimer) {
                    clearTimeout(toast.dismissTimer);
                    toast.dismissTimer = null;
                }
                toast.classList.add('removing');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            },
            
            success(message, duration) {
                return this.show(message, 'success', duration);
            },
            
            error(message, duration) {
                return this.show(message, 'error', duration);
            },
            
            warning(message, duration) {
                return this.show(message, 'warning', duration);
            },
            
            info(message, duration) {
                try {
                    return this.show(message, 'info', duration);
                } catch (error) {
                    console.error('Toast.info error:', error);
                    return null;
                }
            }
        };

        document.querySelectorAll('.help-trigger[data-help-message]').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                Toast.info(button.dataset.helpMessage, 12000);
            });
        });

        // ============================================
        // KEYBOARD SHORTCUTS
        // ============================================
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('.sidebar-search input');
                if (searchInput) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (sidebar && overlay) {
                        sidebar.classList.add('open');
                        overlay.classList.add('active');
                        setTimeout(() => searchInput.focus(), 100);
                    }
                }
            }
            
            // Escape to close sidebar
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar && overlay && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            }
            
            // Number keys 1-9 to switch tools (only if not typing in input)
            if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey) {
                const activeElement = document.activeElement;
                if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA' && activeElement.tagName !== 'SELECT') {
                    const toolButtons = document.querySelectorAll('.tool-nav button[data-tool]');
                    const index = parseInt(e.key) - 1;
                    if (toolButtons[index]) {
                        toolButtons[index].click();
                        Toast.info(toolsT('switched_tool', { tool: toolButtons[index].textContent.trim() }, 'Switched to ' + toolButtons[index].textContent.trim()), 2000);
                    }
                }
            }
        });

        // ============================================
        // LOADING STATE HELPERS
        // ============================================
        function setLoading(element, isLoading) {
            if (isLoading) {
                element.classList.add('is-loading');
                if (element.tagName === 'BUTTON') {
                    element.disabled = true;
                }
            } else {
                element.classList.remove('is-loading');
                if (element.tagName === 'BUTTON') {
                    element.disabled = false;
                }
            }
        }

        // ============================================
        // ENHANCED TOOL ACTIONS WITH FEEDBACK
        // ============================================
        document.querySelectorAll('.tool-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.classList.contains('is-loading')) {
                    e.preventDefault();
                    return;
                }
            });
        });

        // ============================================
        // RESULT ACTIONS (Export, Share, Copy)
        // ============================================
        function addResultActions(resultElement, toolName) {
            // Check if actions already exist
            if (resultElement.querySelector('.result-actions')) return;
            
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'result-actions';
            actionsDiv.style.cssText = 'display: flex; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--tool-border);';
            actionsDiv.innerHTML = `
                <button class="tool-action secondary" onclick="exportResult('${toolName}')" data-tooltip="Export as JSON">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="tool-action secondary" onclick="shareResult('${toolName}')" data-tooltip="Copy shareable link">
                    <i class="fas fa-share-nodes"></i> Share
                </button>
                <button class="tool-action secondary" onclick="printResult('${toolName}')" data-tooltip="Print results">
                    <i class="fas fa-print"></i> Print
                </button>
            `;
            
            resultElement.appendChild(actionsDiv);
        }

        function exportResult(toolName) {
            const resultElement = document.getElementById(`${toolName}Result`);
            if (!resultElement) return;
            
            const data = {
                tool: toolName,
                timestamp: new Date().toISOString(),
                results: resultElement.innerText
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `maroc-pc-${toolName}-${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            Toast.success(toolsT('exported', null, 'Results exported successfully!'), 3000);
        }

        function shareResult(toolName) {
            const url = `${window.location.origin}${window.location.pathname}?tool=${toolName}`;
            copyToClipboard(url);
        }

        function printResult(toolName) {
            const resultElement = document.getElementById(`${toolName}Result`);
            if (!resultElement) return;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${toolsT('export_page_title', { tool: toolName }, 'Maroc PC - ' + toolName + ' Results')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #00f5d4; }
                        .timestamp { color: #666; font-size: 0.9em; }
                    </style>
                </head>
                <body>
                    <h1>${toolsT('export_heading', { tool: toolName }, 'Maroc PC Tools - ' + toolName)}</h1>
                    <p class="timestamp">${toolsT('generated_label', null, 'Generated:')} ${new Date().toLocaleString()}</p>
                    <hr>
                    ${resultElement.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
            
            Toast.info(toolsT('printing', null, 'Opening print dialog...'), 2000);
        }

        // Show welcome toast on page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                Toast.info(toolsT('tip_shortcuts', null, 'Tip: Press Ctrl+K to search, or use number keys 1-9 to switch tools'), 10000);
            }, 1000);
        });

        // ============================================
        // COPY TO CLIPBOARD HELPER
        // ============================================
        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    Toast.success(toolsT('copied', null, 'Copied to clipboard!'), 2000);
                }).catch(() => {
                    Toast.error(toolsT('copy_failed', null, 'Failed to copy to clipboard'), 3000);
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    Toast.success(toolsT('copied', null, 'Copied to clipboard!'), 2000);
                } catch (err) {
                    Toast.error(toolsT('copy_failed', null, 'Failed to copy to clipboard'), 3000);
                }
                document.body.removeChild(textarea);
            }
        }
    </script>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Panel -->
    <nav class="sidebar" id="sidebar" aria-label="Mobile navigation">
        <div class="sidebar-header">
            <a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-logo-link">
                <i class="fas fa-microchip"></i> Maroc PC
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="<?php i18n_e('tools.close_menu', [], 'Close menu'); ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-search">
            <input type="text" placeholder="<?php i18n_e('tools.search_components_placeholder', [], 'Search components...'); ?>" aria-label="<?php i18n_e('tools.search_products', [], 'Search products'); ?>" />
            <button aria-label="<?php i18n_e('tools.search_shortcut', [], 'Search'); ?>">
                <i class="fas fa-search" style="color:#000;"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <li><a href="<?= htmlspecialchars(i18n_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-home"></i> <?php i18n_e('nav.home'); ?></a></li>
            <li><a href="<?= htmlspecialchars(i18n_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-box"></i> <?php i18n_e('nav.products'); ?></a></li>
            <li class="sidebar-dropdown">
                <button class="sidebar-link sidebar-toggle-btn" aria-expanded="false">
                    <i class="fas fa-tools"></i>
                    <?php i18n_e('nav.builder_tools'); ?>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.pc_build_wizard'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=gaming-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.gaming_pc_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('laptop-finder.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.laptop_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=psu-calculator'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.psu_calculator'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('builder.php?tab=memory-finder'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.memory_finder'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('tools.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.tools_cockpit'); ?></a></li>
                </ul>
            </li>
            <li class="sidebar-dropdown">
                <button class="sidebar-link sidebar-toggle-btn" aria-expanded="false">
                    <i class="fas fa-th"></i>
                    <?php i18n_e('nav.categories'); ?>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <ul class="sidebar-submenu">
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=processors'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.processors'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=graphics'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.graphics_cards'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=memory'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.memory_ram'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=storage'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.storage'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=motherboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.motherboards'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=cooling'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.cooling'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=power'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.power_supplies'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=cases'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.pc_cases'); ?></a></li>
                    <li><a href="<?= htmlspecialchars(i18n_url('products.php?category=accessories'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-sublink"><?php i18n_e('nav.accessories'); ?></a></li>
                </ul>
            </li>

            <a href="<?= htmlspecialchars(i18n_url('index.php#deals'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-percent"></i> <?php i18n_e('nav.special_deals'); ?></a>
            <a href="<?= htmlspecialchars(i18n_url('account.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-user-circle"></i> <?php i18n_e('nav.my_account'); ?></a>
            <li>
                <a href="<?= htmlspecialchars(i18n_url('cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link">
                    <i class="fas fa-shopping-cart"></i> <?php i18n_e('nav.cart'); ?>
                    <span class="sidebar-cart-badge" id="sidebarCartCount">0</span>
                </a>
            </li>
            <li><a href="<?= htmlspecialchars(i18n_url('index.php#contact'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i class="fas fa-envelope"></i> <?php i18n_e('nav.contact'); ?></a></li>
        </ul>
    </nav>

    <script>
        // Custom file input handler
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('productImageInput');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const fileChosen = this.closest('.custom-file-input').querySelector('.file-chosen');
                    if (this.files && this.files.length > 0) {
                        fileChosen.textContent = this.files[0].name;
                        fileChosen.classList.add('has-file');
                    } else {
                        fileChosen.textContent = fileChosen.getAttribute('data-placeholder') || '<?php i18n_e('tools.no_file_chosen'); ?>';
                        fileChosen.classList.remove('has-file');
                    }
                });
            }
        });
    </script>
</body>
</html>
