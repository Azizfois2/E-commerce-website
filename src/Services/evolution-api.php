<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

function evolutionSendText(string $phone, string $message, ?string &$error = null): bool
{
    $error = null;
    if (!defined('EVOLUTION_API_KEY') || EVOLUTION_API_KEY === '') {
        $error = 'Evolution API key is not configured.';
        return false;
    }

    $payload = json_encode([
        'number' => ltrim($phone, '+'),
        'text' => $message,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        $error = 'Could not encode Evolution payload.';
        return false;
    }

    $apiUrl = rtrim(EVOLUTION_API_URL, '/') . '/message/sendText/' . rawurlencode(EVOLUTION_INSTANCE_NAME);
    return evolutionPostJson($apiUrl, $payload, $error);
}

function evolutionPostJson(string $url, string $payload, ?string &$error = null): bool
{
    $systemCurl = evolutionPostJsonWithSystemCurl($url, $payload, $error);
    if ($systemCurl !== null) {
        return $systemCurl;
    }

    return evolutionPostJsonWithPhpCurl($url, $payload, $error);
}

function evolutionPostJsonWithSystemCurl(string $url, string $payload, ?string &$error = null): ?bool
{
    $curl = evolutionSystemCurlPath();
    if ($curl === null || !function_exists('proc_open')) {
        return null;
    }

    $base = tempnam(sys_get_temp_dir(), 'evo_');
    if ($base === false) {
        return null;
    }

    $payloadFile = $base . '.json';
    $configFile = $base . '.curl';

    $payloadWritten = file_put_contents($payloadFile, $payload);
    if ($payloadWritten === false) {
        @unlink($base);
        return null;
    }

    $config = implode(PHP_EOL, [
        'url = ' . evolutionCurlQuote($url),
        'request = "POST"',
        'header = ' . evolutionCurlQuote('apikey: ' . EVOLUTION_API_KEY),
        'header = "Content-Type: application/json"',
        'data-binary = ' . evolutionCurlQuote('@' . str_replace('\\', '/', $payloadFile)),
        'silent',
        'show-error',
        'write-out = "\\n%{http_code}"',
    ]) . PHP_EOL;

    $configWritten = file_put_contents($configFile, $config);
    if ($configWritten === false) {
        @unlink($base);
        @unlink($payloadFile);
        return null;
    }

    $cmd = escapeshellarg($curl)
        . ' --connect-timeout 5 --max-time 15 --config '
        . escapeshellarg($configFile);
    $pipes = [];
    $process = proc_open($cmd, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);

    if (!is_resource($process)) {
        @unlink($base);
        @unlink($payloadFile);
        @unlink($configFile);
        return null;
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    @unlink($base);
    @unlink($payloadFile);
    @unlink($configFile);

    if ($exitCode !== 0) {
        $error = trim((string) $stderr) ?: 'System curl exited with code ' . $exitCode;
        return false;
    }

    $stdout = (string) $stdout;
    $splitAt = strrpos($stdout, "\n");
    $body = $splitAt === false ? '' : substr($stdout, 0, $splitAt);
    $status = $splitAt === false ? 0 : (int) trim(substr($stdout, $splitAt + 1));

    if ($status >= 200 && $status < 300) {
        return true;
    }

    $error = 'HTTP ' . $status . ': ' . trim($body);
    return false;
}

function evolutionPostJsonWithPhpCurl(string $url, string $payload, ?string &$error = null): bool
{
    if (!function_exists('curl_init')) {
        $error = 'Neither system curl nor PHP cURL is available.';
        return false;
    }

    $ch = curl_init($url);
    $options = [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . EVOLUTION_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ];
    if (defined('CURL_IPRESOLVE_V4')) {
        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        return true;
    }

    $error = $err ?: ('HTTP ' . $status . ': ' . (string) $raw);
    return false;
}

function evolutionSystemCurlPath(): ?string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $path = getenv('SystemRoot') ?: 'C:\\Windows';
        $candidate = rtrim($path, '\\/') . '\\System32\\curl.exe';
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return 'curl';
}

function evolutionCurlQuote(string $value): string
{
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}
