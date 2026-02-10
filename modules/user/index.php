<?php
require_once '../../includes/auth.php';
requireAdmin(); // ฟังก์ชันตรวจสอบว่าเป็น Admin เท่านั้น
require_once '../../config/db_connect.php';

// ดึงรายชื่อแผนก
$depts = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

// Filter Logic
$search = $_GET['q'] ?? '';
$role = $_GET['role'] ?? '';
$dept = $_GET['dept'] ?? '';

$where = ["1=1"];
$params = [];

// ✅ แก้ไข: ใช้ Placeholder (?) แทนการใส่ตัวแปรตรงๆ
if ($search) {
    $where[] = "(fullname LIKE ? OR username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role) { 
    $where[] = "role = ?"; 
    $params[] = $role; 
}
if ($dept) { 
    $where[] = "department_id = ?"; 
    $params[] = $dept; 
}

$sql_cond = implode(" AND ", $where);

// ✅ ใช้ Prepared Statement
$sql = "SELECT u.*, d.name as dept_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE $sql_cond 
        ORDER BY u.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">User Management</span>
        <span class="text-muted ms-2 small border-start ps-2">จัดการผู้ใช้งาน</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-2"> <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'สำเร็จ!', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-people-fill me-2"></i>รายชื่อผู้ใช้งาน</h6>
                        <button class="btn btn-sm btn-primary shadow-sm" onclick="openModal('add')">
                            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้ใช้งาน
                        </button>
                    </div>

                    <form method="GET" action="">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" name="q" class="form-control form-control-sm" placeholder="ค้นหาชื่อ, username..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">- ทุกสิทธิ์ -</option>
                                    <option value="admin" <?= $role=='admin'?'selected':'' ?>>Admin</option>
                                    <option value="technician" <?= $role=='technician'?'selected':'' ?>>Technician</option>
                                    <option value="user" <?= $role=='user'?'selected':'' ?>>User</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">- ทุกแผนก -</option>
                                    <?php foreach($depts as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= $dept==$d['id']?'selected':'' ?>><?= $d['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-auto">
                                <a href="index.php" class="btn btn-sm btn-light border">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ชื่อ-นามสกุล</th>
                                    <th>Username</th>
                                    <th>แผนก</th>
                                    <th>สิทธิ์ (Role)</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): 
                                    $json = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
                                    $role_badge = match($u['role']) {
                                        'admin' => '<span class="badge bg-danger">Admin</span>',
                                        'technician' => '<span class="badge bg-primary">Tech</span>',
                                        default => '<span class="badge bg-secondary">User</span>'
                                    };
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold"><?= $u['fullname'] ?></div>
                                        <small class="text-muted"><?= $u['email'] ?></small>
                                    </td>
                                    <td><?= $u['username'] ?></td>
                                    <td><?= $u['dept_name'] ?: '-' ?></td>
                                    <td><?= $role_badge ?></td>
                                    <td>
                                        <?php if($u['is_active']): ?>
                                            <span class="text-success small"><i class="bi bi-check-circle-fill"></i> ปกติ</span>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-slash-circle"></i> ระงับ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1 shadow-sm" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light border text-dark py-0 me-1 shadow-sm" onclick="resetPass(<?= $u['id'] ?>, '<?= $u['username'] ?>')" title="รีเซ็ตรหัสผ่าน"><i class="bi bi-key"></i></button>
                                        
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

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="u_action">
                <input type="hidden" name="id" id="u_id">
                
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="u_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-bold">ชื่อ-นามสกุล *</label><input type="text" name="fullname" id="fullname" class="form-control form-control-sm" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small fw-bold">Username *</label><input type="text" name="username" id="username" class="form-control form-control-sm" required></div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">รหัสผ่าน <span id="pass_req" class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="(ว่างไว้ถ้าไม่เปลี่ยน)">
                        </div>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-bold">Email</label><input type="email" name="email" id="email" class="form-control form-control-sm"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">แผนก</label>
                            <select name="department_id" id="department_id" class="form-select form-select-sm">
                                <option value="">-- เลือกแผนก --</option>
                                <?php foreach($depts as $d): ?><option value="<?= $d['id'] ?>"><?= $d['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">สิทธิ์การใช้งาน</label>
                            <select name="role" id="role" class="form-select form-select-sm">
                                <option value="user">User (ผู้แจ้ง)</option>
                                <option value="technician">Technician (ช่าง)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label small" for="is_active">อนุญาตให้ใช้งาน (Active)</label>
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-primary">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var userModal;
    document.addEventListener('DOMContentLoaded', function() { userModal = new bootstrap.Modal(document.getElementById('userModal')); });

    function openModal(action, json = null) {
        document.getElementById('u_action').value = action;
        document.getElementById('u_title').innerText = (action == 'add' ? 'เพิ่มผู้ใช้งานใหม่' : 'แก้ไขข้อมูลผู้ใช้');
        document.getElementById('u_id').value = '';
        document.forms[0].reset();
        
        if(action == 'add') {
            document.getElementById('password').required = true;
            document.getElementById('pass_req').style.display = 'inline';
        } else {
            document.getElementById('password').required = false;
            document.getElementById('pass_req').style.display = 'none';
        }

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('u_id').value = d.id;
            document.getElementById('fullname').value = d.fullname;
            document.getElementById('username').value = d.username;
            document.getElementById('email').value = d.email;
            document.getElementById('department_id').value = d.department_id;
            document.getElementById('role').value = d.role;
            document.getElementById('is_active').checked = (d.is_active == 1);
        }
        userModal.show();
    }

    function resetPass(id, name) {
        Swal.fire({
            title: 'รีเซ็ตรหัสผ่าน?',
            text: `ต้องการตั้งรหัสผ่านของ ${name} เป็น "1234" หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((r) => {
            if(r.isConfirmed) window.location.href = `process.php?action=reset_pass&id=${id}`;
        });
    }
</script>