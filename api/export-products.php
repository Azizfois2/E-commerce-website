<?php
require_once '../admin-helpers.php';
adminRequireAuth();

$pdo = db();
if (!adminTableExists($pdo, 'products')) {
    exit('No products data available to export.');
}

$format = $_GET['format'] ?? 'csv';

if ($format === 'r') {
    // Generate an R script that loads the products
    header('Content-Type: application/R');
    header('Content-Disposition: attachment; filename="import_products.R"');
    echo "# R Script to load Maroc PC Products and save to RData\n";
    echo "# 1. Make sure 'products.csv' is in your working directory.\n";
    echo "# 2. Run this script in RStudio or R console.\n\n";
    echo "products <- read.csv('products.csv', stringsAsFactors=FALSE)\n";
    echo "products\$price <- as.numeric(products\$price)\n";
    echo "products\$stock_quantity <- as.integer(products\$stock_quantity)\n";
    echo "save(products, file='products.RData')\n";
    echo "cat('Products successfully loaded and saved to products.RData!\\n')\n";
    exit;
}

// Default: CSV Export
$products = adminFetchAll($pdo, '
    SELECT id, name, brand, category, price, old_price, badge, rating, reviews, in_stock, stock_quantity, reorder_level, created_at
    FROM products
    ORDER BY name ASC
');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products.csv"');

$output = fopen('php://output', 'w');
// Add BOM for Excel UTF-8 support
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Header row
fputcsv($output, ['ID', 'Name', 'Brand', 'Category', 'Price (MAD)', 'Old Price', 'Badge', 'Rating', 'Reviews', 'In Stock', 'Stock Qty', 'Reorder Level', 'Created At']);

foreach ($products as $row) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['brand'],
        $row['category'],
        $row['price'],
        $row['old_price'],
        $row['badge'],
        $row['rating'],
        $row['reviews'],
        $row['in_stock'] ? 'Yes' : 'No',
        $row['stock_quantity'],
        $row['reorder_level'],
        $row['created_at']
    ]);
}
fclose($output);
exit;
