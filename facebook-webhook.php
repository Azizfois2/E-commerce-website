<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * Facebook Webhook Handler
 * Handles:
 * 1. URL Verification (GET)
 * 2. Deauthorization / Data Deletion (POST)
 */

// --- 1. Verification Logic (For the "Verify" button in FB Dashboard) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $verifyToken = envString('FB_WEBHOOK_VERIFY_TOKEN', 'my_oauth_123');
    
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verifyToken) {
        echo $challenge;
        exit;
    }
    
    http_response_code(403);
    exit('Verification failed');
}

// --- 2. Event Handling Logic (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Logic for handling user removing the app (deauthorized)
    if (isset($data['entry'][0]['messaging'][0]['optin']['type']) && $data['entry'][0]['messaging'][0]['optin']['type'] === 'deauthorized') {
        $fbId = $data['entry'][0]['id'];
        // You could mark the user as inactive or log the event
        error_log("User $fbId has deauthorized the app.");
    }

    // Always return 200 OK to Facebook
    http_response_code(200);
    echo "EVENT_RECEIVED";
    exit;
}
