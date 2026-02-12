<?php
session_start();
require_once 'config/db_connect.php';

// --- 1. Queries ข้อมูล ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$latest_tickets = $pdo->query("SELECT t.*, c.name as cat_name FROM tickets t LEFT JOIN categories c ON t.category_id = c.id ORDER BY t.created_at DESC LIMIT 8")->fetchAll();
$available_assets = $pdo->query("SELECT * FROM assets WHERE status = 'spare' ORDER BY name ASC LIMIT 6")->fetchAll();
$borrowed_list = $pdo->query("SELECT b.*, a.name as asset_name, a.asset_code, a.image, u.fullname as borrower_name FROM borrow_transactions b JOIN assets a ON b.asset_id = a.id LEFT JOIN users u ON b.user_id = u.id WHERE b.status = 'borrowed' ORDER BY b.return_due_date ASC LIMIT 6")->fetchAll();
$latest_kb = $pdo->query("SELECT k.*, c.name as cat_name FROM kb_articles k LEFT JOIN kb_categories c ON k.category_id = c.id WHERE k.is_public = 1 ORDER BY k.views DESC LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Center</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --bg-color: #f3f6f9;
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
        }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg-color); color: #334155; }
        
        /* Navbar Glass Effect */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        /* Modern Card Styling */
        .modern-card {
            background: #fff; border: none; border-radius: 16px;
            box-shadow: var(--card-shadow); overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .modern-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.12); }

        /* Headers */
        .header-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white; padding: 25px; position: relative;
        }
        .header-light {
            background: #fff; padding: 20px 25px; border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* List Items */
        .list-item-modern {
            display: flex; align-items: center; padding: 12px 15px;
            border-bottom: 1px solid #f8fafc; transition: 0.2s; cursor: pointer;
        }
        .list-item-modern:last-child { border-bottom: none; }
        .list-item-modern:hover { background-color: #f8fafc; padding-left: 20px; }

        /* Icons Box */
        .icon-box {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-right: 15px; flex-shrink: 0;
        }
        /* Color Themes */
        .theme-ticket { background: #eff6ff; color: #2563eb; }
        .theme-asset-ok { background: #ecfdf5; color: #10b981; }
        .theme-asset-borrow { background: #fffbeb; color: #f59e0b; }
        .theme-kb { background: #fdf2f8; color: #db2777; }

        /* Form Inputs */
        .form-control, .form-select {
            background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            background-color: #fff; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Footer */
        .main-footer { background: #1e293b; color: #94a3b8; padding: 50px 0 20px; margin-top: 60px; }
        .footer-link { color: #cbd5e1; text-decoration: none; display: block; margin-bottom: 8px; transition: 0.2s; }
        .footer-link:hover { color: #fff; transform: translateX(5px); }

        /* Animations */
        .hover-scale { transition: 0.2s; }
        .hover-scale:hover { transform: scale(1.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-glass sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <div class="bg-primary text-white rounded-3 p-2 me-2 shadow-sm">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div class="lh-1">
                    <span class="fw-bold fs-5 text-primary">IT SERVICE</span><br>
                    <small class="text-muted" style="font-size: 0.75rem;">Support Center</small>
                </div>
            </a>
            
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="row g-4 mb-5">
            <div class="col-lg-5 animate__animated animate__fadeInLeft">
                <div class="modern-card">
                    <div class="header-gradient">
                        <h4 class="m-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>แจ้งปัญหา / ส่งงานซ่อม</h4>
                        <p class="small m-0 text-white-50 mt-1">กรอกข้อมูลให้ครบถ้วน เจ้าหน้าที่จะรีบดำเนินการ</p>
                    </div>
                    <div class="p-4">
                        <form id="publicForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            
                            <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2"><i class="bi bi-person-badge me-1"></i> ข้อมูลผู้แจ้ง</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">1. ชื่อ-นามสกุล *</label>
                                    <input type="text" name="guest_name" class="form-control" placeholder="ชื่อจริง นามสกุล" required>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">2. ตำแหน่ง</label>
                                    <input type="text" name="guest_position" class="form-control" placeholder="เช่น ธุรการ">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">3. ฝ่าย/สังกัด</label>
                                    <input type="text" name="guest_dept" class="form-control" placeholder="เช่น บัญชี">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">4. เบอร์โทร *</label>
                                    <input type="text" name="guest_phone" class="form-control" placeholder="เบอร์ติดต่อ" required>
                                </div>
                            </div>

                            <h6 class="text-primary fw-bold small mb-3 border-bottom pb-2 mt-4"><i class="bi bi-pc-display me-1"></i> รายละเอียดปัญหา</h6>
                            <div class="mb-2">
                                <label class="small text-muted fw-bold">5. เลขครุภัณฑ์ (ถ้ามี)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-barcode"></i></span>
                                    <input type="text" name="asset_code" class="form-control border-start-0" placeholder="เช่น AST-001">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="small text-muted fw-bold">6. หมวดหมู่ปัญหา *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">7. รายละเอียดปัญหา *</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="ระบุอาการที่พบโดยละเอียด..." required></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="small text-muted fw-bold mb-2">8. แนบรูปภาพ (ถ้ามี)</label>
                                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                    <i class="bi bi-cloud-arrow-up fs-2 text-primary"></i>
                                    <div class="small text-muted mt-1">คลิกเพื่อเลือกรูปภาพ</div>
                                    <input type="file" name="attachment" id="fileInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                                    <img id="imgPreview" class="preview-img mx-auto">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm hover-scale">
                                <i class="bi bi-send-fill me-2"></i> ยืนยันการแจ้งซ่อม
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 animate__animated animate__fadeInRight animate__delay-0.5s">
                <div class="modern-card">
                    <div class="header-light">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>รายการแจ้งซ่อมล่าสุด</h6>
                        <button class="btn btn-sm btn-light rounded-circle text-muted" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div class="p-0" style="max-height: 520px; overflow-y: auto;">
                        <?php foreach ($latest_tickets as $row): 
                            $st = $row['status'];
                            $bg = match($st) { 'new'=>'bg-secondary', 'assigned'=>'bg-info text-dark', 'resolved'=>'bg-success', 'closed'=>'bg-dark', default=>'bg-light text-dark' };
                        ?>
                        <div class="list-item-modern" onclick="viewTicket('<?= $row['id'] ?>')">
                            <div class="icon-box theme-ticket"><i class="bi bi-ticket-detailed"></i></div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-truncate text-dark" style="max-width: 70%;">#<?= $row['id'] ?> <?= htmlspecialchars($row['title']??$row['description']) ?></span>
                                    <span class="badge rounded-pill <?= $bg ?>" style="font-size: 0.7rem;"><?= ucfirst($st) ?></span>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-tag"></i> <?= $row['cat_name'] ?> 
                                    <span class="mx-1">•</span> 
                                    <?= date('d/m H:i', strtotime($row['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($latest_tickets)) echo '<div class="text-center py-5 text-muted">ยังไม่มีรายการ</div>'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mb-3 animate__animated animate__fadeInUp">
            <div class="icon-box bg-success text-white rounded-circle shadow-sm me-2" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-laptop"></i></div>
            <h5 class="fw-bold m-0 text-dark">สถานะอุปกรณ์ (Equipment)</h5>
        </div>
        
        <div class="row g-4 mb-5 animate__animated animate__fadeInUp">
            <div class="col-md-6">
                <div class="modern-card">
                    <div class="header-light border-bottom-0 pb-0">
                        <h6 class="m-0 fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>พร้อมใช้งาน (Available)</h6>
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            <?php foreach ($available_assets as $a): 
                                $img = !empty($a['image']) ? "uploads/assets/{$a['image']}" : "https://placehold.co/100x100?text=No+Img";
                            ?>
                            <div class="col-12">
                                <div class="d-flex align-items-center p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25">
                                    <img src="<?= $img ?>" class="rounded-3 me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark small"><?= $a['name'] ?></div>
                                        <small class="text-muted" style="font-size: 0.7rem;"><?= $a['asset_code'] ?></small>
                                    </div>
                                    <span class="ms-auto badge bg-white text-success shadow-sm">ว่าง</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($available_assets)) echo '<div class="text-center text-muted small py-3">ไม่มีอุปกรณ์ว่าง</div>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="modern-card">
                    <div class="header-light border-bottom-0 pb-0">
                        <h6 class="m-0 fw-bold text-warning"><i class="bi bi-clock-history me-2"></i>กำลังถูกยืม (Borrowed)</h6>
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            <?php foreach ($borrowed_list as $b): 
                                $img = !empty($b['image']) ? "uploads/assets/{$b['image']}" : "https://placehold.co/100x100?text=No+Img";
                                $late = strtotime($b['return_due_date']) < time();
                            ?>
                            <div class="col-12">
                                <div class="d-flex align-items-center p-2 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                    <img src="<?= $img ?>" class="rounded-3 me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="lh-1 flex-grow-1">
                                        <div class="fw-bold text-dark small"><?= $b['asset_name'] ?></div>
                                        <small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-person"></i> <?= $b['borrower_name'] ?></small>
                                    </div>
                                    <div class="text-end lh-1">
                                        <small class="text-muted" style="font-size: 0.65rem;">คืน:</small><br>
                                        <span class="fw-bold <?= $late?'text-danger':'text-dark' ?> small"><?= date('d/m', strtotime($b['return_due_date'])) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($borrowed_list)) echo '<div class="text-center text-muted small py-3">ไม่มีรายการยืม</div>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mb-3 animate__animated animate__fadeInUp">
            <div class="icon-box bg-danger text-white rounded-circle shadow-sm me-2" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-book-half"></i></div>
            <h5 class="fw-bold m-0 text-dark">คลังความรู้ (Knowledge Base)</h5>
        </div>

        <div class="row g-4 animate__animated animate__fadeInUp">
            <?php foreach ($latest_kb as $kb): ?>
            <div class="col-md-3">
                <div class="modern-card p-3 h-100 position-relative" style="cursor: pointer;" onclick="viewKB(<?= $kb['id'] ?>)">
                    <div class="icon-box theme-kb mb-3"><i class="bi bi-lightbulb"></i></div>
                    <h6 class="fw-bold text-dark mb-2 line-clamp-2"><?= htmlspecialchars($kb['title']) ?></h6>
                    <small class="text-muted mb-3 d-block line-clamp-2" style="font-size: 0.8rem;">
                        <?= strip_tags(html_entity_decode($kb['content'])) ?>
                    </small>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-auto">
                        <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-folder"></i> <?= $kb['cat_name'] ?></small>
                        <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-eye"></i> <?= $kb['views'] ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-cpu-fill me-2"></i>IT Service Center</h5>
                    <p class="small opacity-75">ระบบสนับสนุนและบริหารจัดการงานไอทีแบบครบวงจร เพื่อประสิทธิภาพสูงสุดขององค์กร</p>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">ติดต่อเรา</h5>
                    <a href="#" class="footer-link"><i class="bi bi-telephone me-2"></i> 02-123-4567 (Helpdesk)</a>
                    <a href="#" class="footer-link"><i class="bi bi-line me-2"></i> @ITSupport</a>
                    <a href="#" class="footer-link"><i class="bi bi-envelope me-2"></i> support@company.com</a>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">เวลาทำการ</h5>
                    <ul class="list-unstyled small opacity-75">
                        <li class="mb-2">จันทร์ - ศุกร์: 08:30 - 17:30 น.</li>
                        <li><span class="badge bg-danger">Emergency</span> 081-999-9999 (24 ชม.)</li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary mt-5 pt-4 text-center small opacity-50">
                &copy; <?= date('Y') ?> IT Service System. All Rights Reserved.
            </div>
        </div>
    </footer>

    <div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 shadow"><div class="modal-header bg-light border-0"><h6 class="modal-title fw-bold">🔎 รายละเอียดใบงาน</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="v_content" class="text-center py-4"><div class="spinner-border text-primary"></div></div></div></div></div></div>
    <div class="modal fade" id="kbModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content rounded-4 shadow"><div class="modal-header border-0"><h5 class="modal-title fw-bold text-primary" id="k_title">...</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body pt-0"><div id="k_content" class="py-3">Loading...</div></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 1. Typewriter Effect
        const text = "ระบบแจ้งซ่อมและติดตามสถานะ 24 ชม.";
        let i = 0; function typeWriter() { if (i < text.length) { document.getElementById("typewriter-text").innerHTML += text.charAt(i); i++; setTimeout(typeWriter, 50); } }
        window.onload = typeWriter;

        // 2. Submit Form
        document.getElementById('publicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalHTML = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังส่ง...';
            
            fetch('public_action.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', html: `เลขที่ใบงานของคุณคือ: <h3 class="text-primary mt-2">#${d.ticket_id}</h3>`, confirmButtonColor: '#2563eb' }).then(() => location.reload());
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', d.message, 'error'); btn.disabled = false; btn.innerHTML = originalHTML;
                }
            });
        });

        // 3. View Ticket
        function viewTicket(id) {
            new bootstrap.Modal(document.getElementById('viewModal')).show();
            const box = document.getElementById('v_content');
            box.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
            
            const fd = new FormData(); fd.append('ticket_no', id);
            fetch('track_status.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                if(d.status==='success') {
                    const i = d.data;
                    box.innerHTML = `
                        <div class="text-start">
                            <span class="badge ${i.status_class} mb-2">${i.status_text}</span>
                            <h5 class="fw-bold text-dark mb-1">#${i.id} ${i.title}</h5>
                            <small class="text-muted"><i class="bi bi-clock"></i> แจ้งเมื่อ: ${i.date}</small>
                            <hr class="my-3">
                            <div class="bg-light p-3 rounded-3 border mb-3 small text-dark">${i.title}</div>
                            <div class="row g-2 small text-muted">
                                <div class="col-6"><i class="bi bi-person-circle"></i> ผู้แจ้ง: <span class="text-dark fw-bold">${i.requester}</span></div>
                                <div class="col-6"><i class="bi bi-tools"></i> ช่าง: <span class="text-dark fw-bold">${i.technician}</span></div>
                            </div>
                        </div>`;
                } else box.innerHTML = `<div class="text-danger">${d.message}</div>`;
            });
        }

        // 4. View KB
        function viewKB(id) {
            new bootstrap.Modal(document.getElementById('kbModal')).show();
            const fd = new FormData(); fd.append('action', 'get_kb'); fd.append('id', id);
            fetch('public_action.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                if(d.status==='success') {
                    document.getElementById('k_title').innerText = d.data.title;
                    document.getElementById('k_content').innerHTML = d.data.content;
                }
            });
        }
    </script>
</body>
</html>