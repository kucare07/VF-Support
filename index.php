<?php
require_once 'includes/auth.php';
require_once 'config/db_connect.php';

// --- PHP Logic (เหมือนเดิม) ---
$stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
$totalTickets = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$new = $statusCounts['new'] ?? 0;
$processing = $statusCounts['assigned'] ?? 0;
$pending = $statusCounts['pending'] ?? 0;
$done = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);
$successRate = ($totalTickets > 0) ? round(($done / $totalTickets) * 100) : 0;
?>

<?php require_once 'includes/header.php'; ?>

<div class="d-flex" id="wrapper">

    <?php require_once 'includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-1 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <h6 class="m-0 text-dark fw-bold">ภาพรวมระบบ (Dashboard)</h6>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="text-end me-2 d-none d-sm-block">
                        <small class="text-muted d-block" style="font-size: 0.7rem;"><?= date('D, d M Y') ?></small>
                        <span class="fw-bold text-primary" style="font-size: 0.8rem;">Admin System</span>
                    </div>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center ms-2" style="width:32px; height:32px;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4 py-3"> 
            
            <div class="row g-3 mb-3"> <div class="col-6 col-lg-3">
                    <div class="card card-counter h-100 mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="label text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Total Tickets</p>
                                <h3 class="text-primary mb-0"><?= $totalTickets ?></h3>
                                <small class="text-muted" style="font-size: 0.7rem;">รายการทั้งหมด</small>
                            </div>
                            <div class="icon-box bg-icon-primary"><i class="bi bi-clipboard-data"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card card-counter h-100 mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="label text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Success Rate</p>
                                <h3 class="text-success mb-0"><?= $successRate ?>%</h3>
                                <small class="text-muted" style="font-size: 0.7rem;">อัตราสำเร็จ</small>
                            </div>
                            <div class="icon-box bg-icon-success"><i class="bi bi-graph-up-arrow"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card card-counter h-100 mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="label text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Total Borrowed</p>
                                <h3 class="mb-0" style="color: #9333ea;">0</h3>
                                <small class="text-muted" style="font-size: 0.7rem;">ครั้ง (สะสม)</small>
                            </div>
                            <div class="icon-box bg-icon-purple"><i class="bi bi-arrow-repeat"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card card-counter h-100 mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="label text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Active Borrow</p>
                                <h3 class="text-info mb-0">0</h3>
                                <small class="text-muted" style="font-size: 0.7rem;">กำลังยืมอยู่</small>
                            </div>
                            <div class="icon-box bg-icon-info"><i class="bi bi-clock-history"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="status-box status-new d-flex flex-column justify-content-center h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">New</div>
                                <div class="stat-desc">รอดำเนินการ</div>
                            </div>
                            <div class="stat-number"><?= $new ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="status-box status-processing d-flex flex-column justify-content-center h-100">
                         <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Processing</div>
                                <div class="stat-desc">กำลังซ่อม</div>
                            </div>
                            <div class="stat-number"><?= $processing ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="status-box status-pending d-flex flex-column justify-content-center h-100">
                         <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Pending</div>
                                <div class="stat-desc">รออะไหล่</div>
                            </div>
                            <div class="stat-number"><?= $pending ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="status-box status-done d-flex flex-column justify-content-center h-100">
                         <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Done</div>
                                <div class="stat-desc">เสร็จสิ้น</div>
                            </div>
                            <div class="stat-number"><?= $done ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                
                <div class="col-lg-9 col-xl-9">
                    <div class="card shadow-sm border-0 h-100 mb-0">
                        <div class="card-body p-4"> <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-title fw-bold text-secondary m-0">
                                    <i class="bi bi-bar-chart-line me-2"></i> สรุปงานซ่อมรายวัน
                                </h6>
                                <select class="form-select form-select-sm w-auto bg-light border-0">
                                    <option>7 วันล่าสุด</option>
                                    <option>เดือนนี้</option>
                                </select>
                            </div>
                            <div style="height: 280px; width: 100%;"> <canvas id="weeklyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-xl-3">
                    
                    <div class="card shadow-sm border-0 bg-dark text-white mb-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 text-warning" style="font-size: 0.85rem;">
                                <i class="bi bi-lightning-charge-fill me-1"></i> Quick Actions
                            </h6>
                            <div class="d-grid gap-2">
                                <a href="modules/helpdesk/create.php" class="btn btn-primary btn-sm text-start py-2 fw-bold">
                                    <i class="bi bi-plus-lg me-2"></i> แจ้งซ่อมใหม่
                                </a>
                                <a href="modules/borrow/index.php" class="btn btn-outline-light btn-sm text-start py-2">
                                    <i class="bi bi-arrow-left-right me-2"></i> ยืม-คืนอุปกรณ์
                                </a>
                                <a href="modules/inventory/adjust.php" class="btn btn-outline-light btn-sm text-start py-2">
                                    <i class="bi bi-box-seam me-2"></i> เบิก/รับอะไหล่
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                            <h6 class="fw-bold m-0" style="font-size: 0.85rem;">การแจ้งเตือน (Alerts)</h6>
                        </div>
                        <div class="card-body p-2 pt-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 px-2 py-2 d-flex align-items-center">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark" style="font-size: 0.8rem;">อะไหล่ใกล้หมด</div>
                                        <div class="text-success small" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill me-1"></i> สถานะปกติ</div>
                                    </div>
                                    <a href="#" class="btn btn-light btn-sm border py-0 px-2" style="font-size: 0.7rem;">Check</a>
                                </div>

                                <div class="list-group-item border-0 px-2 py-2 d-flex align-items-center">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark" style="font-size: 0.8rem;">สัญญาใกล้หมด</div>
                                        <div class="text-success small" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill me-1"></i> สถานะปกติ</div>
                                    </div>
                                    <a href="#" class="btn btn-light btn-sm border py-0 px-2" style="font-size: 0.7rem;">Check</a>
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
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์', 'อาทิตย์'],
            datasets: [{
                label: 'งานซ่อม',
                data: [2, 5, 3, 1, 0, 0, 0],
                borderColor: '#3b82f6',
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, "rgba(59, 130, 246, 0.2)");
                    gradient.addColorStop(1, "rgba(59, 130, 246, 0)");
                    return gradient;
                },
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            interaction: { intersect: false, mode: 'index' },
        }
    });

    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>