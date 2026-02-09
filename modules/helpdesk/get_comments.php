<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if (!isset($_GET['ticket_id'])) exit('');

// ดึงข้อมูลคอมเมนต์เรียงจากเก่าไปใหม่
$sql = "SELECT c.*, u.fullname, u.role 
        FROM ticket_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.ticket_id = ? 
        ORDER BY c.created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_GET['ticket_id']]);
$comments = $stmt->fetchAll();

if (count($comments) == 0) {
    echo '<div class="text-center text-muted small my-3 opacity-50"><i class="bi bi-chat-square-dots"></i> ยังไม่มีการตอบกลับ</div>';
} else {
    foreach ($comments as $c) {
        // เช็คว่าเป็นข้อความของเราเองหรือไม่
        $is_me = ($c['user_id'] == $_SESSION['user_id']);
        
        // จัดฝั่งซ้าย/ขวา และสีพื้นหลัง
        $align = $is_me ? 'align-items-end' : 'align-items-start';
        $bg = $is_me ? 'bg-primary text-white' : 'bg-light text-dark border';
        $name_align = $is_me ? 'text-end' : 'text-start';

        echo '<div class="d-flex flex-column ' . $align . ' mb-2">';
        echo '  <small class="text-muted ' . $name_align . '" style="font-size:0.7rem;">' 
             . htmlspecialchars($c['fullname']) . ' • ' . date('d/m H:i', strtotime($c['created_at'])) 
             . '</small>';
        echo '  <div class="p-2 rounded mt-1 ' . $bg . '" style="max-width: 85%; font-size: 0.85rem; word-wrap: break-word;">';
        echo      nl2br(htmlspecialchars($c['comment']));
        echo '  </div>';
        echo '</div>';
    }
}
?>