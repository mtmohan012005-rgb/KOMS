<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'System Audit Logs';
require_once '../includes/header.php';

// Filter parameters
$module_filter = isset($_GET['module']) ? sanitize_input($_GET['module']) : '';
$action_filter = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$query = "SELECT a.*, u.first_name, u.last_name, u.email 
          FROM audit_logs a 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($module_filter) {
    $query .= " AND a.module = ?";
    $params[] = $module_filter;
}
if ($action_filter) {
    $query .= " AND a.action = ?";
    $params[] = $action_filter;
}
if ($search) {
    $query .= " AND (a.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR a.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY a.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get distinct modules for filter dropdown
$modules = $pdo->query("SELECT DISTINCT module FROM audit_logs ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2>Organization Audit Trail</h2>
        <p class="text-muted">Immutable log of security actions, database updates, and compliance events.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search description, user or IP..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= $module_filter === $m ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($m)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <option value="LOGIN" <?= $action_filter === 'LOGIN' ? 'selected' : '' ?>>LOGIN</option>
                    <option value="LOGOUT" <?= $action_filter === 'LOGOUT' ? 'selected' : '' ?>>LOGOUT</option>
                    <option value="CREATE" <?= $action_filter === 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                    <option value="UPDATE" <?= $action_filter === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $action_filter === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                <?php if ($search || $module_filter || $action_filter): ?>
                    <a href="audit.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="small text-muted text-nowrap">
                                <i class="far fa-clock me-1"></i><?= date('M j, Y g:i:s A', strtotime($l['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($l['first_name']): ?>
                                    <strong><?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($l['email'] ?? '') ?></div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge = [
                                    'LOGIN' => 'info text-dark',
                                    'LOGOUT' => 'secondary',
                                    'CREATE' => 'success',
                                    'UPDATE' => 'warning text-dark',
                                    'DELETE' => 'danger'
                                ][$l['action']] ?? 'primary';
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($l['action']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($l['module']) ?></span>
                            </td>
                            <td>
                                <div class="text-wrap" style="max-width: 450px;">
                                    <?= htmlspecialchars($l['description']) ?>
                                    <?php if ($l['record_id']): ?>
                                        <small class="text-muted">(ID: <?= htmlspecialchars($l['record_id']) ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><code class="small"><?= htmlspecialchars($l['ip_address'] ?: '127.0.0.1') ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 d-block text-muted"></i>
                                No audit records match your filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
