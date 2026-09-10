<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$dojo_stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$dojo_stmt->execute([$master_id]);
$dojo = $dojo_stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage the gallery.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';

/* Keep the gallery module self-contained so it works with existing KOMS databases. */
$pdo->exec("CREATE TABLE IF NOT EXISTS dojo_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dojo_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption TEXT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gallery_dojo (dojo_id),
    INDEX idx_gallery_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$upload_dir = dirname(__DIR__) . '/uploads/gallery';
$upload_url = '../uploads/gallery';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload') {
            $title = trim((string)($_POST['title'] ?? ''));
            $caption = trim((string)($_POST['caption'] ?? ''));

            if ($title === '') {
                $error = 'Please enter a gallery title.';
            } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please select an image to upload.';
            } else {
                $file = $_FILES['image'];
                $max_size = 5 * 1024 * 1024;
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                if ((int)$file['size'] > $max_size) {
                    $error = 'Image size must be 5 MB or less.';
                } elseif (!isset($allowed[$mime])) {
                    $error = 'Only JPG, PNG, WEBP and GIF images are allowed.';
                } elseif (!is_uploaded_file($file['tmp_name'])) {
                    $error = 'Invalid upload detected.';
                } else {
                    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                    $destination = $upload_dir . '/' . $filename;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $error = 'Could not save the uploaded image. Check the uploads folder permissions.';
                    } else {
                        $relative_path = 'uploads/gallery/' . $filename;
                        $insert = $pdo->prepare("INSERT INTO dojo_gallery (dojo_id, title, image_path, caption, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $insert->execute([$dojo_id, $title, $relative_path, $caption !== '' ? $caption : null, $master_id]);
                        $gallery_id = (int)$pdo->lastInsertId();

                        log_audit_action($pdo, $master_id, 'CREATE', 'dojo_gallery', $gallery_id, 'Uploaded dojo gallery image: ' . $title);
                        $_SESSION['success_msg'] = 'Gallery image uploaded successfully.';
                        redirect('/master/gallery.php');
                    }
                }
            }
        } elseif ($action === 'delete') {
            $gallery_id = (int)($_POST['gallery_id'] ?? 0);

            if ($gallery_id <= 0) {
                $error = 'Invalid gallery item selected.';
            } else {
                $item_stmt = $pdo->prepare("SELECT id, image_path FROM dojo_gallery WHERE id = ? AND dojo_id = ? LIMIT 1");
                $item_stmt->execute([$gallery_id, $dojo_id]);
                $item = $item_stmt->fetch();

                if (!$item) {
                    $error = 'Gallery item not found.';
                } else {
                    $delete = $pdo->prepare("DELETE FROM dojo_gallery WHERE id = ? AND dojo_id = ?");
                    $delete->execute([$gallery_id, $dojo_id]);

                    $absolute = dirname(__DIR__) . '/' . ltrim($item['image_path'], '/');
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }

                    log_audit_action($pdo, $master_id, 'DELETE', 'dojo_gallery', $gallery_id, 'Deleted dojo gallery image');
                    $_SESSION['success_msg'] = 'Gallery image deleted.';
                    redirect('/master/gallery.php');
                }
            }
        }
    }
}

$page_title = 'Gallery';
require_once '../includes/header.php';

$list = $pdo->prepare("SELECT id, title, image_path, caption, created_at FROM dojo_gallery WHERE dojo_id = ? ORDER BY created_at DESC LIMIT 100");
$list->execute([$dojo_id]);
$gallery = $list->fetchAll();

$total = count($gallery);
$this_month = 0;
$month_start = date('Y-m-01 00:00:00');
foreach ($gallery as $item) {
    if ($item['created_at'] >= $month_start) {
        $this_month++;
    }
}
?>

<style>
.gal-wrap{max-width:1200px;margin:1.5rem auto}.gal-hero{background:linear-gradient(135deg,#090909,#1c1c1c 58%,#540909);color:#fff;border-radius:24px;padding:1.8rem 2rem;box-shadow:0 22px 52px rgba(0,0,0,.14)}
.gal-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd15b}.gal-title{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:900;margin:.25rem 0 .35rem}.metric{background:#fff;border:0;border-radius:18px;padding:1rem 1.1rem;box-shadow:0 12px 32px rgba(17,24,39,.06);height:100%}.metric small{display:block;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800;font-size:.7rem}.metric strong{font-size:1.65rem}
.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{padding:1.35rem}.form-control{border-radius:12px;padding:.72rem .85rem}.gallery-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.gallery-card{border:1px solid #eee;border-radius:18px;overflow:hidden;background:#fff}.gallery-img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f3f3f3}.gallery-body{padding:1rem}.gallery-title{font-weight:800}.gallery-caption{font-size:.84rem;color:#777;min-height:2.2em}.gallery-meta{font-size:.72rem;color:#999}
@media(max-width:992px){.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:576px){.gal-hero{padding:1.35rem}.gal-wrap{margin:1rem auto}.gallery-grid{grid-template-columns:1fr}}
</style>

<div class="gal-wrap">
    <section class="gal-hero mb-4">
        <div class="gal-kicker">Master Control • Media</div>
        <div class="gal-title">Dojo Gallery</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Upload training, grading, tournament and dojo-event photos for your students and community.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="metric"><small>Total gallery items</small><strong><?= $total ?></strong></div></div>
        <div class="col-md-6"><div class="metric"><small>Uploaded this month</small><strong><?= $this_month ?></strong></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card panel">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Upload</div><h5 class="mb-0 mt-1">Add Gallery Image</h5></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3"><label class="form-label fw-bold">Title</label><input type="text" name="title" class="form-control" maxlength="150" required placeholder="e.g. Independence Day Training"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" required><div class="form-text">JPG, PNG, WEBP or GIF • Maximum 5 MB</div></div>
                        <div class="mb-3"><label class="form-label fw-bold">Caption</label><textarea name="caption" class="form-control" rows="4" maxlength="1000" placeholder="Optional description..."></textarea></div>
                        <button class="btn btn-dark w-100"><i class="fas fa-cloud-arrow-up me-2"></i>Upload Image</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-2"><div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Gallery</div><h5 class="mb-0 mt-1">Your Dojo Photos</h5></div><a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a></div>
                <div class="card-body">
                    <?php if ($gallery): ?>
                        <div class="gallery-grid">
                            <?php foreach ($gallery as $item): ?>
                                <article class="gallery-card">
                                    <img class="gallery-img" src="<?= htmlspecialchars('../' . ltrim($item['image_path'], '/')) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                    <div class="gallery-body">
                                        <div class="gallery-title mb-1"><?= htmlspecialchars($item['title']) ?></div>
                                        <div class="gallery-caption mb-2"><?= nl2br(htmlspecialchars($item['caption'] ?: '')) ?></div>
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <span class="gallery-meta"><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                                            <form method="POST" onsubmit="return confirm('Delete this gallery image?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5"><i class="fas fa-images fa-2x mb-3"></i><div class="fw-bold">No gallery images yet</div><div class="small">Upload your first dojo photo from the panel on the left.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
