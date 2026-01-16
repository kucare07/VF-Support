<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$ticket_id = $_GET['id'];

// --- 1. Handle Form Actions (Update Status & Comment) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // กรณีตอบกลับ (Comment)
    if (isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
        $stmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $_SESSION['user_id'], trim($_POST['comment'])]);
    }

    // กรณีเปลี่ยนสถานะ (Update Status)
    if (isset($_POST['status'])) {
        $new_status = $_POST['status'];
        // ถ้าสถานะเป็น Closed ให้ใส่วันที่ปิดงานด้วย (ถ้ามี Field closed_at)
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ticket_id]);
    }

    // Refresh หน้าเพื่อแสดงข้อมูลใหม่
    header("Location: view.php?id=" . $ticket_id);
    exit();
}

// --- 2. Query Data ---
// ดึงข้อมูล Ticket
$sql = "SELECT t.*, u.fullname as requester_name, u.email, u.phone, 
        c.name as category_name, a.name as asset_name, a.asset_code,
        tech.fullname as tech_name
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN assets a ON t.asset_id = a.id
        LEFT JOIN users tech ON t.assigned_to = tech.id
        WHERE t.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo "ไม่พบใบงานนี้";
    exit();
}

// ดึง Comments
$sql_comments = "SELECT c.*, u.fullname, u.role 
                 FROM ticket_comments c 
                 LEFT JOIN users u ON c.user_id = u.id 
                 WHERE c.ticket_id = ? 
                 ORDER BY c.created_at ASC";
$stmt_c = $pdo->prepare($sql_comments);
$stmt_c->execute([$ticket_id]);
$comments = $stmt_c->fetchAll();

// Helper: สีสถานะ
function getStatusColor($status)
{
    return match ($status) {
        'new' => 'warning',
        'assigned' => 'primary',
        'pending' => 'danger',
        'resolved' => 'success',
        'closed' => 'secondary',
        default => 'light'
    };
}
?>

<?php require_once '../../includes/header.php'; ?>
<div class="d-flex" id="wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <div class="d-flex align-items-center w-100">
                <a href="index.php" class="btn btn-light btn-sm border me-2 shadow-sm" title="ย้อนกลับ">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button class="btn btn-light btn-sm border me-3" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-1 fw-bold text-secondary">รายละเอียดใบงาน #<?= str_pad($ticket['id'], 5, '0', STR_PAD_LEFT) ?></span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h4 class="fw-bold text-primary mb-0"><?= htmlspecialchars($ticket['subject']) ?></h4>
                                <span class="badge bg-<?= getStatusColor($ticket['status']) ?> fs-6 px-3 rounded-pill text-uppercase">
                                    <?= $ticket['status'] ?>
                                </span>
                            </div>
                            <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>

                            <div class="d-flex gap-3 text-muted small border-top pt-3">
                                <span><i class="bi bi-person me-1"></i> <?= $ticket['requester_name'] ?></span>
                                <span><i class="bi bi-folder me-1"></i> <?= $ticket['category_name'] ?></span>
                                <span><i class="bi bi-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted mb-3"><i class="bi bi-chat-dots me-2"></i>ประวัติการดำเนินการ / ตอบกลับ</h6>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($comments as $c):
                            $isMe = ($c['user_id'] == $_SESSION['user_id']);
                            $bgClass = $isMe ? 'bg-primary text-white ms-auto' : 'bg-white border text-dark me-auto';
                            $align = $isMe ? 'text-end' : 'text-start';
                        ?>
                            <div class="d-flex w-75 <?= $isMe ? 'justify-content-end align-self-end' : '' ?>">
                                <?php if (!$isMe): ?>
                                    <div class="flex-shrink-0 me-2">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                            <?= mb_substr($c['fullname'], 0, 1) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="p-3 rounded-3 shadow-sm <?= $bgClass ?>" style="min-width: 200px;">
                                    <div class="d-flex justify-content-between align-items-center mb-1 small <?= $isMe ? 'text-white-50' : 'text-muted' ?>">
                                        <span class="fw-bold"><?= $c['fullname'] ?></span>
                                        <span style="font-size: 0.7rem;"><?= date('d/m/y H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form method="POST" class="d-flex gap-2">
                                <textarea name="comment" class="form-control" rows="2" placeholder="พิมพ์ข้อความตอบกลับ..." required></textarea>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill"></i></button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold"><i class="bi bi-gear-fill me-2"></i>จัดการงาน (Actions)</div>
                        <div class="card-body">
                            <form method="POST">
                                <label class="form-label small fw-bold text-muted">อัปเดตสถานะงาน</label>
                                <div class="input-group mb-3">
                                    <select name="status" class="form-select">
                                        <option value="new" <?= $ticket['status'] == 'new' ? 'selected' : '' ?>>🟠 New (รอรับเรื่อง)</option>
                                        <option value="assigned" <?= $ticket['status'] == 'assigned' ? 'selected' : '' ?>>🔵 Assigned (กำลังทำ)</option>
                                        <option value="pending" <?= $ticket['status'] == 'pending' ? 'selected' : '' ?>>🔴 Pending (รออะไหล่)</option>
                                        <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>🟢 Resolved (เสร็จสิ้น)</option>
                                        <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>⚫ Closed (ปิดงาน)</option>
                                    </select>
                                    <button class="btn btn-outline-primary" type="submit">บันทึก</button>
                                </div>
                            </form>

                            <hr>
                            <a href="print.php?id=<?= $ticket['id'] ?>" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="bi bi-printer me-2"></i>พิมพ์ใบงาน
                            </a>
                            <a href="index.php" class="btn btn-light w-100">ย้อนกลับ</a>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle-fill me-2"></i>ข้อมูลเพิ่มเติม</div>
                        <div class="card-body small">
                            <div class="mb-3">
                                <label class="text-muted d-block">ผู้แจ้ง (Requester)</label>
                                <span class="fw-bold"><?= $ticket['requester_name'] ?></span><br>
                                <span class="text-muted"><?= $ticket['email'] ?> | <?= $ticket['phone'] ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted d-block">ทรัพย์สิน (Asset)</label>
                                <?php if ($ticket['asset_code']): ?>
                                    <a href="../asset/index.php?search=<?= $ticket['asset_code'] ?>" class="fw-bold text-decoration-none">
                                        <?= $ticket['asset_code'] ?> - <?= $ticket['asset_name'] ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">- ไม่ระบุ -</span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted d-block">ความเร่งด่วน</label>
                                <?php if ($ticket['priority'] == 'high' || $ticket['priority'] == 'critical'): ?>
                                    <span class="text-danger fw-bold text-uppercase"><i class="bi bi-exclamation-triangle"></i> <?= $ticket['priority'] ?></span>
                                <?php else: ?>
                                    <span class="text-dark text-uppercase"><?= $ticket['priority'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-0">
                                <label class="text-muted d-block">ช่างผู้รับผิดชอบ</label>
                                <span class="fw-bold text-primary">
                                    <i class="bi bi-tools me-1"></i> <?= $ticket['tech_name'] ?? 'ยังไม่ระบุ (Unassigned)' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Sidebar
    document.getElementById('menu-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar-wrapper').classList.toggle('active');
    });
</script>
<?php require_once '../../includes/footer.php'; ?>