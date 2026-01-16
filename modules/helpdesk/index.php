<?php
// 1. เพิ่มการตรวจสอบสิทธิ์
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// 2. Query ดึงข้อมูล Ticket
$sql = "SELECT t.*, u.fullname, c.name as category_name 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN categories c ON t.category_id = c.id 
        ORDER BY t.created_at DESC";
$stmt = $pdo->query($sql);
$tickets = $stmt->fetchAll();

// 3. ปรับฟังก์ชัน Badge ให้สีสวย (Soft Color) เหมือนหน้าอื่น
function getStatusBadge($status) {
    switch ($status) {
        case 'new': return '<span class="badge bg-warning bg-opacity-10 text-warning px-3 rounded-pill">รอรับเรื่อง</span>';
        case 'assigned': return '<span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill">กำลังซ่อม</span>';
        case 'pending': return '<span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill">รออะไหล่</span>';
        case 'resolved': return '<span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">เสร็จสิ้น</span>';
        case 'closed': return '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 rounded-pill">ปิดงาน</span>';
        default: return '<span class="badge bg-light text-dark border">'.$status.'</span>';
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="d-flex" id="wrapper">
    
    <?php require_once '../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">รายการแจ้งซ่อม (Helpdesk Tickets)</span>
        </nav>

        <div class="container-fluid p-4">
            
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1 fw-bold text-primary"><i class="bi bi-tools me-2"></i>รายการงานซ่อมทั้งหมด</h5>
                            <small class="text-muted">ติดตามสถานะและจัดการใบงานแจ้งซ่อม</small>
                        </div>
                        <a href="create.php" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-plus-lg me-2"></i> แจ้งปัญหาใหม่
                        </a>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0 ps-0" placeholder="ค้นหา หัวข้อ, เลขที่ใบงาน...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th>#ID</th>
                                    <th style="width: 30%;">หัวข้อปัญหา</th>
                                    <th>หมวดหมู่</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>ความเร่งด่วน</th>
                                    <th>สถานะ</th>
                                    <th>วันที่แจ้ง</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($tickets) > 0): ?>
                                    <?php foreach($tickets as $ticket): ?>
                                    <tr>
                                        <td><span class="text-muted fw-bold">#<?= $ticket['id'] ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ticket['subject']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                                <?= mb_substr(strip_tags($ticket['description']), 0, 50) ?>...
                                            </small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border fw-normal"><?= $ticket['category_name'] ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                                    <?= mb_substr($ticket['fullname'], 0, 1) ?>
                                                </div>
                                                <?= $ticket['fullname'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                // ปรับสี Priority
                                                $pColor = match($ticket['priority']) {
                                                    'critical' => 'text-danger fw-bold',
                                                    'high' => 'text-warning fw-bold',
                                                    default => 'text-secondary'
                                                };
                                                $pIcon = match($ticket['priority']) {
                                                    'critical' => '<i class="bi bi-fire"></i>',
                                                    'high' => '<i class="bi bi-exclamation-circle"></i>',
                                                    default => '<i class="bi bi-circle-fill" style="font-size: 6px;"></i>'
                                                };
                                            ?>
                                            <span class="<?= $pColor ?> small text-uppercase">
                                                <?= $pIcon ?> <?= ucfirst($ticket['priority']) ?>
                                            </span>
                                        </td>
                                        <td><?= getStatusBadge($ticket['status']) ?></td>
                                        <td class="small text-muted"><?= date('d/m/y H:i', strtotime($ticket['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="view.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary px-3">
                                                <i class="bi bi-eye"></i> ดู
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                            ยังไม่มีรายการแจ้งซ่อมในระบบ
                                        </td>
                                    </tr>
                                <?php endif; ?>
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