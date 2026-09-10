<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage announcements.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = sanitize_input($_POST['title'] ?? '');
            $content = sanitize_input($_POST['content'] ?? '');
            $publish_date = sanitize_input($_POST['publish_date'] ?? date('Y-m-d'));
            $expiry_date = sanitize_input($_POST['expiry_date'] ?? '');

            if ($title === '' || $content === '') {
                $error = 'Title and announcement content are required.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $publish_date)) {
                $error = 'Please provide a valid publish date.';
            } elseif ($expiry_date !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date) || $expiry_date < $publish_date)) {
                $error = 'Expiry date must be empty or on/after the publish date.';
            } else {
                $insert = $pdo->prepare("INSERT INTO announcements (title, content, level, dojo_id, publish_date, expiry_date, status, created_by) VALUES (?, ?, 'dojo', ?, ?, ?, 'active', ?)");
                $insert->execute([
                    $title,
                    $content,
                    $dojo_id,
                    $publish_date,
                    $expiry_date !== '' ? $expiry_date : null,
                    $master_id
                ]);

                $announcement_id = (int)$pdo->lastInsertId();
                log_audit_action($pdo, $master_id, 'CREATE', 'announcements', $announcement_id, 'Created dojo announcement: ' . $title);
                $_SESSION['success_msg'] = 'Announcement published successfully.';
                redirect('/master/announcements.php');
            }
        } elseif ($action === 'archive') {
            $announcement_id = (int)($_POST['announcement_id'] ?? 0);
            if ($announcement_id > 0) {
                $update = $pdo->prepare("UPDATE announcements SET status = 'archived' WHERE id = ? AND dojo_id = ? AND created_by = ?");
                $update->execute([$announcement_id, $dojo_id, $master_id]);
                log_audit_action($pdo, $master_id, 'UPDATE', 'announcements', $announcement_id, 'Archived dojo announcement');
                $_SESSION['success_msg'] = 'Announcement archived.';
                redirect('/master/announcements.php');
            }
            $error = 'Invalid announcement selected.';
        }
    }
}

$page_title = 'Announcements';
require_once '../includes/header.php';

$list = $pdo->prepare("SELECT * FROM announcements WHERE dojo_id = ? ORDER BY publish_date DESC, created_at DESC LIMIT 50");
$list->execute([$dojo_id]);
$announcements = $list->fetchAll();

$active_count = 0;
$scheduled_count = 0;
$archived_count = 0;
$today = date('Y-m-d');
foreach ($announcements as $a) {
    if ($a['status'] === 'archived') {
        $archived_count++;
    } else {
        $active_count++;
        if ($a['publish_date'] > $today) {
            $scheduled_count++;
        }
    }
}
?>

<style>
    .ann-wrap{max-width:1180px;margin:1.5rem auto}
    .ann-hero{background:linear-gradient(135deg,#090909,#1d1d1d 58%,#4d0808);color:#fff;border-radius:24px;padding:1.8rem 2rem;box-shadow:0 22px 50px rgba(0,0,0,.14)}
    .ann-kicker{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#ffcf5c}
    .ann-title{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:900;margin:.25rem 0 .45rem}
    .metric{background:#fff;border:0;border-radius:18px;padding:1rem 1.1rem;box-shadow:0 12px 30px rgba(17,24,39,.06);height:100%}
    .metric-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}
    .metric-value{font-size:1.65rem;font-weight:900;margin-top:.2rem}
    .panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}
    .panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}
    .panel .card-body{padding:1.35rem}
    .form-control{border-radius:12px;padding:.72rem .85rem}
    .announcement-item{border:1px solid #eee;border-radius:16px;padding:1rem 1.05rem;background:#fff}
    .announcement-item + .announcement-item{margin-top:.8rem}
    .badge-soft{border-radius:999px;padding:.4rem .65rem;font-size:.72rem;font-weight:800}
</style>

<div class="ann-wrap">
    <section class="ann-hero mb-4">
        <div class="ann-kicker">Master Control • Communication</div>
        <div class="ann-title">Announcements for <?= htmlspecialchars($dojo['name']) ?></div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Keep your students informed about training, grading, events, schedule changes and important dojo updates.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric"><div class="metric-label">Active</div><div class="metric-value"><?= $active_count ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Scheduled</div><div class="metric-value"><?= $scheduled_count ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Archived</div><div class="metric-value"><?= $archived_count ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card panel">
                <div class="card-header">
                    <div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Create</div>
                    <h5 class="mb-0 mt-1">New Dojo Announcement</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="title" class="form-control" maxlength="150" required placeholder="e.g. Saturday grading test">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Message</label>
                            <textarea name="content" class="form-control" rows="6" required placeholder="Write the announcement for your dojo students..."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Publish Date</label>
                                <input type="date" name="publish_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 mt-4"><i class="fas fa-paper-plane me-2"></i>Publish Announcement</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">History</div><h5 class="mb-0 mt-1">Recent Announcements</h5></div>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                </div>
                <div class="card-body">
                    <?php foreach ($announcements as $a): ?>
                        <article class="announcement-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['title']) ?></h6>
                                    <div class="small text-muted">Publish: <?= date('M j, Y', strtotime($a['publish_date'])) ?><?php if ($a['expiry_date']): ?> • Expires: <?= date('M j, Y', strtotime($a['expiry_date'])) ?><?php endif; ?></div>
                                </div>
                                <?php if ($a['status'] === 'archived'): ?>
                                    <span class="badge-soft bg-secondary text-white">ARCHIVED</span>
                                <?php elseif ($a['publish_date'] > $today): ?>
                                    <span class="badge-soft bg-warning text-dark">SCHEDULED</span>
                                <?php else: ?>
                                    <span class="badge-soft bg-success text-white">ACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-3 mb-3 text-muted" style="white-space:pre-line;"><?= htmlspecialchars($a['content']) ?></p>
                            <?php if ($a['status'] !== 'archived'): ?>
                                <form method="POST" onsubmit="return confirm('Archive this announcement?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-box-archive me-1"></i>Archive</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($announcements)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-2x mb-3"></i>
                            <div class="fw-bold">No announcements yet</div>
                            <div class="small">Create the first announcement for your dojo.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
