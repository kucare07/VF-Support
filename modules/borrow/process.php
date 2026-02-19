<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
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
$action = $_REQUEST['action'] ?? '';

// --- 1. ทำรายการยืม ---
if ($action == 'borrow') {
    $tx_no = 'BR-' . date('ymd') . '-' . rand(100, 999);
    $user_id = $_POST['user_id'];
    $asset_id = $_POST['asset_id'];
    $handler_id = $_SESSION['user_id']; // คนทำรายการ
    $b_date = $_POST['borrow_date'];
    $due_date = !empty($_POST['return_due_date']) ? $_POST['return_due_date'] : null;
    $note = $_POST['note'];

    // 1.1 บันทึก Transaction
    $sql = "INSERT INTO borrow_transactions (transaction_no, asset_id, user_id, handler_id, borrow_date, return_due_date, status, note) 
            VALUES (?, ?, ?, ?, ?, ?, 'borrowed', ?)";
    $pdo->prepare($sql)->execute([$tx_no, $asset_id, $user_id, $handler_id, $b_date, $due_date, $note]);

    // 1.2 อัปเดต Asset (เปลี่ยนผู้ถือครอง + สถานะ Active)
    $stmt = $pdo->prepare("UPDATE assets SET current_user_id = ?, status = 'active' WHERE id = ?");
    $stmt->execute([$user_id, $asset_id]);

    // 1.3 เก็บ Log
    logAssetAction($asset_id, 'borrow', "ถูกยืมโดย User ID: $user_id (Ref: $tx_no)");

    header("Location: index.php?msg=borrowed");
}

// --- 2. รับคืน ---
elseif ($action == 'return') {
    $id = $_GET['id'];
    $asset_id = $_GET['aid'];
    
    // 2.1 อัปเดต Transaction
    $sql = "UPDATE borrow_transactions SET return_date = NOW(), status = 'returned' WHERE id = ?";
    $pdo->prepare($sql)->execute([$id]);

    // 2.2 อัปเดต Asset (ปลดผู้ถือครอง + สถานะ Spare)
    // หมายเหตุ: ตรงนี้แล้วแต่นโยบาย บางที่คืนแล้วให้เป็น Active ต่อ หรือบางที่ให้เป็น Spare (สำรอง)
    // ในที่นี้ขอตั้งเป็น 'spare' เพื่อให้รู้ว่าของกลับมาคลังแล้ว พร้อมให้ยืมต่อ
    $stmt = $pdo->prepare("UPDATE assets SET current_user_id = NULL, status = 'spare' WHERE id = ?");
    $stmt->execute([$asset_id]);

    // 2.3 เก็บ Log
    logAssetAction($asset_id, 'return', "รับคืนเข้าคลังเรียบร้อย");

    header("Location: index.php?msg=returned");
}

else {
    header("Location: index.php");
}
?>