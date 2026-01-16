<?php
// modules/asset/process.php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin/Staff
require_once '../../config/db_connect.php';

// --- 1. จัดการลบ (Delete) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: index.php?msg=deleted");
    } catch (PDOException $e) {
        header("Location: index.php?error=delete_failed");
    }
    exit();
}

// --- 2. จัดการ เพิ่ม/แก้ไข (Add/Edit) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    // รับค่าจาก Form
    $code = trim($_POST['asset_code']);
    $name = trim($_POST['name']);
    $type_id = $_POST['asset_type_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $serial = $_POST['serial_number'];
    $cpu = $_POST['spec_cpu'];
    $ram = $_POST['spec_ram'];
    $storage = $_POST['spec_storage'];
    $os = $_POST['os_license'];
    $location_id = $_POST['location_id'] ?: null;
    $user_id = $_POST['current_user_id'] ?: null;
    $status = $_POST['status'];
    $price = $_POST['price'] ?: 0;
    $p_date = $_POST['purchase_date'] ?: null;
    $w_expire = $_POST['warranty_expire'] ?: null;

    try {
        if ($action == 'add') {
            $sql = "INSERT INTO assets (asset_code, name, asset_type_id, brand, model, serial_number, spec_cpu, spec_ram, spec_storage, os_license, location_id, current_user_id, status, price, purchase_date, warranty_expire) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $type_id, $brand, $model, $serial, $cpu, $ram, $storage, $os, $location_id, $user_id, $status, $price, $p_date, $w_expire]);
            $msg = "added";

        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $sql = "UPDATE assets SET asset_code=?, name=?, asset_type_id=?, brand=?, model=?, serial_number=?, spec_cpu=?, spec_ram=?, spec_storage=?, os_license=?, location_id=?, current_user_id=?, status=?, price=?, purchase_date=?, warranty_expire=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $type_id, $brand, $model, $serial, $cpu, $ram, $storage, $os, $location_id, $user_id, $status, $price, $p_date, $w_expire, $id]);
            $msg = "updated";
        }
        header("Location: index.php?msg=$msg");

    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}