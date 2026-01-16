<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

$articles = $pdo->query("SELECT k.*, c.name as cat_name FROM kb_articles k LEFT JOIN categories c ON k.category_id = c.id ORDER BY id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 shadow-sm">
            <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-1 fw-bold text-secondary">ฐานความรู้ (KB)</span>
        </nav>

        <div class="container-fluid p-4">
             <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'เรียบร้อย!', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="d-flex justify-content-between mb-4">
                <h5 class="fw-bold text-primary">บทความทั้งหมด</h5>
                <button class="btn btn-primary" onclick="openForm('add')"><i class="bi bi-plus-lg me-1"></i> เขียนบทความ</button>
            </div>

            <div class="row g-4">
                <?php foreach($articles as $row): 
                     $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <span class="badge bg-light text-dark mb-2"><?= $row['cat_name'] ?></span>
                            <h6 class="fw-bold text-truncate"><?= $row['title'] ?></h6>
                            <p class="small text-muted text-truncate"><?= strip_tags($row['content']) ?></p>
                            
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button class="btn btn-sm btn-light border" onclick="openView('<?= $jsonData ?>')"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-sm btn-light border text-warning" onclick="openForm('edit', '<?= $jsonData ?>')"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-light border text-danger" onclick="confirmDel(<?= $row['id'] ?>)"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="action">
                <input type="hidden" name="id" id="kbId">
                <div class="modal-header"><h5 class="modal-title" id="modalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label>หัวข้อ</label><input type="text" name="title" id="title" class="form-control" required></div>
                    <div class="mb-3">
                        <label>หมวดหมู่</label>
                        <select name="category_id" id="cat_id" class="form-select">
                            <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label>เนื้อหา</label><textarea name="content" id="content" class="form-control" rows="6"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold" id="v_title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4" id="v_content"></div>
        </div>
    </div>
</div>

<script>
    var formModal = new bootstrap.Modal(document.getElementById('formModal'));
    var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

    function openForm(action, jsonData = null) {
        document.getElementById('action').value = action;
        document.getElementById('modalTitle').innerText = (action == 'add' ? 'เขียนบทความ' : 'แก้ไขบทความ');
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        
        if (jsonData) {
            const data = JSON.parse(jsonData);
            document.getElementById('kbId').value = data.id;
            document.getElementById('title').value = data.title;
            document.getElementById('content').value = data.content;
            document.getElementById('cat_id').value = data.category_id;
        }
        formModal.show();
    }

    function openView(jsonData) {
        const data = JSON.parse(jsonData);
        document.getElementById('v_title').innerText = data.title;
        // แปลง \n เป็น <br> เพื่อแสดงผลเว้นบรรทัด
        document.getElementById('v_content').innerHTML = data.content.replace(/\n/g, "<br>");
        viewModal.show();
    }

    function confirmDel(id) {
        Swal.fire({title: 'ลบหรือไม่?', icon: 'warning', showCancelButton: true, confirmButtonText: 'ลบ', confirmButtonColor: '#d33'})
        .then((result) => { if(result.isConfirmed) window.location.href = `process.php?action=delete&id=${id}`; });
    }
</script>
<?php require_once '../../includes/footer.php'; ?>