<?php
/**
 * db-diagnostic.php
 *
 * Standalone database connection diagnostic — made for InfinityFree.
 *
 * === USAGE ===
 * 1. Upload this file to your server (e.g. https://marocpc.rf.gd/api/db-diagnostic.php)
 * 2. Visit it with the key:  https://marocpc.rf.gd/api/db-diagnostic.php?key=YOUR_DB_PASS
 *    (Replace YOUR_DB_PASS with the actual DB_PASS value from your .env file)
 * 3. Copy the output and send it to Codebuff for analysis.
 * 4. DELETE THIS FILE from the server when done — it reveals database info!
 *
 * == SECURITY ==
 * - Requires a ?key= parameter that must match the DB_PASS from .env
 * - If ?key= is wrong or missing, nothing sensitive is shown
 * - Still, DELETE this file after use
 */

// ── PHP 7.2 polyfills (InfinityFree runs PHP 7.2) ─────────
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len <= strlen($haystack) && substr($haystack, -$len) === $needle;
    }
}

// ── Mini .env loader (self-contained, no project deps) ─────
function diagLoadEnv(string $path): array {
    $vars = [];
    if (!is_readable($path)) {
        return $vars;
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $vars;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        if ($name === '') {
            continue;
        }
        $value = trim($value);
        // Strip surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $vars[$name] = $value;
    }
    return $vars;
}

// ── Determine .env path ──────────────────────────────────────
$searchPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    __DIR__ . '/.env',
    getenv('DOCUMENT_ROOT') ? getenv('DOCUMENT_ROOT') . '/.env' : null,
    isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/.env' : null,
];

$envPath = null;
foreach ($searchPaths as $p) {
    if ($p !== null && is_readable($p)) {
        $envPath = $p;
        break;
    }
}

// ── Auth check ───────────────────────────────────────────────
// Two ways to authenticate:
//   A. ?key=DB_PASS    — Use the actual database password (from .env)
//   B. DIAG_KEY in .env — Set a separate DIAG_KEY=debug123 in .env and use ?key=debug123
//
// Option B is safer — you don't expose the DB password in your browser URL/history.
// If DIAG_KEY is set in .env, it takes precedence over DB_PASS.

$envVars = $envPath ? diagLoadEnv($envPath) : [];
$expectedKey = $envVars['DIAG_KEY'] ?? $envVars['DB_PASS'] ?? '';
$givenKey = isset($_GET['key']) ? trim($_GET['key']) : '';

if ($expectedKey === '' || $givenKey !== $expectedKey) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== DB DIAGNOSTIC ===\n\n";
    if ($expectedKey === '') {
        echo "ERROR: Could not find DB_PASS or DIAG_KEY in .env file.\n";
        echo "Checked paths:\n";
        foreach ($searchPaths as $p) {
            echo "  - " . ($p ?? '(null)') . " => " . (($p !== null && is_readable($p)) ? 'READABLE' : 'not found') . "\n";
        }
        echo "\nTo fix, add DIAG_KEY=debug123 to your .env file and visit with ?key=debug123\n";
    } else {
        $usingDbPass = !isset($envVars['DIAG_KEY']) || $envVars['DIAG_KEY'] === '';
        if ($usingDbPass) {
            echo "Access denied. Pass ?key=YOUR_DB_PASS from .env to run this diagnostic.\n";
            echo "Or set DIAG_KEY=debug123 in .env and use ?key=debug123 instead (safer).\n";
        } else {
            echo "Access denied. Pass ?key=YOUR_DIAG_KEY (set in .env) to run this diagnostic.\n";
        }
    }
    echo "\nNote: If your key contains special chars (&, #, =), URL-encode them first.\n";
    exit;
}

// ── Authenticated: run full diagnostic ──────────────────────
header('Content-Type: text/plain; charset=utf-8');

echo "============================================\n";
echo "  MAROC PC — DATABASE DIAGNOSTIC\n";
echo "============================================\n\n";

echo "--- SERVER ---\n";
echo "PHP version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Document root: " . (getenv('DOCUMENT_ROOT') ?: 'unknown') . "\n";
echo "Script path: " . __FILE__ . "\n";
echo "Time: " . date('Y-m-d H:i:s') . " (server timezone)\n\n";

echo "--- .ENV FILE ---\n";
if ($envPath) {
    echo "Loaded from: $envPath\n";
    echo "File exists: " . (is_file($envPath) ? 'YES' : 'NO') . "\n";
    echo "File readable: " . (is_readable($envPath) ? 'YES' : 'NO') . "\n";
    echo "File size: " . (is_file($envPath) ? filesize($envPath) . ' bytes' : 'N/A') . "\n\n";

    // Show DB-related env vars (mask password partially)
    echo "DB_HOST: " . ($envVars['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "DB_PORT: " . ($envVars['DB_PORT'] ?? 'NOT SET (default 3306)') . "\n";
    echo "DB_NAME: " . ($envVars['DB_NAME'] ?? 'NOT SET') . "\n";
    echo "DB_USER: " . ($envVars['DB_USER'] ?? 'NOT SET') . "\n";
    $pass = $envVars['DB_PASS'] ?? '';
    echo "DB_PASS: " . ($pass !== '' ? substr($pass, 0, 3) . '****' : 'NOT SET') . "\n";
    echo "DB_CHARSET: " . ($envVars['DB_CHARSET'] ?? 'NOT SET (default utf8mb4)') . "\n";
} else {
    echo "ERROR: No .env file found!\n";
    echo "Checked paths:\n";
    foreach ($searchPaths as $p) {
        echo "  - " . ($p ?? '(null)') . " => " . (($p !== null && is_readable($p)) ? 'READABLE' : 'not found') . "\n";
    }
    echo "\nCreate a .env file with DB_HOST, DB_NAME, DB_USER, DB_PASS and upload it.\n";
    exit;
}

echo "\n--- PHP EXTENSIONS ---\n";
$exts = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'fileinfo'];
foreach ($exts as $ext) {
    echo "  $ext: " . (extension_loaded($ext) ? '✓ LOADED' : '✗ MISSING') . "\n";
}

echo "\n--- PDO DRIVERS ---\n";
$drivers = PDO::getAvailableDrivers();
if (empty($drivers)) {
    echo "  NONE AVAILABLE — PDO is installed but no drivers found!\n";
} else {
    foreach ($drivers as $d) {
        echo "  - $d\n";
    }
    if (!in_array('mysql', $drivers, true)) {
        echo "\n  WARNING: 'mysql' driver not found. Cannot connect to MySQL.\n";
        echo "  Install php-mysql or php-pdo-mysql on your server.\n";
    }
}

echo "\n--- DNS LOOKUP ---\n";
$host = $envVars['DB_HOST'] ?? 'localhost';
$ip = @gethostbyname($host);
if ($ip === $host) {
    echo "  Could not resolve '$host' — might be an IP already or DNS failure.\n";
} else {
    echo "  $host resolves to: $ip\n";
}

echo "\n--- CONNECTION TEST (TCP) ---\n";
$port = isset($envVars['DB_PORT']) ? (int) $envVars['DB_PORT'] : 3306;
$errno = 0;
$errstr = '';
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if ($socket) {
    echo "  ✓ TCP connection to $host:$port SUCCEEDED\n";
    fclose($socket);
} else {
    echo "  ✗ TCP connection to $host:$port FAILED\n";
    echo "    Error ($errno): $errstr\n";
    echo "  Common causes:\n";
    echo "    - DB hostname is wrong for InfinityFree (check your control panel)\n";
    echo "    - DB port is not 3306 (InfinityFree sometimes uses non-standard ports)\n";
    echo "    - Remote MySQL access is disabled in cPanel (enable it)\n";
    echo "    - Firewall is blocking outbound MySQL connections\n";
}

echo "\n--- PDO CONNECTION TEST ---\n";
$dsn = 'mysql:host=' . $host . ';dbname=' . ($envVars['DB_NAME'] ?? '') . ';charset=' . ($envVars['DB_CHARSET'] ?? 'utf8mb4');
if (!empty($envVars['DB_PORT'])) {
    $dsn = 'mysql:host=' . $host . ';port=' . (int) $envVars['DB_PORT'] . ';dbname=' . ($envVars['DB_NAME'] ?? '') . ';charset=' . ($envVars['DB_CHARSET'] ?? 'utf8mb4');
}
echo "  DSN: $dsn\n";

$pdo = null;
try {
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $envVars['DB_USER'] ?? '', $envVars['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $elapsed = round((microtime(true) - $startTime) * 1000);
    echo "  ✓ PDO connection SUCCEEDED ({$elapsed}ms)\n\n";

    echo "--- DATABASE INFO ---\n";
    $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $clientInfo = $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
    echo "  MySQL server: $serverInfo\n";
    echo "  MySQL client: $clientInfo\n";

    // List tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "\n  Tables in database (" . count($tables) . "):\n";
    foreach ($tables as $t) {
        echo "    - $t\n";
    }

    // Check critical tables
    echo "\n  Critical table checks:\n";
    $criticalTables = ['products', 'laptops', 'Client', 'orders', 'admin_users', 'admin_activity'];
    foreach ($criticalTables as $t) {
        if (in_array($t, $tables, true)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "    ✓ '$t' — $count row(s)\n";
        } else {
            echo "    ✗ '$t' — MISSING\n";
        }
    }

    // Check character set
    echo "\n  Connection charset:\n";
    $charsetInfo = $pdo->query('SHOW VARIABLES LIKE "character_set_connection"')->fetch();
    echo "    connection: " . ($charsetInfo['Value'] ?? 'unknown') . "\n";

} catch (PDOException $e) {
    $elapsed = round((microtime(true) - $startTime) * 1000);
    echo "  ✗ PDO connection FAILED ({$elapsed}ms)\n";
    echo "    Error code: " . $e->getCode() . "\n";
    echo "    Error message: " . $e->getMessage() . "\n\n";

    echo "--- COMMON FIXES ---\n";
    $msg = $e->getMessage();
    if (str_contains($msg, 'Unknown database')) {
        echo "  1. The database name ('" . ($envVars['DB_NAME'] ?? '') . "') does not exist.\n";
        echo "     → In InfinityFree cPanel, check 'MySQL Databases' tab.\n";
        echo "     → The real DB name is usually: if0_YOUR_ACCOUNT_dbname\n";
    } elseif (str_contains($msg, 'Access denied for user')) {
        echo "  1. DB_USER or DB_PASS is wrong.\n";
        echo "     → In InfinityFree cPanel → MySQL Databases, check/create the user.\n";
        echo "     → Make sure the user is assigned to the database with ALL PRIVILEGES.\n";
    } elseif (str_contains($msg, 'Connection refused') || str_contains($msg, 'No route to host')) {
        echo "  1. The DB host or port is wrong, or remote MySQL access is blocked.\n";
        echo "     → In InfinityFree cPanel → Remote MySQL, add your domain (marocpc.rf.gd).\n";
        echo "     → Check if the hostname has changed (sometimes InfinityFree rotates servers).\n";
    } elseif (str_contains($msg, 'getaddrinfo failed') || str_contains($msg, 'Name or service not known')) {
        echo "  1. The DB_HOST name cannot be resolved.\n";
        echo "     → Double-check the hostname in InfinityFree cPanel → MySQL Databases.\n";
        echo "     → Try using the IP address instead of the hostname.\n";
    } else {
        echo "  1. Check that the database credentials in .env match InfinityFree cPanel.\n";
        echo "  2. Verify the DB hostname is correct (check cPanel → MySQL Databases).\n";
        echo "  3. Enable 'Remote MySQL' in cPanel and add your domain.\n";
        echo "  4. If recently changed, DNS may have not propagated yet (wait up to 24h).\n";
    }
    echo "\n  2. Still stuck? Copy this full output and send to Codebuff for analysis.\n";
}

echo "\n--- PROJECT BOOTSTRAP TEST ---\n";
$bootstrapPath = __DIR__ . '/../bootstrap.php';
$altBootstrap = __DIR__ . '/../src/bootstrap/application.php';

if (is_readable($bootstrapPath)) {
    echo "  bootstrap.php found at: $bootstrapPath\n";
    // Check if the bootstrap has PHP 8.0+ functions that would fail on PHP 7.2
    $content = @file_get_contents($bootstrapPath);
    if ($content !== false) {
        if (str_contains($content, 'str_contains') || str_contains($content, 'str_starts_with') || str_contains($content, 'str_ends_with')) {
            if (PHP_VERSION_ID < 80000) {
                echo "  ⚠ WARNING: bootstrap.php uses PHP 8.0+ functions (str_contains etc.).\n";
                echo "    Your PHP " . phpversion() . " needs polyfills for these.\n";
                echo "    Check that src/bootstrap/env-bootstrap.php has the polyfill functions.\n";
            }
        }
    }
} else {
    echo "  bootstrap.php NOT found at: $bootstrapPath\n";
}
if (is_readable($altBootstrap)) {
    echo "  application.php found at: $altBootstrap\n";
}

echo "\n============================================\n";
echo "  DIAGNOSTIC COMPLETE\n";
echo "============================================\n";
echo "  Copy this full output and paste it to Codebuff.\n";
echo "  IMPORTANT: DELETE this file when done!\n";
echo "============================================\n";
