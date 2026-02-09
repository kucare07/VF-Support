<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    // เตรียมข้อมูล (เพิ่ม supplier_id ใน array)
    $data = [
        $_POST['asset_code'],
        $_POST['name'],
        $_POST['asset_type_id'],
        $_POST['brand'],
        $_POST['model'],
        $_POST['serial_number'],
        $_POST['spec_cpu'],
        $_POST['spec_ram'],
        $_POST['spec_storage'],
        $_POST['os_license'],
        $_POST['location_id'] ?: null,
        $_POST['current_user_id'] ?: null,
        $_POST['status'],
        $_POST['price'] ?: 0,
        $_POST['purchase_date'] ?: null,
        $_POST['warranty_expire'] ?: null,
        $_POST['supplier_id'] ?: null // ✅ เพิ่มตรงนี้
    ];

    if ($action == 'add') {
        $sql = "INSERT INTO assets (asset_code, name, asset_type_id, brand, model, serial_number, spec_cpu, spec_ram, spec_storage, os_license, location_id, current_user_id, status, price, purchase_date, warranty_expire, supplier_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute($data);
        $msg = "added";
    } else {
        $data[] = $_POST['id'];
        $sql = "UPDATE assets SET asset_code=?, name=?, asset_type_id=?, brand=?, model=?, serial_number=?, spec_cpu=?, spec_ram=?, spec_storage=?, os_license=?, location_id=?, current_user_id=?, status=?, price=?, purchase_date=?, warranty_expire=?, supplier_id=? WHERE id=?";
        $pdo->prepare($sql)->execute($data);
        $msg = "updated";
    }
    header("Location: index.php?msg=$msg");
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // เก็บ Log ก่อนลบ (Optional: หรือจะลบ Log ทิ้งไปด้วยก็ได้)
    // logAssetAction($_GET['id'], 'delete', 'ลบข้อมูลออกจากระบบ'); 

    $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $data = [
        $_POST['asset_code'],
        $_POST['name'],
        $_POST['asset_type_id'],
        $_POST['brand'],
        $_POST['model'],
        $_POST['serial_number'],
        $_POST['spec_cpu'],
        $_POST['spec_ram'],
        $_POST['spec_storage'],
        $_POST['os_license'],
        $_POST['location_id'] ?: null,
        $_POST['current_user_id'] ?: null,
        $_POST['status'],
        $_POST['price'] ?: 0,
        $_POST['purchase_date'] ?: null,
        $_POST['warranty_expire'] ?: null,
        $_POST['supplier_id'] ?: null
    ];

    if ($action == 'add') {
        $sql = "INSERT INTO assets (asset_code, name, asset_type_id, brand, model, serial_number, spec_cpu, spec_ram, spec_storage, os_license, location_id, current_user_id, status, price, purchase_date, warranty_expire, supplier_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute($data);

        // ✅ บันทึก Log เพิ่มใหม่
        $new_id = $pdo->lastInsertId();
        logAssetAction($new_id, 'create', 'นำเข้าข้อมูลใหม่: ' . $_POST['asset_code']);

        $msg = "added";
    } else {
        $data[] = $_POST['id'];
        $sql = "UPDATE assets SET asset_code=?, name=?, asset_type_id=?, brand=?, model=?, serial_number=?, spec_cpu=?, spec_ram=?, spec_storage=?, os_license=?, location_id=?, current_user_id=?, status=?, price=?, purchase_date=?, warranty_expire=?, supplier_id=? WHERE id=?";
        $pdo->prepare($sql)->execute($data);

        // ✅ บันทึก Log แก้ไข
        logAssetAction($_POST['id'], 'update', 'แก้ไขข้อมูลรายละเอียด / สถานะเป็น ' . $_POST['status']);

        $msg = "updated";
    }
    header("Location: index.php?msg=$msg");
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // ลบรูปภาพด้วย (ถ้ามี)
    $stmt = $pdo->prepare("SELECT image FROM assets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../../uploads/assets/" . $img)) {
        unlink("../../uploads/assets/" . $img);
    }

    $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    // --- จัดการรูปภาพ ---
    $image_name = null;
    // ถ้าเป็นการแก้ไข ให้ดึงชื่อรูปเดิมมาก่อน
    if ($action == 'edit') {
        $stmt = $pdo->prepare("SELECT image FROM assets WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $image_name = $stmt->fetchColumn();
    }

    // ถ้ามีการอัปโหลดใหม่
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'asset_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/assets/" . $new_filename)) {
            // ลบรูปเก่าทิ้ง (ถ้ามี)
            if ($image_name && file_exists("../../uploads/assets/" . $image_name)) {
                unlink("../../uploads/assets/" . $image_name);
            }
            $image_name = $new_filename;
        }
    }
    // ------------------

    $data = [
        $_POST['asset_code'],
        $_POST['name'],
        $_POST['asset_type_id'],
        $_POST['brand'],
        $_POST['model'],
        $_POST['serial_number'],
        $_POST['spec_cpu'],
        $_POST['spec_ram'],
        $_POST['spec_storage'],
        $_POST['os_license'],
        $_POST['location_id'] ?: null,
        $_POST['current_user_id'] ?: null,
        $_POST['status'],
        $_POST['price'] ?: 0,
        $_POST['purchase_date'] ?: null,
        $_POST['warranty_expire'] ?: null,
        $_POST['supplier_id'] ?: null,
        $image_name // ✅ เพิ่มฟิลด์รูปภาพ
    ];

    if ($action == 'add') {
        $sql = "INSERT INTO assets (asset_code, name, asset_type_id, brand, model, serial_number, spec_cpu, spec_ram, spec_storage, os_license, location_id, current_user_id, status, price, purchase_date, warranty_expire, supplier_id, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute($data);

        $new_id = $pdo->lastInsertId();
        logAssetAction($new_id, 'create', 'เพิ่มครุภัณฑ์ใหม่: ' . $_POST['asset_code']);
        $msg = "added";
    } else {
        $data[] = $_POST['id']; // ID อยู่ท้ายสุดสำหรับ WHERE
        $sql = "UPDATE assets SET asset_code=?, name=?, asset_type_id=?, brand=?, model=?, serial_number=?, spec_cpu=?, spec_ram=?, spec_storage=?, os_license=?, location_id=?, current_user_id=?, status=?, price=?, purchase_date=?, warranty_expire=?, supplier_id=?, image=? WHERE id=?";
        $pdo->prepare($sql)->execute($data);

        logAssetAction($_POST['id'], 'update', 'แก้ไขข้อมูล');
        $msg = "updated";
    }
    header("Location: index.php?msg=$msg");
}
