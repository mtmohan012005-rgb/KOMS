<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)($_SESSION['user_id'] ?? 0);

// Get the logged-in Master's dojo.
$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = "You need to register your dojo before managing student requests.";
    redirect('/master/register_dojo.php');
}

$dojo_id = (int)$dojo['id'];

// Approve or reject a student join request.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Security token mismatch. Please reload and try again.';
        redirect('/master/requests.php');
    }

    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if (!in_array($action, ['approve', 'reject'], true)) {
        $_SESSION['error_msg'] = 'Invalid request action.';
        redirect('/master/requests.php');
    }

    $new_status = $action === 'approve' ? 'approved' : 'rejected';

    try {
        // Only update pending requests belonging to this Master's dojo.
        $update = $pdo->prepare(
            "UPDATE dojo_memberships
             SET status = ?, joined_at = CASE WHEN ? = 'approved' THEN NOW() ELSE NULL END
             WHERE id = ? AND dojo_id = ? AND status = 'pending'"
        );
        $update->execute([$new_status, $new_status, $request_id, $dojo_id]);

        if ($update->rowCount() > 0) {
            $_SESSION['success_msg'] = $action === 'approve'
                ? 'Student request approved successfully.'
                : 'Student request rejected successfully.';

            try {
                log_audit_action(
                    $pdo,
                    $master_id,
                    'UPDATE',
                    'memberships',
                    $request_id,
                    'Master ' . $new_status . ' student join request'
                );
            } catch (Throwable $audit_error) {
                error_log('KOMS audit log failed for student request: ' . $audit_error->getMessage());
            }
        } else {
            $_SESSION['error_msg'] = 'The request was not found or has already been processed.';
        }
    } catch (Throwable $e) {
        error_log('KOMS student request update error: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Unable to update the student request right now.';
    }

    redirect('/master/requests.php');
}

$page_title = 'Student Join Requests';

// Latest pending requests for this Master's dojo.
$stmt = $pdo->prepare(
    "SELECT m.id, m.student_id, m.created_at,
            u.first_name, u.last_name, u.email, u.phone, u.member_id
     FROM dojo_memberships m
     JOIN users u ON m.student_id = u.id
     WHERE m.dojo_id = ? AND m.status = 'pending'
     ORDER BY m.created_at DESC"
);
$stmt->execute([$dojo_id]);
$requests = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
.requests-wrap{max-width:1180px;margin:1.5rem auto 2.5rem}.requests-hero{padding:1.8rem 2rem;border-radius:24px;color:#fff;background:linear-gradient(135deg,#080808,#1b1b1b 58%,#5a0808);box-shadow:0 22px 50px rgba(0,0,0,.16)}.requests-kicker{font-size:.7rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#ffd15c}.requests-title{margin:.35rem 0;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900}.requests-sub{max-width:760px;color:rgba(255,255,255,.7)}.request-panel{margin-top:1rem;border-radius:20px;background:#fff;box-shadow:0 15px 38px rgba(17,24,39,.08);overflow:hidden}.request-head{padding:1.15rem 1.25rem;border-bottom:1px solid #eee}.request-body{padding:0}.request-avatar{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#f0f0f0;color:#555;font-weight:900}.member-id{display:inline-flex;margin-top:.28rem;padding:.25rem .55rem;border-radius:999px;background:#fff4cf;color:#705200;font-size:.68rem;font-weight:800}.request-meta{font-size:.78rem;color:#777}.request-actions{display:flex;gap:.45rem;flex-wrap:wrap}.empty-state{text-align:center;padding:4rem 1rem;color:#777}.empty-state i{font-size:2.2rem;margin-bottom:.9rem;color:#aaa}@media(max-width:767px){.requests-hero{padding:1.4rem}.request-table{min-width:850px}}
</style>

<div class="requests-wrap">
    <section class="requests-hero">
        <div class="requests-kicker">Mass Dragon Dojo • Student Management</div>
        <h1 class="requests-title">Student Join Requests</h1>
        <p class="requests-sub">Review students who requested to join <?= htmlspecialchars($dojo['name']) ?>. Approving a request makes the student an active dojo member.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
            <a href="students.php" class="btn btn-outline-light"><i class="fas fa-users me-2"></i>Students</a>
        </div>
    </section>

    <section class="request-panel">
        <div class="request-head d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Pending Requests</h4>
                <div class="text-muted small"><?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?> waiting for review</div>
            </div>
            <span class="badge text-bg-warning"><?= count($requests) ?> Pending</span>
        </div>

        <?php if ($requests): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0 request-table">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Contact</th>
                            <th>KOMS User ID</th>
                            <th>Requested</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $request):
                        $full_name = trim($request['first_name'] . ' ' . $request['last_name']);
                        $initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="request-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($full_name) ?></div>
                                        <div class="small text-muted">Database User #<?= (int)$request['student_id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($request['email']) ?></div>
                                <div class="request-meta"><?= htmlspecialchars($request['phone'] ?: 'No phone number') ?></div>
                            </td>
                            <td><span class="member-id"><?= htmlspecialchars($request['member_id'] ?: 'Not assigned') ?></span></td>
                            <td><?= date('M j, Y - g:i A', strtotime($request['created_at'])) ?></td>
                            <td>
                                <div class="request-actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                        <button class="btn btn-sm btn-success" type="submit" name="action" value="approve"><i class="fas fa-check me-1"></i>Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="reject" onclick="return confirm('Reject this student join request?');"><i class="fas fa-xmark me-1"></i>Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-clock d-block"></i>
                <h5>No pending requests</h5>
                <p class="mb-0">New student join requests for your dojo will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
