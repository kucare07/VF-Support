<?php
// config/db_connect.php

// โหลดจาก Environment Variables ของ Server (ถ้ามี) ถ้าไม่มีให้ใช้ค่า Default (สำหรับ Local)
// วิธีนี้ทำให้ตอนเอาขึ้น Production (Server จริง) เราไปตั้งค่า ENV ที่ Server แทน ไม่ต้องฝังรหัสในโค้ด
$host = getenv('DB_HOST') ?: '127.0.0.1'; // แนะนำใช้ 127.0.0.1 แทน localhost เพื่อลดปัญหา Socket
$db   = getenv('DB_NAME') ?: 'it_support_db';
$user = getenv('DB_USER') ?: 'root'; 
$pass = getenv('DB_PASS') ?: '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // ปิดการแสดง Error ตรงๆ (ตามที่ QA แนะนำเพื่อไม่ให้เปิดเผยข้อมูล)
    error_log("DB Connection failed: " . $e->getMessage()); // เก็บลง log แทน
    header('HTTP/1.1 503 Service Unavailable');
    die("ระบบขัดข้อง ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ"); 
}
?>