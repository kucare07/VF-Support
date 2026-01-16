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
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: departments.php");
    exit();
}

// ดึงข้อมูลแผนกทั้งหมด
$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">จัดการแผนก (Departments)</span>
        </nav>

        <div class="container-fluid p-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="index.php" class="btn btn-light border"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="setModal('add')">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มแผนกใหม่
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>รหัสแผนก (Code)</th>
                                    <th>ชื่อแผนก (Name)</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($departments as $d): ?>
                                <tr>
                                    <td><?= $d['id'] ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $d['code'] ?></span></td>
                                    <td class="fw-bold"><?= $d['name'] ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                onclick="setModal('edit', '<?= $d['id'] ?>', '<?= $d['name'] ?>', '<?= $d['code'] ?>')"
                                                data-bs-toggle="modal" data-bs-target="#deptModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบ?');">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="modalId">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">เพิ่มแผนกใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>ชื่อแผนก <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="deptName" class="form-control" required placeholder="เช่น ฝ่ายบุคคล">
                    </div>
                    <div class="mb-3">
                        <label>รหัสแผนก (ตัวย่อ)</label>
                        <input type="text" name="code" id="deptCode" class="form-control" placeholder="เช่น HR">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script จัดการ Modal ให้ใช้ได้ทั้ง Add และ Edit
    function setModal(action, id = '', name = '', code = '') {
        document.getElementById('modalAction').value = action;
        document.getElementById('modalId').value = id;
        document.getElementById('deptName').value = name;
        document.getElementById('deptCode').value = code;
        
        if(action == 'add') {
            document.getElementById('modalTitle').innerText = 'เพิ่มแผนกใหม่';
        } else {
            document.getElementById('modalTitle').innerText = 'แก้ไขแผนก';
        }
    }

    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>