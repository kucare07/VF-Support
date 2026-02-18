<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=borrow_report.csv');
$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Transaction ID', 'Borrower', 'Asset Code', 'Asset Name', 'Borrow Date', 'Due Date', 'Return Date', 'Status']);

$start = $_GET['start_date'] ?? date('Y-m-01');
$end = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

$sql = "SELECT b.id, u.fullname, a.asset_code, a.name, b.borrow_date, b.return_due_date, b.actual_return_date, b.status 
        FROM borrow_transactions b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN assets a ON b.asset_id = a.id
        WHERE DATE(b.borrow_date) BETWEEN ? AND ?";

$params = [$start, $end];
if (!empty($status)) {
    $sql .= " AND b.status = ?";
    $params[] = $status;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
fclose($output);
?>