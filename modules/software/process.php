<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

$action = $_REQUEST['action'] ?? '';

// --- Software CRUD ---
if ($action == 'add_soft') {
    $stmt = $pdo->prepare("INSERT INTO softwares (name, publisher, version, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['publisher'], $_POST['version'], $_POST['description']]);
    header("Location: index.php?msg=added");
} 
elseif ($action == 'edit_soft') {
    $stmt = $pdo->prepare("UPDATE softwares SET name=?, publisher=?, version=?, description=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['publisher'], $_POST['version'], $_POST['description'], $_POST['id']]);
    header("Location: index.php?msg=updated");
}
elseif ($action == 'delete_soft') {
    $pdo->prepare("DELETE FROM softwares WHERE id=?")->execute([$_GET['id']]);
    header("Location: index.php?msg=deleted");
}

// --- License CRUD ---
elseif ($action == 'add_lic') {
    $stmt = $pdo->prepare("INSERT INTO software_licenses (software_id, license_key, max_install, expire_date, notes) VALUES (?, ?, ?, ?, ?)");
    $expire = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;
    $stmt->execute([$_POST['software_id'], $_POST['license_key'], $_POST['max_install'], $expire, $_POST['notes']]);
    header("Location: licenses.php?id=".$_POST['software_id']);
}
elseif ($action == 'edit_lic') {
    $stmt = $pdo->prepare("UPDATE software_licenses SET license_key=?, max_install=?, expire_date=?, notes=? WHERE id=?");
    $expire = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;
    $stmt->execute([$_POST['license_key'], $_POST['max_install'], $expire, $_POST['notes'], $_POST['id']]);
    header("Location: licenses.php?id=".$_POST['software_id']);
}
elseif ($action == 'delete_lic') {
    $pdo->prepare("DELETE FROM software_licenses WHERE id=?")->execute([$_GET['id']]);
    header("Location: licenses.php?id=".$_GET['sid']);
}
else {
    header("Location: index.php");
}
?>