<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// SQL: Fetch data (Includes SLA Due Date)
$sql = "SELECT t.*, 
        u.fullname as requester_name, u.email as req_email, u.phone as req_phone,
        d.name as dept_name,
        c.name as cat_name, 
        tech.fullname as tech_name,
        a.name as asset_name, l.name as location_name
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users tech ON t.assigned_to = tech.id
        LEFT JOIN assets a ON t.asset_code = a.asset_code
        LEFT JOIN locations l ON a.location_id = l.id
        ORDER BY t.priority = 'critical' DESC, t.created_at DESC";

$tickets = $pdo->query($sql)->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$technicians = $pdo->query("SELECT * FROM users WHERE role IN ('technician', 'admin')")->fetchAll();
$users_all = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Helpdesk (Tickets)</span>
        <span class="text-muted ms-2 small border-start ps-2">ระบบแจ้งซ่อม</span>
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
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-list-columns-reverse me-2"></i>รายการแจ้งซ่อม</h6>
                    <button class="btn btn-sm btn-primary" onclick="openAddModal()">
                        <i class="bi bi-plus-circle me-1"></i> แจ้งปัญหา/งานซ่อม
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">รหัส (ID)</th>
                                    <th>ประเภท (Type)</th>
                                    <th>ปัญหา/อาการ</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>สถานะ (Status)</th>
                                    <th>กำหนดเสร็จ (SLA)</th>
                                    <th class="text-end pe-3">จัดการ (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $row):
                                    $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    // SLA Calculation
                                    $is_overdue = false;
                                    $sla_text = '-';
                                    if ($row['sla_due_date']) {
                                        $sla_time = strtotime($row['sla_due_date']);
                                        $sla_text = date('d/m H:i', $sla_time);
                                        // Check if overdue and not resolved/closed
                                        if (!in_array($row['status'], ['resolved', 'closed']) && time() > $sla_time) {
                                            $is_overdue = true;
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold">#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <?php if (isset($row['type']) && $row['type'] == 'request'): ?>
                                                <span class="badge bg-info text-dark" title="Request">Req</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="Incident">Inc</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-truncate text-primary" style="max-width: 200px; cursor:pointer;" onclick="openView('<?= $json ?>')">
                                                <?= $row['description'] ?>
                                            </div>
                                            <small class="text-muted"><i class="bi bi-tag"></i> <?= $row['cat_name'] ?>
                                                <span class="badge bg-<?= match ($row['priority']) {
                                                                            'critical' => 'danger',
                                                                            'high' => 'warning',
                                                                            default => 'light text-dark border'
                                                                        } ?> ms-1"><?= ucfirst($row['priority']) ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <div><?= $row['requester_name'] ?></div>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?= $row['dept_name'] ?></small>
                                        </td>
                                        <td><?= getStatusBadge($row['status']) ?></td>
                                        <td>
                                            <?php if ($is_overdue): ?>
                                                <span class="text-danger fw-bold" title="เกินกำหนด!"><i class="bi bi-fire"></i> <?= $sla_text ?></span>
                                            <?php elseif (in_array($row['status'], ['resolved', 'closed'])): ?>
                                                <span class="text-success"><i class="bi bi-check2"></i> ทันเวลา</span>
                                            <?php else: ?>
                                                <span class="text-muted"><?= $sla_text ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button class="btn btn-sm btn-light border py-0 me-1" onclick="openView('<?= $json ?>')" title="ดูรายละเอียด"><i class="bi bi-eye"></i></button>
                                            <?php if ($_SESSION['role'] != 'user'): ?>
                                                <button class="btn btn-sm btn-light border text-warning py-0" onclick="openEdit('<?= $json ?>')" title="แก้ไขสถานะ"><i class="bi bi-pencil"></i></button>
                                            <?php endif; ?>
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

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold">แจ้งปัญหา / งานซ่อม</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <label class="col-sm-3 col-form-label small fw-bold">ผู้แจ้ง:</label>
                                <div class="col-sm-9">
                                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'technician'): ?>
                                        <select name="requester_id" class="form-select form-select-sm select2">
                                            <option value="<?= $_SESSION['user_id'] ?>">แจ้งเอง (<?= $_SESSION['fullname'] ?>)</option>
                                            <?php foreach ($users_all as $u): ?>
                                                <option value="<?= $u['id'] ?>"><?= $u['fullname'] ?> (<?= $u['username'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control form-control-sm" value="แจ้งเอง (<?= $_SESSION['fullname'] ?>)" readonly>
                                        <input type="hidden" name="requester_id" value="<?= $_SESSION['user_id'] ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">ประเภทงาน <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="incident">🚨 Incident (แจ้งปัญหา/เสีย)</option>
                                <option value="request">❓ Request (คำร้องขอ/ขอของ)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">หมวดหมู่</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">-- ระบุหมวดหมู่ --</option>
                                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">อุปกรณ์ (ถ้ามี)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="asset_code" class="form-control" placeholder="ระบุรหัสทรัพย์สิน">
                                <button class="btn btn-outline-secondary" type="button"><i class="bi bi-qr-code"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">ความเร่งด่วน</label>
                            <select name="priority" class="form-select form-select-sm">
                                <option value="low">Low (+5 วัน)</option>
                                <option value="medium" selected>Medium (+3 วัน)</option>
                                <option value="high">High (+1 วัน)</option>
                                <option value="critical">Critical (+4 ชม.)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold mb-1">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control form-control-sm" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold mb-1">แนบรูปภาพ</label>
                            <input type="file" name="attachment" class="form-control form-control-sm">
                        </div>

                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_line" id="chkLine" checked>
                                <label class="form-check-label small" for="chkLine">แจ้งเตือนทาง LINE</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold">รายละเอียด #<span id="v_id_text"></span></h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex justify-content-between bg-light p-2 border-bottom small">
                    <div>สถานะ: <span id="v_status_badge"></span></div>
                    <div>Due Date: <span id="v_due_date" class="fw-bold"></span></div>
                </div>

                <div class="row g-0">
                    <div class="col-md-7 p-3 border-end">
                        <h6 class="fw-bold text-primary small border-bottom pb-1 mb-2">ข้อมูลการแจ้ง</h6>
                        <p class="fw-bold mb-2" id="v_desc"></p>

                        <div class="row small text-muted mb-2">
                            <div class="col-6">หมวดหมู่: <span id="v_cat" class="text-dark"></span></div>
                            <div class="col-6">วันที่: <span id="v_date" class="text-dark"></span></div>
                        </div>

                        <div id="v_img_container" class="mt-2" style="display:none;">
                            <img id="v_img" src="" class="img-fluid rounded border" style="max-height: 150px;">
                        </div>

                        <h6 class="fw-bold text-primary small border-bottom pb-1 mb-2 mt-3">ข้อมูลทรัพย์สิน</h6>
                        <div class="bg-light p-2 rounded small">
                            <i class="bi bi-pc-display me-1"></i> <span id="v_asset_code" class="fw-bold"></span>
                            <span id="v_asset_name" class="text-muted ms-1"></span><br>
                            <i class="bi bi-geo-alt me-1"></i> <span id="v_location"></span>
                        </div>
                    </div>

                    <div class="col-md-5 p-3 bg-light bg-opacity-50">
                        <h6 class="fw-bold text-primary small border-bottom pb-1 mb-2">ผู้แจ้ง & ผู้รับผิดชอบ</h6>
                        <div class="small mb-3">
                            <div class="text-muted">ผู้แจ้ง:</div>
                            <div class="fw-bold" id="v_req_name"></div>
                            <div class="text-muted" id="v_req_dept"></div>
                        </div>
                        <div class="small mb-3">
                            <div class="text-muted">ช่างผู้รับผิดชอบ:</div>
                            <div class="fw-bold text-primary" id="v_tech"></div>
                        </div>

                        <hr class="my-2">
                        <h6 class="fw-bold text-primary small mb-2">ประวัติการสนทนา</h6>
                        <div id="comment_history" class="border rounded p-2 mb-2 bg-white" style="height: 250px; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="text-center text-muted small mt-5">Loading...</div>
                        </div>
                        <h6 class="fw-bold text-primary small mb-2">ตอบกลับ / บันทึก</h6>
                        <form action="process.php" method="POST">
                            <input type="hidden" name="action" value="comment">
                            <input type="hidden" name="ticket_id" id="c_tid">
                            <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" placeholder="พิมพ์ข้อความ..." required></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">ส่งข้อความ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="e_id">
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title fw-bold text-dark">อัปเดตงาน</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">สถานะ</label>
                        <select name="status" id="e_status" class="form-select form-select-sm">
                            <option value="new">New</option>
                            <option value="assigned">Assigned</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ช่างผู้รับผิดชอบ</label>
                        <select name="assigned_to" id="e_tech" class="form-select form-select-sm">
                            <option value="">-- เลือกช่าง --</option>
                            <?php foreach ($technicians as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= $t['fullname'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-1 border-top-0">
                    <button type="submit" class="btn btn-sm btn-warning w-100">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    function openAddModal() {
        new bootstrap.Modal(document.getElementById('addModal')).show();
    }

    function openEdit(json) {
        const d = JSON.parse(json);
        document.getElementById('e_id').value = d.id;
        document.getElementById('e_status').value = d.status;
        document.getElementById('e_tech').value = d.assigned_to;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function openView(json) {
        const d = JSON.parse(json);
        document.getElementById('v_id_text').innerText = d.id.toString().padStart(5, '0');
        document.getElementById('v_status_badge').innerHTML = `<span class="badge bg-secondary">${d.status.toUpperCase()}</span>`;
        document.getElementById('v_desc').innerText = d.description;
        document.getElementById('v_cat').innerText = d.cat_name || '-';
        document.getElementById('v_date').innerText = d.created_at;
        document.getElementById('v_due_date').innerText = d.sla_due_date || '-';
        document.getElementById('v_req_name').innerText = d.requester_name;
        document.getElementById('v_req_dept').innerText = d.dept_name || '-';
        document.getElementById('v_asset_code').innerText = d.asset_code || '-';
        document.getElementById('v_asset_name').innerText = d.asset_name || '';
        document.getElementById('v_location').innerText = d.location_name || '-';
        document.getElementById('v_tech').innerText = d.tech_name || 'รอจัดสรร';
        document.getElementById('c_tid').value = d.id;

        const historyBox = document.getElementById('comment_history');
        historyBox.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> กำลังโหลด...</div>';
        fetch('get_comments.php?ticket_id=' + d.id)
            .then(response => response.text())
            .then(html => {
                historyBox.innerHTML = html;
                // สั่งให้ Scrollbar เลื่อนลงไปล่างสุดเสมอ เพื่อดูข้อความล่าสุด
                setTimeout(() => {
                    historyBox.scrollTop = historyBox.scrollHeight;
                }, 100);
            })
            .catch(err => {
                historyBox.innerHTML = '<div class="text-center text-danger small mt-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            });
        // ✅ จบส่วนที่เพิ่ม
        if (d.attachment) {
            document.getElementById('v_img_container').style.display = 'block';
            document.getElementById('v_img').src = '../../uploads/tickets/' + d.attachment;
        } else {
            document.getElementById('v_img_container').style.display = 'none';
        }
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    }

    // ✅ Removed the problematic 'menu-toggle' event listener
</script>