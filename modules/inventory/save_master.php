<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "INSERT INTO inventory_items (name, code, min_stock, price_per_unit, unit, qty_on_hand) 
                VALUES (?, ?, ?, ?, ?, 0)"; // เริ่มต้น 0 เสมอ แล้วค่อยไปทำรับเข้า
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['code'],
            $_POST['min_stock'],
            $_POST['price_per_unit'],
            $_POST['unit']
        ]);
        header("Location: index.php");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>