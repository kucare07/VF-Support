<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin เท่านั้น
require_once '../../config/db_connect.php';
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-1 fw-bold text-secondary">ตั้งค่าระบบ (System Settings)</span>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="mb-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3"><i class="bi bi-building me-1"></i> โครงสร้างองค์กร</h6>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <a href="departments.php" class="card text-decoration-none border-0 shadow-sm setting-card h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                    <i class="bi bi-diagram-3-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">จัดการแผนก</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">(Departments)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="locations.php" class="card text-decoration-none border-0 shadow-sm setting-card h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                    <i class="bi bi-geo-alt-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">จัดการสถานที่</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">(Locations)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3"><i class="bi bi-tools me-1"></i> ทรัพย์สินและงานบริการ</h6>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <a href="asset_types.php" class="card text-decoration-none border-0 shadow-sm setting-card h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                    <i class="bi bi-pc-display fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">ประเภทครุภัณฑ์</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">(Asset Types)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="categories.php" class="card text-decoration-none border-0 shadow-sm setting-card h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded p-3 me-3">
                                    <i class="bi bi-tags-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">หมวดหมู่ปัญหา</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">(Ticket Categories)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3"><i class="bi bi-people me-1"></i> ผู้ใช้งานระบบ</h6>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <a href="../user/index.php" class="card text-decoration-none border-0 shadow-sm setting-card h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                    <i class="bi bi-person-lines-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">จัดการผู้ใช้งาน</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">(User Management)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .setting-card { transition: all 0.2s ease-in-out; }
    .setting-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; border-left: 4px solid var(--primary-color) !important; }
    .icon-shape { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; }
</style>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>