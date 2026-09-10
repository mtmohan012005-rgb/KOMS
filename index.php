<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = 'Welcome';
require_once 'includes/header.php';
?>

<div class="row align-items-center mt-5">
    <div class="col-lg-6">
        <h1 class="display-4 fw-bold">Manage Your Karate Organization Effectively</h1>
        <p class="lead mt-3">KOMS is a centralized platform designed to digitize and manage operations across multiple dojos, tracking attendance, fees, grading, and tournaments.</p>
        <div class="mt-4">
            <?php if (is_logged_in()): 
                $my_dash = '/index.php';
                if (has_role('super_admin')) $my_dash = '/admin/dashboard.php';
                elseif (has_role('master')) $my_dash = '/master/dashboard.php';
                elseif (has_role('senior')) $my_dash = '/senior/dashboard.php';
                elseif (has_role('student')) $my_dash = '/student/dashboard.php';
            ?>
                <a href="<?= APP_URL . $my_dash ?>" class="btn btn-primary btn-lg px-4 me-md-2"><i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard</a>
                <a href="<?= APP_URL ?>/index.html" class="btn btn-dark btn-lg px-4 border-warning"><i class="fas fa-dragon text-warning me-2"></i>Mass Dragon Experience</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg px-4 me-md-2">Get Started</a>
                <a href="<?= APP_URL ?>/find_dojo.php" class="btn btn-outline-secondary btn-lg px-4 me-md-2">Find a Dojo</a>
                <a href="<?= APP_URL ?>/index.html" class="btn btn-dark btn-lg px-4 border-warning"><i class="fas fa-dragon text-warning me-2"></i>Mass Dragon Experience</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <img src="https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=800&q=80" alt="Karate Training" class="img-fluid rounded shadow-lg mt-4 mt-lg-0">
    </div>
</div>

<div class="row mt-5 pt-5">
    <div class="col-md-4 text-center mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h3>Dojo Management</h3>
                <p class="text-muted">Register and manage your dojo, track students, and organize classes seamlessly.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-center mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                <h3>Attendance & Fees</h3>
                <p class="text-muted">Take attendance for specific sessions and manage fee payments with historical records.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-center mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <i class="fas fa-medal fa-3x text-primary mb-3"></i>
                <h3>Grading & Tournaments</h3>
                <p class="text-muted">Track belt progression, achievements, and organize cross-dojo tournaments easily.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
