<?php
// includes/functions.php

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db_connect.php';
}

// 1. ดึงค่า Setting
if (!function_exists('getSetting')) {
    function getSetting($key)
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

// 2. Badge สถานะ
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status)
    {
        return match ($status) {
            'new' => '<span class="badge bg-danger">New</span>',
            'assigned' => '<span class="badge bg-primary">Assigned</span>',
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'resolved' => '<span class="badge bg-success">Resolved</span>',
            'closed' => '<span class="badge bg-secondary">Closed</span>',
            'borrowed' => '<span class="badge bg-warning text-dark">กำลังยืม</span>',
            'returned' => '<span class="badge bg-success">คืนแล้ว</span>',
            'active' => '<span class="badge bg-success">ปกติ</span>',
            'repair' => '<span class="badge bg-danger">ส่งซ่อม</span>',
            'write_off' => '<span class="badge bg-secondary">ตัดจำหน่าย</span>',
            'spare' => '<span class="badge bg-info text-dark">สำรอง</span>',
            default => '<span class="badge bg-light text-dark">' . $status . '</span>'
        };
    }
}

// 3. แปลงวันที่ไทย
if (!function_exists('thai_date')) {
    function thai_date($strDate)
    {
        if (!$strDate || $strDate == '0000-00-00') return '-';
        $strYear = date("Y", strtotime($strDate)) + 543;
        $strMonth = date("n", strtotime($strDate));
        $strDay = date("j", strtotime($strDate));
        $strMonthCut = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
        $strMonthThai = $strMonthCut[$strMonth];
        return "$strDay $strMonthThai $strYear";
    }
}

// --------------------------------------------------------
// 4. ฟังก์ชันส่งไลน์ (Messaging API - Push Message)
// ✅ เปลี่ยนมาใช้ตัวนี้แทน Notify
// --------------------------------------------------------
if (!function_exists('sendLineNotify')) {
    // ใช้ชื่อฟังก์ชันเดิม (sendLineNotify) เพื่อไม่ต้องไปแก้ไฟล์อื่นเยอะ
    // แต่ไส้ในเปลี่ยนเป็น Messaging API แล้ว
    function sendLineNotify($message)
    {

        // 1. ดึงค่าจาก Database
        $access_token = getSetting('line_channel_token'); // Long-lived Access Token
        $dest_id = getSetting('line_dest_id');            // User ID หรือ Group ID

        // ถ้าไม่มีค่า จบการทำงาน
        if (empty($access_token) || empty($dest_id)) {
            return false;
        }

        // 2. เตรียม URL และ Header
        $url = 'https://api.line.me/v2/bot/message/push';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        // 3. เตรียม Body (ส่งแบบ Text Message)
        $data = [
            'to' => $dest_id,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        // 4. ยิง Curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // เช็คผลลัพธ์ (200 = OK)
        return ($httpCode == 200);
    }
}

// 5. Log Asset
if (!function_exists('logAssetAction')) {
    function logAssetAction($asset_id, $action, $details = '')
    {
        global $pdo;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? 0;

        try {
            $sql = "INSERT INTO asset_logs (asset_id, user_id, action, details, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$asset_id, $user_id, $action, $details]);
        } catch (Exception $e) {
        }
    }
}

// --------------------------------------------------------
// 6. ฟังก์ชันอัปโหลดไฟล์ที่ปลอดภัย (Secure Upload)
// --------------------------------------------------------
if (!function_exists('uploadSecureFile')) {
    function uploadSecureFile($fileInput, $targetDir = '../../uploads/')
    {
        // 1. เช็คว่ามีการส่งไฟล์มาไหม
        if (empty($fileInput['name'])) return null;

        // 2. เช็ค Error
        if ($fileInput['error'] !== UPLOAD_ERR_OK) return null;

        // 3. กำหนดนามสกุลที่อนุญาต (Whitelist)
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $extension = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExts)) {
            // ถ้านามสกุลไม่ปลอดภัย ให้ข้ามไปเลย หรือ return error
            return null;
        }

        // 4. ตรวจสอบ MIME Type จริงๆ ของไฟล์ (กันการปลอมนามสกุล)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fileInput['tmp_name']);

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($mime, $allowedMimes)) {
            return null;
        }

        // 5. ตั้งชื่อไฟล์ใหม่ (Random) ป้องกันชื่อซ้ำและชื่อแปลกๆ
        $newFilename = uniqid('file_') . '_' . time() . '.' . $extension;

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 6. ย้ายไฟล์
        if (move_uploaded_file($fileInput['tmp_name'], $targetDir . $newFilename)) {
            return $newFilename;
        }

        return null;
    }

    // --- ฟังก์ชันบันทึก Log (Audit Log) ---
    function logActivity($pdo, $user_id, $action, $description)
    {
        try {
            // หา IP Address ของผู้ใช้
            $ip = $_SERVER['REMOTE_ADDR'];

            $sql = "INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $action, $description, $ip]);
        } catch (Exception $e) {
            // ถ้าบันทึก Log ไม่ได้ ไม่ต้องให้ระบบล่ม แค่ปล่อยผ่านไป
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

// เพิ่มต่อท้ายไฟล์ functions.php
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}


// ฟังก์ชันส่ง Line Notify (แจ้งเตือนเข้ากลุ่มเจ้าหน้าที่)
function sendLineNotify($message, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "message=" . $message);
    $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// ฟังก์ชันดึง Token จาก Database (ตาราง settings)
function getSystemSetting($key, $pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}