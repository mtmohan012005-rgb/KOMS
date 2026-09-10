<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage tournament registrations.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reg_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Invalid form submission.';
        redirect('/master/tournaments.php');
    }

    $reg_id = (int)$_POST['reg_id'];
    $action = $_POST['action'];
    $new_status = $action === 'select' ? 'selected' : ($action === 'reject' ? 'rejected' : null);

    if (!$new_status) {
        $_SESSION['error_msg'] = 'Invalid tournament action.';
        redirect('/master/tournaments.php');
    }

    $stmt = $pdo->prepare("UPDATE tournament_registrations SET status = ?, reviewed_by = ? WHERE id = ? AND dojo_id = ? AND status = 'pending_review'");
    $stmt->execute([$new_status, $_SESSION['user_id'], $reg_id, $dojo_id]);

    if ($stmt->rowCount() > 0) {
        log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'tournament_registrations', $reg_id, "Tournament registration marked {$new_status}");
        $_SESSION['success_msg'] = $new_status === 'selected'
            ? 'Student selected for the tournament.'
            : 'Student application rejected.';
    } else {
        $_SESSION['error_msg'] = 'Registration not found or already reviewed.';
    }

    redirect('/master/tournaments.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE dojo_id = ? AND status = 'pending_review'");
$stmt->execute([$dojo_id]);
$pending_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE dojo_id = ? AND status = 'selected'");
$stmt->execute([$dojo_id]);
$selected_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE dojo_id = ? AND status = 'rejected'");
$stmt->execute([$dojo_id]);
$rejected_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE status IN ('open','upcoming','published') AND event_date >= CURDATE()");
$stmt->execute();
$upcoming_tournaments = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT r.id, r.status, r.created_at,
           t.name AS tournament_name, t.event_date, t.venue, t.status AS tournament_status,
           u.id AS student_id, u.member_id, u.first_name, u.last_name, u.email
    FROM tournament_registrations r
    JOIN tournaments t ON r.tournament_id = t.id
    JOIN users u ON r.student_id = u.id
    WHERE r.dojo_id = ?
    ORDER BY CASE r.status WHEN 'pending_review' THEN 0 WHEN 'selected' THEN 1 ELSE 2 END,
             t.event_date ASC, r.created_at DESC
");
$stmt->execute([$dojo_id]);
$registrations = $stmt->fetchAll();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $needle = mb_strtolower($search);
    $registrations = array_values(array_filter($registrations, static function ($r) use ($needle) {
        $haystack = mb_strtolower(
            ($r['first_name'] ?? '') . ' ' .
            ($r['last_name'] ?? '') . ' ' .
            ($r['member_id'] ?? '') . ' ' .
            ($r['email'] ?? '') . ' ' .
            ($r['tournament_name'] ?? '') . ' ' .
            ($r['venue'] ?? '')
        );
        return str_contains($haystack, $needle);
    }));
}

$page_title = 'Tournament Management';
require_once '../includes/header.php';
?>

<style>
    .tour-wrap{max-width:1200px;margin:1.5rem auto}
    .tour-hero{border-radius:24px;padding:1.7rem 2rem;color:#fff;background:linear-gradient(135deg,#080808,#1b1b1b 58%,#5c0b0b);box-shadow:0 22px 55px rgba(0,0,0,.16)}
    .tour-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd25f}
    .tour-title{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;margin:.35rem 0 .3rem}
    .tour-stat{background:#fff;border:0;border-radius:18px;padding:1.2rem 1.25rem;box-shadow:0 12px 35px rgba(17,24,39,.07);height:100%}
    .tour-stat .num{font-size:1.8rem;font-weight:900}
    .tour-card{border:0;border-radius:20px;overflow:hidden;box-shadow:0 14px 38px rgba(17,24,39,.08)}
    .tour-card .card-header{background:#fff;border:0;padding:1.1rem 1.25rem}
    .tour-table th{font-size:.76rem;text-transform:uppercase;letter-spacing:.06em;color:#777;white-space:nowrap}
    .tour-table td{padding-top:1rem;padding-bottom:1rem}
    .status{border-radius:999px;padding:.42rem .7rem;font-size:.72rem;font-weight:800;text-transform:uppercase;white-space:nowrap}
    .status-pending{background:#fff0c2;color:#6f5100}.status-selected{background:#dff7e8;color:#146c35}.status-rejected{background:#ffe1e1;color:#9a1c1c}
    .student-name{font-weight:800}.student-email{font-size:.76rem;color:#888}.member-pill{display:inline-block;margin-top:.25rem;background:#f3f3f3;border-radius:999px;padding:.18rem .48rem;font-size:.68rem;font-weight:800;color:#444}
    .search-box{border-radius:12px;padding:.65rem .8rem;border:1px solid #ddd;min-width:230px}
    @media(max-width:768px){.tour-hero{padding:1.35rem}.tour-wrap{margin:1rem auto}.tour-table{min-width:1050px}.search-box{width:100%;min-width:0}}
</style>

<div class="tour-wrap">
    <section class="tour-hero mb-4">
        <div class="tour-kicker">Master Control • Tournament Desk</div>
        <div class="tour-title">Tournament Management</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Review your students' applications and decide who will represent <?= htmlspecialchars($dojo['name']) ?>.</p>
    </section>

    <?php if (!empty($_SESSION['success_msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($_SESSION['success_msg']) ?></div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($_SESSION['error_msg']) ?></div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="tour-stat"><div class="text-muted small fw-bold text-uppercase">Upcoming Tournaments</div><div class="num mt-1"><?= $upcoming_tournaments ?></div><div class="small text-muted">Events available in KOMS</div></div></div>
        <div class="col-md-3"><div class="tour-stat"><div class="text-muted small fw-bold text-uppercase">Pending Review</div><div class="num text-warning mt-1"><?= $pending_count ?></div><div class="small text-muted">Applications waiting for your decision</div></div></div>
        <div class="col-md-3"><div class="tour-stat"><div class="text-muted small fw-bold text-uppercase">Selected</div><div class="num text-success mt-1"><?= $selected_count ?></div><div class="small text-muted">Students chosen to represent the dojo</div></div></div>
        <div class="col-md-3"><div class="tour-stat"><div class="text-muted small fw-bold text-uppercase">Rejected</div><div class="num text-danger mt-1"><?= $rejected_count ?></div><div class="small text-muted">Applications not selected</div></div></div>
    </div>

    <div class="card tour-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><div class="small text-muted fw-bold text-uppercase">Applications</div><h5 class="mb-0 mt-1">Student Tournament Registrations</h5></div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex gap-2">
                    <input type="search" class="search-box" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search student, KOMS ID, tournament...">
                    <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-search"></i></button>
                    <?php if ($search !== ''): ?><a href="tournaments.php" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
                </form>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tour-table">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th><th>Tournament</th><th>Event</th><th>Venue</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($registrations as $r): ?>
                        <tr>
                            <td>
                                <div class="student-name"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
                                <div class="student-email"><?= htmlspecialchars($r['email']) ?></div>
                                <?php if (!empty($r['member_id'])): ?><span class="member-pill">KOMS ID: <?= htmlspecialchars($r['member_id']) ?></span><?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($r['tournament_name']) ?></strong><div class="small text-muted"><?= htmlspecialchars(ucwords(str_replace('_',' ',$r['tournament_status']))) ?></div></td>
                            <td><?= date('M j, Y', strtotime($r['event_date'])) ?></td>
                            <td><?= htmlspecialchars($r['venue']) ?></td>
                            <td>
                                <?php $status_class = $r['status']==='pending_review' ? 'status-pending' : ($r['status']==='selected' ? 'status-selected' : 'status-rejected'); ?>
                                <span class="status <?= $status_class ?>"><?= htmlspecialchars(str_replace('_',' ',$r['status'])) ?></span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending_review'): ?>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="reg_id" value="<?= (int)$r['id'] ?>">
                                        <button name="action" value="select" class="btn btn-sm btn-success" onclick="return confirm('Select this student for the tournament?')"><i class="fas fa-check me-1"></i>Select</button>
                                        <button name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this tournament application?')"><i class="fas fa-times me-1"></i>Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted"><i class="fas fa-check-circle me-1"></i>Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="6" class="text-center py-5"><i class="fas fa-trophy fa-2x text-muted mb-2"></i><div class="fw-bold">No registrations found</div><div class="small text-muted"><?= $search !== '' ? 'Try another search term.' : 'Tournament applications from your students will appear here.' ?></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
