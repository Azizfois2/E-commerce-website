<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$sqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'add-cn-budget-products.sql';
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    fwrite(STDERR, "Unable to read {$sqlFile}\n");
    exit(1);
}

db()->exec($sql);

$count = db()
    ->query('SELECT COUNT(*) FROM products WHERE id BETWEEN 31 AND 39')
    ->fetchColumn();

echo "CN budget products present: {$count}\n";
