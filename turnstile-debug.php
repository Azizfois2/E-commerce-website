<?php
/**
 * Cloudflare Turnstile Debug & Test Page
 * Use this page to diagnose Turnstile integration issues
 */

require_once 'config.php';

$testResults = [];
$testToken = $_POST['cf-turnstile-response'] ?? null;
$testPerformed = $_SERVER['REQUEST_METHOD'] === 'POST';

// Test 1: Check if constants are defined
$testResults['constants'] = [
    'site_key_defined' => defined('TURNSTILE_SITE_KEY'),
    'site_key_value' => defined('TURNSTILE_SITE_KEY') ? (TURNSTILE_SITE_KEY !== '' ? substr(TURNSTILE_SITE_KEY, 0, 10) . '...' : '[EMPTY]') : '[NOT DEFINED]',
    'secret_key_defined' => defined('TURNSTILE_SECRET_KEY'),
    'secret_key_value' => defined('TURNSTILE_SECRET_KEY') ? (TURNSTILE_SECRET_KEY !== '' ? substr(TURNSTILE_SECRET_KEY, 0, 10) . '...' : '[EMPTY]') : '[NOT DEFINED]',
];

// Test 2: Check server connectivity to Cloudflare
$testResults['connectivity'] = [
    'status' => 'unknown',
    'message' => '',
];

try {
    $ch = curl_init('https://challenges.cloudflare.com/cdn-cgi/trace');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $testResults['connectivity']['status'] = 'error';
        $testResults['connectivity']['message'] = "cURL Error: $curlError";
    } elseif ($httpCode === 200) {
        $testResults['connectivity']['status'] = 'success';
        $testResults['connectivity']['message'] = "Successfully connected to Cloudflare (HTTP $httpCode)";
    } else {
        $testResults['connectivity']['status'] = 'warning';
        $testResults['connectivity']['message'] = "Unexpected HTTP code: $httpCode";
    }
} catch (Exception $e) {
    $testResults['connectivity']['status'] = 'error';
    $testResults['connectivity']['message'] = "Exception: " . $e->getMessage();
}

// Test 3: Test actual token verification if submitted
if ($testPerformed && $testToken) {
    $testResults['verification'] = [
        'token_received' => true,
        'token_length' => strlen($testToken),
        'token_preview' => substr($testToken, 0, 30) . '...',
    ];
    
    // Perform verification
    $verifyResult = verifyTurnstile($testToken);
    $testResults['verification']['result'] = $verifyResult ? 'SUCCESS' : 'FAILED';
    
    // Check PHP error log for details
    $testResults['verification']['note'] = 'Check PHP error_log for detailed verification information';
} elseif ($testPerformed) {
    $testResults['verification'] = [
        'token_received' => false,
        'message' => 'No token was received from the widget. This indicates a frontend issue.',
    ];
}

// Test 4: Environment info
$testResults['environment'] = [
    'php_version' => phpversion(),
    'curl_enabled' => function_exists('curl_init'),
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnstile Debug & Test - Maroc PC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            background: #0a0e27;
            color: #e0e0e0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #00f5d4;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .subtitle {
            color: #888;
            margin-bottom: 40px;
            font-size: 0.9rem;
        }
        .test-section {
            background: #1a1f3a;
            border: 1px solid #2a3f5f;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .test-section h2 {
            color: #00f5d4;
            margin-bottom: 16px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-success { background: #00f5d4; color: #000; }
        .status-error { background: #ff3b5c; color: #fff; }
        .status-warning { background: #ff6b35; color: #fff; }
        .status-unknown { background: #555; color: #fff; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .info-item {
            background: #0f1628;
            padding: 12px;
            border-radius: 4px;
            border-left: 3px solid #00f5d4;
        }
        .info-label {
            color: #888;
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-value {
            color: #fff;
            font-size: 0.9rem;
            word-break: break-all;
        }
        .test-widget {
            background: #0f1628;
            padding: 32px;
            border-radius: 8px;
            text-align: center;
        }
        .test-btn {
            background: #00f5d4;
            color: #000;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-family: inherit;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .test-btn:hover {
            background: #00d4b8;
            transform: translateY(-2px);
        }
        .test-btn:disabled {
            background: #555;
            cursor: not-allowed;
            transform: none;
        }
        .console-log {
            background: #000;
            color: #0f0;
            padding: 16px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-top: 16px;
            max-height: 300px;
            overflow-y: auto;
        }
        .console-log pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        #turnstileErrorDisplay {
            color: #ff3b5c;
            margin-top: 16px;
            padding: 12px;
            background: rgba(255, 59, 92, 0.1);
            border-radius: 4px;
            display: none;
        }
    </style>
    <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <h1>🔒 Cloudflare Turnstile Debug Console</h1>
        <p class="subtitle">Comprehensive testing and diagnostics for Turnstile integration</p>

        <!-- Test 1: Configuration -->
        <div class="test-section">
            <h2>
                <span>1️⃣</span>
                Configuration Check
                <?php if ($testResults['constants']['site_key_defined'] && $testResults['constants']['secret_key_defined']): ?>
                    <span class="status-badge status-success">CONFIGURED</span>
                <?php else: ?>
                    <span class="status-badge status-error">MISSING</span>
                <?php endif; ?>
            </h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Site Key Status</div>
                    <div class="info-value"><?= $testResults['constants']['site_key_defined'] ? '✅ Defined' : '❌ Not Defined' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Site Key Value</div>
                    <div class="info-value"><?= htmlspecialchars($testResults['constants']['site_key_value']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Secret Key Status</div>
                    <div class="info-value"><?= $testResults['constants']['secret_key_defined'] ? '✅ Defined' : '❌ Not Defined' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Secret Key Value</div>
                    <div class="info-value"><?= htmlspecialchars($testResults['constants']['secret_key_value']) ?></div>
                </div>
            </div>
            <?php if (!$testResults['constants']['site_key_defined'] || !$testResults['constants']['secret_key_defined']): ?>
                <div class="console-log">
                    <pre>❌ ACTION REQUIRED: Add these lines to your config.php:

define('TURNSTILE_SITE_KEY', 'your_site_key_here');
define('TURNSTILE_SECRET_KEY', 'your_secret_key_here');

Get your keys from: https://dash.cloudflare.com/?to=/:account/turnstile</pre>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test 2: Connectivity -->
        <div class="test-section">
            <h2>
                <span>2️⃣</span>
                Server Connectivity
                <span class="status-badge status-<?= $testResults['connectivity']['status'] ?>">
                    <?= strtoupper($testResults['connectivity']['status']) ?>
                </span>
            </h2>
            <div class="info-item" style="margin-top: 16px;">
                <div class="info-label">Test Result</div>
                <div class="info-value"><?= htmlspecialchars($testResults['connectivity']['message']) ?></div>
            </div>
        </div>

        <!-- Test 3: Live Widget Test -->
        <div class="test-section">
            <h2>
                <span>3️⃣</span>
                Live Widget Test
                <?php if ($testPerformed): ?>
                    <?php if (isset($testResults['verification']['result'])): ?>
                        <span class="status-badge status-<?= $testResults['verification']['result'] === 'SUCCESS' ? 'success' : 'error' ?>">
                            <?= $testResults['verification']['result'] ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-error">NO TOKEN</span>
                    <?php endif; ?>
                <?php endif; ?>
            </h2>
            
            <?php if ($testPerformed && isset($testResults['verification'])): ?>
                <div class="info-grid">
                    <?php if ($testResults['verification']['token_received']): ?>
                        <div class="info-item">
                            <div class="info-label">Token Received</div>
                            <div class="info-value">✅ Yes</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Token Length</div>
                            <div class="info-value"><?= $testResults['verification']['token_length'] ?> characters</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Token Preview</div>
                            <div class="info-value"><?= htmlspecialchars($testResults['verification']['token_preview']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Verification Result</div>
                            <div class="info-value">
                                <?= $testResults['verification']['result'] === 'SUCCESS' ? '✅ PASSED' : '❌ FAILED' ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <div class="info-label">Error</div>
                            <div class="info-value">❌ <?= htmlspecialchars($testResults['verification']['message']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="console-log">
                    <pre><?= $testResults['verification']['note'] ?? 'Check server logs for detailed verification information' ?></pre>
                </div>
            <?php endif; ?>
            
            <div class="test-widget">
                <h3 style="color: #00f5d4; margin-bottom: 20px;">Test the Turnstile Widget</h3>
                <p style="color: #888; margin-bottom: 20px;">Complete the challenge and click "Run Test" to verify both frontend and backend</p>
                
                <form method="POST" id="testForm">
                    <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
                        <div class="cf-turnstile" 
                             data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>"
                             data-callback="onTurnstileSuccess"
                             data-error-callback="onTurnstileError"
                             data-expired-callback="onTurnstileExpired"
                             style="display: inline-block;"></div>
                        <div id="turnstileErrorDisplay"></div>
                        <button type="submit" class="test-btn" id="testBtn" disabled>Run Test</button>
                    <?php else: ?>
                        <p style="color: #ff3b5c;">⚠️ Turnstile keys not configured. Add them to config.php first.</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Test 4: Environment -->
        <div class="test-section">
            <h2>
                <span>4️⃣</span>
                Environment Information
            </h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">PHP Version</div>
                    <div class="info-value"><?= $testResults['environment']['php_version'] ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">cURL Enabled</div>
                    <div class="info-value"><?= $testResults['environment']['curl_enabled'] ? '✅ Yes' : '❌ No' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Server IP</div>
                    <div class="info-value"><?= htmlspecialchars($testResults['environment']['server_ip']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Client IP</div>
                    <div class="info-value"><?= htmlspecialchars($testResults['environment']['client_ip']) ?></div>
                </div>
            </div>
        </div>

        <!-- Console Logs -->
        <div class="test-section">
            <h2><span>📋</span> Browser Console Logs</h2>
            <div class="console-log" id="consoleOutput">
                <pre>Console output will appear here...</pre>
            </div>
        </div>
    </div>

    <script>
        // Capture console logs
        const consoleOutput = document.getElementById('consoleOutput');
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        
        const logs = [];
        
        function addLog(type, ...args) {
            const timestamp = new Date().toLocaleTimeString();
            const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)).join(' ');
            logs.push(`[${timestamp}] [${type}] ${message}`);
            consoleOutput.innerHTML = '<pre>' + logs.join('\n') + '</pre>';
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        console.log = function(...args) {
            addLog('LOG', ...args);
            originalLog.apply(console, args);
        };
        
        console.error = function(...args) {
            addLog('ERROR', ...args);
            originalError.apply(console, args);
        };
        
        console.warn = function(...args) {
            addLog('WARN', ...args);
            originalWarn.apply(console, args);
        };
        
        // Turnstile callbacks
        let tokenReceived = false;
        
        function onTurnstileSuccess(token) {
            tokenReceived = true;
            console.log('✅ Turnstile Success - Token received (length: ' + token.length + ')');
            console.log('Token preview: ' + token.substring(0, 30) + '...');
            
            document.getElementById('testBtn').disabled = false;
            document.getElementById('turnstileErrorDisplay').style.display = 'none';
        }
        
        function onTurnstileError(error) {
            tokenReceived = false;
            console.error('❌ Turnstile Error:', error);
            
            document.getElementById('testBtn').disabled = true;
            const errorDiv = document.getElementById('turnstileErrorDisplay');
            errorDiv.textContent = '❌ Widget error: ' + error;
            errorDiv.style.display = 'block';
        }
        
        function onTurnstileExpired() {
            tokenReceived = false;
            console.warn('⏰ Turnstile Expired - Token expired after 5 minutes');
            
            const errorDiv = document.getElementById('turnstileErrorDisplay');
            errorDiv.textContent = '⏰ Token expired - Widget will refresh automatically';
            errorDiv.style.display = 'block';
        }
        
        // Form submission validation
        document.getElementById('testForm')?.addEventListener('submit', function(e) {
            const token = document.querySelector('input[name="cf-turnstile-response"]')?.value;
            
            if (!token) {
                e.preventDefault();
                console.error('❌ Form blocked: No token present');
                alert('Please complete the Turnstile challenge first');
                return false;
            }
            
            console.log('✅ Form submitting with token');
        });
        
        console.log('🔒 Turnstile Debug Console initialized');
        console.log('📍 Current URL: ' + window.location.href);
        console.log('👤 User Agent: ' + navigator.userAgent);
    </script>
</body>
</html>
