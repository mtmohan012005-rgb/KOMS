<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    if (has_role('master')) {
        redirect('/master/index.php');
    }
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please reload and try again.';
    } else {
        $login = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($login === '' || $password === '') {
            $error = 'Please enter User ID/email and password.';
        } else {
            $result = login_user($pdo, $login, $password);
            if ($result['success']) {
                if ($result['role'] !== 'master') {
                    $_SESSION = [];
                    session_destroy();
                    $error = 'This portal is only for Master accounts.';
                } else {
                    $_SESSION['success_msg'] = 'Welcome to the Master Portal.';
                    redirect('/master/index.php');
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0d0f12">
<title>Mass Dragon Dojo - Master Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;font-family:Poppins,sans-serif;background:radial-gradient(circle at 20% 20%,rgba(216,173,66,.2),transparent 25%),radial-gradient(circle at 80% 80%,rgba(181,18,24,.2),transparent 25%),linear-gradient(135deg,#08090b,#17191e,#0c0d0f);color:#fff;overflow-x:hidden}body:before{content:'龍';position:fixed;right:-30px;bottom:-60px;font-size:330px;color:rgba(240,213,132,.025);pointer-events:none}.login-container{width:100%;max-width:420px;position:relative;z-index:2}.login-card{padding:32px 28px;background:linear-gradient(145deg,rgba(31,34,40,.97),rgba(13,15,18,.97));border:1px solid rgba(216,173,66,.18);border-radius:24px;box-shadow:0 25px 70px rgba(0,0,0,.45);backdrop-filter:blur(15px);animation:cardAppear .7s ease}@keyframes cardAppear{from{opacity:0;transform:translateY(25px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}.logo-wrapper{display:flex;justify-content:center}.logo{width:74px;height:74px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(145deg,#f1d27a,#a87c20);color:#191408;font-size:32px;box-shadow:0 12px 35px rgba(216,173,66,.2);animation:logoFloat 3s ease-in-out infinite}@keyframes logoFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}.title{text-align:center;margin-top:18px}.title h1{font-size:25px;font-weight:800}.title h1 span{color:#f0d27c}.title p{margin-top:5px;color:#8f959e;font-size:9px;line-height:1.6}.login-label{margin-top:25px;margin-bottom:13px;text-align:center;color:#aaaeb5;font-size:9px;text-transform:uppercase;letter-spacing:1.5px}.error{padding:10px 12px;margin-bottom:13px;border-radius:10px;background:rgba(215,50,59,.12);border:1px solid rgba(215,50,59,.25);color:#ff858b;text-align:center;font-size:8px;animation:errorShake .3s ease}@keyframes errorShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}.form-group{margin-bottom:14px}.form-group label{display:block;margin-bottom:6px;color:#b7bac0;font-size:9px;font-weight:500}.input-wrapper{position:relative}.input-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#d7ba63;font-size:14px;pointer-events:none}.input-wrapper input{width:100%;height:46px;padding:0 42px;border:1px solid #2d3239;outline:none;border-radius:11px;background:#1a1d22;color:#fff;font-family:inherit;font-size:10px;transition:.2s ease}.input-wrapper input::placeholder{color:#676d76}.input-wrapper input:focus{border-color:#aa8938;box-shadow:0 0 0 3px rgba(216,173,66,.07);background:#1d2025}.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#878d95;cursor:pointer;font-size:14px}.password-toggle:hover{color:#f0d27c}.form-options{display:flex;justify-content:space-between;align-items:center;margin-top:4px;margin-bottom:18px}.remember{display:flex;align-items:center;gap:6px;color:#818791;font-size:8px}.remember input{accent-color:#d8ad42}.login-button{width:100%;height:46px;border:0;border-radius:11px;background:linear-gradient(135deg,#f0d27c,#c0932d);color:#211908;font-size:10px;font-weight:800;cursor:pointer;box-shadow:0 10px 25px rgba(190,145,37,.2);transition:.25s ease}.login-button:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(190,145,37,.28)}.login-note{margin-top:17px;padding:11px;border-radius:10px;background:#191c21;border:1px solid #282c33;text-align:center;color:#737983;font-size:7px;line-height:1.8}.login-note strong{color:#d8bd6c}.back-link{display:block;margin-top:16px;text-align:center;color:#8c929a;font-size:8px;text-decoration:none}.back-link:hover{color:#f0d27c}.footer{margin-top:18px;text-align:center;color:#60656d;font-size:7px}@media(max-width:500px){body{padding:12px}.login-card{padding:25px 18px;border-radius:20px}.logo{width:65px;height:65px;font-size:27px}.title h1{font-size:21px}.input-wrapper input,.login-button{height:45px}}
</style>
</head>
<body>
<div class="login-container">
<div class="login-card">
<div class="logo-wrapper"><div class="logo">🥋</div></div>
<div class="title"><h1><span>Mass Dragon</span> Dojo</h1><p>Master Portal · Karate Organization Management System</p></div>
<div class="login-label">Master Login</div>
<?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST" action="" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
<div class="form-group"><label for="username">User ID / Email</label><div class="input-wrapper"><span class="input-icon">👤</span><input type="text" id="username" name="username" placeholder="Enter Master User ID or email" required autocomplete="username"></div></div>
<div class="form-group"><label for="password">Password</label><div class="input-wrapper"><span class="input-icon">🔒</span><input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password"><button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">👁</button></div></div>
<div class="form-options"><label class="remember"><input type="checkbox" name="remember"> Remember me</label><span style="color:#6f747b;font-size:8px">Secure KOMS login</span></div>
<button type="submit" class="login-button" id="loginButton">LOGIN TO MASTER PORTAL</button>
</form>
<div class="login-note"><strong>Database Login</strong><br>Use your KOMS Master User ID or registered email and password.</div>
<a href="../login.php" class="back-link">← Back to Main KOMS Login</a>
</div>
<div class="footer">© <?= date('Y') ?> Mass Dragon Dojo</div>
</div>
<script>const password=document.getElementById('password');const toggle=document.getElementById('passwordToggle');const form=document.querySelector('form');const button=document.getElementById('loginButton');toggle.addEventListener('click',function(){const show=password.type==='password';password.type=show?'text':'password';toggle.textContent=show?'🙈':'👁';toggle.setAttribute('aria-label',show?'Hide password':'Show password')});form.addEventListener('submit',function(){button.textContent='CHECKING LOGIN...';button.style.opacity='.75'});</script>
</body>
</html>
