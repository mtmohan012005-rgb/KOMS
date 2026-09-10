<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= APP_URL ?>/index.php">
            <img src="<?= APP_URL ?>/assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu" style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid #f4bd17; object-fit: cover;">
            <span><span style="color: #ffcc00;">MASS DRAGON DOJO</span> <span style="font-size: 0.75rem; color: #888; font-weight: normal;">| KOMS</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (!is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/find_dojo.php">Find Dojo</a></li>
                <?php else: ?>
                    <?php if (has_role('super_admin')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dojos.php">Dojos</a></li>
                    <?php elseif (has_role('master')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/students.php">Students</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/master/attendance.php">Attendance</a></li>
                    <?php elseif (has_role('senior')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/senior/dashboard.php">Dashboard</a></li>
                    <?php elseif (has_role('student')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/student/my_dojo.php">My Dojo</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/profile.php">Profile</a></li>
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
