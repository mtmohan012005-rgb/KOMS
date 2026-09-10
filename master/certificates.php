<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$dojo_stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$dojo_stmt->execute([$master_id]);
$dojo = $dojo_stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage certificates.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';

/* Create a lightweight certificates table when the KOMS database has not
   received the optional certificate module schema yet. */
$pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    dojo_id INT UNSIGNED NOT NULL,
    certificate_type VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL,
    certificate_number VARCHAR(100) NOT NULL,
    issue_date DATE NOT NULL,
    notes TEXT NULL,
    issued_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_certificate_number (certificate_number),
    INDEX idx_cert_student (student_id),
    INDEX idx_cert_dojo (dojo_id),
    INDEX idx_cert_issue_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'issue') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            $type = trim(sanitize_input($_POST['certificate_type'] ?? ''));
            $title = trim(sanitize_input($_POST['title'] ?? ''));
            $certificate_number = trim(sanitize_input($_POST['certificate_number'] ?? ''));
            $issue_date = trim(sanitize_input($_POST['issue_date'] ?? date('Y-m-d')));
            $notes = trim(sanitize_input($_POST['notes'] ?? ''));

            $valid_types = ['Participation','Achievement','Grading','Tournament','Attendance','Instructor Recognition','Other'];

            if (!$student_id || $type === '' || $title === '' || $certificate_number === '' || $issue_date === '') {
                $error = 'Please complete all required certificate fields.';
            } elseif (!in_array($type, $valid_types, true)) {
                $error = 'Invalid certificate type.';
            } elseif (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $issue_date) || strtotime($issue_date) > strtotime(date('Y-m-d'))) {
                $error = 'Please provide a valid issue date that is not in the future.';
            } else {
                $student_stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ? AND role = 'student' AND status = 'active' AND id IN (SELECT student_id FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved') LIMIT 1");
                $student_stmt->execute([$student_id, $dojo_id]);
                $student = $student_stmt->fetch();

                if (!$student) {
                    $error = 'Selected student is not an approved active member of this dojo.';
                } else {
                    try {
                        $insert = $pdo->prepare("INSERT INTO certificates (student_id, dojo_id, certificate_type, title, certificate_number, issue_date, notes, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $insert->execute([$student_id, $dojo_id, $type, $title, $certificate_number, $issue_date, $notes !== '' ? $notes : null, $master_id]);
                        $certificate_id = (int)$pdo->lastInsertId();
                        log_audit_action($pdo, $master_id, 'CREATE', 'certificates', $certificate_id, 'Issued certificate ' . $certificate_number . ' to student ' . $student_id);
                        $_SESSION['success_msg'] = 'Certificate issued successfully.';
                        redirect('/master/certificates.php');
                    } catch (PDOException $e) {
                        $error = strpos($e->getMessage(), 'uq_certificate_number') !== false
                            ? 'Certificate number already exists. Please use a unique number.'
                            : 'Unable to issue the certificate right now.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $certificate_id = (int)($_POST['certificate_id'] ?? 0);
            if ($certificate_id > 0) {
                $delete = $pdo->prepare("DELETE FROM certificates WHERE id = ? AND dojo_id = ?");
                $delete->execute([$certificate_id, $dojo_id]);
                if ($delete->rowCount() > 0) {
                    log_audit_action($pdo, $master_id, 'DELETE', 'certificates', $certificate_id, 'Deleted certificate');
                    $_SESSION['success_msg'] = 'Certificate deleted.';
                } else {
                    $_SESSION['error_msg'] = 'Certificate not found.';
                }
                redirect('/master/certificates.php');
            }
            $error = 'Invalid certificate selected.';
        }
    }
}

$student_stmt = $pdo->prepare("SELECT u.id, u.member_id, u.first_name, u.last_name, COALESCE((SELECT gh.new_belt FROM grading_history gh WHERE gh.student_id = u.id AND gh.dojo_id = ? ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1), 'White') AS current_belt FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' AND u.role = 'student' AND u.status = 'active' ORDER BY u.first_name, u.last_name");
$student_stmt->execute([$dojo_id, $dojo_id]);
$students = $student_stmt->fetchAll();

$search = trim($_GET['search'] ?? '');
$sql = "SELECT c.*, u.first_name, u.last_name, u.member_id FROM certificates c JOIN users u ON c.student_id = u.id WHERE c.dojo_id = ?";
$params = [$dojo_id];
if ($search !== '') {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.member_id LIKE ? OR c.certificate_number LIKE ? OR c.title LIKE ?)";
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term, $term, $term);
}
$sql .= " ORDER BY c.issue_date DESC, c.id DESC LIMIT 100";
$cert_stmt = $pdo->prepare($sql);
$cert_stmt->execute($params);
$certificates = $cert_stmt->fetchAll();

$total = count($certificates);
$month_count = 0;
$today = date('Y-m-d');
$this_month = date('Y-m');
$types = [];
foreach ($certificates as $c) {
    if (substr($c['issue_date'], 0, 7) === $this_month) $month_count++;
    $types[$c['certificate_type']] = ($types[$c['certificate_type']] ?? 0) + 1;
}

$page_title = 'Certificates';
require_once '../includes/header.php';
?>

<style>
.cert-wrap{max-width:1200px;margin:1.5rem auto}
.cert-hero{border-radius:24px;padding:1.8rem 2rem;color:#fff;background:linear-gradient(135deg,#080808,#202020 58%,#5b0a0a);box-shadow:0 22px 55px rgba(0,0,0,.15)}
.cert-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd15b}.cert-title{font-size:clamp(1.7rem,4vw,2.65rem);font-weight:900;margin:.3rem 0}.metric{background:#fff;border:0;border-radius:18px;padding:1rem 1.1rem;box-shadow:0 12px 30px rgba(17,24,39,.06);height:100%}.metric-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}.metric-value{font-size:1.7rem;font-weight:900;margin-top:.15rem}.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{padding:1.35rem}.form-control,.form-select{border-radius:12px;padding:.72rem .85rem}.table th{font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#777;white-space:nowrap}.cert-number{font-family:monospace;font-size:.78rem;background:#f5f5f5;padding:.25rem .4rem;border-radius:7px}.type-badge{font-size:.7rem;font-weight:800;border-radius:999px;padding:.38rem .6rem;background:#f1f1f1}.student-sub{font-size:.74rem;color:#888}
@media(max-width:768px){.cert-hero{padding:1.35rem}.cert-table{min-width:920px}}
</style>

<div class="cert-wrap">
    <section class="cert-hero mb-4">
        <div class="cert-kicker">Master Control • Recognition</div>
        <div class="cert-title">Certificates</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Issue and maintain official achievement, grading, tournament and recognition certificates for your dojo students.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric"><div class="metric-label">Certificates shown</div><div class="metric-value"><?= $total ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Issued this month</div><div class="metric-value"><?= $month_count ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Certificate types</div><div class="metric-value"><?= count($types) ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card panel">
                <div class="card-header"><div class="small text-muted text-uppercase fw-bold">Issue</div><h5 class="mb-0 mt-1">Create Certificate</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="issue">
                        <div class="mb-3"><label class="form-label fw-bold">Student *</label><select name="student_id" class="form-select" required><option value="" disabled selected>Select student</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?><?= $s['member_id'] ? ' — '.htmlspecialchars($s['member_id']) : '' ?> · <?= htmlspecialchars($s['current_belt']) ?></option><?php endforeach; ?></select></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-bold">Type *</label><select name="certificate_type" class="form-select" required><?php foreach(['Participation','Achievement','Grading','Tournament','Attendance','Instructor Recognition','Other'] as $type): ?><option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Issue Date *</label><input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
                        </div>
                        <div class="mb-3 mt-3"><label class="form-label fw-bold">Certificate Title *</label><input name="title" class="form-control" maxlength="180" required placeholder="e.g. First Place - Kata"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Certificate Number *</label><input name="certificate_number" class="form-control" maxlength="100" required placeholder="e.g. KOMS-MD-2026-0001"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Notes</label><textarea name="notes" class="form-control" rows="4" maxlength="2000" placeholder="Achievement details or recognition notes."></textarea></div>
                        <button class="btn btn-dark w-100" type="submit"><i class="fas fa-award me-2"></i>Issue Certificate</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap"><div><div class="small text-muted text-uppercase fw-bold">Records</div><h5 class="mb-0 mt-1">Certificate History</h5></div><a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a></div>
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-2"><div class="col"><input name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search student, KOMS ID, certificate no. or title"></div><div class="col-auto"><button class="btn btn-dark"><i class="fas fa-search"></i></button></div><?php if($search!==''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="certificates.php">Clear</a></div><?php endif; ?></form>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0 cert-table"><thead class="table-light"><tr><th>Student</th><th>Certificate</th><th>Type</th><th>Issue Date</th><th>Action</th></tr></thead><tbody>
                <?php foreach($certificates as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></strong><div class="student-sub"><?= htmlspecialchars($c['member_id'] ?: 'No KOMS ID') ?></div></td>
                        <td><div class="fw-bold"><?= htmlspecialchars($c['title']) ?></div><span class="cert-number"><?= htmlspecialchars($c['certificate_number']) ?></span></td>
                        <td><span class="type-badge"><?= htmlspecialchars($c['certificate_type']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($c['issue_date'])) ?></td>
                        <td><form method="POST" onsubmit="return confirm('Delete this certificate record?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="certificate_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($certificates)): ?><tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-certificate fa-2x mb-3"></i><div class="fw-bold">No certificates yet</div><div class="small">Issued certificates for your dojo will appear here.</div></td></tr><?php endif; ?>
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>