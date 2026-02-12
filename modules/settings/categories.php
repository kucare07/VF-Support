<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// --- Query Data ---
$sql = "SELECT * FROM categories ORDER BY name ASC";
$categories = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">หมวดหมู่งานซ่อม (Categories)</span>
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
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-tags-fill me-2"></i>รายการหมวดหมู่</h6>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete_category')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มหมวดหมู่ใหม่
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
                                    <th class="ps-3">ชื่อหมวดหมู่</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    // ✅ แก้ไข: ตรวจสอบตัวแปร status ก่อนใช้งาน
                                    $status = isset($row['status']) ? $row['status'] : 'active';
                                    $status_badge = ($status == 'active')
                                        ? '<span class="badge bg-success">Active</span>'
                                        : '<span class="badge bg-secondary">Inactive</span>';
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>

                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= $status_badge ?></td>
                                        <td class="text-end pe-3 text-nowrap">
                                            <button class="btn btn-sm btn-light border text-warning shadow-sm me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="confirmDelete('process.php?action=delete_category&id=<?= $row['id'] ?>', 'ยืนยันลบหมวดหมู่ <?= htmlspecialchars($row['name']) ?>?')"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="c_action">
                <input type="hidden" name="id" id="c_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="c_title">จัดการหมวดหมู่</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="เช่น Hardware, Network, Software" required>
                    </div>

                    <div class="form-check bg-light p-2 rounded border">
                        <input class="form-check-input ms-1" type="checkbox" name="status" id="status" value="active" checked>
                        <label class="form-check-label small fw-bold ms-2" for="status">เปิดใช้งาน (Active)</label>
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
    var catModal;
    document.addEventListener('DOMContentLoaded', function() {
        catModal = new bootstrap.Modal(document.getElementById('catModal'));
    });

    function openModal(action, json = null) {
        document.getElementById('c_action').value = action + '_category'; // e.g. add_category, edit_category
        document.getElementById('c_title').innerText = (action == 'add' ? 'เพิ่มหมวดหมู่ใหม่' : 'แก้ไขหมวดหมู่');
        document.getElementById('c_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('c_id').value = d.id;
            document.getElementById('name').value = d.name;
            document.getElementById('status').checked = (d.status === 'active');
        }
        catModal.show();
    }
</script>