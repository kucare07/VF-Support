<?php
require_once 'includes/auth.php';
require_once 'config/db_connect.php';

$success = "";
$error = "";

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Update ชื่อ
    $stmt = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
    $stmt->execute([$fullname, $id]);
    $_SESSION['fullname'] = $fullname; // อัปเดต Session

    // Update รหัสผ่าน (ถ้ากรอก)
    if (!empty($password)) {
        if ($password === $confirm_password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            $success = "บันทึกข้อมูลและเปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
        } else {
            $error = "รหัสผ่านยืนยันไม่ตรงกัน";
        }
    } else {
        $success = "บันทึกข้อมูลเรียบร้อยแล้ว";
    }
}

// ดึงข้อมูลปัจจุบัน
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<?php require_once 'includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">ข้อมูลส่วนตัว (My Profile)</span>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px; font-size: 2rem;">
                                    <?= mb_substr($user['fullname'], 0, 1) ?>
                                </div>
                                <h5 class="fw-bold"><?= $user['fullname'] ?></h5>
                                <span class="badge bg-secondary"><?= ucfirst($user['role']) ?></span>
                            </div>

                            <?php if($success): ?>
                                <div class="alert alert-success small"><?= $success ?></div>
                            <?php endif; ?>
                            <?php if($error): ?>
                                <div class="alert alert-danger small"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control bg-light" value="<?= $user['username'] ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ชื่อ-นามสกุล</label>
                                    <input type="text" name="fullname" class="form-control" value="<?= $user['fullname'] ?>" required>
                                </div>
                                
                                <hr class="my-4">
                                <h6 class="fw-bold text-primary mb-3">เปลี่ยนรหัสผ่าน</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">รหัสผ่านใหม่</label>
                                    <input type="password" name="password" class="form-control" placeholder="กรอกเฉพาะเมื่อต้องการเปลี่ยน">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="กรอกอีกครั้ง">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">บันทึกการเปลี่ยนแปลง</button>
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
<?php require_once 'includes/footer.php'; ?>