<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่า settings จากฟอร์ม
    $settings = $_POST['settings'] ?? [];

    try {
        $pdo->beginTransaction();

        // เตรียม Query ครั้งเดียว (อยู่นอก Loop) เพื่อความเร็ว
        // ใช้ ? แทนชื่อตัวแปร เพื่อแก้ปัญหา HY093
        $sql = "INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $pdo->prepare($sql);

        foreach ($settings as $key => $value) {
            $val = trim($value);
            // ส่งค่า 3 ตัว: 1.Key, 2.Value(สำหรับ Insert), 3.Value(สำหรับ Update)
            $stmt->execute([$key, $val, $val]);
        }

        $pdo->commit();
        header("Location: index.php?msg=saved");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>