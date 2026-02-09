<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin เท่านั้นที่ดู Log ได้
require_once '../../config/db_connect.php';

// ดึงข้อมูล Log 1000 รายการล่าสุด (เพื่อไม่ให้โหลดหนักเกินไป)
$sql = "SELECT l.*, u.fullname, u.username 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC LIMIT 1000";
$logs = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">System Security</span>
        <span class="text-muted ms-2 small border-start ps-2">บันทึกการใช้งานระบบ (Audit Logs)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <div class="alert alert-info small py-2">
                <i class="bi bi-info-circle me-2"></i> แสดงรายการบันทึก 1,000 รายการล่าสุด
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-activity me-2"></i>รายการ Log ล่าสุด</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 15%">วัน-เวลา</th>
                                    <th style="width: 15%">ผู้ใช้งาน</th>
                                    <th style="width: 10%">Action</th>
                                    <th>รายละเอียด</th>
                                    <th style="width: 10%">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $row): 
                                    // สี Badge ตาม Action
                                    $badge = match($row['action']) {
                                        'LOGIN', 'LOGOUT' => 'info',
                                        'INSERT', 'ADD' => 'success',
                                        'UPDATE', 'EDIT' => 'warning',
                                        'DELETE' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?></td>
                                    <td class="fw-bold">
                                        <?= $row['fullname'] ? $row['fullname'] : '<span class="text-muted">Unknown</span>' ?>
                                        <?php if($row['username']) echo "<div class='small text-muted'>({$row['username']})</div>"; ?>
                                    </td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $row['action'] ?></span></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="font-monospace text-muted small"><?= $row['ip_address'] ?></td>
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

<?php require_once '../../includes/footer.php'; ?>