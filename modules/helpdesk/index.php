<?php
// modules/helpdesk/index.php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// --- 1. Filter / Search Logic ---
$where_clauses = [];
$params = [];

// --- 2. Query Data ---
// แก้ไข: เรียงจากน้อยไปมาก (ASC) ตามที่ขอ เพื่อให้งานเก่าสุดขึ้นก่อน
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
        ORDER BY t.id ASC"; // <--- แก้ไขจุดที่ 1: เรียง ID น้อยไปมาก

$stmt = $pdo->prepare($sql);
$stmt->execute();
$tickets = $stmt->fetchAll();

// --- 3. Dropdown Data ---
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$technicians = $pdo->query("SELECT * FROM users WHERE role IN ('technician', 'admin')")->fetchAll();
// แก้ไข: เพิ่ม JOIN กับตาราง departments เพื่อให้มี dept_name
$users_all = $pdo->query("SELECT u.*, d.name as dept_name 
                          FROM users u 
                          LEFT JOIN departments d ON u.department_id = d.id 
                          WHERE u.is_active = 1 
                          ORDER BY u.fullname ASC")->fetchAll();

function renderMiniStepper($current_status)
{
    $steps = ['new', 'assigned', 'pending', 'resolved', 'closed'];
    $current_index = array_search($current_status, $steps);
    if ($current_index === false) $current_index = 0;

    $html = '<div class="mini-stepper" title="สถานะ: ' . ucfirst($current_status) . '">';
    foreach ($steps as $index => $step) {
        $class = '';
        if ($index < $current_index) {
            $class = 'completed';
        } elseif ($index == $current_index) {
            $class = 'active';
        }
        $html .= '<div class="mini-step ' . $class . '"></div>';
    }
    $html .= '</div>';
    return $html;
}
?>

<?php require_once '../../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk (Tickets)</title>
</head>
<style>
    /* Stepper Styles */
    .mini-stepper { display: flex; align-items: center; gap: 3px; }
    .mini-step { width: 8px; height: 8px; border-radius: 50%; background-color: #e9ecef; display: inline-block; }
    .mini-step.active { background-color: #0d6efd; transform: scale(1.3); }
    .mini-step.completed { background-color: #198754; }
    
    .stepper-wrapper { display: flex; justify-content: space-between; margin-bottom: 20px; position: relative; }
    .stepper-wrapper::before { content: ""; position: absolute; top: 15px; left: 0; width: 100%; height: 2px; background: #e0e0e0; z-index: 0; }
    .stepper-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 1; }
    .stepper-circle { width: 30px; height: 30px; border-radius: 50%; background: #fff; border: 2px solid #e0e0e0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #999; margin-bottom: 6px; transition: all 0.3s; }
    .stepper-label { font-size: 0.75rem; color: #999; font-weight: 500; }
    .stepper-item.completed .stepper-circle { background: #198754; border-color: #198754; color: #fff; }
    .stepper-item.completed .stepper-label { color: #198754; }
    .stepper-item.active .stepper-circle { background: #0d6efd; border-color: #0d6efd; color: #fff; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2); }
    .stepper-item.active .stepper-label { color: #0d6efd; font-weight: bold; }

    /* Custom Form Styles to match Index.php */
    .upload-area {
        border: 2px dashed #cbd5e1; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: 0.2s; background: #f8fafc;
    }
    .upload-area:hover { border-color: var(--primary-color); background: #eff6ff; }
    .form-control, .form-select { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 15px; }
    .form-control:focus, .form-select:focus { background-color: #fff; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
</style>

<body>
    <div id="page-content-wrapper">
        <nav class="main-navbar">
            <span class="fw-bold text-dark">Helpdesk (Tickets)</span>
            <span class="text-muted ms-2 small border-start ps-2">ระบบแจ้งซ่อม</span>
        </nav>

        <div class="main-content-scroll">
            <div class="container-fluid p-3">

                <?php if (isset($_GET['msg'])): ?>
                    <script>
                        Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: 'บันทึกข้อมูลเรียบร้อยแล้ว', timer: 1500, showConfirmButton: false });
                    </script>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-primary m-0"><i class="bi bi-list-columns-reverse me-2"></i>รายการแจ้งซ่อม</h6>

                            <button id="bulkActionBtn" class="btn btn-danger btn-sm shadow-sm animate__animated animate__fadeIn" style="display:none;" onclick="deleteSelected('process.php?action=bulk_delete')">
                                <i class="bi bi-trash"></i> ลบที่เลือก
                            </button>
                        </div>

                        <button type="button" class="btn btn-primary shadow-sm hover-scale rounded-pill px-4" onclick="openAddModal()">
                            <i class="bi bi-pencil-square me-2"></i> แจ้งปัญหา / ส่งงานซ่อม
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
                                    <?php 
                                    // แก้ไขจุดที่ 3: ประกาศตัวแปร $i เพื่อป้องกัน Error Undefined Variable
                                    $i = 1; 
                                    foreach ($tickets as $row):
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
                                        $ticketCode = 'VF-TK-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input row-checkbox" value="<?= $row['id'] ?>" onclick="checkRow()">
                                            </td>
                                            <td class="text-center text-muted small fw-bold"><?= $i++ ?></td> <td class="ps-3 fw-bold text-primary text-nowrap"><?= $ticketCode ?></td>

                                            <td>
                                                <?php if (isset($row['type']) && $row['type'] == 'request'): ?>
                                                    <span class="badge bg-info text-dark">Req</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inc</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="text-wrap-fix">
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px; cursor:pointer;"
                                                    data-ticket="<?= $json ?>"
                                                    onclick="openView(this.getAttribute('data-ticket'))">
                                                    <?= $row['description'] ?>
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bi bi-tag"></i> <?= $row['cat_name'] ?>
                                                    <span class="badge bg-<?= match ($row['priority']) {
                                                                                'critical' => 'danger',
                                                                                'high' => 'warning',
                                                                                default => 'light text-dark border'
                                                                            } ?> ms-1"><?= ucfirst($row['priority']) ?></span>
                                                </small>
                                            </td>

                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;"><?= $row['requester_name'] ?></div>
                                                <small class="text-muted" style="font-size: 0.75rem;"><?= $row['dept_name'] ?></small>
                                            </td>

                                            <td>
                                                <div class="d-flex flex-column align-items-start">
                                                    <?= getStatusBadge($row['status']) ?>
                                                    <div class="mt-1">
                                                        <?= renderMiniStepper($row['status']) ?>
                                                    </div>
                                                </div>
                                            </td>

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
                                                <button class="btn btn-sm btn-light border text-info py-0 me-1 shadow-sm"
                                                    data-ticket="<?= $json ?>"
                                                    onclick="openView(this.getAttribute('data-ticket'))"
                                                    title="ดูรายละเอียด"><i class="bi bi-eye"></i></button>

                                                <?php if ($_SESSION['role'] != 'user'): ?>
                                                    <button class="btn btn-sm btn-light border text-warning py-0 me-1 shadow-sm"
                                                        data-ticket="<?= $json ?>"
                                                        onclick="openEdit(this.getAttribute('data-ticket'))"
                                                        title="แก้ไขสถานะ"><i class="bi bi-pencil"></i></button>
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

        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
                    <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="header-gradient p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="m-0 fw-bold text-white"><i class="bi bi-pencil-square me-2"></i>แจ้งปัญหา / ส่งงานซ่อม</h4>
                                    <p class="text-primary fw-bold small mb-3">กรอกรายละเอียดงานซ่อมเพื่อมอบหมายงาน</p>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                        </div>

                        <div class="modal-body p-4 bg-white">
                            <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2"><i class="bi bi-person-badge me-1"></i> ข้อมูลผู้แจ้ง</h6>

                            <div class="row g-2 mb-4">
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
                                <div class="col-md-4">
                                    <label class="small text-muted fw-bold mb-1">ประเภทงาน <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select">
                                        <option value="incident">🚨 Incident (แจ้งเสีย)</option>
                                        <option value="request">❓ Request (ขอข้อมูล/ของ)</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2"><i class="bi bi-pc-display me-1"></i> รายละเอียดปัญหา</h6>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold mb-1">เลขครุภัณฑ์ (ถ้ามี)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-barcode"></i></span>
                                        <input type="text" name="asset_code" class="form-control border-start-0" placeholder="เช่น AST-001">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold mb-1">หมวดหมู่ปัญหา <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select">
                                        <option value="">-- ระบุหมวดหมู่ --</option>
                                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="small text-muted fw-bold mb-1">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="ระบุอาการที่พบโดยละเอียด..." required></textarea>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <label class="small text-muted fw-bold mb-1">ความเร่งด่วน</label>
                                    <select name="priority" class="form-select">
                                        <option value="low">Low (ไม่ด่วน)</option>
                                        <option value="medium" selected>Medium (ปานกลาง)</option>
                                        <option value="high">High (ด่วน)</option>
                                        <option value="critical">Critical (ด่วนที่สุด)</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-3">
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
                            <button type="submit" class="btn btn-primary px-4 fw-bold hover-scale shadow-sm rounded-3">
                                <i class="bi bi-send-fill me-2"></i> ยืนยันการแจ้งซ่อม
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header bg-info text-white py-2 rounded-top-4 header-gradient">
                        <h6 class="modal-title fw-bold">รายละเอียด <span id="v_id_text"></span></h6>
                        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3 bg-white border-bottom">
                            <div id="v_stepper_container" class="stepper-wrapper">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between bg-light p-2 border-bottom small">
                            <div>สถานะ: <span id="v_status_badge"></span></div>
                            <div>Due Date: <span id="v_due_date" class="fw-bold"></span></div>
                        </div>

                        <div class="row g-0">
                            <div class="col-md-7 p-3 border-end">
                                <h6 class="fw-bold text-primary small border-bottom pb-1 mb-2">ข้อมูลการแจ้ง</h6>
                                <p class="fw-bold mb-2" id="v_desc" style="white-space: pre-wrap; word-wrap: break-word;"></p>

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
                                    <option value="new">รอรับเรื่อง</option>
                                    <option value="assigned">รับเรื่องแล้ว</option>
                                    <option value="pending">รอดำเนินการ</option>
                                    <option value="resolved">ดำเนินการเสร็จสิ้น</option>
                                    <option value="closed">ปิดงาน</option>
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
            // Initialize Modals
            var addModal, editModal, viewModal;

            document.addEventListener('DOMContentLoaded', function() {
                addModal = new bootstrap.Modal(document.getElementById('addModal'));
                editModal = new bootstrap.Modal(document.getElementById('editModal'));
                viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            });

            // Preview Image
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

            // Open Add Modal
            function openAddModal() {
                document.getElementById('addForm').reset();
                document.getElementById('imgPreview').style.display = 'none';
                addModal.show();
            }

            // Open Edit Modal
            function openEdit(json) {
                const d = JSON.parse(json);
                document.getElementById('e_id').value = d.id;
                document.getElementById('e_status').value = d.status;
                document.getElementById('e_tech').value = d.assigned_to;
                editModal.show();
            }

            // Open View Modal (With VF-TK Format)
            function openView(json) {
                const d = JSON.parse(json);
                // Format ID to VF-TK-XXX
                const ticketId = 'VF-TK-' + d.id.toString().padStart(3, '0');
                document.getElementById('v_stepper_container').innerHTML = generateFullStepper(d.status);
                document.getElementById('v_id_text').innerText = ticketId;
                document.getElementById('v_status_badge').innerHTML = getStatusBadgeJS(d.status);
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

                // Load Comments
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

            function generateFullStepper(status) {
                const steps = [{
                        key: 'new',
                        label: 'แจ้งงาน',
                        icon: '<i class="bi bi-exclamation-circle"></i>'
                    },
                    {
                        key: 'assigned',
                        label: 'รับเรื่อง',
                        icon: '<i class="bi bi-person-gear"></i>'
                    },
                    {
                        key: 'pending',
                        label: 'รอดำเนินการ',
                        icon: '<i class="bi bi-hourglass-split"></i>'
                    },
                    {
                        key: 'resolved',
                        label: 'แก้ไขเสร็จ',
                        icon: '<i class="bi bi-check-lg"></i>'
                    },
                    {
                        key: 'closed',
                        label: 'ปิดงาน',
                        icon: '<i class="bi bi-archive"></i>'
                    }
                ];

                let currentIndex = steps.findIndex(s => s.key === status);
                if (currentIndex === -1) currentIndex = 0;

                let html = '';
                steps.forEach((step, index) => {
                    let stateClass = '';
                    if (index < currentIndex) stateClass = 'completed';
                    else if (index === currentIndex) stateClass = 'active';

                    html += `
            <div class="stepper-item ${stateClass}">
                <div class="stepper-circle">${step.icon}</div>
                <div class="stepper-label">${step.label}</div>
            </div>`;
                });
                return html;
            }

            function getStatusBadgeJS(status) {
                switch (status) {
                    case 'new':
                        return '<span class="badge bg-danger">รอรับเรื่อง</span>';
                    case 'assigned':
                        return '<span class="badge bg-primary">รับเรื่องแล้ว</span>';
                    case 'pending':
                        return '<span class="badge bg-warning text-dark">รอดำเนินการ</span>';
                    case 'resolved':
                        return '<span class="badge bg-success">ดำเนินการเสร็จสิ้น</span>';
                    case 'closed':
                        return '<span class="badge bg-secondary">ปิดงาน</span>';
                    default:
                        return '<span class="badge bg-secondary">' + status + '</span>';
                }
            }
        </script>

</body>

</html>