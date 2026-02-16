<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถ้าไม่มี Session User ให้เด้งไปหน้า Login
if (!isset($_SESSION['user_id'])) {
    // ปรับ Path ให้ถอยกลับไปหา login.php ได้ถูกต้อง
    // คำนวณความลึกของโฟลเดอร์
    $path = $_SERVER['PHP_SELF'];
    $depth = substr_count($path, '/') - 2; // -2 เพราะเราอยู่ใน folder ย่อย (it_support/modules/xxx)
    $prefix = str_repeat('../', $depth > 0 ? $depth : 0);
    
    header("Location: " . $prefix . "login.php");
    exit();
}

// ฟังก์ชันเช็ค Admin
function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        echo "<h3>⛔ Access Denied</h3><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Admin Only)</p>";
        echo "<a href='../../index.php'>กลับหน้าหลัก</a>";
        exit();
    }
}
