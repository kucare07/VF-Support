<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) header("Location: index.php");
$soft_id = $_GET['id'];

// ดึงข้อมูล Software แม่
$soft = $pdo->prepare("SELECT * FROM softwares WHERE id = ?");
$soft->execute([$soft_id]);
$s_info = $soft->fetch();

// ดึง License List
$sql = "SELECT l.*, 
        (SELECT COUNT(*) FROM asset_software WHERE license_id = l.id) as installed_count 
        FROM software_licenses l 
        WHERE l.software_id = ? ORDER BY l.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$soft_id]);
$licenses = $stmt->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <button class="btn btn-light btn-sm me-3 border" id="menu-toggle"><i class="bi bi-list"></i></button>
        <span class="fw-bold text-dark">License Management</span>
        <span class="text-muted ms-2 small border-start ps-2"><?= $s_info['name'] ?></span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-0">
            <div class="d-flex justify-content-between mb-3">
                <a href="index.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                <button class="btn btn-sm btn-success" onclick="openLicModal('add')"><i class="bi bi-key-fill me-1"></i> เพิ่ม License Key</button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">License Key / Serial</th>
                                    <th>จำนวนสิทธิ์ (Max)</th>
                                    <th>ใช้ไป (Installed)</th>
                                    <th>วันหมดอายุ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($licenses as $row): 
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    $status = ($row['installed_count'] >= $row['max_install']) ? 'text-danger fw-bold' : 'text-success';
                                ?>
                                <tr>
                                    <td class="ps-3 font-monospace"><?= $row['license_key'] ?></td>
                                    <td><?= $row['max_install'] ?> เครื่อง</td>
                                    <td class="<?= $status ?>"><?= $row['installed_count'] ?> เครื่อง</td>
                                    <td><?= $row['expire_date'] ? date('d/m/Y', strtotime($row['expire_date'])) : 'ถาวร (Lifetime)' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1" onclick="openLicModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                        <a href="process.php?action=delete_lic&id=<?= $row['id'] ?>&sid=<?= $soft_id ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ลบ License นี้?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="licModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="l_action">
                <input type="hidden" name="id" id="l_id">
                <input type="hidden" name="software_id" value="<?= $soft_id ?>">
                <div class="modal-header bg-success text-white py-2"><h6 class="modal-title fw-bold" id="l_title"></h6><button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-bold">License Key / Serial Number *</label><input type="text" name="license_key" id="license_key" class="form-control form-control-sm font-monospace" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small fw-bold">จำนวนเครื่องที่ลงได้ (Max Install)</label><input type="number" name="max_install" id="max_install" class="form-control form-control-sm" value="1"></div>
                        <div class="col-6"><label class="form-label small fw-bold">วันหมดอายุ (ว่าง=ถาวร)</label><input type="date" name="expire_date" id="expire_date" class="form-control form-control-sm"></div>
                    </div>
                    <div class="mt-2"><label class="form-label small fw-bold">หมายเหตุ</label><textarea name="notes" id="notes" class="form-control form-control-sm" rows="2"></textarea></div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-success">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var licModal;
    document.addEventListener('DOMContentLoaded', function() { licModal = new bootstrap.Modal(document.getElementById('licModal')); });

    function openLicModal(action, json = null) {
        document.getElementById('l_action').value = action + '_lic';
        document.getElementById('l_title').innerText = (action == 'add' ? 'เพิ่ม License Key' : 'แก้ไข License');
        document.getElementById('l_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('l_id').value = d.id;
            document.getElementById('license_key').value = d.license_key;
            document.getElementById('max_install').value = d.max_install;
            document.getElementById('expire_date').value = d.expire_date;
            document.getElementById('notes').value = d.notes;
        }
        licModal.show();
    }
    document.getElementById('menu-toggle').addEventListener('click', e => { e.preventDefault(); document.getElementById('sidebar-wrapper').classList.toggle('active'); });
</script>