<?php
declare(strict_types=1);

/**
 * Single application bootstrap (paths, env, session, DB helpers).
 * Safe to require multiple times — loads once.
 */
if (defined('MAROC_APP_BOOTSTRAPPED')) {
    return;
}

define('ROOT_PATH', __DIR__);
define('SRC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'src');

require_once SRC_PATH . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'env-bootstrap.php';

loadEnvFile(ROOT_PATH . DIRECTORY_SEPARATOR . '.env');

$envLocal = ROOT_PATH . DIRECTORY_SEPARATOR . '.env.local';
if (is_readable($envLocal)) {
    loadEnvFile($envLocal, true);
}

require_once SRC_PATH . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'application.php';

// Register a clean PSR-4 autoloader for our custom app classes
spl_autoload_register(function (string $class) {
    $prefix = 'MarocPC\\Chatbot\\';
    $baseDir = SRC_PATH . DIRECTORY_SEPARATOR . 'Chatbot' . DIRECTORY_SEPARATOR;
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

define('MAROC_APP_BOOTSTRAPPED', true);
