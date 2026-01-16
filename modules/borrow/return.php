<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $pdo->beginTransaction();

        // 1. ดึงข้อมูลรายการยืมเพื่อหา asset_id
        $stmt = $pdo->prepare("SELECT asset_id FROM borrow_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $asset_id = $stmt->fetchColumn();

        // 2. อัปเดตสถานะใน borrow_transactions เป็น returned และใส่วันที่คืนจริง
        $update1 = $pdo->prepare("UPDATE borrow_transactions SET status = 'returned', actual_return_date = NOW() WHERE id = ?");
        $update1->execute([$id]);

        // 3. อัปเดต Asset ให้ current_user_id เป็น NULL (คือไม่มีใครถือครองแล้ว หรือกลับมาที่ส่วนกลาง)
        $update2 = $pdo->prepare("UPDATE assets SET current_user_id = NULL WHERE id = ?");
        $update2->execute([$asset_id]);

        $pdo->commit();
        header("Location: index.php?msg=returned");

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>