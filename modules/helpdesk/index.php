<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// --- Filter Logic (Secure) ---
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

$stmt = $pdo->prepare($sql);
$stmt->execute();
$tickets = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$technicians = $pdo->query("SELECT * FROM users WHERE role IN ('technician', 'admin')")->fetchAll();
$users_all = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY fullname ASC")->fetchAll();
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<style>
    :root { --primary-color: #2563eb; }
    
    .header-gradient {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        padding: 20px 25px;
        position: relative;
        overflow: hidden;
    }
    
    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: 0.2s;
        background: #f8fafc;
    }
    .upload-area:hover {
        border-color: var(--primary-color);
        background: #eff6ff;
    }
    
    .form-control, .form-select {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 15px;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .hover-scale { transition: 0.2s; }
    .hover-scale:hover { transform: scale(1.02); }
    
    .preview-img {
        max-height: 150px;
        display: none;
        margin-top: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* ✅ แก้ปัญหาตัวอักษรซ้อนทับ */
    .table td {
        vertical-align: middle;
        white-space: nowrap; /* ให้แสดงบรรทัดเดียว */
    }
    /* ยกเว้นคอลัมน์รายละเอียด ให้ตัดคำได้ */
    .text-wrap-fix {
        white-space: normal !important;
        min-width: 200px;
    }
    /* แก้ใน Modal ให้ตัดคำยาวๆ */
    #v_desc {
        word-wrap: break-word;
        word-break: break-word;
        white-space: pre-wrap;
    }
</style>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        <span class="fw-bold text-dark">Helpdesk (Tickets)</span>
        <span class="text-muted ms-2 small border-start ps-2">ระบบแจ้งซ่อม</span>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-3"> 
            <?php if (isset($_GET['msg'])): ?>
                <script>Swal.fire({icon: 'success', title: 'สำเร็จ!', timer: 1500, showConfirmButton: false});</script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-list-columns-reverse me-2"></i>รายการแจ้งซ่อม</h6>
                    <button type="button" class="btn btn-primary shadow-sm hover-scale rounded-pill px-4" onclick="openAddModal()">
                        <i class="bi bi-plus-lg me-1"></i> แจ้งปัญหา/งานซ่อม
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 datatable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">รหัส (ID)</th>
                                    <th>ประเภท</th>
                                    <th>ปัญหา/อาการ</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>สถานะ</th>
                                    <th>กำหนดเสร็จ (SLA)</th>
                                    <th class="text-end pe-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $row):
                                    $json = htmlspecialchars(json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                    
                                    $is_overdue = false;
                                    $sla_text = '-';
                                    if ($row['sla_due_date']) {
                                        $sla_time = strtotime($row['sla_due_date']);
                                        $sla_text = date('d/m H:i', $sla_time);
                                        if (!in_array($row['status'], ['resolved', 'closed']) && time() > $sla_time) {
                                            $is_overdue = true;
                                        }
                                    }

                                    // สร้างรหัส VF-TK-XXX
                                    $ticketCode = 'VF-TK-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-primary text-nowrap"><?= $ticketCode ?></td>
                                        <td>
                                            <?php if (isset($row['type']) && $row['type'] == 'request'): ?>
                                                <span class="badge bg-info text-dark">Req</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inc</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-wrap-fix">
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 250px; cursor:pointer;" onclick="openView('<?= $json ?>')">
                                                <?= $row['description'] ?>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-tag"></i> <?= $row['cat_name'] ?>
                                                <span class="badge bg-<?= match ($row['priority']) { 'critical' => 'danger', 'high' => 'warning', default => 'light text-dark border' } ?> ms-1"><?= ucfirst($row['priority']) ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 150px;"><?= $row['requester_name'] ?></div>
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
                                        <td class="text-end pe-3 text-nowrap">
                                            <button class="btn btn-sm btn-light border text-info py-0 me-1 shadow-sm" onclick="openView('<?= $json ?>')" title="ดูรายละเอียด"><i class="bi bi-eye"></i></button>
                                            
                                            <?php if ($_SESSION['role'] != 'user'): ?>
                                                <button class="btn btn-sm btn-light border text-warning py-0 me-1 shadow-sm" onclick="openEdit('<?= $json ?>')" title="แก้ไขสถานะ"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-light border text-danger py-0 shadow-sm" 
                                                    onclick="confirmDelete('process.php?action=delete&id=<?= $row['id'] ?>', 'ยืนยันลบ Ticket <?= $ticketCode ?>?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
            <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="header-gradient">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>แจ้งปัญหา / งานซ่อม</h5>
                            <p class="small m-0 text-white-50 mt-1">กรอกรายละเอียดงานซ่อมเพื่อมอบหมายงาน</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2"><i class="bi bi-person-badge me-1"></i> ข้อมูลผู้แจ้ง</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="small text-muted fw-bold mb-1">ผู้แจ้ง (Requester)</label>
                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'technician'): ?>
                                <select name="requester_id" class="form-select select2">
                                    <option value="<?= $_SESSION['user_id'] ?>">แจ้งเอง (<?= $_SESSION['fullname'] ?>)</option>
                                    <?php foreach ($users_all as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= $u['fullname'] ?> (<?= $u['dept_name'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control" value="แจ้งเอง (<?= $_SESSION['fullname'] ?>)" readonly>
                                <input type="hidden" name="requester_id" value="<?= $_SESSION['user_id'] ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2"><i class="bi bi-pc-display me-1"></i> รายละเอียดปัญหา</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">ประเภทงาน <span class="text-danger">*</span></label>
                            <select name="type" class="form-select">
                                <option value="incident">🚨 Incident (แจ้งปัญหา/เสีย)</option>
                                <option value="request">❓ Request (คำร้องขอ/ขอของ)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">หมวดหมู่</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- ระบุหมวดหมู่ --</option>
                                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">อุปกรณ์ (ถ้ามี)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-qr-code"></i></span>
                                <input type="text" name="asset_code" class="form-control border-start-0" placeholder="รหัสทรัพย์สิน (Asset Code)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">ความเร่งด่วน</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low (ไม่ด่วน)</option>
                                <option value="medium" selected>Medium (ปานกลาง)</option>
                                <option value="high">High (ด่วน)</option>
                                <option value="critical">Critical (ด่วนที่สุด)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold mb-1">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" placeholder="ระบุอาการที่พบโดยละเอียด..." required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="small text-muted fw-bold mb-2">แนบรูปภาพ (ถ้ามี)</label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="bi bi-cloud-arrow-up fs-2 text-primary"></i>
                                <div class="small text-muted mt-1">คลิกเพื่อเลือกรูปภาพ</div>
                                <input type="file" name="attachment" id="fileInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                                <img id="imgPreview" class="preview-img mx-auto">
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notify_line" id="chkLine" checked>
                                <label class="form-check-label small" for="chkLine">ส่งแจ้งเตือนทาง LINE Notify</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-3 border-top bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale shadow-sm">
                        <i class="bi bi-send-fill me-2"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-info text-white py-2 rounded-top-4">
                <h6 class="modal-title fw-bold">รายละเอียด <span id="v_id_text"></span></h6>
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
                        <div id="comment_history" class="border rounded p-2 mb-2 bg-white" style="height: 200px; overflow-y: auto;">
                        </div>
                        
                        <h6 class="fw-bold text-primary small mb-2">ตอบกลับ / บันทึก</h6>
                        <form action="process.php" method="POST">
                            <input type="hidden" name="action" value="comment">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"> <input type="hidden" name="ticket_id" id="c_tid">
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
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"> <input type="hidden" name="id" id="e_id">
                
                <div class="modal-header bg-warning text-dark py-2 rounded-top-4">
                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>อัปเดตงาน</h6>
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
                <div class="modal-footer py-2 border-top-0 bg-light rounded-bottom-4">
                    <button type="submit" class="btn btn-sm btn-warning w-100">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
    var addModal, editModal, viewModal;
    
    document.addEventListener('DOMContentLoaded', function() {
        addModal = new bootstrap.Modal(document.getElementById('addModal'));
        editModal = new bootstrap.Modal(document.getElementById('editModal'));
        viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    });

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPreview').src = e.target.result;
                document.getElementById('imgPreview').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddModal() {
        document.getElementById('addForm').reset();
        document.getElementById('imgPreview').style.display = 'none';
        addModal.show();
    }

    function openEdit(json) {
        const d = JSON.parse(json);
        document.getElementById('e_id').value = d.id;
        document.getElementById('e_status').value = d.status;
        document.getElementById('e_tech').value = d.assigned_to;
        editModal.show();
    }

    function openView(json) {
        const d = JSON.parse(json);
        const ticketId = 'VF-TK-' + d.id.toString().padStart(3, '0');
        
        document.getElementById('v_id_text').innerText = ticketId;
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

        if (d.attachment) {
            document.getElementById('v_img_container').style.display = 'block';
            document.getElementById('v_img').src = '../../uploads/tickets/' + d.attachment;
        } else {
            document.getElementById('v_img_container').style.display = 'none';
        }

        const historyBox = document.getElementById('comment_history');
        historyBox.innerHTML = '<div class="text-center small text-muted mt-3">Loading...</div>';
        fetch('get_comments.php?ticket_id=' + d.id)
            .then(r => r.text())
            .then(html => {
                historyBox.innerHTML = html;
                historyBox.scrollTop = historyBox.scrollHeight;
            });

        viewModal.show();
    }
</script>