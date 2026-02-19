<?php
// includes/auth.php

// --- เพิ่ม Session Hardening ตามคำแนะนำของ Tester ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 วัน
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']), // บังคับใช้ HTTPS (ถ้ามี)
        'httponly' => true, // ป้องกัน JavaScript เข้าถึง Cookie (กัน XSS)
        'samesite' => 'Strict' // ป้องกัน CSRF ระดับ Cookie
    ]);
    session_start();
}

// ถ้าไม่มี Session User ให้เด้งไปหน้า Login
if (!isset($_SESSION['user_id'])) {
    // คำนวณความลึกของโฟลเดอร์
    $path = $_SERVER['PHP_SELF'];
    $depth = substr_count($path, '/') - 2;
    $prefix = str_repeat('../', $depth > 0 ? $depth : 0);
    
    header("Location: " . $prefix . "login.php");
    exit();
}

// ฟังก์ชันเช็ค Admin
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // แนะนำให้ส่ง HTTP Status Code 403 ด้วย
        header('HTTP/1.0 403 Forbidden');
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
        echo "<h3>⛔ Access Denied</h3><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Admin Only)</p>";
        echo "<a href='../../index.php'>กลับหน้าหลัก</a>";
        echo "</div>";
        exit();
    }
}
?>