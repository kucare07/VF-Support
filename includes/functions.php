<?php
// ฟังก์ชันส่ง LINE Notify
function sendLineNotify($message) {
    // 1. ไปออก Token ที่ https://notify-bot.line.me/my/
    // 2. เลือกกลุ่มที่ต้องการให้แจ้งเตือน
    // 3. นำ Token มาใส่ตรงนี้
    $token = "ใส่_LINE_TOKEN_ของคุณที่นี่"; 

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "message=" . $message);
    $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $token . '',);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// ฟังก์ชันแปลงวันที่เป็นไทย (แถมให้ครับ)
function thai_date($strDate) {
    $strYear = date("Y",strtotime($strDate))+543;
    $strMonth= date("n",strtotime($strDate));
    $strDay= date("j",strtotime($strDate));
    $strMonthCut = Array("","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค.");
    $strMonthThai=$strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}

/**
 * ส่งข้อความผ่าน LINE Messaging API
 * @param string $message ข้อความที่ต้องการส่ง
 */
function sendLineMessage($message) {
    // 1. ใส่ Channel Access Token (ได้จาก LINE Developers Console)
    $accessToken = "OYK70oopG5CdB7wOaSOmkZN8/NH0Z7R5MfeG6NrYaChb4WVZpk2i+at0YQ4UQtM6kCi7otx1BSLdXHNFgN4o9E5CDWe10xZEPI3IYBX2uJ+ETyP+3Dz8b9+iRZlEk8II3yneZ4Zz6qUbZGBqmMdx5QdB04t89/1O/w1cDnyilFU="; 
    
    // 2. ใส่ User ID หรือ Group ID ที่ต้องการให้แจ้งเตือน (Admin Group)
    // (ดูวิธีหา ID ด้านล่างคำตอบครับ)
    $targetId = "U50a12087eb945acedb7ffb5310b26082"; 

    $content = [
        'to' => $targetId,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);

    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>