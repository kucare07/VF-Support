<?php
require_once '../../includes/auth.php';
requireAdmin(); // เฉพาะ Admin
require_once '../../config/db_connect.php';

// --- จัดการข้อมูล (Backend) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $name = trim($_POST['name']);
    
    if ($action == 'add' && !empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO asset_types (name) VALUES (?)");
        $stmt->execute([$name]);
    } elseif ($action == 'edit' && !empty($name)) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE asset_types SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }
    header("Location: asset_types.php"); // Redirect ล้างค่า POST
    exit();
}

if (isset($_GET['delete'])) {
    // เช็คก่อนลบว่ามีการใช้งานอยู่ไหม
    $chk = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_type_id = ?");
    $chk->execute([$_GET['delete']]);
    if ($chk->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM asset_types WHERE id = ?")->execute([$_GET['delete']]);
    } else {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากมีการใช้งานอยู่'); window.location='asset_types.php';</script>";
        exit();
    }
    header("Location: asset_types.php");
    exit();
}

// ดึงข้อมูล
$items = $pdo->query("SELECT * FROM asset_types ORDER BY name ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">ประเภททรัพย์สิน (Asset Types)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-laptop me-2"></i>รายการประเภททรัพย์สิน</h6>
                    <button class="btn btn-sm btn-primary" onclick="openModal('add')"><i class="bi bi-plus-lg me-1"></i> เพิ่มประเภท</button>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 10%;">ID</th>
                                    <th>ชื่อประเภททรัพย์สิน</th>
                                    <th class="text-end pe-3" style="width: 15%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $row): ?>
                                <tr>
                                    <td class="ps-3 text-muted">#<?= $row['id'] ?></td>
                                    <td class="fw-bold text-primary"><?= $row['name'] ?></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1" 
                                            onclick="openModal('edit', '<?= $row['id'] ?>', '<?= htmlspecialchars($row['name']) ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" 
                                           onclick="return confirm('ยืนยันการลบข้อมูลนี้?')">
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
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-sm"> <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="m_action">
                <input type="hidden" name="id" id="m_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="m_title">จัดการข้อมูล</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ชื่อประเภททรัพย์สิน *</label>
                        <input type="text" name="name" id="m_name" class="form-control form-control-sm" required placeholder="เช่น Notebook, PC">
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
    var dataModal;
    document.addEventListener('DOMContentLoaded', function() {
        dataModal = new bootstrap.Modal(document.getElementById('dataModal'));
    });

    function openModal(action, id = '', name = '') {
        document.getElementById('m_action').value = action;
        document.getElementById('m_id').value = id;
        document.getElementById('m_name').value = name;
        
        // เปลี่ยนชื่อหัว Modal
        document.getElementById('m_title').innerText = (action == 'add' ? 'เพิ่มประเภทใหม่' : 'แก้ไขประเภท');
        
        dataModal.show();
    }
    
    // Toggle Menu Script (ถ้ามีใน footer แล้ว บรรทัดนี้ไม่ต้องใส่ก็ได้ แต่ใส่กันเหนียวไว้ก่อน)
    if(document.getElementById('menu-toggle')){
        document.getElementById('menu-toggle').addEventListener('click', e => {
            e.preventDefault();
            document.getElementById('sidebar-wrapper').classList.toggle('active');
        });
    }
</script>