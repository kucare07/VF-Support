<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';
// --- ตรวจสอบ CSRF Token และบังคับใช้ POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        error_log("CSRF Token Validation Failed for user: " . ($_SESSION['user_id'] ?? 'unknown') . " on " . $_SERVER['REQUEST_URI']);
        die("⛔ ขออภัย, ระบบปฏิเสธการทำรายการเนื่องจากตรวจพบความเสี่ยงด้านความปลอดภัย (Invalid CSRF Token)");
    }
}
// ------------------------------------------

// จากนั้นเปลี่ยนการรับค่า $action จาก $_GET/$_REQUEST เป็น $_POST ทั้งหมด (เพราะเราเปลี่ยนปุ่มลบเป็น POST แล้ว)
$action = $_POST['action'] ?? '';
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