<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tournament'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $name = sanitize_input($_POST['name']);
        $date = sanitize_input($_POST['event_date']);
        $venue = sanitize_input($_POST['venue']);
        $deadline = sanitize_input($_POST['deadline']);
        $desc = sanitize_input($_POST['description']);
        $target = $_POST['target_type'];

        $stmt = $pdo->prepare("INSERT INTO tournaments (name, description, event_date, venue, registration_deadline, status, target_type, created_by) VALUES (?, ?, ?, ?, ?, 'published', ?, ?)");
        if ($stmt->execute([$name, $desc, $date, $venue, $deadline, $target, $_SESSION['user_id']])) {
            $_SESSION['success_msg'] = "Tournament created and published.";
            redirect('/admin/tournaments.php');
        } else {
            $_SESSION['error_msg'] = "Failed to create tournament.";
        }
    }
}

$page_title = 'Manage Tournaments';
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM tournaments ORDER BY event_date DESC");
$tournaments = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Create Tournament</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="create_tournament" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Tournament Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Date</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Registration Deadline</label>
                        <input type="date" name="deadline" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Audience</label>
                        <select name="target_type" class="form-select">
                            <option value="all">All Dojos (Organization-wide)</option>
                            <option value="specific">Selected Dojos (TBD)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description / Categories</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Publish Tournament</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">All Tournaments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournaments as $t): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= date('M j, Y', strtotime($t['event_date'])) ?></td>
                                <td><?= htmlspecialchars($t['venue']) ?></td>
                                <td class="<?= strtotime($t['registration_deadline']) < time() ? 'text-danger' : '' ?>">
                                    <?= date('M j, Y', strtotime($t['registration_deadline'])) ?>
                                </td>
                                <td>
                                    <?php 
                                    $bg = ['draft'=>'secondary', 'published'=>'info', 'registration_open'=>'success', 'registration_closed'=>'warning', 'completed'=>'dark'][$t['status']] ?? 'primary';
                                    ?>
                                    <span class="badge bg-<?= $bg ?> text-uppercase"><?= str_replace('_', ' ', $t['status']) ?></span>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
