<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงข้อมูล Assets และ Master Data สำหรับ Dropdown
$assets = $pdo->query("SELECT a.*, t.name as type_name, l.name as location_name, u.fullname as owner_name 
                       FROM assets a 
                       LEFT JOIN asset_types t ON a.asset_type_id = t.id
                       LEFT JOIN locations l ON a.location_id = l.id
                       LEFT JOIN users u ON a.current_user_id = u.id
                       ORDER BY a.id DESC")->fetchAll();

$types = $pdo->query("SELECT * FROM asset_types")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_active = 1")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 shadow-sm">
            <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-1 fw-bold text-secondary">ทะเบียนทรัพย์สิน (Asset Management)</span>
        </nav>

        <div class="container-fluid p-4">
            <?php if(isset($_GET['msg'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'ดำเนินการเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    });
                </script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-primary"><i class="bi bi-pc-display me-2"></i>รายการทรัพย์สิน</h5>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มครุภัณฑ์ (Popup)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัส</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>ประเภท</th>
                                    <th>สถานะ</th>
                                    <th>ผู้ถือครอง</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($assets as $item): 
                                    // แปลงข้อมูลทั้งก้อนเป็น JSON เพื่อฝังในปุ่ม
                                    $jsonData = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= $item['asset_code'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= $item['name'] ?></div>
                                        <small class="text-muted"><?= $item['brand'] ?> <?= $item['model'] ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $item['type_name'] ?></span></td>
                                    <td>
                                        <?php 
                                            $st_color = match($item['status']) { 'active'=>'success', 'repair'=>'warning', 'write_off'=>'secondary', default=>'info' };
                                            $st_text = match($item['status']) { 'active'=>'ใช้งานปกติ', 'repair'=>'ส่งซ่อม', 'write_off'=>'ตัดจำหน่าย', 'spare'=>'สำรอง', default=>$item['status'] };
                                        ?>
                                        <span class="badge bg-<?= $st_color ?>"><?= $st_text ?></span>
                                    </td>
                                    <td><?= $item['owner_name'] ?: '-' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light text-info border me-1" onclick="openViewModal('<?= $jsonData ?>')" title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-warning border me-1" onclick="openEditModal('<?= $jsonData ?>')" title="แก้ไข">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-danger border" onclick="confirmDelete(<?= $item['id'] ?>, '<?= $item['asset_code'] ?>')" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="formAction"> <input type="hidden" name="id" id="assetId">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="formTitle">จัดการครุภัณฑ์</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">ข้อมูลทั่วไป</button></li>
                        <li class="nav-item"><button class="nav-link" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec" type="button">สเปกเครื่อง</button></li>
                        <li class="nav-item"><button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button">สถานะ/การจัดซื้อ</button></li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="general">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label>รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                                    <input type="text" name="asset_code" id="asset_code" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>ชื่อเครื่อง <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>ประเภท <span class="text-danger">*</span></label>
                                    <select name="asset_type_id" id="asset_type_id" class="form-select" required>
                                        <option value="">-- เลือก --</option>
                                        <?php foreach($types as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><label>Serial Number</label><input type="text" name="serial_number" id="serial_number" class="form-control"></div>
                                <div class="col-md-6"><label>ยี่ห้อ</label><input type="text" name="brand" id="brand" class="form-control"></div>
                                <div class="col-md-6"><label>รุ่น</label><input type="text" name="model" id="model" class="form-control"></div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="spec">
                            <div class="row g-3">
                                <div class="col-md-12"><label>CPU</label><input type="text" name="spec_cpu" id="spec_cpu" class="form-control"></div>
                                <div class="col-md-6"><label>RAM</label><input type="text" name="spec_ram" id="spec_ram" class="form-control"></div>
                                <div class="col-md-6"><label>Storage</label><input type="text" name="spec_storage" id="spec_storage" class="form-control"></div>
                                <div class="col-md-12"><label>OS / License</label><input type="text" name="os_license" id="os_license" class="form-control"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="status">
                             <div class="row g-3">
                                <div class="col-md-6">
                                    <label>สถานะ</label>
                                    <select name="status" id="status_val" class="form-select">
                                        <option value="active">✅ ใช้งานปกติ</option>
                                        <option value="spare">📦 สำรอง</option>
                                        <option value="repair">🔧 ส่งซ่อม</option>
                                        <option value="write_off">🗑️ ตัดจำหน่าย</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>ผู้ถือครอง</label>
                                    <select name="current_user_id" id="current_user_id" class="form-select">
                                        <option value="">-- ว่าง --</option>
                                        <?php foreach($users as $u): ?><option value="<?= $u['id'] ?>"><?= $u['fullname'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>สถานที่</label>
                                    <select name="location_id" id="location_id" class="form-select">
                                        <option value="">-- เลือก --</option>
                                        <?php foreach($locations as $l): ?><option value="<?= $l['id'] ?>"><?= $l['name'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><label>ราคา</label><input type="number" name="price" id="price" class="form-control"></div>
                                <div class="col-md-6"><label>วันที่ซื้อ</label><input type="date" name="purchase_date" id="purchase_date" class="form-control"></div>
                                <div class="col-md-6"><label>ประกันหมด</label><input type="date" name="warranty_expire" id="warranty_expire" class="form-control"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="viewTitle">รายละเอียด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr><td class="bg-light fw-bold w-25">รหัส</td><td id="v_code"></td></tr>
                    <tr><td class="bg-light fw-bold">ชื่อ</td><td id="v_name"></td></tr>
                    <tr><td class="bg-light fw-bold">สเปก</td><td id="v_spec"></td></tr>
                    <tr><td class="bg-light fw-bold">ผู้ใช้</td><td id="v_user"></td></tr>
                    <tr><td class="bg-light fw-bold">สถานที่</td><td id="v_location"></td></tr>
                    <tr><td class="bg-light fw-bold">สถานะ</td><td id="v_status"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    var formModal = new bootstrap.Modal(document.getElementById('formModal'));
    var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

    // เปิด Modal เพิ่ม
    function openAddModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('assetId').value = '';
        document.getElementById('formTitle').innerText = 'เพิ่มครุภัณฑ์ใหม่';
        document.getElementById('btnSave').innerText = 'บันทึกข้อมูล';
        document.getElementById('btnSave').classList.replace('btn-warning', 'btn-primary');
        
        // Clear Form
        document.querySelectorAll('#formModal input, #formModal select').forEach(input => {
            if(input.type != 'hidden') input.value = '';
        });
        
        formModal.show();
    }

    // เปิด Modal แก้ไข (ดึง JSON มาใส่ Form)
    function openEditModal(jsonData) {
        const data = JSON.parse(jsonData); // แปลง JSON กลับเป็น Object
        
        document.getElementById('formAction').value = 'edit';
        document.getElementById('assetId').value = data.id;
        document.getElementById('formTitle').innerText = 'แก้ไขครุภัณฑ์: ' + data.asset_code;
        document.getElementById('btnSave').innerText = 'อัปเดตข้อมูล';
        document.getElementById('btnSave').classList.replace('btn-primary', 'btn-warning');

        // Loop ใส่ค่าลง Input ตาม ID (ตั้งชื่อ ID ให้ตรงกับชื่อ Field ใน DB)
        // เทคนิค: ใช้ Object.keys วนลูป
        for (const [key, value] of Object.entries(data)) {
            let input = document.getElementById(key); // หา input ที่มี id ตรงกับชื่อ field
            if (input) {
                input.value = value || ''; // ถ้าค่าเป็น null ให้ใส่ว่าง
            }
        }
        
        // กรณีชื่อ field ใน json ไม่ตรงกับ id ใน html ต้อง map เอง (ถ้ามี)
        // เช่น id="status_val"
        document.getElementById('status_val').value = data.status;

        formModal.show();
    }

    // เปิด Modal ดูรายละเอียด
    function openViewModal(jsonData) {
        const data = JSON.parse(jsonData);
        
        document.getElementById('viewTitle').innerText = data.asset_code;
        document.getElementById('v_code').innerText = data.asset_code;
        document.getElementById('v_name').innerText = data.name + ' (' + (data.brand||'-') + ' ' + (data.model||'-') + ')';
        document.getElementById('v_spec').innerText = (data.spec_cpu||'-') + ' / ' + (data.spec_ram||'-') + ' / ' + (data.spec_storage||'-');
        document.getElementById('v_user').innerText = data.owner_name || 'ว่าง';
        document.getElementById('v_location').innerText = data.location_name || '-';
        document.getElementById('v_status').innerText = data.status.toUpperCase();
        
        viewModal.show();
    }

    // ยืนยันการลบ
    function confirmDelete(id, code) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณต้องการลบ ${code} ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `process.php?action=delete&id=${id}`;
            }
        });
    }

    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>

<?php require_once '../../includes/footer.php'; ?>