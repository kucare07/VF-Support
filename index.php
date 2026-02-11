<?php
session_start(); // เริ่ม Session เพื่อเช็คสถานะล็อกอิน
require_once 'config/db_connect.php';

// ดึงหมวดหมู่
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// ดึง 10 รายการแจ้งซ่อมล่าสุด
$latest_tickets = $pdo->query("
    SELECT t.*, c.name as cat_name 
    FROM tickets t 
    LEFT JOIN categories c ON t.category_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
")->fetchAll();

// ดึง 4 บทความล่าสุด (ฐานความรู้)
$latest_kb = $pdo->query("
    SELECT k.*, c.name as cat_name 
    FROM kb_articles k 
    LEFT JOIN kb_categories c ON k.category_id = c.id 
    WHERE k.is_public = 1 
    ORDER BY k.created_at DESC 
    LIMIT 4
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Center | ระบบแจ้งซ่อมออนไลน์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --primary: #2563eb; --secondary: #f1f5f9; --dark: #1e293b; }
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        
        /* Navbar */
        .navbar { background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 1rem 0; }
        .brand-icon { width: 42px; height: 42px; background: var(--primary); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-right: 12px; }
        
        /* Cards */
        .main-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: white; overflow: hidden; height: 100%; transition: transform 0.2s; }
        .card-header-c { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; background: white; }
        
        /* Ticket List */
        .ticket-item { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: 0.2s; border-left: 3px solid transparent; }
        .ticket-item:hover { background: #f8fafc; border-left-color: var(--primary); }

        /* KB Cards */
        .kb-card { border: none; border-radius: 12px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: 0.3s; cursor: pointer; height: 100%; border: 1px solid #f1f5f9; }
        .kb-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.1); border-color: var(--primary); }
        .kb-icon { width: 40px; height: 40px; background: #eff6ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }

        /* Footer */
        .footer { background: var(--dark); color: #94a3b8; padding: 60px 0 30px; margin-top: 80px; }
        .footer-link { color: #cbd5e1; text-decoration: none; display: block; margin-bottom: 10px; transition: 0.2s; }
        .footer-link:hover { color: white; padding-left: 5px; }
        
        /* Button */
        .btn-gradient { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; color: white; border-radius: 10px; padding: 12px; font-weight: 500; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2); }
        .btn-gradient:hover { background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); color: white; transform: translateY(-1px); }
    </style>
</head>
<body>

    <nav class="navbar sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-primary" href="#">
                <div class="brand-icon"><i class="bi bi-cpu-fill"></i></div>
                <div class="lh-1">
                    IT Service Center<br>
                    <small class="text-muted fw-normal" style="font-size: 0.75rem;">ระบบแจ้งซ่อมและฐานความรู้</small>
                </div>
            </a>
            <div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="admin.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-speedometer2 me-1"></i> ไปที่ Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-shield-lock me-1"></i> เจ้าหน้าที่เข้าสู่ระบบ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="row g-4 mb-5">
            <div class="col-lg-7">
                <div class="main-card">
                    <div class="card-header-c">
                        <h5 class="fw-bold m-0 text-dark"><i class="bi bi-send-plus text-primary me-2"></i>แจ้งปัญหา / ส่งงานซ่อม</h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="publicForm">
                            <input type="hidden" name="action" value="create">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">ชื่อผู้แจ้ง <span class="text-danger">*</span></label>
                                    <input type="text" name="guest_name" class="form-control bg-light border-0 py-2" placeholder="ระบุชื่อ-นามสกุล" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">เบอร์โทร / แผนก <span class="text-danger">*</span></label>
                                    <input type="text" name="guest_contact" class="form-control bg-light border-0 py-2" placeholder="เช่น 089-xxxx, บัญชี" required>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-1">หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select bg-light border-0 py-2" required>
                                        <option value="">-- เลือกหมวดหมู่ปัญหา --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-1">หัวข้อเรื่อง <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control bg-light border-0 py-2" placeholder="สรุปอาการ เช่น เปิดไม่ติด, จอฟ้า" required>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-1">รายละเอียดเพิ่มเติม</label>
                                    <textarea name="description" class="form-control bg-light border-0" rows="4" placeholder="ระบุรายละเอียด..."></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-gradient w-100">
                                        <i class="bi bi-paperplane-fill me-2"></i> ส่งแจ้งซ่อม
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="main-card">
                    <div class="card-header-c d-flex justify-content-between align-items-center bg-primary text-white">
                        <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2"></i>รายการล่าสุด</h5>
                        <button class="btn btn-sm btn-light rounded-circle text-primary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div class="card-body p-0" style="max-height: 580px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            <?php foreach ($latest_tickets as $row): 
                                $status_badge = match ($row['status']) {
                                    'new' => '<span class="badge bg-secondary">New</span>',
                                    'assigned' => '<span class="badge bg-info text-dark">Assigned</span>',
                                    'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                                    'resolved' => '<span class="badge bg-success">Done</span>',
                                    'closed' => '<span class="badge bg-dark">Closed</span>',
                                    default => '<span class="badge bg-secondary">'.$row['status'].'</span>'
                                };
                            ?>
                                <div class="ticket-item" onclick="viewTicket('<?= $row['id'] ?>')">
                                    <div class="d-flex justify-content-between mb-2">
                                        <?= $status_badge ?>
                                        <small class="text-muted"><?= date('d/m H:i', strtotime($row['created_at'])) ?></small>
                                    </div>
                                    <div class="fw-bold text-dark text-truncate mb-1">#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?> <?= htmlspecialchars($row['title'] ?? $row['description']) ?></div>
                                    <small class="text-muted"><i class="bi bi-tag-fill me-1"></i> <?= $row['cat_name'] ?></small>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($latest_tickets) == 0): ?>
                                <div class="text-center py-5 text-muted">ไม่มีรายการ</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center mb-4">
            <div class="col">
                <h4 class="fw-bold m-0 text-dark"><i class="bi bi-journal-bookmark-fill text-warning me-2"></i>คลังความรู้ & วิธีแก้ปัญหาเบื้องต้น</h4>
                <p class="text-muted small mb-0">ค้นหาวิธีแก้ไขปัญหาด้วยตนเอง ก่อนแจ้งเจ้าหน้าที่</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($latest_kb as $kb): ?>
            <div class="col-md-6 col-lg-3">
                <div class="kb-card p-4 h-100" onclick="viewKB(<?= $kb['id'] ?>)">
                    <div class="kb-icon"><i class="bi bi-lightbulb-fill"></i></div>
                    <h6 class="fw-bold mb-2 text-dark line-clamp-2"><?= htmlspecialchars($kb['title']) ?></h6>
                    <div class="small text-muted mb-3 line-clamp-2" style="font-size: 0.85rem;">
                        <?= strip_tags(html_entity_decode($kb['content'])) ?>...
                    </div>
                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-primary fw-bold">อ่านต่อ <i class="bi bi-arrow-right"></i></small>
                        <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-eye"></i> <?= $kb['views'] ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($latest_kb) == 0): ?>
                <div class="col-12 text-center py-4 text-muted bg-light rounded">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i> ยังไม่มีบทความ
                </div>
            <?php endif; ?>
        </div>

    </div>

    <footer class="footer">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-hdd-network me-2"></i>IT Service</h5>
                    <p class="small opacity-75">
                        ระบบสนับสนุนงานบริการเทคโนโลยีสารสนเทศ<br>
                        เพื่อความรวดเร็วและประสิทธิภาพสูงสุดในการทำงาน
                    </p>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">ติดต่อเจ้าหน้าที่</h5>
                    <a href="#" class="footer-link"><i class="bi bi-telephone-fill me-2 text-primary"></i> 02-123-4567 (Helpdesk)</a>
                    <a href="#" class="footer-link"><i class="bi bi-line me-2 text-success"></i> @ITSupport</a>
                    <a href="#" class="footer-link"><i class="bi bi-envelope-fill me-2 text-warning"></i> support@company.com</a>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">เวลาทำการ</h5>
                    <ul class="list-unstyled small opacity-75">
                        <li class="mb-2">จันทร์ - ศุกร์: 08:30 - 17:30 น.</li>
                        <li class="mb-2">เสาร์ - อาทิตย์: ติดต่อเบอร์ฉุกเฉิน</li>
                        <li><span class="badge bg-danger">Emergency</span> 081-999-9999</li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary mt-5 pt-4 text-center small opacity-50">
                &copy; <?= date('Y') ?> IT Service System. All Rights Reserved.
            </div>
        </div>
    </footer>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-primary text-white">
                    <h6 class="modal-title fw-bold">รายละเอียดใบงาน</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalLoading" class="text-center"><div class="spinner-border text-primary"></div></div>
                    <div id="modalContent" style="display:none;">
                        <h5 id="v_title" class="fw-bold text-primary"></h5>
                        <div id="v_desc" class="p-3 bg-light rounded border my-3"></div>
                        <div class="row small text-muted">
                             <div class="col-6">สถานะ: <span id="v_status" class="fw-bold text-dark"></span></div>
                             <div class="col-6 text-end">ช่าง: <span id="v_tech" class="fw-bold text-dark"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kbModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-primary" id="kb_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="d-flex align-items-center text-muted small mb-3 pb-3 border-bottom">
                        <span class="me-3"><i class="bi bi-tag"></i> <span id="kb_cat"></span></span>
                        <span class="me-3"><i class="bi bi-person"></i> <span id="kb_author"></span></span>
                        <span><i class="bi bi-eye"></i> <span id="kb_views"></span> วิว</span>
                    </div>
                    <div id="kb_content" class="text-dark" style="line-height: 1.6;"></div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 1. Submit Form
        document.getElementById('publicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('public_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('สำเร็จ', 'เลขที่ใบงาน: #' + data.ticket_id, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // 2. View Ticket
        const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
        function viewTicket(id) {
            viewModal.show();
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalContent').style.display = 'none';
            
            const formData = new FormData();
            formData.append('ticket_no', id);
            fetch('track_status.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalLoading').style.display = 'none';
                if(data.status === 'success') {
                    document.getElementById('modalContent').style.display = 'block';
                    document.getElementById('v_title').innerText = '#' + data.data.id;
                    document.getElementById('v_desc').innerText = data.data.title;
                    document.getElementById('v_status').innerText = data.data.status_text;
                    document.getElementById('v_tech').innerText = data.data.technician;
                }
            });
        }

        // 3. View KB
        const kbModal = new bootstrap.Modal(document.getElementById('kbModal'));
        function viewKB(id) {
            kbModal.show();
            document.getElementById('kb_title').innerText = 'Loading...';
            document.getElementById('kb_content').innerHTML = '';
            
            const formData = new FormData();
            formData.append('action', 'get_kb');
            formData.append('id', id);

            fetch('public_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    const kb = data.data;
                    document.getElementById('kb_title').innerText = kb.title;
                    document.getElementById('kb_cat').innerText = kb.cat_name || 'General';
                    document.getElementById('kb_author').innerText = kb.author_name || 'Admin';
                    document.getElementById('kb_views').innerText = kb.views;
                    document.getElementById('kb_content').innerHTML = kb.content;
                }
            });
        }
    </script>
</body>
</html>