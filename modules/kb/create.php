<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// เฉพาะ IT/Admin เท่านั้นที่เขียนได้
if ($_SESSION['role'] == 'user') {
    header("Location: index.php");
    exit();
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $created_by = $_SESSION['user_id'];

    $sql = "INSERT INTO kb_articles (title, content, category_id, created_by) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $content, $category_id, $created_by]);

    header("Location: index.php");
}
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm" title="ย้อนกลับ">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-1 fw-bold text-secondary">เขียนบทความใหม่</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">หัวข้อบทความ</label>
                                    <input type="text" name="title" class="form-control" required placeholder="เช่น วิธีแก้ไขเมื่อปริ้นไม่ออก">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">หมวดหมู่</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">-- เลือก --</option>
                                            <?php foreach ($categories as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">เนื้อหาบทความ</label>
                                    <textarea name="content" class="form-control" rows="10" required placeholder="พิมพ์รายละเอียดที่นี่..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-light">ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary px-4">บันทึกบทความ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>