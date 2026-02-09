<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$_POST['name']]);
    } elseif ($_POST['action'] == 'edit') {
        $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['id']]);
    }
    header("Location: categories.php"); exit();
}

if (isset($_GET['delete'])) {
    // เช็คก่อนลบว่ามี Ticket ใช้อยู่ไหม
    $chk = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE category_id = ?");
    $chk->execute([$_GET['delete']]);
    if ($chk->fetchColumn() > 0) {
        echo "<script>alert('ไม่สามารถลบได้ เนื่องจากมีงานแจ้งซ่อมในหมวดหมู่นี้'); window.location='categories.php';</script>";
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_GET['delete']]);
        header("Location: categories.php");
    }
    exit();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Settings</span>
        <span class="text-muted ms-2 small border-start ps-2">หมวดหมู่งานซ่อม (Ticket Categories)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3"> <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-tags me-2"></i>รายการหมวดหมู่</h6>
                    <button class="btn btn-sm btn-primary" onclick="openModal('add')"><i class="bi bi-plus-lg me-1"></i> เพิ่มหมวดหมู่</button>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 10%;">ID</th>
                                    <th>ชื่อหมวดหมู่</th>
                                    <th class="text-end pe-3" style="width: 15%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $row): 
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted">#<?= $row['id'] ?></td>
                                    <td class="fw-bold text-primary"><?= $row['name'] ?></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ยืนยันลบข้อมูลนี้?')"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="m_action">
                <input type="hidden" name="id" id="m_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="m_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ชื่อหมวดหมู่ *</label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm" required placeholder="เช่น Hardware, Network, Printer">
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
    var catModal;
    document.addEventListener('DOMContentLoaded', function() { 
        catModal = new bootstrap.Modal(document.getElementById('catModal')); 
    });
    
    function openModal(action, json = null) {
        document.getElementById('m_action').value = action;
        document.getElementById('m_title').innerText = (action == 'add' ? 'เพิ่มหมวดหมู่ใหม่' : 'แก้ไขหมวดหมู่');
        document.getElementById('m_id').value = '';
        document.forms[0].reset();
        
        if (json) {
            const d = JSON.parse(json);
            document.getElementById('m_id').value = d.id;
            document.getElementById('name').value = d.name;
        }
        catModal.show();
    }
    
    // Toggle Menu Script
    if(document.getElementById('menu-toggle')){
        document.getElementById('menu-toggle').addEventListener('click', e => { 
            e.preventDefault(); 
            document.getElementById('sidebar-wrapper').classList.toggle('active'); 
        });
    }
</script>