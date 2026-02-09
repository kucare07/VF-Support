<?php
// ไฟล์สำหรับดู Group ID (Webhook)
$input = file_get_contents('php://input');
$events = json_decode($input, true);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        // บันทึก ID ลงไฟล์ log.txt
        $log = date('Y-m-d H:i:s') . "\n";
        if (isset($event['source']['groupId'])) {
            $log .= "Group ID: " . $event['source']['groupId'] . "\n";
        } elseif (isset($event['source']['userId'])) {
            $log .= "User ID: " . $event['source']['userId'] . "\n";
        }
        $log .= "-------------------------\n";
        file_put_contents('line_log.txt', $log, FILE_APPEND);
    }
}
echo "OK";
?>