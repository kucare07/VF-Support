<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ลบ
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $pdo->prepare("DELETE FROM kb_articles WHERE id = ?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// เพิ่ม/แก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $cat_id = $_POST['category_id'];
    
    if ($_POST['action'] == 'add') {
        $sql = "INSERT INTO kb_articles (title, content, category_id, created_by) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$title, $content, $cat_id, $_SESSION['user_id']]);
        $msg = "added";
    } else {
        $sql = "UPDATE kb_articles SET title=?, content=?, category_id=? WHERE id=?";
        $pdo->prepare($sql)->execute([$title, $content, $cat_id, $_POST['id']]);
        $msg = "updated";
    }
    header("Location: index.php?msg=$msg");
    exit();
}