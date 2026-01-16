<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// ลบข้อมูล
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $pdo->prepare("DELETE FROM inventory_items WHERE id = ?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// เพิ่ม/แก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $qty = $_POST['qty'];
    $min = $_POST['min_level'];
    $unit = $_POST['unit'];

    if ($_POST['action'] == 'add') {
        $sql = "INSERT INTO inventory_items (name, description, qty, min_level, unit) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $desc, $qty, $min, $unit]);
        $msg = "added";
    } else {
        $sql = "UPDATE inventory_items SET name=?, description=?, qty=?, min_level=?, unit=? WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $desc, $qty, $min, $unit, $_POST['id']]);
        $msg = "updated";
    }
    header("Location: index.php?msg=$msg");
    exit();
}