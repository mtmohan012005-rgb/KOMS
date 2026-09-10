<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'User Management';
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1">Organization Users</h2>
        <p class="text-muted mb-0">Manage KOMS members and student accounts.</p>
    </div>
    <a href="add_student.php" class="btn btn-danger"><i class="fas fa-user-plus me-2"></i>Add Student</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php $role_colors = ['super_admin'=>'danger', 'master'=>'primary', 'senior'=>'info', 'student'=>'secondary']; ?>
                            <span class="badge bg-<?= $role_colors[$user['role']] ?? 'secondary' ?> text-uppercase"><?= str_replace('_', ' ', $user['role']) ?></span>
                        </td>
                        <td><span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'dark' ?>"><?= htmlspecialchars($user['status']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
