<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // เข้ารหัสรหัสผ่าน
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $dept_id = $_POST['department_id'] ?: null;

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, fullname, role, department_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$username, $password, $fullname, $role, $dept_id]);
        echo "<script>alert('เพิ่มผู้ใช้งานเรียบร้อย!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: ชื่อผู้ใช้ซ้ำหรือเกิดข้อผิดพลาด');</script>";
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">เพิ่มผู้ใช้งานใหม่</span>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 fw-bold">กรอกข้อมูลพนักงาน</div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Username (สำหรับ Login) <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control" required placeholder="เช่น somchai.j">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">รหัสผ่านเริ่มต้น <span class="text-danger">*</span></label>
                                        <input type="text" name="password" class="form-control" required value="123456">
                                        <small class="text-muted">เปลี่ยนได้ภายหลัง</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" class="form-control" required placeholder="นายสมชาย ใจดี">
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">แผนก/ฝ่าย</label>
                                        <select name="department_id" class="form-select">
                                            <option value="">-- เลือกแผนก --</option>
                                            <?php foreach($departments as $d): ?>
                                                <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">สิทธิ์การใช้งาน (Role)</label>
                                        <select name="role" class="form-select" required>
                                            <option value="user">User (ผู้แจ้งปัญหา)</option>
                                            <option value="technician">Technician (เจ้าหน้าที่ไอที)</option>
                                            <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light">ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary px-4">บันทึก</button>
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