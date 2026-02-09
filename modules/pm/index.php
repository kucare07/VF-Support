<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Fetch PM Plans
$sql = "SELECT p.*, a.asset_code, a.name as asset_name, l.name as location_name 
        FROM pm_plans p 
        LEFT JOIN assets a ON p.asset_id = a.id
        LEFT JOIN locations l ON a.location_id = l.id
        ORDER BY p.next_due_date ASC";
$plans = $pdo->query($sql)->fetchAll();

// Fetch Assets for Dropdown
$assets = $pdo->query("SELECT id, asset_code, name FROM assets WHERE status = 'active' ORDER BY asset_code ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Preventive Maintenance (PM)</span>
        <span class="text-muted ms-2 small border-start ps-2">แผนบำรุงรักษา</span>
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
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-calendar-check me-2"></i>รายการแผน PM</h6>
                    <button class="btn btn-sm btn-primary" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> สร้างแผนใหม่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ชื่องาน (Task Name)</th>
                                    <th>ทรัพย์สิน (Asset)</th>
                                    <th>ความถี่ (Frequency)</th>
                                    <th>ทำล่าสุดเมื่อ</th>
                                    <th>กำหนดครั้งถัดไป (Next Due)</th>
                                    <th>สถานะ (Status)</th>
                                    <th class="text-end pe-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    // Status Logic for Due Date
                                    $due_html = '-';
                                    $row_class = '';
                                    if ($row['next_due_date']) {
                                        $days_left = (strtotime($row['next_due_date']) - time()) / (60 * 60 * 24);
                                        $date_str = date('d/m/Y', strtotime($row['next_due_date']));

                                        if ($days_left < 0) {
                                            $due_html = "<span class='text-danger fw-bold'><i class='bi bi-exclamation-circle'></i> $date_str (เกินกำหนด!)</span>";
                                            $row_class = 'bg-danger bg-opacity-10';
                                        } elseif ($days_left <= 7) {
                                            $due_html = "<span class='text-warning fw-bold text-dark'>$date_str (ใกล้ถึง)</span>";
                                        } else {
                                            $due_html = "<span class='text-success'>$date_str</span>";
                                        }
                                    }
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td class="ps-3 fw-bold"><?= $row['name'] ?></td>
                                        <td>
                                            <div>[<?= $row['asset_code'] ?>] <?= $row['asset_name'] ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= $row['location_name'] ?: '-' ?></small>
                                        </td>
                                        <td>ทุกๆ <?= $row['frequency_days'] ?> วัน</td>
                                        <td><?= $row['last_done_date'] ? date('d/m/Y', strtotime($row['last_done_date'])) : '-' ?></td>
                                        <td><?= $due_html ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="process.php?action=complete&id=<?= $row['id'] ?>" class="btn btn-sm btn-success border py-0 me-1" title="บำรุงรักษาเสร็จสิ้น" onclick="return confirm('ยืนยันการบำรุงรักษาเสร็จสิ้น?\nระบบจะคำนวณวันนัดครั้งต่อไปให้อัตโนมัติ')">
                                                <i class="bi bi-check-lg"></i> ทำแล้ว
                                            </a>
                                            <button class="btn btn-sm btn-light border text-warning py-0 me-1" onclick="openModal('edit', '<?= $json ?>')"><i class="bi bi-pencil"></i></button>
                                            <a href="process.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger py-0" onclick="return confirm('ยืนยันลบแผนนี้?')"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="pmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="p_action">
                <input type="hidden" name="id" id="p_id">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold" id="p_title"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ชื่องาน (Task Name) *</label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm" placeholder="เช่น เป่าฝุ่น, ตรวจเช็คสภาพ, ต่ออายุ" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">ทรัพย์สิน (Asset) *</label>
                        <select name="asset_id" id="asset_id" class="form-select form-select-sm select2" required>
                            <option value="">-- เลือกเครื่อง --</option>
                            <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>">[<?= $a['asset_code'] ?>] <?= $a['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ความถี่ (วัน)</label>
                            <input type="number" name="frequency_days" id="frequency_days" class="form-control form-control-sm" value="30" required>
                            <small class="text-muted" style="font-size:10px;">30=1เดือน, 90=3เดือน, 365=1ปี</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">เริ่มนับวันไหน (Start Date)</label>
                            <input type="date" name="next_due_date" id="next_due_date" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small fw-bold">หมายเหตุ / วิธีการ</label>
                        <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="active" checked>
                        <label class="form-check-label small" for="status">เปิดใช้งานแผนนี้ (Active)</label>
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0"><button type="submit" class="btn btn-sm btn-primary">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
    var pmModal;
    document.addEventListener('DOMContentLoaded', function() {
        pmModal = new bootstrap.Modal(document.getElementById('pmModal'));
    });

    function openModal(action, json = null) {
        document.getElementById('p_action').value = action;
        document.getElementById('p_title').innerText = (action == 'add' ? 'สร้างแผน PM ใหม่' : 'แก้ไขแผน PM');
        document.getElementById('p_id').value = '';
        document.forms[0].reset();

        // Reset Select2
        $('#asset_id').val(null).trigger('change');

        // Default Start Date = Today
        if (action == 'add') {
            document.getElementById('next_due_date').valueAsDate = new Date();
        }

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('p_id').value = d.id;
            document.getElementById('name').value = d.name;
            $('#asset_id').val(d.asset_id).trigger('change'); // Update Select2
            document.getElementById('frequency_days').value = d.frequency_days;
            document.getElementById('next_due_date').value = d.next_due_date;
            document.getElementById('notes').value = d.notes;
            document.getElementById('status').checked = (d.status === 'active');
        }
        pmModal.show();
    }

    // ✅ Removed 'menu-toggle' event listener
</script>