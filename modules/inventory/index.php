<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

// ดึงข้อมูลสินค้า
$items = $pdo->query("SELECT * FROM inventory_items ORDER BY name ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 shadow-sm">
            <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-1 fw-bold text-secondary">คลังวัสดุ (Inventory)</span>
        </nav>

        <div class="container-fluid p-4">
            <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'เรียบร้อย!', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-primary"><i class="bi bi-box-seam me-2"></i>รายการวัสดุอุปกรณ์</h5>
                        <button class="btn btn-primary" onclick="openForm('add')"><i class="bi bi-plus-lg me-1"></i> เพิ่มสินค้าใหม่</button>
                    </div>

                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อสินค้า</th>
                                <th class="text-center">คงเหลือ</th>
                                <th class="text-center">หน่วย</th>
                                <th>สถานะ</th>
                                <th class="text-end">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $row): 
                                $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                $statusClass = ($row['qty'] <= $row['min_level']) ? 'bg-danger text-white' : 'bg-success bg-opacity-10 text-success';
                                $statusText = ($row['qty'] <= $row['min_level']) ? 'ของใกล้หมด' : 'ปกติ';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= $row['name'] ?></div>
                                    <small class="text-muted"><?= $row['description'] ?></small>
                                </td>
                                <td class="text-center fw-bold fs-5"><?= $row['qty'] ?></td>
                                <td class="text-center text-muted"><?= $row['unit'] ?></td>
                                <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light text-info border me-1" onclick="openView('<?= $jsonData ?>')"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-light text-warning border me-1" onclick="openForm('edit', '<?= $jsonData ?>')"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-light text-danger border" onclick="confirmDel(<?= $row['id'] ?>)"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="action">
                <input type="hidden" name="id" id="itemId">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle"></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label>ชื่อสินค้า</label><input type="text" name="name" id="name" class="form-control" required></div>
                    <div class="mb-3"><label>รายละเอียด</label><textarea name="description" id="desc" class="form-control"></textarea></div>
                    <div class="row">
                        <div class="col-4"><label>จำนวน</label><input type="number" name="qty" id="qty" class="form-control" required></div>
                        <div class="col-4"><label>แจ้งเตือนเมื่อต่ำกว่า</label><input type="number" name="min_level" id="min" class="form-control" value="5"></div>
                        <div class="col-4"><label>หน่วยนับ</label><input type="text" name="unit" id="unit" class="form-control" placeholder="อัน/กล่อง"></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-primary">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-1" id="v_name"></h5>
                <p class="text-muted small mb-3" id="v_desc"></p>
                <div class="display-4 fw-bold text-primary mb-0" id="v_qty"></div>
                <small class="text-muted" id="v_unit"></small>
            </div>
        </div>
    </div>
</div>

<script>
    var formModal = new bootstrap.Modal(document.getElementById('formModal'));
    var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

    function openForm(action, jsonData = null) {
        document.getElementById('action').value = action;
        document.getElementById('modalTitle').innerText = (action == 'add' ? 'เพิ่มสินค้า' : 'แก้ไขสินค้า');
        
        // Reset Form
        document.querySelectorAll('#formModal input, #formModal textarea').forEach(i => { if(i.type!='hidden') i.value = ''; });

        if (jsonData) {
            const data = JSON.parse(jsonData);
            document.getElementById('itemId').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('desc').value = data.description;
            document.getElementById('qty').value = data.qty;
            document.getElementById('min').value = data.min_level;
            document.getElementById('unit').value = data.unit;
        }
        formModal.show();
    }

    function openView(jsonData) {
        const data = JSON.parse(jsonData);
        document.getElementById('v_name').innerText = data.name;
        document.getElementById('v_desc').innerText = data.description;
        document.getElementById('v_qty').innerText = data.qty;
        document.getElementById('v_unit').innerText = data.unit;
        viewModal.show();
    }

    function confirmDel(id) {
        Swal.fire({
            title: 'ยืนยันลบ?', icon: 'warning', showCancelButton: true, confirmButtonText: 'ลบ', confirmButtonColor: '#d33'
        }).then((result) => { if(result.isConfirmed) window.location.href = `process.php?action=delete&id=${id}`; });
    }
</script>
<?php require_once '../../includes/footer.php'; ?>