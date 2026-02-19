<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// --- Query Data ---
$sql = "SELECT * FROM asset_types ORDER BY name ASC";
$types = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">ประเภททรัพย์สิน (Asset Types)</span>
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
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-tags-fill me-2"></i>รายการประเภททรัพย์สิน</h6>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete_type')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มประเภทใหม่
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
                                    <th class="ps-3">ชื่อประเภท</th>
                                    <th>รายละเอียด</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                foreach ($types as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    $desc = isset($row['description']) ? $row['description'] : '-';
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>
                                        <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($desc) ?></td>
                                        <td class="text-end pe-3 text-nowrap">
                                            <button class="btn btn-sm btn-light border text-warning shadow-sm me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="confirmDelete('process.php?action=delete_type&id=<?= $row['id'] ?>', 'ยืนยันลบประเภท <?= htmlspecialchars($row['name']) ?>?')"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="t_action">
                <input type="hidden" name="id" id="t_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="t_title">จัดการประเภททรัพย์สิน</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อประเภท <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="เช่น คอมพิวเตอร์, ปริ้นเตอร์" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">รายละเอียด</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
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
    var typeModal;
    document.addEventListener('DOMContentLoaded', function() {
        typeModal = new bootstrap.Modal(document.getElementById('typeModal'));
    });

    function openModal(action, json = null) {
        document.getElementById('t_action').value = action + '_type'; // e.g. add_type, edit_type
        document.getElementById('t_title').innerText = (action == 'add' ? 'เพิ่มประเภทใหม่' : 'แก้ไขประเภท');
        document.getElementById('t_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('t_id').value = d.id;
            document.getElementById('name').value = d.name;
            document.getElementById('description').value = d.description;
        }
        typeModal.show();
    }
</script>