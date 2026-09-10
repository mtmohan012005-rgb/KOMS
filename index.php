<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect authenticated users directly to their designated dashboard
if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') redirect('/admin/dashboard.php');
    if ($role === 'master') redirect('/master/dashboard.php');
    if ($role === 'senior') redirect('/senior/dashboard.php');
    if ($role === 'student') redirect('/student/dashboard.php');
    redirect('/admin/dashboard.php');
}

$error_message = '';
// Handle direct login authentication on index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = "Security token mismatch. Please try again.";
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = login_user($pdo, $email, $password);
        
        if ($result['success']) {
            $_SESSION['success_msg'] = "Welcome to Mass Dragon Dojo!";
            if ($result['role'] === 'super_admin') redirect('/admin/dashboard.php');
            if ($result['role'] === 'master') redirect('/master/dashboard.php');
            if ($result['role'] === 'senior') redirect('/senior/dashboard.php');
            if ($result['role'] === 'student') redirect('/student/dashboard.php');
            redirect('/index.php');
        } else {
            $error_message = $result['message'];
        }
    }
} elseif (isset($_SESSION['error_msg'])) {
    $error_message = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Fetch live statistics and featured dojos for homepage showcase
$stats = [
    'dojos' => 0,
    'students' => 0,
    'masters' => 0,
    'black_belts' => 0
];
try {
    $stats['dojos'] = (int)$pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'active'")->fetchColumn();
    $stats['students'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['masters'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'master'")->fetchColumn();
    $stats['black_belts'] = (int)$pdo->query("SELECT COUNT(*) FROM grading_history WHERE new_belt LIKE '%Black%'")->fetchColumn();
    
    $dojos_stmt = $pdo->query("SELECT d.id, d.name, d.location, d.training_days, d.training_timings, u.first_name, u.last_name FROM dojos d LEFT JOIN users u ON d.master_id = u.id WHERE d.status = 'active' ORDER BY d.id ASC LIMIT 3");
    $featured_dojos = $dojos_stmt->fetchAll();
} catch (Exception $e) {
    $featured_dojos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Dragon Dojo - Karate Organization Management System (KOMs)</title>
    <meta name="description" content="Mass Dragon Dojo & Karate Organization Management System (KOMs) - Traditional Okinawan Shorin-Ryu Karate martial arts enterprise platform.">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&family=Cinzel:wght@600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Unified KOMS Theme Stylesheet -->
    <link rel="stylesheet" href="assets/css/koms-portal-theme.css?v=2">

    <style>
        /* Additional styling specific to public landing showcase */
        .showcase-section {
            margin-bottom: 48px;
        }
        .section-header {
            margin-bottom: 24px;
        }
        .section-tag {
            font-size: 0.75rem;
            color: var(--koms-gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .section-title {
            font-family: var(--koms-font-heading);
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            color: #fff;
            margin-top: 4px;
        }
        .belt-card {
            background: rgba(18, 20, 26, 0.94);
            border: 1px solid var(--koms-border-subtle);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.25s ease;
        }
        .belt-card:hover {
            border-color: var(--koms-border);
            transform: translateY(-3px);
        }
        .belt-bar {
            width: 8px;
            height: 48px;
            border-radius: 999px;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="koms-body">

<!-- Ambient Background Artwork -->
<div class="koms-ambient-bg"></div>

<!-- Mobile Overlay Backdrop -->
<div class="koms-overlay" id="komsOverlay"></div>

<!-- Top Navigation Bar -->
<header class="koms-topbar">
    <div class="koms-topbar-left">
        <button class="koms-menu-toggle" id="komsMenuToggle" onclick="toggleSidebar()" aria-label="Toggle navigation drawer">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php" class="koms-brand">
            <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Crest" class="koms-brand-crest">
            <div class="koms-brand-text">
                <strong>MASS DRAGON DOJO</strong>
                <span>Karate Organization Management System (KOMs)</span>
            </div>
        </a>
    </div>

    <!-- Center Pill Search Bar -->
    <div class="koms-topbar-search">
        <i class="fas fa-search koms-search-icon"></i>
        <input type="text" class="koms-search-input" placeholder="Search dojos, belts, syllabus, events... (Press '/' to focus)" aria-label="Search">
    </div>

    <!-- Right Controls -->
    <div class="koms-topbar-right">
        <a href="uploads/koms-mobile.apk" class="koms-icon-btn" title="Download Android Mobile App" download>
            <i class="fab fa-android" style="color:#3ddc84;"></i>
        </a>
        <button class="koms-icon-btn" onclick="openLoginDrawer()" title="Notifications & Announcements" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="koms-badge-dot"></span>
        </button>
        <button class="koms-login-btn" onclick="openLoginDrawer()" data-open-login>
            <i class="fas fa-user-ninja"></i>
            <span>Login</span>
            <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</header>

<!-- Left Sidebar Navigation (Matching Mockup) -->
<aside class="koms-sidebar" id="komsSidebar">
    <div>
        <div class="koms-sidebar-header">
            <button class="koms-sidebar-close" id="komsSidebarClose" onclick="closeSidebar()" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
            <a href="index.php" class="koms-brand">
                <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu" class="koms-brand-crest">
                <div class="koms-brand-text">
                    <strong>MASS DRAGON DOJO</strong>
                    <span>KOMs Portal</span>
                </div>
            </a>
        </div>

        <div class="koms-sidebar-menu-title">NAVIGATION</div>
        <nav class="koms-nav">
            <a href="#home" class="koms-nav-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="#about" class="koms-nav-item">
                <i class="fas fa-user-shield"></i>
                <span>About Us</span>
            </a>
            <a href="#dojos" class="koms-nav-item">
                <i class="fas fa-torii-gate"></i>
                <span>Dojo's</span>
            </a>
            <a href="#events" class="koms-nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </a>
            <a href="#gallery" class="koms-nav-item">
                <i class="fas fa-images"></i>
                <span>Gallery</span>
            </a>
            <a href="#belts" class="koms-nav-item">
                <i class="fas fa-medal"></i>
                <span>Rank &amp; Belt</span>
            </a>
            <a href="#news" class="koms-nav-item">
                <i class="fas fa-newspaper"></i>
                <span>News &amp; Updates</span>
            </a>
            <a href="#contact" class="koms-nav-item">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
            <a href="#" class="koms-nav-item" data-open-login>
                <i class="fas fa-sign-in-alt" style="color:var(--koms-gold);"></i>
                <span style="color:var(--koms-gold);font-weight:700;">Student / Master Login</span>
            </a>
        </nav>
    </div>

    <!-- Calligraphic Brush Slogan (Matching Mockup Image) -->
    <div class="koms-sidebar-slogan">
        <div class="koms-slogan-text">
            Discipline.<br>
            Strength.<br>
            Internal Peace.
        </div>
        <div class="koms-slogan-stroke"></div>
    </div>
</aside>

<!-- Main Stage Content -->
<main class="koms-main" id="home">
    <div class="koms-container">

        <?php if (!empty($error_message)): ?>
            <div style="background:rgba(198,26,26,0.25);border:1px solid #c61a1a;border-radius:12px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:center;gap:12px;color:#ffcccc;">
                <i class="fas fa-exclamation-triangle" style="color:#ff6b6b;font-size:20px;"></i>
                <div style="flex:1;">
                    <strong>Authentication Error:</strong> <?= htmlspecialchars($error_message) ?>
                </div>
                <button onclick="openLoginDrawer()" class="koms-btn koms-btn-gold" style="padding:6px 14px;font-size:0.78rem;">Try Again</button>
            </div>
        <?php endif; ?>

        <!-- HERO SECTION (MATCHING REFERENCE MOCKUP IMAGE) -->
        <section class="koms-hero">
            <!-- Left Hero Showcase -->
            <div class="koms-hero-left">
                <div class="koms-crest-circle-wrap">
                    <div class="koms-crest-circle-glow"></div>
                    <img src="assets/images/shorin_ryu_crest.jpg" alt="Mass Dragon Shorin-Ryu" class="koms-crest-circle-img">
                </div>

                <h1 class="koms-hero-title">MASS DRAGON DOJO</h1>
                <p class="koms-hero-subtitle">Karate Organization Management System (KOMs)</p>

                <!-- 4 Circular Pillar / Stat Badges -->
                <div class="koms-pillar-grid">
                    <div class="koms-pillar-item" onclick="openLoginDrawer()">
                        <div class="koms-pillar-circle">
                            <i class="fas fa-fist-raised"></i>
                        </div>
                        <span class="koms-pillar-title">TRAIN</span>
                        <span class="koms-pillar-sub">Build Skills</span>
                    </div>

                    <div class="koms-pillar-item" onclick="openLoginDrawer()">
                        <div class="koms-pillar-circle">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="koms-pillar-title">GROW</span>
                        <span class="koms-pillar-sub">Build Community</span>
                    </div>

                    <div class="koms-pillar-item" onclick="openLoginDrawer()">
                        <div class="koms-pillar-circle">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="koms-pillar-title">ACHIEVE</span>
                        <span class="koms-pillar-sub">Build Confidence</span>
                    </div>

                    <div class="koms-pillar-item" onclick="openLoginDrawer()">
                        <div class="koms-pillar-circle">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <span class="koms-pillar-title">EXCEL</span>
                        <span class="koms-pillar-sub">Build Champions</span>
                    </div>
                </div>
            </div>

            <!-- Right Hero Showcase (Authentic Typography & Silhouette Canvas) -->
            <div class="koms-hero-right">
                <div class="koms-hero-kicker">— TRADITION • DISCIPLINE • EXCELLENCE —</div>
                <div class="koms-hero-headline">BUILDING STRONGER</div>
                <div class="koms-hero-script">MINDS &amp; BODIES</div>
                <div class="koms-hero-subline">THROUGH KARATE</div>

                <p style="color:#b3b9c5;font-size:0.92rem;line-height:1.7;margin-bottom:24px;max-width:480px;">
                    Welcome to the official Shorin-Ryu Karate portal. Enter to track attendance sessions, syllabus katas, belt gradings, tournament brackets, and verified certifications.
                </p>

                <div class="koms-hero-actions">
                    <button class="koms-btn koms-btn-gold" data-open-login>
                        <i class="fas fa-dragon"></i>
                        <span>Enter Dojo Portal</span>
                    </button>
                    <a href="#dojos" class="koms-btn koms-btn-outline">
                        <i class="fas fa-torii-gate"></i>
                        <span>Explore Dojos</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Live Statistics Counter Grid -->
        <div class="koms-grid-4">
            <div class="koms-stat-card">
                <div class="koms-stat-icon"><i class="fas fa-torii-gate"></i></div>
                <div class="koms-stat-info">
                    <div class="koms-stat-label">Affiliated Dojos</div>
                    <div class="koms-stat-value"><?= max($stats['dojos'], 3) ?></div>
                    <div class="koms-stat-sub">Active Okinawan Academies</div>
                </div>
            </div>

            <div class="koms-stat-card">
                <div class="koms-stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="koms-stat-info">
                    <div class="koms-stat-label">Active Students</div>
                    <div class="koms-stat-value"><?= max($stats['students'], 18) ?></div>
                    <div class="koms-stat-sub">Enrolled Practitioners</div>
                </div>
            </div>

            <div class="koms-stat-card">
                <div class="koms-stat-icon"><i class="fas fa-user-ninja"></i></div>
                <div class="koms-stat-info">
                    <div class="koms-stat-label">Certified Senseis</div>
                    <div class="koms-stat-value"><?= max($stats['masters'], 5) ?></div>
                    <div class="koms-stat-sub">Dojo Masters &amp; Seniors</div>
                </div>
            </div>

            <div class="koms-stat-card">
                <div class="koms-stat-icon"><i class="fas fa-award"></i></div>
                <div class="koms-stat-info">
                    <div class="koms-stat-label">Black Belts Awarded</div>
                    <div class="koms-stat-value"><?= max($stats['black_belts'], 12) ?></div>
                    <div class="koms-stat-sub">Sealed Dan Certificates</div>
                </div>
            </div>
        </div>

        <!-- Featured Dojos Showcase -->
        <section class="showcase-section" id="dojos">
            <div class="section-header">
                <div class="section-tag">TRADITIONAL TRAINING LOCATIONS</div>
                <h2 class="section-title">Official Shorin-Ryu Dojos</h2>
            </div>

            <div class="koms-grid-3">
                <?php if (!empty($featured_dojos)): ?>
                    <?php foreach ($featured_dojos as $d): ?>
                        <div class="koms-card">
                            <div class="koms-card-header">
                                <span class="koms-card-title">
                                    <i class="fas fa-torii-gate"></i>
                                    <?= htmlspecialchars($d['name']) ?>
                                </span>
                                <span style="font-size:0.7rem;color:var(--koms-gold);background:rgba(255,204,0,0.12);padding:2px 8px;border-radius:999px;">Active</span>
                            </div>
                            <p style="color:#cbd5e1;font-size:0.85rem;margin-bottom:12px;">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?= htmlspecialchars($d['location']) ?>
                            </p>
                            <div style="font-size:0.78rem;color:var(--koms-text-muted);border-top:1px solid rgba(255,255,255,0.06);padding-top:10px;display:flex;justify-content:space-between;">
                                <span><i class="fas fa-user-ninja me-1"></i> Sensei <?= htmlspecialchars(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))) ?></span>
                                <span><i class="fas fa-calendar-check me-1"></i> <?= htmlspecialchars($d['training_days'] ?: 'Mon-Fri') ?></span>
                            </div>
                            <div style="margin-top:16px;">
                                <button onclick="openLoginDrawer()" class="koms-btn koms-btn-outline" style="width:100%;justify-content:center;padding:7px;">
                                    Join Dojo Portal
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <span class="koms-card-title"><i class="fas fa-torii-gate"></i> Main Dragon Honbu Dojo</span>
                        </div>
                        <p style="color:#cbd5e1;font-size:0.85rem;">Central Okinawa Karate Center, Main Hall</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Rank & Belt Progression Syllabus -->
        <section class="showcase-section" id="belts">
            <div class="section-header">
                <div class="section-tag">SHORIN-RYU PROGRESSION</div>
                <h2 class="section-title">Kyu &amp; Dan Belt Grading Matrix</h2>
            </div>

            <div class="koms-grid-4">
                <div class="belt-card">
                    <div class="belt-bar" style="background:#ffffff;"></div>
                    <div>
                        <strong style="color:#ffffff;font-size:0.9rem;">White Belt (10th Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Fukyugata Ichi • Stance Basics</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#ffd700;"></div>
                    <div>
                        <strong style="color:#ffd700;font-size:0.9rem;">Yellow Belt (8th Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Fukyugata Ni • Defense Blocks</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#ff8c00;"></div>
                    <div>
                        <strong style="color:#ff8c00;font-size:0.9rem;">Orange Belt (7th Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Pinan Shodan • Striking Focus</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#22c55e;"></div>
                    <div>
                        <strong style="color:#22c55e;font-size:0.9rem;">Green Belt (6th Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Pinan Nidan • Sparring Basics</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#3b82f6;"></div>
                    <div>
                        <strong style="color:#3b82f6;font-size:0.9rem;">Blue Belt (4th Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Pinan Sandan • Combinations</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#a855f7;"></div>
                    <div>
                        <strong style="color:#a855f7;font-size:0.9rem;">Purple Belt (3rd Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Pinan Yondan • Naihanchi Ichi</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:#8b4513;"></div>
                    <div>
                        <strong style="color:#b45309;font-size:0.9rem;">Brown Belt (1st Kyu)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Pinan Godan • Passai Dai</div>
                    </div>
                </div>

                <div class="belt-card">
                    <div class="belt-bar" style="background:linear-gradient(180deg,#c61a1a,#000000);"></div>
                    <div>
                        <strong style="color:var(--koms-gold);font-size:0.9rem;">Black Belt (1st Dan)</strong>
                        <div style="font-size:0.72rem;color:var(--koms-text-muted);">Kusanku • Seisan • Master Dan</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us & Contact Section -->
        <section class="showcase-section" id="about">
            <div class="koms-grid-2">
                <div class="koms-card">
                    <div class="koms-card-header">
                        <span class="koms-card-title"><i class="fas fa-scroll"></i> About Mass Dragon Dojo</span>
                    </div>
                    <p style="color:#cbd5e1;font-size:0.9rem;line-height:1.8;">
                        Mass Dragon Dojo preserves the authentic Okinawan martial art heritage of Shorin-Ryu Karate. Guided by tradition, discipline, and excellence, the academy nurtures martial artists of all ages, developing physical prowess, mental fortitude, and respectful conduct.
                    </p>
                    <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
                        <span style="background:rgba(255,204,0,0.1);color:var(--koms-gold);padding:4px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;">Okinawan Lineage</span>
                        <span style="background:rgba(255,204,0,0.1);color:var(--koms-gold);padding:4px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;">Traditional Kata</span>
                        <span style="background:rgba(255,204,0,0.1);color:var(--koms-gold);padding:4px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;">WKF Compliant</span>
                    </div>
                </div>

                <div class="koms-card" id="contact">
                    <div class="koms-card-header">
                        <span class="koms-card-title"><i class="fas fa-envelope-open-text"></i> Contact &amp; Support</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:12px;font-size:0.88rem;color:#cbd5e1;">
                        <div><i class="fas fa-envelope text-warning me-2"></i> info@massdragondojo.com</div>
                        <div><i class="fas fa-phone-alt text-success me-2"></i> +91 98765 43210</div>
                        <div><i class="fas fa-clock text-info me-2"></i> Dojo Hours: 06:00 AM - 08:30 PM (Mon - Sat)</div>
                        <div><i class="fas fa-map-pin text-danger me-2"></i> Honbu Dojo, Shorin-Ryu Karate Center</div>
                    </div>
                    <div style="margin-top:18px;">
                        <a href="forgot_password.php" class="koms-btn koms-btn-outline" style="width:100%;justify-content:center;">
                            <i class="fas fa-key"></i> Request Password Reset
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer style="text-align:center;padding:32px 0 16px;color:var(--koms-text-dim);font-size:0.8rem;border-top:1px solid rgba(255,255,255,0.06);">
            <p>&copy; <?= date('Y') ?> Mass Dragon Dojo &bull; Karate Organization Management System (KOMs). All Rights Reserved.</p>
        </footer>

    </div>
</main>

<!-- SLIDE-IN LOGIN DRAWER (DUAL USER ID & GMAIL, 1-TIME DOB PASSWORD SUPPORT) -->
<div class="koms-login-drawer" id="komsLoginDrawer">
    <button class="koms-drawer-close" id="komsLoginDrawerClose" aria-label="Close login drawer">
        <i class="fas fa-times"></i>
    </button>

    <div style="text-align:center;margin-bottom:24px;">
        <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Crest" style="width:70px;height:70px;border-radius:50%;border:2px solid var(--koms-gold);box-shadow:0 0 20px rgba(255,204,0,0.5);margin-bottom:12px;">
        <h3 style="font-family:var(--koms-font-brush);color:var(--koms-gold);font-size:1.8rem;margin:0;">MASS DRAGON DOJO</h3>
        <p style="font-size:0.75rem;color:var(--koms-text-muted);letter-spacing:0.8px;margin-top:4px;">ENTER DOJO PORTAL</p>
    </div>

    <!-- Dual Credential Notice -->
    <div style="background:rgba(255,204,0,0.08);border:1px solid var(--koms-border);border-radius:10px;padding:10px 14px;margin-bottom:20px;font-size:0.76rem;color:#f3e8c7;line-height:1.5;">
        <i class="fas fa-info-circle text-warning me-1"></i>
        <strong>Sign-in Credential:</strong> Use your <strong>User ID</strong> (e.g. <code>sairohan2012.koms</code>) or <strong>Gmail address</strong>. Initial password is your <strong>Date of Birth</strong> (<code>DD.MM.YYYY</code>).
    </div>

    <!-- Login Form -->
    <form action="index.php" method="POST">
        <?= csrf_input() ?>

        <div class="koms-form-group">
            <label class="koms-label" for="loginIdentity">User ID or Gmail Address</label>
            <div class="koms-input-wrap">
                <i class="fas fa-user-circle"></i>
                <input type="text" id="loginIdentity" name="email" class="koms-input" placeholder="User ID or Gmail Address" required autocomplete="username">
            </div>
            <div class="koms-input-hint">e.g. sairohan2012.koms or admin@gmail.com</div>
        </div>

        <div class="koms-form-group">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <label class="koms-label" for="loginPassword" style="margin:0;">Password</label>
                <a href="forgot_password.php" style="color:var(--koms-gold);font-size:0.75rem;text-decoration:none;">Forgot?</a>
            </div>
            <div class="koms-input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="loginPassword" name="password" class="koms-input" placeholder="Password (DOB: DD.MM.YYYY for students)" required autocomplete="current-password">
            </div>
            <div class="koms-input-hint">Default for students: Date of Birth (e.g. 20.10.2012)</div>
        </div>

        <button type="submit" class="koms-btn koms-btn-gold" style="width:100%;justify-content:center;padding:12px;margin-top:8px;">
            <i class="fas fa-dragon"></i>
            <span>Enter Dojo</span>
        </button>
    </form>

    <!-- 1-Click Quick Demo Pill Buttons -->
    <div style="margin-top:28px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
        <div style="font-size:0.72rem;letter-spacing:1px;color:var(--koms-gold);font-weight:700;margin-bottom:12px;text-transform:uppercase;">
            <i class="fas fa-key me-1"></i> Quick 1-Click Demo Accounts
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <button type="button" onclick="quickLogin('admin@koms.com','password123')" class="koms-btn koms-btn-outline" style="padding:7px 10px;font-size:0.72rem;text-align:left;justify-content:flex-start;">
                <i class="fas fa-crown text-warning me-1"></i> Grand Master
            </button>
            <button type="button" onclick="quickLogin('master@koms.com','password123')" class="koms-btn koms-btn-outline" style="padding:7px 10px;font-size:0.72rem;text-align:left;justify-content:flex-start;">
                <i class="fas fa-torii-gate text-danger me-1"></i> Dojo Master
            </button>
            <button type="button" onclick="quickLogin('sairohan2012.koms','20.10.2012')" class="koms-btn koms-btn-outline" style="padding:7px 10px;font-size:0.72rem;text-align:left;justify-content:flex-start;">
                <i class="fas fa-user-graduate text-info me-1"></i> Sai Rohan (DOB)
            </button>
            <button type="button" onclick="quickLogin('dguhan2015.koms','25.09.2015')" class="koms-btn koms-btn-outline" style="padding:7px 10px;font-size:0.72rem;text-align:left;justify-content:flex-start;">
                <i class="fas fa-user-graduate text-success me-1"></i> D. Guhan (DOB)
            </button>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM NAVIGATION BAR (FOR EFFORTLESS ONE-HANDED SMARTPHONE USE) -->
<nav class="koms-bottom-nav">
    <a href="index.php" class="koms-bottom-nav-item active">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="#dojos" class="koms-bottom-nav-item">
        <i class="fas fa-torii-gate"></i>
        <span>Dojos</span>
    </a>
    <a href="#belts" class="koms-bottom-nav-item">
        <i class="fas fa-medal"></i>
        <span>Belts</span>
    </a>
    <a href="#events" class="koms-bottom-nav-item">
        <i class="fas fa-calendar-alt"></i>
        <span>Events</span>
    </a>
    <a href="#" class="koms-bottom-nav-item" data-open-login>
        <i class="fas fa-user-ninja"></i>
        <span>Login</span>
    </a>
</nav>

<!-- Unified Script -->
<script src="assets/js/koms-portal.js?v=2"></script>
</body>
</html>
