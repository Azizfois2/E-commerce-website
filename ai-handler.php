<?php
declare(strict_types=1);

/**
 * AI Chatbot Gateway Handler
 * Delegated directly to modern OOP components.
 */

// Load single bootstrapper
require_once 'bootstrap.php';

use MarocPC\Chatbot\RequestParser;
use MarocPC\Chatbot\IntentClassifier;
use MarocPC\Chatbot\ProductRepository;
use MarocPC\Chatbot\ResponseGenerator;
use MarocPC\Chatbot\ChatbotController;

try {
    // Instantiate chatbot orchestration engine
    $controller = new ChatbotController(
        new RequestParser(),
        new IntentClassifier(),
        new ProductRepository(db()),
        new ResponseGenerator()
    );

    // Coordinate parsing, intent classification, and DB execution
    $controller->handleRequest();

} catch (\Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'response' => "Oops, I encountered a brief system hiccup! Please let me know and try again.",
        'delay_ms' => 500,
        'error'    => $e->getMessage()
    ]);
    exit;
}
