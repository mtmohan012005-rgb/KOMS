<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_login();
if (!has_role('master') && !has_role('super_admin')) {
    $_SESSION['error_msg'] = "Unauthorized access.";
    redirect('/index.php');
}

$master_id = (int)($_SESSION['user_id'] ?? 0);

// Get the logged-in Master's dojo
$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

$dojo_id = $dojo ? (int)$dojo['id'] : null;

// Handle request approval / rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Security token mismatch. Please reload and try again.';
        redirect('/master/password_requests.php');
    }

    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    // Fetch the request
    $reqStmt = $pdo->prepare("SELECT pr.*, u.first_name, u.last_name, u.email, u.member_id, u.dob FROM password_reset_requests pr JOIN users u ON pr.user_id = u.id WHERE pr.id = ? LIMIT 1");
    $reqStmt->execute([$request_id]);
    $req = $reqStmt->fetch();

    if (!$req) {
        $_SESSION['error_msg'] = 'Password reset request not found.';
        redirect('/master/password_requests.php');
    }

    $student_id = (int)$req['user_id'];
    $student_name = trim($req['first_name'] . ' ' . $req['last_name']);

    if ($action === 'grant_change') {
        // Option 1: Reset password_change_count to 0, allowing student 1 new self-change
        $pdo->beginTransaction();
        try {
            $upUser = $pdo->prepare("UPDATE users SET password_change_count = 0, must_change_password = 0, updated_at = NOW() WHERE id = ?");
            $upUser->execute([$student_id]);

            $upReq = $pdo->prepare("UPDATE password_reset_requests SET status = 'approved', master_notes = 'Approved: Student granted 1 new self-service password change', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            $upReq->execute([$master_id, $request_id]);

            log_audit_action($pdo, $master_id, 'UPDATE', 'password_request', $request_id, "Master approved password change for student #{$student_id} ({$student_name})");
            $pdo->commit();

            $_SESSION['success_msg'] = "Approved! {$student_name} ({$req['member_id']}) has been granted 1 self-service password change.";
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Grant change error: ' . $e->getMessage());
            $_SESSION['error_msg'] = 'Failed to approve request. Please try again.';
        }
    } elseif ($action === 'reset_dob') {
        // Option 2: Reset password back to default Date of Birth (DD.MM.YYYY)
        $dobRaw = $req['dob'] ?? '';
        $dobFormatted = format_dob_password($dobRaw) ?: '01.01.2010';
        $newHash = password_hash($dobFormatted, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $upUser = $pdo->prepare("UPDATE users SET password_hash = ?, password_change_count = 0, must_change_password = 1, updated_at = NOW() WHERE id = ?");
            $upUser->execute([$newHash, $student_id]);

            $upReq = $pdo->prepare("UPDATE password_reset_requests SET status = 'approved', master_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            $upReq->execute(["Password reset to Date of Birth ({$dobFormatted})", $master_id, $request_id]);

            log_audit_action($pdo, $master_id, 'UPDATE', 'password_request', $request_id, "Master reset password to DOB for student #{$student_id} ({$student_name})");
            $pdo->commit();

            $_SESSION['success_msg'] = "Success! {$student_name}'s password has been reset to Date of Birth: {$dobFormatted}.";
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Reset DOB error: ' . $e->getMessage());
            $_SESSION['error_msg'] = 'Failed to reset password. Please try again.';
        }
    } elseif ($action === 'reject') {
        $notes = trim($_POST['reject_reason'] ?? 'Request declined by Master');
        $upReq = $pdo->prepare("UPDATE password_reset_requests SET status = 'rejected', master_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $upReq->execute([$notes, $master_id, $request_id]);

        log_audit_action($pdo, $master_id, 'UPDATE', 'password_request', $request_id, "Master rejected password change for student #{$student_id}");
        $_SESSION['success_msg'] = "Password request for {$student_name} was rejected.";
    }

    redirect('/master/password_requests.php');
}

$page_title = 'Student Password Reset Requests';

// Status filter
$filter = $_GET['filter'] ?? 'pending';
$valid_filters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $valid_filters, true)) {
    $filter = 'pending';
}

$query = "
    SELECT pr.*, u.first_name, u.last_name, u.email, u.member_id, u.dob, u.phone, u.password_change_count,
           d.name AS dojo_name, m.first_name AS master_first, m.last_name AS master_last
    FROM password_reset_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN dojos d ON pr.dojo_id = d.id
    LEFT JOIN users m ON pr.reviewed_by = m.id
";

$params = [];
if ($filter !== 'all') {
    $query .= " WHERE pr.status = ?";
    $params[] = $filter;
}
$query .= " ORDER BY CASE WHEN pr.status = 'pending' THEN 0 ELSE 1 END, pr.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Counts for tabs
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'")->fetchColumn();
$approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'approved'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'rejected'")->fetchColumn();

require_once '../includes/header.php';
?>

<style>
.requests-wrap{max-width:1180px;margin:1.5rem auto 2.5rem}
.requests-hero{padding:1.8rem 2rem;border-radius:24px;color:#fff;background:linear-gradient(135deg,#080808,#1b1b1b 58%,#5a0808);box-shadow:0 22px 50px rgba(0,0,0,.16)}
.requests-kicker{font-size:.7rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#ffd15c}
.requests-title{margin:.35rem 0;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900}
.requests-sub{max-width:760px;color:rgba(255,255,255,.7)}
.nav-pills-koms{display:flex;gap:8px;margin-top:1.5rem;flex-wrap:wrap}
.nav-pills-koms .nav-link{border-radius:999px;padding:6px 16px;font-size:12px;font-weight:700;color:#aaa;background:rgba(255,255,255,.06);text-decoration:none;transition:all .2s ease}
.nav-pills-koms .nav-link:hover{color:#fff;background:rgba(255,255,255,.12)}
.nav-pills-koms .nav-link.active{background:#ffd15c;color:#111}
.request-panel{margin-top:1.2rem;border-radius:20px;background:#fff;box-shadow:0 15px 38px rgba(17,24,39,.08);overflow:hidden}
.request-head{padding:1.15rem 1.25rem;border-bottom:1px solid #eee}
.request-avatar{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#222;color:#ffd15c;font-weight:900;font-size:14px}
.member-id{display:inline-flex;padding:.25rem .55rem;border-radius:999px;background:#fff4cf;color:#705200;font-size:.72rem;font-weight:800;font-family:monospace}
.dob-badge{display:inline-flex;padding:.2rem .5rem;border-radius:6px;background:#f0f4f8;color:#2c3e50;font-size:.75rem;font-weight:700;font-family:monospace}
.request-meta{font-size:.78rem;color:#777}
.request-actions{display:flex;gap:.45rem;flex-wrap:wrap}
.empty-state{text-align:center;padding:4rem 1rem;color:#777}
.empty-state i{font-size:2.2rem;margin-bottom:.9rem;color:#aaa}
@media(max-width:767px){.requests-hero{padding:1.4rem}.request-table{min-width:850px}}
</style>

<div class="requests-wrap">
    <section class="requests-hero">
        <div class="requests-kicker">Mass Dragon Dojo • Security Administration</div>
        <h1 class="requests-title">Student Password Requests</h1>
        <p class="requests-sub">
            Students are limited to <strong>1 self-service password change</strong> from their initial Date of Birth password. 
            Any subsequent password change requests are routed here for Master authorization.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
            <a href="requests.php" class="btn btn-outline-light btn-sm"><i class="fas fa-user-plus me-1"></i>Join Requests</a>
            <a href="students.php" class="btn btn-outline-light btn-sm"><i class="fas fa-users me-1"></i>Students Roster</a>
        </div>

        <!-- Filter Pills -->
        <div class="nav-pills-koms">
            <a href="?filter=pending" class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock me-1"></i>Pending (<?= $pendingCount ?>)
            </a>
            <a href="?filter=approved" class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>">
                <i class="fas fa-check-circle me-1"></i>Approved (<?= $approvedCount ?>)
            </a>
            <a href="?filter=rejected" class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>">
                <i class="fas fa-times-circle me-1"></i>Rejected (<?= $rejectedCount ?>)
            </a>
            <a href="?filter=all" class="nav-link <?= $filter === 'all' ? 'active' : '' ?>">
                All Requests (<?= $pendingCount + $approvedCount + $rejectedCount ?>)
            </a>
        </div>
    </section>

    <section class="request-panel">
        <div class="request-head d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1 text-capitalize"><?= htmlspecialchars($filter) ?> Password Requests</h4>
                <div class="text-muted small"><?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?> displayed</div>
            </div>
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                    <i class="fas fa-bell me-1"></i> <?= $pendingCount ?> Awaiting Master Review
                </span>
            <?php else: ?>
                <span class="badge bg-success px-3 py-2">
                    <i class="fas fa-check me-1"></i> All Caught Up
                </span>
            <?php endif; ?>
        </div>

        <?php if ($requests): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0 request-table">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>User ID &amp; Contact</th>
                            <th>Date of Birth (Default Pass)</th>
                            <th>Reason for Reset</th>
                            <th>Requested On</th>
                            <th>Status &amp; Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $req):
                        $full_name = trim($req['first_name'] . ' ' . $req['last_name']);
                        $initials = strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1));
                        $dobDisplay = format_dob_password($req['dob'] ?? '') ?: 'N/A';
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="request-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($full_name) ?></div>
                                        <div class="small text-muted">User #<?= (int)$req['user_id'] ?> &bull; Changes used: <?= (int)$req['password_change_count'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><span class="member-id"><?= htmlspecialchars($req['member_id'] ?: 'N/A') ?></span></div>
                                <div class="small text-muted mt-1"><?= htmlspecialchars($req['email']) ?></div>
                                <?php if (!empty($req['phone'])): ?>
                                    <div class="request-meta"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($req['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dob-badge" title="Default one-time password format">
                                    <i class="fas fa-cake-candles me-1 text-danger"></i> <?= htmlspecialchars($dobDisplay) ?>
                                </span>
                            </td>
                            <td style="max-width: 250px;">
                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($req['reason'] ?: 'No specific reason given') ?></div>
                                <?php if (!empty($req['master_notes'])): ?>
                                    <div class="small text-muted mt-1 bg-light p-1 rounded border">
                                        <em>Master note: <?= htmlspecialchars($req['master_notes']) ?></em>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small"><?= date('M j, Y', strtotime($req['created_at'])) ?></div>
                                <div class="request-meta"><?= date('g:i A', strtotime($req['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <div class="request-actions">
                                        <!-- Option 1: Grant 1 new self-service password change -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Allow student <?= htmlspecialchars($full_name) ?> to change password directly once?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                            <button class="btn btn-sm btn-success" type="submit" name="action" value="grant_change" title="Allows student to choose their new password in profile">
                                                <i class="fas fa-key me-1"></i>Grant 1 Change
                                            </button>
                                        </form>

                                        <!-- Option 2: Reset directly to default DOB password -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Reset password back to DOB (<?= $dobDisplay ?>) for <?= htmlspecialchars($full_name) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                            <button class="btn btn-sm btn-primary" type="submit" name="action" value="reset_dob" title="Reset password to student Date of Birth: <?= $dobDisplay ?>">
                                                <i class="fas fa-rotate-left me-1"></i>Reset to DOB
                                            </button>
                                        </form>

                                        <!-- Option 3: Reject -->
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm<?= $req['id'] ?>">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </div>

                                    <div class="collapse mt-2" id="rejectForm<?= $req['id'] ?>">
                                        <form method="POST" class="p-2 border rounded bg-light">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="reject_reason" class="form-control form-control-sm mb-2" placeholder="Rejection reason..." required>
                                            <button type="submit" class="btn btn-danger btn-sm">Confirm Reject</button>
                                        </form>
                                    </div>
                                <?php elseif ($req['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                        <i class="fas fa-check-circle me-1"></i> Approved
                                    </span>
                                    <?php if (!empty($req['reviewed_at'])): ?>
                                        <div class="small text-muted" style="font-size: 10px;">
                                            <?= date('M j, g:i A', strtotime($req['reviewed_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                        <i class="fas fa-times-circle me-1"></i> Rejected
                                    </span>
                                    <?php if (!empty($req['reviewed_at'])): ?>
                                        <div class="small text-muted" style="font-size: 10px;">
                                            <?= date('M j, g:i A', strtotime($req['reviewed_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shield-halved d-block"></i>
                <h5>No <?= htmlspecialchars($filter) ?> password requests</h5>
                <p class="mb-0">When students reach their 1-time change limit and request a reset, their submissions will show here.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
