<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจาก Form เป็น Array และวนลูปอัปเดต
    $settings = $_POST['settings']; // รับ array ชื่อ settings[key]

    try {
        $pdo->beginTransaction();

        foreach ($settings as $key => $value) {
            // อัปเดตทีละค่า
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([trim($value), $key]);
        }

        $pdo->commit();
        header("Location: index.php?msg=updated");

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
}
?>