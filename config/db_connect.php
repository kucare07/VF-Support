<?php
// config/db_connect.php
$host = 'localhost'; // หรือลองเปลี่ยนเป็น 127.0.0.1 หาก localhost มีปัญหา
$db   = 'it_support_db';
$user = 'root';
$pass = '';
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
    // แสดง Error แบบ User-Friendly
    die('
        <div style="font-family: sans-serif; text-align: center; padding: 50px;">
            <h2 style="color: red;">❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
            <p>Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>
            <hr>
            <p style="color: #666;">
                <b>คำแนะนำ:</b><br>
                1. ตรวจสอบว่าเปิด XAMPP/MySQL หรือยัง<br>
                2. ตรวจสอบชื่อฐานข้อมูล (<code>' . $db . '</code>) ว่ามีอยู่จริง<br>
                3. หากใช้ Mac/Linux ลองเปลี่ยน Host จาก <code>localhost</code> เป็น <code>127.0.0.1</code>
            </p>
        </div>
    ');
}
?>