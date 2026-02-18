<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_report.csv');
$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Item Name', 'Category', 'Quantity', 'Min Quantity', 'Unit', 'Last Updated']);

$sql = "SELECT i.item_name, c.name as cat_name, i.quantity, i.min_quantity, i.unit, i.updated_at 
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.id";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
fclose($output);
?>