<?php
// modules/calendar/fetch_events.php
require_once '../../config/db_connect.php';

$events = [];

// ---------------------------------------------------------------------------
// 1. ดึงข้อมูลงานซ่อม (Tickets)
// ---------------------------------------------------------------------------
$sql_tickets = "SELECT t.id, t.description, t.created_at, t.status, t.priority, u.fullname 
                FROM tickets t 
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.status != 'closed'";
$stmt = $pdo->query($sql_tickets);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // กำหนดสีตามสถานะ
    $color = match($row['status']) {
        'new' => '#ffc107',      // เหลือง (New)
        'assigned' => '#0d6efd', // ฟ้า (Assigned)
        'pending' => '#dc3545',  // แดง (Pending)
        'resolved' => '#198754', // เขียว (Resolved)
        default => '#6c757d'     // เทา
    };

    $events[] = [
        'id' => 'ticket_' . $row['id'],
        'title' => 'Ticket #' . $row['id'],
        'start' => date('Y-m-d', strtotime($row['created_at'])),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'ticket',
            'detail' => $row['description'],
            'who' => $row['fullname'],
            'status' => ucfirst($row['status']),
            'priority' => ucfirst($row['priority'])
        ]
    ];
}

// ---------------------------------------------------------------------------
// 2. ดึงรายการยืม-คืน (Borrow Returns)
// ---------------------------------------------------------------------------
$sql_borrow = "SELECT b.id, b.transaction_no, b.return_due_date, u.fullname, a.name as asset_name
               FROM borrow_transactions b
               LEFT JOIN users u ON b.user_id = u.id
               LEFT JOIN assets a ON b.asset_id = a.id
               WHERE b.status = 'borrowed' AND b.return_due_date IS NOT NULL";
$stmt = $pdo->query($sql_borrow);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        'id' => 'borrow_' . $row['id'],
        'title' => 'คืนของ (Return): ' . $row['asset_name'],
        'start' => $row['return_due_date'],
        'backgroundColor' => '#6f42c1', // สีม่วง
        'borderColor' => '#6f42c1',
        'extendedProps' => [
            'type' => 'borrow',
            'detail' => 'กำหนดส่งคืน: ' . $row['asset_name'] . ' (' . $row['transaction_no'] . ')',
            'who' => $row['fullname'],
            'status' => 'Due Date'
        ]
    ];
}

// ---------------------------------------------------------------------------
// 3. ดึงแผนบำรุงรักษา (PM Plans) **(เพิ่มส่วนนี้ให้ครับ)**
// ---------------------------------------------------------------------------
// จะได้เห็นว่าวันไหนมีนัดเข้าไปตรวจเช็คเครื่องจักร/อุปกรณ์
$sql_pm = "SELECT p.id, p.name, p.next_due_date, a.name as asset_name, a.asset_code 
           FROM pm_plans p 
           LEFT JOIN assets a ON p.asset_id = a.id 
           WHERE p.status = 'active' AND p.next_due_date IS NOT NULL";
$stmt = $pdo->query($sql_pm);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        'id' => 'pm_' . $row['id'],
        'title' => 'PM: ' . $row['name'],
        'start' => $row['next_due_date'],
        'backgroundColor' => '#0dcaf0', // สีฟ้าอ่อน (Cyan)
        'borderColor' => '#0dcaf0',
        'textColor' => '#000', // ตัวหนังสือสีดำจะได้อ่านง่ายบนพื้นฟ้า
        'extendedProps' => [
            'type' => 'pm', // เพิ่มประเภท PM
            'detail' => 'แผนบำรุงรักษา: ' . $row['name'] . ' (' . $row['asset_code'] . ')',
            'who' => 'System / Technician',
            'status' => 'Planned'
        ]
    ];
}

// ส่งค่ากลับเป็น JSON
header('Content-Type: application/json');
echo json_encode($events);
?>