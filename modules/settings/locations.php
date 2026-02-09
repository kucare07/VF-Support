<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    if ($_POST['action'] == 'add') {
        $pdo->prepare("INSERT INTO locations (name) VALUES (?)")->execute([$name]);
    } else {
        $pdo->prepare("UPDATE locations SET name = ? WHERE id = ?")->execute([$name, $_POST['id']]);
    }
    header("Location: locations.php"); exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    // Optional: Check constraint (e.g. assets using this location)
    $chk = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE location_id = ?");
    $chk->execute([$_GET['delete']]);
    if ($chk->fetchColumn() > 0) {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากมีทรัพย์สินอยู่ในสถานที่นี้'); window.location='locations.php';</script>";
    } else {
        $pdo->prepare("DELETE FROM locations WHERE id = ?")->execute([$_GET['delete']]);
        header("Location: locations.php");
    }
    exit();
}

$items = $pdo->query("SELECT * FROM locations ORDER BY id ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">จัดการสถานที่ (Locations)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-geo-alt me-2"></i>รายชื่อสถานที่</h6>
                    <button class="btn btn-sm btn-primary" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มสถานที่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 10%;">ID</th>
                                    <th>ชื่อสถานที่ / อาคาร / ห้อง</th>
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
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ยืนยันลบ?')">
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

<div class="modal fade" id="mainModal" tabindex="-1">
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
                        <label class="form-label small fw-bold">ชื่อสถานที่ *</label>
                        <input type="text" name="name" id="modalName" class="form-control form-control-sm" required placeholder="เช่น อาคาร A ชั้น 2">
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
    var mainModal;
    document.addEventListener('DOMContentLoaded', function() {
        mainModal = new bootstrap.Modal(document.getElementById('mainModal'));
    });

    function openModal(action, id = '', name = '') {
        document.getElementById('modalAction').value = action;
        document.getElementById('modalId').value = id;
        document.getElementById('modalName').value = name;
        
        document.getElementById('modalTitle').innerText = (action == 'add' ? 'เพิ่มสถานที่ใหม่' : 'แก้ไขสถานที่');
        mainModal.show();
    }
    
    if(document.getElementById('menu-toggle')){
        document.getElementById('menu-toggle').addEventListener('click', (e) => { 
            e.preventDefault(); 
            document.getElementById('sidebar-wrapper').classList.toggle('active'); 
        });
    }
</script>
