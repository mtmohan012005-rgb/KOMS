<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'Executive System Reports';

// 1. Overall Metrics
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_dojos = (int)$pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'approved'")->fetchColumn();
$total_masters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'master'")->fetchColumn();
$total_seniors = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'senior'")->fetchColumn();

// 2. Belt Distribution
$belt_counts = $pdo->query("
    SELECT g.new_belt as current_belt, COUNT(DISTINCT g.student_id) as count 
    FROM grading_history g 
    JOIN users u ON g.student_id = u.id 
    WHERE u.status = 'active' 
    GROUP BY g.new_belt 
    ORDER BY count DESC
")->fetchAll();

// 3. Dojo Performance Summary
$dojo_summaries = $pdo->query("
    SELECT d.id, d.name, d.location, u.first_name as master_first, u.last_name as master_last,
           (SELECT COUNT(*) FROM dojo_memberships dm WHERE dm.dojo_id = d.id AND dm.status = 'approved') as student_count,
           (SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.dojo_id = d.id) as sessions_held
    FROM dojos d
    JOIN users u ON d.master_id = u.id
    WHERE d.status = 'approved'
    ORDER BY student_count DESC
")->fetchAll();

// 4. Monthly Attendance Trend (Last 30 Days)
$attendance_trend = $pdo->query("
    SELECT DATE(ats.session_date) as sdate, COUNT(ae.id) as attendees
    FROM attendance_sessions ats
    JOIN attendance_entries ae ON ats.id = ae.session_id
    WHERE ae.status = 'present' AND ats.session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(ats.session_date)
    ORDER BY sdate DESC
    LIMIT 7
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="text-uppercase" style="color: #f4bd17; font-size: 11px; font-weight: 800; letter-spacing: 1.5px;">Executive Intelligence</div>
            <h1 class="h2 fw-bold text-white mb-0">System Reports & Analytics</h1>
            <p class="text-muted small mb-0">Federation-wide enrollment, belt distribution, and dojo performance statistics.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button onclick="window.print()" class="btn btn-dark border-warning text-warning btn-sm"><i class="fas fa-print me-1"></i> Print Report</button>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-dark border-secondary h-100 p-3 shadow-sm">
                <div class="text-muted small text-uppercase">Total Students</div>
                <div class="display-6 fw-bold text-white"><?= $total_students ?></div>
                <div class="small text-success mt-1"><i class="fas fa-shield-halved me-1"></i> Active practitioners</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-dark border-secondary h-100 p-3 shadow-sm">
                <div class="text-muted small text-uppercase">Affiliated Dojos</div>
                <div class="display-6 fw-bold text-warning"><?= $total_dojos ?></div>
                <div class="small text-muted mt-1"><i class="fas fa-torii-gate me-1"></i> Approved training halls</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-dark border-secondary h-100 p-3 shadow-sm">
                <div class="text-muted small text-uppercase">Certified Masters</div>
                <div class="display-6 fw-bold text-danger"><?= $total_masters ?></div>
                <div class="small text-muted mt-1"><i class="fas fa-crown me-1"></i> Sensei instructors</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-dark border-secondary h-100 p-3 shadow-sm">
                <div class="text-muted small text-uppercase">Senior Assistants</div>
                <div class="display-6 fw-bold text-info"><?= $total_seniors ?></div>
                <div class="small text-muted mt-1"><i class="fas fa-user-ninja me-1"></i> Sub-administrators</div>
            </div>
        </div>
    </div>

    <!-- Dojo Summaries Table -->
    <div class="card bg-dark border-secondary mb-4 shadow-sm">
        <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fas fa-trophy text-warning me-2"></i>Dojo Performance Summary</h5>
            <span class="badge bg-danger"><?= count($dojo_summaries) ?> Dojos</span>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>Dojo Name</th>
                        <th>Location</th>
                        <th>Head Sensei</th>
                        <th class="text-center">Active Students</th>
                        <th class="text-center">Training Sessions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dojo_summaries)): ?>
                        <?php foreach ($dojo_summaries as $dojo): ?>
                            <tr>
                                <td class="fw-bold text-white"><?= htmlspecialchars($dojo['name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($dojo['location']) ?></td>
                                <td><?= htmlspecialchars($dojo['master_first'] . ' ' . $dojo['master_last']) ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= $dojo['student_count'] ?></span></td>
                                <td class="text-center"><span class="badge bg-dark border border-secondary"><?= $dojo['sessions_held'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No approved dojos registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Belt Roster Grid -->
    <div class="card bg-dark border-secondary shadow-sm">
        <div class="card-header bg-transparent border-secondary py-3">
            <h5 class="mb-0 text-white"><i class="fas fa-medal text-warning me-2"></i>Practitioner Belt Distribution</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if (!empty($belt_counts)): ?>
                    <?php foreach ($belt_counts as $b): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 border border-secondary rounded bg-black d-flex justify-content-between align-items-center">
                                <span class="text-white fw-bold"><?= htmlspecialchars($b['current_belt'] ?: 'White Belt') ?></span>
                                <span class="badge bg-warning text-dark fs-6"><?= $b['count'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-muted">No belt statistics recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
