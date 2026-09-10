<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];
$page_title = 'My Achievements';

$stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM achievements a
    LEFT JOIN users u ON a.added_by = u.id
    WHERE a.student_id = ?
    ORDER BY a.achievement_date DESC, a.id DESC
");
$stmt->execute([$user_id]);
$achievements = $stmt->fetchAll();

$total = count($achievements);
$competition_count = 0;
$podium_count = 0;
$latest_date = null;

foreach ($achievements as $item) {
    if (!empty($item['competition_event'])) {
        $competition_count++;
    }

    $position = strtolower(trim((string)($item['position_result'] ?? '')));
    if (in_array($position, ['1st', 'first', 'gold', 'winner', '1', '2nd', 'second', 'silver', '2', '3rd', 'third', 'bronze', '3'], true)) {
        $podium_count++;
    }

    if ($latest_date === null || $item['achievement_date'] > $latest_date) {
        $latest_date = $item['achievement_date'];
    }
}

require_once '../includes/header.php';
?>

<style>
    .ach-page{margin-top:1.5rem;padding-bottom:3rem}
    .ach-hero{position:relative;overflow:hidden;border-radius:26px;padding:2rem;color:#fff;background:linear-gradient(135deg,#070707 0%,#171717 55%,#4d0a0a 100%);box-shadow:0 24px 60px rgba(0,0,0,.16)}
    .ach-hero:before{content:'';position:absolute;right:-90px;top:-120px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(244,189,23,.28),transparent 68%)}
    .ach-kicker{color:#ffcf5c;font-size:.74rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
    .ach-title{font-size:clamp(1.9rem,4vw,3rem);font-weight:900;margin:.35rem 0 .5rem}
    .ach-copy{color:rgba(255,255,255,.72);margin:0;max-width:760px}
    .ach-stat{border:0;border-radius:20px;background:#fff;box-shadow:0 14px 38px rgba(17,24,39,.08);height:100%}
    .ach-stat-icon{width:46px;height:46px;border-radius:15px;display:inline-flex;align-items:center;justify-content:center;background:#111;color:#fff}
    .ach-label{margin-top:.85rem;color:#8b8b8b;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
    .ach-value{margin-top:.25rem;font-size:1.8rem;font-weight:900;line-height:1.1}
    .ach-card{border:0;border-radius:20px;box-shadow:0 14px 38px rgba(17,24,39,.08);overflow:hidden;background:#fff;height:100%;transition:transform .18s ease,box-shadow .18s ease}
    .ach-card:hover{transform:translateY(-3px);box-shadow:0 18px 42px rgba(17,24,39,.13)}
    .ach-card-top{padding:1.25rem 1.25rem .85rem;background:linear-gradient(135deg,#fff,#f7f7f7)}
    .ach-badge{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.36rem .68rem;background:#111;color:#fff;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
    .ach-card h3{font-size:1.15rem;font-weight:900;margin:.8rem 0 .35rem}
    .ach-meta{font-size:.8rem;color:#777}
    .ach-body{padding:1.1rem 1.25rem 1.25rem}
    .ach-description{color:#555;line-height:1.6;white-space:pre-line}
    .ach-position{display:inline-flex;align-items:center;gap:.35rem;padding:.34rem .58rem;border-radius:8px;background:rgba(244,189,23,.14);color:#8b6500;font-size:.75rem;font-weight:800}
    .ach-cert{margin-top:1rem}
    .ach-empty{padding:3.5rem 1rem;text-align:center;background:#fff;border-radius:20px;box-shadow:0 14px 38px rgba(17,24,39,.08)}
    .ach-empty i{font-size:2.5rem;color:#888;margin-bottom:.9rem}
    @media(max-width:767.98px){.ach-page{margin-top:1rem}.ach-hero{padding:1.35rem;border-radius:18px}}
</style>

<div class="ach-page">
    <section class="ach-hero mb-4">
        <div class="position-relative">
            <div class="ach-kicker"><i class="fas fa-trophy me-1"></i> KOMS Student Portfolio</div>
            <div class="ach-title">My Achievements</div>
            <p class="ach-copy">Keep a professional record of your competition results, awards and milestones earned during your karate journey.</p>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card ach-stat"><div class="card-body p-3 p-lg-4"><span class="ach-stat-icon"><i class="fas fa-award"></i></span><div class="ach-label">Total Achievements</div><div class="ach-value"><?= $total ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card ach-stat"><div class="card-body p-3 p-lg-4"><span class="ach-stat-icon"><i class="fas fa-medal"></i></span><div class="ach-label">Podium Results</div><div class="ach-value"><?= $podium_count ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card ach-stat"><div class="card-body p-3 p-lg-4"><span class="ach-stat-icon"><i class="fas fa-flag-checkered"></i></span><div class="ach-label">Competition Records</div><div class="ach-value"><?= $competition_count ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card ach-stat"><div class="card-body p-3 p-lg-4"><span class="ach-stat-icon"><i class="fas fa-calendar-day"></i></span><div class="ach-label">Latest Milestone</div><div class="ach-value" style="font-size:1.15rem"><?= $latest_date ? date('M j, Y', strtotime($latest_date)) : '—' ?></div></div></div></div>
    </div>

    <?php if ($achievements): ?>
        <div class="row g-4">
            <?php foreach ($achievements as $a): ?>
                <div class="col-md-6 col-xl-4">
                    <article class="ach-card">
                        <div class="ach-card-top">
                            <span class="ach-badge"><i class="fas fa-star"></i> Achievement</span>
                            <h3><?= htmlspecialchars($a['title']) ?></h3>
                            <div class="ach-meta"><i class="fas fa-calendar me-1"></i><?= date('F j, Y', strtotime($a['achievement_date'])) ?></div>
                            <?php if (!empty($a['competition_event'])): ?>
                                <div class="ach-meta mt-1"><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($a['competition_event']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="ach-body">
                            <?php if (!empty($a['position_result'])): ?>
                                <span class="ach-position"><i class="fas fa-ranking-star"></i><?= htmlspecialchars($a['position_result']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($a['description'])): ?>
                                <p class="ach-description mt-3 mb-0"><?= htmlspecialchars($a['description']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($a['certificate_file'])): ?>
                                <div class="ach-cert">
                                    <a class="btn btn-sm btn-outline-dark" href="<?= htmlspecialchars($a['certificate_file']) ?>" target="_blank" rel="noopener">
                                        <i class="fas fa-file-certificate me-1"></i> View Certificate
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($a['first_name']) || !empty($a['last_name'])): ?>
                                <div class="ach-meta mt-3"><i class="fas fa-user-shield me-1"></i>Recorded by <?= htmlspecialchars(trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''))) ?></div>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="ach-empty">
            <i class="fas fa-trophy"></i>
            <h3 class="fw-bold">No achievements recorded yet</h3>
            <p class="text-muted mb-0">Your Master or authorized administrator can add your competition results and awards here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
