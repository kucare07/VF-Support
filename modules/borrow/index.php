<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Fetch Active Borrows
$sqlActive = "SELECT b.*, a.asset_code, a.name as asset_name, u.fullname as user_name, h.fullname as handler_name 
              FROM borrow_transactions b
              JOIN assets a ON b.asset_id = a.id
              JOIN users u ON b.user_id = u.id
              JOIN users h ON b.handler_id = h.id
              WHERE b.status = 'borrowed' ORDER BY b.id DESC";
$activeItems = $pdo->query($sqlActive)->fetchAll();

// Fetch History (Returned) - Limit 50
$sqlHistory = "SELECT b.*, a.asset_code, a.name as asset_name, u.fullname as user_name 
               FROM borrow_transactions b
               JOIN assets a ON b.asset_id = a.id
               JOIN users u ON b.user_id = u.id
               WHERE b.status = 'returned' ORDER BY b.return_date DESC LIMIT 50";
$historyItems = $pdo->query($sqlHistory)->fetchAll();

// Fetch Available Assets for Borrowing
$assets = $pdo->query("SELECT id, asset_code, name FROM assets WHERE status IN ('spare', 'active') AND current_user_id IS NULL ORDER BY asset_code ASC")->fetchAll();
// Fetch Users
$users = $pdo->query("SELECT id, fullname, department_id FROM users WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll();
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
        <div class="container-fluid p-3"> <?php if (isset($_GET['msg'])): ?>
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
                    <ul class="nav nav-pills card-header-pills">
                        <li class="nav-item">
                            <a class="nav-link active py-1 px-3 small" data-bs-toggle="tab" href="#tabActive">
                                <i class="bi bi-clock-history me-1"></i> กำลังยืม (Active)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-3 small text-secondary" data-bs-toggle="tab" href="#tabHistory">
                                <i class="bi bi-archive me-1"></i> ประวัติการคืน (History)
                            </a>
                        </li>
                        <button class="btn btn-sm btn-primary" onclick="openBorrowModal()">
                            <i class="bi bi-plus-lg me-1"></i> ทำรายการยืม (New Borrow)
                        </button>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabActive">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">เลขที่รายการ (Transaction No.)</th>
                                            <th>ผู้ยืม</th>
                                            <th>อุปกรณ์</th>
                                            <th>วันที่ยืม</th>
                                            <th>กำหนดคืน (Due Date)</th>
                                            <th class="text-end pe-3">จัดการ (Action)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeItems as $row):
                                            $is_overdue = ($row['return_due_date'] && strtotime($row['return_due_date']) < time());
                                            $due_text = $row['return_due_date'] ? date('d/m/Y', strtotime($row['return_due_date'])) : '-';
                                            if ($is_overdue) $due_text = "<span class='badge bg-danger'>$due_text (เกินกำหนด)</span>";
                                        ?>
                                            <tr>
                                                <td class="ps-3 text-primary fw-bold"><?= $row['transaction_no'] ?></td>
                                                <td><?= $row['user_name'] ?></td>
                                                <td>
                                                    <div class="fw-bold"><?= $row['asset_code'] ?></div>
                                                    <small class="text-muted"><?= $row['asset_name'] ?></small>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['borrow_date'])) ?></td>
                                                <td><?= $due_text ?></td>
                                                <td class="text-end pe-3">
                                                    <a href="print_form.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-light border py-0 me-1" title="พิมพ์ใบยืม"><i class="bi bi-printer"></i></a>
                                                    <a href="process.php?action=return&id=<?= $row['id'] ?>&aid=<?= $row['asset_id'] ?>" class="btn btn-sm btn-success border py-0" onclick="return confirm('ยืนยันรับของคืน? ตรวจสอบสภาพเรียบร้อยแล้ว')"><i class="bi bi-box-arrow-in-down-left me-1"></i> รับคืน</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tabHistory">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">เลขที่</th>
                                            <th>ผู้ยืม</th>
                                            <th>อุปกรณ์</th>
                                            <th>วันที่คืนจริง</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historyItems as $row): ?>
                                            <tr>
                                                <td class="ps-3 text-muted"><?= $row['transaction_no'] ?></td>
                                                <td><?= $row['user_name'] ?></td>
                                                <td><?= $row['asset_code'] ?> - <?= $row['asset_name'] ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['return_date'])) ?></td>
                                                <td><span class="badge bg-secondary">คืนแล้ว</span></td>
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
    </div>
</div>

<div class="modal fade" id="borrowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="borrow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold">ทำรายการยืม (New Borrow)</h6><button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ผู้ยืม (Borrower) *</label>
                        <select name="user_id" class="form-select form-select-sm select2" required>
                            <option value="">-- เลือกรายชื่อ --</option>
                            <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= $u['fullname'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">อุปกรณ์ที่ยืม (Asset) *</label>
                        <select name="asset_id" class="form-select form-select-sm select2" required>
                            <option value="">-- เลือกอุปกรณ์ (เฉพาะสถานะว่าง) --</option>
                            <?php foreach ($assets as $a): ?><option value="<?= $a['id'] ?>">[<?= $a['asset_code'] ?>] <?= $a['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">วันที่ยืม</label>
                            <input type="datetime-local" name="borrow_date" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">กำหนดคืน (Due Date)</label>
                            <input type="date" name="return_due_date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small fw-bold">หมายเหตุ</label>
                        <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="เช่น ยืมไป Present งานลูกค้า"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-primary">บันทึกรายการ</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    function openBorrowModal() {
        new bootstrap.Modal(document.getElementById('borrowModal')).show();
    }

    // ✅ Removed 'menu-toggle' event listener
</script>