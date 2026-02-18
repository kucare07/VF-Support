<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pm_plan_report.csv');
$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Plan Name', 'Asset', 'Frequency (Days)', 'Last Done', 'Next Due', 'Status']);

$month = $_GET['pm_month'] ?? date('Y-m'); // Not used in simple query but good for future filter

$sql = "SELECT p.name, a.name as asset_name, p.frequency_days, p.last_done_date, p.next_due_date, p.status 
        FROM pm_plans p 
        LEFT JOIN assets a ON p.asset_id = a.id";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
fclose($output);
?>