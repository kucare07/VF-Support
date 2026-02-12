<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    // 1. ดึงค่า Config จาก Database (ตาราง system_settings)
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_key IN ('line_channel_token', 'line_dest_id')");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['setting_key']] = $row['setting_value'];
    }

    $token = $config['line_channel_token'] ?? '';
    $dest_id = $config['line_dest_id'] ?? '';

    // 2. ตรวจสอบว่ามีค่าไหม
    if (empty($token) || empty($dest_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ Token หรือ Destination ID ในระบบ กรุณาบันทึกข้อมูลก่อนทดสอบ']);
        exit;
    }

    // 3. ทดสอบส่ง
    $msg = "🔔 ทดสอบการเชื่อมต่อระบบ IT Support\n(" . date('d/m/Y H:i:s') . ")";
    $res = sendLinePush($dest_id, $msg, $token);

    // 4. ตรวจสอบผลลัพธ์จาก LINE
    if ($res['status'] == 200) {
        echo json_encode(['status' => 'success', 'message' => 'ส่งข้อความสำเร็จ! โปรดเช็ค LINE ของคุณ']);
    } else {
        $detail = json_decode($res['response'], true);
        $err_msg = $detail['message'] ?? 'Unknown Error';
        echo json_encode(['status' => 'error', 'message' => 'ส่งไม่ผ่าน (HTTP ' . $res['status'] . '): ' . $err_msg]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>