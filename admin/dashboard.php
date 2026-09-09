<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'Super Admin Dashboard';
require_once '../includes/header.php';

// Fetch quick stats
$stats = [
    'dojos' => $pdo->query("SELECT COUNT(*) FROM dojos")->fetchColumn(),
    'active_dojos' => $pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'approved'")->fetchColumn(),
    'pending_dojos' => $pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'pending'")->fetchColumn(),
    'students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'masters' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'master'")->fetchColumn()
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Super Admin Dashboard</h1>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Total Dojos</h5>
                <h2 class="display-4"><?= $stats['dojos'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Active Dojos</h5>
                <h2 class="display-4"><?= $stats['active_dojos'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Pending Approvals</h5>
                <h2 class="display-4"><?= $stats['pending_dojos'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <h2 class="display-4"><?= $stats['students'] ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= APP_URL ?>/admin/dojos.php" class="btn btn-outline-primary text-start"><i class="fas fa-torii-gate me-2"></i> Manage Dojos</a>
                    <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline-secondary text-start"><i class="fas fa-users me-2"></i> Manage Users</a>
                    <a href="<?= APP_URL ?>/admin/tournaments.php" class="btn btn-outline-success text-start"><i class="fas fa-trophy me-2"></i> Manage Tournaments</a>
                    <a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-outline-info text-start"><i class="fas fa-bullhorn me-2"></i> Global Announcements</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity Logs</h5>
                <a href="<?= APP_URL ?>/admin/audit.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->query("SELECT a.*, u.first_name, u.last_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
                    while ($log = $stmt->fetch()):
                    ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($log['action']) ?></h6>
                            <small class="text-muted"><?= date('M j, g:i a', strtotime($log['created_at'])) ?></small>
                        </div>
                        <p class="mb-1 small"><?= htmlspecialchars($log['description']) ?></p>
                        <small class="text-muted">By: <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name'] ?? 'System') ?></small>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
