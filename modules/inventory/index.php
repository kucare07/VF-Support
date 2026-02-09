<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// Fetch Inventory Items
$sql = "SELECT * FROM inventory_items ORDER BY name ASC";
$items = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Inventory Management</span>
        <span class="text-muted ms-2 small border-start ps-2">คลังวัสดุสิ้นเปลือง</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3"> <?php if (isset($_GET['msg'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                </script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-box-seam me-2"></i>รายการวัสดุ (Consumables)</h6>
                    <button class="btn btn-sm btn-primary" onclick="openItemModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มสินค้าใหม่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">รูปภาพ</th>
                                    <th>ชื่อสินค้า / รหัส</th>
                                    <th class="text-center">คงเหลือ (Qty)</th>
                                    <th class="text-center">หน่วย (Unit)</th>
                                    <th>สถานะ (Status)</th>
                                    <th class="text-end pe-3">จัดการสต็อก (Stock Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    // Status Logic
                                    $status_badge = '<span class="badge bg-success">ปกติ</span>';
                                    $row_class = '';
                                    if ($row['qty_on_hand'] == 0) {
                                        $status_badge = '<span class="badge bg-secondary">สินค้าหมด</span>';
                                        $row_class = 'opacity-75 bg-light';
                                    } elseif ($row['qty_on_hand'] <= $row['min_stock']) {
                                        $status_badge = '<span class="badge bg-danger">ใกล้หมด!</span>';
                                    }

                                    $img_name = isset($row['image']) ? $row['image'] : null;
                                    $img_src = $img_name ? "../../uploads/inventory/" . $img_name : "https://via.placeholder.com/40?text=IMG";
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td class="ps-3"><img src="<?= $img_src ?>" class="rounded border" width="40" height="40" style="object-fit:cover;"></td>
                                        <td>
                                            <div class="fw-bold"><?= $row['name'] ?></div>
                                            <small class="text-muted font-monospace"><?= $row['code'] ?: '-' ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="fs-6 fw-bold <?= ($row['qty_on_hand'] <= $row['min_stock']) ? 'text-danger' : 'text-dark' ?>">
                                                <?= number_format($row['qty_on_hand']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center text-muted"><?= $row['unit'] ?></td>
                                        <td><?= $status_badge ?></td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-success bg-white shadow-sm" onclick="openStockModal(<?= $row['id'] ?>, 'in', '<?= htmlspecialchars($row['name']) ?>')"><i class="bi bi-plus-lg"></i></button>
                                                <button class="btn btn-sm btn-outline-danger bg-white shadow-sm" onclick="openStockModal(<?= $row['id'] ?>, 'out', '<?= htmlspecialchars($row['name']) ?>')"><i class="bi bi-dash-lg"></i></button>
                                            </div>

                                            <button class="btn btn-sm btn-light border text-warning ms-1 shadow-sm" onclick="openItemModal('edit', '<?= $json ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button class="btn btn-sm btn-light border text-secondary ms-1 shadow-sm" onclick="confirmDelete('process.php?action=delete&id=<?= $row['id'] ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="i_action">
                <input type="hidden" name="id" id="i_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="i_title"></h6><button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-bold">ชื่อสินค้า *</label><input type="text" name="name" id="name" class="form-control form-control-sm" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small fw-bold">รหัสสินค้า (Code)</label><input type="text" name="code" id="code" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">หน่วยนับ</label><input type="text" name="unit" id="unit" class="form-control form-control-sm" placeholder="ชิ้น, กล่อง, อัน"></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6"><label class="form-label small fw-bold">จุดเตือนสั่งซื้อ (Min Stock)</label><input type="number" name="min_stock" id="min_stock" class="form-control form-control-sm" value="5"></div>
                        <div class="col-6"><label class="form-label small fw-bold">ราคาต่อหน่วย</label><input type="number" name="unit_price" id="unit_price" class="form-control form-control-sm" step="0.01"></div>
                    </div>
                    <div class="mt-2"><label class="form-label small fw-bold">รูปภาพ</label><input type="file" name="image" class="form-control form-control-sm"></div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-primary">บันทึกข้อมูล</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="id" id="s_id">
                <input type="hidden" name="type" id="s_type">

                <div class="modal-header py-2 text-white" id="s_header">
                    <h6 class="modal-title fw-bold" id="s_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h5 class="fw-bold mb-3" id="s_name"></h5>
                    <div class="form-floating mb-2">
                        <input type="number" name="qty" class="form-control fw-bold text-center fs-4" id="s_qty" placeholder="จำนวน" value="1" min="1" required>
                        <label for="s_qty">จำนวน (Quantity)</label>
                    </div>
                    <textarea name="note" class="form-control form-control-sm" placeholder="หมายเหตุ (เช่น เบิกให้ใคร, ซื้อเพิ่มจากร้านไหน)" rows="2" required></textarea>
                </div>
                <div class="modal-footer py-1 border-top-0">
                    <button type="submit" class="btn btn-sm w-100 fw-bold" id="s_btn">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var itemModal, stockModal;
    document.addEventListener('DOMContentLoaded', function() {
        itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        stockModal = new bootstrap.Modal(document.getElementById('stockModal'));
    });

    function openItemModal(action, json = null) {
        document.getElementById('i_action').value = action;
        document.getElementById('i_title').innerText = (action == 'add' ? 'เพิ่มสินค้าใหม่' : 'แก้ไขสินค้า');
        document.getElementById('i_id').value = '';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('i_id').value = d.id;
            document.getElementById('name').value = d.name;
            document.getElementById('code').value = d.code;
            document.getElementById('unit').value = d.unit;
            document.getElementById('min_stock').value = d.min_stock;
            document.getElementById('unit_price').value = d.unit_price;
        }
        itemModal.show();
    }

    function openStockModal(id, type, name) {
        document.getElementById('s_id').value = id;
        document.getElementById('s_type').value = type;
        document.getElementById('s_name').innerText = name;

        const header = document.getElementById('s_header');
        const btn = document.getElementById('s_btn');

        if (type === 'in') {
            header.className = 'modal-header py-2 text-white bg-success';
            document.getElementById('s_title').innerText = 'รับของเข้า (Stock In)';
            btn.className = 'btn btn-sm w-100 fw-bold btn-success';
            btn.innerText = 'ยืนยันรับเข้า';
        } else {
            header.className = 'modal-header py-2 text-white bg-danger';
            document.getElementById('s_title').innerText = 'เบิกของออก (Stock Out)';
            btn.className = 'btn btn-sm w-100 fw-bold btn-danger';
            btn.innerText = 'ยืนยันการเบิก';
        }
        document.querySelector('#stockModal textarea').value = '';
        document.getElementById('s_qty').value = 1;

        stockModal.show();
    }

    // ✅ Removed 'menu-toggle' event listener
</script>