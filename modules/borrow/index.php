<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// --- 1. Prepare Data ---
// ดึงข้อมูลการยืมคืนทั้งหมด
$sql = "SELECT b.*, a.name as asset_name, a.asset_code, u.fullname as user_name, u.department_id 
        FROM borrow_transactions b 
        JOIN assets a ON b.asset_id = a.id 
        LEFT JOIN users u ON b.user_id = u.id 
        ORDER BY b.status = 'borrowed' DESC, b.borrow_date DESC";
$transactions = $pdo->query($sql)->fetchAll();

// ดึงข้อมูลสำหรับ Dropdown (ของที่ยืมได้ + ผู้ใช้งาน)
$assets = $pdo->query("SELECT * FROM assets WHERE status = 'spare' ORDER BY name ASC")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Borrow & Return</span>
        <span class="text-muted ms-2 small border-start ps-2">ระบบยืม-คืนอุปกรณ์</span>
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
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-arrow-left-right me-2"></i>ประวัติการยืม-คืน</h6>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openBorrowModal()">
                        <i class="bi bi-plus-circle me-1"></i> ทำรายการยืมใหม่
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
                                    <th class="ps-3">รายการอุปกรณ์</th>
                                    <th>ผู้ยืม</th>
                                    <th>วันที่ยืม</th>
                                    <th>กำหนดคืน</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                foreach ($transactions as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    $is_overdue = ($row['status'] == 'borrowed' && strtotime($row['return_due_date']) < time());
                                    $status_badge = match ($row['status']) {
                                        'borrowed' => $is_overdue ? '<span class="badge bg-danger animate__animated animate__pulse animate__infinite">เกินกำหนด</span>' : '<span class="badge bg-warning text-dark">กำลังยืม</span>',
                                        'returned' => '<span class="badge bg-success">คืนแล้ว</span>',
                                        default => '<span class="badge bg-secondary">ยกเลิก</span>'
                                    };
                                    $due_date_text = date('d/m/Y', strtotime($row['return_due_date']));
                                    if ($is_overdue) $due_date_text = "<span class='text-danger fw-bold'>$due_date_text</span>";
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>
                                        <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                        <td class="ps-3">
                                            <div class="fw-bold text-primary"><?= $row['asset_code'] ?></div>
                                            <small class="text-muted"><?= $row['asset_name'] ?></small>
                                        </td>
                                        <td><?= $row['user_name'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['borrow_date'])) ?></td>
                                        <td><?= $due_date_text ?></td>
                                        <td><?= $status_badge ?></td>
                                        <td class="text-end pe-3 text-nowrap">
                                            <?php if ($row['status'] == 'borrowed'): ?>
                                                <button class="btn btn-sm btn-success shadow-sm me-1" onclick="openReturnModal(<?= $row['id'] ?>, '<?= $row['asset_code'] ?>')" title="แจ้งคืนอุปกรณ์">
                                                    <i class="bi bi-box-arrow-in-down"></i> คืน
                                                </button>
                                                <button class="btn btn-sm btn-light border text-warning shadow-sm me-1" onclick="openEditModal('<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="confirmDelete('process.php?action=delete&id=<?= $row['id'] ?>', 'ยืนยันลบประวัติการยืมนี้?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<div class="modal fade" id="borrowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="b_action" value="borrow">
                <input type="hidden" name="id" id="b_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="b_title">ทำรายการยืมอุปกรณ์</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ผู้ยืม <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-select select2" required>
                            <option value="">-- เลือกรายชื่อ --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= $u['fullname'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">อุปกรณ์ที่ต้องการยืม <span class="text-danger">*</span></label>
                        <select name="asset_id" id="asset_id" class="form-select select2" required>
                            <option value="">-- เลือกอุปกรณ์ (Spare) --</option>
                            <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= $a['asset_code'] ?> - <?= $a['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted">* แสดงเฉพาะสถานะ "Spare" เท่านั้น</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">วันที่ยืม</label>
                            <input type="date" name="borrow_date" id="borrow_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">กำหนดคืน</label>
                            <input type="date" name="return_due_date" id="return_due_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">หมายเหตุ</label>
                        <textarea name="note" id="note" class="form-control" rows="2" placeholder="เช่น ยืมไปใช้ออกบูธ..."></textarea>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale">บันทึกรายการ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="return">
                <input type="hidden" name="id" id="r_id">

                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title fw-bold">รับคืนอุปกรณ์</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center p-4">
                    <div class="mb-3 text-success">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-2">ยืนยันการรับคืน?</h5>
                    <p class="text-muted small mb-4" id="r_asset_name">...</p>

                    <div class="form-floating mb-2">
                        <input type="date" name="return_date" class="form-control text-center fw-bold" id="return_date" value="<?= date('Y-m-d') ?>" required>
                        <label>วันที่คืนจริง</label>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top bg-light justify-content-center">
                    <button type="submit" class="btn btn-success w-100 fw-bold hover-scale">ยืนยันรับคืน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var borrowModal, returnModal;
    document.addEventListener('DOMContentLoaded', function() {
        borrowModal = new bootstrap.Modal(document.getElementById('borrowModal'));
        returnModal = new bootstrap.Modal(document.getElementById('returnModal'));
    });

    function openBorrowModal() {
        document.getElementById('b_action').value = 'borrow';
        document.getElementById('b_title').innerText = 'ทำรายการยืมอุปกรณ์';
        document.getElementById('b_id').value = '';
        document.forms[0].reset();

        // Reset Select2
        if (window.$ && $.fn.select2) {
            $('#user_id').val(null).trigger('change');
            $('#asset_id').val(null).trigger('change');
        }

        borrowModal.show();
    }

    function openEditModal(json) {
        const d = JSON.parse(json);
        document.getElementById('b_action').value = 'edit';
        document.getElementById('b_title').innerText = 'แก้ไขข้อมูลการยืม';
        document.getElementById('b_id').value = d.id;

        document.getElementById('borrow_date').value = d.borrow_date;
        document.getElementById('return_due_date').value = d.return_due_date;
        document.getElementById('note').value = d.note;

        // Handle Select2 for Edit
        if (window.$ && $.fn.select2) {
            $('#user_id').val(d.user_id).trigger('change');
            // Asset might not be in list if it's currently borrowed (status != spare), 
            // you might need to append option manually or handle logic in PHP to include current asset
            if ($('#asset_id option[value="' + d.asset_id + '"]').length > 0) {
                $('#asset_id').val(d.asset_id).trigger('change');
            } else {
                // Create temporary option for current asset
                var newOption = new Option(d.asset_code + ' (Current)', d.asset_id, true, true);
                $('#asset_id').append(newOption).trigger('change');
            }
        } else {
            document.getElementById('user_id').value = d.user_id;
            document.getElementById('asset_id').value = d.asset_id;
        }

        borrowModal.show();
    }

    function openReturnModal(id, assetName) {
        document.getElementById('r_id').value = id;
        document.getElementById('r_asset_name').innerText = 'รหัส: ' + assetName;
        returnModal.show();
    }
</script>