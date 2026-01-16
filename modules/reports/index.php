<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// --- ตั้งค่าตัวกรอง (Filter) ---
$currentYear = date('Y');
$currentMonth = date('m');

$selectedYear = $_GET['year'] ?? $currentYear;
$selectedMonth = $_GET['month'] ?? $currentMonth;

// --- Query 1: สรุปงานแยกตามหมวดหมู่ (สำหรับ Pie Chart) ---
$sqlCat = "SELECT c.name, COUNT(t.id) as total 
           FROM tickets t
           JOIN categories c ON t.category_id = c.id
           WHERE YEAR(t.created_at) = ? AND MONTH(t.created_at) = ?
           GROUP BY c.name";
$stmtCat = $pdo->prepare($sqlCat);
$stmtCat->execute([$selectedYear, $selectedMonth]);
$catData = $stmtCat->fetchAll();

// เตรียมข้อมูลส่งให้ JS Chart
$catLabels = [];
$catValues = [];
foreach ($catData as $d) {
    $catLabels[] = $d['name'];
    $catValues[] = $d['total'];
}

// --- Query 2: ประสิทธิภาพช่าง (Top Technicians) ---
$sqlTech = "SELECT u.fullname, 
            COUNT(t.id) as total_jobs,
            SUM(CASE WHEN t.status = 'resolved' OR t.status = 'closed' THEN 1 ELSE 0 END) as finished_jobs
            FROM tickets t
            JOIN users u ON t.assigned_to = u.id
            WHERE YEAR(t.created_at) = ? AND MONTH(t.created_at) = ?
            GROUP BY u.fullname
            ORDER BY finished_jobs DESC LIMIT 5";
$stmtTech = $pdo->prepare($sqlTech);
$stmtTech->execute([$selectedYear, $selectedMonth]);
$techData = $stmtTech->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 shadow-sm sticky-top">
            <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">รายงานสรุปผล (Monthly Reports)</span>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="fw-bold text-secondary small"><i class="bi bi-funnel me-1"></i>ตัวกรอง:</label>
                        </div>
                        <div class="col-auto">
                            <select name="month" class="form-select form-select-sm bg-light">
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>>
                                        <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="year" class="form-select form-select-sm bg-light">
                                <?php for($y=$currentYear; $y >= $currentYear-2; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3">ค้นหา</button>
                        </div>
                        <div class="col-auto ms-auto">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-primary"><i class="bi bi-pie-chart-fill me-2"></i>สัดส่วนปัญหาตามหมวดหมู่</h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <div style="width: 300px; height: 300px;">
                                <canvas id="catChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-success"><i class="bi bi-trophy-fill me-2"></i>ประสิทธิภาพทีมช่าง (Top 5)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary small">
                                        <tr>
                                            <th>ชื่อเจ้าหน้าที่</th>
                                            <th class="text-center">รับงาน</th>
                                            <th class="text-center">ปิดงานสำเร็จ</th>
                                            <th class="text-end">Success Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($techData) > 0): ?>
                                            <?php foreach($techData as $tech): 
                                                $rate = ($tech['total_jobs'] > 0) ? round(($tech['finished_jobs'] / $tech['total_jobs']) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?= $tech['fullname'] ?></td>
                                                <td class="text-center"><?= $tech['total_jobs'] ?></td>
                                                <td class="text-center text-success fw-bold"><?= $tech['finished_jobs'] ?></td>
                                                <td class="text-end">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <span class="small me-2"><?= $rate ?>%</span>
                                                        <div class="progress" style="width: 50px; height: 6px;">
                                                            <div class="progress-bar bg-success" style="width: <?= $rate ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">ไม่มีข้อมูลในเดือนนี้</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                     <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center bg-primary bg-opacity-10 rounded">
                            <div>
                                <h5 class="fw-bold text-primary mb-1">สรุปภาพรวมทรัพย์สิน</h5>
                                <p class="text-muted small mb-0">ข้อมูล ณ ปัจจุบัน</p>
                            </div>
                            <div class="d-flex gap-4 text-center">
                                <?php
                                    $stats = $pdo->query("SELECT status, COUNT(*) as c FROM assets GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
                                ?>
                                <div>
                                    <h4 class="fw-bold mb-0"><?= $stats['active'] ?? 0 ?></h4>
                                    <small class="text-uppercase text-muted" style="font-size: 0.7rem;">Active</small>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0 text-warning"><?= $stats['repair'] ?? 0 ?></h4>
                                    <small class="text-uppercase text-muted" style="font-size: 0.7rem;">Repair</small>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0 text-secondary"><?= $stats['write_off'] ?? 0 ?></h4>
                                    <small class="text-uppercase text-muted" style="font-size: 0.7rem;">Write-off</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- Config Pie Chart ---
    const ctxCat = document.getElementById('catChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catValues) ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });

    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>