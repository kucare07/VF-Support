<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php'; // เรียกใช้ฟังก์ชันต่างๆ เช่น thai_date

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. STATS: ดึงตัวเลขสรุป ---
// งานค้าง (New + Assigned + Pending)
$ticket_pending = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();
// งานเกินกำหนด (SLA Breached)
$ticket_overdue = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved', 'closed') AND sla_due_date < NOW()")->fetchColumn();
// สินค้าหมด/ใกล้หมด
$stock_low = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE qty_on_hand <= min_stock")->fetchColumn();
// ของที่ยังไม่คืน (Active Borrow)
$borrow_active = $pdo->query("SELECT COUNT(*) FROM borrow_transactions WHERE status = 'borrowed'")->fetchColumn();
// แผน PM ที่ต้องทำเดือนนี้
$pm_due = $pdo->query("SELECT COUNT(*) FROM pm_plans WHERE status='active' AND MONTH(next_due_date) = MONTH(CURRENT_DATE()) AND YEAR(next_due_date) = YEAR(CURRENT_DATE())")->fetchColumn();

// --- 2. CHARTS: เตรียมข้อมูลกราฟ ---
// กราฟ 1: ปริมาณงานย้อนหลัง 7 วัน (Line Chart)
$sql_trend = "SELECT DATE_FORMAT(created_at, '%d/%m') as date_label, COUNT(*) as total 
              FROM tickets 
              WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY 
              GROUP BY DATE(created_at) ORDER BY created_at ASC";
$trend_data = $pdo->query($sql_trend)->fetchAll();
$trend_labels = json_encode(array_column($trend_data, 'date_label'));
$trend_values = json_encode(array_column($trend_data, 'total'));

// กราฟ 2: สัดส่วนงานตามหมวดหมู่ (Doughnut Chart)
$sql_cat = "SELECT c.name, COUNT(t.id) as total FROM tickets t JOIN categories c ON t.category_id = c.id GROUP BY c.name";
$cat_data = $pdo->query($sql_cat)->fetchAll();
$cat_labels = json_encode(array_column($cat_data, 'name'));
$cat_values = json_encode(array_column($cat_data, 'total'));

// --- 3. TABLES: ข้อมูลตาราง ---
// งานด่วน/ล่าช้า 5 รายการล่าสุด
$urgent_tickets = $pdo->query("SELECT t.*, u.fullname FROM tickets t JOIN users u ON t.user_id = u.id 
                               WHERE t.status NOT IN ('resolved','closed') 
                               ORDER BY (t.sla_due_date < NOW()) DESC, t.priority = 'critical' DESC, t.created_at ASC LIMIT 5")->fetchAll();

// วัสดุใกล้หมด 5 รายการ
$low_stock_items = $pdo->query("SELECT name, qty_on_hand, min_stock, unit FROM inventory_items WHERE qty_on_hand <= min_stock ORDER BY qty_on_hand ASC LIMIT 5")->fetchAll();
?>

<?php require_once 'includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php require_once 'includes/sidebar.php'; ?>

<style>
    /* Custom Card Style */
    .stat-card {
        transition: transform 0.2s;
        border: none;
        overflow: hidden;
        position: relative;
        color: white;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .stat-icon {
        position: absolute;
        right: 15px;
        bottom: 10px;
        font-size: 3rem;
        opacity: 0.3;
        transform: rotate(-15deg);
    }

    /* Gradients */
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-danger { background: linear-gradient(45deg, #e74a3b, #be2617); }
    .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
</style>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Dashboard</span>
        <span class="text-muted ms-2 small border-start ps-2">ภาพรวมระบบ</span>
        <div class="ms-auto small text-muted">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
        </div>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark m-0">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['fullname']) ?>! 👋</h4>
                    <small class="text-muted">นี่คือสรุปสถานะงานไอทีล่าสุดของคุณ</small>
                </div>
                <div>
                    <a href="modules/helpdesk/index.php" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-plus-lg me-1"></i> แจ้งซ่อม</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card bg-gradient-primary shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase small fw-bold text-white-50">งานรอดำเนินการ</h6>
                            <div class="fs-2 fw-bold"><?= number_format($ticket_pending) ?></div>
                            <div class="small text-white-50 mt-1">Tickets Pending</div>
                            <i class="bi bi-ticket-perforated stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card bg-gradient-danger shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase small fw-bold text-white-50">งานเกินกำหนด (SLA)</h6>
                            <div class="fs-2 fw-bold"><?= number_format($ticket_overdue) ?></div>
                            <div class="small text-white-50 mt-1">Overdue Tickets</div>
                            <i class="bi bi-exclamation-octagon stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card bg-gradient-warning shadow-sm h-100 text-white">
                        <div class="card-body">
                            <h6 class="text-uppercase small fw-bold text-white-50">วัสดุใกล้หมด</h6>
                            <div class="fs-2 fw-bold"><?= number_format($stock_low) ?></div>
                            <div class="small text-white-50 mt-1">Low Stock Items</div>
                            <i class="bi bi-box-seam stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card bg-gradient-info shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-uppercase small fw-bold text-white-50">ยืม-คืน (ค้างส่ง)</h6>
                            <div class="fs-2 fw-bold"><?= number_format($borrow_active) ?></div>
                            <div class="small text-white-50 mt-1">Active Borrows</div>
                            <i class="bi bi-arrow-left-right stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>ปริมาณงานย้อนหลัง 7 วัน</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-success"><i class="bi bi-pie-chart me-2"></i>ประเภทปัญหา</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 250px; position: relative;">
                                <canvas id="catChart"></canvas>
                            </div>
                            <div class="text-center mt-3 small text-muted">สัดส่วนงานซ่อมทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-danger"><i class="bi bi-fire me-2"></i>งานเร่งด่วน / ล่าช้า</h6>
                            <a href="modules/helpdesk/index.php" class="btn btn-sm btn-light border">ดูทั้งหมด</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>หัวข้อปัญหา</th>
                                        <th>SLA (กำหนดส่ง)</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($urgent_tickets as $t):
                                        $is_late = (strtotime($t['sla_due_date']) < time());
                                        $sla_color = $is_late ? 'text-danger fw-bold' : 'text-muted';
                                        $icon = $is_late ? '<i class="bi bi-exclamation-circle-fill"></i>' : '';
                                    ?>
                                        <tr>
                                            <td class="fw-bold">#<?= str_pad($t['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;"><?= htmlspecialchars($t['description']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($t['fullname']) ?></small>
                                            </td>
                                            <td class="<?= $sla_color ?>">
                                                <?= $icon ?> <?= date('d/m H:i', strtotime($t['sla_due_date'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $t['status'] == 'new' ? 'danger' : ($t['status'] == 'assigned' ? 'primary' : 'warning') ?>">
                                                    <?= ucfirst($t['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($urgent_tickets) == 0): ?>
                                    <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">ไม่มีงานค้าง เยี่ยมมาก! 👍</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-warning"><i class="bi bi-box-seam me-2"></i>วัสดุต้องสั่งซื้อ (Low Stock)</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush small">
                                <?php foreach ($low_stock_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                        <div><span class="fw-bold"><?= htmlspecialchars($item['name']) ?></span></div>
                                        <div class="text-end">
                                            <span class="badge bg-danger rounded-pill"><?= $item['qty_on_hand'] ?> <?= $item['unit'] ?></span>
                                            <div style="font-size: 10px;" class="text-muted">Min: <?= $item['min_stock'] ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (count($low_stock_items) == 0): ?>
                                    <li class="list-group-item text-center text-muted py-3">สต็อกเพียงพอทุกรายการ ✅</li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($pm_due > 0): ?>
                                <div class="p-3 bg-light border-top mt-auto">
                                    <div class="d-flex align-items-center text-primary">
                                        <i class="bi bi-calendar-check fs-4 me-2"></i>
                                        <div>
                                            <span class="fw-bold">มีแผนบำรุงรักษา (PM)</span><br>
                                            <span class="small">ต้องทำในเดือนนี้ <?= $pm_due ?> รายการ</span>
                                        </div>
                                        <a href="modules/pm/index.php" class="btn btn-sm btn-outline-primary ms-auto">ดูแผนงาน</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // --- Chart 1: Trend (งานย้อนหลัง 7 วัน) ---
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= $trend_labels ?>,
            datasets: [{
                label: 'งานแจ้งซ่อมใหม่',
                data: <?= $trend_values ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                tension: 0.3,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4e73df',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });

    // --- Chart 2: Categories (สัดส่วนปัญหา) ---
    const ctxCat = document.getElementById('catChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= $cat_labels ?>,
            datasets: [{
                data: <?= $cat_values ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', #858796'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 11 }, usePointStyle: true }
                }
            },
            cutout: '70%'
        }
    });
</script>