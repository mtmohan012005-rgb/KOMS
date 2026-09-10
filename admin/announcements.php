<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $title = sanitize_input($_POST['title']);
        $content = sanitize_input($_POST['content']);
        $level = 'global'; // Admin posts are always global
        
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, level, publish_date, created_by) VALUES (?, ?, ?, CURDATE(), ?)");
        if ($stmt->execute([$title, $content, $level, $_SESSION['user_id']])) {
            $_SESSION['success_msg'] = "Global announcement published.";
            redirect('/admin/announcements.php');
        } else {
            $_SESSION['error_msg'] = "Failed to publish announcement.";
        }
    }
}

$page_title = 'Global Announcements';
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM announcements WHERE level = 'global' ORDER BY publish_date DESC");
$announcements = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Post Global Announcement</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="post_announcement" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Announcement Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                        <small class="text-muted">This will be visible to ALL masters and students across the organization.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-bullhorn"></i> Publish Broadcast</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <h4 class="mb-3">Recent Global Broadcasts</h4>
        <?php foreach ($announcements as $a): ?>
            <div class="card shadow-sm mb-3 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title text-primary mb-0"><?= htmlspecialchars($a['title']) ?></h5>
                        <small class="text-muted"><i class="fas fa-calendar-alt"></i> <?= date('M j, Y', strtotime($a['publish_date'])) ?></small>
                    </div>
                    <p class="card-text"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($announcements)): ?>
            <div class="alert alert-light text-center">No global announcements posted yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
