<?php
require_once '../../includes/auth.php';
// requireAdmin(); // เปิดถ้าต้องการจำกัดสิทธิ์เฉพาะ Admin
require_once '../../config/db_connect.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// 1. KPI Cards: ดึงตัวเลขสำคัญ
$total_assets = $pdo->query("SELECT COUNT(*) FROM assets WHERE status != 'write_off'")->fetchColumn();
$tickets_this_month = $pdo->query("SELECT COUNT(*) FROM tickets WHERE MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)")->fetchColumn();
$avg_resolve_time = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM tickets WHERE status = 'resolved'")->fetchColumn(); // หน่วยชั่วโมง

// 2. กราฟวงกลม: ปัญหาตามหมวดหมู่ (Top 5)
$top_cats = $pdo->query("SELECT c.name, COUNT(t.id) as total FROM tickets t JOIN categories c ON t.category_id = c.id GROUP BY c.name ORDER BY total DESC LIMIT 5")->fetchAll();
$cat_labels = json_encode(array_column($top_cats, 'name'));
$cat_data = json_encode(array_column($top_cats, 'total'));

?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Executive Dashboard</span>
    </nav>
    <div class="main-content-scroll">
        <div class="container-fluid p-2">
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-4 border-primary h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small">ทรัพย์สินทั้งหมด</h6>
                            <h2 class="fw-bold text-primary"><?= number_format($total_assets) ?> <small class="fs-6 text-muted">รายการ</small></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-4 border-warning h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small">งานซ่อมเดือนนี้</h6>
                            <h2 class="fw-bold text-warning"><?= number_format($tickets_this_month) ?> <small class="fs-6 text-muted">เคส</small></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-4 border-success h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small">เวลาแก้ปัญหาเฉลี่ย</h6>
                            <h2 class="fw-bold text-success"><?= number_format($avg_resolve_time, 1) ?> <small class="fs-6 text-muted">ชม./เคส</small></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">สัดส่วนปัญหาตามหมวดหมู่ (Top 5)</div>
                        <div class="card-body">
                            <canvas id="catChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: {
            labels: <?= $cat_labels ?>,
            datasets: [{
                data: <?= $cat_data ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
            }]
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>