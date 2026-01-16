<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];

// 1. ดึงข้อมูล Asset เก่า
$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) { echo "ไม่พบข้อมูล"; exit(); }

// 2. ดึง Master Data (Dropdowns)
$types = $pdo->query("SELECT * FROM asset_types")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1")->fetchAll();

// 3. บันทึกการแก้ไข (Update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "UPDATE assets SET 
            asset_code=?, name=?, asset_type_id=?, serial_number=?, brand=?, model=?, 
            spec_cpu=?, spec_ram=?, spec_storage=?, os_license=?, 
            supplier_id=?, purchase_date=?, price=?, warranty_expire=?,
            location_id=?, current_user_id=?, status=?
            WHERE id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['asset_code'], $_POST['name'], $_POST['asset_type_id'], $_POST['serial_number'], $_POST['brand'], $_POST['model'],
            $_POST['spec_cpu'], $_POST['spec_ram'], $_POST['spec_storage'], $_POST['os_license'],
            $_POST['supplier_id'] ?: null, $_POST['purchase_date'] ?: null, $_POST['price'] ?: 0, $_POST['warranty_expire'] ?: null,
            $_POST['location_id'] ?: null, $_POST['current_user_id'] ?: null, $_POST['status'],
            $id
        ]);

        echo "<script>alert('แก้ไขข้อมูลเรียบร้อย!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm"><i class="bi bi-arrow-left"></i></a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
                <span class="ms-1 fw-bold text-secondary">แก้ไขครุภัณฑ์: <?= $asset['asset_code'] ?></span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <form method="POST">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 fw-bold">ข้อมูลทั่วไป</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">รหัสครุภัณฑ์</label>
                                        <input type="text" name="asset_code" class="form-control" required value="<?= $asset['asset_code'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">ชื่อเครื่อง</label>
                                        <input type="text" name="name" class="form-control" required value="<?= $asset['name'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">ประเภท</label>
                                        <select name="asset_type_id" class="form-select" required>
                                            <option value="">-- เลือก --</option>
                                            <?php foreach($types as $t): ?>
                                                <option value="<?= $t['id'] ?>" <?= $asset['asset_type_id'] == $t['id'] ? 'selected' : '' ?>>
                                                    <?= $t['name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Serial Number</label>
                                        <input type="text" name="serial_number" class="form-control" value="<?= $asset['serial_number'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">ยี่ห้อ</label>
                                        <input type="text" name="brand" class="form-control" value="<?= $asset['brand'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">รุ่น</label>
                                        <input type="text" name="model" class="form-control" value="<?= $asset['model'] ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 fw-bold">สเปกเครื่อง</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">CPU</label>
                                        <input type="text" name="spec_cpu" class="form-control" value="<?= $asset['spec_cpu'] ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">RAM</label>
                                        <input type="text" name="spec_ram" class="form-control" value="<?= $asset['spec_ram'] ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Storage</label>
                                        <input type="text" name="spec_storage" class="form-control" value="<?= $asset['spec_storage'] ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted">OS / License</label>
                                        <input type="text" name="os_license" class="form-control" value="<?= $asset['os_license'] ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 fw-bold">การครอบครอง</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">สถานะ</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $asset['status']=='active'?'selected':'' ?>>✅ ใช้งานปกติ</option>
                                        <option value="spare" <?= $asset['status']=='spare'?'selected':'' ?>>📦 เครื่องสำรอง</option>
                                        <option value="repair" <?= $asset['status']=='repair'?'selected':'' ?>>🔧 ส่งซ่อม</option>
                                        <option value="write_off" <?= $asset['status']=='write_off'?'selected':'' ?>>🗑️ ตัดจำหน่าย</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">ผู้ใช้งาน</label>
                                    <select name="current_user_id" class="form-select">
                                        <option value="">-- ว่าง --</option>
                                        <?php foreach($users as $u): ?>
                                            <option value="<?= $u['id'] ?>" <?= $asset['current_user_id'] == $u['id'] ? 'selected' : '' ?>>
                                                <?= $u['fullname'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">สถานที่</label>
                                    <select name="location_id" class="form-select">
                                        <option value="">-- เลือก --</option>
                                        <?php foreach($locations as $l): ?>
                                            <option value="<?= $l['id'] ?>" <?= $asset['location_id'] == $l['id'] ? 'selected' : '' ?>>
                                                <?= $l['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 fw-bold">การจัดซื้อ</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">วันที่ซื้อ</label>
                                    <input type="date" name="purchase_date" class="form-control" value="<?= $asset['purchase_date'] ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">หมดประกัน</label>
                                    <input type="date" name="warranty_expire" class="form-control" value="<?= $asset['warranty_expire'] ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">ราคา</label>
                                    <input type="number" name="price" class="form-control" value="<?= $asset['price'] ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 gap-2 pb-5">
                    <a href="index.php" class="btn btn-light border px-4">ยกเลิก</a>
                    <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save me-2"></i>อัปเดตข้อมูล</button>
                </div>
            </form>
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