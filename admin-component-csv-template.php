<?php
declare(strict_types=1);

require_once 'admin-helpers.php';
adminRequireAuth();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=component_inventory_template.csv');

$output = fopen('php://output', 'w');

// Write headers
fputcsv($output, [
    'name',
    'brand',
    'category',
    'price',
    'old_price',
    'badge',
    'rating',
    'reviews',
    'image',
    'in_stock',
    'stock_quantity',
    'reorder_level',
    'specs'
]);

// Write sample rows
fputcsv($output, [
    'AMD Ryzen 7 7800X3D',
    'AMD',
    'cpu',
    '4299.00',
    '4499.00',
    'Gaming',
    '4.9',
    '2145',
    'images/products/ryzen7-7800x3d.png',
    '1',
    '15',
    '5',
    '{"Cores": "8 / 16 threads", "Boost Clock": "5.0 GHz", "TDP": "120 W", "Socket": "AM5"}'
]);

fputcsv($output, [
    'NVIDIA RTX 4080 Super',
    'NVIDIA',
    'gpu',
    '10999.00',
    '',
    'New',
    '4.8',
    '706',
    'images/products/rtx4080super.png',
    '1',
    '8',
    '3',
    '{"VRAM": "16 GB GDDR6X", "Core Clock": "2.55 GHz", "TDP": "320 W", "Outputs": "3x DP 1.4 - 1x HDMI 2.1a"}'
]);

fclose($output);
exit();
