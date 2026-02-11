<?php
// public_action.php - รับข้อมูลจากหน้าเว็บแบบไม่ต้อง Login
require_once 'config/db_connect.php';
header('Content-Type: application/json');

// --- 1. จัดการการบันทึกข้อมูล (CREATE) ---
if ($_POST['action'] == 'create') {
    try {
        $guest_name = trim($_POST['guest_name']);
        $guest_contact = trim($_POST['guest_contact']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category_id = $_POST['category_id'];
        
        // ** สำคัญ: ระบุ ID ของ Guest User ที่เราสร้างไว้ (ไปดูใน Database ว่า ID อะไร แล้วมาแก้ตรงนี้) **
        // สมมติว่า Guest ID = 2 (ถ้าไม่ใช่ ให้แก้เลข 2 เป็น ID ที่ถูกต้อง)
        $guest_user_id = 2; 

        // ตรวจสอบ user_id ว่ามีจริงไหม
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $check->execute([$guest_user_id]);
        if (!$check->fetch()) {
            // ถ้าไม่เจอ ให้ใช้ Admin (ID 1) แทนกันเหนียว
            $guest_user_id = 1; 
        }

        // รวมชื่อผู้แจ้งไปในรายละเอียด (เพราะตาราง tickets ไม่มีช่อง guest_name)
        $full_description = "ผู้แจ้ง: $guest_name ($guest_contact)\n\nรายละเอียด:\n$description";

        $sql = "INSERT INTO tickets (user_id, category_id, title, description, priority, status, type, created_at) 
                VALUES (?, ?, ?, ?, 'medium', 'new', 'incident', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$guest_user_id, $category_id, $title, $full_description]);
        $new_id = $pdo->lastInsertId();

        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลเรียบร้อย', 'ticket_id' => str_pad($new_id, 5, '0', STR_PAD_LEFT)]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}

// --- 2. อ่านบทความ (Read KB) ---
if ($_POST['action'] == 'get_kb') {
    try {
        $id = $_POST['id'];
        // อัปเดตยอดวิว
        $pdo->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?")->execute([$id]);
        
        // ดึงข้อมูล
        $stmt = $pdo->prepare("SELECT k.*, c.name as cat_name, u.fullname as author_name 
                              FROM kb_articles k 
                              LEFT JOIN kb_categories c ON k.category_id = c.id 
                              LEFT JOIN users u ON k.author_id = u.id 
                              WHERE k.id = ? AND k.is_public = 1");
        $stmt->execute([$id]);
        $kb = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kb) {
            echo json_encode(['status' => 'success', 'data' => $kb]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล หรือบทความนี้ไม่เผยแพร่']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>