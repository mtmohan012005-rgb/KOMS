<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = 'Find a Dojo';
require_once 'includes/header.php';

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$query = "SELECT d.*, u.first_name, u.last_name FROM dojos d JOIN users u ON d.master_id = u.id WHERE d.status = 'approved'";
$params = [];

if ($search) {
    $query .= " AND (d.name LIKE ? OR d.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dojos = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Find a Dojo</h2>
        <p class="text-muted">Browse active dojos in our organization and request to join.</p>
    </div>
    <div class="col-md-4">
        <form method="GET" action="find_dojo.php" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by name or location..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>

<div class="row">
    <?php if (count($dojos) > 0): ?>
        <?php foreach ($dojos as $dojo): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h4 class="card-title text-primary"><?= htmlspecialchars($dojo['name']) ?></h4>
                        <h6 class="card-subtitle mb-3 text-muted">Master <?= htmlspecialchars($dojo['first_name'] . ' ' . $dojo['last_name']) ?></h6>
                        <p class="card-text mb-1"><i class="fas fa-map-marker-alt text-danger w-20"></i> <?= htmlspecialchars($dojo['location']) ?></p>
                        <p class="card-text"><i class="fas fa-calendar-alt text-info w-20"></i> <?= htmlspecialchars($dojo['training_days']) ?></p>
                        <a href="dojo_details.php?id=<?= $dojo['id'] ?>" class="btn btn-outline-primary w-100 mt-2">View Details & Join</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center my-5">
            <h4 class="text-muted">No active dojos found.</h4>
            <?php if ($search): ?>
                <a href="find_dojo.php" class="btn btn-link">Clear Search</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
