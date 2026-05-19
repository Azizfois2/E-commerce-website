<?php
require_once '../admin-helpers.php';
adminRequireAuth();

$pdo = db();
if (!adminTableExists($pdo, 'Client')) {
    exit('No customer data available to export.');
}

$format = $_GET['format'] ?? 'csv';

if ($format === 'r') {
    // Generate an R script that loads the customers
    header('Content-Type: application/R');
    header('Content-Disposition: attachment; filename="import_customers.R"');
    echo "# R Script to load Maroc PC Customers and save to RData\n";
    echo "# 1. Make sure 'customers.csv' is in your working directory.\n";
    echo "# 2. Run this script in RStudio or R console.\n\n";
    echo "customers <- read.csv('customers.csv', stringsAsFactors=FALSE)\n";
    echo "customers\$created_at <- as.POSIXct(customers\$created_at, format='%Y-%m-%d %H:%M:%S')\n";
    echo "customers\$total_spent <- as.numeric(customers\$total_spent)\n";
    echo "customers\$order_count <- as.integer(customers\$order_count)\n";
    echo "save(customers, file='customers.RData')\n";
    echo "cat('Customers successfully loaded and saved to customers.RData!\\n')\n";
    exit;
}

$hasOrders = adminTableExists($pdo, 'orders');

if ($hasOrders) {
    $customers = adminFetchAll($pdo, '
        SELECT
            c.id_client,
            c.nom,
            c.email,
            c.telephone,
            c.adresse,
            c.created_at,
            c.is_suspended,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total), 0) AS total_spent
        FROM Client c
        LEFT JOIN orders o ON o.client_id = c.id_client
        GROUP BY c.id_client, c.nom, c.email, c.telephone, c.adresse, c.created_at, c.is_suspended
        ORDER BY c.created_at DESC
    ');
} else {
    $customers = adminFetchAll($pdo, '
        SELECT
            c.id_client,
            c.nom,
            c.email,
            c.telephone,
            c.adresse,
            c.created_at,
            c.is_suspended,
            0 AS order_count,
            0 AS total_spent
        FROM Client c
        ORDER BY c.created_at DESC
    ');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers.csv"');

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

fputcsv($output, ['Client ID', 'Name', 'Email', 'Phone', 'Address', 'Status', 'Order Count', 'Total Spent (MAD)', 'Joined At']);

foreach ($customers as $row) {
    $status = $row['is_suspended'] ? 'Suspended' : 'Active';
    fputcsv($output, [
        $row['id_client'],
        $row['nom'],
        $row['email'],
        $row['telephone'],
        $row['adresse'],
        $status,
        $row['order_count'],
        $row['total_spent'],
        $row['created_at']
    ]);
}
fclose($output);
exit;
