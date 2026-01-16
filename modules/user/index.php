<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// ดึงข้อมูล Users และ Departments
$users = $pdo->query("SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.id ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">จัดการผู้ใช้งาน (User Management)</span>
        </nav>

        <div class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> ทำรายการสำเร็จ
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>รายชื่อผู้ใช้งาน</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-person-plus-fill me-2"></i> เพิ่มผู้ใช้งาน (Popup)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>แผนก</th>
                                    <th>สิทธิ์</th>
                                    <th>สถานะ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= $u['username'] ?></td>
                                    <td><?= $u['fullname'] ?></td>
                                    <td><span class="badge bg-light text-secondary border"><?= $u['dept_name'] ?? '-' ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $u['role']=='admin'?'danger':($u['role']=='technician'?'success':'secondary') ?>">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($u['is_active']): ?>
                                            <span class="text-success small"><i class="bi bi-circle-fill"></i> ปกติ</span>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-slash-circle"></i> ระงับ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                onclick="openEditModal(this)"
                                                data-id="<?= $u['id'] ?>"
                                                data-username="<?= $u['username'] ?>"
                                                data-fullname="<?= $u['fullname'] ?>"
                                                data-role="<?= $u['role'] ?>"
                                                data-dept="<?= $u['department_id'] ?>"
                                                data-active="<?= $u['is_active'] ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openDeleteModal(<?= $u['id'] ?>, '<?= $u['username'] ?>')">
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

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="add"> <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>เพิ่มผู้ใช้งานใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required placeholder="เช่น somchai">
                    </div>
                    <div class="mb-3">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" required value="123456">
                    </div>
                    <div class="mb-3">
                        <label>ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required placeholder="นายสมชาย ใจดี">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>แผนก</label>
                            <select name="department_id" class="form-select">
                                <option value="">- เลือก -</option>
                                <?php foreach($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label>สิทธิ์</label>
                            <select name="role" class="form-select" required>
                                <option value="user">User</option>
                                <option value="technician">Technician</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="edit"> <input type="hidden" name="id" id="edit_id"> <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลผู้ใช้</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" id="edit_username" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label>ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>แผนก</label>
                            <select name="department_id" id="edit_dept" class="form-select">
                                <option value="">- เลือก -</option>
                                <?php foreach($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label>สิทธิ์</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="user">User</option>
                                <option value="technician">Technician</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_active">
                        <label class="form-check-label" for="edit_active">สถานะ Active (เปิดใช้งาน)</label>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <label class="small text-muted">เปลี่ยนรหัสผ่าน (ปล่อยว่างถ้าไม่เปลี่ยน)</label>
                        <input type="text" name="password" class="form-control form-control-sm" placeholder="รหัสผ่านใหม่...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body p-4">
                <i class="bi bi-exclamation-circle text-danger display-4 mb-3"></i>
                <h5 class="fw-bold">ยืนยันการลบ?</h5>
                <p class="text-muted small mb-4">คุณต้องการลบผู้ใช้ <span id="del_username" class="fw-bold text-dark"></span> ใช่หรือไม่?<br>การกระทำนี้ไม่สามารถย้อนกลับได้</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" id="btn_confirm_delete" class="btn btn-danger">ยืนยันลบ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. ฟังก์ชันเปิด Modal แก้ไข และดึงค่าไปใส่ใน Form
    function openEditModal(btn) {
        // ดึงค่าจาก data-attributes ที่ฝังในปุ่ม
        const id = btn.getAttribute('data-id');
        const username = btn.getAttribute('data-username');
        const fullname = btn.getAttribute('data-fullname');
        const role = btn.getAttribute('data-role');
        const dept = btn.getAttribute('data-dept');
        const active = btn.getAttribute('data-active');

        // เอาค่าไปใส่ใน Input ของ Modal
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_fullname').value = fullname;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_dept').value = dept;
        document.getElementById('edit_active').checked = (active == 1);

        // สั่งเปิด Modal
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    // 2. ฟังก์ชันเปิด Modal ลบ
    function openDeleteModal(id, username) {
        document.getElementById('del_username').innerText = username;
        // สร้าง Link สำหรับลบ ส่งไปที่ process.php
        document.getElementById('btn_confirm_delete').href = 'process.php?action=delete&id=' + id;
        
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    
    // Sidebar Toggle
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>

<?php require_once '../../includes/footer.php'; ?>