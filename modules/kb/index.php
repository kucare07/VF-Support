<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// รับค่าค้นหา
$search = $_GET['q'] ?? '';
$cat_id = $_GET['cat'] ?? '';

// สร้าง Query
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat_id) {
    $where[] = "category_id = ?";
    $params[] = $cat_id;
}

// ถ้าเป็น User ธรรมดา ให้เห็นเฉพาะ Public
if ($_SESSION['role'] == 'user') {
    $where[] = "is_public = 1";
}

$sql_cond = implode(" AND ", $where);
$sql = "SELECT k.*, c.name as cat_name, u.fullname as author_name 
        FROM kb_articles k 
        LEFT JOIN kb_categories c ON k.category_id = c.id 
        LEFT JOIN users u ON k.author_id = u.id 
        WHERE $sql_cond 
        ORDER BY k.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// หมวดหมู่สำหรับ Sidebar
$cats = $pdo->query("SELECT * FROM kb_categories")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold text-dark">Knowledge Base</span>
        <span class="text-muted ms-2 small border-start ps-2">ฐานความรู้ / คู่มือการแก้ปัญหา</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-4">
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <form action="" method="GET" class="mb-3">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="q" class="form-control" placeholder="ค้นหาบทความ..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                            <h6 class="fw-bold text-secondary small text-uppercase">หมวดหมู่ (Categories)</h6>
                            <div class="list-group list-group-flush small">
                                <a href="index.php" class="list-group-item list-group-item-action <?= $cat_id==''?'active':'' ?>">ทั้งหมด (All)</a>
                                <?php foreach($cats as $c): ?>
                                    <a href="index.php?cat=<?= $c['id'] ?>" class="list-group-item list-group-item-action <?= $cat_id==$c['id']?'active':'' ?>">
                                        <i class="bi bi-folder2 me-2"></i><?= $c['name'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($_SESSION['role'] != 'user'): ?>
                    <div class="d-grid">
                        <button class="btn btn-primary shadow-sm" onclick="openEditor('add')">
                            <i class="bi bi-plus-lg me-2"></i> เขียนบทความใหม่
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-9">
                    <?php if(isset($_GET['msg'])): ?>
                        <div class="alert alert-success py-2 small mb-3">ดำเนินการสำเร็จ!</div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if(count($articles) > 0): ?>
                            <?php foreach($articles as $row): 
                                $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm hover-shadow h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title fw-bold text-primary mb-1">
                                                <a href="#" class="text-decoration-none" onclick="openView('<?= $json ?>')"><?= $row['title'] ?></a>
                                            </h5>
                                            <?php if($_SESSION['role'] != 'user'): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm p-0 border-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end small">
                                                        <li><a class="dropdown-item" href="#" onclick="openEditor('edit', '<?= $json ?>')">แก้ไข</a></li>
                                                        <li><a class="dropdown-item text-danger" href="process.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('ยืนยันลบ?')">ลบ</a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-muted small mb-2">
                                            <span class="badge bg-light text-dark border me-2"><i class="bi bi-folder2-open"></i> <?= $row['cat_name'] ?></span>
                                            <span class="me-2"><i class="bi bi-person"></i> <?= $row['author_name'] ?></span>
                                            <span class="me-2"><i class="bi bi-clock"></i> <?= date('d/m/Y', strtotime($row['updated_at'])) ?></span>
                                            <span><i class="bi bi-eye"></i> <?= $row['views'] ?></span>
                                            <?php if($row['is_public']==0): ?><span class="badge bg-warning text-dark ms-2">ภายในเท่านั้น</span><?php endif; ?>
                                        </div>
                                        
                                        <p class="card-text text-secondary small text-truncate">
                                            <?= mb_strimwidth(strip_tags($row['content']), 0, 150, '...') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                                ไม่พบบทความตามเงื่อนไข
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editorModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="kb_action">
                <input type="hidden" name="id" id="kb_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="editorTitle">เขียนบทความ</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input type="text" name="title" id="title" class="form-control" placeholder="หัวข้อบทความ (Topic)" required>
                        </div>
                        <div class="col-md-4">
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <textarea id="summernote" name="content"></textarea>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_public" id="is_public" value="1" checked>
                        <label class="form-check-label" for="is_public">เผยแพร่สาธารณะ (Public) - ผู้ใช้งานทั่วไปสามารถเห็นได้</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">บันทึกบทความ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary w-100" id="v_title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="text-muted small border-bottom pb-2 mb-3">
                    <i class="bi bi-folder2-open me-1"></i> <span id="v_cat"></span> &bull; 
                    <i class="bi bi-clock me-1"></i> <span id="v_date"></span> &bull; 
                    <i class="bi bi-person me-1"></i> <span id="v_author"></span>
                </div>
                <div id="v_content" class="article-content"></div>
            </div>
            <div class="modal-footer py-1 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var editorModal, viewModal;

    $(document).ready(function() {
        // Init Summernote
        $('#summernote').summernote({
            placeholder: 'เขียนรายละเอียดขั้นตอนการแก้ปัญหา...',
            tabsize: 2,
            height: 400,
            toolbar: [
                ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'table', 'hr']], // เพิ่มปุ่มแทรกรูปและตาราง
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        // Init Modals
        editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
        viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    });

    function openEditor(action, json = null) {
        document.getElementById('kb_action').value = action;
        document.getElementById('editorTitle').innerText = (action == 'add' ? 'เขียนบทความใหม่' : 'แก้ไขบทความ');
        
        // Reset Form
        document.getElementById('kb_id').value = '';
        document.getElementById('title').value = '';
        document.getElementById('category_id').value = '';
        $('#summernote').summernote('code', ''); // Reset Editor
        document.getElementById('is_public').checked = true;

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('kb_id').value = d.id;
            document.getElementById('title').value = d.title;
            document.getElementById('category_id').value = d.category_id;
            $('#summernote').summernote('code', d.content); // Set Content
            document.getElementById('is_public').checked = (d.is_public == 1);
        }
        editorModal.show();
    }

    function openView(json) {
        const d = JSON.parse(json);
        document.getElementById('v_title').innerText = d.title;
        document.getElementById('v_cat').innerText = d.cat_name;
        document.getElementById('v_date').innerText = d.updated_at;
        document.getElementById('v_author').innerText = d.author_name;
        document.getElementById('v_content').innerHTML = d.content; // Render HTML
        
        // นับยอดวิว (ส่ง Ajax ไปอัปเดตเงียบๆ)
        fetch('process.php?action=count_view&id=' + d.id);

        viewModal.show();
    }

    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>