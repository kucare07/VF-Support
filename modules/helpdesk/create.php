<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// ดึงข้อมูล Master Data
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$users_technician = $pdo->query("SELECT * FROM users WHERE role IN ('technician', 'admin')")->fetchAll();

// ตัวแปรสำหรับแจ้งเตือน (SweetAlert)
$alert = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $requester_id = $_SESSION['user_id'];
    $department_id = $_POST['department_id'];
    $asset_code = trim($_POST['asset_code']);
    $priority = $_POST['priority'];
    $category_id = $_POST['category_id'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $description = trim($_POST['description']);
    $status = 'new';

    // File Upload
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = '../../uploads/tickets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $new_filename = 'ticket_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $new_filename)) {
            $attachment = $new_filename;
        }
    }

    try {
        $sql = "INSERT INTO tickets (
                    user_id, category_id, asset_code, priority, 
                    status, description, assigned_to, attachment, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $requester_id, $category_id, $asset_code, $priority, 
            $status, $description, $assigned_to, $attachment
        ]);
        
        $ticket_id = $pdo->lastInsertId();

        // ส่ง LINE Notify (ถ้ามีฟังก์ชัน)
        if (function_exists('sendLineMessage')) {
            $lineMsg = "🔔 *เปิดใบงานใหม่* (#".str_pad($ticket_id, 5, '0', STR_PAD_LEFT).")\n";
            $lineMsg .= "👤 ผู้แจ้ง: " . ($_SESSION['fullname'] ?? 'Unknown') . "\n";
            $lineMsg .= "🚨 ความเร่งด่วน: " . ucfirst($priority) . "\n";
            $lineMsg .= "📝 อาการ: " . $description;
            sendLineMessage($lineMsg);
        }

        // เตรียมข้อมูลสำหรับแสดงผลใน SweetAlert
        $priority_text = match($priority) {
            'low' => '🟢 ต่ำ (Low)',
            'medium' => '🟡 ปานกลาง (Medium)',
            'high' => '🟠 สูง (High)',
            'critical' => '🔴 วิกฤต (Critical)',
            default => $priority
        };

        $alert = [
            'type' => 'success',
            'title' => 'บันทึกใบงานสำเร็จ!',
            'html' => "
                <div class='text-start border p-3 rounded bg-light mt-2'>
                    <div class='mb-1'><b>เลขที่ใบงาน:</b> <span class='text-primary'>#".str_pad($ticket_id, 5, '0', STR_PAD_LEFT)."</span></div>
                    <div class='mb-1'><b>วันที่แจ้ง:</b> ".date('d/m/Y H:i')."</div>
                    <div class='mb-1'><b>ความเร่งด่วน:</b> $priority_text</div>
                    <div class='mb-1'><b>รหัสทรัพย์สิน:</b> ".($asset_code ?: '-')."</div>
                    <hr class='my-2'>
                    <div class='text-muted small'><b>รายละเอียด:</b><br>".nl2br(htmlspecialchars($description))."</div>
                </div>
            ",
            'redirect' => '/it_support/index.php'
        ];

    } catch (PDOException $e) {
        $alert = [
            'type' => 'error',
            'title' => 'เกิดข้อผิดพลาด!',
            'html' => 'Database Error: ' . $e->getMessage(),
            'redirect' => null
        ];
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="/it_support/index.php" class="btn btn-light btn-sm border me-2 shadow-sm" title="ย้อนกลับ">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-1 fw-bold text-secondary">เปิดใบงานซ่อม (Create Ticket)</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-primary bg-opacity-10 py-3 border-0" style="border-radius: 12px 12px 0 0;">
                    <div class="d-flex align-items-center text-primary">
                        <i class="bi bi-folder-plus fs-4 me-2"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">ฟอร์มแจ้งซ่อม (สำหรับเจ้าหน้าที่)</h6>
                            <small class="text-muted" style="font-size: 0.8rem;">กรอกข้อมูลเพื่อเปิด Ticket ใหม่เข้าระบบ</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <div class="form-section-title">ข้อมูลผู้แจ้ง</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ชื่อผู้แจ้ง</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control form-control-custom border-start-0 ps-0" 
                                               value="<?= $_SESSION['fullname'] ?? 'Admin' ?>" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">แผนก <span class="text-danger">*</span></label>
                                    <select name="department_id" class="form-select form-control-custom" required>
                                        <option value="">- เลือกแผนก -</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-section-title">รายละเอียดงานซ่อม</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">รหัสทรัพย์สิน</label>
                                    <div class="input-group">
                                        <input type="text" name="asset_code" class="form-control form-control-custom" placeholder="ระบุรหัส หรือกดสแกน">
                                        <button class="btn btn-light border" type="button"><i class="bi bi-qr-code"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ความเร่งด่วน</label>
                                    <select name="priority" class="form-select form-control-custom">
                                        <option value="low">🟢 ต่ำ (Low)</option>
                                        <option value="medium" selected>🟡 ปานกลาง (Medium)</option>
                                        <option value="high">🟠 สูง (High)</option>
                                        <option value="critical">🔴 วิกฤต (Critical)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">ประเภทปัญหา <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select form-control-custom" required>
                                        <option value="">- เลือกประเภท -</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">มอบหมายช่าง</label>
                                    <select name="assigned_to" class="form-select form-control-custom">
                                        <option value="">- ยังไม่ระบุ (Auto Assign) -</option>
                                        <?php foreach ($users_technician as $tech): ?>
                                            <option value="<?= $tech['id'] ?>"><?= $tech['fullname'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted">รายละเอียดอาการ <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control form-control-custom" rows="4" placeholder="อธิบายอาการ..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted">รูปภาพประกอบ</label>
                                    <div class="custom-file-upload text-center py-3">
                                        <input type="file" name="attachment" class="d-none" id="fileInput">
                                        <label for="fileInput" class="text-primary" style="cursor: pointer;">
                                            <i class="bi bi-cloud-upload me-2"></i>เลือกไฟล์
                                        </label>
                                        <span class="text-muted small ms-2">ไม่ได้เลือกไฟล์ใด</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/it_support/index.php" class="btn btn-light border px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-send-fill me-2"></i>บันทึกใบงาน</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Sidebar
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });

    // File Input Show Name
    document.getElementById('fileInput').addEventListener('change', function() {
        let fileName = this.files[0] ? this.files[0].name : 'ไม่ได้เลือกไฟล์ใด';
        this.nextElementSibling.nextElementSibling.textContent = fileName;
    });

    // SweetAlert2 Trigger
    <?php if ($alert): ?>
    Swal.fire({
        icon: '<?= $alert['type'] ?>',
        title: '<?= $alert['title'] ?>',
        html: `<?= $alert['html'] ?>`,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0d6efd',
        allowOutsideClick: false
    }).then((result) => {
        <?php if ($alert['redirect']): ?>
        if (result.isConfirmed) {
            window.location.href = '<?= $alert['redirect'] ?>';
        }
        <?php endif; ?>
    });
    <?php endif; ?>
</script>
<?php require_once '../../includes/footer.php'; ?>