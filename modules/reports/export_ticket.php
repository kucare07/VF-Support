<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tickets_report.csv');

$output = fopen('php://output', 'w');
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF))); // Fix Excel Font Thai

// Header
fputcsv($output, ['ID', 'Requester', 'Category', 'Description', 'Priority', 'Status', 'Technician', 'Created At']);

$start = $_GET['start_date'] ?? date('Y-m-01');
$end   = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

$sql = "SELECT t.id, u.fullname, c.name as category, t.description, t.priority, t.status, tech.fullname as tech_name, t.created_at 
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users tech ON t.assigned_to = tech.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?";

$params = [$start, $end];
if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fclose($output);
?>