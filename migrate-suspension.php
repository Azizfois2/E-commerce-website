<?php
require_once 'config.php';
$pdo = db();
try {
    $pdo->exec("ALTER TABLE Client ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN suspension_reason TEXT DEFAULT NULL");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
