<?php
require_once 'config/db_connect.php';
header('Content-Type: application/json');

if ($_POST['action'] == 'create') {
    try {
        // 1. รับข้อมูลจากฟอร์ม
        $guest_name = trim($_POST['guest_name']);
        $guest_position = trim($_POST['guest_position']);
        $guest_dept = trim($_POST['guest_dept']);
        $guest_phone = trim($_POST['guest_phone']);
        $asset_code = trim($_POST['asset_code']); // เลขครุภัณฑ์
        $category_id = $_POST['category_id'];
        $description_text = trim($_POST['description']);

        // 2. จัดการรูปภาพ (Attachment)
        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
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

        // 3. รวมข้อมูลผู้แจ้งไว้ในรายละเอียด (เพราะ Guest ไม่มี User ID จริง)
        $full_description = "ผู้แจ้ง: $guest_name\n";
        if($guest_position) $full_description .= "ตำแหน่ง: $guest_position\n";
        if($guest_dept) $full_description .= "สังกัด: $guest_dept\n";
        $full_description .= "เบอร์โทร: $guest_phone\n";
        if($asset_code) $full_description .= "เลขครุภัณฑ์: $asset_code\n";
        $full_description .= "-----------------------------------\n";
        $full_description .= "อาการ: " . $description_text;

        // 4. บันทึก (ใช้ Guest ID = 2 หรือตามที่คุณตั้งไว้)
        $guest_user_id = 2; // อย่าลืมเช็คว่า ID นี้มีในตาราง Users
        
        $sql = "INSERT INTO tickets (user_id, category_id, asset_code, description, attachment, priority, status, type, created_at) 
                VALUES (?, ?, ?, ?, ?, 'medium', 'new', 'incident', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $guest_user_id, 
            $category_id, 
            $asset_code, // บันทึกเลขครุภัณฑ์ลงช่อง asset_code ด้วย
            $full_description, 
            $attachment
        ]);
        
        $new_id = $pdo->lastInsertId();
        // --- ส่วนส่งไลน์แจ้งเตือนเจ้าหน้าที่ ---
        // 1. ดึง Token จากตาราง settings
        $line_token = getSystemSetting('line_notify_token', $pdo); // ตรวจสอบชื่อใน DB ว่าใช้ชื่ออะไร แน่ใจว่าเป็น line_notify_token หรือ line_token

        if ($line_token) {
            $notify_msg = "\n🔥 มีรายการแจ้งซ่อมใหม่ (Guest)";
            $notify_msg .= "\nเลขที่: #" . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            $notify_msg .= "\nผู้แจ้ง: " . $guest_name;
            $notify_msg .= "\nแผนก/เบอร์: " . $guest_dept . " (" . $guest_phone . ")";
            $notify_msg .= "\nอาการ: " . $description_text;
            
            // ส่งข้อความ
            sendLineNotify($notify_msg, $line_token);
        }

        echo json_encode(['status' => 'success', 'ticket_id' => str_pad($new_id, 5, '0', STR_PAD_LEFT)]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} elseif ($_POST['action'] == 'get_kb') {
    // ... (ส่วนอ่าน KB คงเดิม) ...
    $stmt = $pdo->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $stmt = $pdo->prepare("SELECT k.*, c.name as cat_name FROM kb_articles k LEFT JOIN kb_categories c ON k.category_id = c.id WHERE k.id = ?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
}
?>