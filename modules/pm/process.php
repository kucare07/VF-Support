<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

$action = $_REQUEST['action'] ?? '';

// --- Add ---
if ($action == 'add') {
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $stmt = $pdo->prepare("INSERT INTO pm_plans (name, asset_id, frequency_days, next_due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['asset_id'], $_POST['frequency_days'], $_POST['next_due_date'], $status, $_POST['notes']]);
    header("Location: index.php?msg=added");
}

// --- Edit ---
elseif ($action == 'edit') {
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $stmt = $pdo->prepare("UPDATE pm_plans SET name=?, asset_id=?, frequency_days=?, next_due_date=?, status=?, notes=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['asset_id'], $_POST['frequency_days'], $_POST['next_due_date'], $status, $_POST['notes'], $_POST['id']]);
    header("Location: index.php?msg=updated");
}

// --- Delete ---
elseif ($action == 'delete') {
    $pdo->prepare("DELETE FROM pm_plans WHERE id=?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
}

// --- Complete Job (ทำเสร็จแล้ว) ---
elseif ($action == 'complete') {
    // 1. ดึงข้อมูลแผนปัจจุบัน
    $stmt = $pdo->prepare("SELECT * FROM pm_plans WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $plan = $stmt->fetch();

    if ($plan) {
        // 2. คำนวณวันถัดไป (Next Due = วันนี้ + ความถี่)
        $today = date('Y-m-d');
        $next_date = date('Y-m-d', strtotime($today . ' + ' . $plan['frequency_days'] . ' days'));

        // 3. อัปเดตข้อมูล
        $sql = "UPDATE pm_plans SET last_done_date = ?, next_due_date = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$today, $next_date, $_GET['id']]);
    }
    header("Location: index.php?msg=completed");
}

else {
    header("Location: index.php");
}
?>