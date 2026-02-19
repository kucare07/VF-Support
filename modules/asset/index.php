<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// --- 1. Prepare Dropdown Data ---
$types = $pdo->query("SELECT * FROM asset_types ORDER BY name ASC")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations ORDER BY name ASC")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();

// --- 2. Filter Logic (Secure Way) ---
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$type_id  = isset($_GET['type']) ? $_GET['type'] : '';
$loc_id   = isset($_GET['location']) ? $_GET['location'] : '';
$status   = isset($_GET['status']) ? $_GET['status'] : '';

$where = ["1=1"];
$params = [];

// Filter: Search
if ($search) {
    $where[] = "(a.asset_code LIKE ? OR a.name LIKE ? OR a.serial_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
// Filter: Dropdowns
if ($type_id) {
    $where[] = "a.asset_type_id = ?";
    $params[] = $type_id;
}
if ($loc_id) {
    $where[] = "a.location_id = ?";
    $params[] = $loc_id;
}
if ($status) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

$sql_cond = implode(" AND ", $where);

// --- 3. Query Data ---
$sql = "SELECT a.*, t.name as type_name, l.name as location_name, u.fullname as owner_name, s.name as supplier_name 
        FROM assets a 
        LEFT JOIN asset_types t ON a.asset_type_id = t.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.current_user_id = u.id
        LEFT JOIN suppliers s ON a.supplier_id = s.id 
        WHERE $sql_cond
        ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Asset Management</span>
        <span class="text-muted ms-2 small border-start ps-2">ทะเบียนทรัพย์สิน</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">

            <?php if (isset($_GET['msg'])): ?>
                <script>
                    let msg = "<?= $_GET['msg'] ?>";
                    let title = "สำเร็จ!";
                    if (msg === 'imported') title = "นำเข้าข้อมูลเรียบร้อย";
                    Swal.fire({
                        icon: 'success',
                        title: title,
                        timer: 1500,
                        showConfirmButton: false
                    });
                </script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-primary m-0"><i class="bi bi-pc-display me-2"></i>รายการครุภัณฑ์ทั้งหมด</h6>

                            <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                                <i class="bi bi-trash"></i> ลบที่เลือก
                            </button>
                        </div>

                        <div>
                            <a href="export_assets.php" target="_blank" class="btn btn-sm btn-success me-1 shadow-sm">
                                <i class="bi bi-file-earmark-excel"></i> Export
                            </a>
                            <a href="import_assets.php" class="btn btn-sm btn-outline-success me-1 shadow-sm">
                                <i class="bi bi-cloud-upload"></i> Import
                            </a>
                            <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openAddModal()">
                                <i class="bi bi-plus-lg me-1"></i> เพิ่มรายการใหม่
                            </button>
                        </div>
                    </div>

                    <form method="GET" action="index.php">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" class="form-control border-start-0" placeholder="รหัส, ชื่อ, SN..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">- ทุกประเภท -</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $type_id == $t['id'] ? 'selected' : '' ?>><?= $t['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="location" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">- ทุกสถานที่ -</option>
                                    <?php foreach ($locations as $l): ?>
                                        <option value="<?= $l['id'] ?>" <?= $loc_id == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">- ทุกสถานะ -</option>
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>ใช้งานปกติ</option>
                                    <option value="spare" <?= $status == 'spare' ? 'selected' : '' ?>>สำรอง</option>
                                    <option value="repair" <?= $status == 'repair' ? 'selected' : '' ?>>ส่งซ่อม</option>
                                    <option value="write_off" <?= $status == 'write_off' ? 'selected' : '' ?>>ตัดจำหน่าย</option>
                                </select>
                            </div>
                            <div class="col-md-auto">
                                <a href="index.php" class="btn btn-sm btn-light border text-danger">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="assetTable" class="table table-hover table-sm align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-checkbox py-3 text-center">
                                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleAll(this)">
                                    </th>
                                    <th class="text-center" style="width: 50px;">ลำดับ</th>
                                    <th class="ps-3">รหัสทรัพย์สิน (Code)</th>
                                    <th>ชื่อ/รุ่น/รายละเอียด</th>
                                    <th>ประเภท (Type)</th>
                                    <th>สถานที่ (Location)</th>
                                    <th>สถานะ (Status)</th>
                                    <th>ผู้ถือครอง (Owner)</th>
                                    <th class="text-end pe-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($assets) > 0): ?>
                                    <?php $i = 1; // ตัวแปรลำดับ 
                                    ?>
                                    <?php foreach ($assets as $item):
                                        $jsonData = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                                        $code = htmlspecialchars($item['asset_code'] ?? '-');
                                        $name = htmlspecialchars($item['name'] ?? '-');
                                        $brand = htmlspecialchars($item['brand'] ?? '');
                                        $model = htmlspecialchars($item['model'] ?? '');
                                        $type = htmlspecialchars($item['type_name'] ?? '-');
                                        $location = htmlspecialchars($item['location_name'] ?? '-');
                                        $owner = htmlspecialchars($item['owner_name'] ?? '-');

                                        $st_val = $item['status'] ?? 'active';
                                        $st_color = match ($st_val) {
                                            'active' => 'success',
                                            'repair' => 'warning',
                                            'write_off' => 'secondary',
                                            'spare' => 'info',
                                            default => 'light text-dark border'
                                        };
                                        $st_text = match ($st_val) {
                                            'active' => 'ปกติ',
                                            'repair' => 'ส่งซ่อม',
                                            'write_off' => 'ตัดจำหน่าย',
                                            'spare' => 'สำรอง',
                                            default => ucfirst($st_val)
                                        };
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input row-checkbox" value="<?= $item['id'] ?>" onclick="checkRow()">
                                            </td>
                                            <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                            <td class="ps-3 fw-bold text-primary text-nowrap">
                                                <?= $code ?>
                                                <?php if (!empty($item['image'])): ?><i class="bi bi-image text-muted ms-1" title="มีรูปภาพ"></i><?php endif; ?>
                                            </td>
                                            <td class="text-wrap-fix">
                                                <div class="fw-bold text-dark"><?= $name ?></div>
                                                <small class="text-muted"><?= $brand ?> <?= $model ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= $type ?></span></td>
                                            <td><?= $location ?></td>
                                            <td><span class="badge bg-<?= $st_color ?>"><?= $st_text ?></span></td>
                                            <td><?= $owner ?></td>
                                            <td class="text-end pe-3 text-nowrap">
                                                <a href="print_qr.php?id=<?= $item['id'] ?>" target="_blank" class="btn btn-sm btn-light border py-0 me-1 shadow-sm" title="พิมพ์ QR Code"><i class="bi bi-qr-code"></i></a>
                                                <button class="btn btn-sm btn-light border text-info py-0 me-1 shadow-sm" onclick="openViewModal('<?= $jsonData ?>')"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-light border text-warning py-0 me-1 shadow-sm" onclick="openEditModal('<?= $jsonData ?>')"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-light border text-danger py-0 shadow-sm" onclick="confirmDelete('process.php?action=delete&id=<?= $item['id'] ?>', 'ต้องการลบ <?= $code ?> ใช่หรือไม่?')"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูล</td>
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

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="assetId">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="formTitle">จัดการครุภัณฑ์</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active py-1 small fw-bold" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">ข้อมูลทั่วไป</button></li>
                        <li class="nav-item"><button class="nav-link py-1 small fw-bold" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec" type="button">สเปกเครื่อง</button></li>
                        <li class="nav-item"><button class="nav-link py-1 small fw-bold" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button">สถานะ/การจัดซื้อ</button></li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="general">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small fw-bold">รหัสครุภัณฑ์ <span class="text-danger">*</span></label><input type="text" name="asset_code" id="asset_code" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">ชื่อเครื่อง <span class="text-danger">*</span></label><input type="text" name="name" id="name" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">ประเภท <span class="text-danger">*</span></label><select name="asset_type_id" id="asset_type_id" class="form-select" required>
                                        <option value="">-- เลือก --</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Serial Number</label><input type="text" name="serial_number" id="serial_number" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">ยี่ห้อ</label><input type="text" name="brand" id="brand" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">รุ่น</label><input type="text" name="model" id="model" class="form-control"></div>
                                <div class="col-12 mt-2">
                                    <label class="form-label small fw-bold">รูปภาพ</label>
                                    <div class="upload-area" onclick="document.getElementById('imageInput').click()">
                                        <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                                        <div class="small text-muted mt-1">คลิกเพื่ออัปโหลด</div>
                                        <input type="file" name="image" id="imageInput" class="d-none" accept="image/*" onchange="previewAssetImage(this)">
                                        <img id="preview_img" class="preview-img mx-auto">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="spec">
                            <div class="row g-3">
                                <div class="col-md-12"><label class="form-label small fw-bold">CPU</label><input type="text" name="spec_cpu" id="spec_cpu" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">RAM</label><input type="text" name="spec_ram" id="spec_ram" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Storage</label><input type="text" name="spec_storage" id="spec_storage" class="form-control"></div>
                                <div class="col-md-12"><label class="form-label small fw-bold">OS / License</label><input type="text" name="os_license" id="os_license" class="form-control"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="status">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small fw-bold">สถานะ</label><select name="status" id="status_val" class="form-select">
                                        <option value="active">Active (ปกติ)</option>
                                        <option value="spare">Spare (สำรอง)</option>
                                        <option value="repair">Repair (ส่งซ่อม)</option>
                                        <option value="write_off">Write-off (ตัดจำหน่าย)</option>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">ผู้ถือครอง</label><select name="current_user_id" id="current_user_id" class="form-select select2">
                                        <option value="">-- ว่าง (กองกลาง) --</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= $u['fullname'] ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">สถานที่</label><select name="location_id" id="location_id" class="form-select">
                                        <option value="">-- เลือก --</option><?php foreach ($locations as $l): ?><option value="<?= $l['id'] ?>"><?= $l['name'] ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">ผู้ขาย</label><select name="supplier_id" id="supplier_id" class="form-select">
                                        <option value="">-- เลือก --</option><?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= $s['name'] ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">ราคา</label><input type="number" name="price" id="price" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">วันที่ซื้อ</label><input type="date" name="purchase_date" id="purchase_date" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">ประกันหมด</label><input type="date" name="warranty_expire" id="warranty_expire" class="form-control"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="header-gradient">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold" id="viewTitle">รายละเอียด</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="viewTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active py-1 small" data-bs-toggle="tab" data-bs-target="#v_info" type="button"><i class="bi bi-info-circle"></i> รายละเอียด</button></li>
                    <li class="nav-item"><button class="nav-link py-1 small" data-bs-toggle="tab" data-bs-target="#v_history" type="button"><i class="bi bi-clock-history"></i> ประวัติ Log</button></li>
                    <li class="nav-item"><button class="nav-link py-1 small text-primary" data-bs-toggle="tab" data-bs-target="#v_tickets" type="button"><i class="bi bi-ticket-perforated"></i> แจ้งซ่อม</button></li>
                    <li class="nav-item"><button class="nav-link py-1 small text-success" data-bs-toggle="tab" data-bs-target="#v_software" type="button"><i class="bi bi-window-sidebar"></i> ซอฟต์แวร์</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="v_info">
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-bordered table-sm mb-0 small">
                                    <tr>
                                        <td class="bg-light fw-bold w-25 ps-3">รหัส</td>
                                        <td class="ps-3" id="v_code"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">ชื่อ</td>
                                        <td class="ps-3" id="v_name"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">สเปก</td>
                                        <td class="ps-3" id="v_spec"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">ผู้ใช้</td>
                                        <td class="ps-3" id="v_user"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">สถานที่</td>
                                        <td class="ps-3" id="v_location"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">ผู้ขาย</td>
                                        <td class="ps-3" id="v_supplier"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">ประกันหมด</td>
                                        <td class="ps-3" id="v_warranty"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-bold ps-3">สถานะ</td>
                                        <td class="ps-3" id="v_status"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="border rounded p-2 bg-light h-100 d-flex align-items-center justify-content-center">
                                    <img id="v_image" src="" alt="No Image" class="img-fluid" style="max-height: 200px; display:none;">
                                    <span id="v_no_image" class="text-muted small">ไม่มีรูปภาพ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v_history">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm small">
                                <tbody id="history_body">
                                    <tr>
                                        <td class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v_tickets">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm small">
                                <tbody id="ticket_body">
                                    <tr>
                                        <td class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v_software">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm small">
                                <tbody id="software_body">
                                    <tr>
                                        <td class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var formModal, viewModal;
    document.addEventListener('DOMContentLoaded', function() {
        formModal = new bootstrap.Modal(document.getElementById('formModal'));
        viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    });

    function previewAssetImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview_img').src = e.target.result;
                document.getElementById('preview_img').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('formTitle').innerText = 'เพิ่มรายการใหม่';
        document.getElementById('assetId').value = '';
        document.getElementById('preview_img').style.display = 'none';
        document.forms[0].reset();

        // Reset Select2 if used
        if (window.$ && $.fn.select2) {
            $('#current_user_id').val(null).trigger('change');
        }

        formModal.show();
    }

    function openEditModal(jsonData) {
        const data = JSON.parse(jsonData);
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formTitle').innerText = 'แก้ไขรายการ';
        document.getElementById('assetId').value = data.id;

        // Map Fields
        const fields = ['asset_code', 'name', 'serial_number', 'brand', 'model', 'spec_cpu', 'spec_ram', 'spec_storage', 'os_license', 'price', 'purchase_date', 'warranty_expire'];
        fields.forEach(f => {
            if (document.getElementById(f)) document.getElementById(f).value = data[f] || '';
        });

        // Selects
        if (data.asset_type_id) document.getElementById('asset_type_id').value = data.asset_type_id;
        if (data.status) document.getElementById('status_val').value = data.status;
        if (data.location_id) document.getElementById('location_id').value = data.location_id;
        if (data.supplier_id) document.getElementById('supplier_id').value = data.supplier_id;

        if (data.current_user_id) {
            if (window.$ && $.fn.select2) {
                $('#current_user_id').val(data.current_user_id).trigger('change');
            } else {
                document.getElementById('current_user_id').value = data.current_user_id;
            }
        }

        // Image
        const imgPreview = document.getElementById('preview_img');
        if (data.image) {
            imgPreview.src = '../../uploads/assets/' + data.image;
            imgPreview.style.display = 'block';
        } else {
            imgPreview.style.display = 'none';
        }

        formModal.show();
    }

    function openViewModal(jsonData) {
        const data = JSON.parse(jsonData);
        document.getElementById('viewTitle').innerText = data.asset_code;
        document.getElementById('v_code').innerText = data.asset_code;
        document.getElementById('v_name').innerText = data.name + ' (' + (data.brand || '-') + ' ' + (data.model || '-') + ')';
        document.getElementById('v_spec').innerText = (data.spec_cpu || '-') + ' / ' + (data.spec_ram || '-') + ' / ' + (data.spec_storage || '-');
        document.getElementById('v_user').innerText = data.owner_name || 'ว่าง';
        document.getElementById('v_location').innerText = data.location_name || '-';
        document.getElementById('v_supplier').innerText = data.supplier_name || '-';
        document.getElementById('v_warranty').innerText = data.warranty_expire || '-';
        document.getElementById('v_status').innerText = (data.status || '').toUpperCase();

        const img = document.getElementById('v_image');
        const noImg = document.getElementById('v_no_image');
        if (data.image) {
            img.src = '../../uploads/assets/' + data.image;
            img.style.display = 'block';
            noImg.style.display = 'none';
        } else {
            img.style.display = 'none';
            noImg.style.display = 'block';
        }

        // AJAX Loaders
        loadHistory(data.id);
        loadTickets(data.asset_code);
        loadSoftware(data.id);

        viewModal.show();
    }

    function loadHistory(id) {
        fetch('get_history.php?id=' + id).then(r => r.text()).then(h => {
            document.getElementById('history_body').innerHTML = h;
        });
    }

    function loadTickets(code) {
        fetch('get_tickets.php?code=' + encodeURIComponent(code)).then(r => r.text()).then(h => {
            document.getElementById('ticket_body').innerHTML = h;
        });
    }

    function loadSoftware(id) {
        fetch('get_software.php?id=' + id).then(r => r.text()).then(h => {
            document.getElementById('software_body').innerHTML = h;
        });
    }
</script>