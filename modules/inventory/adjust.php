<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงรายการสินค้า
$items = $pdo->query("SELECT * FROM inventory_items ORDER BY name ASC")->fetchAll();

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST['item_id'];
    $type = $_POST['type']; // 'in' or 'out'
    $qty = intval($_POST['quantity']);
    $note = $_POST['note'];
    $user_id = $_SESSION['user_id'];

    if ($qty > 0 && $item_id) {
        try {
            $pdo->beginTransaction();

            // 1. บันทึก Transaction Log
            $stmt = $pdo->prepare("INSERT INTO inventory_transactions (item_id, user_id, type, quantity, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item_id, $user_id, $type, $qty, $note]);

            // 2. อัปเดตยอดคงเหลือใน Master Table
            if ($type == 'in') {
                $update = $pdo->prepare("UPDATE inventory_items SET qty_on_hand = qty_on_hand + ? WHERE id = ?");
            } else {
                // เช็คก่อนว่าของพอให้ตัดไหม
                $check = $pdo->prepare("SELECT qty_on_hand FROM inventory_items WHERE id = ?");
                $check->execute([$item_id]);
                $current = $check->fetchColumn();

                if ($current < $qty) {
                    throw new Exception("ยอดคงเหลือไม่พอให้เบิก (เหลือ $current)");
                }
                $update = $pdo->prepare("UPDATE inventory_items SET qty_on_hand = qty_on_hand - ? WHERE id = ?");
            }
            $update->execute([$qty, $item_id]);

            $pdo->commit();
            echo "<script>alert('บันทึกรายการสำเร็จ!'); window.location='index.php';</script>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
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
                <span class="ms-1 fw-bold text-secondary">ทำรายการสต็อก (Stock Adjustment)</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 fw-bold border-bottom-0">
                            <i class="bi bi-arrow-left-right me-2"></i> แบบฟอร์ม รับเข้า / เบิกออก
                        </div>
                        <div class="card-body p-4">

                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-4 text-center">
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="type" id="typeIn" value="in" autocomplete="off">
                                        <label class="btn btn-outline-success py-3" for="typeIn"><i class="bi bi-arrow-down-circle fs-4 d-block"></i> รับของเข้า (Stock In)</label>

                                        <input type="radio" class="btn-check" name="type" id="typeOut" value="out" autocomplete="off" checked>
                                        <label class="btn btn-outline-danger py-3" for="typeOut"><i class="bi bi-arrow-up-circle fs-4 d-block"></i> เบิกของออก (Stock Out)</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">เลือกสินค้า</label>
                                    <select name="item_id" class="form-select form-select-lg" required>
                                        <option value="">-- กรุณาเลือก --</option>
                                        <?php foreach ($items as $i): ?>
                                            <option value="<?= $i['id'] ?>">
                                                <?= $i['name'] ?> (คงเหลือ: <?= $i['qty_on_hand'] ?> <?= $i['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">จำนวน</label>
                                    <input type="number" name="quantity" class="form-control form-control-lg" min="1" required placeholder="0">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-muted small">หมายเหตุ / อ้างอิง (เช่น เบิกให้ฝ่ายบุคคล, เลขที่ PO)</label>
                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">บันทึกรายการ</button>
                                    <a href="index.php" class="btn btn-light text-muted">ยกเลิก</a>
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