</main> <!-- End koms-subpage-main -->

<footer style="background:#0a0c10;border-top:1px solid rgba(255,204,0,0.12);padding:24px 0;text-align:center;color:#64748b;font-size:0.8rem;margin-top:auto;">
    <div class="container">
        <p class="mb-1">&copy; <?= date('Y') ?> Mass Dragon Dojo &bull; Karate Organization Management System (KOMs).</p>
        <small style="color:#475569;">Traditional Okinawan Shorin-Ryu Karate Enterprise Portal</small>
    </div>
</footer>

<!-- Shared Mobile Bottom Navigation Bar (Smooth Smartphone Access) -->
<?php if (is_logged_in()): ?>
    <?php if (has_role('master')): ?>
        <nav class="koms-bottom-nav">
            <a href="<?= APP_URL ?>/master/index.php" class="koms-bottom-nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= APP_URL ?>/master/students.php" class="koms-bottom-nav-item">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a href="<?= APP_URL ?>/master/attendance.php" class="koms-bottom-nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="<?= APP_URL ?>/master/password_requests.php" class="koms-bottom-nav-item">
                <i class="fas fa-key"></i>
                <span>Requests</span>
            </a>
            <a href="<?= APP_URL ?>/master/profile.php" class="koms-bottom-nav-item">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        </nav>
    <?php elseif (has_role('student')): ?>
        <nav class="koms-bottom-nav">
            <a href="<?= APP_URL ?>/student/dashboard.php" class="koms-bottom-nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= APP_URL ?>/student/attendance.php" class="koms-bottom-nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="<?= APP_URL ?>/student/grading.php" class="koms-bottom-nav-item">
                <i class="fas fa-medal"></i>
                <span>Grading</span>
            </a>
            <a href="<?= APP_URL ?>/student/my_dojo.php" class="koms-bottom-nav-item">
                <i class="fas fa-torii-gate"></i>
                <span>My Dojo</span>
            </a>
            <a href="<?= APP_URL ?>/profile.php" class="koms-bottom-nav-item">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <nav class="koms-bottom-nav">
        <a href="<?= APP_URL ?>/index.php" class="koms-bottom-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= APP_URL ?>/find_dojo.php" class="koms-bottom-nav-item">
            <i class="fas fa-torii-gate"></i>
            <span>Find Dojo</span>
        </a>
        <a href="<?= APP_URL ?>/uploads/koms-mobile.apk" class="koms-bottom-nav-item" download>
            <i class="fab fa-android" style="color:#3ddc84;"></i>
            <span>Mobile App</span>
        </a>
        <a href="<?= APP_URL ?>/index.php" class="koms-bottom-nav-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Sign In</span>
        </a>
    </nav>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Unified KOMS Portal JS -->
<script src="<?= APP_URL ?>/assets/js/koms-portal.js?v=2"></script>
</body>
</html>
