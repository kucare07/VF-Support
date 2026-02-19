<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// --- Query Data ---
// ดึงข้อมูลแผน PM ทั้งหมด
$sql = "SELECT p.*, a.asset_code, a.name as asset_name, l.name as location_name 
        FROM pm_plans p 
        LEFT JOIN assets a ON p.asset_id = a.id
        LEFT JOIN locations l ON a.location_id = l.id
        ORDER BY p.next_due_date ASC";
$plans = $pdo->query($sql)->fetchAll();

// ดึงข้อมูลทรัพย์สินสำหรับ Dropdown (เฉพาะที่ Active)
$assets = $pdo->query("SELECT id, asset_code, name FROM assets WHERE status = 'active' ORDER BY asset_code ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Preventive Maintenance (PM)</span>
        <span class="text-muted ms-2 small border-start ps-2">แผนบำรุงรักษา</span>
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
                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <ul class="nav nav-pills card-header-pills" id="pmTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active btn-sm" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button">
                                    <i class="bi bi-list-ul me-1"></i> รายการ (List)
                                </button>
                            </li>
                            <li class="nav-item ms-1">
                                <button class="nav-link btn-sm" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendarView" type="button">
                                    <i class="bi bi-calendar-date me-1"></i> ปฏิทิน (Calendar)
                                </button>
                            </li>
                        </ul>

                        <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn ms-2" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                            <i class="bi bi-trash"></i> ลบที่เลือก
                        </button>
                    </div>

                    <button class="btn btn-sm btn-primary shadow-sm hover-scale" onclick="openModal('add')">
                        <i class="bi bi-plus-lg me-1"></i> สร้างแผนใหม่
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="listView">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0 datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="w-checkbox py-3 text-center">
                                                <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleAll(this)">
                                            </th>
                                            <th class="text-center" style="width: 50px;">ลำดับ</th>
                                            <th class="ps-3">ชื่องาน (Task)</th>
                                            <th>ทรัพย์สิน (Asset)</th>
                                            <th>ความถี่</th>
                                            <th>ทำล่าสุด</th>
                                            <th>กำหนดครั้งถัดไป (Due Date)</th>
                                            <th>สถานะ</th>
                                            <th class="text-end pe-3">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        foreach ($plans as $row):
                                            $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                            $due_html = '-';
                                            $row_class = '';

                                            if ($row['next_due_date']) {
                                                $days_left = (strtotime($row['next_due_date']) - time()) / (60 * 60 * 24);
                                                $date_str = date('d/m/Y', strtotime($row['next_due_date']));

                                                if ($days_left < 0) {
                                                    $due_html = "<span class='text-danger fw-bold'><i class='bi bi-exclamation-circle-fill'></i> $date_str</span>";
                                                    $row_class = 'bg-danger bg-opacity-10';
                                                } elseif ($days_left <= 7) {
                                                    $due_html = "<span class='text-warning fw-bold text-dark'>$date_str</span>";
                                                } else {
                                                    $due_html = "<span class='text-success'>$date_str</span>";
                                                }
                                            }
                                        ?>
                                            <tr class="<?= $row_class ?>">
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                                </td>
                                                <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>

                                                <td class="ps-3 fw-bold text-primary"><?= $row['name'] ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark">[<?= $row['asset_code'] ?>]</div>
                                                    <small class="text-muted"><?= $row['asset_name'] ?></small>
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
                                                <td class="text-end pe-3 text-nowrap">
                                                    <button class="btn btn-sm btn-light border text-info py-0 me-1 shadow-sm" onclick="openView('<?= $json ?>')" title="ดูรายละเอียด"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-light border text-warning py-0 me-1 shadow-sm" onclick="openModal('edit', '<?= $json ?>')" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-light border text-danger py-0 shadow-sm" onclick="confirmDelete('process.php?action=delete&id=<?= $row['id'] ?>', 'ยืนยันลบแผนงานนี้?')" title="ลบ"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="calendarView">
                            <div class="p-3">
                                <div id="calendar"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" id="p_action">
                <input type="hidden" name="id" id="p_id">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="modal-title fw-bold m-0" id="p_title">สร้างแผน PM ใหม่</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่องาน (Task Name) <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="เช่น เป่าฝุ่น, ตรวจเช็คสภาพ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">ทรัพย์สิน (Asset) <span class="text-danger">*</span></label>
                        <select name="asset_id" id="asset_id" class="form-select select2" required>
                            <option value="">-- เลือกทรัพย์สิน --</option>
                            <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>">[<?= $a['asset_code'] ?>] <?= $a['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ความถี่ (วัน)</label>
                            <input type="number" name="frequency_days" id="frequency_days" class="form-control" value="30" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">วันเริ่ม/นัดครั้งต่อไป</label>
                            <input type="date" name="next_due_date" id="next_due_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">หมายเหตุ / วิธีการ</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="รายละเอียดการทำงาน..."></textarea>
                    </div>

                    <div class="form-check bg-light p-2 rounded border">
                        <input class="form-check-input ms-1" type="checkbox" name="status" id="status" value="active" checked>
                        <label class="form-check-label small fw-bold ms-2" for="status">เปิดใช้งานแผนนี้ (Active)</label>
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

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">

            <div class="header-gradient">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold m-0"><i class="bi bi-clipboard-check me-2"></i>รายละเอียดแผน PM</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1" id="v_name"></h5>
                    <span class="badge bg-secondary" id="v_status"></span>
                </div>

                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="fw-bold text-muted w-35">รหัสทรัพย์สิน:</td>
                        <td id="v_asset_code"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">ชื่อทรัพย์สิน:</td>
                        <td id="v_asset_name"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">สถานที่:</td>
                        <td id="v_location"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">ความถี่:</td>
                        <td>ทุกๆ <span id="v_freq"></span> วัน</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">ทำล่าสุด:</td>
                        <td id="v_last_done"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">กำหนดถัดไป:</td>
                        <td id="v_next_due" class="fw-bold text-primary"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">หมายเหตุ:</td>
                        <td id="v_notes" class="text-muted fst-italic"></td>
                    </tr>
                </table>

                <hr class="my-4">

                <div class="d-grid">
                    <a href="#" id="btnComplete" class="btn btn-success fw-bold shadow-sm hover-scale" onclick="return confirm('ยืนยันว่าบำรุงรักษาเสร็จสิ้นแล้ว?\nระบบจะคำนวณวันนัดครั้งต่อไปให้อัตโนมัติ')">
                        <i class="bi bi-check-circle-fill me-2"></i> บันทึกว่าบำรุงรักษาเรียบร้อย
                    </a>
                    <div class="text-center mt-2">
                        <small class="text-muted" style="font-size: 0.75rem;">* ระบบจะเลื่อนวันนัดครั้งต่อไปให้อัตโนมัติตามความถี่</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var pmModal, viewModal;
    var calendar;

    document.addEventListener('DOMContentLoaded', function() {
        pmModal = new bootstrap.Modal(document.getElementById('pmModal'));
        viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

        // Init Calendar
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'th',
            initialView: 'dayGridMonth',
            height: 600,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            events: 'fetch_pm_events.php', // ไฟล์ดึงข้อมูล JSON
            eventClick: function(info) {
                Swal.fire({
                    title: info.event.title,
                    html: `<b>${info.event.extendedProps.detail}</b><br>
                           <small class="text-muted">${info.event.extendedProps.freq}</small><br>
                           <p class="mt-2 text-start small border p-2 rounded bg-light">${info.event.extendedProps.notes || 'ไม่มีหมายเหตุ'}</p>`,
                    icon: 'info',
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#0d6efd'
                });
            }
        });

        // Re-render calendar when tab shown
        document.getElementById('calendar-tab').addEventListener('shown.bs.tab', function(e) {
            calendar.render();
        });
    });

    // Open Add/Edit Modal
    function openModal(action, json = null) {
        document.getElementById('p_action').value = action;
        document.getElementById('p_title').innerText = (action == 'add' ? 'สร้างแผน PM ใหม่' : 'แก้ไขแผน PM');
        document.getElementById('p_id').value = '';
        document.forms[0].reset();

        // Reset Select2 if used
        if (window.$ && $.fn.select2) {
            $('#asset_id').val(null).trigger('change');
        }

        if (action == 'add') {
            document.getElementById('next_due_date').valueAsDate = new Date();
        }

        if (json) {
            const d = JSON.parse(json);
            document.getElementById('p_id').value = d.id;
            document.getElementById('name').value = d.name;

            if (window.$ && $.fn.select2) {
                $('#asset_id').val(d.asset_id).trigger('change');
            } else {
                document.getElementById('asset_id').value = d.asset_id;
            }

            document.getElementById('frequency_days').value = d.frequency_days;
            document.getElementById('next_due_date').value = d.next_due_date;
            document.getElementById('notes').value = d.notes;
            document.getElementById('status').checked = (d.status === 'active');
        }
        pmModal.show();
    }

    // Open View Modal
    function openView(json) {
        const d = JSON.parse(json);
        document.getElementById('v_name').innerText = d.name;
        document.getElementById('v_status').innerText = d.status.toUpperCase();
        document.getElementById('v_status').className = 'badge ' + (d.status == 'active' ? 'bg-success' : 'bg-secondary');

        document.getElementById('v_asset_code').innerText = d.asset_code || '-';
        document.getElementById('v_asset_name').innerText = d.asset_name || '-';
        document.getElementById('v_location').innerText = d.location_name || '-';
        document.getElementById('v_freq').innerText = d.frequency_days;

        // Date Formatting
        const formatDate = (dateStr) => dateStr ? new Date(dateStr).toLocaleDateString('th-TH') : '-';
        document.getElementById('v_last_done').innerText = formatDate(d.last_done_date);
        document.getElementById('v_next_due').innerText = formatDate(d.next_due_date);

        document.getElementById('v_notes').innerText = d.notes || '-';

        // Update Action Link
        document.getElementById('btnComplete').href = `process.php?action=complete&id=${d.id}`;

        viewModal.show();
    }
</script>