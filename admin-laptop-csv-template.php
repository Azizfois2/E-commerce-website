<?php
declare(strict_types=1);

require_once 'admin-helpers.php';
adminRequireAuth();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laptop_inventory_template.csv');

$output = fopen('php://output', 'w');

// Write headers
fputcsv($output, [
    'name',
    'brand',
    'price',
    'old_price',
    'image',
    'usage_category',
    'portability_tier',
    'screen_size',
    'screen_quality',
    'gpu_tier',
    'battery_wh',
    'weight_kg',
    'specs',
    'stock_quantity',
    'in_stock'
]);

// Write sample rows
fputcsv($output, [
    'ASUS ROG Zephyrus G16 OLED',
    'ASUS',
    '25999.00',
    '27999.00',
    'images/products/zephyrusg16.png',
    'gaming',
    'ultralight',
    '16.0',
    'oled',
    'dedicated',
    '90',
    '1.85',
    '{"CPU": "Intel Core Ultra 9", "RAM": "32GB", "Storage": "1TB SSD", "GPU": "RTX 4070"}',
    '10',
    '1'
]);

fputcsv($output, [
    'Dell XPS 13 9340',
    'Dell',
    '14999.00',
    '',
    'images/products/xps13.png',
    'student',
    'ultralight',
    '13.4',
    'standard',
    'integrated',
    '55',
    '1.19',
    '{"CPU": "Intel Core Ultra 5", "RAM": "16GB", "Storage": "512GB SSD", "GPU": "Intel Graphics"}',
    '8',
    '1'
]);

fclose($output);
exit();
