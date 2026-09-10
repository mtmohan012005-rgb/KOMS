<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Announcements';

// Find the student's approved dojo, when available.
$membership_stmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? AND status = 'approved' LIMIT 1");
$membership_stmt->execute([$user_id]);
$dojo_id = $membership_stmt->fetchColumn();

// Only show currently published and non-expired announcements.
if ($dojo_id) {
    $stmt = $pdo->prepare("
        SELECT a.*
        FROM announcements a
        WHERE a.status = 'active'
          AND a.publish_date <= CURDATE()
          AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
          AND (a.level = 'global' OR (a.level = 'dojo' AND a.dojo_id = ?))
        ORDER BY a.publish_date DESC, a.id DESC
    ");
    $stmt->execute([(int)$dojo_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT a.*
        FROM announcements a
        WHERE a.status = 'active'
          AND a.publish_date <= CURDATE()
          AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
          AND a.level = 'global'
        ORDER BY a.publish_date DESC, a.id DESC
    ");
    $stmt->execute();
}

$announcements = $stmt->fetchAll();

$global_count = 0;
$dojo_count = 0;
foreach ($announcements as $announcement) {
    if ($announcement['level'] === 'global') {
        $global_count++;
    } else {
        $dojo_count++;
    }
}
?>

<style>
    .ann-page {
        margin: -1rem -0.75rem 0;
        min-height: 100vh;
        padding: 1.25rem .75rem 3rem;
        background:
            radial-gradient(circle at 90% 0%, rgba(229, 9, 20, .12), transparent 30%),
            radial-gradient(circle at 0% 15%, rgba(244, 189, 23, .08), transparent 28%),
            #070707;
        color: #f5f5f5;
    }
    .ann-shell { max-width: 1120px; margin: 0 auto; }
    .ann-hero {
        position: relative;
        overflow: hidden;
        padding: 1.6rem;
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(244,189,23,.13), rgba(229,9,20,.08) 48%, rgba(255,255,255,.02));
        border: 1px solid rgba(244,189,23,.18);
        box-shadow: 0 18px 55px rgba(0,0,0,.35);
        margin-bottom: 1rem;
    }
    .ann-hero::after {
        content: '';
        position: absolute;
        right: -70px;
        top: -80px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        border: 1px solid rgba(244,189,23,.13);
        box-shadow: 0 0 0 30px rgba(229,9,20,.03), 0 0 0 60px rgba(244,189,23,.02);
    }
    .ann-kicker {
        color: #f4bd17;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .15em;
        text-transform: uppercase;
    }
    .ann-title { margin: .35rem 0 .45rem; font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900; letter-spacing: -.03em; }
    .ann-copy { color: #a7a7a7; margin: 0; max-width: 740px; }
    .ann-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap: .8rem;
        margin-bottom: 1rem;
    }
    .ann-stat {
        background: #111;
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 16px;
        padding: .95rem 1rem;
    }
    .ann-stat-label { color:#777; font-size:.68rem; text-transform:uppercase; letter-spacing:.09em; font-weight:800; }
    .ann-stat-value { font-size:1.55rem; font-weight:900; margin-top:.2rem; }
    .ann-board {
        background: rgba(17,17,17,.94);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 14px 40px rgba(0,0,0,.28);
    }
    .ann-board-head {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:1rem;
        padding:1rem 1.15rem;
        border-bottom:1px solid rgba(255,255,255,.07);
        background:rgba(255,255,255,.02);
    }
    .ann-board-head h3 { margin:0; font-size:1rem; font-weight:800; }
    .ann-board-head span { color:#777; font-size:.78rem; }
    .ann-list { padding: .2rem 1rem; }
    .ann-card {
        position: relative;
        padding: 1.05rem .25rem 1.05rem 1rem;
        border-bottom:1px solid rgba(255,255,255,.06);
    }
    .ann-card:last-child { border-bottom:0; }
    .ann-card::before {
        content:'';
        position:absolute;
        left:0;
        top:1.25rem;
        bottom:1.25rem;
        width:3px;
        border-radius:10px;
        background:#f4bd17;
    }
    .ann-card.dojo::before { background:#e02323; }
    .ann-top { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .ann-tag {
        display:inline-flex;
        align-items:center;
        gap:.4rem;
        padding:.34rem .6rem;
        border-radius:999px;
        font-size:.66rem;
        font-weight:800;
        letter-spacing:.05em;
        text-transform:uppercase;
    }
    .ann-tag.global { background:rgba(244,189,23,.12); color:#f7d25e; }
    .ann-tag.dojo { background:rgba(229,9,20,.12); color:#ff868c; }
    .ann-date { color:#777; font-size:.76rem; }
    .ann-card h4 { color:#fff; font-size:1.08rem; font-weight:850; margin:.7rem 0 .45rem; }
    .ann-content { color:#aaa; line-height:1.7; margin:0; white-space:normal; }
    .ann-expiry { margin-top:.55rem; color:#666; font-size:.72rem; }
    .ann-empty { text-align:center; padding:3.2rem 1.25rem; color:#777; }
    .ann-empty i { color:#555; font-size:2.4rem; margin-bottom:.8rem; }
    .ann-empty strong { display:block; color:#aaa; margin-bottom:.25rem; font-size:1.05rem; }
    .ann-note {
        margin-top:1rem;
        padding:.8rem 1rem;
        border-radius:12px;
        border:1px dashed rgba(244,189,23,.2);
        background:rgba(244,189,23,.04);
        color:#858585;
        font-size:.74rem;
    }
    @media(max-width:767px) {
        .ann-page { margin:-.75rem -.75rem 0; }
        .ann-stats { grid-template-columns:1fr; }
        .ann-hero { padding:1.25rem; border-radius:18px; }
        .ann-board-head { align-items:flex-start; flex-direction:column; }
    }
</style>

<div class="ann-page">
    <div class="ann-shell">
        <section class="ann-hero">
            <div class="ann-kicker"><i class="fas fa-bullhorn me-1"></i> KOMS Student Communications</div>
            <h1 class="ann-title">Announcements</h1>
            <p class="ann-copy">Stay informed about organization news, dojo updates, training notices, events and important instructions.</p>
        </section>

        <section class="ann-stats">
            <div class="ann-stat">
                <div class="ann-stat-label">Total visible</div>
                <div class="ann-stat-value"><?= count($announcements) ?></div>
            </div>
            <div class="ann-stat">
                <div class="ann-stat-label">Organization</div>
                <div class="ann-stat-value"><?= $global_count ?></div>
            </div>
            <div class="ann-stat">
                <div class="ann-stat-label">My dojo</div>
                <div class="ann-stat-value"><?= $dojo_count ?></div>
            </div>
        </section>

        <section class="ann-board">
            <div class="ann-board-head">
                <h3><i class="fas fa-newspaper me-2" style="color:#f4bd17"></i>Latest Updates</h3>
                <span>Published &amp; currently active</span>
            </div>

            <div class="ann-list">
                <?php if ($announcements): ?>
                    <?php foreach ($announcements as $a): ?>
                        <article class="ann-card <?= $a['level'] === 'dojo' ? 'dojo' : '' ?>">
                            <div class="ann-top">
                                <?php if ($a['level'] === 'global'): ?>
                                    <span class="ann-tag global"><i class="fas fa-globe"></i> Organization</span>
                                <?php else: ?>
                                    <span class="ann-tag dojo"><i class="fas fa-torii-gate"></i> My Dojo</span>
                                <?php endif; ?>
                                <span class="ann-date"><i class="far fa-calendar me-1"></i><?= date('l, F j, Y', strtotime($a['publish_date'])) ?></span>
                            </div>

                            <h4><?= htmlspecialchars($a['title']) ?></h4>
                            <p class="ann-content"><?= nl2br(htmlspecialchars($a['content'])) ?></p>

                            <?php if (!empty($a['expiry_date'])): ?>
                                <div class="ann-expiry"><i class="far fa-clock me-1"></i>Visible until <?= date('M j, Y', strtotime($a['expiry_date'])) ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ann-empty">
                        <i class="far fa-bell-slash"></i>
                        <strong>No active announcements</strong>
                        You are all caught up. New organization or dojo updates will appear here.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="ann-note">
            <i class="fas fa-info-circle me-1"></i>
            Only announcements that are active, published and not expired are shown. Dojo announcements are limited to your approved dojo.
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
