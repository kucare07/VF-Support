<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';

// --- Backend Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $line = trim($_POST['line_id']);
    $addr = trim($_POST['address']);

    if ($_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, line_id, address) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $contact, $phone, $email, $line, $addr]);
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, line_id=?, address=? WHERE id=?");
        $stmt->execute([$name, $contact, $phone, $email, $line, $addr, $id]);
    }
    header("Location: suppliers.php?msg=saved"); exit();
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: suppliers.php?msg=deleted"); exit();
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Master Data</span>
        <span class="text-muted ms-2 small border-start ps-2">ผู้ขาย / ร้านค้า (Suppliers)</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3"> <?php if(isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'สำเร็จ', timer: 1000, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-shop me-2"></i>รายชื่อผู้ขาย</h6>
                    <button class="btn btn-sm btn-primary" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มร้านค้า
                    </button>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ชื่อร้านค้า / บริษัท</th>
                                    <th>ผู้ติดต่อ</th>
                                    <th>เบอร์โทร</th>
                                    <th>ช่องทางติดต่อ</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($suppliers as $row): 
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary"><?= $row['name'] ?></td>
                                    <td><?= $row['contact_person'] ?: '-' ?></td>
                                    <td><?= $row['phone'] ?: '-' ?></td>
                                    <td>
                                        <?php if($row['line_id']) echo '<div class="small text-success"><i class="bi bi-line me-1"></i>'.$row['line_id'].'</div>'; ?>
                                        <?php if($row['email']) echo '<div class="small text-muted"><i class="bi bi-envelope me-1"></i>'.$row['email'].'</div>'; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-light border text-warning py-0 me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ยืนยันลบ?')"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" id="m_action">
                <input type="hidden" name="id" id="m_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="m_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-bold">ชื่อร้านค้า *</label><input type="text" name="name" id="m_name" class="form-control form-control-sm" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small fw-bold">ผู้ติดต่อ</label><input type="text" name="contact_person" id="m_contact" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">เบอร์โทร</label><input type="text" name="phone" id="m_phone" class="form-control form-control-sm"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small fw-bold">Line ID</label><input type="text" name="line_id" id="m_line" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Email</label><input type="email" name="email" id="m_email" class="form-control form-control-sm"></div>
                    </div>
                    <div class="mb-0"><label class="form-label small fw-bold">ที่อยู่</label><textarea name="address" id="m_address" class="form-control form-control-sm" rows="2"></textarea></div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-primary w-100">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var dataModal;
    document.addEventListener('DOMContentLoaded', function() { dataModal = new bootstrap.Modal(document.getElementById('dataModal')); });
    function openModal(action, json = null) {
        document.getElementById('m_action').value = action;
        document.getElementById('m_title').innerText = (action == 'add' ? 'เพิ่มร้านค้า' : 'แก้ไขร้านค้า');
        document.getElementById('m_id').value = '';
        document.forms[0].reset();
        if (json) {
            const d = JSON.parse(json);
            document.getElementById('m_id').value = d.id;
            document.getElementById('m_name').value = d.name;
            document.getElementById('m_contact').value = d.contact_person;
            document.getElementById('m_phone').value = d.phone;
            document.getElementById('m_email').value = d.email;
            document.getElementById('m_line').value = d.line_id;
            document.getElementById('m_address').value = d.address;
        }
        dataModal.show();
    }
</script>