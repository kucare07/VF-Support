<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// --- Filter Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

// --- Query Data ---
$sql = "SELECT l.*, u.fullname as user_name 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE DATE(l.created_at) BETWEEN ? AND ? 
        ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$logs = $stmt->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">System Logs</span>
        <span class="text-muted ms-2 small border-start ps-2">ประวัติการใช้งานระบบ</span>
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
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-primary m-0"><i class="bi bi-clock-history me-2"></i>รายการ Logs</h6>

                            <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete_logs')">
                                <i class="bi bi-trash"></i> ลบที่เลือก
                            </button>
                        </div>

                        <button class="btn btn-sm btn-outline-danger shadow-sm hover-scale" onclick="confirmClearLogs()">
                            <i class="bi bi-trash2-fill me-1"></i> ล้าง Log เก่ากว่า 90 วัน
                        </button>
                    </div>

                    <form method="GET" action="" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="small fw-bold text-muted">ตั้งแต่วันที่</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                        </div>
                        <div class="col-auto">
                            <label class="small fw-bold text-muted">ถึงวันที่</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i> ค้นหา</button>
                            <a href="index.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-checkbox py-3 text-center">
                                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleAll(this)">
                                    </th>
                                    <th class="ps-3">วัน-เวลา</th>
                                    <th>ผู้ใช้งาน</th>
                                    <th>กิจกรรม (Action)</th>
                                    <th>รายละเอียด</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $row):
                                    // Format Badge Color
                                    $action_color = match (strtolower($row['action'])) {
                                        'login', 'logout' => 'info',
                                        'create', 'add' => 'success',
                                        'update', 'edit' => 'warning text-dark',
                                        'delete' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>

                                        <td class="ps-3 text-muted small" style="white-space:nowrap;">
                                            <?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($row['user_name'] ?? 'System/Guest') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $action_color ?>"><?= strtoupper($row['action']) ?></span>
                                        </td>
                                        <td class="text-wrap-fix small text-muted">
                                            <?= htmlspecialchars($row['details'] ?? $row['description'] ?? '-') ?>
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            <?= $row['ip_address'] ?>
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

<?php require_once '../../includes/footer.php'; ?>

<script>
    function confirmClearLogs() {
        Swal.fire({
            title: 'ล้างประวัติเก่า?',
            text: "คุณต้องการลบ Log ที่เก่ากว่า 90 วันทั้งหมดใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ยืนยันลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'process.php?action=clear_old_logs';
            }
        });
    }
</script>