<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = 'Home';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="koms-hero position-relative overflow-hidden rounded-4 shadow-lg mb-5">
    <div class="koms-hero-overlay"></div>
    <div class="row align-items-center g-0 position-relative">
        <div class="col-lg-7">
            <div class="p-4 p-md-5 p-xl-5 text-white">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 mb-4">
                    <i class="fas fa-shield-halved"></i>
                    <span class="small fw-semibold">Karate Organization Management System</span>
                </div>

                <h1 class="display-3 fw-bold lh-1 mb-4">
                    Train. Manage. <span class="text-warning">Excel.</span>
                </h1>

                <p class="lead text-white-50 mb-4" style="max-width: 680px;">
                    KOMS gives karate organizations one professional platform to manage dojos,
                    students, attendance, fees, grading, tournaments, announcements, and reports.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= APP_URL ?>/profile.php" class="btn btn-warning btn-lg px-4">
                            <i class="fas fa-gauge-high me-2"></i>Open My Dashboard
                        </a>
                        <a href="<?= APP_URL ?>/find_dojo.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-location-dot me-2"></i>Find a Dojo
                        </a>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/register.php" class="btn btn-warning btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-right-to-bracket me-2"></i>Sign In
                        </a>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap gap-4 mt-5 pt-2 text-white-50 small">
                    <span><i class="fas fa-check-circle text-warning me-2"></i>Role-based access</span>
                    <span><i class="fas fa-check-circle text-warning me-2"></i>Multi-dojo management</span>
                    <span><i class="fas fa-check-circle text-warning me-2"></i>Centralized records</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5 d-none d-lg-block">
            <div class="p-4 p-xl-5">
                <div class="hero-image-card rounded-4 overflow-hidden shadow-lg">
                    <img
                        src="https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=1000&q=85"
                        alt="Karate training"
                        class="img-fluid w-100"
                    >
                    <div class="hero-image-badge">
                        <div class="fw-bold">Built for modern karate organizations</div>
                        <div class="small text-white-50">Simple operations. Better visibility. Stronger management.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Platform Highlights -->
<section class="mb-5">
    <div class="text-center mb-4">
        <span class="section-kicker">ONE PLATFORM</span>
        <h2 class="fw-bold mt-2 mb-2">Everything your organization needs</h2>
        <p class="text-muted mb-0 mx-auto" style="max-width: 720px;">
            Replace scattered registers, spreadsheets, and manual follow-up with a single,
            structured management system.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-primary-subtle text-primary">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Dojo Management</h4>
                    <p class="text-muted mb-0">
                        Organize multiple dojos, masters, students, memberships, and operational information from one place.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-success-subtle text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Attendance & Fees</h4>
                    <p class="text-muted mb-0">
                        Track training attendance, fee structures, payment history, and outstanding records with clarity.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-warning-subtle text-warning">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Grading & Achievements</h4>
                    <p class="text-muted mb-0">
                        Maintain belt progression, grading history, achievements, and important student milestones.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-danger-subtle text-danger">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Tournaments</h4>
                    <p class="text-muted mb-0">
                        Manage tournament information, registrations, participation, and organization-wide event records.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-info-subtle text-info">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Announcements</h4>
                    <p class="text-muted mb-0">
                        Keep students and staff informed with timely notices, events, and organization updates.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card feature-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon bg-dark-subtle text-dark">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="fw-bold mt-4">Reports & Insights</h4>
                    <p class="text-muted mb-0">
                        Turn organization data into useful operational insights for smarter planning and decision-making.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust / Workflow Section -->
<section class="row g-4 align-items-stretch mb-5">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <span class="section-kicker">BUILT AROUND YOUR WORKFLOW</span>
                <h2 class="fw-bold mt-2">From daily training to organization-wide visibility</h2>
                <p class="text-muted mt-3">
                    KOMS is structured around the way karate organizations actually operate:
                    people belong to dojos, training creates attendance, payments create history,
                    grading creates progress, and events create achievements.
                </p>

                <div class="workflow-step d-flex gap-3 mt-4">
                    <div class="workflow-number">1</div>
                    <div>
                        <h6 class="fw-bold mb-1">Manage people and dojos</h6>
                        <p class="text-muted small mb-0">Keep organizational ownership, roles, and memberships structured.</p>
                    </div>
                </div>

                <div class="workflow-step d-flex gap-3 mt-3">
                    <div class="workflow-number">2</div>
                    <div>
                        <h6 class="fw-bold mb-1">Record daily operations</h6>
                        <p class="text-muted small mb-0">Track attendance, fees, payments, notices, and training activity.</p>
                    </div>
                </div>

                <div class="workflow-step d-flex gap-3 mt-3">
                    <div class="workflow-number">3</div>
                    <div>
                        <h6 class="fw-bold mb-1">Measure student progress</h6>
                        <p class="text-muted small mb-0">Maintain grading, achievements, tournaments, and historical records.</p>
                    </div>
                </div>

                <div class="workflow-step d-flex gap-3 mt-3">
                    <div class="workflow-number">4</div>
                    <div>
                        <h6 class="fw-bold mb-1">Make better decisions</h6>
                        <p class="text-muted small mb-0">Use dashboards and reports to understand organization performance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 stats-panel">
            <div class="card-body p-4 p-lg-5">
                <span class="section-kicker">KOMS PRINCIPLES</span>
                <h3 class="fw-bold mt-2">Designed for professional operations</h3>

                <div class="mini-stat mt-4">
                    <div class="mini-stat-icon"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <div class="fw-bold">Role-based security</div>
                        <div class="small text-muted">Different responsibilities, controlled access.</div>
                    </div>
                </div>

                <div class="mini-stat mt-3">
                    <div class="mini-stat-icon"><i class="fas fa-database"></i></div>
                    <div>
                        <div class="fw-bold">Centralized records</div>
                        <div class="small text-muted">Keep important organization history together.</div>
                    </div>
                </div>

                <div class="mini-stat mt-3">
                    <div class="mini-stat-icon"><i class="fas fa-mobile-screen-button"></i></div>
                    <div>
                        <div class="fw-bold">Responsive experience</div>
                        <div class="small text-muted">Usable on desktop, tablet, and mobile screens.</div>
                    </div>
                </div>

                <div class="mini-stat mt-3">
                    <div class="mini-stat-icon"><i class="fas fa-clock-rotate-left"></i></div>
                    <div>
                        <div class="fw-bold">Historical visibility</div>
                        <div class="small text-muted">Protect and review important records over time.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="cta-banner rounded-4 shadow-sm mb-5">
    <div class="p-4 p-md-5 text-center">
        <i class="fas fa-bowling-ball text-warning fs-2 mb-3"></i>
        <h2 class="fw-bold">Bring your karate organization into one system</h2>
        <p class="text-white-50 mx-auto mb-4" style="max-width: 700px;">
            Reduce manual work, improve visibility, and give every role a cleaner way to manage their responsibilities.
        </p>
        <?php if (!is_logged_in()): ?>
            <a href="<?= APP_URL ?>/register.php" class="btn btn-warning btn-lg px-4">
                <i class="fas fa-rocket me-2"></i>Start with KOMS
            </a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/profile.php" class="btn btn-warning btn-lg px-4">
                <i class="fas fa-arrow-right me-2"></i>Continue to KOMS
            </a>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
