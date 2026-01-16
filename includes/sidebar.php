<?php
// ดึงชื่อไฟล์ปัจจุบันมาเก็บไว้เช็ค Active
$current_page = $_SERVER['PHP_SELF'];
?>

<div class="border-end" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom border-secondary">
        <i class="bi bi-shield-lock-fill text-primary"></i> <span class="ms-2">IT Service</span>
    </div>
    
    <div class="list-group list-group-flush mt-3">
        <div class="px-3 text-uppercase text-secondary small mb-2 fw-bold" style="font-size: 0.7rem;">General</div>
        
        <a href="/it_support/index.php" class="list-group-item list-group-item-action <?= $current_page == '/it_support/index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <a href="/it_support/modules/reports/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/reports') !== false ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> รายงาน (Reports)
        </a>
        <a href="/it_support/modules/calendar/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/calendar') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i> ปฏิทินงาน (Calendar)
        </a>
        <a href="/it_support/modules/kb/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/kb') !== false ? 'active' : '' ?>">
            <i class="bi bi-book"></i> ฐานความรู้ (KB)
        </a>

        <div class="px-3 text-uppercase text-secondary small mb-2 mt-3 fw-bold" style="font-size: 0.7rem;">Modules</div>
        
        <a href="/it_support/modules/helpdesk/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/helpdesk') !== false ? 'active' : '' ?>">
            <i class="bi bi-tools"></i> งานซ่อมบำรุง
        </a>
        <a href="/it_support/modules/borrow/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/borrow') !== false ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> ระบบยืม-คืน
        </a>
        <a href="/it_support/modules/asset/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/asset') !== false ? 'active' : '' ?>">
            <i class="bi bi-pc-display"></i> ทะเบียนทรัพย์สิน
        </a>
        <a href="/it_support/modules/inventory/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/inventory') !== false ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> คลังวัสดุ
        </a>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <div class="px-3 text-uppercase text-secondary small mb-2 mt-3 fw-bold" style="font-size: 0.7rem;">Admin Zone</div>
            
            <a href="/it_support/modules/user/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/user') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i> จัดการผู้ใช้งาน
            </a>
            <a href="/it_support/modules/settings/index.php" class="list-group-item list-group-item-action <?= strpos($current_page, 'modules/settings') !== false ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> ตั้งค่าระบบ
            </a>
        <?php endif; ?>
    </div>
    
    <div class="mt-auto p-3 border-top border-secondary">
        <div class="d-flex align-items-center mb-3">
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 35px; height: 35px;">
                <?= mb_substr($_SESSION['fullname'] ?? 'A', 0, 1) ?>
            </div>
            <div class="lh-1 text-white">
                <div class="small fw-bold text-truncate" style="max-width: 120px;"><?= $_SESSION['fullname'] ?? 'Guest' ?></div>
                <small class="text-white-50" style="font-size: 0.75rem;"><?= ucfirst($_SESSION['role'] ?? 'User') ?></small>
            </div>
        </div>
        <a href="/it_support/logout.php" class="btn btn-danger w-100 btn-sm">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</div>