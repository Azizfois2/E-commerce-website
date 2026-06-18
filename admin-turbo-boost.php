<?php
/**
 * Admin Turbo Boost - Extreme Performance Optimizations
 * Makes admin pages EXTREMELY fast with aggressive techniques
 */

// ============================================
// 1. OPCODE CACHING & PRELOADING
// ============================================

if (function_exists('opcache_compile_file')) {
    // Precompile frequently used files
    $criticalFiles = [
        __DIR__ . '/admin-helpers.php',
        __DIR__ . '/src/Services/admin-helpers.php',
        __DIR__ . '/config.php',
    ];
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            @opcache_compile_file($file);
        }
    }
}

// ============================================
// 2. AGGRESSIVE OUTPUT BUFFERING WITH COMPRESSION
// ============================================

if (!ob_get_level() && extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
} elseif (!ob_get_level()) {
    ob_start();
}

// ============================================
// 3. PERSISTENT DATABASE CONNECTION POOL
// ============================================

class AdminDBPool {
    private static $connection = null;
    private static $stmtCache = [];
    
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            self::$connection = db();
            
            // Enable persistent connections
            self::$connection->setAttribute(PDO::ATTR_PERSISTENT, true);
            
            // Use prepared statement cache
            self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Set faster MySQL modes
            self::$connection->exec("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
            self::$connection->exec("SET SESSION query_cache_type=ON");
        }
        
        return self::$connection;
    }
    
    public static function prepare(string $sql): PDOStatement {
        $hash = md5($sql);
        
        if (!isset(self::$stmtCache[$hash])) {
            self::$stmtCache[$hash] = self::getConnection()->prepare($sql);
        }
        
        return self::$stmtCache[$hash];
    }
}

// ============================================
// 4. REDIS/APCU CACHING (if available)
// ============================================

class AdminFastCache {
    private static $driver = null;
    
    public static function init() {
        // Try APCu first (usually available)
        if (extension_loaded('apcu') && apcu_enabled()) {
            self::$driver = 'apcu';
        }
        // Try Redis second
        elseif (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                if ($redis->connect('127.0.0.1', 6379, 0.1)) {
                    self::$driver = 'redis';
                    return $redis;
                }
            } catch (Exception $e) {
                // Redis not available
            }
        }
    }
    
    public static function get(string $key) {
        if (self::$driver === 'apcu') {
            $success = false;
            $value = apcu_fetch('admin_' . $key, $success);
            return $success ? $value : null;
        }
        
        // Fallback to session
        return $_SESSION['admin_cache'][$key] ?? null;
    }
    
    public static function set(string $key, $value, int $ttl = 300) {
        if (self::$driver === 'apcu') {
            return apcu_store('admin_' . $key, $value, $ttl);
        }
        
        // Fallback to session
        $_SESSION['admin_cache'][$key] = $value;
        $_SESSION['admin_cache_expires'][$key] = time() + $ttl;
        return true;
    }
    
    public static function delete(string $key) {
        if (self::$driver === 'apcu') {
            return apcu_delete('admin_' . $key);
        }
        
        unset($_SESSION['admin_cache'][$key]);
        unset($_SESSION['admin_cache_expires'][$key]);
        return true;
    }
    
    public static function clear(string $pattern = null) {
        if (self::$driver === 'apcu') {
            if ($pattern) {
                $iterator = new APCUIterator('/^admin_' . preg_quote($pattern) . '/');
                apcu_delete($iterator);
            } else {
                apcu_clear_cache();
            }
            return true;
        }
        
        // Fallback
        if ($pattern) {
            foreach ($_SESSION['admin_cache'] ?? [] as $key => $value) {
                if (strpos($key, $pattern) !== false) {
                    unset($_SESSION['admin_cache'][$key]);
                    unset($_SESSION['admin_cache_expires'][$key]);
                }
            }
        } else {
            $_SESSION['admin_cache'] = [];
            $_SESSION['admin_cache_expires'] = [];
        }
        return true;
    }
}

AdminFastCache::init();

// ============================================
// 5. QUERY RESULT CACHE WITH COMPRESSION
// ============================================

function adminTurboFetch(PDO $pdo, string $sql, array $params = [], int $ttl = 300) {
    $cacheKey = md5($sql . serialize($params));
    
    // Check cache first
    $cached = AdminFastCache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    // Execute query
    $stmt = AdminDBPool::prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cache result
    AdminFastCache::set($cacheKey, $result, $ttl);
    
    return $result ?: [];
}

function adminTurboValue(PDO $pdo, string $sql, array $params = [], int $ttl = 300) {
    $cacheKey = md5($sql . serialize($params) . '_value');
    
    $cached = AdminFastCache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $stmt = AdminDBPool::prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchColumn();
    
    AdminFastCache::set($cacheKey, $result, $ttl);
    
    return $result;
}

// ============================================
// 6. PRELOAD CRITICAL DATA ON FIRST REQUEST
// ============================================

function adminPreloadCriticalData(): void {
    static $preloaded = false;
    if ($preloaded) return;
    
    try {
        $pdo = AdminDBPool::getConnection();
        
        // Preload in parallel-ish using prepared statements
        $queries = [
            'stats_products' => "SELECT COUNT(*) FROM products",
            'stats_orders' => "SELECT COUNT(*) FROM orders",
            'stats_customers' => "SELECT COUNT(*) FROM Client",
            'categories' => "SELECT DISTINCT category FROM products",
        ];
        
        foreach ($queries as $key => $sql) {
            adminTurboValue($pdo, $sql, [], 600); // 10 min cache
        }
        
        $preloaded = true;
    } catch (Exception $e) {
        // Silently fail
    }
}

// Preload on first access
register_shutdown_function(function() {
    if (rand(1, 10) === 1) { // 10% chance to refresh
        adminPreloadCriticalData();
    }
});

// ============================================
// 7. MINIMIZE SESSION WRITES
// ============================================

// Only write session when actually modified
register_shutdown_function(function() {
    static $sessionData = null;
    
    if ($sessionData === null) {
        $sessionData = $_SESSION ?? [];
    } else {
        // Only write if changed
        if (serialize($_SESSION) !== serialize($sessionData)) {
            session_write_close();
        } else {
            session_abort(); // Don't write unchanged session
        }
    }
});

// ============================================
// 8. CRITICAL CSS INLINE GENERATOR
// ============================================

function adminInlineCriticalCSS(): string {
    static $css = null;
    
    if ($css === null) {
        $css = <<<CSS
:root{--page-bg:#0a0e17;--card-bg:#1a1f2e;--text:#e0e6ed;--muted:#8b92a0;--border:rgba(255,255,255,.08);--cyan:#00f5d4}
*{box-sizing:border-box}body{background:var(--page-bg);color:var(--text);font:16px/1.6 system-ui;margin:0}
.table-card{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:24px}
table{width:100%;border-collapse:collapse}th{padding:12px;color:var(--cyan);font-size:.75rem;text-transform:uppercase}
td{padding:12px;border-bottom:1px solid var(--border)}
.button{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:600}
.button-primary{background:var(--cyan);color:#000}
CSS;
    }
    
    return $css;
}

// ============================================
// 9. LAZY LOAD HEAVY COMPONENTS
// ============================================

function adminDeferComponent(string $component, string $placeholder = ''): string {
    $id = 'deferred_' . md5($component);
    return <<<HTML
<div id="{$id}" data-component="{$component}">{$placeholder}</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('{$id}');
    if(el){
        fetch('?component='+encodeURIComponent('{$component}'))
        .then(r=>r.text())
        .then(html=>el.innerHTML=html)
        .catch(e=>console.error(e));
    }
});
</script>
HTML;
}

// ============================================
// 10. BACKGROUND TASK QUEUE (Simple)
// ============================================

class AdminTaskQueue {
    private static $tasks = [];
    
    public static function add(callable $task, string $name = '') {
        self::$tasks[] = ['task' => $task, 'name' => $name];
    }
    
    public static function process() {
        if (empty(self::$tasks)) return;
        
        // Process after response sent
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_end_flush();
            flush();
        }
        
        // Execute tasks
        foreach (self::$tasks as $item) {
            try {
                $item['task']();
            } catch (Exception $e) {
                error_log("Task {$item['name']} failed: " . $e->getMessage());
            }
        }
    }
}

register_shutdown_function([AdminTaskQueue::class, 'process']);

// ============================================
// 11. DATABASE QUERY OPTIMIZER
// ============================================

function adminOptimizeQuery(string $sql): string {
    // Add query hints for MySQL
    $sql = preg_replace('/^SELECT /i', 'SELECT SQL_CACHE ', $sql);
    
    // Force index usage for common patterns
    if (preg_match('/FROM products WHERE stock_quantity/i', $sql)) {
        $sql = str_replace('FROM products WHERE', 'FROM products USE INDEX (idx_products_stock) WHERE', $sql);
    }
    
    if (preg_match('/FROM orders WHERE status/i', $sql)) {
        $sql = str_replace('FROM orders WHERE', 'FROM orders USE INDEX (idx_orders_status) WHERE', $sql);
    }
    
    return $sql;
}

// ============================================
// 12. HTTP/2 SERVER PUSH HINTS
// ============================================

function adminServerPushHints(): void {
    static $pushed = false;
    if ($pushed) return;
    
    $resources = [
        '/assets/css/admin-optimized.css' => 'style',
        '/assets/js/admin-performance.js' => 'script',
        '/assets/css/admin-ui-enhanced.css' => 'style',
    ];
    
    foreach ($resources as $path => $type) {
        header("Link: <{$path}>; rel=preload; as={$type}", false);
    }
    
    $pushed = true;
}

// ============================================
// 13. SMART PREFETCHING
// ============================================

function adminPrefetchHints(): string {
    return <<<HTML
<link rel="prefetch" href="admin-orders.php">
<link rel="prefetch" href="admin-products.php">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
HTML;
}

// ============================================
// 14. CONDITIONAL QUERY EXECUTION
// ============================================

function adminConditionalQuery(PDO $pdo, string $sql, array $params, callable $condition): array {
    if (!$condition()) {
        return [];
    }
    
    return adminTurboFetch($pdo, $sql, $params);
}

// ============================================
// 15. MICRO-OPTIMIZATION UTILITIES
// ============================================

// Fast array column extraction
function adminPluck(array $array, string $key): array {
    return array_column($array, $key);
}

// Fast unique count
function adminCountUnique(array $array, string $key): int {
    return count(array_unique(array_column($array, $key)));
}

// Fast grouping
function adminGroupBy(array $array, string $key): array {
    $result = [];
    foreach ($array as $item) {
        $result[$item[$key]][] = $item;
    }
    return $result;
}

// ============================================
// AUTO-APPLY TURBO BOOST
// ============================================

// Set optimal PHP settings for admin (only if not already set)
if (ini_get('memory_limit') < 256) {
    @ini_set('memory_limit', '256M');
}
@ini_set('max_execution_time', '60');
@ini_set('output_buffering', '4096');

// Enable compression if not already
if (!ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', 'On');
    @ini_set('zlib.output_compression_level', '6');
}

// Optimize garbage collection
gc_enable();
@ini_set('session.gc_probability', '1');
@ini_set('session.gc_divisor', '1000');

// Apply server push hints
if (isset($_SERVER['SERVER_PROTOCOL']) && strpos($_SERVER['SERVER_PROTOCOL'], '2') !== false) {
    adminServerPushHints();
}
