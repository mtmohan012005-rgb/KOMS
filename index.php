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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Dragon Dojo - Karate Organization Management System</title>
    <meta name="description" content="Mass Dragon Dojo & Karate Organization Management System (KOMS) - Enterprise multi-tenant martial arts platform.">

    <!-- Google Fonts: Caveat Brush for calligraphy & Inter for clean UI -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 for Input & Martial Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #080808;
            --form-bg: rgba(12, 12, 12, 0.94);
            --input-bg: #161616;
            --text-main: #ffffff;
            --text-muted: #888888;
            --accent-red: #c61a1a;
            --accent-red-bright: #e02323;
            --accent-yellow: #ffcc00;
            --accent-yellow-gold: #f4bd17;
        }

        * {
            box-sizing: border-box;
        }

        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Dragon & Red Slashes Background Artwork */
        .background {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: 
                linear-gradient(to bottom, rgba(8, 8, 8, 0.45), rgba(8, 8, 8, 0.88)),
                url('assets/images/dragon_bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 1;
            pointer-events: none;
            filter: contrast(1.15);
        }

        /* Ambient Ember / Particle Canvas */
        #particleCanvas {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: 2;
            pointer-events: none;
        }

        /* Red Corner Slashes Simulation Overlay */
        .slash-overlay {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: 
                linear-gradient(135deg, rgba(200, 0, 0, 0.22) 0%, transparent 28%),
                linear-gradient(-45deg, rgba(200, 0, 0, 0.22) 0%, transparent 28%);
            z-index: 2;
            pointer-events: none;
        }

        .main-container {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 440px;
            padding: 24px 20px;
            margin: auto;
        }

        /* --- 1. CHOREOGRAPHED LOGO CONVERGENCE (6 STEPS) --- */
        .logo-wrapper {
            position: relative;
            width: 165px;
            height: 165px;
            transform: translateY(140px);
            animation: moveLogoUp 1s cubic-bezier(0.16, 1, 0.3, 1) 2.2s forwards;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-halves {
            position: absolute;
            width: 100%;
            height: 100%;
            animation: hideHalves 0.1s linear 1.25s forwards;
        }

        .logo-image {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        /* Left and Right halves using clip-path on the authentic Shorin Ryu crest */
        .logo-left {
            clip-path: inset(0 50% 0 0);
            animation: slideLeftIn 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            filter: drop-shadow(-4px 0 12px rgba(255, 204, 0, 0.5));
        }

        .logo-right {
            clip-path: inset(0 0 0 50%);
            animation: slideRightIn 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            filter: drop-shadow(4px 0 12px rgba(255, 204, 0, 0.5));
        }

        .logo-full {
            opacity: 0;
            animation: showFullLogo 0.6s ease-out 1.1s forwards;
        }

        /* Central Vertical Laser Flare (Step 1 & 2) */
        .flare {
            position: absolute;
            top: 50%; left: 50%;
            width: 3px; height: 180%;
            background: #ffffff;
            box-shadow: 
                0 0 15px 6px rgba(255, 255, 255, 0.95),
                0 0 35px 15px rgba(255, 204, 0, 0.85),
                0 0 60px 25px rgba(224, 35, 35, 0.65);
            transform: translate(-50%, -50%) scaleY(0);
            opacity: 0;
            z-index: 15;
            animation: flashLight 0.7s cubic-bezier(0.1, 0.9, 0.2, 1) 0.95s forwards;
        }

        /* Horizontal Light Streaks (Step 2) */
        .flare-horizontal {
            position: absolute;
            top: 50%; left: 50%;
            width: 260%; height: 2px;
            background: #ffffff;
            box-shadow: 0 0 20px 8px rgba(255, 204, 0, 0.85);
            transform: translate(-50%, -50%) scaleX(0);
            opacity: 0;
            z-index: 14;
            animation: flashHorizontal 0.6s ease-out 1.05s forwards;
        }

        /* Radial burst behind logo on convergence */
        .burst-ring {
            position: absolute;
            width: 165px;
            height: 165px;
            border-radius: 50%;
            border: 2px solid rgba(255, 204, 0, 0.9);
            box-shadow: 0 0 32px rgba(255, 204, 0, 0.75);
            opacity: 0;
            transform: scale(0.6);
            animation: burstExpand 0.7s cubic-bezier(0.1, 0.8, 0.2, 1) 1.1s forwards;
            pointer-events: none;
        }

        /* --- 2. TEXT SECTION --- */
        .title-section {
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) 2.5s forwards;
            margin-bottom: 22px;
        }

        .title-section h1 {
            font-family: 'Caveat Brush', cursive;
            color: var(--accent-yellow);
            font-size: 34px;
            margin: 0 0 4px 0;
            letter-spacing: 1.5px;
            text-shadow: 0 0 16px rgba(255, 204, 0, 0.4);
        }

        .title-section p {
            color: var(--text-main);
            font-size: 12px;
            font-weight: 500;
            margin: 0;
            line-height: 1.45;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .title-section .subtitle-tag {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            background: rgba(198, 26, 26, 0.25);
            border: 1px solid rgba(224, 35, 35, 0.45);
            border-radius: 20px;
            font-size: 10px;
            color: #ffcc00;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* --- 3. LOGIN FORM SECTION --- */
        .login-form {
            width: 100%;
            background: var(--form-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 26px 24px 22px 24px;
            border-radius: 18px;
            border: 1px solid rgba(224, 35, 35, 0.35);
            box-shadow: 
                0 15px 40px rgba(0, 0, 0, 0.85),
                0 0 25px rgba(224, 35, 35, 0.15);
            box-sizing: border-box;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) 2.9s forwards;
        }

        /* Error Notification Alert */
        .error-alert {
            background: rgba(198, 26, 26, 0.22);
            border: 1px solid rgba(224, 35, 35, 0.65);
            color: #ff9b9b;
            font-size: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.4s ease-in-out;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        .input-group i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            transition: color 0.25s;
        }

        .input-group i.icon-left { left: 16px; }
        .input-group i.icon-right { 
            right: 16px; 
            cursor: pointer; 
            padding: 6px;
            z-index: 5;
        }
        .input-group i.icon-right:hover {
            color: var(--accent-yellow);
        }

        .input-group input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid #242424;
            padding: 14px 44px;
            border-radius: 10px;
            color: var(--text-main);
            font-size: 13.5px;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .input-group input:focus {
            border-color: rgba(224, 35, 35, 0.85);
            background: #1c1c1c;
            box-shadow: 0 0 14px rgba(224, 35, 35, 0.28);
        }

        .input-group input::placeholder {
            color: #666666;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #9b0b0b 0%, #e02323 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 14.5px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            margin-top: 6px;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(198, 26, 26, 0.4);
        }

        .login-btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(224, 35, 35, 0.55);
        }

        .login-btn:active {
            transform: translateY(1px);
        }

        /* 1-Click Demo Role Switcher */
        .demo-roles-container {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #1c1c1c;
        }

        .demo-roles-label {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent-yellow-gold);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .demo-pills {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .demo-pill {
            background: #141414;
            border: 1px solid #282828;
            color: #bbb;
            font-size: 11px;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .demo-pill:hover {
            border-color: var(--accent-yellow);
            color: #fff;
            background: #1f1a0e;
            transform: translateY(-1px);
        }

        .demo-pill i {
            color: var(--accent-yellow);
            font-size: 10px;
        }

        /* Bottom Quick Links */
        .footer-links {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .footer-links a {
            color: #aaa;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--accent-yellow);
        }

        /* --- ANIMATION KEYFRAMES --- */
        @keyframes slideLeftIn {
            0% { transform: translateX(-140px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideRightIn {
            0% { transform: translateX(140px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes flashLight {
            0% { transform: translate(-50%, -50%) scaleY(0); opacity: 0; }
            45% { transform: translate(-50%, -50%) scaleY(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scaleY(1.3); opacity: 0; }
        }

        @keyframes flashHorizontal {
            0% { transform: translate(-50%, -50%) scaleX(0); opacity: 0; }
            50% { transform: translate(-50%, -50%) scaleX(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scaleX(1.4); opacity: 0; }
        }

        @keyframes burstExpand {
            0% { transform: scale(0.6); opacity: 0.9; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        @keyframes hideHalves {
            to { opacity: 0; visibility: hidden; }
        }

        @keyframes showFullLogo {
            0% { opacity: 0; transform: scale(0.92); }
            50% { opacity: 1; transform: scale(1.05); filter: drop-shadow(0 0 25px rgba(255, 204, 0, 0.9)); }
            100% { opacity: 1; transform: scale(1); filter: drop-shadow(0 0 15px rgba(255, 204, 0, 0.5)); }
        }

        @keyframes moveLogoUp {
            0% { transform: translateY(140px); }
            100% { transform: translateY(0); }
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* Mobile Adjustments */
        @media (max-width: 480px) {
            .logo-wrapper { width: 140px; height: 140px; transform: translateY(110px); }
            .title-section h1 { font-size: 28px; }
            .login-form { padding: 22px 18px 18px 18px; }
            .demo-pills { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Dragon Cinematic Art Background -->
    <div class="background"></div>

    <!-- Martial Arts Red Corner Overlays -->
    <div class="slash-overlay"></div>

    <!-- Dynamic Fire Ember Particle Canvas -->
    <canvas id="particleCanvas"></canvas>

    <div class="main-container">
        
        <!-- --- 1. CHOREOGRAPHED SHORIN RYU LOGO CONVERGENCE --- -->
        <div class="logo-wrapper">
            <!-- Left & Right Halves of Crest for Split Entrance -->
            <div class="logo-halves">
                <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Left" class="logo-image logo-left">
                <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Right" class="logo-image logo-right">
            </div>
            
            <!-- Laser Flares on Contact Point -->
            <div class="flare"></div>
            <div class="flare-horizontal"></div>
            <div class="burst-ring"></div>

            <!-- Complete Joined Crest -->
            <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Mass Dragon Dojo Crest" class="logo-image logo-full" id="finalLogo">
        </div>

        <!-- --- 2. TITLE & BRANDING --- -->
        <div class="title-section">
            <h1>Mass Dragon Dojo</h1>
            <p>Karate Organization Management System</p>
            <div class="subtitle-tag"><i class="fas fa-shield-halved me-1"></i> Official Portal • Shorin-Ryu</div>
        </div>

        <!-- --- 3. INTERACTIVE LOGIN FORM --- -->
        <form class="login-form" method="POST" action="index.php" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <?php if (!empty($error_message)): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <i class="fa-solid fa-id-card-clip icon-left" style="color:#ffd21a;"></i>
                <input type="text" name="email" id="email" placeholder="User ID or Gmail Address" required autocomplete="username" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock icon-left"></i>
                <input type="password" name="password" id="password" placeholder="Password (DOB: DD.MM.YYYY for students)" required autocomplete="current-password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
                <i class="fa-regular fa-eye icon-right" id="togglePassword" title="Toggle password visibility"></i>
            </div>

            <div style="font-size: 11px; color: #a0a0a0; margin: -6px 0 14px 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-shield-halved" style="color: #ffd21a; font-size: 11px;"></i>
                <span>Accepts <strong>User ID</strong> (e.g. <code>sairohan2012.koms</code>) or <strong>Gmail</strong></span>
            </div>

            <button type="submit" class="login-btn" id="submitBtn">
                <i class="fas fa-dragon"></i>
                <span>Enter Dojo</span>
            </button>

            <!-- 1-Click Role Switcher for Fast Evaluation -->
            <div class="demo-roles-container">
                <div class="demo-roles-label">
                    <span><i class="fas fa-key me-1"></i> Quick Demo Roles</span>
                    <span style="font-size: 9px; opacity: 0.7;">Gmail or Student User ID</span>
                </div>
                <div class="demo-pills">
                    <button type="button" class="demo-pill" onclick="fillRole('admin@gmail.com', 'password123', 'Grand Master')">
                        <i class="fas fa-crown"></i> <strong>Grand Master</strong>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillRole('master@gmail.com', 'password123', 'Dojo Master')">
                        <i class="fas fa-torii-gate"></i> <strong>Dojo Master</strong>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillRole('sairohan2012.koms', '20.10.2012', 'Sai Rohan (Student ID)')" title="User ID: sairohan2012.koms | Pass: 20.10.2012">
                        <i class="fas fa-user-ninja"></i> <strong>Sai Rohan (ID)</strong>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillRole('dguhan2015.koms', '25.09.2015', 'Guhan (Student ID)')" title="User ID: dguhan2015.koms | Pass: 25.09.2015">
                        <i class="fas fa-user-graduate"></i> <strong>Guhan (ID)</strong>
                    </button>
                </div>
            </div>

            <!-- Portal Links -->
            <div class="footer-links">
                <a href="forgot_password.php" title="Request Password Reset from Master Portal" style="color:#ffd21a;"><i class="fas fa-key me-1"></i> Reset Password</a>
                <a href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                <a href="find_dojo.php"><i class="fas fa-compass me-1"></i> Find Dojo</a>
                <a href="uploads/koms-mobile.apk" download title="Download KOMS Android App (APK)"><i class="fab fa-android me-1" style="color: #3ddc84;"></i> Mobile App</a>
                <a href="index.html" title="Static presentation experience"><i class="fas fa-scroll me-1"></i> Tour</a>
            </div>
        </form>

    </div>

    <!-- Dynamic Ember Particle System & Interactivity Script -->
    <script>
        // Password Reveal Toggle
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // 1-Click Role Autofill
        function fillRole(email, password, roleName) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            const submitBtn = document.getElementById('submitBtn');
            
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = password;
                
                // Visual feedback highlight
                emailInput.style.borderColor = '#ffcc00';
                passInput.style.borderColor = '#ffcc00';
                submitBtn.innerHTML = `<i class="fas fa-bolt"></i> Entering as ${roleName}...`;
                
                setTimeout(() => {
                    document.getElementById('loginForm').submit();
                }, 350);
            }
        }

        // Background Fire Ember Particle System
        const canvas = document.getElementById('particleCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let particles = [];

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resize);
            resize();

            class Particle {
                constructor() {
                    this.reset();
                }
                reset() {
                    this.x = Math.random() * canvas.width;
                    this.y = canvas.height + Math.random() * 50;
                    this.size = Math.random() * 2.5 + 0.8;
                    this.speedY = Math.random() * 1.6 + 0.4;
                    this.speedX = (Math.random() - 0.5) * 0.8;
                    this.opacity = Math.random() * 0.6 + 0.3;
                    this.fadeSpeed = Math.random() * 0.006 + 0.002;
                    // Embers range from red to golden yellow
                    this.hue = Math.random() > 0.4 ? (Math.random() * 20 + 35) : (Math.random() * 15);
                }
                update() {
                    this.y -= this.speedY;
                    this.x += this.speedX;
                    this.opacity -= this.fadeSpeed;
                    if (this.y < -10 || this.opacity <= 0) {
                        this.reset();
                    }
                }
                draw() {
                    ctx.save();
                    ctx.globalAlpha = Math.max(0, this.opacity);
                    ctx.fillStyle = `hsl(${this.hue}, 100%, 55%)`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
            }

            for (let i = 0; i < 45; i++) {
                particles.push(new Particle());
            }

            function animateParticles() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        }
    </script>
</body>
</html>
