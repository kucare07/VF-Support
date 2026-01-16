<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงข้อมูลตัวเลือก (Dropdown Options)
$types = $pdo->query("SELECT * FROM asset_types")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1")->fetchAll();

// บันทึกข้อมูลเมื่อกด Submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "INSERT INTO assets (
            asset_code, name, asset_type_id, serial_number, brand, model, 
            spec_cpu, spec_ram, spec_storage, os_license, 
            supplier_id, purchase_date, price, warranty_expire,
            location_id, current_user_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['asset_code'],
            $_POST['name'],
            $_POST['asset_type_id'],
            $_POST['serial_number'],
            $_POST['brand'],
            $_POST['model'],
            $_POST['spec_cpu'],
            $_POST['spec_ram'],
            $_POST['spec_storage'],
            $_POST['os_license'],
            $_POST['supplier_id'] ?: null,
            $_POST['purchase_date'] ?: null,
            $_POST['price'] ?: 0,
            $_POST['warranty_expire'] ?: null,
            $_POST['location_id'] ?: null,
            $_POST['current_user_id'] ?: null,
            $_POST['status']
        ]);

        echo "<script>alert('บันทึกข้อมูลครุภัณฑ์เรียบร้อย!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');</script>";
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm" title="ย้อนกลับ">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-1 fw-bold text-secondary">เพิ่มครุภัณฑ์ใหม่ (Add Asset)</span>
            </div>
        </nav>

        <div class="container-fluid p-4">

            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-primary bg-opacity-10 py-3 border-0" style="border-radius: 12px 12px 0 0;">
                    <div class="d-flex align-items-center text-primary">
                        <i class="bi bi-pc-display-horizontal fs-4 me-2"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">ฟอร์มลงทะเบียนทรัพย์สิน</h6>
                            <small class="text-muted" style="font-size: 0.8rem;">กรอกข้อมูลรายละเอียดอุปกรณ์เพื่อนำเข้าระบบ</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST">

                        <div class="mb-4">
                            <div class="form-section-title">ข้อมูลทั่วไป (General Information)</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">รหัสครุภัณฑ์ (Asset Code) <span class="text-danger">*</span></label>
                                    <input type="text" name="asset_code" class="form-control form-control-custom" required placeholder="ระบุเลขครุภัณฑ์ (เช่น AST-67-001)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ชื่อเครื่อง (Host Name / Asset Name) <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-custom" required placeholder="ระบุชื่อเครื่อง (เช่น NB-IT-01)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ประเภทอุปกรณ์ (Type) <span class="text-danger">*</span></label>
                                    <select name="asset_type_id" class="form-select form-control-custom" required>
                                        <option value="">- เลือกประเภท -</option>
                                        <?php foreach ($types as $t): ?>
                                            <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Serial Number (S/N)</label>
                                    <input type="text" name="serial_number" class="form-control form-control-custom" placeholder="ระบุหมายเลขเครื่องจากโรงงาน">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ยี่ห้อ (Brand)</label>
                                    <input type="text" name="brand" class="form-control form-control-custom" placeholder="เช่น Dell, HP, Lenovo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">รุ่น (Model)</label>
                                    <input type="text" name="model" class="form-control form-control-custom" placeholder="เช่น Latitude 3420">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-section-title">สเปกเครื่อง (Specification)</div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">CPU Model</label>
                                    <input type="text" name="spec_cpu" class="form-control form-control-custom" placeholder="เช่น Intel Core i5">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">RAM</label>
                                    <input type="text" name="spec_ram" class="form-control form-control-custom" placeholder="เช่น 16GB">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Storage (HDD/SSD)</label>
                                    <input type="text" name="spec_storage" class="form-control form-control-custom" placeholder="เช่น SSD 512GB">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">OS / License</label>
                                    <input type="text" name="os_license" class="form-control form-control-custom" placeholder="เช่น Win 11 Pro">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-section-title">สถานะและการครอบครอง</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">สถานะปัจจุบัน (Status)</label>
                                    <select name="status" class="form-select form-control-custom">
                                        <option value="active" selected>✅ ใช้งานปกติ (Active)</option>
                                        <option value="spare">📦 เครื่องสำรอง (Spare)</option>
                                        <option value="repair">🔧 ส่งซ่อม (Repair)</option>
                                        <option value="write_off">🗑️ ตัดจำหน่าย (Write-off)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">ผู้ถือครอง (Owner)</label>
                                    <select name="current_user_id" class="form-select form-control-custom">
                                        <option value="">-- ส่วนกลาง / ยังไม่มีเจ้าของ --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= $u['id'] ?>"><?= $u['fullname'] ?> (<?= $u['department_id'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">สถานที่ตั้ง (Location)</label>
                                    <select name="location_id" class="form-select form-control-custom">
                                        <option value="">-- เลือกสถานที่ --</option>
                                        <?php foreach ($locations as $l): ?>
                                            <option value="<?= $l['id'] ?>"><?= $l['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="accordion" id="accordionFinance">
                                <div class="accordion-item border rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFinance">
                                            <i class="bi bi-currency-dollar me-2"></i> ข้อมูลการจัดซื้อและการรับประกัน (คลิกเพื่อแสดง)
                                        </button>
                                    </h2>
                                    <div id="collapseFinance" class="accordion-collapse collapse" data-bs-parent="#accordionFinance">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted">ผู้จำหน่าย (Supplier)</label>
                                                    <select name="supplier_id" class="form-select form-control-custom">
                                                        <option value="">-- เลือก Supplier --</option>
                                                        <?php foreach ($suppliers as $s): ?>
                                                            <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small text-muted">วันที่ซื้อ</label>
                                                    <input type="date" name="purchase_date" class="form-control form-control-custom">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small text-muted">หมดประกันวันที่</label>
                                                    <input type="date" name="warranty_expire" class="form-control form-control-custom">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small text-muted">ราคา (บาท)</label>
                                                    <input type="number" name="price" class="form-control form-control-custom" placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-light border px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-save me-2"></i>บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>