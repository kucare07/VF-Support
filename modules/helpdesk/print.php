<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) die("Invalid ID");
$id = $_GET['id'];

// ดึงข้อมูล Ticket
$sql = "SELECT t.*, u.fullname as requester, u.phone, d.name as department, 
        c.name as category, a.asset_code, a.name as asset_name, 
        tech.fullname as technician
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN assets a ON t.asset_code = a.asset_code
        LEFT JOIN users tech ON t.assigned_to = tech.id
        WHERE t.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) die("Ticket not found");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Job Sheet <?= str_pad($t['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #eee; font-size: 14px; }
        .page {
            background: #fff;
            width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            margin: 20px auto;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        @media print {
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; width: 100%; min-height: auto; padding: 0; }
            .no-print { display: none !important; }
        }
        .header-line { border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 10px; }
        .box-section { border: 1px solid #ccc; padding: 12px; border-radius: 4px; margin-bottom: 12px; }
        .label-head { font-weight: 600; color: #333; }
        .sign-area { margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 20px; }
    </style>
</head>
<body>

<div class="text-center mt-3 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
        🖨️ สั่งพิมพ์ / บันทึก PDF
    </button>
</div>

<div class="page">
    <div class="d-flex justify-content-between align-items-center header-line">
        <div class="d-flex align-items-center">
            <div style="width: 50px; height: 50px; background: #0d6efd; color: #fff; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold; font-size:20px; margin-right:15px;">
                IT
            </div>
            <div>
                <h5 class="mb-0 fw-bold">ใบแจ้งซ่อม / Service Job</h5>
                <small>IT Service Center</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">JOB NO: <?= str_pad($t['id'], 5, '0', STR_PAD_LEFT) ?></h5> <small>วันที่: <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small>
        </div>
    </div>

    <div class="box-section">
        <h6 class="fw-bold border-bottom pb-2 mb-3 bg-light p-1">1. ข้อมูลผู้แจ้ง (Requester)</h6>
        <div class="row g-2">
            <div class="col-6">
                <span class="label-head">ชื่อ-นามสกุล:</span> <?= $t['requester'] ?>
            </div>
            <div class="col-6">
                <span class="label-head">แผนก/ฝ่าย:</span> <?= $t['department'] ?? '-' ?>
            </div>
            <div class="col-6">
                <span class="label-head">เบอร์โทร:</span> <?= $t['phone'] ?? '-' ?>
            </div>
            <div class="col-6">
                <span class="label-head">สถานที่:</span> <?= isset($t['location_name']) ? $t['location_name'] : '-' ?>
            </div>
        </div>
    </div>

    <div class="box-section">
        <h6 class="fw-bold border-bottom pb-2 mb-3 bg-light p-1">2. รายละเอียด (Details)</h6>
        <div class="row g-2">
            <div class="col-12">
                <span class="label-head">ประเภทงาน:</span> <?= ucfirst($t['type']) ?>
            </div>
            <div class="col-6">
                <span class="label-head">หมวดหมู่:</span> <?= $t['category'] ?>
            </div>
            <div class="col-6">
                <span class="label-head">ทรัพย์สิน:</span> <?= $t['asset_code'] ?? '-' ?> <?= !empty($t['asset_name']) ? '('.$t['asset_name'].')' : '' ?>
            </div>
            <div class="col-12 mt-2">
                <span class="label-head d-block mb-1">รายละเอียดอาการ:</span>
                <div class="p-2 border rounded" style="min-height: 60px;">
                    <?= nl2br($t['description']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="box-section">
        <h6 class="fw-bold border-bottom pb-2 mb-3 bg-light p-1">3. การดำเนินการ (For Technician)</h6>
        <div class="row g-2">
            <div class="col-6">
                <span class="label-head">ผู้รับผิดชอบ:</span> <?= $t['technician'] ?? '..........................................................' ?>
            </div>
            <div class="col-6">
                <span class="label-head">ความเร่งด่วน:</span> <?= ucfirst($t['priority']) ?>
            </div>
            <div class="col-12 mt-3">
                <span class="label-head">ผลการดำเนินการ / วิธีแก้ไข:</span>
                <div style="height: 80px; border-bottom: 1px dotted #ccc; margin-top: 20px;"></div>
                <div style="height: 30px; border-bottom: 1px dotted #ccc;"></div>
            </div>
            <div class="col-12 mt-3">
                 <span class="label-head">สถานะงาน:</span><br>
                 <span style="display:inline-block; width:150px;">⬜ เสร็จสิ้น</span>
                 <span style="display:inline-block; width:150px;">⬜ รออะไหล่</span>
                 <span style="display:inline-block; width:150px;">⬜ ส่งซ่อมภายนอก</span>
                 <span>⬜ อื่นๆ ...........................</span>
            </div>
        </div>
    </div>

    <div class="row sign-area text-center">
        <div class="col-6">
            <br><br>
            _____________________________<br>
            ( ....................................................... )<br>
            <span class="fw-bold">ผู้แจ้ง / ผู้รับมอบงาน</span><br>
            วันที่ ....... / ....... / ...........
        </div>
        <div class="col-6">
            <br><br>
            _____________________________<br>
            ( <?= $t['technician'] ?? '.......................................................' ?> )<br>
            <span class="fw-bold">เจ้าหน้าที่ผู้ปฏิบัติงาน</span><br>
            วันที่ ....... / ....... / ...........
        </div>
    </div>

</div>

</body>
</html>