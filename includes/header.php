<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="bg-dark text-white" style="background-color:#080808 !important;color:#ffffff !important;min-height:100vh;display:flex;flex-direction:column;">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= APP_URL ?>/index.php">
            <img src="<?= APP_URL ?>/assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu" style="width:32px;height:32px;border-radius:50%;border:1px solid #f4bd17;object-fit:cover;">
            <span><span style="color:#ffcc00;">MASS DRAGON DOJO</span> <span style="font-size:.75rem;color:#888;font-weight:normal;">| KOMS</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto align-items-lg-center">
                <?php if (!is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/find_dojo.php">Find Dojo</a></li>
                <?php else: ?>
                    <?php if (has_role('super_admin')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dojos.php">Dojos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/reports.php">Reports</a></li>
                    <?php elseif (has_role('master')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/index.php"><i class="fas fa-gauge-high me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/students.php"><i class="fas fa-users me-1"></i>Students</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/password_requests.php"><i class="fas fa-key me-1"></i>Password Requests</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/attendance.php"><i class="fas fa-calendar-check me-1"></i>Attendance</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-layer-group me-1"></i>Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/fees.php">Fees &amp; Payments</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/grading.php">Grading &amp; Belts</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/tournaments.php">Tournaments</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/events.php">Events</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/announcements.php">Announcements</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/reports.php">Reports</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/inventory.php">Inventory</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/communication.php">Communication</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/certificates.php">Certificates</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/gallery.php">Gallery</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-gear me-1"></i>Account</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/profile.php">Master Profile</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php elseif (has_role('senior')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/senior/dashboard.php">Dashboard</a></li>
                    <?php elseif (has_role('student')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/my_dojo.php">My Dojo</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="<?= APP_URL ?>/uploads/koms-mobile.apk" download title="Download KOMS Android App">
                        <i class="fab fa-android" style="color: #3ddc84;"></i> <span>Mobile App</span>
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= has_role('master') ? APP_URL . '/master/profile.php' : APP_URL . '/profile.php' ?>">Profile</a></li>
                            <?php if (has_role('master')): ?>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/master/settings.php">Settings</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light ms-2" href="<?= APP_URL ?>/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-5 min-vh-100">
<?php display_alert(); ?>
