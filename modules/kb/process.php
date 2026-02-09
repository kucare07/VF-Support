<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

$action = $_REQUEST['action'] ?? '';

if ($action == 'add') {
    requireAdmin(); // ฟังก์ชันจาก auth.php (ถ้ามี) หรือเช็ค role
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $sql = "INSERT INTO kb_articles (title, content, category_id, author_id, is_public) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $_POST['category_id'], $_SESSION['user_id'], $is_public]);
    
    header("Location: index.php?msg=added");
}

elseif ($action == 'edit') {
    requireAdmin();
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $sql = "UPDATE kb_articles SET title=?, content=?, category_id=?, is_public=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $_POST['category_id'], $is_public, $_POST['id']]);
    
    header("Location: index.php?msg=updated");
}

elseif ($action == 'delete') {
    requireAdmin();
    $pdo->prepare("DELETE FROM kb_articles WHERE id=?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
}

elseif ($action == 'count_view') {
    // อัปเดตยอดวิวเงียบๆ
    $id = $_GET['id'];
    $pdo->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?")->execute([$id]);
    exit; // ไม่ต้อง redirect
}

else {
    header("Location: index.php");
}

// Helper function check permission (ถ้ายังไม่มีใน auth.php ให้ใส่ไว้ที่นี่ชั่วคราว)
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if ($_SESSION['role'] == 'user') { exit('Access Denied'); }
    }
}
?>