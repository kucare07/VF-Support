<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// ค่าเริ่มต้นตัวกรอง
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // วันที่ 1 ของเดือน
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');     // วันสุดท้ายของเดือน
$report_type = isset($_GET['type']) ? $_GET['type'] : 'tickets';

$results = [];
$title = "";

// Logic การดึงข้อมูลตามประเภทรายงาน
if ($report_type == 'tickets') {
    $title = "รายงานการแจ้งซ่อม (Helpdesk)";
    $sql = "SELECT t.*, u.fullname, c.name as cat_name, a.name as asset_name 
            FROM tickets t 
            LEFT JOIN users u ON t.user_id = u.id 
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN assets a ON t.asset_code = a.asset_code
            WHERE DATE(t.created_at) BETWEEN ? AND ? 
            ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();

} elseif ($report_type == 'borrow') {
    $title = "รายงานประวัติการยืม-คืน";
    $sql = "SELECT b.*, u.fullname as user_name, a.asset_code, a.name as asset_name 
            FROM borrow_transactions b 
            LEFT JOIN users u ON b.user_id = u.id 
            LEFT JOIN assets a ON b.asset_id = a.id
            WHERE DATE(b.borrow_date) BETWEEN ? AND ? 
            ORDER BY b.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();

} elseif ($report_type == 'assets') {
    $title = "รายงานทะเบียนทรัพย์สิน (ทั้งหมด)";
    // กรณี Asset ไม่ดูวันที่ แต่ดูสถานะปัจจุบัน
    $sql = "SELECT a.*, t.name as type_name, l.name as loc_name, u.fullname as owner 
            FROM assets a 
            LEFT JOIN asset_types t ON a.asset_type_id = t.id
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN users u ON a.current_user_id = u.id
            ORDER BY a.asset_code ASC";
    $results = $pdo->query($sql)->fetchAll();
}
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<style>
    @media print {
        #sidebar-wrapper, .main-navbar, .no-print { display: none !important; }
        .main-content-scroll { margin: 0; padding: 0; height: auto; overflow: visible; }
        .card { border: none !important; shadow: none !important; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #000 !important; padding: 5px; }
        body { background: white; -webkit-print-color-adjust: exact; }
    }
</style>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Reports System</span>
        <span class="text-muted ms-2 small border-start ps-2">ระบบรายงาน</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-4">
            
            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>ตัวกรองรายงาน</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">ประเภทรายงาน</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="tickets" <?= $report_type=='tickets'?'selected':'' ?>>แจ้งซ่อม (Tickets)</option>
                                <option value="borrow" <?= $report_type=='borrow'?'selected':'' ?>>การยืม-คืน (Borrow)</option>
                                <option value="assets" <?= $report_type=='assets'?'selected':'' ?>>ทรัพย์สิน (Assets List)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">ตั้งแต่วันที่</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">ถึงวันที่</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> ค้นหาข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0"><?= $title ?></h5>
                        <div class="no-print">
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> พิมพ์ / PDF</button>
                            <button onclick="exportTableToExcel('reportTable')" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</button>
                        </div>
                    </div>
                    
                    <?php if($report_type != 'assets'): ?>
                        <p class="small text-muted mb-3">ข้อมูลระหว่างวันที่: <strong><?= date('d/m/Y', strtotime($start_date)) ?></strong> ถึง <strong><?= date('d/m/Y', strtotime($end_date)) ?></strong></p>
                    <?php else: ?>
                        <p class="small text-muted mb-3">ข้อมูลทรัพย์สินทั้งหมด ณ วันที่: <strong><?= date('d/m/Y') ?></strong></p>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm small mb-0" id="reportTable">
                            
                            <?php if($report_type == 'tickets'): ?>
                            <thead class="table-light">
                                <tr>
                                    <th>Job ID</th>
                                    <th>วันที่แจ้ง</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>หมวดหมู่</th>
                                    <th>รายละเอียด</th>
                                    <th>สถานะ</th>
                                    <th>ผู้รับผิดชอบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $row): ?>
                                <tr>
                                    <td class="text-center">#<?= $row['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td><?= $row['fullname'] ?></td>
                                    <td><?= $row['cat_name'] ?></td>
                                    <td><?= $row['description'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= match($row['status']){'new'=>'danger','resolved'=>'success',default=>'secondary'} ?> no-print">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                        <span class="d-none d-print-inline"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td><?= $row['assigned_to'] ? 'ช่าง' : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <?php elseif($report_type == 'borrow'): ?>
                            <thead class="table-light">
                                <tr>
                                    <th>เลขที่</th>
                                    <th>วันที่ยืม</th>
                                    <th>ผู้ยืม</th>
                                    <th>อุปกรณ์</th>
                                    <th>รหัสทรัพย์สิน</th>
                                    <th>กำหนดคืน</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $row): ?>
                                <tr>
                                    <td><?= $row['transaction_no'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['borrow_date'])) ?></td>
                                    <td><?= $row['user_name'] ?></td>
                                    <td><?= $row['asset_name'] ?></td>
                                    <td><?= $row['asset_code'] ?></td>
                                    <td><?= $row['return_due_date'] ? date('d/m/Y', strtotime($row['return_due_date'])) : '-' ?></td>
                                    <td><?= $row['status']=='returned' ? 'คืนแล้ว' : 'กำลังยืม' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <?php elseif($report_type == 'assets'): ?>
                            <thead class="table-light">
                                <tr>
                                    <th>รหัส</th>
                                    <th>ชื่อเครื่อง/รุ่น</th>
                                    <th>ประเภท</th>
                                    <th>Serial No.</th>
                                    <th>สถานที่</th>
                                    <th>ผู้ถือครอง</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $row): ?>
                                <tr>
                                    <td class="fw-bold"><?= $row['asset_code'] ?></td>
                                    <td><?= $row['name'] ?> <br><small class="text-muted"><?= $row['brand'] ?> <?= $row['model'] ?></small></td>
                                    <td><?= $row['type_name'] ?></td>
                                    <td><?= $row['serial_number'] ?></td>
                                    <td><?= $row['loc_name'] ?></td>
                                    <td><?= $row['owner'] ?: '-' ?></td>
                                    <td><?= ucfirst($row['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php endif; ?>

                            <?php if(count($results) == 0): ?>
                                <tbody><tr><td colspan="10" class="text-center py-4 text-muted">ไม่พบข้อมูล</td></tr></tbody>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    document.getElementById('menu-toggle').addEventListener('click', e => { e.preventDefault(); document.getElementById('sidebar-wrapper').classList.toggle('active'); });

    // ฟังก์ชัน Export Excel แบบบ้านๆ (ไม่ต้องใช้ Library)
    function exportTableToExcel(tableID, filename = 'report_data') {
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
        
        // Specify file name
        filename = filename ? filename + '.xls' : 'excel_data.xls';
        
        // Create download link element
        downloadLink = document.createElement("a");
        
        document.body.appendChild(downloadLink);
        
        if(navigator.msSaveOrOpenBlob){
            var blob = new Blob(['\ufeff', tableHTML], {
                type: dataType
            });
            navigator.msSaveOrOpenBlob( blob, filename);
        }else{
            // Create a link to the file
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            
            // Setting the file name
            downloadLink.download = filename;
            
            //triggering the function
            downloadLink.click();
        }
    }
</script>