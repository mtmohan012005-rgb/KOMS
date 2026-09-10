<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Invalid form submission.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = login_user($pdo, $email, $password);

        if ($result['success']) {
            session_regenerate_id(true);
            $_SESSION['success_msg'] = 'Welcome back!';

            if ($result['role'] === 'super_admin') redirect('/admin/dashboard.php');
            if ($result['role'] === 'master') redirect('/master/dashboard.php');
            if ($result['role'] === 'senior') redirect('/senior/dashboard.php');
            if ($result['role'] === 'student') redirect('/student/dashboard.php');

            redirect('/index.php');
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>

<style>
    .koms-login-page {
        --login-bg: #050505;
        --login-panel: rgba(10, 10, 10, 0.92);
        --login-input: #151515;
        --login-red: #c91515;
        --login-red-bright: #f12b1f;
        --login-gold: #ffd21a;
        --login-muted: #777;
        min-height: calc(100vh - 64px);
        margin: -24px -12px -30px;
        background:
            radial-gradient(circle at 50% 42%, rgba(255, 186, 0, .075), transparent 25%),
            radial-gradient(circle at 0% 100%, rgba(180, 0, 0, .18), transparent 28%),
            radial-gradient(circle at 100% 100%, rgba(180, 0, 0, .18), transparent 28%),
            var(--login-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        isolation: isolate;
        padding: 38px 18px;
        box-sizing: border-box;
        font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .koms-login-page::before,
    .koms-login-page::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .koms-login-page::before {
        background:
            linear-gradient(135deg, rgba(185, 0, 0, .24), transparent 17%),
            linear-gradient(315deg, rgba(185, 0, 0, .24), transparent 17%);
        z-index: -2;
    }

    .koms-login-page::after {
        background:
            repeating-linear-gradient(135deg, transparent 0 46px, rgba(255, 0, 0, .025) 47px 49px, transparent 50px 90px),
            linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
        background-size: auto, 48px 48px, 48px 48px;
        opacity: .45;
        z-index: -1;
    }

    .koms-login-shell {
        width: min(430px, 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .koms-logo-wrap {
        position: relative;
        width: 170px;
        height: 170px;
        margin-bottom: 6px;
        transform: translateY(125px);
        animation: komsLogoMove 1s cubic-bezier(.2,.8,.2,1) 2s forwards;
    }

    .koms-logo-halves,
    .koms-logo-full {
        position: absolute;
        inset: 0;
    }

    .koms-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        filter: drop-shadow(0 0 10px rgba(255, 190, 0, .16));
    }

    .koms-logo-left,
    .koms-logo-right {
        position: absolute;
        inset: 0;
    }

    .koms-logo-left {
        clip-path: inset(0 50% 0 0);
        animation: komsLeftIn 1s cubic-bezier(.2,.8,.2,1) forwards;
    }

    .koms-logo-right {
        clip-path: inset(0 0 0 50%);
        animation: komsRightIn 1s cubic-bezier(.2,.8,.2,1) forwards;
    }

    .koms-logo-halves {
        animation: komsHideHalves .1s linear 1.2s forwards;
    }

    .koms-logo-full {
        opacity: 0;
        animation: komsShowFull .55s ease-out 1.1s forwards;
    }

    .koms-flare {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 3px;
        height: 170%;
        transform: translate(-50%, -50%) scaleY(0);
        opacity: 0;
        background: #fff;
        box-shadow: 0 0 18px 8px rgba(255,210,0,.78), 0 0 42px 20px rgba(255,95,0,.35);
        z-index: 4;
        pointer-events: none;
        animation: komsFlash .6s ease-out .9s forwards;
    }

    .koms-title {
        text-align: center;
        opacity: 0;
        transform: translateY(20px);
        animation: komsFadeUp .8s ease-out 2.55s forwards;
        margin: 0 0 24px;
    }

    .koms-title h1 {
        margin: 0 0 5px;
        color: var(--login-gold);
        font-family: "Caveat Brush", "Segoe Print", cursive;
        font-size: clamp(28px, 7vw, 36px);
        line-height: 1;
        letter-spacing: 1.5px;
        text-shadow: 0 0 18px rgba(255, 193, 7, .18);
    }

    .koms-title p {
        margin: 0;
        color: #f2f2f2;
        font-size: 11px;
        line-height: 1.45;
        letter-spacing: .35px;
    }

    .koms-login-card {
        width: 100%;
        padding: 28px 24px 20px;
        box-sizing: border-box;
        background: var(--login-panel);
        border: 1px solid rgba(240, 36, 36, .44);
        border-radius: 16px;
        box-shadow: 0 24px 55px rgba(0, 0, 0, .64), inset 0 1px 0 rgba(255,255,255,.025);
        backdrop-filter: blur(12px);
        opacity: 0;
        transform: translateY(20px);
        animation: komsFadeUp .8s ease-out 3s forwards;
    }

    .koms-login-card.gold-border {
        border-color: rgba(255, 196, 0, .34);
    }

    .koms-error {
        padding: 11px 13px;
        margin-bottom: 16px;
        border-radius: 10px;
        color: #ffd7d7;
        background: rgba(156, 12, 12, .2);
        border: 1px solid rgba(241, 43, 31, .38);
        font-size: 12px;
    }

    .koms-field {
        position: relative;
        margin-bottom: 14px;
    }

    .koms-field > i:first-child {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #858585;
        font-size: 14px;
        z-index: 2;
    }

    .koms-field input {
        width: 100%;
        height: 48px;
        padding: 0 44px;
        border-radius: 9px;
        border: 1px solid #252525;
        outline: 0;
        background: var(--login-input);
        color: #fff;
        font-size: 13px;
        transition: border-color .25s, box-shadow .25s, background .25s;
        box-sizing: border-box;
    }

    .koms-field input::placeholder {
        color: #686868;
    }

    .koms-field input:focus {
        border-color: rgba(255, 60, 30, .7);
        background: #181818;
        box-shadow: 0 0 0 3px rgba(213, 27, 27, .1);
    }

    .koms-toggle-pass {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        padding: 3px;
        border: 0;
        background: transparent;
        color: #7e7e7e;
        cursor: pointer;
        z-index: 2;
    }

    .koms-toggle-pass:hover {
        color: #d7d7d7;
    }

    .koms-login-btn {
        width: 100%;
        height: 50px;
        margin-top: 5px;
        border: 0;
        border-radius: 9px;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .2px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        cursor: pointer;
        background: linear-gradient(90deg, #aa0909 0%, #e02020 55%, #f3481c 100%);
        box-shadow: 0 10px 20px rgba(165, 13, 13, .22);
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }

    .koms-login-btn:hover {
        transform: translateY(-1px);
        filter: brightness(1.05);
        box-shadow: 0 14px 25px rgba(198, 23, 20, .28);
    }

    .koms-login-btn:active {
        transform: translateY(0);
    }

    .koms-login-meta {
        margin-top: 17px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 11px;
    }

    .koms-login-meta a {
        color: #b7b7b7;
        text-decoration: none;
    }

    .koms-login-meta a:hover {
        color: #fff;
    }

    .koms-register {
        margin-top: 14px;
        text-align: center;
        color: #5e5e5e;
        font-size: 10px;
    }

    .koms-register a {
        color: #aaa;
        text-decoration: none;
        font-weight: 600;
    }

    .koms-register a:hover {
        color: var(--login-gold);
    }

    .koms-motto {
        margin-top: 15px;
        color: #4e4e4e;
        font-size: 10px;
        letter-spacing: .35px;
        text-align: center;
    }

    @keyframes komsLeftIn {
        from { transform: translateX(-100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes komsRightIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes komsFlash {
        0% { transform: translate(-50%, -50%) scaleY(0); opacity: 0; }
        38% { transform: translate(-50%, -50%) scaleY(1); opacity: 1; }
        100% { transform: translate(-50%, -50%) scaleY(0); opacity: 0; }
    }

    @keyframes komsShowFull {
        from { opacity: 0; filter: drop-shadow(0 0 20px rgba(255, 200, 0, 0)); }
        to { opacity: 1; filter: drop-shadow(0 0 18px rgba(255, 200, 0, .42)); }
    }

    @keyframes komsHideHalves {
        to { opacity: 0; visibility: hidden; }
    }

    @keyframes komsLogoMove {
        from { transform: translateY(125px); }
        to { transform: translateY(0); }
    }

    @keyframes komsFadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .koms-logo-wrap,
        .koms-logo-left,
        .koms-logo-right,
        .koms-logo-halves,
        .koms-logo-full,
        .koms-flare,
        .koms-title,
        .koms-login-card {
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
    }

    @media (max-width: 575.98px) {
        .koms-login-page {
            min-height: calc(100vh - 56px);
            margin: -16px -8px -24px;
            padding: 22px 13px 28px;
        }

        .koms-logo-wrap {
            width: 145px;
            height: 145px;
        }

        .koms-login-card {
            padding: 23px 18px 18px;
        }
    }
</style>

<div class="koms-login-page">
    <div class="koms-login-shell">
        <div class="koms-logo-wrap" aria-label="Mass Dragon Dojo logo animation">
            <div class="koms-flare"></div>

            <div class="koms-logo-halves" aria-hidden="true">
                <img src="<?= APP_URL ?>/assets/images/mass-dragon-logo.png" class="koms-logo koms-logo-left" alt="">
                <img src="<?= APP_URL ?>/assets/images/mass-dragon-logo.png" class="koms-logo koms-logo-right" alt="">
            </div>

            <div class="koms-logo-full">
                <img src="<?= APP_URL ?>/assets/images/mass-dragon-logo.png" class="koms-logo" alt="Mass Dragon Dojo">
            </div>
        </div>

        <div class="koms-title">
            <h1>MASS DRAGON DOJO</h1>
            <p>Karate Organization Management System<br>(KOMS)</p>
        </div>

        <div class="koms-login-card <?= isset($_SESSION['error_msg']) ? '' : 'gold-border' ?>">
            <?php if (!empty($_SESSION['error_msg'])): ?>
                <div class="koms-error">
                    <i class="fas fa-circle-exclamation me-1"></i>
                    <?= htmlspecialchars($_SESSION['error_msg']) ?>
                </div>
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">

                <div class="koms-field">
                    <i class="fas fa-user"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="Username / Email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="koms-field">
                    <i class="fas fa-lock"></i>
                    <input
                        id="komsPassword"
                        type="password"
                        name="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="koms-toggle-pass" id="komsTogglePassword" aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="koms-login-btn">
                    Login <i class="fas fa-arrow-right" style="font-size:12px;"></i>
                </button>
            </form>

            <div class="koms-login-meta">
                <a href="<?= APP_URL ?>/register.php"><i class="fas fa-user-plus me-1"></i>Create account</a>
                <a href="<?= APP_URL ?>/index.php"><i class="fas fa-home me-1"></i>Back to home</a>
            </div>

            <div class="koms-register">Secure access for KOMS members and administrators.</div>
        </div>

        <div class="koms-motto">Dream &nbsp;•&nbsp; Discipline &nbsp;•&nbsp; Dojo</div>
    </div>
</div>

<script>
    (() => {
        const password = document.getElementById('komsPassword');
        const toggle = document.getElementById('komsTogglePassword');

        if (!password || !toggle) return;

        toggle.addEventListener('click', () => {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            toggle.innerHTML = `<i class="fas fa-${isPassword ? 'eye-slash' : 'eye'}"></i>`;
        });
    })();
</script>

<?php require_once 'includes/footer.php'; ?>
