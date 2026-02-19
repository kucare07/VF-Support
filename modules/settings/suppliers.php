<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// --- Query Data ---
$sql = "SELECT * FROM suppliers ORDER BY name ASC";
$suppliers = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">ผู้ขาย/คู่ค้า (Suppliers)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">

            <?php if (isset($_GET['msg'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                </script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-shop me-2"></i>รายการผู้ขาย</h6>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete_supplier')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มผู้ขายใหม่
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
                                    <th class="text-center" style="width: 50px;">ลำดับ</th>
                                    <th class="ps-3">ชื่อบริษัท/ร้านค้า</th>
                                    <th>ผู้ติดต่อ</th>
                                    <th>เบอร์โทร</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                foreach ($suppliers as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>
                                        <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                                        <td class="text-end pe-3 text-nowrap">
                                            <button class="btn btn-sm btn-light border text-warning shadow-sm me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="confirmDelete('process.php?action=delete_supplier&id=<?= $row['id'] ?>', 'ยืนยันลบผู้ขาย <?= htmlspecialchars($row['name']) ?>?')"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="supModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="s_action">
                <input type="hidden" name="id" id="s_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="s_title">จัดการข้อมูลผู้ขาย</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อบริษัท/ร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ผู้ติดต่อ</label>
                            <input type="text" name="contact_name" id="contact_name" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">เบอร์โทร</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email / Line ID</label>
                        <input type="text" name="email" id="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ที่อยู่</label>
                        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
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
    var supModal;
    document.addEventListener('DOMContentLoaded', function() {
        supModal = new bootstrap.Modal(document.getElementById('supModal'));
    });

    function openModal(action, json = null) {
        document.getElementById('s_action').value = action + '_supplier'; // e.g. add_supplier, edit_supplier
        document.getElementById('s_title').innerText = (action == 'add' ? 'เพิ่มผู้ขายใหม่' : 'แก้ไขผู้ขาย');
        document.getElementById('s_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('s_id').value = d.id;
            document.getElementById('name').value = d.name;
            if (document.getElementById('contact_name')) document.getElementById('contact_name').value = d.contact_name || '';
            if (document.getElementById('phone')) document.getElementById('phone').value = d.phone || '';
            if (document.getElementById('email')) document.getElementById('email').value = d.email || '';
            if (document.getElementById('address')) document.getElementById('address').value = d.address || '';
        }
        supModal.show();
    }
</script>