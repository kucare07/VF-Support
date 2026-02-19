<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// --- Query Data ---
// ดึงข้อมูลสินค้าทั้งหมด เรียงตามชื่อ
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
        <div class="container-fluid p-3">

            <?php if (isset($_GET['msg'])): ?>
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
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-box-seam me-2"></i>รายการวัสดุ (Consumables)</h6>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openItemModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มสินค้าใหม่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-checkbox py-3 text-center">
                                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleAll(this)">
                                    </th>
                                    <th class="text-center" style="width: 50px;">ลำดับ</th>
                                    <th class="ps-3">รูปภาพ</th>
                                    <th>ชื่อสินค้า / รหัส</th>
                                    <th class="text-center">คงเหลือ (Qty)</th>
                                    <th class="text-center">หน่วย (Unit)</th>
                                    <th>สถานะ (Status)</th>
                                    <th class="text-end pe-3">จัดการสต็อก (Stock Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                foreach ($items as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    $status_badge = '<span class="badge bg-success">ปกติ</span>';
                                    $row_class = '';
                                    if ($row['qty_on_hand'] == 0) {
                                        $status_badge = '<span class="badge bg-secondary">สินค้าหมด</span>';
                                        $row_class = 'bg-light text-muted';
                                    } elseif ($row['qty_on_hand'] <= $row['min_stock']) {
                                        $status_badge = '<span class="badge bg-danger animate__animated animate__pulse animate__infinite">ใกล้หมด!</span>';
                                    }
                                    $img_name = isset($row['image']) ? $row['image'] : null;
                                    $img_src = $img_name ? "../../uploads/inventory/" . $img_name : "https://via.placeholder.com/40?text=IMG";
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                        </td>
                                        <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                        <td class="ps-3">
                                            <img src="<?= $img_src ?>" class="rounded border shadow-sm" width="40" height="40" style="object-fit:cover;">
                                        </td>
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
                                        <td class="text-end pe-3 text-nowrap">
                                            <div class="btn-group me-1" role="group">
                                                <button class="btn btn-sm btn-outline-success bg-white shadow-sm" onclick="openStockModal(<?= $row['id'] ?>, 'in', '<?= htmlspecialchars($row['name']) ?>')" title="รับของเข้า"><i class="bi bi-plus-lg"></i></button>
                                                <button class="btn btn-sm btn-outline-danger bg-white shadow-sm" onclick="openStockModal(<?= $row['id'] ?>, 'out', '<?= htmlspecialchars($row['name']) ?>')" title="เบิกของออก" <?= $row['qty_on_hand'] == 0 ? 'disabled' : '' ?>><i class="bi bi-dash-lg"></i></button>
                                            </div>
                                            <button class="btn btn-sm btn-light border text-warning shadow-sm" onclick="openItemModal('edit', '<?= $json ?>')" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border text-secondary shadow-sm" onclick="confirmDelete('process.php?action=delete&id=<?= $row['id'] ?>')" title="ลบ"><i class="bi bi-trash"></i></button>
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
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="i_action">
                <input type="hidden" name="id" id="i_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="i_title">จัดการสินค้า</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">รหัสสินค้า (Code)</label>
                            <input type="text" name="code" id="code" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">หน่วยนับ</label>
                            <input type="text" name="unit" id="unit" class="form-control" placeholder="ชิ้น, กล่อง">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-danger">จุดเตือนสั่งซื้อ (Min)</label>
                            <input type="number" name="min_stock" id="min_stock" class="form-control" value="5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">ราคาต่อหน่วย</label>
                            <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small fw-bold">รูปภาพ</label>
                        <div class="upload-area" onclick="document.getElementById('imgInput').click()">
                            <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                            <div class="small text-muted mt-1">คลิกเพื่ออัปโหลดรูป</div>
                            <input type="file" name="image" id="imgInput" class="d-none" accept="image/*" onchange="previewInvImage(this)">
                            <img id="preview_img" class="preview-img mx-auto">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="id" id="s_id">
                <input type="hidden" name="type" id="s_type">

                <div class="modal-header py-2 text-white" id="s_header">
                    <h6 class="modal-title fw-bold" id="s_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border mb-2" id="s_name_badge"></span>
                        <h5 class="fw-bold" id="s_name"></h5>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" name="qty" class="form-control fw-bold text-center fs-2 text-primary" id="s_qty" placeholder="จำนวน" value="1" min="1" required>
                        <label for="s_qty">จำนวน (Quantity)</label>
                    </div>

                    <textarea name="note" class="form-control form-control-sm bg-light" placeholder="ระบุหมายเหตุ (เช่น เบิกให้ใคร, สั่งซื้อจากร้านไหน)..." rows="3" required></textarea>
                </div>

                <div class="modal-footer py-2 border-top bg-light justify-content-center">
                    <button type="submit" class="btn w-100 fw-bold shadow-sm" id="s_btn">ยืนยัน</button>
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

    // Preview Image
    function previewInvImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview_img').src = e.target.result;
                document.getElementById('preview_img').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Open Add/Edit Item Modal
    function openItemModal(action, json = null) {
        document.getElementById('i_action').value = action;
        document.getElementById('i_title').innerText = (action == 'add' ? 'เพิ่มสินค้าใหม่' : 'แก้ไขสินค้า');
        document.getElementById('i_id').value = '';
        document.getElementById('preview_img').style.display = 'none';
        document.forms[0].reset();

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('i_id').value = d.id;
            document.getElementById('name').value = d.name;
            document.getElementById('code').value = d.code;
            document.getElementById('unit').value = d.unit;
            document.getElementById('min_stock').value = d.min_stock;
            document.getElementById('unit_price').value = d.unit_price;

            if (d.image) {
                document.getElementById('preview_img').src = '../../uploads/inventory/' + d.image;
                document.getElementById('preview_img').style.display = 'block';
            }
        }
        itemModal.show();
    }

    // Open Stock In/Out Modal
    function openStockModal(id, type, name) {
        document.getElementById('s_id').value = id;
        document.getElementById('s_type').value = type;
        document.getElementById('s_name').innerText = name;
        document.getElementById('s_name_badge').innerText = (type === 'in' ? 'RECEIVE' : 'ISSUE');

        const header = document.getElementById('s_header');
        const btn = document.getElementById('s_btn');

        if (type === 'in') {
            header.className = 'modal-header py-2 text-white bg-success';
            document.getElementById('s_title').innerHTML = '<i class="bi bi-box-arrow-in-down me-2"></i>รับของเข้า (Stock In)';
            btn.className = 'btn btn-success hover-scale';
            btn.innerText = 'ยืนยันรับเข้า';
        } else {
            header.className = 'modal-header py-2 text-white bg-danger';
            document.getElementById('s_title').innerHTML = '<i class="bi bi-box-arrow-up me-2"></i>เบิกของออก (Stock Out)';
            btn.className = 'btn btn-danger hover-scale';
            btn.innerText = 'ยืนยันการเบิก';
        }

        document.querySelector('#stockModal textarea').value = '';
        document.getElementById('s_qty').value = 1;

        stockModal.show();
    }
</script>