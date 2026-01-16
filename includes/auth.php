<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เช็คว่ามี User ID ใน Session หรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่มี ให้เด้งไปหน้า Login
    header("Location: /it_support/login.php");
    exit();
}

// ฟังก์ชันเช็คสิทธิ์ (Helper Function)
function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        echo "<h1>Access Denied</h1><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p><a href='/it_support/index.php'>กลับหน้าหลัก</a>";
        exit();
    }
}
?>