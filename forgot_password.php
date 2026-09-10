<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch. Please reload and try again.';
    } else {
        $login_id = trim($_POST['login_id'] ?? '');
        $reason = trim($_POST['reason'] ?? 'Forgot password / Need reset to Date of Birth');

        if ($login_id === '') {
            $error_message = 'Please enter your User ID or Gmail address.';
        } else {
            // Find student by member_id or email
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, member_id, email, dob, password_change_count FROM users WHERE member_id = ? OR email = ? LIMIT 1");
            $stmt->execute([$login_id, $login_id]);
            $user = $stmt->fetch();

            if (!$user) {
                $error_message = 'No account found matching this User ID or Gmail address.';
            } else {
                $user_id = (int)$user['id'];

                // Check for existing pending request
                $chk = $pdo->prepare("SELECT id, created_at FROM password_reset_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
                $chk->execute([$user_id]);
                $existingReq = $chk->fetch();

                if ($existingReq) {
                    $success_message = 'A password reset request is already pending review with your Dojo Master (Submitted: ' . date('d M Y, h:i A', strtotime($existingReq['created_at'])) . '). Please contact your Sensei.';
                } else {
                    // Find dojo ID
                    $dojoStmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? ORDER BY id DESC LIMIT 1");
                    $dojoStmt->execute([$user_id]);
                    $dojo_id = (int)$dojoStmt->fetchColumn() ?: null;

                    $ins = $pdo->prepare("INSERT INTO password_reset_requests (user_id, dojo_id, reason, status) VALUES (?, ?, ?, 'pending')");
                    if ($ins->execute([$user_id, $dojo_id, $reason])) {
                        try {
                            log_audit_action($pdo, $user_id, 'CREATE', 'password_request', $pdo->lastInsertId(), 'Submitted external password reset request to Master Portal');
                        } catch (Throwable $e) {}

                        $student_name = trim($user['first_name'] . ' ' . $user['last_name']);
                        $success_message = "Your password reset request for <strong>" . htmlspecialchars($student_name) . "</strong> (" . htmlspecialchars($user['member_id'] ?: $user['email']) . ") has been successfully submitted to the <strong>Master Portal</strong>! Your Sensei will review and reset your access.";
                    } else {
                        $error_message = 'Could not submit request right now. Please try again or contact your dojo.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Password Reset • Master Portal | KOMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #080808;
            --form-bg: rgba(14, 14, 14, 0.94);
            --input-bg: #161616;
            --text-main: #ffffff;
            --text-muted: #888888;
            --accent-red: #c61a1a;
            --accent-yellow: #ffcc00;
        }

        * { box-sizing: border-box; }
        body, html {
            margin: 0; padding: 0; width: 100%; min-height: 100vh;
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex; align-items: center; justify-content: center;
        }

        .bg-grid {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(198, 26, 26, 0.15), transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(255, 204, 0, 0.1), transparent 40%),
                        #080808;
            z-index: 0;
        }

        .card-container {
            position: relative; z-index: 2;
            width: min(92%, 520px);
            background: var(--form-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 36px 30px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(12px);
        }

        .card-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .crest-img {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 2px solid var(--accent-yellow);
            margin: 0 auto 12px;
            display: block;
            object-fit: cover;
        }

        .badge-kicker {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 800;
            color: var(--accent-yellow);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 800;
            margin: 6px 0 8px;
            color: #fff;
        }

        .policy-box {
            background: rgba(255, 204, 0, 0.06);
            border: 1px solid rgba(255, 204, 0, 0.2);
            border-radius: 12px;
            padding: 14px;
            font-size: 12px;
            line-height: 1.5;
            color: #ddd;
            margin-bottom: 22px;
        }

        .policy-box strong {
            color: var(--accent-yellow);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #ccc;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            color: var(--accent-yellow);
            font-size: 14px;
        }

        input[type="text"], textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            color: #fff;
            font-size: 13px;
            transition: border-color .2s;
            font-family: inherit;
        }

        textarea {
            padding-left: 14px;
            min-height: 80px;
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-yellow);
            box-shadow: 0 0 10px rgba(255, 204, 0, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(90deg, var(--accent-red), #e02323);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform .15s, opacity .2s;
            margin-top: 20px;
        }

        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(198, 26, 26, 0.15);
            border: 1px solid #c61a1a;
            color: #ff9b9b;
        }

        .alert-success {
            background: rgba(46, 125, 50, 0.15);
            border: 1px solid #2e7d32;
            color: #a5d6a7;
        }

        .footer-nav {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
        }

        .footer-nav a {
            color: var(--accent-yellow);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="bg-grid"></div>

<div class="card-container">
    <div class="card-header">
        <img src="assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu Crest" class="crest-img">
        <div class="badge-kicker">Mass Dragon Dojo • Security</div>
        <h1>Request Password Reset</h1>
    </div>

    <div class="policy-box">
        <i class="fas fa-shield-halved me-1" style="color:var(--accent-yellow)"></i>
        <strong>Academy Password Policy:</strong> Default student passwords are set to your <strong>Date of Birth (DD.MM.YYYY)</strong>. 
        Each student is granted <strong>1 direct password change</strong>. Subsequent changes must be requested through the <strong>Master Portal</strong> for Sensei approval.
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert-box alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <div><?= htmlspecialchars($error_message) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert-box alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= $success_message ?></div>
        </div>
        <div class="footer-nav">
            <a href="index.php"><i class="fas fa-arrow-left me-1"></i> Return to Dojo Login</a>
        </div>
    <?php else: ?>
        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="form-group">
                <label for="login_id">User ID or Gmail Address</label>
                <div class="input-wrap">
                    <i class="fas fa-id-card-clip"></i>
                    <input type="text" name="login_id" id="login_id" placeholder="e.g. sairohan2012.koms or student@gmail.com" required autofocus value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="reason">Reason for Reset Request</label>
                <textarea name="reason" id="reason" placeholder="e.g. Forgot password, need reset back to Date of Birth, or phone/device lost" required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                <span>Submit Request to Master Portal</span>
            </button>
        </form>

        <div class="footer-nav">
            <a href="index.php"><i class="fas fa-arrow-left me-1"></i> Return to Dojo Login</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
