<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// (Optional) เตรียมข้อมูลสำหรับ Dropdown ในอนาคตถ้าต้องการ
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<style>
    .report-card {
        transition: transform 0.2s;
        height: 100%;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    .report-icon {
        font-size: 2.5rem;
        opacity: 0.2;
        position: absolute;
        right: 15px;
        top: 15px;
    }
</style>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Report Center</span>
        <span class="text-muted ms-2 small border-start ps-2">ศูนย์รวมรายงานและการส่งออกข้อมูล</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-4"> 
            
            <div class="row g-4">
                
                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="header-gradient rounded-top">
                            <h6 class="m-0 fw-bold"><i class="bi bi-ticket-perforated me-2"></i>รายงานการแจ้งซ่อม</h6>
                        </div>
                        <div class="card-body">
                            <i class="bi bi-tools report-icon text-primary"></i>
                            <p class="small text-muted mb-3">Export ข้อมูลรายการแจ้งซ่อม, สถานะงาน, และ SLA</p>
                            
                            <form action="export_ticket.php" method="GET" target="_blank">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">ช่วงวันที่แจ้ง</label>
                                    <div class="input-group input-group-sm">
                                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                                        <span class="input-group-text bg-light">ถึง</span>
                                        <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">สถานะงาน</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">ทั้งหมด (All)</option>
                                        <option value="resolved">เสร็จสิ้น (Resolved)</option>
                                        <option value="pending">รอดำเนินการ (Pending)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100 hover-scale">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="header-gradient rounded-top">
                            <h6 class="m-0 fw-bold"><i class="bi bi-pc-display me-2"></i>ทะเบียนทรัพย์สิน</h6>
                        </div>
                        <div class="card-body">
                            <i class="bi bi-box-seam report-icon text-success"></i>
                            <p class="small text-muted mb-3">Export รายการครุภัณฑ์, อุปกรณ์ไอที, และสถานะปัจจุบัน</p>
                            
                            <form action="../asset/export_assets.php" method="GET" target="_blank">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">ประเภททรัพย์สิน</label>
                                    <select name="type_id" class="form-select form-select-sm">
                                        <option value="">ทั้งหมด (All)</option>
                                        </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">สถานะ</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">ทั้งหมด (All)</option>
                                        <option value="active">ใช้งานอยู่ (Active)</option>
                                        <option value="spare">สำรอง (Spare)</option>
                                        <option value="repair">ส่งซ่อม (Repair)</option>
                                        <option value="write_off">ตัดจำหน่าย (Write-off)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100 hover-scale">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Asset List
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="header-gradient rounded-top">
                            <h6 class="m-0 fw-bold"><i class="bi bi-arrow-left-right me-2"></i>ประวัติการยืม-คืน</h6>
                        </div>
                        <div class="card-body">
                            <i class="bi bi-journal-text report-icon text-warning"></i>
                            <p class="small text-muted mb-3">สรุปรายการเบิกยืมอุปกรณ์, การคืน, และรายการค้างส่ง</p>
                            
                            <form action="export_borrow.php" method="GET" target="_blank">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">วันที่ทำรายการ</label>
                                    <div class="input-group input-group-sm">
                                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
                                        <span class="input-group-text bg-light">ถึง</span>
                                        <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">ประเภทรายการ</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">ทั้งหมด (All)</option>
                                        <option value="borrowed">กำลังยืม (Borrowed)</option>
                                        <option value="returned">คืนแล้ว (Returned)</option>
                                        <option value="overdue">เกินกำหนด (Overdue)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100 hover-scale text-dark">
                                    <i class="bi bi-file-text me-1"></i> Export History
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="header-gradient rounded-top">
                            <h6 class="m-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>แผนบำรุงรักษา (PM)</h6>
                        </div>
                        <div class="card-body">
                            <i class="bi bi-clipboard-pulse report-icon text-info"></i>
                            <p class="small text-muted mb-3">รายงานแผนการดูแลรักษาประจำเดือน/ปี</p>
                            
                            <form action="export_pm.php" method="GET" target="_blank">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">เลือกช่วงเวลา</label>
                                    <div class="input-group input-group-sm">
                                        <input type="month" name="pm_month" class="form-control" value="<?= date('Y-m') ?>">
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="type" value="plan" class="btn btn-info btn-sm hover-scale text-white">
                                        <i class="bi bi-calendar3 me-1"></i> Export PM Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="header-gradient rounded-top">
                            <h6 class="m-0 fw-bold"><i class="bi bi-box-seam me-2"></i>วัสดุสิ้นเปลือง</h6>
                        </div>
                        <div class="card-body">
                            <i class="bi bi-boxes report-icon text-danger"></i>
                            <p class="small text-muted mb-3">สรุปยอดคงเหลือ และประวัติการรับเข้า/เบิกออก</p>
                            
                            <form action="export_inventory.php" method="GET" target="_blank">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">ประเภทรายงาน</label>
                                    <select name="report_type" class="form-select form-select-sm">
                                        <option value="balance">ยอดคงเหลือปัจจุบัน (Stock Balance)</option>
                                        <option value="movement">ประวัติการเคลื่อนไหว (Stock Card)</option>
                                        <option value="low_stock">สินค้าใกล้หมด (Low Stock)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm w-100 hover-scale">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card report-card bg-dark text-white" style="background: linear-gradient(45deg, #212529, #343a40);">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-5">
                            <i class="bi bi-bar-chart-line-fill fs-1 mb-3 text-warning"></i>
                            <h5 class="fw-bold">Executive Dashboard</h5>
                            <p class="small opacity-75 mb-4">สรุปภาพรวมสถิติ กราฟ และตัวชี้วัดประสิทธิภาพ (KPI)</p>
                            <a href="executive.php" class="btn btn-warning btn-sm px-4 rounded-pill fw-bold hover-scale text-dark">
                                <i class="bi bi-eye me-1"></i> เข้าดู Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div> </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>