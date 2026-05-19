<?php

$VERIFY_TOKEN = "my_verify_token_123";

/*
|--------------------------------------------------------------------------
| WEBHOOK VERIFICATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(403);
    exit;
}

/*
|--------------------------------------------------------------------------
| RECEIVE MESSAGES
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = file_get_contents("php://input");

    // Save webhook payload
    file_put_contents(
        "webhook_log.txt",
        date('Y-m-d H:i:s') . PHP_EOL .
        $input . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);
    echo "EVENT_RECEIVED";
    exit;
}