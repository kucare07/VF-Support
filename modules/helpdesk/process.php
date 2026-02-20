<?php

ob_start(); // เริ่มต้น Buffer ทันที ห้ามมีอะไรก่อนบรรทัดนี้
session_start();

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
// ตัวอย่างการบังคับใช้ CSRF ใน process.php
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    die("⛔ ตรวจพบความเสี่ยง CSRF Attack - ขอปฏิเสธการทำรายการ");
}

// ฟังก์ชันช่วย Redirect แบบปลอดภัย (ใช้ทั้ง PHP และ JS)
function safeRedirect($url, $msg = null, $error = null) {
    $params = [];
    if ($msg) $params['msg'] = $msg;
    if ($error) $params['error'] = $error;
    
    $query = http_build_query($params);
    $target = $url . ($query ? '?' . $query : '');

    // ลองใช้ PHP Header ก่อน
    if (!headers_sent()) {
        header("Location: $target");
    } else {
        // ถ้า Header หลุดไปแล้ว ให้ใช้ JS แทน (แก้ปัญหาหน้าขาว/Form Resubmission)
        echo "<script>window.location.href='$target';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$target'></noscript>";
    }
    exit();
}

// เช็ค Login
if (!isset($_SESSION['user_id'])) {
    safeRedirect('../../login.php');
}

$action = $_REQUEST['action'] ?? '';

try {
    // -----------------------------------------------------------------
    // CASE: DELETE (ลบงาน)
    // -----------------------------------------------------------------
    if ($action == 'delete' && isset($_GET['id'])) {
        if ($_SESSION['role'] == 'user') {
            safeRedirect('index.php', null, 'access_denied');
        }
        
        $stmt = $pdo->prepare("SELECT attachment FROM tickets WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $ticket = $stmt->fetch();
        
        if ($ticket && !empty($ticket['attachment'])) {
            $file_path = "../../uploads/tickets/" . $ticket['attachment'];
            if (file_exists($file_path)) { @unlink($file_path); }
        }

        $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$_GET['id']]);
        safeRedirect('index.php', 'deleted');
    }

    // -----------------------------------------------------------------
    // CASE: ADD (เพิ่มงานใหม่)
    // -----------------------------------------------------------------
    elseif ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $requester_id = !empty($_POST['requester_id']) ? $_POST['requester_id'] : $_SESSION['user_id'];
        $cat = !empty($_POST['category_id']) ? $_POST['category_id'] : 1;
        $asset = trim($_POST['asset_code']);
        $desc = trim($_POST['description']);
        $prio = $_POST['priority'];
        $type = $_POST['type'] ?? 'incident';

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachment = uploadSecureFile($_FILES['attachment'], '../../uploads/tickets/');
        }

        $hours_to_add = match($prio) {
            'critical' => 4,
            'high' => 24,
            'medium' => 72,
            default => 120
        };
        $sla_due_date = date('Y-m-d H:i:s', strtotime("+$hours_to_add hours"));

        $sql = "INSERT INTO tickets (user_id, category_id, asset_code, priority, status, description, attachment, created_at, sla_due_date, type) 
                VALUES (?, ?, ?, ?, 'new', ?, ?, NOW(), ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requester_id, $cat, $asset, $prio, $desc, $attachment, $sla_due_date, $type]);
        
        if (isset($_POST['notify_line']) && function_exists('sendLineNotify')) {
            $tid = $pdo->lastInsertId();
            @sendLineNotify("🔔 New Ticket #$tid\nDetail: $desc\nBy: " . $_SESSION['fullname']);
        }

        safeRedirect('index.php', 'added');
    } 
    
    // -----------------------------------------------------------------
    // CASE: EDIT (แก้ไขสถานะ)
    // -----------------------------------------------------------------
    elseif ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $tech = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;

        $sql = "UPDATE tickets SET status=?, assigned_to=? WHERE id=?";
        $pdo->prepare($sql)->execute([$status, $tech, $id]);
        
        safeRedirect('index.php', 'updated');
    } 
    
    // -----------------------------------------------------------------
    // CASE: COMMENT (ตอบกลับ)
    // -----------------------------------------------------------------
    elseif ($action == 'comment' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $ticket_id = $_POST['ticket_id'];
        $comment = trim($_POST['comment']);

        if (!empty($comment)) {
            $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())")
                ->execute([$ticket_id, $_SESSION['user_id'], $comment]);
            safeRedirect('index.php', 'commented');
        } else {
            safeRedirect('index.php');
        }
    } else {
        safeRedirect('index.php');
    }

} catch (Exception $e) {
    safeRedirect('index.php', null, $e->getMessage());
}

ob_end_flush();
?>