<?php
session_start();
require_once 'config/db_connect.php';

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไป Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            // ดึงข้อมูล User จาก Username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // ตรวจสอบรหัสผ่าน
            // หมายเหตุ: กรณีทดสอบ ถ้าใน DB เป็น Plain Text '123456' อาจต้องแก้ตรงนี้ชั่วคราว
            // แต่ code นี้รองรับ Standard Password Hash
            if ($user && password_verify($password, $user['password_hash'])) {
                // ล็อกอินสำเร็จ -> เก็บ Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                // Update Last Login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Redirect ไปหน้า Dashboard
                header("Location: index.php");
                exit();
            } else {
                // กรณีรหัสผิด หรือไม่มี User
                // *Hack สำหรับ Dev*: ถ้าใน DB ยังเป็น 123456 แบบ Plain Text ให้ยอมผ่านไปก่อน (เฉพาะช่วง dev)
                if ($user && $user['password_hash'] === $password) {
                     // Auto Fix Hash (แปลงให้ปลอดภัยทันที)
                     $newHash = password_hash($password, PASSWORD_DEFAULT);
                     $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                     
                     // Set Session (Login สำเร็จ)
                     $_SESSION['user_id'] = $user['id'];
                     $_SESSION['fullname'] = $user['fullname'];
                     $_SESSION['role'] = $user['role'];
                     header("Location: index.php");
                     exit();
                }

                $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 400px; width: 100%; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: none; }
        .login-header { background: #1e293b; color: white; border-radius: 15px 15px 0 0; padding: 30px; text-align: center; }
        .btn-primary { background-color: #3b82f6; border: none; padding: 10px; }
        .btn-primary:hover { background-color: #2563eb; }
    </style>
</head>
<body>

<div class="login-card card">
    <div class="login-header">
        <h3 class="mb-1"><i class="bi bi-shield-lock"></i> IT Service</h3>
        <small class="opacity-75">สำนักงานกองทุนหมู่บ้านและชุมชนเมืองแห่งชาติ</small>
    </div>
    <div class="card-body p-4">
        <?php if($error): ?>
            <div class="alert alert-danger text-center small"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="admin" autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill">เข้าสู่ระบบ</button>
        </form>
    </div>
    <div class="card-footer bg-white text-center border-0 pb-4">
        <small class="text-muted">ติดต่อฝ่ายไอที โทร. 1234</small>
    </div>
</div>

</body>
</html>