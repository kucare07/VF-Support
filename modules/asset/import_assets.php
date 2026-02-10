<?php
/**
 * Import Assets from XLSX
 * Path: modules/asset/import_xlsx.php
 */

require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$errors = [];
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    
    try {
        $file = $_FILES['excel_file']['tmp_name'];
        
        // โหลดไฟล์
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        
        // เริ่มจากแถวที่ 2 (ข้ามหัวตาราง)
        for ($row = 2; $row <= $highestRow; $row++) {
            $asset_code = $sheet->getCell('A' . $row)->getValue();
            
            // ข้ามแถวว่าง
            if (empty($asset_code)) continue;
            
            // อ่านข้อมูล
            $data = [
                'asset_code' => $asset_code,
                'name' => $sheet->getCell('B' . $row)->getValue(),
                'brand' => $sheet->getCell('C' . $row)->getValue(),
                'model' => $sheet->getCell('D' . $row)->getValue(),
                'serial_number' => $sheet->getCell('E' . $row)->getValue(),
                'type_name' => $sheet->getCell('F' . $row)->getValue(),
                'location_name' => $sheet->getCell('G' . $row)->getValue(),
                'status' => $sheet->getCell('I' . $row)->getValue() ?: 'active',
                'price' => $sheet->getCell('J' . $row)->getValue(),
                'purchase_date' => $sheet->getCell('K' . $row)->getValue(),
                'warranty_expire' => $sheet->getCell('L' . $row)->getValue(),
            ];
            
            // แปลง Type Name -> Type ID
            $type_stmt = $pdo->prepare("SELECT id FROM asset_types WHERE name = ?");
            $type_stmt->execute([$data['type_name']]);
            $type_id = $type_stmt->fetchColumn();
            
            if (!$type_id) {
                $errors[] = "แถว $row: ไม่พบประเภท '{$data['type_name']}'";
                continue;
            }
            
            // แปลง Location Name -> Location ID
            $loc_id = null;
            if (!empty($data['location_name'])) {
                $loc_stmt = $pdo->prepare("SELECT id FROM locations WHERE name = ?");
                $loc_stmt->execute([$data['location_name']]);
                $loc_id = $loc_stmt->fetchColumn();
            }
            
            // เช็คว่ามี Asset Code นี้อยู่แล้วหรือไม่
            $check = $pdo->prepare("SELECT id FROM assets WHERE asset_code = ?");
            $check->execute([$data['asset_code']]);
            
            if ($check->fetchColumn()) {
                // UPDATE
                $sql = "UPDATE assets SET 
                        name=?, brand=?, model=?, serial_number=?, asset_type_id=?, 
                        location_id=?, status=?, price=?, purchase_date=?, warranty_expire=?
                        WHERE asset_code=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['name'], $data['brand'], $data['model'], $data['serial_number'],
                    $type_id, $loc_id, $data['status'], $data['price'], 
                    $data['purchase_date'], $data['warranty_expire'], $data['asset_code']
                ]);
            } else {
                // INSERT
                $sql = "INSERT INTO assets (asset_code, name, brand, model, serial_number, 
                        asset_type_id, location_id, status, price, purchase_date, warranty_expire) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['asset_code'], $data['name'], $data['brand'], $data['model'], 
                    $data['serial_number'], $type_id, $loc_id, $data['status'], 
                    $data['price'], $data['purchase_date'], $data['warranty_expire']
                ]);
            }
            
            $success_count++;
        }
        
        $_SESSION['import_result'] = [
            'success' => $success_count,
            'errors' => $errors
        ];
        
        header("Location: index.php?msg=imported");
        exit();
        
    } catch (Exception $e) {
        $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// --- แสดงหน้า UI ---
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Import Assets</span>
        <span class="text-muted ms-2 small border-start ps-2">นำเข้าข้อมูลครุภัณฑ์จาก Excel</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-warning">
                    <h6 class="fw-bold">พบข้อผิดพลาด:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold text-primary m-0">
                                <i class="bi bi-upload me-2"></i>อัปโหลดไฟล์ Excel (.xlsx)
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">เลือกไฟล์ Excel</label>
                                    <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> รองรับเฉพาะไฟล์ .xlsx เท่านั้น
                                    </div>
                                </div>
                                
                                <div class="alert alert-info small">
                                    <strong>คำแนะนำ:</strong>
                                    <ul class="mb-0">
                                        <li>ดาวน์โหลด Template ด้านล่างก่อน</li>
                                        <li>กรอกข้อมูลตาม Format ที่กำหนด</li>
                                        <li>หากรหัสครุภัณฑ์ซ้ำ ระบบจะ UPDATE ข้อมูลเก่า</li>
                                    </ul>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload me-2"></i>เริ่มนำเข้าข้อมูล
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-excel text-success" style="font-size: 3rem;"></i>
                            <h6 class="fw-bold mt-3">ดาวน์โหลด Template</h6>
                            <p class="small text-muted">ไฟล์ตัวอย่างพร้อมคอลัมน์ที่ถูกต้อง</p>
                            <a href="download_template.php" class="btn btn-success btn-sm">
                                <i class="bi bi-download me-1"></i> Download Template
                            </a>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white py-2">
                            <h6 class="fw-bold small m-0">รูปแบบไฟล์</h6>
                        </div>
                        <div class="card-body small">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted">Column A:</td><td class="fw-bold">รหัสครุภัณฑ์ *</td></tr>
                                <tr><td class="text-muted">Column B:</td><td>ชื่อ *</td></tr>
                                <tr><td class="text-muted">Column C:</td><td>ยี่ห้อ</td></tr>
                                <tr><td class="text-muted">Column F:</td><td>ประเภท *</td></tr>
                                <tr><td class="text-muted">Column J:</td><td>ราคา</td></tr>
                            </table>
                            <div class="text-muted" style="font-size: 0.75rem;">* = จำเป็นต้องมี</div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
