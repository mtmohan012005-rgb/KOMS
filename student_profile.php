<?php
/**
 * student_profile.php
 * KOMS - Dedicated High-Fidelity Student Profile View
 * Matching official KOMS martial arts design language
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Allow logged in users or default to demo view
$current_user_id = is_logged_in() ? (int)$_SESSION['user_id'] : null;
$current_user_role = is_logged_in() ? $_SESSION['user_role'] : 'guest';

// Determine target student ID
$target_student_id = isset($_GET['id']) ? (int)$_GET['id'] : ($current_user_id ?: 10);

// Fetch target student details
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT gh.new_belt FROM grading_history gh WHERE gh.student_id = u.id ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1) AS current_belt,
           (SELECT d.name FROM dojo_memberships dm JOIN dojos d ON dm.dojo_id = d.id WHERE dm.student_id = u.id AND dm.status = 'approved' LIMIT 1) AS dojo_name,
           (SELECT d.location FROM dojo_memberships dm JOIN dojos d ON dm.dojo_id = d.id WHERE dm.student_id = u.id AND dm.status = 'approved' LIMIT 1) AS dojo_location
    FROM users u 
    WHERE u.id = ? 
    LIMIT 1
");
$stmt->execute([$target_student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    // Fallback to student 10 if ID not found
    $stmt->execute([10]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate age
$age = 14;
if (!empty($student['dob'])) {
    $dobDate = new DateTime($student['dob']);
    $now = new DateTime();
    $age = $now->diff($dobDate)->y;
}

// Formatting
$full_name = trim($student['first_name'] . ' ' . $student['last_name']);
if (strpos($student['last_name'], 'Sai') !== false || strpos($student['first_name'], 'Sai') !== false) {
    $display_name = 'L. Sai Rohan';
} else {
    $display_name = $full_name;
}

$dob_formatted = !empty($student['dob']) ? date('d M Y', strtotime($student['dob'])) : '20 Oct 2012';
$doj_formatted = !empty($student['date_of_joining']) ? date('d M Y', strtotime($student['date_of_joining'])) : '01 Aug 2026';
$gender = ucfirst($student['gender'] ?: 'Male');
$blood_group = $student['blood_group'] ?: 'A1+ve';
$dojo_name = $student['dojo_name'] ?: 'Main Dojo';
$dojo_location = $student['dojo_location'] ?: 'Chennai';
$current_rank = $student['current_belt'] ?: 'White Belt';

$father_name = $student['father_name'] ?: 'Lingadhurai. S';
$mother_name = $student['mother_name'] ?: 'Patturani. L';
$phone = $student['phone'] ?: '8939319656';
$alternate_phone = $student['alternate_phone'] ?: '9841882666';
$address = $student['address'] ?: 'J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai.';
$profile_photo = $student['profile_photo'] ?: '/assets/images/sai_rohan_portrait.jpg';

// Calculate monthly progress metrics
$att_total = 16;
$att_attended = 12;
try {
    $att_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_entries WHERE student_id = ? AND status = 'present'");
    $att_stmt->execute([$target_student_id]);
    $real_present = (int)$att_stmt->fetchColumn();
    if ($real_present > 0) $att_attended = $real_present;
} catch (Exception $e) {}

$att_pct = min(100, round(($att_attended / $att_total) * 100));

// Handle inline edit submission
$edit_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student_profile'])) {
    if (is_logged_in() && in_array($current_user_role, ['master', 'super_admin', 'student'])) {
        $p_father = trim($_POST['father_name'] ?? '');
        $p_mother = trim($_POST['mother_name'] ?? '');
        $p_phone = trim($_POST['phone'] ?? '');
        $p_alt_phone = trim($_POST['alternate_phone'] ?? '');
        $p_address = trim($_POST['address'] ?? '');
        $p_blood = trim($_POST['blood_group'] ?? '');

        $up_stmt = $pdo->prepare("
            UPDATE users 
            SET father_name = ?, mother_name = ?, phone = ?, alternate_phone = ?, address = ?, blood_group = ?
            WHERE id = ?
        ");
        $up_stmt->execute([$p_father, $p_mother, $p_phone, $p_alt_phone, $p_address, $p_blood, $target_student_id]);
        $edit_success = 'Profile details updated successfully.';
        
        // Refresh values
        $father_name = $p_father;
        $mother_name = $p_mother;
        $phone = $p_phone;
        $alternate_phone = $p_alt_phone;
        $address = $p_address;
        $blood_group = $p_blood;
    }
}

// Master / Topbar user display
$master_display = 'R.N. Thirukailash';
$master_role_label = 'Master';
if (is_logged_in()) {
    $master_display = $_SESSION['user_name'] ?? 'R.N. Thirukailash';
    $master_role_label = ucfirst($_SESSION['user_role'] ?? 'Master');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($display_name) ?> - Student Profile - KOMS</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@400;500;600;700;800&family=Noto+Serif+JP:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --koms-navy: #0B1F33;
            --koms-dark: #070d14;
            --koms-gold: #D4AF37;
            --koms-crimson: #C62828;
            --koms-crimson-hover: #b71c1c;
            --koms-card-bg: #FFFFFF;
            --koms-text-main: #1e293b;
            --koms-text-muted: #64748b;
            --koms-border: #e2e8f0;
            --sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: var(--koms-text-main);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR STYLING MATCHING MOCKUP */
        .koms-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0B1F33 0%, #07121e 100%);
            color: #ffffff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid var(--koms-gold);
            object-fit: cover;
            box-shadow: 0 0 12px rgba(212,175,55,0.4);
        }

        .brand-text h1 {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            letter-spacing: 1px;
            margin: 0;
        }

        .brand-text span {
            font-size: 0.6rem;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: block;
            margin-top: 3px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 16px 14px;
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .sidebar-item {
            margin-bottom: 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .sidebar-link i {
            width: 20px;
            font-size: 1.05rem;
            text-align: center;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.06);
        }

        /* Active capsule matching screenshot */
        .sidebar-item.active .sidebar-link {
            background-color: var(--koms-crimson);
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(198,40,40,0.45);
        }

        .sidebar-footer {
            padding: 24px 20px;
            position: relative;
            border-top: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }

        .sidebar-dragon-watermark {
            position: absolute;
            right: -15px;
            bottom: -15px;
            width: 140px;
            height: 140px;
            opacity: 0.12;
            pointer-events: none;
            filter: invert(1);
        }

        .sidebar-kanji {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.8rem;
            color: rgba(255,255,255,0.1);
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .sidebar-motto {
            position: relative;
            z-index: 1;
        }

        .sidebar-motto h5 {
            font-size: 0.82rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
            font-style: italic;
        }

        .sidebar-motto p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin: 0;
            font-style: italic;
        }

        /* MAIN CONTENT AREA */
        .koms-main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1 1 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: calc(100% - var(--sidebar-width));
        }

        /* TOP BAR */
        .koms-topbar {
            height: 68px;
            background: #ffffff;
            border-bottom: 1px solid var(--koms-border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-title h2 {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 0;
        }

        .topbar-title span {
            font-size: 0.75rem;
            color: var(--koms-text-muted);
            display: block;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-notification {
            position: relative;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #475569;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-notification .badge-count {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--koms-crimson);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 2px solid #ffffff;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
        }

        .topbar-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0f172a;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .topbar-user-info {
            line-height: 1.15;
            text-align: left;
        }

        .topbar-user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
        }

        .topbar-user-role {
            font-size: 0.7rem;
            color: var(--koms-text-muted);
        }

        /* PROFILE BODY CONTAINER */
        .profile-container {
            padding: 24px 28px 40px;
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
        }

        /* HERO CARD MATCHING SCREENSHOT */
        .hero-profile-card {
            border-radius: 20px;
            background: #08111d;
            background-image: linear-gradient(90deg, rgba(8,17,29,0.96) 0%, rgba(8,17,29,0.78) 55%, rgba(8,17,29,0.4) 100%), url('assets/images/sunset_martial_hero.jpg');
            background-size: cover;
            background-position: center right;
            padding: 28px 32px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(11,31,51,0.16);
            margin-bottom: 24px;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 24px;
            z-index: 2;
        }

        .hero-avatar-wrapper {
            position: relative;
            width: 105px;
            height: 105px;
            border-radius: 50%;
            border: 3px solid var(--koms-gold);
            padding: 3px;
            box-shadow: 0 0 20px rgba(212,175,55,0.4);
            flex-shrink: 0;
            background: #08111d;
        }

        .hero-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .hero-avatar-initials {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: var(--koms-gold);
            font-size: 2rem;
            font-weight: 800;
            display: grid;
            place-items: center;
        }

        .hero-details h1 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: -0.3px;
        }

        .badge-student-role {
            background-color: #b45309;
            color: #fef3c7;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 999px;
            display: inline-block;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .hero-meta {
            font-size: 0.82rem;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-kanji-bg {
            font-family: 'Noto Serif JP', serif;
            font-size: 4.8rem;
            font-weight: 900;
            color: rgba(255,255,255,0.06);
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            writing-mode: vertical-rl;
            text-orientation: upright;
            user-select: none;
            pointer-events: none;
            letter-spacing: 8px;
        }

        /* 4 QUICK METRIC STAT CARDS */
        .stat-badge-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-badge-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--koms-border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-badge-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }

        .stat-icon-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .icon-green { background: #dcfce7; color: #15803d; }
        .icon-red { background: #fee2e2; color: #b91c1c; }
        .icon-blue { background: #dbeafe; color: #1d4ed8; }
        .icon-gold { background: #fef3c7; color: #b45309; }

        .stat-text {
            line-height: 1.25;
            flex-grow: 1;
        }

        .stat-label {
            font-size: 0.72rem;
            color: var(--koms-text-muted);
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-subtext {
            font-size: 0.7rem;
            color: #64748b;
        }

        .stat-arrow {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* MAIN TWO COLUMN GRID */
        .profile-main-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
        }

        /* SECTION CARDS */
        .koms-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--koms-border);
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .koms-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-left i {
            color: #1e293b;
            font-size: 1rem;
        }

        .card-header-left h3 {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .btn-card-edit {
            color: #2563eb;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            border: 0;
            background: transparent;
        }

        .btn-card-edit:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* INFO ROWS */
        .info-table {
            width: 100%;
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .info-row-left {
            width: 42%;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #64748b;
            font-size: 0.85rem;
        }

        .info-row-left i {
            width: 16px;
            color: #94a3b8;
            text-align: center;
        }

        .info-row-right {
            width: 58%;
            font-size: 0.88rem;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }

        /* QUICK ACTIONS GRID */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .quick-action-tile {
            background: #ffffff;
            border: 1px solid var(--koms-border);
            border-radius: 14px;
            padding: 18px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: #0f172a;
            transition: all 0.2s;
        }

        .quick-action-tile:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-2px);
        }

        .action-tile-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-tile-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1rem;
        }

        .tile-blue { background: #eff6ff; color: #2563eb; }
        .tile-purple { background: #faf5ff; color: #9333ea; }
        .tile-gold { background: #fffbeb; color: #d97706; }
        .tile-indigo { background: #e0e7ff; color: #4338ca; }

        .action-tile-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
        }

        .quick-action-tile i.fa-arrow-right {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* MONTHLY PROGRESS SECTION */
        .progress-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .progress-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .progress-circle-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .progress-item-body {
            flex-grow: 1;
        }

        .progress-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.82rem;
        }

        .progress-item-title {
            font-weight: 600;
            color: #334155;
        }

        .progress-item-value {
            font-weight: 800;
            color: #0f172a;
        }

        .koms-progress-bar {
            height: 7px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }

        .koms-progress-fill {
            height: 100%;
            border-radius: 999px;
        }

        .fill-green { background: #10b981; }
        .fill-blue { background: #2563eb; }
        .fill-gold { background: #d97706; }
        .fill-purple { background: #8b5cf6; }

        .progress-percentage {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            min-width: 36px;
            text-align: right;
        }

        /* INSPIRATIONAL BANNER CARD */
        .inspirational-banner {
            border-radius: 16px;
            background: linear-gradient(135deg, #09131d 0%, #172433 100%);
            padding: 24px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .inspirational-text {
            position: relative;
            z-index: 2;
        }

        .inspirational-text h4 {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            font-weight: 700;
            font-style: italic;
            margin: 0;
            line-height: 1.25;
        }

        .inspirational-text h3 {
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            font-weight: 900;
            color: var(--koms-gold);
            font-style: italic;
            margin: 4px 0 0 0;
        }

        .inspirational-silhouette {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 140px;
            background-image: url('assets/images/mass_dragon_hero.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.35;
            mix-blend-mode: screen;
        }

        .banner-red-streak {
            position: absolute;
            right: 30px;
            bottom: 0;
            width: 80px;
            height: 10px;
            background: var(--koms-crimson);
            transform: skewX(-25deg);
        }

        @media (max-width: 992px) {
            body { flex-direction: column; }
            .koms-sidebar { display: none; }
            .koms-main-wrapper { margin-left: 0; width: 100%; }
            .stat-badge-grid { grid-template-columns: repeat(2, 1fr); }
            .profile-main-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- LEFT SIDEBAR -->
    <aside class="koms-sidebar">
        <div class="sidebar-brand">
            <img src="assets/images/shorin_ryu_crest.jpg" alt="KOMS Crest" class="sidebar-logo">
            <div class="brand-text">
                <h1>KOMS</h1>
                <span>Karate Organization System</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/index.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item active">
                <a href="<?= APP_URL ?>/master/students.php" class="sidebar-link">
                    <i class="fas fa-user-friends"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/student/attendance.php" class="sidebar-link">
                    <i class="fas fa-user-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/find_dojo.php" class="sidebar-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/student/fees.php" class="sidebar-link">
                    <i class="fas fa-indian-rupee-sign"></i>
                    <span>Fees</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/student/achievements.php" class="sidebar-link">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/student/grading.php" class="sidebar-link">
                    <i class="fas fa-certificate"></i>
                    <span>Certificates</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/admin/reports.php" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= APP_URL ?>/profile.php" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-kanji">武道</div>
            <img src="assets/images/mass-dragon-logo.png" class="sidebar-dragon-watermark" alt="">
            <div class="sidebar-motto">
                <h5>Discipline.</h5>
                <h5>Focus.</h5>
                <p>Stronger Future.</p>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="koms-main-wrapper">
        
        <!-- TOP APP BAR -->
        <header class="koms-topbar">
            <div class="topbar-left">
                <button class="btn btn-sm btn-light border-0 d-lg-none" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title">
                    <h2>KARATE DOJO</h2>
                    <span>Student Profile</span>
                </div>
            </div>

            <div class="topbar-right">
                <a href="<?= APP_URL ?>/student/announcements.php" class="btn-notification" title="Announcements & Alerts">
                    <i class="far fa-bell"></i>
                    <span class="badge-count">3</span>
                </a>

                <div class="topbar-user">
                    <div class="topbar-user-avatar">
                        <i class="fas fa-user-ninja"></i>
                    </div>
                    <div class="topbar-user-info">
                        <div class="topbar-user-name"><?= htmlspecialchars($master_display) ?></div>
                        <div class="topbar-user-role"><?= htmlspecialchars($master_role_label) ?> <i class="fas fa-chevron-down ms-1" style="font-size:0.65rem;"></i></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- PROFILE MAIN CONTAINER -->
        <main class="profile-container">

            <?php if (!empty($edit_success)): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($edit_success) ?>
                </div>
            <?php endif; ?>

            <!-- HERO HEADER CARD -->
            <section class="hero-profile-card">
                <div class="hero-left">
                    <div class="hero-avatar-wrapper">
                        <?php if (!empty($profile_photo)): ?>
                            <img src="<?= htmlspecialchars($profile_photo) ?>" alt="<?= htmlspecialchars($display_name) ?>" class="hero-avatar-img">
                        <?php else: ?>
                            <div class="hero-avatar-initials"><?= strtoupper(substr($student['first_name'],0,1) . substr($student['last_name'],0,1)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="hero-details">
                        <h1><?= htmlspecialchars($display_name) ?></h1>
                        <span class="badge-student-role">Student</span>
                        <div class="hero-meta">
                            <span>Age: <?= $age ?> years</span>
                            <span>|</span>
                            <span>DOB: <?= $dob_formatted ?></span>
                            <span>|</span>
                            <span>Joined On: <?= $doj_formatted ?></span>
                        </div>
                    </div>
                </div>

                <div class="hero-kanji-bg">大道通天</div>
            </section>

            <!-- 4 STAT/METRIC BADGES -->
            <section class="stat-badge-grid">
                <div class="stat-badge-card">
                    <div class="stat-icon-circle icon-green">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-text">
                        <div class="stat-label">Gender</div>
                        <div class="stat-value"><?= htmlspecialchars($gender) ?></div>
                    </div>
                </div>

                <div class="stat-badge-card">
                    <div class="stat-icon-circle icon-red">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="stat-text">
                        <div class="stat-label">Blood Group</div>
                        <div class="stat-value"><?= htmlspecialchars($blood_group) ?></div>
                    </div>
                </div>

                <div class="stat-badge-card">
                    <div class="stat-icon-circle icon-blue">
                        <i class="fas fa-torii-gate"></i>
                    </div>
                    <div class="stat-text">
                        <div class="stat-label">Dojo</div>
                        <div class="stat-value"><?= htmlspecialchars($dojo_name) ?></div>
                        <div class="stat-subtext"><?= htmlspecialchars($dojo_location) ?></div>
                    </div>
                </div>

                <a href="<?= APP_URL ?>/student/grading.php" class="stat-badge-card">
                    <div class="stat-icon-circle icon-gold">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-text">
                        <div class="stat-label">Current Rank</div>
                        <div class="stat-value"><?= htmlspecialchars($current_rank) ?></div>
                    </div>
                    <div class="stat-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            </section>

            <!-- TWO-COLUMN MAIN PROFILE CONTENT -->
            <div class="profile-main-grid">
                
                <!-- LEFT COLUMN: PERSONAL, PARENT, CONTACT, ADDRESS -->
                <div class="grid-col-left">
                    
                    <!-- PERSONAL INFORMATION -->
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-user-circle"></i>
                                <h3>Personal Information</h3>
                            </div>
                            <button class="btn-card-edit" type="button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </div>
                        <div class="info-table">
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-user"></i> Full Name</div>
                                <div class="info-row-right"><?= htmlspecialchars($display_name) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-calendar"></i> Date of Birth</div>
                                <div class="info-row-right"><?= $dob_formatted ?> (<?= $age ?> years)</div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-venus-mars"></i> Gender</div>
                                <div class="info-row-right"><?= htmlspecialchars($gender) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-tint"></i> Blood Group</div>
                                <div class="info-row-right"><?= htmlspecialchars($blood_group) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-calendar-check"></i> Date of Joining</div>
                                <div class="info-row-right"><?= $doj_formatted ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- PARENT INFORMATION -->
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-users"></i>
                                <h3>Parent Information</h3>
                            </div>
                            <button class="btn-card-edit" type="button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </div>
                        <div class="info-table">
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-user-tie"></i> Father's Name</div>
                                <div class="info-row-right"><?= htmlspecialchars($father_name) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-female"></i> Mother's Name</div>
                                <div class="info-row-right"><?= htmlspecialchars($mother_name) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTACT INFORMATION -->
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-phone-alt"></i>
                                <h3>Contact Information</h3>
                            </div>
                            <button class="btn-card-edit" type="button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </div>
                        <div class="info-table">
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-mobile-alt"></i> Mobile Number</div>
                                <div class="info-row-right"><?= htmlspecialchars($phone) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-row-left"><i class="fas fa-phone"></i> Alternate Mobile Number</div>
                                <div class="info-row-right"><?= htmlspecialchars($alternate_phone) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- ADDRESS -->
                    <div class="koms-card mb-0">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-map-marker-alt"></i>
                                <h3>Address</h3>
                            </div>
                            <button class="btn-card-edit" type="button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </div>
                        <div class="info-table">
                            <div class="info-row">
                                <div class="info-row-left" style="width:10%;"><i class="fas fa-location-arrow"></i></div>
                                <div class="info-row-right" style="width:90%; line-height:1.5;"><?= htmlspecialchars($address) ?></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: ACTIONS, PROGRESS, BANNER -->
                <div class="grid-col-right">
                    
                    <!-- QUICK ACTIONS -->
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-bolt text-warning"></i>
                                <h3>Quick Actions</h3>
                            </div>
                        </div>
                        <div class="quick-actions-grid">
                            <a href="<?= APP_URL ?>/student/attendance.php" class="quick-action-tile">
                                <div class="action-tile-content">
                                    <div class="action-tile-icon tile-blue">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="action-tile-label">View Attendance</div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </a>

                            <a href="<?= APP_URL ?>/student/fees.php" class="quick-action-tile">
                                <div class="action-tile-content">
                                    <div class="action-tile-icon tile-purple">
                                        <i class="fas fa-indian-rupee-sign"></i>
                                    </div>
                                    <div class="action-tile-label">View Fees</div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </a>

                            <a href="<?= APP_URL ?>/student/achievements.php" class="quick-action-tile">
                                <div class="action-tile-content">
                                    <div class="action-tile-icon tile-gold">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="action-tile-label">View Achievements</div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </a>

                            <a href="<?= APP_URL ?>/student/grading.php" class="quick-action-tile">
                                <div class="action-tile-content">
                                    <div class="action-tile-icon tile-indigo">
                                        <i class="fas fa-certificate"></i>
                                    </div>
                                    <div class="action-tile-label">View Certificates</div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- MONTHLY PROGRESS -->
                    <div class="koms-card">
                        <div class="koms-card-header">
                            <div class="card-header-left">
                                <i class="fas fa-chart-line text-primary"></i>
                                <h3>Monthly Progress</h3>
                            </div>
                            <a href="<?= APP_URL ?>/student/attendance.php" class="btn-card-edit">View All</a>
                        </div>
                        
                        <div class="progress-list">
                            <!-- Classes Attended -->
                            <div class="progress-item">
                                <div class="progress-circle-icon icon-green">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="progress-item-body">
                                    <div class="progress-item-header">
                                        <span class="progress-item-title">Classes Attended</span>
                                        <span class="progress-item-value"><?= $att_attended ?> / <?= $att_total ?></span>
                                    </div>
                                    <div class="koms-progress-bar">
                                        <div class="koms-progress-fill fill-green" style="width: <?= $att_pct ?>%;"></div>
                                    </div>
                                </div>
                                <div class="progress-percentage"><?= $att_pct ?>%</div>
                            </div>

                            <!-- Fees Paid -->
                            <div class="progress-item">
                                <div class="progress-circle-icon icon-blue">
                                    <i class="fas fa-indian-rupee-sign"></i>
                                </div>
                                <div class="progress-item-body">
                                    <div class="progress-item-header">
                                        <span class="progress-item-title">Fees Paid</span>
                                        <span class="progress-item-value">₹ 2,000 / ₹ 3,000</span>
                                    </div>
                                    <div class="koms-progress-bar">
                                        <div class="koms-progress-fill fill-blue" style="width: 67%;"></div>
                                    </div>
                                </div>
                                <div class="progress-percentage">67%</div>
                            </div>

                            <!-- Achievements -->
                            <div class="progress-item">
                                <div class="progress-circle-icon icon-gold">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="progress-item-body">
                                    <div class="progress-item-header">
                                        <span class="progress-item-title">Achievements</span>
                                        <span class="progress-item-value">2</span>
                                    </div>
                                    <div class="koms-progress-bar">
                                        <div class="koms-progress-fill fill-gold" style="width: 100%;"></div>
                                    </div>
                                </div>
                                <div class="progress-percentage">100%</div>
                            </div>

                            <!-- Certificates -->
                            <div class="progress-item">
                                <div class="progress-circle-icon icon-purple" style="background:#f3e8ff; color:#7e22ce;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="progress-item-body">
                                    <div class="progress-item-header">
                                        <span class="progress-item-title">Certificates</span>
                                        <span class="progress-item-value">1</span>
                                    </div>
                                    <div class="koms-progress-bar">
                                        <div class="koms-progress-fill fill-purple" style="width: 50%;"></div>
                                    </div>
                                </div>
                                <div class="progress-percentage">50%</div>
                            </div>
                        </div>
                    </div>

                    <!-- INSPIRATIONAL CHAMPION BANNER -->
                    <div class="inspirational-banner">
                        <div class="inspirational-text">
                            <h4>Small Steps<br>Build</h4>
                            <h3>Big Champions</h3>
                        </div>
                        <div class="banner-red-streak"></div>
                        <div class="inspirational-silhouette"></div>
                    </div>

                </div>

            </div>

        </main>

    </div>

    <!-- EDIT PROFILE MODAL -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form method="POST">
                    <input type="hidden" name="save_student_profile" value="1">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editProfileModalLabel">
                            <i class="fas fa-user-edit text-primary me-2"></i>Edit Student Profile
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($father_name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($mother_name) ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Mobile Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Alternate Mobile</label>
                                <input type="text" name="alternate_phone" class="form-control" value="<?= htmlspecialchars($alternate_phone) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control" value="<?= htmlspecialchars($blood_group) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($address) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
