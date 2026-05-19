<?php
/**
 * api/rate-limiter.php — Lightweight API rate limiter using the api_rate_limits table.
 *
 * Include at the top of any API endpoint to enforce per-IP, per-endpoint rate limiting.
 * Allows 60 requests per IP per endpoint per 60-second sliding window.
 * Returns HTTP 429 if exceeded.
 */

function enforceRateLimit(int $maxRequests = 60, int $windowSeconds = 60): void
{
    try {
        $pdo = db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $endpoint = basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');
        $now = new DateTime();
        $windowExpiry = (clone $now)->modify("+{$windowSeconds} seconds")->format('Y-m-d H:i:s');

        // Clean up expired windows (lightweight, probabilistic — runs ~10% of requests)
        if (mt_rand(1, 10) === 1) {
            $pdo->prepare("DELETE FROM api_rate_limits WHERE window_expires_at < NOW()")->execute();
        }

        // Check if there's an active window for this IP + endpoint
        $stmt = $pdo->prepare("SELECT id, request_count, window_expires_at FROM api_rate_limits WHERE ip_address = ? AND endpoint = ? AND window_expires_at > NOW() ORDER BY window_expires_at DESC LIMIT 1");
        $stmt->execute([$ip, $endpoint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ((int) $row['request_count'] >= $maxRequests) {
                // Rate limit exceeded
                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $windowSeconds);
                echo json_encode([
                    'error' => 'Too many requests. Please slow down.',
                    'retry_after' => $windowSeconds,
                ]);
                exit;
            }
            // Increment counter
            $pdo->prepare("UPDATE api_rate_limits SET request_count = request_count + 1 WHERE id = ?")->execute([(int) $row['id']]);
        } else {
            // Create new window
            $pdo->prepare("INSERT INTO api_rate_limits (ip_address, endpoint, request_count, window_expires_at) VALUES (?, ?, 1, ?)")->execute([$ip, $endpoint, $windowExpiry]);
        }
    } catch (Throwable $e) {
        // Rate limiting should never block the actual request if the DB fails
    }
}

// Auto-enforce when included
enforceRateLimit();
