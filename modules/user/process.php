<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // ✅ เรียกใช้ฟังก์ชัน Log

$action = $_REQUEST['action'] ?? '';
$current_user_id = $_SESSION['user_id']; // ไอดีคนทำรายการ (Admin)

// --- ADD: เพิ่มผู้ใช้ ---
if ($action == 'add') {
    // เช็ค Username ซ้ำ
    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $chk->execute([$_POST['username']]);
    if ($chk->fetch()) { header("Location: index.php?error=duplicate"); exit(); }

    $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $active = isset($_POST['is_active']) ? 1 : 0;
    $dept = !empty($_POST['department_id']) ? $_POST['department_id'] : null;

    // ✅ ใช้ column 'password_hash' ให้ตรงกับ Login
    $sql = "INSERT INTO users (username, password_hash, fullname, email, department_id, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$_POST['username'], $passHash, $_POST['fullname'], $_POST['email'], $dept, $_POST['role'], $active]);
    
    // ✅ บันทึก Log
    logActivity($pdo, $current_user_id, 'INSERT', 'เพิ่มผู้ใช้งานใหม่: ' . $_POST['username']);

    header("Location: index.php?msg=added");
}

// --- EDIT: แก้ไขผู้ใช้ ---
elseif ($action == 'edit') {
    $id = $_POST['id'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    $dept = !empty($_POST['department_id']) ? $_POST['department_id'] : null;

    // ดึงชื่อเก่ามาเก็บไว้ใน Log (Option) หรือแค่บอกว่าแก้ ID ไหนก็ได้
    $old_data = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $old_data->execute([$id]);
    $target_user = $old_data->fetch();

    // ถ้ามีการกรอกรหัสผ่านใหม่ ให้เปลี่ยนด้วย
    if (!empty($_POST['password'])) {
        $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET fullname=?, email=?, department_id=?, role=?, is_active=?, password_hash=? WHERE id=?";
        $pdo->prepare($sql)->execute([$_POST['fullname'], $_POST['email'], $dept, $_POST['role'], $active, $passHash, $id]);
        
        // ✅ Log (เปลี่ยนรหัสด้วย)
        logActivity($pdo, $current_user_id, 'UPDATE', 'แก้ไขข้อมูลและรหัสผ่าน User: ' . $target_user['username']);
    } else {
        $sql = "UPDATE users SET fullname=?, email=?, department_id=?, role=?, is_active=? WHERE id=?";
        $pdo->prepare($sql)->execute([$_POST['fullname'], $_POST['email'], $dept, $_POST['role'], $active, $id]);
        
        // ✅ Log (แก้ไขปกติ)
        logActivity($pdo, $current_user_id, 'UPDATE', 'แก้ไขข้อมูล User: ' . $target_user['username']);
    }
    
    header("Location: index.php?msg=updated");
}

// --- RESET PASSWORD: รีเซ็ตรหัส ---
elseif ($action == 'reset_pass') {
    $id = $_GET['id'];
    
    // ดึงชื่อ User มาเพื่อบันทึก Log
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target_user = $stmt->fetch();

    $defaultPass = password_hash("1234", PASSWORD_DEFAULT); // รหัสเริ่มต้น

    // ✅ ใช้ column 'password_hash'
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$defaultPass, $id]);
    
    // ✅ บันทึก Log
    logActivity($pdo, $current_user_id, 'UPDATE', 'รีเซ็ตรหัสผ่าน (Reset Pass) ของ User: ' . $target_user['username']);

    header("Location: index.php?msg=reset");
}

else {
    header("Location: index.php");
}