<?php
// เปิดแสดง Error ทั้งหมด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>1. Checking Paths...</h3>";
$paths = [
    '../../includes/auth.php',
    '../../config/db_connect.php',
    '../../includes/functions.php'
];
foreach ($paths as $path) {
    echo "File: $path -> " . (file_exists($path) ? "<span style='color:green'>Found</span>" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
}

require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

echo "<h3>2. Checking Database...</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_key IN ('line_channel_token', 'line_dest_id')");
    $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "Connected to DB!<br>";
    echo "Token: " . (!empty($data['line_channel_token']) ? "<span style='color:green'>OK (Length: ".strlen($data['line_channel_token']).")</span>" : "<span style='color:red'>EMPTY</span>") . "<br>";
    echo "Dest ID: " . (!empty($data['line_dest_id']) ? "<span style='color:green'>OK ({$data['line_dest_id']})</span>" : "<span style='color:red'>EMPTY</span>") . "<br>";

    if (!empty($data['line_channel_token']) && !empty($data['line_dest_id'])) {
        echo "<h3>3. Sending Test Message...</h3>";
        $res = sendLinePush($data['line_dest_id'], "Test from Debug Script", $data['line_channel_token']);
        echo "HTTP Status: " . $res['status'] . "<br>";
        echo "Response: <pre>" . print_r($res, true) . "</pre>";
    } else {
        echo "<h3 style='color:red'>STOP: No Token/ID found. Please Save Settings first.</h3>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red'>DB Error: " . $e->getMessage() . "</h3>";
}
?>