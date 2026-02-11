<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php'; // Required for logActivity()

// If already logged in, redirect to Dashboard
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
            // Retrieve User data
            // Note: Ensuring 'is_active = 1' prevents banned users from logging in
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // Verify Password using standard Hash
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // Security: Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                // Set Session Variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id']; // Optional, if needed

                // Update Last Login Timestamp
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // ✅ LOGGING: Record successful login
                if (function_exists('logActivity')) {
                    logActivity($pdo, $user['id'], 'LOGIN', 'เข้าสู่ระบบสำเร็จ');
                }

                // Redirect to Dashboard
                header("Location: admin.php");
                exit();

            } else {
                // Invalid credentials
                $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
                
                // Optional: Log failed attempt (Be careful not to flood logs)
                // logActivity($pdo, null, 'LOGIN_FAILED', "Failed login attempt for: $username");
            }

        } catch (PDOException $e) {
            // In production, log this error to a file instead of showing it to the user
            error_log("Login DB Error: " . $e->getMessage());
            $error = "เกิดข้อผิดพลาดในการเชื่อมต่อระบบ";
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
    <title>เข้าสู่ระบบ - IT Support System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: #f0f2f5; 
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-card { 
            max-width: 400px; 
            width: 100%; 
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            border: none; 
            overflow: hidden;
        }
        .login-header { 
            background: #1e293b; 
            background: linear-gradient(to right, #1e293b, #0f172a);
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        .login-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #60a5fa;
        }
        .btn-primary { 
            background-color: #3b82f6; 
            border: none; 
            padding: 12px; 
            transition: all 0.3s;
        }
        .btn-primary:hover { 
            background-color: #2563eb; 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }
    </style>
</head>
<body>

<div class="login-card card">
    <div class="login-header">
        <div class="login-icon"><i class="bi bi-cpu"></i></div>
        <h3 class="mb-1 fw-bold">IT Service Desk</h3>
        <small class="text-white-50">เข้าสู่ระบบเพื่อจัดการงานแจ้งซ่อม</small>
    </div>
    <div class="card-body p-4 pt-5">
        <?php if($error): ?>
            <div class="alert alert-danger text-center small border-0 bg-danger-subtle text-danger d-flex align-items-center justify-content-center">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" required placeholder="ชื่อผู้ใช้งาน" autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" required placeholder="รหัสผ่าน">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ
            </button>
        </form>
    </div>
    <div class="card-footer bg-white text-center border-0 pb-4 pt-0">
        <small class="text-muted" style="font-size: 0.8rem;">
            ติดปัญหาการใช้งาน? ติดต่อฝ่ายไอที (โทร. 1234)
        </small>
    </div>
</div>

</body>
</html>