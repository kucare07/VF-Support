<?php
// ✅ ระบบตรวจสอบ Path อัตโนมัติ (Smart Path Detection)
// ไม่ต้องแก้ $base บ่อยๆ สคริปต์จะหาให้เองว่าตอนนี้อยู่ที่ Root หรือ Sub-folder
$base_path = file_exists('config/db_connect.php') ? '' : '../../';

// ฟังก์ชันช่วยเช็ค Active Menu
function isActive($needle, $haystack) {
    return strpos($haystack, $needle) !== false ? 'active' : '';
}

// หา Path ปัจจุบัน
$current_page = $_SERVER['PHP_SELF'];
?>

<style>
    /* --- Sidebar Custom CSS (Compact & Responsive) --- */
    :root {
        --sidebar-width: 230px; /* ✅ ลดความกว้างลงเหลือ 230px */
    }

    #sidebar-wrapper {
        min-height: 100vh;
        width: var(--sidebar-width);
        margin-left: calc(var(--sidebar-width) * -1);
        background: #212529;
        background: linear-gradient(180deg, #1a1e21 0%, #212529 100%);
        color: #fff;
        transition: margin 0.3s ease-out;
        position: fixed;
        z-index: 1000;
        top: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        font-size: 0.8rem; /* ✅ ลด Base Font Size ลง (เดิม 0.85) */
    }

    #sidebar-wrapper.active {
        margin-left: 0;
    }

    /* Brand Header */
    .sidebar-brand {
        padding: 0.8rem 1rem; /* ลด Padding */
        font-size: 1rem; /* ลดขนาดหัวข้อแบรนด์ */
        font-weight: bold;
        color: #fff;
        background: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        letter-spacing: 0.5px;
    }

    /* Menu Items */
    .sidebar-menu {
        padding: 10px 8px;
        overflow-y: auto;
        flex-grow: 1;
    }
    
    /* Scrollbar สวยๆ เล็กๆ */
    .sidebar-menu::-webkit-scrollbar { width: 4px; }
    .sidebar-menu::-webkit-scrollbar-thumb { background: #555; border-radius: 2px; }
    .sidebar-menu::-webkit-scrollbar-track { background: transparent; }

    .sidebar-heading {
        font-size: 0.65rem; /* ✅ ลดขนาดหัวข้อย่อย */
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d; /* สีจางลงนิดนึงเพื่อไม่แย่งสายตา */
        margin-top: 1rem;
        margin-bottom: 0.2rem;
        padding-left: 0.8rem;
        font-weight: 700;
    }

    /* Links */
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 7px 10px; /* ✅ ลด Padding บนล่างให้กระชับ */
        color: #ced4da;
        text-decoration: none;
        border-radius: 5px;
        transition: all 0.2s ease;
        margin-bottom: 1px;
        font-size: 0.82rem; /* ✅ ลดขนาดตัวอักษรเมนู */
        white-space: nowrap; /* ห้ามตัดบรรทัด */
        overflow: hidden;
        text-overflow: ellipsis; /* ถ้ายาวเกินให้ ... */
    }

    .sidebar-link i {
        width: 20px;
        text-align: center;
        margin-right: 8px;
        font-size: 0.95rem; /* ไอคอนเล็กลงนิดนึง */
        opacity: 0.8;
    }

    /* Hover & Active States */
    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        transform: translateX(3px);
    }

    .sidebar-link.active {
        background: #0d6efd; /* สีน้ำเงิน Bootstrap */
        background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%);
        color: #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar-link.active i { opacity: 1; }

    /* Submenu */
    .sidebar-submenu {
        margin-left: 10px;
        padding-left: 8px;
        border-left: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-submenu .sidebar-link {
        font-size: 0.78rem; /* เมนูย่อยเล็กกว่าปกตินิดนึง */
        padding: 5px 10px;
    }

    /* Footer Profile */
    .sidebar-footer {
        padding: 10px;
        background: rgba(0, 0, 0, 0.2);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .user-profile {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #fff;
        transition: 0.2s;
    }
    
    .user-avatar {
        width: 32px; /* ลดขนาด Avatar */
        height: 32px;
        background: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold;
        margin-right: 8px;
        font-size: 0.9rem;
    }

    /* --- Responsive Logic --- */
    @media (min-width: 768px) {
        #sidebar-wrapper { margin-left: 0; }
        #page-content-wrapper { margin-left: var(--sidebar-width); }
        #sidebar-wrapper.active { margin-left: calc(var(--sidebar-width) * -1); }
    }
</style>

<div id="sidebar-wrapper">
    <div class="sidebar-brand">
        <i class="bi bi-cpu-fill me-2 text-primary"></i>
        <span>IT Support <span class="text-primary">Sys</span></span>
    </div>

    <div class="sidebar-menu">

        <a href="<?= $base_path ?>index.php" class="sidebar-link <?= isActive('index.php', $current_page) && strpos($current_page, 'modules') === false ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>แดชบอร์ด (Dashboard)</span>
        </a>

        <div class="sidebar-heading">Service Desk</div>

        <a href="<?= $base_path ?>modules/helpdesk/index.php" class="sidebar-link <?= isActive('helpdesk', $current_page) ?>">
            <i class="bi bi-ticket-perforated"></i> <span>แจ้งซ่อม (Tickets)</span>
        </a>

        <a href="<?= $base_path ?>modules/kb/index.php" class="sidebar-link <?= isActive('kb', $current_page) ?>">
            <i class="bi bi-journal-bookmark"></i> <span>ฐานความรู้ (KB)</span>
        </a>

        <div class="sidebar-heading">Assets & Inventory</div>

        <a href="<?= $base_path ?>modules/asset/index.php" class="sidebar-link <?= isActive('asset', $current_page) ?>">
            <i class="bi bi-pc-display"></i> <span>ทรัพย์สิน (Assets)</span>
        </a>

        <a href="<?= $base_path ?>modules/software/index.php" class="sidebar-link <?= isActive('software', $current_page) ?>">
            <i class="bi bi-window-sidebar"></i> <span>ซอฟต์แวร์ (Software)</span>
        </a>

        <a href="<?= $base_path ?>modules/borrow/index.php" class="sidebar-link <?= isActive('borrow', $current_page) ?>">
            <i class="bi bi-arrow-left-right"></i> <span>ยืม-คืน (Borrow & Return)</span>
        </a>

        <a href="<?= $base_path ?>modules/inventory/index.php" class="sidebar-link <?= isActive('inventory', $current_page) ?>">
            <i class="bi bi-box-seam"></i> <span>วัสดุสิ้นเปลือง (Inventory)</span>
        </a>

        <a href="<?= $base_path ?>modules/pm/index.php" class="sidebar-link <?= isActive('pm', $current_page) ?>">
            <i class="bi bi-calendar-check"></i> <span>แผนบำรุงรักษา (PM)</span>
        </a>

        <div class="sidebar-heading">Reporting</div>
        
        <a href="<?= $base_path ?>modules/reports/index.php" class="sidebar-link <?= isActive('reports', $current_page) ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> <span>รายงาน (Reports)</span>
        </a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <div class="sidebar-heading">System</div>

            <a href="<?= $base_path ?>modules/user/index.php" class="sidebar-link <?= isActive('user', $current_page) ?>">
                <i class="bi bi-people"></i> <span>ผู้ใช้งาน (Users)</span>
            </a>

            <a href="#settingsSubmenu" class="sidebar-link dropdown-toggle <?= isActive('settings', $current_page) ? '' : 'collapsed' ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?= isActive('settings', $current_page) ? 'true' : 'false' ?>">
                <i class="bi bi-gear"></i> <span>ตั้งค่า (Settings)</span>
            </a>

            <div class="collapse <?= isActive('settings', $current_page) ? 'show' : '' ?>" id="settingsSubmenu">
                <div class="sidebar-submenu">
                    <a href="<?= $base_path ?>modules/settings/index.php" class="sidebar-link <?= isActive('settings/index.php', $current_page) ?>">
                        <span>ทั่วไป (General)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/settings/asset_types.php" class="sidebar-link <?= isActive('asset_types.php', $current_page) ?>">
                        <span>ประเภททรัพย์สิน (Types)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/settings/categories.php" class="sidebar-link <?= isActive('categories.php', $current_page) ?>">
                        <span>หมวดหมู่งานซ่อม (Cats)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/settings/locations.php" class="sidebar-link <?= isActive('locations.php', $current_page) ?>">
                        <span>สถานที่ (Locations)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/settings/departments.php" class="sidebar-link <?= isActive('departments.php', $current_page) ?>">
                        <span>แผนก (Departments)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/settings/suppliers.php" class="sidebar-link <?= isActive('suppliers.php', $current_page) ?>">
                        <span>ผู้ขาย (Suppliers)</span>
                    </a>
                    <a href="<?= $base_path ?>modules/logs/index.php" class="sidebar-link <?= isActive('logs', $current_page) ?>">
                        <span>บันทึกระบบ (Audit Logs)</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div style="height: 40px;"></div>
    </div>

    <div class="sidebar-footer">
        <div class="dropup">
            <a href="#" class="user-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="d-flex flex-column text-truncate" style="line-height: 1.1;">
                    <span class="fw-bold" style="font-size:0.8rem;"><?= mb_strimwidth($_SESSION['fullname'] ?? 'Guest', 0, 15, '..') ?></span>
                    <span class="small text-muted" style="font-size:0.65rem;"><?= strtoupper($_SESSION['role'] ?? '-') ?></span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark shadow small">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>โปรไฟล์ (Profile)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $base_path ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ (Logout)</a></li>
            </ul>
        </div>
    </div>
</div>