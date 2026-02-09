<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// --- Logic จัดการ Database (Save/Delete) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmt->execute([$name, $code]);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE departments SET name = ?, code = ? WHERE id = ?");
        $stmt->execute([$name, $code, $id]);
    }
    header("Location: departments.php");
    exit();
}

if (isset($_GET['delete'])) {
    // เช็คก่อนลบว่ามี User ใช้อยู่ไหม
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE department_id = ?");
    $chk->execute([$_GET['delete']]);
    if ($chk->fetchColumn() > 0) {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากมีพนักงานในแผนกนี้'); window.location='departments.php';</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: departments.php");
    }
    exit();
}

// ดึงข้อมูลแผนกทั้งหมด
$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
       
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">จัดการแผนก (Departments)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-building me-2"></i>รายชื่อแผนก</h6>
                    <button class="btn btn-sm btn-primary" onclick="setModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มแผนกใหม่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 10%;">ID</th>
                                    <th>รหัสแผนก (Code)</th>
                                    <th>ชื่อแผนก (Name)</th>
                                    <th class="text-end pe-3" style="width: 15%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($departments as $d): ?>
                                <tr>
                                    <td class="ps-3 text-muted">#<?= $d['id'] ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $d['code'] ?></span></td>
                                    <td class="fw-bold text-primary"><?= $d['name'] ?></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1" 
                                                onclick="setModal('edit', '<?= $d['id'] ?>', '<?= htmlspecialchars($d['name']) ?>', '<?= htmlspecialchars($d['code']) ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ยืนยันการลบ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="id" id="modalId">
                
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="modalTitle"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ชื่อแผนก *</label>
                        <input type="text" name="name" id="deptName" class="form-control form-control-sm" required placeholder="เช่น ฝ่ายบุคคล">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">รหัสแผนก (ตัวย่อ)</label>
                        <input type="text" name="code" id="deptCode" class="form-control form-control-sm" placeholder="เช่น HR">
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0">
                    <button type="submit" class="btn btn-sm btn-primary w-100">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var deptModal;
    document.addEventListener('DOMContentLoaded', function() {
        deptModal = new bootstrap.Modal(document.getElementById('deptModal'));
    });

    function setModal(action, id = '', name = '', code = '') {
        document.getElementById('modalAction').value = action;
        document.getElementById('modalId').value = id;
        document.getElementById('deptName').value = name;
        document.getElementById('deptCode').value = code;
        
        document.getElementById('modalTitle').innerText = (action == 'add' ? 'เพิ่มแผนกใหม่' : 'แก้ไขแผนก');
        deptModal.show();
    }

    if(document.getElementById('menu-toggle')){
        document.getElementById('menu-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sidebar-wrapper').classList.toggle('active');
        });
    }
</script>