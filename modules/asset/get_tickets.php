<?php
// modules/asset/get_tickets.php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if (!isset($_GET['code'])) exit('<tr><td colspan="5" class="text-center">Error: No Code</td></tr>');

$code = $_GET['code'];

// ดึง Ticket ที่ระบุ asset_code ตรงกัน
$sql = "SELECT t.*, u.fullname as requester 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.asset_code = ? 
        ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$tickets = $stmt->fetchAll();

if (count($tickets) == 0) {
    echo '<tr><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i> ไม่พบประวัติการแจ้งซ่อม (เครื่องนี้สุขภาพดี)</td></tr>';
} else {
    foreach ($tickets as $t) {
        $status = getStatusBadge($t['status']);
        $date = date('d/m/Y H:i', strtotime($t['created_at']));
        $desc = htmlspecialchars($t['description']);
        
        echo "<tr>
            <td><span class='badge bg-light text-dark border'>#{$t['id']}</span></td>
            <td class='small'>{$date}</td>
            <td class='text-start text-truncate' style='max-width: 200px;' title='{$desc}'>{$desc}</td>
            <td class='small'>{$t['requester']}</td>
            <td>{$status}</td>
        </tr>";
    }
}
?>