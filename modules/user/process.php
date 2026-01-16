<?php
// modules/user/process.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// 1. จัดการการ "ลบข้อมูล" (Delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=deleted");
    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}

// 2. จัดการการ "เพิ่ม" และ "แก้ไข" (Add & Edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action']; // รับค่าว่าเป็น add หรือ edit
    
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $department_id = $_POST['department_id'] ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        if ($action == 'add') {
            // --- โหมดเพิ่มข้อมูลใหม่ ---
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password_hash, fullname, role, department_id, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $password, $fullname, $role, $department_id, 1]); // Default Active
            $msg = "added";

        } elseif ($action == 'edit') {
            // --- โหมดแก้ไขข้อมูล ---
            $id = $_POST['id'];
            
            // เช็คว่ามีการเปลี่ยนรหัสผ่านไหม
            $password_sql = "";
            $params = [$fullname, $role, $department_id, $is_active];
            
            if (!empty($_POST['password'])) {
                $password_sql = ", password_hash = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $params[] = $id; // ปิดท้ายด้วย ID สำหรับ WHERE

            $sql = "UPDATE users SET fullname = ?, role = ?, department_id = ?, is_active = ? $password_sql WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "updated";
        }

        header("Location: index.php?msg=$msg");

    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}
?>