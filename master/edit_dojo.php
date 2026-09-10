<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'No dojo found. Please register your dojo first.';
    redirect('/master/register_dojo.php');
}

$days_list = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $location = sanitize_input($_POST['location'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $selected_days = isset($_POST['days']) && is_array($_POST['days']) ? $_POST['days'] : [];
        $training_days = implode(', ', array_values(array_intersect($days_list, $selected_days)));
        $timings = sanitize_input($_POST['timings'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');

        if ($name === '' || $location === '') {
            $error = 'Dojo name and location are required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid contact email.';
        } else {
            $update = $pdo->prepare("UPDATE dojos SET name = ?, location = ?, contact_number = ?, email = ?, training_days = ?, training_timings = ?, description = ? WHERE id = ? AND master_id = ?");
            $update->execute([
                $name,
                $location,
                $phone,
                $email,
                $training_days,
                $timings,
                $description,
                (int)$dojo['id'],
                $master_id
            ]);

            log_audit_action($pdo, $master_id, 'UPDATE', 'dojos', (int)$dojo['id'], "Updated dojo profile: $name");
            $_SESSION['success_msg'] = 'Dojo profile updated successfully.';
            redirect('/master/edit_dojo.php');
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM dojos WHERE id = ? AND master_id = ? LIMIT 1");
$stmt->execute([(int)$dojo['id'], $master_id]);
$dojo = $stmt->fetch() ?: $dojo;
$active_days = array_filter(array_map('trim', explode(',', (string)($dojo['training_days'] ?? ''))));

$page_title = 'Manage Dojo';
require_once '../includes/header.php';
?>

<style>
    .dojo-page { max-width: 1100px; margin: 1.5rem auto 2rem; }
    .dojo-hero { position: relative; overflow: hidden; border-radius: 24px; padding: 2rem; color: #fff; background: radial-gradient(circle at 90% 10%, rgba(181,28,28,.7), transparent 35%), linear-gradient(135deg,#080808,#1b1b1b 60%,#420707); box-shadow: 0 22px 55px rgba(0,0,0,.18); }
    .dojo-hero:after { content:''; position:absolute; width:180px; height:180px; right:-70px; bottom:-80px; border:1px solid rgba(255,255,255,.1); border-radius:50%; box-shadow:0 0 0 30px rgba(255,255,255,.03),0 0 0 60px rgba(255,255,255,.02); }
    .eyebrow { color:#f1c75b; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.15em; }
    .hero-title { margin:.4rem 0 .5rem; font-size:clamp(1.7rem,4vw,2.7rem); font-weight:900; }
    .hero-copy { max-width:720px; margin:0; color:rgba(255,255,255,.72); }
    .card-modern { border:0; border-radius:20px; overflow:hidden; box-shadow:0 15px 42px rgba(17,24,39,.08); }
    .card-modern .card-header { background:#fff; border:0; padding:1.2rem 1.35rem; }
    .card-modern .card-body { background:#fff; padding:1.35rem; }
    .section-label { margin:0 0 .2rem; color:#989898; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.09em; }
    .form-label { font-size:.86rem; font-weight:750; }
    .form-control { border-radius:12px; padding:.72rem .85rem; }
    .form-control:focus { border-color:#1b1b1b; box-shadow:0 0 0 .18rem rgba(0,0,0,.07); }
    .day-chip { min-width:72px; padding:.55rem .7rem; border:1px solid #dedede; border-radius:12px; background:#fafafa; display:inline-flex; justify-content:center; align-items:center; gap:.4rem; cursor:pointer; user-select:none; transition:.2s ease; }
    .day-chip:hover { transform:translateY(-1px); border-color:#aaa; }
    .status-pill { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .75rem; border-radius:999px; font-size:.74rem; font-weight:800; }
    .tool-btn { border-radius:12px; padding:.7rem .85rem; font-weight:700; }
    .muted-note { color:#888; font-size:.78rem; }
</style>

<div class="dojo-page">
    <section class="dojo-hero mb-4">
        <div class="eyebrow">Master Control • Dojo Profile</div>
        <div class="hero-title">Manage <?= htmlspecialchars($dojo['name']) ?></div>
        <p class="hero-copy">Maintain your dojo information, training schedule and contact details. Keep this profile accurate so students and administrators see the right information.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-modern">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="section-label">Profile</div>
                        <h5 class="mb-0">Dojo Information</h5>
                    </div>
                    <?php $status = strtolower((string)$dojo['status']); ?>
                    <span class="status-pill <?= $status === 'approved' ? 'bg-success text-white' : ($status === 'rejected' ? 'bg-danger text-white' : 'bg-warning text-dark') ?>">
                        <i class="fas fa-circle" style="font-size:.48rem"></i><?= htmlspecialchars(ucfirst($status)) ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Dojo Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($dojo['name']) ?>" maxlength="150" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($dojo['contact_number'] ?? '') ?>" maxlength="30" placeholder="Phone number">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location / Address *</label>
                                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($dojo['location']) ?>" required placeholder="Full dojo address">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dojo['email'] ?? '') ?>" maxlength="150" placeholder="dojo@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Training Timings</label>
                                <input type="text" name="timings" class="form-control" value="<?= htmlspecialchars($dojo['training_timings'] ?? '') ?>" placeholder="5:00 PM - 8:00 PM">
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Training Days</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($days_list as $day): ?>
                                        <label class="day-chip">
                                            <input type="checkbox" name="days[]" value="<?= htmlspecialchars($day) ?>" <?= in_array($day, $active_days, true) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($day) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="muted-note mt-2">Select every day the dojo normally trains.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">About the Dojo</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Describe the dojo, training environment, facilities or philosophy."><?= htmlspecialchars($dojo['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-dark px-4"><i class="fas fa-save me-2"></i>Save Dojo Changes</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-modern mb-4">
                <div class="card-header">
                    <div class="section-label">Registration</div>
                    <h5 class="mb-0">Dojo Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Approval</span>
                        <strong class="text-capitalize"><?= htmlspecialchars($status) ?></strong>
                    </div>
                    <?php if ($status === 'pending'): ?>
                        <div class="alert alert-warning border-0 small mb-0"><i class="fas fa-hourglass-half me-2"></i>Your dojo is waiting for Grand Master approval.</div>
                    <?php elseif ($status === 'approved'): ?>
                        <div class="alert alert-success border-0 small mb-0"><i class="fas fa-check-circle me-2"></i>Your dojo is approved and active.</div>
                    <?php elseif ($status === 'rejected'): ?>
                        <div class="alert alert-danger border-0 small mb-0"><i class="fas fa-circle-exclamation me-2"></i>Your dojo was rejected. Review the profile and contact the administrator.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-modern">
                <div class="card-header">
                    <div class="section-label">Quick Access</div>
                    <h5 class="mb-0">Dojo Management</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="students.php" class="btn btn-outline-dark text-start tool-btn"><i class="fas fa-users me-2"></i>Manage Students</a>
                    <a href="attendance.php" class="btn btn-outline-dark text-start tool-btn"><i class="fas fa-calendar-check me-2"></i>Attendance</a>
                    <a href="grading.php" class="btn btn-outline-dark text-start tool-btn"><i class="fas fa-medal me-2"></i>Grading & Belts</a>
                    <a href="fees.php" class="btn btn-outline-dark text-start tool-btn"><i class="fas fa-wallet me-2"></i>Fees & Payments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>