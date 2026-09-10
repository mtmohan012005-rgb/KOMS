<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') redirect('/admin/dashboard.php');
    if ($role === 'master') redirect('/master/dashboard.php');
    if ($role === 'senior') redirect('/senior/dashboard.php');
    if ($role === 'student') redirect('/student/dashboard.php');
    redirect('/index.php');
}

$error_message = '';
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
    <title>Mass Dragon Dojo - Login | KOMS</title>
    <!-- FontAwesome for Input Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Variables */
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
            height: 100%;
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* Dragon & Red Slashes Background Artwork */
        .background {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(to bottom, rgba(8, 8, 8, 0.45), rgba(8, 8, 8, 0.85)),
                url('assets/images/dragon_bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 1;
            pointer-events: none;
            filter: contrast(1.1);
        }

        /* Ambient Ember / Particle Canvas */
        #particleCanvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        /* Red Corner Slashes Simulation Overlay */
        .slash-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
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
            max-width: 420px;
            padding: 20px;
        }

        /* --- 1. LOGO ANIMATION SECTION --- */
        .logo-wrapper {
            position: relative;
            width: 170px;
            height: 170px;
            /* Starts lower in center, moves up at 2s */
            transform: translateY(160px);
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
                0 0 15px 6px rgba(255, 255, 255, 0.9),
                0 0 35px 15px rgba(255, 204, 0, 0.85),
                0 0 60px 25px rgba(224, 35, 35, 0.6);
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
            box-shadow: 0 0 20px 8px rgba(255, 204, 0, 0.8);
            transform: translate(-50%, -50%) scaleX(0);
            opacity: 0;
            z-index: 14;
            animation: flashHorizontal 0.6s ease-out 1.05s forwards;
        }

        /* Radial burst behind logo on convergence */
        .burst-ring {
            position: absolute;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            border: 2px solid rgba(255, 204, 0, 0.9);
            box-shadow: 0 0 30px rgba(255, 204, 0, 0.7);
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
            font-size: 32px;
            margin: 0 0 4px 0;
            letter-spacing: 1.5px;
            text-shadow: 0 0 15px rgba(255, 204, 0, 0.35);
        }

        .title-section p {
            color: var(--text-main);
            font-size: 11.5px;
            font-weight: 500;
            margin: 0;
            line-height: 1.45;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        /* --- 3. LOGIN FORM SECTION --- */
        .login-form {
            width: 100%;
            background: var(--form-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 28px 24px 22px 24px;
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
            background: rgba(198, 26, 26, 0.2);
            border: 1px solid rgba(224, 35, 35, 0.6);
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
            border: 1px solid #232323;
            padding: 14px 44px;
            border-radius: 10px;
            color: var(--text-main);
            font-size: 13.5px;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .input-group input:focus {
            border-color: rgba(224, 35, 35, 0.8);
            background: #1c1c1c;
            box-shadow: 0 0 14px rgba(224, 35, 35, 0.25);
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

        /* Demo Role Pills for Instant 1-Click Access */
        .demo-roles-container {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #1a1a1a;
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
            background: #202020;
            border-color: var(--accent-yellow);
            color: #fff;
        }

        .demo-pill i {
            color: var(--accent-yellow);
            font-size: 11px;
        }

        .footer-links {
            text-align: center;
            margin-top: 18px;
            font-size: 11px;
            color: #666;
            letter-spacing: 0.5px;
        }

        .sub-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 11px;
        }

        .sub-actions a {
            color: #888;
            text-decoration: none;
            transition: color 0.2s;
        }

        .sub-actions a:hover {
            color: var(--accent-yellow);
        }

        /* --- KEYFRAMES --- */
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
            40% { transform: translate(-50%, -50%) scaleY(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scaleY(0); opacity: 0; }
        }

        @keyframes flashHorizontal {
            0% { transform: translate(-50%, -50%) scaleX(0); opacity: 0; }
            50% { transform: translate(-50%, -50%) scaleX(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scaleX(0); opacity: 0; }
        }

        @keyframes burstExpand {
            0% { transform: scale(0.6); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        @keyframes showFullLogo {
            0% { opacity: 0; filter: drop-shadow(0 0 25px rgba(255, 204, 0, 0)); transform: scale(0.92); }
            50% { filter: drop-shadow(0 0 30px rgba(255, 204, 0, 0.9)); transform: scale(1.05); }
            100% { opacity: 1; filter: drop-shadow(0 0 16px rgba(255, 204, 0, 0.5)); transform: scale(1); }
        }

        @keyframes hideHalves {
            to { opacity: 0; visibility: hidden; }
        }

        @keyframes moveLogoUp {
            0% { transform: translateY(160px); }
            100% { transform: translateY(0); }
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(22px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* Fast forward / Skip button for rapid workflows */
        .skip-intro-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(20, 20, 20, 0.6);
            border: 1px solid #333;
            color: #888;
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 20px;
            cursor: pointer;
            z-index: 20;
            transition: all 0.2s;
        }

        .skip-intro-btn:hover {
            color: var(--accent-yellow);
            border-color: var(--accent-yellow);
        }

        /* Immediate display class when skipping intro */
        .skip-animation .logo-wrapper {
            transform: translateY(0) !important;
            animation: none !important;
        }
        .skip-animation .logo-halves {
            display: none !important;
        }
        .skip-animation .logo-full {
            opacity: 1 !important;
            animation: none !important;
            filter: drop-shadow(0 0 16px rgba(255, 204, 0, 0.5)) !important;
        }
        .skip-animation .title-section,
        .skip-animation .login-form {
            opacity: 1 !important;
            transform: translateY(0) !important;
            animation: none !important;
        }
        .skip-animation .flare,
        .skip-animation .flare-horizontal,
        .skip-animation .burst-ring {
            display: none !important;
        }
    </style>
</head>
<body>

    <!-- Background Artwork -->
    <div class="background"></div>
    <div class="slash-overlay"></div>
    <canvas id="particleCanvas"></canvas>

    <button type="button" class="skip-intro-btn" id="skipIntroBtn" title="Skip animation">
        <i class="fas fa-forward me-1"></i> Skip Intro
    </button>

    <div class="main-container" id="mainContainer">
        
        <!-- --- 1. LOGO ANIMATION SECTION --- -->
        <div class="logo-wrapper">
            <div class="flare"></div>
            <div class="flare-horizontal"></div>
            <div class="burst-ring"></div>
            
            <div class="logo-halves">
                <!-- Authentic Shorin Ryu Karate Crest -->
                <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Left Half" class="logo-image logo-left">
                <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Right Half" class="logo-image logo-right">
            </div>
            
            <!-- Full Converged Logo -->
            <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Crest Full" class="logo-image logo-full"> 
        </div> 
 
        <!-- --- 2. DOJO TITLE SECTION --- --> 
        <div class="title-section"> 
            <h1>MASS DRAGON DOJO</h1> 
            <p>Karate Organization Management System<br>(KOMS)</p> 
        </div> 
 
        <!-- --- 3. LOGIN FORM SECTION --- --> 
        <div class="login-form">
            <?php if (!empty($error_message)): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm"> 
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="input-group"> 
                    <i class="fas fa-user icon-left"></i> 
                    <input type="text" name="email" id="emailInput" placeholder="Username / Email" required autofocus autocomplete="username"> 
                </div> 

                <div class="input-group"> 
                    <i class="fas fa-lock icon-left"></i> 
                    <input type="password" name="password" id="passwordInput" placeholder="Password" required autocomplete="current-password"> 
                    <i class="fas fa-eye icon-right" id="togglePassword" title="Toggle password visibility"></i> 
                </div> 

                <button type="submit" class="login-btn" id="loginSubmitBtn">
                    <span>Login</span>
                    <i class="fas fa-arrow-right" style="font-size:13px;"></i>
                </button> 
            </form>

            <div class="sub-actions">
                <a href="find_dojo.php"><i class="fas fa-compass me-1"></i> Find Dojo</a>
                <a href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                <a href="uploads/koms-mobile.apk" download title="Download KOMS Android App (APK)"><i class="fab fa-android me-1" style="color: #3ddc84;"></i> Android App</a>
            </div>

            <!-- Demo 1-Click Role Fillers -->
            <div class="demo-roles-container">
                <div class="demo-roles-label">
                    <span><i class="fas fa-bolt me-1"></i> 1-Click Demo Accounts</span>
                    <span style="color:#777; font-weight:normal;">Password: password123</span>
                </div>
                <div class="demo-pills">
                    <button type="button" class="demo-pill" onclick="fillCredentials('admin@gmail.com', 'password123')">
                        <i class="fas fa-crown"></i>
                        <span>Grand Master</span>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('master@gmail.com', 'password123')">
                        <i class="fas fa-user-ninja"></i>
                        <span>Sensei Master</span>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('senior@gmail.com', 'password123')">
                        <i class="fas fa-medal"></i>
                        <span>Senior Belt</span>
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('student@gmail.com', 'password123')">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student</span>
                    </button>
                </div>
            </div>

            <div class="footer-links"> 
                Dream &nbsp;&bull;&nbsp; Discipline &nbsp;&bull;&nbsp; Dojo 
            </div> 
        </div> 
 
    </div> 

    <script>
        // 1. Password Visibility Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // 2. Demo Credentials Auto-Fill
        function fillCredentials(email, password) {
            const emailInput = document.getElementById('emailInput');
            const pwdInput = document.getElementById('passwordInput');
            if (emailInput && pwdInput) {
                emailInput.value = email;
                pwdInput.value = password;
                emailInput.focus();
                
                // Subtle visual feedback
                const form = document.querySelector('.login-form');
                form.style.borderColor = 'var(--accent-yellow)';
                setTimeout(() => {
                    form.style.borderColor = 'rgba(224, 35, 35, 0.35)';
                }, 400);
            }
        }

        // 3. Skip Animation Toggle
        const skipBtn = document.getElementById('skipIntroBtn');
        const container = document.getElementById('mainContainer');

        if (skipBtn && container) {
            // Auto skip if redirected back due to error
            <?php if (!empty($error_message)): ?>
                container.classList.add('skip-animation');
                skipBtn.style.display = 'none';
            <?php endif; ?>

            skipBtn.addEventListener('click', () => {
                container.classList.add('skip-animation');
                skipBtn.style.display = 'none';
            });
        }

        // 4. Subtle Martial Arts Ember Particles
        const canvas = document.getElementById('particleCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let width = canvas.width = window.innerWidth;
            let height = canvas.height = window.innerHeight;

            window.addEventListener('resize', () => {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            });

            const particles = [];
            const particleCount = 28;

            for (let i = 0; i < particleCount; i++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 2 + 0.8,
                    speedY: -(Math.random() * 0.8 + 0.3),
                    speedX: (Math.random() - 0.5) * 0.5,
                    color: Math.random() > 0.4 ? 'rgba(255, 204, 0, ' : 'rgba(224, 35, 35, ',
                    opacity: Math.random() * 0.7 + 0.2
                });
            }

            function render() {
                ctx.clearRect(0, 0, width, height);
                for (let p of particles) {
                    p.y += p.speedY;
                    p.x += p.speedX;
                    if (p.y < 0) {
                        p.y = height + 10;
                        p.x = Math.random() * width;
                    }
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fillStyle = p.color + p.opacity + ')';
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = '#ffcc00';
                    ctx.fill();
                }
                requestAnimationFrame(render);
            }
            render();
        }
    </script>
</body> 
</html>
