<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];

$sql = "SELECT a.*, at.name as type_name, l.name as location_name, u.fullname as owner_name, s.name as supplier_name
        FROM assets a
        LEFT JOIN asset_types at ON a.asset_type_id = at.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.current_user_id = u.id
        LEFT JOIN suppliers s ON a.supplier_id = s.id
        WHERE a.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) { echo "ไม่พบข้อมูล"; exit(); }
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm"><i class="bi bi-arrow-left"></i></a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
                <span class="ms-1 fw-bold text-secondary">รายละเอียดครุภัณฑ์</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h4 class="fw-bold text-primary mb-1"><?= $asset['asset_code'] ?></h4>
                                    <span class="text-muted"><?= $asset['name'] ?></span>
                                </div>
                                <span class="badge bg-light text-dark border fs-6"><?= $asset['type_name'] ?></span>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">ยี่ห้อ (Brand)</small>
                                    <span class="fw-bold"><?= $asset['brand'] ?: '-' ?></span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">รุ่น (Model)</small>
                                    <span class="fw-bold"><?= $asset['model'] ?: '-' ?></span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Serial Number</small>
                                    <span class="fw-bold"><?= $asset['serial_number'] ?: '-' ?></span>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-cpu me-2"></i>Specification</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">CPU</small>
                                    <span><?= $asset['spec_cpu'] ?: '-' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">RAM</small>
                                    <span><?= $asset['spec_ram'] ?: '-' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Storage</small>
                                    <span><?= $asset['spec_storage'] ?: '-' ?></span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">OS / License</small>
                                    <span><?= $asset['os_license'] ?: '-' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold">สถานะปัจจุบัน</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">สถานะ</small>
                                <?php if($asset['status'] == 'active'): ?>
                                    <span class="badge bg-success">ใช้งานปกติ</span>
                                <?php elseif($asset['status'] == 'repair'): ?>
                                    <span class="badge bg-warning text-dark">ส่งซ่อม</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= $asset['status'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">ผู้ถือครอง (Owner)</small>
                                <div class="fw-bold text-primary"><i class="bi bi-person me-1"></i> <?= $asset['owner_name'] ?: 'ว่าง / ส่วนกลาง' ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">สถานที่ (Location)</small>
                                <div><i class="bi bi-geo-alt me-1"></i> <?= $asset['location_name'] ?: '-' ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold">ข้อมูลการจัดซื้อ</div>
                        <div class="card-body">
                             <div class="mb-2">
                                <small class="text-muted d-block">วันที่ซื้อ</small>
                                <span><?= $asset['purchase_date'] ? date('d/m/Y', strtotime($asset['purchase_date'])) : '-' ?></span>
                            </div>
                             <div class="mb-2">
                                <small class="text-muted d-block">หมดประกัน</small>
                                <span><?= $asset['warranty_expire'] ? date('d/m/Y', strtotime($asset['warranty_expire'])) : '-' ?></span>
                            </div>
                             <div class="mb-2">
                                <small class="text-muted d-block">ราคา</small>
                                <span><?= number_format($asset['price'], 2) ?> บาท</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <a href="edit.php?id=<?= $asset['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil me-2"></i>แก้ไขข้อมูล</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>