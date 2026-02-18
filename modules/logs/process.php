<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

$action = $_GET['action'] ?? '';

if ($action == 'clear_old_logs') {
    // ลบ Log ที่เก่ากว่า 90 วัน
    try {
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE created_at < NOW() - INTERVAL 90 DAY");
        $stmt->execute();
        header("Location: index.php?msg=cleared");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

} elseif ($action == 'bulk_delete_logs') {
    // ลบที่เลือก (ถ้ามีฟังก์ชันนี้)
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
    header("Location: index.php?msg=deleted");
} else {
    header("Location: index.php");
}
?>