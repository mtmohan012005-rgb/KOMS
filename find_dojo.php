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

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h2>Find a Dojo</h2>
        <p class="text-muted mb-0">Browse affiliated martial arts training halls across the organization and enroll.</p>
    </div>
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="dojoLiveSearch" class="form-control" placeholder="Type to filter by name, sensei, or location in real-time..." value="<?= htmlspecialchars($search) ?>">
        </div>
    </div>
</div>

<!-- Real-time Day Filter Pills -->
<div class="mb-4 d-flex gap-2 flex-wrap align-items-center">
    <span class="small text-muted fw-bold me-1"><i class="fas fa-filter me-1"></i>Filter Days:</span>
    <button class="btn btn-sm btn-outline-primary day-filter active" data-day="all">All Days</button>
    <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
        <button class="btn btn-sm btn-outline-secondary day-filter" data-day="<?= $d ?>"><?= $d ?></button>
    <?php endforeach; ?>
</div>

<div class="row" id="dojoGrid">
    <?php if (count($dojos) > 0): ?>
        <?php foreach ($dojos as $dojo): ?>
            <div class="col-md-4 mb-4 dojo-card-item" data-days="<?= htmlspecialchars($dojo['training_days']) ?>" data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . $dojo['location'] . ' ' . $dojo['first_name'] . ' ' . $dojo['last_name'])) ?>">
                <div class="card shadow-sm h-100 border-top border-primary border-4">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="card-title text-primary mb-0"><?= htmlspecialchars($dojo['name']) ?></h4>
                            <span class="badge bg-success small"><i class="fas fa-check-circle me-1"></i>Approved</span>
                        </div>
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="fas fa-user-ninja text-danger me-1"></i>Sensei <?= htmlspecialchars($dojo['first_name'] . ' ' . $dojo['last_name']) ?>
                        </h6>
                        <p class="card-text mb-2 text-secondary small">
                            <i class="fas fa-map-marker-alt text-danger me-2 w-20"></i><?= htmlspecialchars($dojo['location']) ?>
                        </p>
                        <p class="card-text mb-3 text-secondary small">
                            <i class="fas fa-calendar-alt text-info me-2 w-20"></i><?= htmlspecialchars($dojo['training_days'] ?: 'Daily Sessions') ?>
                            <?php if ($dojo['training_timings']): ?>
                                <br><i class="fas fa-clock text-warning me-2 w-20"></i><?= htmlspecialchars($dojo['training_timings']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="mt-auto">
                            <a href="dojo_details.php?id=<?= $dojo['id'] ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-info-circle me-1"></i>View Details & Join
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center my-5">
            <i class="fas fa-torii-gate fa-4x text-muted mb-3 d-block"></i>
            <h4 class="text-muted">No active dojos found.</h4>
            <?php if ($search): ?>
                <a href="find_dojo.php" class="btn btn-primary mt-2">Clear Search</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="noMatchMessage" class="text-center my-5 d-none">
    <i class="fas fa-search-minus fa-3x text-muted mb-2 d-block"></i>
    <p class="text-muted">No dojos match your real-time search filter.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('dojoLiveSearch');
    const dayFilters = document.querySelectorAll('.day-filter');
    const cards = document.querySelectorAll('.dojo-card-item');
    const noMatch = document.getElementById('noMatchMessage');
    let activeDay = 'all';

    function filterDojos() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(card => {
            const text = card.getAttribute('data-search') || '';
            const days = card.getAttribute('data-days') || '';
            const matchesQuery = !query || text.includes(query);
            const matchesDay = (activeDay === 'all') || days.includes(activeDay);

            if (matchesQuery && matchesDay) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0 && cards.length > 0) {
            noMatch.classList.remove('d-none');
        } else {
            noMatch.classList.add('d-none');
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterDojos);
    }

    dayFilters.forEach(btn => {
        btn.addEventListener('click', () => {
            dayFilters.forEach(b => b.classList.remove('active', 'btn-primary'));
            dayFilters.forEach(b => b.classList.add('btn-outline-secondary'));
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-secondary');
            activeDay = btn.getAttribute('data-day');
            filterDojos();
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
