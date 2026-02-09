<?php
// modules/asset/get_history.php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) exit('Invalid Request');

$sql = "SELECT l.*, u.fullname 
        FROM asset_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE l.asset_id = ? 
        ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_GET['id']]);
$logs = $stmt->fetchAll();

if (count($logs) == 0) {
    echo '<tr><td colspan="4" class="text-center text-muted py-3">ยังไม่มีประวัติการดำเนินการ</td></tr>';
} else {
    foreach ($logs as $log) {
        $date = thai_date(date('Y-m-d', strtotime($log['created_at']))) . ' ' . date('H:i', strtotime($log['created_at']));
        $action_badge = match($log['action']) {
            'create' => '<span class="badge bg-success">สร้างใหม่</span>',
            'update' => '<span class="badge bg-warning text-dark">แก้ไข</span>',
            'delete' => '<span class="badge bg-danger">ลบ</span>',
            'repair' => '<span class="badge bg-danger">แจ้งซ่อม</span>', // รองรับอนาคต
            default => '<span class="badge bg-secondary">'.$log['action'].'</span>'
        };
        
        echo '<tr>';
        echo '<td class="small">' . $date . '</td>';
        echo '<td>' . $action_badge . '</td>';
        echo '<td class="small">' . htmlspecialchars($log['details']) . '</td>';
        echo '<td class="small">' . htmlspecialchars($log['fullname']) . '</td>';
        echo '</tr>';
    }
}
?>