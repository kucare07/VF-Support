<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

$action = $_REQUEST['action'] ?? '';

// --- 1. จัดการข้อมูลสินค้า (Add/Edit) ---
if ($action == 'add' || $action == 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $code = $_POST['code'];
    $unit = $_POST['unit'];
    $min = $_POST['min_stock'];
    $price = $_POST['unit_price'];
    
    // Upload Image
    $image_name = null;
    if ($action == 'edit') {
        $stmt = $pdo->prepare("SELECT image FROM inventory_items WHERE id = ?");
        $stmt->execute([$id]);
        $image_name = $stmt->fetchColumn();
    }
    
    if (!empty($_FILES['image']['name'])) {
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists("../../uploads/inventory")) mkdir("../../uploads/inventory", 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'inv_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/inventory/" . $new_filename)) {
            $image_name = $new_filename;
        }
    }

    if ($action == 'add') {
        $sql = "INSERT INTO inventory_items (name, code, unit, min_stock, unit_price, image) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $code, $unit, $min, $price, $image_name]);
    } else {
        $sql = "UPDATE inventory_items SET name=?, code=?, unit=?, min_stock=?, unit_price=?, image=? WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $code, $unit, $min, $price, $image_name, $id]);
    }
    
    header("Location: index.php?msg=saved");
}

// --- 2. ลบสินค้า ---
elseif ($action == 'delete') {
    $pdo->prepare("DELETE FROM inventory_items WHERE id=?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
}

// --- 3. ปรับสต็อก (In/Out) ---
elseif ($action == 'adjust_stock') {
    $id = $_POST['id'];
    $type = $_POST['type']; // in หรือ out
    $qty = intval($_POST['qty']);
    $note = $_POST['note'];
    $user_id = $_SESSION['user_id'];

    // ดึงยอดเดิม
    $stmt = $pdo->prepare("SELECT qty_on_hand FROM inventory_items WHERE id = ?");
    $stmt->execute([$id]);
    $current_qty = $stmt->fetchColumn();

    // คำนวณยอดใหม่
    $new_qty = ($type == 'in') ? ($current_qty + $qty) : ($current_qty - $qty);
    if ($new_qty < 0) $new_qty = 0; // ห้ามติดลบ

    // อัปเดต Master
    $pdo->prepare("UPDATE inventory_items SET qty_on_hand = ? WHERE id = ?")->execute([$new_qty, $id]);

    // บันทึก Log Transaction
    $sqlLog = "INSERT INTO inventory_transactions (item_id, user_id, action_type, qty, balance_after, note) VALUES (?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sqlLog)->execute([$id, $user_id, $type, $qty, $new_qty, $note]);

    header("Location: index.php?msg=adjusted");
}

else {
    header("Location: index.php");
}
?>