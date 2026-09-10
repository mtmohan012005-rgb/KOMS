<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];
$page_title = 'My Grading & Belt Progress';

$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.previous_belt,
        g.new_belt,
        g.exam_date,
        g.grade,
        g.remarks,
        g.certificate_file,
        u.first_name AS instructor_first_name,
        u.last_name AS instructor_last_name,
        d.name AS dojo_name
    FROM grading_history g
    INNER JOIN users u ON g.instructor_id = u.id
    INNER JOIN dojos d ON g.dojo_id = d.id
    WHERE g.student_id = ?
    ORDER BY g.exam_date DESC, g.id DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

$current_belt = $history[0]['new_belt'] ?? 'White Belt';
$latest_grade = $history[0]['grade'] ?? '—';
$exam_count = count($history);
$certificate_count = 0;

foreach ($history as $row) {
    if (!empty($row['certificate_file'])) {
        $certificate_count++;
    }
}

function grading_class(string $belt): string {
    $belt = strtolower($belt);

    if (str_contains($belt, 'black')) return 'black-belt';
    if (str_contains($belt, 'brown')) return 'brown-belt';
    if (str_contains($belt, 'blue')) return 'blue-belt';
    if (str_contains($belt, 'green')) return 'green-belt';
    if (str_contains($belt, 'yellow')) return 'yellow-belt';
    if (str_contains($belt, 'orange')) return 'orange-belt';

    return 'white-belt';
}
?>

<style>
    .grading-page {
        --g-gold: #f4bd17;
        --g-red: #e50914;
        --g-bg: #070707;
        --g-card: #121212;
        --g-line: rgba(255,255,255,.08);
        color: #f5f5f5;
        margin: -1.5rem -0.75rem 0;
        padding: 1.5rem 0.75rem 3rem;
        min-height: 100vh;
        background:
            radial-gradient(circle at 90% 0%, rgba(229,9,20,.12), transparent 32%),
            radial-gradient(circle at 0% 20%, rgba(244,189,23,.08), transparent 28%),
            var(--g-bg);
    }

    .grading-shell { max-width: 1180px; margin: 0 auto; }

    .grading-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1.25rem;
        padding: 1.6rem;
        border-radius: 20px;
        border: 1px solid rgba(244,189,23,.18);
        background: linear-gradient(135deg, rgba(244,189,23,.12), rgba(229,9,20,.08) 52%, rgba(255,255,255,.02));
        box-shadow: 0 18px 55px rgba(0,0,0,.32);
        margin-bottom: 1.1rem;
    }

    .grading-kicker {
        text-transform: uppercase;
        letter-spacing: .15em;
        color: #aaa;
        font-size: .72rem;
        font-weight: 800;
        margin-bottom: .45rem;
    }

    .grading-kicker i { color: var(--g-gold); margin-right: .35rem; }
    .grading-hero h1 { margin: 0; font-weight: 900; }
    .grading-hero p { margin: .45rem 0 0; color: #929292; }

    .rank-chip {
        min-width: 190px;
        padding: 1rem 1.2rem;
        border-radius: 16px;
        text-align: center;
        border: 1px solid var(--g-line);
        background: rgba(0,0,0,.35);
    }

    .rank-chip .small-label {
        display: block;
        text-transform: uppercase;
        font-size: .67rem;
        color: #777;
        letter-spacing: .08em;
        font-weight: 800;
    }

    .rank-chip .rank {
        display: block;
        margin-top: .3rem;
        font-size: 1.25rem;
        font-weight: 900;
        color: var(--g-gold);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap: .9rem;
        margin-bottom: 1.1rem;
    }

    .grade-stat {
        background: rgba(18,18,18,.94);
        border: 1px solid var(--g-line);
        border-radius: 16px;
        padding: 1rem 1.1rem;
    }

    .grade-stat .label {
        text-transform: uppercase;
        color: #777;
        font-size: .68rem;
        letter-spacing: .08em;
        font-weight: 800;
    }

    .grade-stat .value {
        margin-top: .3rem;
        font-size: 1.5rem;
        font-weight: 900;
    }

    .grade-stat .sub { color: #777; font-size: .78rem; margin-top: .1rem; }

    .timeline {
        position: relative;
        padding-left: 1.2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: .23rem;
        top: .7rem;
        bottom: .7rem;
        width: 2px;
        background: rgba(244,189,23,.16);
    }

    .grade-card {
        position: relative;
        background: rgba(18,18,18,.96);
        border: 1px solid var(--g-line);
        border-radius: 18px;
        padding: 1rem 1.1rem;
        margin-bottom: .9rem;
        box-shadow: 0 10px 28px rgba(0,0,0,.2);
    }

    .grade-card::before {
        content: '';
        position: absolute;
        left: -1.02rem;
        top: 1.35rem;
        width: .62rem;
        height: .62rem;
        border-radius: 50%;
        background: var(--g-gold);
        box-shadow: 0 0 0 5px rgba(244,189,23,.08);
    }

    .grade-top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .grade-date { color: #777; font-size: .76rem; }

    .belt-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
        margin: .6rem 0 .65rem;
    }

    .belt {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .4rem .7rem;
        font-size: .74rem;
        font-weight: 800;
        border: 1px solid rgba(255,255,255,.1);
    }

    .white-belt { background: #f2f2f2; color: #111; }
    .yellow-belt { background: #e6c32e; color: #111; }
    .orange-belt { background: #d97706; color: #fff; }
    .green-belt { background: #16803c; color: #fff; }
    .blue-belt { background: #2563eb; color: #fff; }
    .brown-belt { background: #7c3f16; color: #fff; }
    .black-belt { background: #050505; color: #fff; }

    .arrow-icon { color: #666; }

    .grade-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
        gap: .65rem;
        margin-top: .75rem;
    }

    .meta-box {
        padding: .7rem;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 12px;
    }

    .meta-box .label {
        color: #666;
        font-size: .66rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-weight: 800;
    }

    .meta-box .value { margin-top: .2rem; color: #ddd; font-size: .86rem; }

    .remarks {
        margin-top: .8rem;
        padding: .8rem .9rem;
        border-left: 3px solid rgba(244,189,23,.55);
        background: rgba(244,189,23,.045);
        color: #999;
        font-size: .8rem;
        border-radius: 0 10px 10px 0;
    }

    .certificate-link {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        margin-top: .8rem;
        padding: .48rem .7rem;
        border-radius: 10px;
        text-decoration: none;
        color: #111;
        background: var(--g-gold);
        font-size: .75rem;
        font-weight: 800;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: rgba(18,18,18,.94);
        border: 1px solid var(--g-line);
        border-radius: 18px;
    }

    .empty-state i { color: #555; font-size: 2.5rem; margin-bottom: .8rem; }
    .empty-state h3 { margin-bottom: .3rem; font-weight: 800; }
    .empty-state p { margin: 0; color: #777; }

    @media (max-width: 760px) {
        .grading-hero { flex-direction: column; align-items: flex-start; }
        .rank-chip { width: 100%; }
        .stat-grid { grid-template-columns: 1fr; }
        .grade-meta { grid-template-columns: 1fr; }
    }

    @media (max-width: 576px) {
        .grading-page { margin: -1rem -0.75rem 0; padding-top: 1rem; }
        .grading-hero { padding: 1.2rem; }
        .grade-top { flex-direction: column; }
    }
</style>

<div class="grading-page">
    <div class="grading-shell">
        <section class="grading-hero">
            <div>
                <div class="grading-kicker"><i class="fas fa-medal"></i>KOMS Student Progress</div>
                <h1>Grading & Belt Progress</h1>
                <p>Your recorded belt examinations, grades, instructors, and certificates.</p>
            </div>
            <div class="rank-chip">
                <span class="small-label">Current Rank</span>
                <span class="rank"><?= htmlspecialchars($current_belt) ?></span>
            </div>
        </section>

        <section class="stat-grid">
            <div class="grade-stat">
                <div class="label">Exams recorded</div>
                <div class="value"><?= $exam_count ?></div>
                <div class="sub">Recorded grading history</div>
            </div>
            <div class="grade-stat">
                <div class="label">Latest grade</div>
                <div class="value"><?= htmlspecialchars($latest_grade) ?></div>
                <div class="sub">Most recent examination</div>
            </div>
            <div class="grade-stat">
                <div class="label">Certificates</div>
                <div class="value"><?= $certificate_count ?></div>
                <div class="sub">Certificates attached to records</div>
            </div>
        </section>

        <?php if ($history): ?>
            <div class="timeline">
                <?php foreach ($history as $row): ?>
                    <article class="grade-card">
                        <div class="grade-top">
                            <div>
                                <strong><?= htmlspecialchars($row['dojo_name']) ?></strong>
                                <div class="grade-date">
                                    Exam date: <?= date('F j, Y', strtotime($row['exam_date'])) ?>
                                </div>
                            </div>
                            <div class="grade-date">
                                Instructor: <?= htmlspecialchars(trim($row['instructor_first_name'] . ' ' . $row['instructor_last_name'])) ?>
                            </div>
                        </div>

                        <div class="belt-row">
                            <?php if (!empty($row['previous_belt'])): ?>
                                <span class="belt <?= grading_class($row['previous_belt']) ?>">
                                    <?= htmlspecialchars($row['previous_belt']) ?>
                                </span>
                                <i class="fas fa-arrow-right arrow-icon"></i>
                            <?php endif; ?>

                            <span class="belt <?= grading_class($row['new_belt']) ?>">
                                <i class="fas fa-certificate"></i>
                                <?= htmlspecialchars($row['new_belt']) ?>
                            </span>
                        </div>

                        <div class="grade-meta">
                            <div class="meta-box">
                                <div class="label">Grade / Score</div>
                                <div class="value"><?= htmlspecialchars($row['grade'] ?: 'Not recorded') ?></div>
                            </div>
                            <div class="meta-box">
                                <div class="label">Training Centre</div>
                                <div class="value"><?= htmlspecialchars($row['dojo_name']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($row['remarks'])): ?>
                            <div class="remarks">
                                <?= nl2br(htmlspecialchars($row['remarks'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($row['certificate_file'])): ?>
                            <a class="certificate-link" target="_blank" rel="noopener"
                               href="<?= htmlspecialchars('../uploads/' . basename($row['certificate_file'])) ?>">
                                <i class="fas fa-file-pdf"></i> View Certificate
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-medal"></i>
                <h3>No grading history yet</h3>
                <p>Your Master will record belt examinations and results here as you progress.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
