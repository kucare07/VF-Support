<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

$id = $_GET['id'];
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

// ดึงข้อมูลเก่า
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $dept_id = $_POST['department_id'] ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Logic เปลี่ยนรหัสผ่าน (ถ้ากรอกมาใหม่ค่อยเปลี่ยน)
    $password_sql = "";
    $params = [$fullname, $role, $dept_id, $is_active];

    if (!empty($_POST['new_password'])) {
        $password_sql = ", password_hash = ?";
        $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }
    
    $params[] = $id; // ID สำหรับ WHERE

    try {
        $sql = "UPDATE users SET fullname = ?, role = ?, department_id = ?, is_active = ? $password_sql WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">แก้ไขข้อมูลผู้ใช้: <?= $user['username'] ?></span>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อ-นามสกุล</label>
                                    <input type="text" name="fullname" class="form-control" value="<?= $user['fullname'] ?>" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">แผนก</label>
                                        <select name="department_id" class="form-select">
                                            <option value="">-- เลือกแผนก --</option>
                                            <?php foreach($departments as $d): ?>
                                                <option value="<?= $d['id'] ?>" <?= $user['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                                    <?= $d['name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">สิทธิ์การใช้งาน</label>
                                        <select name="role" class="form-select">
                                            <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="technician" <?= $user['role'] == 'technician' ? 'selected' : '' ?>>Technician</option>
                                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label text-danger fw-bold">เปลี่ยนรหัสผ่าน (Reset Password)</label>
                                    <input type="text" name="new_password" class="form-control" placeholder="ปล่อยว่างไว้ถ้าไม่ต้องการเปลี่ยน">
                                </div>
                                
                                <div class="mb-4 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="activeSwitch" <?= $user['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activeSwitch">เปิดใช้งานบัญชีนี้ (Active)</label>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light">ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary px-4">บันทึกการเปลี่ยนแปลง</button>
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