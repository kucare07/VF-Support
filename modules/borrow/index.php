<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงรายการยืมทั้งหมด
$sql = "SELECT b.*, u.fullname as borrower_name, a.asset_code, a.name as asset_name, admin.fullname as admin_name
        FROM borrow_transactions b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN assets a ON b.asset_id = a.id
        LEFT JOIN users admin ON b.created_by = admin.id
        ORDER BY b.created_at DESC";
$transactions = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">ระบบยืม-คืน (Borrow & Return)</span>
        </nav>

        <div class="container-fluid p-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold text-primary mb-1"><i class="bi bi-arrow-left-right me-2"></i>รายการยืม-คืนอุปกรณ์</h5>
                            <small class="text-muted">จัดการการยืมและรับคืนครุภัณฑ์</small>
                        </div>
                        <a href="create.php" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> ทำรายการยืมใหม่
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-uppercase small text-secondary">
                                <tr>
                                    <th>เลขที่รายการ</th>
                                    <th>ผู้ยืม</th>
                                    <th>อุปกรณ์</th>
                                    <th>วันที่ยืม</th>
                                    <th>กำหนดคืน</th>
                                    <th>สถานะ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transactions as $t): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= $t['transaction_no'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle p-1 me-2 text-secondary"><i class="bi bi-person-fill"></i></div>
                                            <?= $t['borrower_name'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block fw-bold"><?= $t['asset_code'] ?></span>
                                        <small class="text-muted"><?= $t['asset_name'] ?></small>
                                    </td>
                                    <td><?= date('d/m/y H:i', strtotime($t['borrow_date'])) ?></td>
                                    <td>
                                        <?php if($t['return_due_date']): ?>
                                            <?= date('d/m/Y', strtotime($t['return_due_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($t['status'] == 'borrowed'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">กำลังยืม</span>
                                        <?php elseif($t['status'] == 'returned'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">คืนแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">เกินกำหนด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($t['status'] == 'borrowed'): ?>
                                            <a href="return.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('ยืนยันรับคืนอุปกรณ์?');">
                                                <i class="bi bi-box-arrow-in-down-left"></i> รับคืน
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted" disabled>รับคืนแล้ว</button>
                                        <?php endif; ?>
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
<script>
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>