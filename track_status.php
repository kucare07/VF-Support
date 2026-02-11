<?php
// track_status.php - API สำหรับค้นหางานซ่อม (ไม่ต้อง Login)
require_once 'config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_no = trim($_POST['ticket_no'] ?? '');

    if (empty($ticket_no)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุเลขที่ใบงาน']);
        exit;
    }

    // ค้นหาข้อมูล (เชื่อมตารางเพื่อดึงชื่อช่างและสถานะ)
    // ใช้ id หรือ ticket_no ในการค้นหาก็ได้ (ในที่นี้สมมติว่า User ค้นหาด้วย ID เช่น 1, 2, 3 หรือ Ticket Number ถ้ามี)
    $sql = "SELECT t.*, 
            u.fullname as requester_name, 
            tech.fullname as tech_name,
            c.name as cat_name
            FROM tickets t 
            LEFT JOIN users u ON t.user_id = u.id 
            LEFT JOIN users tech ON t.assigned_to = tech.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.id = :id OR t.id LIKE :id_like"; // แก้ไขเงื่อนไขตาม Format เลขที่คุณใช้จริง

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $ticket_no, ':id_like' => $ticket_no]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // จัดรูปแบบวันที่
            $date = date('d/m/Y H:i', strtotime($result['created_at']));
            
            // แปลงสถานะเป็นภาษาไทยและสี
            $status_map = [
                'new' => ['text' => 'รอรับเรื่อง', 'color' => 'bg-secondary'],
                'assigned' => ['text' => 'กำลังดำเนินการ (มอบหมายแล้ว)', 'color' => 'bg-info text-dark'],
                'pending' => ['text' => 'รออะไหล่/รอตรวจสอบ', 'color' => 'bg-warning text-dark'],
                'resolved' => ['text' => 'ดำเนินการเสร็จสิ้น', 'color' => 'bg-success'],
                'closed' => ['text' => 'ปิดงาน', 'color' => 'bg-dark']
            ];
            $st = $status_map[$result['status']] ?? ['text' => $result['status'], 'color' => 'bg-secondary'];

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'id' => str_pad($result['id'], 5, '0', STR_PAD_LEFT),
                    'title' => $result['description'], // หรือ title ถ้ามี
                    'requester' => $result['requester_name'],
                    'category' => $result['cat_name'],
                    'technician' => $result['tech_name'] ?? 'ยังไม่ระบุ',
                    'date' => $date,
                    'status_text' => $st['text'],
                    'status_class' => $st['color'],
                    'last_update' => date('d/m/Y H:i', strtotime($result['updated_at']))
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบงานนี้']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในระบบ']);
    }
}
?>