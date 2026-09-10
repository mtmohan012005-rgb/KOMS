<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'Announcements';
require_once '../includes/header.php';

// Get student's dojo
$stmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

// Fetch Global AND Dojo announcements
if ($dojo_id) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE status = 'active' AND (level = 'global' OR dojo_id = ?) ORDER BY publish_date DESC");
    $stmt->execute([$dojo_id]);
} else {
    // Only see global if not in a dojo
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE status = 'active' AND level = 'global' ORDER BY publish_date DESC");
    $stmt->execute();
}

$announcements = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-1">Announcements Board</h2>
        <p class="text-muted">Stay updated with the latest news from the organization and your dojo.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <?php foreach ($announcements as $a): ?>
            <div class="card shadow-sm mb-4 border-0 <?= $a['level'] === 'global' ? 'border-start border-primary' : 'border-start border-success' ?> border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <?php if ($a['level'] === 'global'): ?>
                                <span class="badge bg-primary me-2"><i class="fas fa-globe"></i> Organization Wide</span>
                            <?php else: ?>
                                <span class="badge bg-success me-2"><i class="fas fa-torii-gate"></i> Dojo specific</span>
                            <?php endif; ?>
                            <small class="text-muted fw-bold"><?= date('l, F j, Y', strtotime($a['publish_date'])) ?></small>
                        </div>
                    </div>
                    <h4 class="card-title text-dark"><?= htmlspecialchars($a['title']) ?></h4>
                    <p class="card-text mt-3" style="font-size: 1.1rem; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($a['content'])) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($announcements)): ?>
            <div class="text-center py-5">
                <i class="far fa-bell-slash fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">All caught up!</h4>
                <p>There are no new announcements at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
