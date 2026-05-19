<?php
require_once '../admin-helpers.php';
adminRequireAuth();

$pdo = db();
if (!adminTableExists($pdo, 'orders')) {
    exit('No orders data available to export.');
}

$format = $_GET['format'] ?? 'csv';

if ($format === 'r') {
    // Generate an R script that loads the orders
    header('Content-Type: application/R');
    header('Content-Disposition: attachment; filename="import_orders.R"');
    echo "# R Script to load Maroc PC Orders and save to RData\n";
    echo "# 1. Make sure 'orders.csv' is in your working directory.\n";
    echo "# 2. Run this script in RStudio or R console.\n\n";
    echo "orders <- read.csv('orders.csv', stringsAsFactors=FALSE)\n";
    echo "orders\$created_at <- as.POSIXct(orders\$created_at, format='%Y-%m-%d %H:%M:%S')\n";
    echo "orders\$total <- as.numeric(orders\$total)\n";
    echo "save(orders, file='orders.RData')\n";
    echo "cat('Orders successfully loaded and saved to orders.RData!\\n')\n";
    exit;
}

// Default: CSV Export
$orders = adminFetchAll($pdo, '
    SELECT o.id, o.status, o.total, o.payment_status, o.payment_method, o.created_at, c.nom AS client_name, c.email AS client_email
    FROM orders o
    LEFT JOIN Client c ON c.id_client = o.client_id
    ORDER BY o.created_at DESC
');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders.csv"');

$output = fopen('php://output', 'w');
// Add BOM for Excel UTF-8 support
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Header row
fputcsv($output, ['Order ID', 'Client Name', 'Client Email', 'Status', 'Total (MAD)', 'Payment Status', 'Payment Method', 'Date']);

foreach ($orders as $row) {
    fputcsv($output, [
        $row['id'],
        $row['client_name'],
        $row['client_email'],
        $row['status'],
        $row['total'],
        $row['payment_status'],
        $row['payment_method'],
        $row['created_at']
    ]);
}
fclose($output);
exit;
