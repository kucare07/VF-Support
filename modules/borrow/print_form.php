<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) exit('No ID');

$sql = "SELECT b.*, 
        a.asset_code, a.name as asset_name, a.brand, a.model, a.serial_number,
        u.fullname as user_name, u.position, d.name as dept_name,
        h.fullname as handler_name
        FROM borrow_transactions b
        JOIN assets a ON b.asset_id = a.id
        JOIN users u ON b.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        JOIN users h ON b.handler_id = h.id
        WHERE b.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_GET['id']]);
$data = $stmt->fetch();

if (!$data) exit('ไม่พบข้อมูล');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบยืมพัสดุ - <?= $data['transaction_no'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #eee; }
        .page { background: white; width: 21cm; min-height: 29.7cm; margin: 20px auto; padding: 2cm; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h3 { font-weight: bold; text-align: center; margin-bottom: 30px; }
        .head-info { margin-bottom: 20px; }
        .table-custom th, .table-custom td { padding: 10px; border: 1px solid #000; }
        .sign-area { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
        .sign-box { width: 45%; }
        @media print {
            body { background: white; }
            .page { box-shadow: none; margin: 0; width: 100%; height: auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print text-center py-3">
        <button onclick="window.print()" class="btn btn-primary">🖨️ พิมพ์เอกสาร / Print</button>
    </div>

    <div class="page">
        <h3>ใบยืมพัสดุ / อุปกรณ์คอมพิวเตอร์<br><small style="font-size: 16px; font-weight: normal;">Borrowing Form</small></h3>
        
        <div class="d-flex justify-content-between mb-4">
            <div>
                <strong>วันที่ยืม:</strong> <?= thai_date(date('Y-m-d', strtotime($data['borrow_date']))) ?><br>
                <strong>กำหนดคืน:</strong> <?= $data['return_due_date'] ? thai_date($data['return_due_date']) : 'ไม่มีกำหนด' ?>
            </div>
            <div class="text-end">
                <strong>เลขที่:</strong> <?= $data['transaction_no'] ?><br>
                <strong>สถานะ:</strong> <?= $data['status']=='borrowed'?'กำลังยืม':'คืนแล้ว' ?>
            </div>
        </div>

        <div class="card mb-4 border-dark">
            <div class="card-header bg-transparent border-dark fw-bold">ข้อมูลผู้ยืม (Borrower)</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><strong>ชื่อ-สกุล:</strong> <?= $data['user_name'] ?></div>
                    <div class="col-6"><strong>แผนก:</strong> <?= $data['dept_name'] ?></div>
                    <div class="col-12 mt-2"><strong>ตำแหน่ง:</strong> <?= $data['position'] ?: '-' ?></div>
                </div>
            </div>
        </div>

        <table class="table table-custom w-100 mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">ลำดับ</th>
                    <th>รายการทรัพย์สิน (Description)</th>
                    <th>รหัสทรัพย์สิน (Asset Code)</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <?= $data['name'] ?><br>
                        <small>ยี่ห้อ: <?= $data['brand'] ?> รุ่น: <?= $data['model'] ?></small><br>
                        <small>S/N: <?= $data['serial_number'] ?></small>
                    </td>
                    <td><?= $data['asset_code'] ?></td>
                    <td><?= $data['note'] ?></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4">
            <p><strong>ข้อตกลง:</strong> ข้าพเจ้าขอรับรองว่าจะดูแลรักษาอุปกรณ์ดังกล่าวเป็นอย่างดี หากเกิดความเสียหาย หรือสูญหาย ข้าพเจ้ายินดีรับผิดชอบตามระเบียบของบริษัท</p>
        </div>

        <div class="sign-area">
            <div class="sign-box">
                <br><br>...........................................................<br>
                ( <?= $data['user_name'] ?> )<br>
                ผู้ยืม / Borrower<br>
                วันที่ ........................................
            </div>
            <div class="sign-box">
                <br><br>...........................................................<br>
                ( <?= $data['handler_name'] ?> )<br>
                เจ้าหน้าที่จ่ายของ / IT Support<br>
                วันที่ ........................................
            </div>
        </div>
    </div>
</body>
</html>