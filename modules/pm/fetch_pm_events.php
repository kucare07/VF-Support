<?php
// modules/pm/fetch_pm_events.php
require_once '../../config/db_connect.php';

// ดึงแผนบำรุงรักษาทั้งหมดที่มีกำหนดวันถัดไป
$sql = "SELECT p.*, a.name as asset_name, a.asset_code 
        FROM pm_plans p 
        LEFT JOIN assets a ON p.asset_id = a.id 
        WHERE p.status = 'active' AND p.next_due_date IS NOT NULL";
$stmt = $pdo->query($sql);
$events = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // กำหนดสีตามความเร่งด่วน (ถ้าเลยกำหนดเป็นสีแดง, ใกล้ถึงเป็นสีเหลือง)
    $color = '#0dcaf0'; // สีฟ้า (ปกติ)
    $today = date('Y-m-d');
    
    if ($row['next_due_date'] < $today) {
        $color = '#dc3545'; // แดง (เกินกำหนด)
    } elseif ($row['next_due_date'] == $today) {
        $color = '#ffc107'; // เหลือง (วันนี้)
    }

    $events[] = [
        'id' => $row['id'],
        'title' => $row['name'] . ' (' . $row['asset_code'] . ')',
        'start' => $row['next_due_date'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#000',
        'extendedProps' => [
            'detail' => 'ทรัพย์สิน: ' . $row['asset_name'],
            'freq' => 'ทุกๆ ' . $row['frequency_days'] . ' วัน',
            'notes' => $row['notes']
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
?>