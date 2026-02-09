<?php
// modules/helpdesk/process.php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// --- ลบ (Delete) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // ป้องกัน User ลบงาน (ให้เฉพาะ Admin/Technician)
    if ($_SESSION['role'] == 'user') {
        header("Location: index.php?error=access_denied");
        exit();
    }
    
    try {
        // ลบไฟล์แนบ (ถ้ามี) ก่อนลบ Record
        $stmt = $pdo->prepare("SELECT attachment FROM tickets WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $ticket = $stmt->fetch();
        if ($ticket && $ticket['attachment']) {
            $file_path = "../../uploads/tickets/" . $ticket['attachment'];
            if (file_exists($file_path)) unlink($file_path);
        }

        $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$_GET['id']]);
        header("Location: index.php?msg=deleted");
    } catch (Exception $e) {
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}

// --- เพิ่ม/แก้ไข/คอมเมนต์ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // -----------------------------------------------------------------
        // CASE: ADD (เพิ่มงานใหม่)
        // -----------------------------------------------------------------
        if ($action == 'add') {
            
            // 1. ระบุผู้แจ้ง (User แจ้งเอง หรือ Admin แจ้งแทน)
            $requester_id = $_SESSION['user_id']; // Default
            if (!empty($_POST['requester_id'])) {
                $requester_id = $_POST['requester_id'];
            }

            // 2. รับค่าจากฟอร์ม
            $cat = !empty($_POST['category_id']) ? $_POST['category_id'] : 1; // Default Cat ID 1
            $asset = trim($_POST['asset_code']);
            $desc = trim($_POST['description']);
            $prio = $_POST['priority'];
            // เช็คว่ามี field type ส่งมาไหม (ถ้าไม่มีให้เป็น 'incident')
            $type = isset($_POST['type']) ? $_POST['type'] : 'incident'; 

            // 3. Upload File (ใช้ฟังก์ชัน Secure Upload ที่สร้างไว้)
            $attachment = null;
            if (!empty($_FILES['attachment']['name'])) {
                // อัปโหลดไปที่ uploads/tickets/
                $attachment = uploadSecureFile($_FILES['attachment'], '../../uploads/tickets/');
            }

            // 4. คำนวณ SLA (กำหนดส่งงาน)
            $hours_to_add = 120; // Default Low (5 วัน)
            switch ($prio) {
                case 'critical': $hours_to_add = 4; break;   // 4 ชม.
                case 'high':     $hours_to_add = 24; break;  // 1 วัน
                case 'medium':   $hours_to_add = 72; break;  // 3 วัน
                case 'low':      $hours_to_add = 120; break; // 5 วัน
            }
            $sla_due_date = date('Y-m-d H:i:s', strtotime("+$hours_to_add hours"));

            // 5. บันทึกข้อมูล
            $sql = "INSERT INTO tickets (user_id, category_id, asset_code, priority, status, description, attachment, created_at, sla_due_date, type) 
                    VALUES (?, ?, ?, ?, 'new', ?, ?, NOW(), ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$requester_id, $cat, $asset, $prio, $desc, $attachment, $sla_due_date, $type]);
            
            // 6. ส่ง LINE แจ้งเตือน (ถ้าติ๊ก)
            if (isset($_POST['notify_line'])) {
                $ticket_id = $pdo->lastInsertId(); // หา ID ล่าสุดเพื่อใส่ในข้อความ
                $msg = "🔔 New Ticket #$ticket_id\n";
                $msg .= "Type: " . ucfirst($type) . "\n";
                $msg .= "Priority: " . ucfirst($prio) . "\n";
                $msg .= "Detail: $desc\n";
                $msg .= "By: " . $_SESSION['fullname'];

                sendLineNotify($msg); // ใช้ฟังก์ชัน Messaging API ที่แก้ไป
            }

            header("Location: index.php?msg=added");
        } 
        
        // -----------------------------------------------------------------
        // CASE: EDIT (แก้ไขสถานะ/มอบหมายงาน)
        // -----------------------------------------------------------------
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $tech = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;

            // Update ข้อมูล
            $sql = "UPDATE tickets SET status=?, assigned_to=? WHERE id=?";
            $pdo->prepare($sql)->execute([$status, $tech, $id]);
            
            header("Location: index.php?msg=updated");
        } 
        
        // -----------------------------------------------------------------
        // CASE: COMMENT (ตอบกลับ)
        // -----------------------------------------------------------------
        elseif ($action == 'comment') {
            $ticket_id = $_POST['ticket_id'];
            $comment = trim($_POST['comment']);

            if (!empty($comment)) {
                $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$ticket_id, $_SESSION['user_id'], $comment]);
                
                // (Optional) อาจจะส่งไลน์บอกเจ้าของเคสว่ามีการตอบกลับ
                header("Location: index.php?msg=commented");
            } else {
                header("Location: index.php"); // ถ้าว่างเปล่า ไม่ต้องทำอะไร
            }
        }

    } catch (Exception $e) {
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
} else {
    // ถ้าไม่ใช่ POST ให้กลับไปหน้าหลัก
    header("Location: index.php");
}
?>