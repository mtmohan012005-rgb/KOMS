<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int) $_SESSION['user_id'];
$page_title = 'My Attendance';

// Attendance history for the logged-in student.
$stmt = $pdo->prepare("
    SELECT
        s.id AS session_id,
        s.session_date,
        s.start_time,
        s.end_time,
        s.is_locked,
        e.status,
        e.remarks,
        u.first_name AS instructor_first_name,
        u.last_name AS instructor_last_name,
        d.name AS dojo_name
    FROM attendance_entries e
    INNER JOIN attendance_sessions s ON e.session_id = s.id
    LEFT JOIN users u ON s.instructor_id = u.id
    LEFT JOIN dojos d ON s.dojo_id = d.id
    WHERE e.student_id = ?
    ORDER BY s.session_date DESC, s.start_time DESC
");
$stmt->execute([$user_id]);
$records = $stmt->fetchAll();

$total = count($records);
$present = 0;
$absent = 0;
$late = 0;
$excused = 0;
$locked = 0;

foreach ($records as $record) {
    switch ($record['status']) {
        case 'present': $present++; break;
        case 'absent': $absent++; break;
        case 'late': $late++; break;
        case 'excused': $excused++; break;
    }
    if ((int)$record['is_locked'] === 1) {
        $locked++;
    }
}

$percentage = $total > 0 ? round(($present / $total) * 100) : 0;

// Keep the percentage visually meaningful.
if ($percentage >= 80) {
    $score_class = 'good';
} elseif ($percentage >= 60) {
    $score_class = 'watch';
} else {
    $score_class = 'low';
}

require_once '../includes/header.php';
?>

<style>
    .attendance-page {
        --md-red: #e50914;
        --md-gold: #f4bd17;
        --md-dark: #090909;
        --md-panel: #121212;
        --md-line: rgba(255,255,255,.08);
        --md-muted: #a4a4a4;
        margin-top: -1.5rem;
        padding: 2rem 0 3rem;
    }

    .attendance-hero {
        background:
            radial-gradient(circle at 85% 20%, rgba(229,9,20,.20), transparent 32%),
            radial-gradient(circle at 15% 80%, rgba(244,189,23,.12), transparent 28%),
            linear-gradient(135deg, #090909 0%, #151515 100%);
        border: 1px solid rgba(244,189,23,.18);
        border-radius: 24px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(0,0,0,.28);
    }

    .attendance-hero::after {
        content: '';
        position: absolute;
        width: 190px;
        height: 190px;
        right: -50px;
        top: -60px;
        border: 1px solid rgba(244,189,23,.25);
        border-radius: 50%;
    }

    .eyebrow {
        color: var(--md-gold);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .hero-title {
        color: #fff;
        font-weight: 800;
        margin: .35rem 0 .55rem;
    }

    .hero-copy {
        color: #b8b8b8;
        max-width: 720px;
        margin-bottom: 0;
    }

    .score-panel {
        background: rgba(255,255,255,.04);
        border: 1px solid var(--md-line);
        border-radius: 18px;
        padding: 1.2rem 1.3rem;
        min-width: 210px;
        text-align: center;
        backdrop-filter: blur(8px);
    }

    .score-label {
        color: #a7a7a7;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
    }

    .score-value {
        font-size: 2.45rem;
        line-height: 1;
        font-weight: 900;
        margin-top: .35rem;
    }

    .score-value.good { color: #39d98a; }
    .score-value.watch { color: var(--md-gold); }
    .score-value.low { color: #ff626b; }

    .metric-card {
        background: var(--md-panel);
        border: 1px solid var(--md-line);
        border-radius: 18px;
        padding: 1.15rem;
        height: 100%;
    }

    .metric-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(244,189,23,.10);
        color: var(--md-gold);
        margin-bottom: .8rem;
    }

    .metric-label {
        color: #9d9d9d;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .metric-number {
        color: #fff;
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .attendance-table-wrap {
        background: var(--md-panel);
        border: 1px solid var(--md-line);
        border-radius: 20px;
        overflow: hidden;
    }

    .section-head {
        padding: 1.25rem 1.35rem;
        border-bottom: 1px solid var(--md-line);
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
    }

    .section-title {
        color: #fff;
        font-weight: 800;
        margin: 0;
    }

    .table-dark-custom {
        --bs-table-bg: transparent;
        --bs-table-color: #efefef;
        --bs-table-hover-bg: rgba(255,255,255,.025);
        margin: 0;
    }

    .table-dark-custom thead th {
        color: #929292;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-size: .68rem;
        white-space: nowrap;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--md-line);
        background: #0e0e0e;
    }

    .table-dark-custom tbody td {
        padding: 1rem 1.2rem;
        border-color: var(--md-line);
        vertical-align: middle;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .38rem .62rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .status-present { background: rgba(57,217,138,.12); color: #50e19a; }
    .status-absent { background: rgba(229,9,20,.12); color: #ff6870; }
    .status-late { background: rgba(244,189,23,.12); color: #ffd55d; }
    .status-excused { background: rgba(87,171,255,.12); color: #79bdff; }

    .lock-chip {
        color: #8c8c8c;
        font-size: .74rem;
    }

    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #8f8f8f;
    }

    .empty-state i {
        font-size: 2rem;
        color: var(--md-gold);
        margin-bottom: .8rem;
    }

    @media (max-width: 767.98px) {
        .attendance-page { padding-top: 1rem; }
        .attendance-hero { padding: 1.35rem; border-radius: 18px; }
        .hero-title { font-size: 1.6rem; }
        .score-panel { min-width: 100%; margin-top: 1rem; }
        .section-head { align-items: flex-start; flex-direction: column; }
    }
</style>

<div class="attendance-page">
    <div class="container-fluid">
        <div class="attendance-hero mb-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="eyebrow"><i class="fas fa-bullseye me-1"></i> Training record</div>
                    <h1 class="hero-title">My Attendance</h1>
                    <p class="hero-copy">Track your dojo training history, attendance status and recorded remarks in one place.</p>
                </div>
                <div class="col-lg-4 d-flex justify-content-lg-end">
                    <div class="score-panel">
                        <div class="score-label">Overall attendance</div>
                        <div class="score-value <?= $score_class ?>"><?= $percentage ?>%</div>
                        <div class="small text-secondary mt-2"><?= $present ?> of <?= $total ?> sessions marked present</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="metric-label">Total sessions</div>
                    <div class="metric-number"><?= $total ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="metric-label">Present</div>
                    <div class="metric-number"><?= $present ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="metric-label">Late</div>
                    <div class="metric-number"><?= $late ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-lock"></i></div>
                    <div class="metric-label">Locked records</div>
                    <div class="metric-number"><?= $locked ?></div>
                </div>
            </div>
        </div>

        <div class="attendance-table-wrap">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Training history</h3>
                    <div class="small text-secondary mt-1">Read-only records entered by your dojo instructor.</div>
                </div>
                <span class="badge rounded-pill text-bg-dark border border-secondary-subtle px-3 py-2">
                    <?= $total ?> record<?= $total === 1 ? '' : 's' ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Dojo</th>
                            <th>Time</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records): ?>
                            <?php foreach ($records as $record): ?>
                                <?php
                                    $status = $record['status'] ?? 'absent';
                                    $status_class = 'status-' . htmlspecialchars($status);
                                    $status_icon = [
                                        'present' => 'fa-check',
                                        'absent' => 'fa-times',
                                        'late' => 'fa-clock',
                                        'excused' => 'fa-info-circle'
                                    ][$status] ?? 'fa-circle';

                                    $instructor = trim(
                                        ($record['instructor_first_name'] ?? '') . ' ' .
                                        ($record['instructor_last_name'] ?? '')
                                    );
                                ?>
                                <tr>
                                    <td class="fw-semibold text-white">
                                        <?= date('M d, Y', strtotime($record['session_date'])) ?>
                                    </td>
                                    <td>
                                        <span class="text-light"><?= htmlspecialchars($record['dojo_name'] ?: 'Dojo') ?></span>
                                    </td>
                                    <td class="text-secondary">
                                        <?php if ($record['start_time']): ?>
                                            <?= date('g:i A', strtotime($record['start_time'])) ?>
                                            <?php if ($record['end_time']): ?>
                                                <span class="mx-1">–</span><?= date('g:i A', strtotime($record['end_time'])) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($instructor ?: 'Not specified') ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <i class="fas <?= $status_icon ?>"></i>
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                        <?php if ((int)$record['is_locked'] === 1): ?>
                                            <span class="lock-chip ms-2"><i class="fas fa-lock"></i> Locked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary">
                                        <?= htmlspecialchars($record['remarks'] ?: '—') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div><i class="fas fa-calendar-day"></i></div>
                                        <h5 class="text-white">No attendance records yet</h5>
                                        <p class="mb-0">Your dojo attendance will appear here after a training session is recorded.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>