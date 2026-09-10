<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/auth.php';
}

$pending_reqs_badge = 0;
if (is_logged_in() && has_role('master')) {
    try {
        $stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM password_reset_requests prr JOIN dojos d ON prr.dojo_id = d.id WHERE d.master_id = ? AND prr.status = 'pending'");
        $stmt_badge->execute([$_SESSION['user_id']]);
        $pending_reqs_badge = (int)$stmt_badge->fetchColumn();
    } catch (Exception $e) {
        $pending_reqs_badge = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&family=Cinzel:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- KOMS Unified Responsive Portal Theme -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/koms-portal-theme.css?v=2">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=2">
    
    <style>
        body {
            background-color: #07080a !important;
            color: #ffffff !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .koms-subpage-main {
            flex: 1 0 auto;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 88px 24px 80px;
        }
        .navbar-koms {
            background: rgba(10, 12, 16, 0.94) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255, 204, 0, 0.15);
            height: 70px;
        }
        .navbar-koms .nav-link {
            color: #cbd5e1 !important;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .navbar-koms .nav-link:hover, .navbar-koms .nav-link.active {
            color: #ffcc00 !important;
            background: rgba(255, 204, 0, 0.1);
        }
        .dropdown-menu-dark-koms {
            background: #12141a !important;
            border: 1px solid rgba(255, 204, 0, 0.2) !important;
            border-radius: 12px !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.7) !important;
        }
        .dropdown-menu-dark-koms .dropdown-item {
            color: #cbd5e1 !important;
            font-size: 0.85rem;
            padding: 8px 16px;
        }
        .dropdown-menu-dark-koms .dropdown-item:hover {
            background: rgba(255, 204, 0, 0.12) !important;
            color: #ffcc00 !important;
        }
    </style>
</head>
<body class="koms-body">

<!-- Ambient Background Artwork -->
<div class="koms-ambient-bg"></div>

<!-- Top Navigation Bar Matching Mockup -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-koms">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/index.php">
            <img src="<?= APP_URL ?>/assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu" style="width:40px;height:40px;border-radius:50%;border:2px solid #ffcc00;box-shadow:0 0 14px rgba(255,204,0,0.4);object-fit:cover;">
            <div style="line-height:1.1;">
                <span style="font-family:'Caveat Brush',cursive;font-size:1.35rem;color:#ffcc00;letter-spacing:0.8px;">MASS DRAGON DOJO</span>
                <span style="display:block;font-size:0.65rem;color:#9ba1ad;text-transform:uppercase;letter-spacing:0.5px;">Karate Organization Management System</span>
            </div>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#komsSubNav" aria-controls="komsSubNav" aria-expanded="false" aria-label="Toggle navigation" style="border-color:rgba(255,204,0,0.3);">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="komsSubNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center ms-lg-3">
                <?php if (!is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/find_dojo.php"><i class="fas fa-torii-gate me-1"></i>Find Dojo</a></li>
                <?php else: ?>
                    <?php if (has_role('super_admin')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><i class="fas fa-gauge-high me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dojos.php"><i class="fas fa-torii-gate me-1"></i>Dojos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-users me-1"></i>Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/reports.php"><i class="fas fa-chart-pie me-1"></i>Reports</a></li>
                    <?php elseif (has_role('master')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/index.php"><i class="fas fa-gauge-high me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/students.php"><i class="fas fa-users me-1"></i>Students</a></li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-1" href="<?= APP_URL ?>/master/password_requests.php">
                                <i class="fas fa-key me-1"></i>Password Requests
                                <?php if ($pending_reqs_badge > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.65rem;"><?= $pending_reqs_badge ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/attendance.php"><i class="fas fa-calendar-check me-1"></i>Attendance</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-layer-group me-1"></i>Management
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark-koms">
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/fees.php"><i class="fas fa-rupee-sign me-2 text-warning"></i>Fees &amp; Payments</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/grading.php"><i class="fas fa-medal me-2 text-warning"></i>Grading &amp; Belts</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/tournaments.php"><i class="fas fa-trophy me-2 text-warning"></i>Tournaments</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/events.php"><i class="fas fa-calendar-alt me-2 text-warning"></i>Events</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/announcements.php"><i class="fas fa-bullhorn me-2 text-warning"></i>Announcements</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/certificates.php"><i class="fas fa-certificate me-2 text-warning"></i>Certificates</a></li>
                            </ul>
                        </li>
                    <?php elseif (has_role('student')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/attendance.php"><i class="fas fa-calendar-check me-1"></i>Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/grading.php"><i class="fas fa-medal me-1"></i>Belt Grading</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/my_dojo.php"><i class="fas fa-torii-gate me-1"></i>My Dojo</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-2">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="<?= APP_URL ?>/uploads/koms-mobile.apk" download title="Download Android App">
                        <i class="fab fa-android" style="color:#3ddc84;font-size:16px;"></i>
                        <span style="font-size:0.8rem;">Mobile App</span>
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1 px-3" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,204,0,0.25);border-radius:999px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#ffd700,#d49b06);color:#1a1202;display:grid;place-items:center;font-weight:800;font-size:0.75rem;">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span style="font-size:0.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark-koms">
                            <li><a class="dropdown-item" href="<?= has_role('master') ? APP_URL . '/master/profile.php' : APP_URL . '/profile.php' ?>"><i class="fas fa-user-shield me-2"></i>Profile &amp; Security</a></li>
                            <?php if (has_role('master')): ?>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/settings.php"><i class="fas fa-cog me-2"></i>Dojo Settings</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.1);"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php">Sign In</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm fw-bold px-3 ms-2" href="<?= APP_URL ?>/register.php" style="border-radius:999px;">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="koms-subpage-main">
<?php display_alert(); ?>
