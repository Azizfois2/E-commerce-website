<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = db();
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN xbox_id VARCHAR(255) DEFAULT NULL UNIQUE");
    echo "Column xbox_id added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column xbox_id already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}
