<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT dm.dojo_id, d.name AS dojo_name FROM dojo_memberships dm INNER JOIN dojos d ON d.id = dm.dojo_id WHERE dm.student_id = ? AND dm.status = 'approved' LIMIT 1");
$stmt->execute([$user_id]);
$membership = $stmt->fetch();
$dojo_id = $membership ? (int)$membership['dojo_id'] : null;
$dojo_name = $membership['dojo_name'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Invalid form submission. Please try again.';
    } elseif (!$dojo_id) {
        $_SESSION['error_msg'] = 'You must join and receive approval for a dojo before registering.';
    } else {
        $tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);

        if (!$tournament_id) {
            $_SESSION['error_msg'] = 'Invalid tournament selected.';
        } else {
            $t_stmt = $pdo->prepare("SELECT id, name FROM tournaments WHERE id = ? AND status IN ('published', 'registration_open') AND registration_deadline >= CURRENT_DATE LIMIT 1");
            $t_stmt->execute([$tournament_id]);
            $tournament = $t_stmt->fetch();

            if (!$tournament) {
                $_SESSION['error_msg'] = 'This tournament is no longer open for registration.';
            } else {
                $check = $pdo->prepare("SELECT id FROM tournament_registrations WHERE tournament_id = ? AND student_id = ? LIMIT 1");
                $check->execute([$tournament_id, $user_id]);

                if ($check->fetch()) {
                    $_SESSION['error_msg'] = 'You are already registered for this tournament.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO tournament_registrations (tournament_id, student_id, dojo_id, status) VALUES (?, ?, ?, 'pending_review')");
                    $insert->execute([$tournament_id, $user_id, $dojo_id]);
                    $_SESSION['success_msg'] = 'Registration submitted successfully for ' . $tournament['name'] . '. Your Master will review it.';
                    redirect('/student/tournaments.php');
                }
            }
        }
    }
}

$page_title = 'Tournaments';
require_once '../includes/header.php';
$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT id, name, description, event_date, venue, registration_deadline, status FROM tournaments WHERE status IN ('published', 'registration_open') AND registration_deadline >= CURRENT_DATE ORDER BY event_date ASC");
$available = $stmt->fetchAll();

$reg_stmt = $pdo->prepare("SELECT r.id AS reg_id, r.status AS reg_status, r.tournament_id, t.name, t.description, t.event_date, t.venue, t.registration_deadline, t.status AS tournament_status FROM tournament_registrations r INNER JOIN tournaments t ON r.tournament_id = t.id WHERE r.student_id = ? ORDER BY t.event_date DESC, r.id DESC");
$reg_stmt->execute([$user_id]);
$my_regs = $reg_stmt->fetchAll();
$registered_ids = array_map('intval', array_column($my_regs, 'tournament_id'));
?>

<style>
.tourney-hero{position:relative;overflow:hidden;border-radius:24px;padding:32px;margin-bottom:24px;color:#fff;background:radial-gradient(circle at 85% 15%,rgba(220,38,38,.34),transparent 28%),radial-gradient(circle at 15% 80%,rgba(212,175,55,.18),transparent 30%),linear-gradient(135deg,#080808 0%,#141414 55%,#220909 100%);border:1px solid rgba(212,175,55,.22);box-shadow:0 20px 50px rgba(0,0,0,.18)}
.tourney-hero::after{content:'';position:absolute;width:220px;height:220px;border-radius:50%;right:-70px;bottom:-90px;border:1px solid rgba(255,255,255,.1)}
.tourney-kicker{color:#d4af37;text-transform:uppercase;letter-spacing:.14em;font-size:.75rem;font-weight:800;margin-bottom:8px}
.tourney-hero h1{font-weight:800;margin-bottom:8px}.tourney-hero p{max-width:720px;color:rgba(255,255,255,.72);margin-bottom:0}
.dojo-chip{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:9px 13px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);font-size:.85rem}
.tourney-card{height:100%;border:1px solid rgba(0,0,0,.07);border-radius:20px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}.tourney-card:hover{transform:translateY(-4px);box-shadow:0 18px 36px rgba(0,0,0,.10);border-color:rgba(220,38,38,.22)}
.tourney-topline{height:5px;background:linear-gradient(90deg,#b91c1c,#d4af37)}.tourney-card .card-body{padding:24px}
.meta-box{background:#f8f9fa;border-radius:14px;padding:12px;height:100%}.meta-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#6c757d;font-weight:700}.meta-value{font-weight:700;margin-top:4px}
.status-pill{border-radius:999px;padding:7px 10px;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.empty-state{border:1px dashed #ced4da;border-radius:18px;padding:40px 20px;text-align:center;color:#6c757d;background:#fff}.table-wrap{border-radius:18px;overflow:hidden;border:1px solid rgba(0,0,0,.06)}.table-wrap table{margin-bottom:0}.section-title{font-weight:800}
</style>

<div class="container-fluid py-3">
    <section class="tourney-hero">
        <div class="tourney-kicker">KOMS • Competition Desk</div>
        <h1><i class="fas fa-trophy me-2"></i>Tournaments</h1>
        <p>Discover upcoming competitions, submit your registration, and track the review status from one place.</p>
        <?php if ($dojo_id): ?>
            <div class="dojo-chip"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($dojo_name) ?></div>
        <?php else: ?>
            <div class="dojo-chip"><i class="fas fa-info-circle"></i> No approved dojo membership</div>
        <?php endif; ?>
    </section>

    <?php if (!empty($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error_msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <ul class="nav nav-pills gap-2 mb-4" id="tournamentTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#available" type="button"><i class="fas fa-calendar-check me-2"></i>Available</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#my-regs" type="button"><i class="fas fa-clipboard-list me-2"></i>My Registrations <span class="badge text-bg-dark ms-1"><?= count($my_regs) ?></span></button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="available" role="tabpanel">
            <div class="mb-3"><h3 class="section-title mb-1">Upcoming tournaments</h3><p class="text-muted mb-0">Registration closes according to each event deadline.</p></div>
            <div class="row g-4">
                <?php $shown_available = 0; foreach ($available as $t): if (in_array((int)$t['id'], $registered_ids, true)) continue; $shown_available++; ?>
                    <div class="col-xl-4 col-lg-6">
                        <div class="card tourney-card shadow-sm"><div class="tourney-topline"></div><div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-3 align-items-start mb-3"><h4 class="h5 fw-bold mb-0"><?= htmlspecialchars($t['name']) ?></h4><span class="badge bg-success status-pill">Open</span></div>
                            <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($t['venue']) ?></p>
                            <p class="small text-secondary flex-grow-1 mb-4"><?= nl2br(htmlspecialchars($t['description'] ?? 'Tournament details will be shared by the organizers.')) ?></p>
                            <div class="row g-2 mb-4">
                                <div class="col-6"><div class="meta-box"><div class="meta-label">Event</div><div class="meta-value"><?= date('M j, Y', strtotime($t['event_date'])) ?></div></div></div>
                                <div class="col-6"><div class="meta-box"><div class="meta-label">Deadline</div><div class="meta-value text-danger"><?= date('M j, Y', strtotime($t['registration_deadline'])) ?></div></div></div>
                            </div>
                            <?php if ($dojo_id): ?>
                                <form method="POST" action="" onsubmit="return confirm('Submit your registration for this tournament?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"><input type="hidden" name="register" value="1"><input type="hidden" name="tournament_id" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="btn btn-danger w-100 fw-semibold"><i class="fas fa-paper-plane me-2"></i>Register Now</button>
                                </form>
                            <?php else: ?><button class="btn btn-outline-secondary w-100" disabled><i class="fas fa-lock me-2"></i>Join a Dojo First</button><?php endif; ?>
                        </div></div>
                    </div>
                <?php endforeach; ?>
                <?php if ($shown_available === 0): ?><div class="col-12"><div class="empty-state"><i class="fas fa-trophy fa-2x mb-3"></i><h4 class="fw-bold">No new tournaments right now</h4><p class="mb-0">Your available competitions will appear here when registration opens.</p></div></div><?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="my-regs" role="tabpanel">
            <div class="mb-3"><h3 class="section-title mb-1">My registrations</h3><p class="text-muted mb-0">Track every tournament application and review decision.</p></div>
            <?php if (!empty($my_regs)): ?>
                <div class="table-responsive table-wrap bg-white shadow-sm"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Tournament</th><th>Event Date</th><th>Venue</th><th>Registration Deadline</th><th>Selection Status</th></tr></thead><tbody>
                    <?php foreach ($my_regs as $r): $status_class = ['pending_review'=>'warning text-dark','selected'=>'success','rejected'=>'danger'][$r['reg_status']] ?? 'secondary'; ?>
                        <tr><td><div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div><div class="small text-muted"><?= htmlspecialchars($r['tournament_status']) ?></div></td><td><?= date('M j, Y', strtotime($r['event_date'])) ?></td><td><?= htmlspecialchars($r['venue']) ?></td><td><?= date('M j, Y', strtotime($r['registration_deadline'])) ?></td><td><span class="badge bg-<?= $status_class ?> status-pill"><?= htmlspecialchars(str_replace('_', ' ', $r['reg_status'])) ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><div class="empty-state"><i class="fas fa-clipboard-check fa-2x mb-3"></i><h4 class="fw-bold">No registrations yet</h4><p class="mb-0">Your tournament applications will appear here after you register.</p></div><?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
