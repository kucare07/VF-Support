<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// ดึงค่า Config ทั้งหมด
$stmt = $pdo->query("SELECT * FROM system_settings");
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['setting_key']] = $row['setting_value'];
}
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
    
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">ตั้งค่าระบบ (System Config)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'บันทึกเรียบร้อย', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <script>Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '<?= htmlspecialchars($_GET['error']) ?>'});</script>
            <?php endif; ?>

            <form action="process.php" method="POST">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-primary m-0"><i class="bi bi-sliders me-2"></i>ตั้งค่าการทำงาน</h5>
                        <small class="text-muted">จัดการข้อมูลทั่วไปและการเชื่อมต่อ API</small>
                    </div>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="bi bi-save me-2"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom pt-3 px-3">
                        <ul class="nav nav-tabs card-header-tabs" id="settingTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                    <i class="bi bi-hdd-rack me-2"></i>ทั่วไป
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold" id="notify-tab" data-bs-toggle="tab" data-bs-target="#notify" type="button">
                                    <i class="bi bi-line me-2"></i>LINE API
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold text-danger" id="advance-tab" data-bs-toggle="tab" data-bs-target="#advance" type="button">
                                    <i class="bi bi-shield-exclamation me-2"></i>ขั้นสูง
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="tab-content" id="settingTabContent">
                            
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">ข้อมูลองค์กร</h6>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">ชื่อระบบ (Site Name)</label>
                                    <div class="col-md-9">
                                        <input type="text" name="settings[site_name]" class="form-control" value="<?= htmlspecialchars($config['site_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">ชื่อองค์กร (Organization)</label>
                                    <div class="col-md-9">
                                        <input type="text" name="settings[org_name]" class="form-control" value="<?= htmlspecialchars($config['org_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">แจ้งเตือน PM ล่วงหน้า</label>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="number" name="settings[pm_alert_days]" class="form-control" value="<?= htmlspecialchars($config['pm_alert_days'] ?? '7') ?>">
                                            <span class="input-group-text">วัน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notify" role="tabpanel">
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="bi bi-robot fs-1 me-3"></i>
                                    <div>
                                        <strong>LINE Messaging API</strong><br>
                                        ระบบใหม่! ใช้การส่งข้อความผ่าน Bot (Push Message)<br>
                                        <small>ต้องมี 1. Channel Access Token และ 2. User ID (หรือ Group ID) ที่ต้องการให้บอทส่งหา</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">Channel Access Token</label>
                                    <div class="col-md-9">
                                        <textarea name="settings[line_channel_token]" class="form-control font-monospace" rows="3" placeholder="วาง Token ยาวๆ ที่นี่..."><?= htmlspecialchars($config['line_channel_token'] ?? '') ?></textarea>
                                        <div class="form-text">จากเมนู Messaging API ใน LINE Developers Console</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">Destination ID</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" name="settings[line_dest_id]" class="form-control font-monospace" value="<?= htmlspecialchars($config['line_dest_id'] ?? '') ?>" placeholder="Uxxxxxxxx... หรือ Cxxxxxxxx...">
                                        </div>
                                        <div class="form-text">ID ของผู้ใช้ (User ID) หรือกลุ่ม (Group ID) ที่ต้องการรับการแจ้งเตือน</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-9 offset-md-3">
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="testLine()">
                                            <i class="bi bi-send"></i> ทดสอบส่งข้อความ
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="advance" role="tabpanel">
                                <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">System Control</h6>
                                <div class="row mb-3 align-items-center">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">สถานะ Maintenance</label>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="settings[maintenance_mode]" value="0">
                                            <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" value="1" id="maintSwitch" <?= ($config['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintSwitch">เปิดโหมดปรับปรุงระบบ (User ทั่วไปจะเข้าไม่ได้)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('menu-toggle').addEventListener('click', e => { e.preventDefault(); document.getElementById('sidebar-wrapper').classList.toggle('active'); });

    function testLine() {
        Swal.fire({
            title: 'บันทึกก่อนทดสอบ',
            text: 'กรุณากดปุ่ม "บันทึกการเปลี่ยนแปลง" ก่อนกดทดสอบ เพื่อให้ระบบจำค่าล่าสุด',
            icon: 'info'
        });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// ดึงค่า Config ทั้งหมด
$stmt = $pdo->query("SELECT * FROM system_settings");
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['setting_key']] = $row['setting_value'];
}
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">ตั้งค่าระบบ (System Config)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'บันทึกเรียบร้อย', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <script>Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '<?= htmlspecialchars($_GET['error']) ?>'});</script>
            <?php endif; ?>

            <form action="process.php" method="POST">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-primary m-0"><i class="bi bi-sliders me-2"></i>ตั้งค่าการทำงาน</h5>
                        <small class="text-muted">จัดการข้อมูลทั่วไปและการเชื่อมต่อ API</small>
                    </div>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="bi bi-save me-2"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom pt-3 px-3">
                        <ul class="nav nav-tabs card-header-tabs" id="settingTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                    <i class="bi bi-hdd-rack me-2"></i>ทั่วไป
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold" id="notify-tab" data-bs-toggle="tab" data-bs-target="#notify" type="button">
                                    <i class="bi bi-line me-2"></i>LINE API
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold text-danger" id="advance-tab" data-bs-toggle="tab" data-bs-target="#advance" type="button">
                                    <i class="bi bi-shield-exclamation me-2"></i>ขั้นสูง
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="tab-content" id="settingTabContent">
                            
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">ข้อมูลองค์กร</h6>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">ชื่อระบบ (Site Name)</label>
                                    <div class="col-md-9">
                                        <input type="text" name="settings[site_name]" class="form-control" value="<?= htmlspecialchars($config['site_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">ชื่อองค์กร (Organization)</label>
                                    <div class="col-md-9">
                                        <input type="text" name="settings[org_name]" class="form-control" value="<?= htmlspecialchars($config['org_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">แจ้งเตือน PM ล่วงหน้า</label>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="number" name="settings[pm_alert_days]" class="form-control" value="<?= htmlspecialchars($config['pm_alert_days'] ?? '7') ?>">
                                            <span class="input-group-text">วัน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notify" role="tabpanel">
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="bi bi-robot fs-1 me-3"></i>
                                    <div>
                                        <strong>LINE Messaging API</strong><br>
                                        ระบบใหม่! ใช้การส่งข้อความผ่าน Bot (Push Message)<br>
                                        <small>ต้องมี 1. Channel Access Token และ 2. User ID (หรือ Group ID) ที่ต้องการให้บอทส่งหา</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">Channel Access Token</label>
                                    <div class="col-md-9">
                                        <textarea name="settings[line_channel_token]" class="form-control font-monospace" rows="3" placeholder="วาง Token ยาวๆ ที่นี่..."><?= htmlspecialchars($config['line_channel_token'] ?? '') ?></textarea>
                                        <div class="form-text">จากเมนู Messaging API ใน LINE Developers Console</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">Destination ID</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" name="settings[line_dest_id]" class="form-control font-monospace" value="<?= htmlspecialchars($config['line_dest_id'] ?? '') ?>" placeholder="Uxxxxxxxx... หรือ Cxxxxxxxx...">
                                        </div>
                                        <div class="form-text">ID ของผู้ใช้ (User ID) หรือกลุ่ม (Group ID) ที่ต้องการรับการแจ้งเตือน</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-9 offset-md-3">
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="testLine()">
                                            <i class="bi bi-send"></i> ทดสอบส่งข้อความ
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="advance" role="tabpanel">
                                <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">System Control</h6>
                                <div class="row mb-3 align-items-center">
                                    <label class="col-md-3 col-form-label fw-bold text-muted">สถานะ Maintenance</label>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="settings[maintenance_mode]" value="0">
                                            <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" value="1" id="maintSwitch" <?= ($config['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintSwitch">เปิดโหมดปรับปรุงระบบ (User ทั่วไปจะเข้าไม่ได้)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('menu-toggle').addEventListener('click', e => { e.preventDefault(); document.getElementById('sidebar-wrapper').classList.toggle('active'); });

    function testLine() {
        Swal.fire({
            title: 'บันทึกก่อนทดสอบ',
            text: 'กรุณากดปุ่ม "บันทึกการเปลี่ยนแปลง" ก่อนกดทดสอบ เพื่อให้ระบบจำค่าล่าสุด',
            icon: 'info'
        });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>