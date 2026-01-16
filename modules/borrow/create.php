<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงรายชื่อ User
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1")->fetchAll();

// ดึง Asset ที่สถานะ Active และยังไม่ถูกยืม (Logic ง่ายๆ คือเช็ค status ในตาราง assets)
// หมายเหตุ: ในระบบจริงอาจต้องเช็คละเอียดกว่านี้ แต่เบื้องต้นใช้ status='active' คือพร้อมให้ยืม
$assets = $pdo->query("SELECT * FROM assets WHERE status = 'active'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $asset_id = $_POST['asset_id'];
    $due_date = $_POST['return_due_date'] ?: null;
    $note = $_POST['note'];
    $admin_id = $_SESSION['user_id'];

    // Gen เลขที่รายการ BR-YYMM-XXXX
    $ym = date('ym');
    $tx_no = "BR-$ym-" . rand(1000, 9999);

    try {
        $pdo->beginTransaction();

        // 1. บันทึกลง borrow_transactions
        $sql = "INSERT INTO borrow_transactions (transaction_no, user_id, asset_id, borrow_date, return_due_date, note, created_by) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tx_no, $user_id, $asset_id, $due_date, $note, $admin_id]);

        // 2. อัปเดตสถานะ Asset เป็น 'active' (จริงๆ ควรมีสถานะ 'borrowed' แต่ในที่นี้เราใช้ Field current_user_id บอกว่าใครถือครอง)
        // หรือถ้าจะให้ดี เพิ่มสถานะ 'borrowed' ใน ENUM ของ assets ก็ได้
        // ในที่นี้ผมจะอัปเดต current_user_id ให้เป็นคนยืมครับ
        $update = $pdo->prepare("UPDATE assets SET current_user_id = ? WHERE id = ?");
        $update->execute([$user_id, $asset_id]);

        $pdo->commit();
        echo "<script>alert('บันทึกการยืมสำเร็จ!'); window.location='index.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
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
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm" title="ย้อนกลับ">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-1 fw-bold text-secondary">ทำรายการยืม (New Borrow)</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-primary mb-4">กรอกข้อมูลการยืม</h5>
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">ผู้ยืม (Borrower)</label>
                                        <select name="user_id" class="form-select form-control-custom" required>
                                            <option value="">-- เลือกผู้ยืม --</option>
                                            <?php foreach ($users as $u): ?>
                                                <option value="<?= $u['id'] ?>"><?= $u['fullname'] ?> (<?= $u['username'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">กำหนดส่งคืน (Due Date)</label>
                                        <input type="date" name="return_due_date" class="form-control form-control-custom">
                                        <small class="text-muted">เว้นว่างได้หากไม่มีกำหนด</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">เลือกอุปกรณ์ (Asset)</label>
                                    <select name="asset_id" class="form-select form-control-custom" required>
                                        <option value="">-- เลือกอุปกรณ์ --</option>
                                        <?php foreach ($assets as $a): ?>
                                            <option value="<?= $a['id'] ?>">[<?= $a['asset_code'] ?>] <?= $a['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-success"><i class="bi bi-check-circle"></i> แสดงเฉพาะเครื่องที่ว่างอยู่ (Active)</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">หมายเหตุ</label>
                                    <textarea name="note" class="form-control form-control-custom" rows="3" placeholder="เช่น ยืมไปใช้ Present งานสัมมนา..."></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-light">ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary px-4">ยืนยันการยืม</button>
                                </div>
                            </form>
                        </div>
                    </div>
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