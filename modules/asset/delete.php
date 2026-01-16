<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=deleted");
    } catch (PDOException $e) {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากข้อมูลถูกใช้งานอยู่'); window.location='index.php';</script>";
    }
} else {
    header("Location: index.php");
}
?>