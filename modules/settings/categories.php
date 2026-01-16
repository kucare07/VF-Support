<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    if ($_POST['action'] == 'add') {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    } else {
        $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $_POST['id']]);
    }
    header("Location: categories.php"); exit();
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: categories.php"); exit();
}

$items = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-3 fw-bold text-secondary">หมวดหมู่ปัญหา (Ticket Categories)</span>
        </nav>
        <div class="container-fluid p-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="index.php" class="btn btn-light border"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                        <button class="btn btn-danger" onclick="openModal('add')"><i class="bi bi-plus-lg me-1"></i> เพิ่มหมวดหมู่</button>
                    </div>
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>ID</th><th>ชื่อหมวดหมู่</th><th class="text-end">จัดการ</th></tr></thead>
                        <tbody>
                            <?php foreach($items as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td class="fw-bold text-danger"><?= $row['name'] ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="openModal('edit', '<?= $row['id'] ?>', '<?= $row['name'] ?>')"><i class="bi bi-pencil"></i></button>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันลบ?')"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="mainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="id" id="modalId">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title" id="modalTitle"></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label>ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="modalName" class="form-control" required placeholder="เช่น Hardware, Network, Software">
                </div>
                <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-danger">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(action, id = '', name = '') {
        document.getElementById('modalAction').value = action;
        document.getElementById('modalId').value = id;
        document.getElementById('modalName').value = name;
        document.getElementById('modalTitle').innerText = (action == 'add' ? 'เพิ่มหมวดหมู่ใหม่' : 'แก้ไขหมวดหมู่');
        new bootstrap.Modal(document.getElementById('mainModal')).show();
    }
    document.getElementById('menu-toggle').addEventListener('click', (e) => { e.preventDefault(); document.getElementById('sidebar-wrapper').classList.toggle('active'); });
</script>
<?php require_once '../../includes/footer.php'; ?>