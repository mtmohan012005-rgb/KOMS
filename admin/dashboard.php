<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'Admin Dashboard';

// Dashboard statistics
$stats = [
    'dojos' => (int)$pdo->query("SELECT COUNT(*) FROM dojos")->fetchColumn(),
    'active_dojos' => (int)$pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'approved'")->fetchColumn(),
    'pending_dojos' => (int)$pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'pending'")->fetchColumn(),
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'masters' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'master'")->fetchColumn(),
    'seniors' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'senior'")->fetchColumn(),
    'users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

// Recent users
$recentUsers = $pdo->query("SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Recent activity
$recentLogs = $pdo->query("SELECT a.*, u.first_name, u.last_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();

require_once '../includes/header.php';
?>

<style>
    .admin-page {
        --adm-bg: #070707;
        --adm-card: rgba(15,15,15,.92);
        --adm-card-2: rgba(20,20,20,.94);
        --adm-border: rgba(255,255,255,.08);
        --adm-muted: #858585;
        --adm-text: #f6f6f6;
        --adm-red: #e1261c;
        --adm-gold: #ffd21a;
        margin: -12px -12px -30px;
        padding: 24px 12px 44px;
        min-height: calc(100vh - 70px);
        background:
            radial-gradient(circle at 8% 0%, rgba(211,0,0,.16), transparent 22%),
            radial-gradient(circle at 92% 5%, rgba(255,196,0,.07), transparent 20%),
            #070707;
    }
    .admin-shell { max-width: 1400px; margin: 0 auto; }
    .admin-topbar {
        display:flex; justify-content:space-between; align-items:center; gap:16px;
        margin-bottom:24px; flex-wrap:wrap;
    }
    .admin-kicker { color:var(--adm-gold); font-size:11px; text-transform:uppercase; letter-spacing:1.8px; font-weight:700; }
    .admin-title { margin:3px 0 4px; color:var(--adm-text); font-size:32px; font-weight:800; letter-spacing:-.6px; }
    .admin-subtitle { color:#929292; margin:0; font-size:13px; }
    .admin-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .admin-action {
        display:inline-flex; align-items:center; gap:8px; padding:11px 15px; border-radius:10px;
        border:1px solid var(--adm-border); color:#e7e7e7; text-decoration:none; background:rgba(255,255,255,.035);
        font-size:12px; font-weight:600; transition:.2s ease;
    }
    .admin-action:hover { color:#fff; border-color:rgba(255,210,26,.35); transform:translateY(-1px); }
    .admin-action.primary { background:linear-gradient(90deg,#a90c0c,#e22b20); border-color:transparent; }

    .stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:15px; margin-bottom:20px; }
    .stat-card {
        position:relative; overflow:hidden; border:1px solid var(--adm-border); border-radius:16px;
        background:linear-gradient(145deg,rgba(24,24,24,.97),rgba(10,10,10,.97)); padding:19px;
        box-shadow:0 14px 30px rgba(0,0,0,.24);
    }
    .stat-card::after { content:""; position:absolute; width:110px; height:110px; right:-42px; top:-48px; border-radius:50%; background:rgba(255,210,26,.06); }
    .stat-icon { width:41px; height:41px; display:grid; place-items:center; border-radius:11px; background:rgba(255,255,255,.06); color:var(--adm-gold); margin-bottom:15px; }
    .stat-label { color:#8a8a8a; font-size:11px; text-transform:uppercase; letter-spacing:.9px; }
    .stat-value { color:#fff; font-size:30px; line-height:1; font-weight:800; margin-top:7px; }
    .stat-note { color:#686868; font-size:10px; margin-top:7px; }
    .accent-red .stat-icon { color:#ff6b5d; background:rgba(225,38,28,.1); }
    .accent-green .stat-icon { color:#62d98a; background:rgba(37,180,94,.1); }
    .accent-blue .stat-icon { color:#67b8ff; background:rgba(54,140,255,.1); }

    .panel { border:1px solid var(--adm-border); border-radius:16px; background:var(--adm-card); overflow:hidden; box-shadow:0 16px 36px rgba(0,0,0,.2); }
    .panel-head { padding:17px 19px; border-bottom:1px solid var(--adm-border); display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .panel-head h3 { color:#fff; margin:0; font-size:15px; font-weight:750; }
    .panel-head p { margin:3px 0 0; color:#6f6f6f; font-size:10px; }
    .panel-link { color:#aaa; text-decoration:none; font-size:11px; }
    .panel-link:hover { color:#fff; }

    .overview-grid { display:grid; grid-template-columns:1.55fr 1fr; gap:18px; margin-bottom:18px; }
    .quick-grid { padding:18px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:11px; }
    .quick-link {
        display:flex; align-items:center; gap:11px; min-height:62px; padding:12px; border-radius:12px;
        background:rgba(255,255,255,.035); border:1px solid rgba(255,255,255,.06); color:#ddd; text-decoration:none;
        transition:.2s ease;
    }
    .quick-link:hover { color:#fff; background:rgba(255,255,255,.06); transform:translateY(-1px); border-color:rgba(255,210,26,.24); }
    .quick-link i { width:36px; height:36px; display:grid; place-items:center; border-radius:10px; background:rgba(205,24,24,.12); color:#ff7167; }
    .quick-link strong { display:block; font-size:12px; }
    .quick-link span { display:block; margin-top:3px; color:#707070; font-size:10px; }

    .role-list { padding:6px 18px 12px; }
    .role-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid rgba(255,255,255,.055); }
    .role-row:last-child { border-bottom:0; }
    .role-left { display:flex; align-items:center; gap:10px; }
    .role-dot { width:9px; height:9px; border-radius:50%; background:var(--adm-gold); box-shadow:0 0 10px rgba(255,210,26,.4); }
    .role-name { color:#d9d9d9; font-size:12px; }
    .role-count { color:#fff; font-weight:800; font-size:15px; }

    .bottom-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:18px; }
    .table-wrap { overflow-x:auto; }
    .admin-table { width:100%; border-collapse:collapse; min-width:620px; }
    .admin-table th { color:#666; font-size:10px; text-transform:uppercase; letter-spacing:.8px; text-align:left; padding:13px 16px; border-bottom:1px solid var(--adm-border); }
    .admin-table td { color:#d3d3d3; font-size:11px; padding:13px 16px; border-bottom:1px solid rgba(255,255,255,.05); vertical-align:middle; }
    .admin-table tr:last-child td { border-bottom:0; }
    .user-main { display:flex; align-items:center; gap:9px; }
    .avatar { width:32px; height:32px; border-radius:10px; display:grid; place-items:center; background:linear-gradient(135deg,#282828,#111); color:var(--adm-gold); font-size:11px; font-weight:800; }
    .user-name { color:#eee; font-size:11px; font-weight:650; }
    .user-email { color:#666; font-size:9px; margin-top:2px; }
    .role-badge, .status-badge { display:inline-flex; align-items:center; border-radius:999px; padding:5px 8px; font-size:9px; font-weight:700; }
    .role-badge { background:rgba(255,210,26,.09); color:#f7d95e; }
    .status-badge.approved { background:rgba(56,182,105,.1); color:#6be194; }
    .status-badge.pending { background:rgba(255,171,0,.1); color:#ffc65d; }

    .activity-list { padding:8px 18px 13px; }
    .activity { display:flex; gap:10px; padding:12px 0; border-bottom:1px solid rgba(255,255,255,.05); }
    .activity:last-child { border-bottom:0; }
    .activity-icon { flex:0 0 30px; width:30px; height:30px; border-radius:9px; display:grid; place-items:center; color:#ff7469; background:rgba(201,21,21,.12); font-size:11px; }
    .activity-title { color:#dedede; font-size:11px; font-weight:650; }
    .activity-text { color:#747474; margin-top:3px; font-size:10px; line-height:1.45; }
    .activity-time { color:#555; margin-top:4px; font-size:9px; }
    .empty-state { padding:28px 18px; color:#666; text-align:center; font-size:11px; }

    @media (max-width: 1050px) { .stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .overview-grid,.bottom-grid { grid-template-columns:1fr; } }
    @media (max-width: 640px) {
        .admin-page { margin:-8px -8px -24px; padding:16px 8px 32px; }
        .admin-title { font-size:25px; }
        .stat-grid { grid-template-columns:1fr 1fr; gap:10px; }
        .stat-card { padding:15px; }
        .stat-value { font-size:25px; }
        .quick-grid { grid-template-columns:1fr; }
        .admin-actions { width:100%; }
        .admin-action { flex:1; justify-content:center; }
    }
</style>

<div class="admin-page">
    <div class="admin-shell">
        <div class="admin-topbar">
            <div>
                <div class="admin-kicker">Mass Dragon Dojo • KOMS</div>
                <h1 class="admin-title">Command Center</h1>
                <p class="admin-subtitle">Monitor dojos, members, approvals and system activity from one place.</p>
            </div>
            <div class="admin-actions">
                <a href="<?= APP_URL ?>/admin/dojos.php" class="admin-action"><i class="fas fa-building"></i> Dojos</a>
                <a href="<?= APP_URL ?>/admin/users.php" class="admin-action"><i class="fas fa-users"></i> Users</a>
                <a href="<?= APP_URL ?>/admin/dojos.php" class="admin-action primary"><i class="fas fa-plus"></i> Add / Manage</a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-torii-gate"></i></div>
                <div class="stat-label">Total Dojos</div>
                <div class="stat-value"><?= $stats['dojos'] ?></div>
                <div class="stat-note">All registered locations</div>
            </div>
            <div class="stat-card accent-green">
                <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                <div class="stat-label">Active Dojos</div>
                <div class="stat-value"><?= $stats['active_dojos'] ?></div>
                <div class="stat-note">Approved and operating</div>
            </div>
            <div class="stat-card accent-red">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-value"><?= $stats['pending_dojos'] ?></div>
                <div class="stat-note">Requires admin attention</div>
            </div>
            <div class="stat-card accent-blue">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-label">Students</div>
                <div class="stat-value"><?= $stats['students'] ?></div>
                <div class="stat-note">Registered karate students</div>
            </div>
        </div>

        <div class="overview-grid">
            <section class="panel">
                <div class="panel-head">
                    <div><h3>Quick Management</h3><p>Jump directly into key administration areas.</p></div>
                </div>
                <div class="quick-grid">
                    <a href="<?= APP_URL ?>/admin/dojos.php" class="quick-link"><i class="fas fa-torii-gate"></i><div><strong>Manage Dojos</strong><span>Approve and manage dojo records</span></div></a>
                    <a href="<?= APP_URL ?>/admin/users.php" class="quick-link"><i class="fas fa-users"></i><div><strong>Manage Users</strong><span>Students, masters and seniors</span></div></a>
                    <a href="<?= APP_URL ?>/admin/tournaments.php" class="quick-link"><i class="fas fa-trophy"></i><div><strong>Tournaments</strong><span>Events and registrations</span></div></a>
                    <a href="<?= APP_URL ?>/admin/announcements.php" class="quick-link"><i class="fas fa-bullhorn"></i><div><strong>Announcements</strong><span>Publish organization notices</span></div></a>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div><h3>People Overview</h3><p>Current user distribution by role.</p></div>
                </div>
                <div class="role-list">
                    <div class="role-row"><div class="role-left"><span class="role-dot"></span><span class="role-name">All Users</span></div><span class="role-count"><?= $stats['users'] ?></span></div>
                    <div class="role-row"><div class="role-left"><span class="role-dot"></span><span class="role-name">Masters</span></div><span class="role-count"><?= $stats['masters'] ?></span></div>
                    <div class="role-row"><div class="role-left"><span class="role-dot"></span><span class="role-name">Senior / Sub-Admin</span></div><span class="role-count"><?= $stats['seniors'] ?></span></div>
                    <div class="role-row"><div class="role-left"><span class="role-dot"></span><span class="role-name">Students</span></div><span class="role-count"><?= $stats['students'] ?></span></div>
                </div>
            </section>
        </div>

        <div class="bottom-grid">
            <section class="panel">
                <div class="panel-head">
                    <div><h3>New Members</h3><p>Most recently created user accounts.</p></div>
                    <a class="panel-link" href="<?= APP_URL ?>/admin/users.php">View all <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <?php if ($recentUsers): ?>
                    <div class="table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Member</th><th>Role</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <?php $initials = strtoupper(substr($user['first_name'] ?? 'U',0,1) . substr($user['last_name'] ?? '',0,1)); ?>
                                <tr>
                                    <td><div class="user-main"><div class="avatar"><?= htmlspecialchars($initials) ?></div><div><div class="user-name"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></div><div class="user-email"><?= htmlspecialchars($user['email']) ?></div></div></div></td>
                                    <td><span class="role-badge"><?= htmlspecialchars(ucwords(str_replace('_',' ', $user['role']))) ?></span></td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No users found.</div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div><h3>Recent Activity</h3><p>Latest audit events in the system.</p></div>
                    <a class="panel-link" href="<?= APP_URL ?>/admin/audit.php">Audit log <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="activity-list">
                    <?php if ($recentLogs): ?>
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="activity">
                                <div class="activity-icon"><i class="fas fa-shield-halved"></i></div>
                                <div>
                                    <div class="activity-title"><?= htmlspecialchars($log['action']) ?></div>
                                    <div class="activity-text"><?= htmlspecialchars($log['description']) ?></div>
                                    <div class="activity-time"><i class="far fa-clock me-1"></i><?= htmlspecialchars(date('d M, g:i a', strtotime($log['created_at']))) ?> · <?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No recent activity yet.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
