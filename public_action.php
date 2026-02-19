<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Rate Limiting: ห้ามส่งคำขอซ้ำภายใน 30 วินาที ---
$rate_limit_seconds = 30;
if (isset($_SESSION['last_public_submit']) && (time() - $_SESSION['last_public_submit']) < $rate_limit_seconds) {
    header('Content-Type: application/json');
    $wait_time = $rate_limit_seconds - (time() - $_SESSION['last_public_submit']);
    echo json_encode(['status' => 'error', 'message' => "กรุณารอ $wait_time วินาทีก่อนทำรายการใหม่ เพื่อป้องกันระบบค้าง"]);
    exit;
}
// บันทึกเวลาที่ทำรายการล่าสุด (เมื่อมีการ POST เข้ามา)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['last_public_submit'] = time();
}
// ----------------------------------------------------
require_once 'config/db_connect.php';
require_once 'includes/functions.php'; // ✅ เพิ่มบรรทัดนี้เพื่อให้เรียกใช้ sendLineNotify ได้

header('Content-Type: application/json');

if (isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        // 1. รับข้อมูลจากฟอร์ม
        $guest_name = trim($_POST['guest_name']);
        $guest_position = trim($_POST['guest_position']);
        $guest_dept = trim($_POST['guest_dept']);
        $guest_phone = trim($_POST['guest_phone']);
        $asset_code = trim($_POST['asset_code']); // เลขครุภัณฑ์
        $category_id = $_POST['category_id'];
        $description_text = trim($_POST['description']);

        // 2. จัดการรูปภาพ (Attachment) - ควรใช้ฟังก์ชัน uploadSecureFile ถ้าเป็นไปได้
        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            // ใช้ฟังก์ชัน uploadSecureFile จาก functions.php เพื่อความปลอดภัย (ตรวจสอบ MIME type)
            // ถ้ายังไม่มีการ include functions.php ให้ใช้ logic เดิมแต่เพิ่มความรัดกุม
            if (function_exists('uploadSecureFile')) {
                $attachment = uploadSecureFile($_FILES['attachment'], 'uploads/tickets/');
            } else {
                // Fallback กรณีไม่มี function (แต่แนะนำให้ใช้ผ่าน function)
                $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $new_name = 'guest_' . uniqid() . '.' . $ext;
                    $upload_path = 'uploads/tickets/' . $new_name;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                        $attachment = $new_name;
                    }
                }
            }
        }

        // 3. รวมข้อมูลผู้แจ้งไว้ในรายละเอียด
        $full_description = "ผู้แจ้ง: $guest_name\n";
        if($guest_position) $full_description .= "ตำแหน่ง: $guest_position\n";
        if($guest_dept) $full_description .= "สังกัด: $guest_dept\n";
        $full_description .= "เบอร์โทร: $guest_phone\n";
        if($asset_code) $full_description .= "เลขครุภัณฑ์: $asset_code\n";
        $full_description .= "-----------------------------------\n";
        $full_description .= "อาการ: " . $description_text;

        // 4. บันทึก (ใช้ Guest ID = 2 หรือตามที่ตั้งไว้)
        $guest_user_id = 2; 
        
        $sql = "INSERT INTO tickets (user_id, category_id, asset_code, description, attachment, priority, status, type, created_at) 
                VALUES (?, ?, ?, ?, ?, 'medium', 'new', 'incident', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $guest_user_id, 
            $category_id, 
            $asset_code, 
            $full_description, 
            $attachment
        ]);
        
        $new_id = $pdo->lastInsertId();

        // --- 5. ส่วนส่งไลน์แจ้งเตือนเจ้าหน้าที่ ---
        // ✅ แก้ไข: เรียกใช้ sendLineNotify ได้เลย ไม่ต้องดึง Token เอง เพราะฟังก์ชันจัดการให้แล้ว
        $notify_msg = "🔥 มีรายการแจ้งซ่อมใหม่ (Guest)";
        $notify_msg .= "\nเลขที่: #" . str_pad($new_id, 5, '0', STR_PAD_LEFT);
        $notify_msg .= "\nผู้แจ้ง: " . $guest_name;
        $notify_msg .= "\nแผนก/เบอร์: " . $guest_dept . " (" . $guest_phone . ")";
        $notify_msg .= "\nอาการ: " . $description_text;
        
        // ส่งข้อความ (ถ้าตั้งค่า Token ไว้ในระบบแล้ว)
        sendLineNotify($notify_msg);

        echo json_encode(['status' => 'success', 'ticket_id' => str_pad($new_id, 5, '0', STR_PAD_LEFT)]);

    } catch (PDOException $e) {
        // ✅ แก้ไข: ไม่ส่ง $e->getMessage() กลับไปหา Client เพื่อป้องกัน Information Disclosure
        error_log("Public Ticket Error: " . $e->getMessage()); // เก็บ Log ไว้ดูเอง
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่หรือติดต่อเจ้าหน้าที่']);
    }
} elseif (isset($_POST['action']) && $_POST['action'] == 'get_kb') {
    // ... (ส่วนอ่าน KB คงเดิม) ...
    if(isset($_POST['id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $stmt = $pdo->prepare("SELECT k.*, c.name as cat_name FROM kb_articles k LEFT JOIN kb_categories c ON k.category_id = c.id WHERE k.id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            error_log("KB Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถดึงข้อมูลได้']);
        }
    }
}
?>