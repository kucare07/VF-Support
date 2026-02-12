<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// --- Query Data ---
// ดึงข้อมูลซอฟต์แวร์ พร้อมนับจำนวน License และการใช้งาน
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM software_licenses l WHERE l.software_id = s.id) as total_licenses,
        (SELECT SUM(max_install) FROM software_licenses l WHERE l.software_id = s.id) as total_seats,
        (SELECT COUNT(*) FROM asset_software asw 
         JOIN software_licenses l ON asw.license_id = l.id 
         WHERE l.software_id = s.id) as used_seats
        FROM softwares s 
        ORDER BY s.name ASC";
$softwares = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Software Management</span>
        <span class="text-muted ms-2 small border-start ps-2">จัดการลิขสิทธิ์ซอฟต์แวร์</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3"> 
            
            <?php if (isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'สำเร็จ!', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-window-sidebar me-2"></i>รายชื่อซอฟต์แวร์ (Catalog)</h6>
                        
                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>
                    
                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openSoftModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มซอฟต์แวร์
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-checkbox py-3 text-center">
                                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleAll(this)">
                                    </th>
                                    <th class="ps-3">ชื่อซอฟต์แวร์</th>
                                    <th>ผู้ผลิต (Publisher)</th>
                                    <th>เวอร์ชัน</th>
                                    <th class="text-center">License Keys</th>
                                    <th class="text-center">การใช้งาน (Usage)</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($softwares as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    
                                    // Usage Bar Calculation
                                    $total = $row['total_seats'] ?: 0;
                                    $used = $row['used_seats'] ?: 0;
                                    $percent = ($total > 0) ? ($used / $total) * 100 : 0;
                                    $barColor = ($percent >= 100) ? 'bg-danger' : (($percent > 80) ? 'bg-warning' : 'bg-success');
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>
                                        
                                        <td class="ps-3 fw-bold text-primary"><?= $row['name'] ?></td>
                                        <td><?= $row['publisher'] ?: '-' ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['version'] ?: 'N/A' ?></span></td>
                                        <td class="text-center">
                                            <a href="licenses.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary py-0 shadow-sm" title="จัดการ License Key">
                                                <i class="bi bi-key me-1"></i> <?= $row['total_licenses'] ?> Keys
                                            </a>
                                        </td>
                                        <td class="text-center" style="width: 180px;">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="me-2 small fw-bold text-muted" style="font-size: 0.75rem;"><?= $used ?> / <?= $total ?></span>
                                                <div class="progress flex-grow-1 shadow-sm" style="height: 6px; background-color: #e9ecef;">
                                                    <div class="progress-bar <?= $barColor ?> rounded-pill" style="width: <?= $percent ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            <button class="btn btn-sm btn-light border text-warning shadow-sm me-1" onclick="openSoftModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="confirmDelete('process.php?action=delete_soft&id=<?= $row['id'] ?>', 'ยืนยันลบซอฟต์แวร์นี้? \n(ข้อมูล License Key ทั้งหมดจะหายไปด้วย)')"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="softModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="s_action">
                <input type="hidden" name="id" id="s_id">
                
                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="s_title">จัดการซอฟต์แวร์</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อซอฟต์แวร์ <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="เช่น Microsoft Office 2021" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ผู้ผลิต (Publisher)</label>
                            <input type="text" name="publisher" id="publisher" class="form-control" placeholder="เช่น Microsoft">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">เวอร์ชัน (Version)</label>
                            <input type="text" name="version" id="version" class="form-control" placeholder="เช่น 2021, v1.0">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">รายละเอียดเพิ่มเติม</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="บันทึกช่วยจำ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var softModal;
    document.addEventListener('DOMContentLoaded', function() {
        softModal = new bootstrap.Modal(document.getElementById('softModal'));
    });

    function openSoftModal(action, json = null) {
        document.getElementById('s_action').value = action + '_soft';
        document.getElementById('s_title').innerText = (action == 'add' ? 'เพิ่มซอฟต์แวร์ใหม่' : 'แก้ไขซอฟต์แวร์');
        document.getElementById('s_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('s_id').value = d.id;
            document.getElementById('name').value = d.name;
            document.getElementById('publisher').value = d.publisher;
            document.getElementById('version').value = d.version;
            document.getElementById('description').value = d.description;
        }
        softModal.show();
    }
</script>