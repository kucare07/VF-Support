<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) exit('ไม่พบรหัสครุภัณฑ์');

$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->execute([$_GET['id']]);
$asset = $stmt->fetch();

if (!$asset) exit('ไม่พบข้อมูล');

// สร้าง URL สำหรับสแกน (ลิงก์ไปหน้า View ของ Asset นั้นๆ)
// ปรับ localhost เป็น IP จริงของ Server หากต้องการให้มือถือสแกนได้
$scan_url = "http://" . $_SERVER['HTTP_HOST'] . "/it_support/modules/asset/index.php?scan=" . $asset['asset_code'];
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($scan_url);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Print QR: <?= $asset['asset_code'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; padding: 0; background: #eee; }
        .page { background: white; width: 5cm; height: 3cm; margin: 20px auto; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; display: flex; align-items: center; page-break-inside: avoid; }
        .qr-box { flex-shrink: 0; margin-right: 10px; }
        .qr-box img { width: 80px; height: 80px; }
        .info-box { flex-grow: 1; overflow: hidden; }
        .title { font-weight: bold; font-size: 14px; margin-bottom: 2px; }
        .code { font-size: 12px; color: #333; margin-bottom: 2px; font-family: monospace; }
        .detail { font-size: 10px; color: #555; }
        @media print {
            body { background: white; }
            .page { border: none; margin: 0; page-break-after: always; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align:center; padding: 10px;">
        <button onclick="window.print()">🖨️ สั่งพิมพ์</button>
    </div>

    <div class="page">
        <div class="qr-box">
            <img src="<?= $qr_api ?>" alt="QR Code">
        </div>
        <div class="info-box">
            <div class="title">ทรัพย์สินของบริษัท</div>
            <div class="code"><?= $asset['asset_code'] ?></div>
            <div class="detail"><?= mb_strimwidth($asset['name'], 0, 40, '...') ?></div>
            <div class="detail">รับประกัน: <?= $asset['warranty_expire'] ? date('d/m/Y', strtotime($asset['warranty_expire'])) : '-' ?></div>
        </div>
    </div>
</body>
</html>