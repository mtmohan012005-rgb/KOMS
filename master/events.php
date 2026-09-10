<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

/*
 * KOMS does not currently require a separate events table in the base schema.
 * Create it safely at runtime so the Master Events module can work on an
 * existing deployment without requiring a manual database import.
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dojo_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    start_time TIME NULL,
    location VARCHAR(180) NULL,
    status ENUM('planned','completed','cancelled') NOT NULL DEFAULT 'planned',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_events_dojo_date (dojo_id, event_date),
    INDEX idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage events.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$master_id = (int)$_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $event_id = (int)($_POST['event_id'] ?? 0);
            $title = trim(sanitize_input($_POST['title'] ?? ''));
            $description = trim(sanitize_input($_POST['description'] ?? ''));
            $event_date = trim(sanitize_input($_POST['event_date'] ?? ''));
            $start_time = trim(sanitize_input($_POST['start_time'] ?? ''));
            $location = trim(sanitize_input($_POST['location'] ?? ''));
            $status = trim(sanitize_input($_POST['status'] ?? 'planned'));
            $allowed_statuses = ['planned', 'completed', 'cancelled'];

            if ($title === '' || $event_date === '') {
                $error = 'Event title and date are required.';
            } elseif (!in_array($status, $allowed_statuses, true)) {
                $error = 'Invalid event status.';
            } elseif (strtotime($event_date) === false) {
                $error = 'Please enter a valid event date.';
            } elseif ($start_time !== '' && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
                $error = 'Please enter a valid start time.';
            } else {
                if ($event_id > 0) {
                    $check = $pdo->prepare("SELECT id FROM events WHERE id = ? AND dojo_id = ? LIMIT 1");
                    $check->execute([$event_id, $dojo_id]);

                    if (!$check->fetchColumn()) {
                        $error = 'Event not found.';
                    } else {
                        $update = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, start_time = ?, location = ?, status = ? WHERE id = ? AND dojo_id = ?");
                        $update->execute([
                            $title,
                            $description !== '' ? $description : null,
                            $event_date,
                            $start_time !== '' ? $start_time : null,
                            $location !== '' ? $location : null,
                            $status,
                            $event_id,
                            $dojo_id
                        ]);

                        log_audit_action($pdo, $master_id, 'UPDATE', 'events', $event_id, 'Updated dojo event: ' . $title);
                        $_SESSION['success_msg'] = 'Event updated successfully.';
                        redirect('/master/events.php');
                    }
                } else {
                    $insert = $pdo->prepare("INSERT INTO events (dojo_id, title, description, event_date, start_time, location, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $dojo_id,
                        $title,
                        $description !== '' ? $description : null,
                        $event_date,
                        $start_time !== '' ? $start_time : null,
                        $location !== '' ? $location : null,
                        $status,
                        $master_id
                    ]);

                    $new_id = (int)$pdo->lastInsertId();
                    log_audit_action($pdo, $master_id, 'CREATE', 'events', $new_id, 'Created dojo event: ' . $title);
                    $_SESSION['success_msg'] = 'Event created successfully.';
                    redirect('/master/events.php');
                }
            }
        } elseif ($action === 'delete') {
            $event_id = (int)($_POST['event_id'] ?? 0);

            if ($event_id <= 0) {
                $error = 'Invalid event.';
            } else {
                $check = $pdo->prepare("SELECT title FROM events WHERE id = ? AND dojo_id = ? LIMIT 1");
                $check->execute([$event_id, $dojo_id]);
                $event_title = $check->fetchColumn();

                if (!$event_title) {
                    $error = 'Event not found.';
                } else {
                    $delete = $pdo->prepare("DELETE FROM events WHERE id = ? AND dojo_id = ?");
                    $delete->execute([$event_id, $dojo_id]);
                    log_audit_action($pdo, $master_id, 'DELETE', 'events', $event_id, 'Deleted dojo event: ' . $event_title);
                    $_SESSION['success_msg'] = 'Event deleted successfully.';
                    redirect('/master/events.php');
                }
            }
        }
    }
}

$edit_event = null;
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    $edit_stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND dojo_id = ? LIMIT 1");
    $edit_stmt->execute([$edit_id, $dojo_id]);
    $edit_event = $edit_stmt->fetch();
}

$today = date('Y-m-d');

$count_stmt = $pdo->prepare("SELECT
    COUNT(*) AS total,
    SUM(event_date >= ?) AS upcoming,
    SUM(event_date = ?) AS today_count,
    SUM(status = 'completed') AS completed
    FROM events WHERE dojo_id = ?");
$count_stmt->execute([$today, $today, $dojo_id]);
$stats = $count_stmt->fetch() ?: ['total' => 0, 'upcoming' => 0, 'today_count' => 0, 'completed' => 0];

$event_stmt = $pdo->prepare("SELECT id, title, description, event_date, start_time, location, status, created_at FROM events WHERE dojo_id = ? ORDER BY event_date DESC, COALESCE(start_time, '23:59:59') DESC, id DESC");
$event_stmt->execute([$dojo_id]);
$events = $event_stmt->fetchAll();

$page_title = 'Events';
require_once '../includes/header.php';
?>

<style>
    .event-wrap{max-width:1200px;margin:1.5rem auto}
    .event-hero{border-radius:24px;padding:1.8rem 2rem;color:#fff;background:linear-gradient(135deg,#080808,#1c1c1c 58%,#5e0b0b);box-shadow:0 22px 55px rgba(0,0,0,.15)}
    .event-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd45f}
    .event-title{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;margin:.3rem 0}.event-sub{color:rgba(255,255,255,.72);max-width:750px}
    .event-stat,.event-card{background:#fff;border:0;border-radius:20px;box-shadow:0 14px 38px rgba(17,24,39,.08)}
    .event-stat{padding:1.15rem;height:100%}.event-stat .num{font-size:1.8rem;font-weight:900}
    .event-card{overflow:hidden}.event-card .card-header{background:#fff;border:0;padding:1.15rem 1.3rem}
    .form-control,.form-select{border-radius:12px;padding:.72rem .85rem}.form-label{font-size:.85rem;font-weight:750}
    .event-row td{padding-top:1rem;padding-bottom:1rem}.event-name{font-weight:850}.event-meta{font-size:.78rem;color:#888}
    .event-badge{display:inline-flex;border-radius:999px;padding:.4rem .7rem;font-size:.7rem;font-weight:850;text-transform:uppercase}.planned{background:#fff0c2;color:#6d5000}.completed{background:#dcf6e5;color:#176b38}.cancelled{background:#ffe1e1;color:#9a1c1c}
    .calendar-box{width:48px;height:54px;border-radius:12px;background:#f3f3f3;text-align:center;display:flex;flex-direction:column;justify-content:center;font-weight:900;line-height:1.05}.calendar-box small{font-size:.63rem;letter-spacing:.08em;color:#a00}.calendar-box span{font-size:1.15rem}
    @media(max-width:768px){.event-wrap{margin:1rem auto}.event-hero{padding:1.35rem}.event-table{min-width:900px}}
</style>

<div class="event-wrap">
    <section class="event-hero mb-4">
        <div class="event-kicker">Master Control • Dojo Calendar</div>
        <div class="event-title">Events Management</div>
        <p class="event-sub mb-0">Create and manage dojo events, training activities, seminars, celebrations and other important dates for your students.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="event-stat"><div class="small text-muted text-uppercase fw-bold">Total Events</div><div class="num mt-1"><?= (int)$stats['total'] ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="event-stat"><div class="small text-muted text-uppercase fw-bold">Upcoming</div><div class="num text-primary mt-1"><?= (int)$stats['upcoming'] ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="event-stat"><div class="small text-muted text-uppercase fw-bold">Today</div><div class="num text-danger mt-1"><?= (int)$stats['today_count'] ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="event-stat"><div class="small text-muted text-uppercase fw-bold">Completed</div><div class="num text-success mt-1"><?= (int)$stats['completed'] ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card event-card">
                <div class="card-header">
                    <div class="small text-muted text-uppercase fw-bold"><?= $edit_event ? 'Edit Event' : 'Create Event' ?></div>
                    <h5 class="mb-0 mt-1"><?= $edit_event ? 'Update Event Details' : 'Add New Event' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="event_id" value="<?= (int)($edit_event['id'] ?? 0) ?>">

                        <div class="mb-3"><label class="form-label">Event Title *</label><input class="form-control" name="title" maxlength="180" required value="<?= htmlspecialchars($edit_event['title'] ?? '') ?>" placeholder="Belt Training Seminar"></div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4" maxlength="5000" placeholder="Add event details for the dojo."><?= htmlspecialchars($edit_event['description'] ?? '') ?></textarea></div>
                        <div class="row g-2">
                            <div class="col-7"><label class="form-label">Date *</label><input type="date" class="form-control" name="event_date" required value="<?= htmlspecialchars($edit_event['event_date'] ?? $today) ?>"></div>
                            <div class="col-5"><label class="form-label">Start Time</label><input type="time" class="form-control" name="start_time" value="<?= htmlspecialchars(isset($edit_event['start_time']) ? substr((string)$edit_event['start_time'],0,5) : '') ?>"></div>
                        </div>
                        <div class="mb-3 mt-3"><label class="form-label">Location</label><input class="form-control" name="location" maxlength="180" value="<?= htmlspecialchars($edit_event['location'] ?? '') ?>" placeholder="Old Perungalathur Dojo"></div>
                        <div class="mb-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['planned'=>'Planned','completed'=>'Completed','cancelled'=>'Cancelled'] as $key=>$label): ?><option value="<?= $key ?>" <?= (($edit_event['status'] ?? 'planned') === $key) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-dark flex-grow-1" type="submit"><i class="fas fa-calendar-plus me-2"></i><?= $edit_event ? 'Update Event' : 'Create Event' ?></button>
                            <?php if ($edit_event): ?><a href="events.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card event-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><div class="small text-muted text-uppercase fw-bold"><?= htmlspecialchars($dojo['name']) ?></div><h5 class="mb-0 mt-1">Dojo Event Calendar</h5></div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 event-table">
                            <thead class="table-light"><tr><th>Event</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($events as $event): ?>
                                <?php $event_ts = strtotime($event['event_date']); ?>
                                <tr class="event-row">
                                    <td><div class="d-flex align-items-center gap-3"><div class="calendar-box"><small><?= strtoupper(date('M',$event_ts)) ?></small><span><?= date('d',$event_ts) ?></span></div><div><div class="event-name"><?= htmlspecialchars($event['title']) ?></div><div class="event-meta"><?= htmlspecialchars($event['description'] ?: 'No description') ?></div></div></div></td>
                                    <td><?= date('M j, Y', $event_ts) ?><?php if ($event['event_date'] === $today): ?><div class="small text-danger fw-bold">Today</div><?php endif; ?></td>
                                    <td><?= $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : '—' ?></td>
                                    <td><?= htmlspecialchars($event['location'] ?: '—') ?></td>
                                    <td><span class="event-badge <?= htmlspecialchars($event['status']) ?>"><?= htmlspecialchars($event['status']) ?></span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a class="btn btn-sm btn-outline-dark" href="events.php?edit=<?= (int)$event['id'] ?>" title="Edit"><i class="fas fa-pen"></i></a>
                                            <form method="POST" onsubmit="return confirm('Delete this event?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$events): ?><tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-calendar-days fa-2x mb-2"></i><div class="fw-bold">No events created yet</div><div class="small">Create the first dojo event using the form.</div></td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
