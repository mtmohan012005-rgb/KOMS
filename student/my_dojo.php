<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

// Fetch student's dojo membership
$stmt = $pdo->prepare("
    SELECT d.*, m.status as membership_status, m.joined_at, u.first_name as master_fname, u.last_name as master_lname, u.email as master_email, u.phone as master_phone
    FROM dojo_memberships m
    JOIN dojos d ON m.dojo_id = d.id
    JOIN users u ON d.master_id = u.id
    WHERE m.student_id = ? AND m.status = 'approved'
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['info_msg'] = "You are not currently an approved member of any dojo.";
    redirect('/student/dashboard.php');
}

$page_title = 'My Dojo - ' . $dojo['name'];
require_once '../includes/header.php';

// Fetch classmates
$classmates_stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, m.joined_at,
    (SELECT new_belt FROM grading_history WHERE student_id = u.id ORDER BY exam_date DESC LIMIT 1) as belt
    FROM dojo_memberships m
    JOIN users u ON m.student_id = u.id
    WHERE m.dojo_id = ? AND m.status = 'approved' AND u.id != ?
    ORDER BY u.first_name ASC
    LIMIT 10
");
$classmates_stmt->execute([$dojo['id'], $_SESSION['user_id']]);
$classmates = $classmates_stmt->fetchAll();

// Fetch dojo announcements
$ann_stmt = $pdo->prepare("SELECT * FROM announcements WHERE dojo_id = ? AND status = 'active' ORDER BY publish_date DESC LIMIT 5");
$ann_stmt->execute([$dojo['id']]);
$announcements = $ann_stmt->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h2 text-primary mb-1"><?= htmlspecialchars($dojo['name']) ?></h1>
        <p class="text-muted"><i class="fas fa-user-ninja text-danger me-1"></i>Head Instructor: Sensei <?= htmlspecialchars($dojo['master_fname'] . ' ' . $dojo['master_lname']) ?></p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>My Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- Dojo Info Card -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 mb-4 border-top border-primary border-4">
            <div class="card-body p-4">
                <h4 class="card-title mb-3">About Our Dojo</h4>
                <p class="lead" style="font-size: 1.05rem;">
                    <?= nl2br(htmlspecialchars($dojo['description'] ?: 'Traditional karate dojo dedicated to technical perfection and character building.')) ?>
                </p>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted small text-uppercase mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i>Location</h6>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($dojo['location']) ?></p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted small text-uppercase mb-1"><i class="fas fa-calendar-alt text-primary me-1"></i>Training Schedule</h6>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($dojo['training_days'] ?: 'See schedule') ?></p>
                            <small class="text-muted"><?= htmlspecialchars($dojo['training_timings'] ?: '') ?></small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted small text-uppercase mb-1"><i class="fas fa-envelope text-info me-1"></i>Contact Email</h6>
                            <p class="mb-0"><?= htmlspecialchars($dojo['email'] ?: $dojo['master_email']) ?></p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted small text-uppercase mb-1"><i class="fas fa-phone text-success me-1"></i>Contact Phone</h6>
                            <p class="mb-0"><?= htmlspecialchars($dojo['contact_number'] ?: $dojo['master_phone'] ?: 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dojo Announcements -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-bullhorn text-primary me-2"></i>Dojo Announcements</h5>
            </div>
            <div class="card-body p-4">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="border-start border-primary border-3 ps-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['title']) ?></h6>
                                <small class="text-muted"><?= date('M j, Y', strtotime($a['publish_date'])) ?></small>
                            </div>
                            <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0 text-center py-3">No specific announcements for this dojo currently.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Classmates & Quick Links -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 text-uppercase small fw-bold">Membership Status</h6>
                <h4 class="mb-2"><i class="fas fa-check-circle me-2"></i>Active Member</h4>
                <p class="small text-white-50 mb-0">Enrolled since <?= date('F j, Y', strtotime($dojo['joined_at'])) ?></p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-users text-secondary me-2"></i>Fellow Students</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (count($classmates) > 0): ?>
                        <?php foreach ($classmates as $cm): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="fw-semibold"><?= htmlspecialchars($cm['first_name'] . ' ' . $cm['last_name']) ?></span>
                                <div class="small text-muted">Joined <?= date('M Y', strtotime($cm['joined_at'])) ?></div>
                            </div>
                            <span class="badge bg-secondary"><?= htmlspecialchars($cm['belt'] ?: 'White Belt') ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-3">No other students registered yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
