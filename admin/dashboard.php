<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Allow super_admin or grand_master
if (function_exists('require_role')) {
    require_role('super_admin');
}

$page_title = 'Grand Master Dashboard - KOMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for Interactive Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #0d1117; color: #1e293b; overflow-x: hidden; }

        /* Top Header */
        .top-header {
            background-color: #080c10;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-crest {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1.5px solid #f59e0b;
            background: #1e1e1e url('../assets/images/shorin_ryu_crest.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            font-size: 16px;
        }
        .brand-text h1 {
            color: #f59e0b;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .brand-text span {
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            display: block;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .notif-btn {
            position: relative;
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 18px;
            cursor: pointer;
        }
        .notif-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-widget {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #f59e0b;
            background: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }
        .profile-info .profile-name {
            color: #f8fafc;
            font-size: 13px;
            font-weight: 700;
        }
        .profile-info .profile-role {
            color: #94a3b8;
            font-size: 11px;
        }

        /* Layout Container */
        .app-container {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        /* Left Sidebar */
        .sidebar {
            width: 230px;
            background: #080c10;
            border-right: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            padding: 16px 0;
        }
        .nav-list {
            list-style: none;
        }
        .nav-item {
            margin-bottom: 2px;
            padding: 0 10px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
        }
        .nav-item.active .nav-link {
            background: #dc2626;
            color: #ffffff;
            font-weight: 600;
        }
        .nav-link i {
            width: 18px;
            font-size: 13px;
            text-align: center;
        }
        .nav-badge {
            margin-left: auto;
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 999px;
        }
        .nav-item.active .nav-badge {
            background: #fff;
            color: #dc2626;
        }
        .sidebar-kanji-footer {
            padding: 20px 16px;
            text-align: center;
            opacity: 0.2;
            color: #fff;
            font-size: 28px;
            letter-spacing: 4px;
            font-family: serif;
        }

        /* Main Workspace */
        .workspace {
            flex: 1;
            background: #f1f5f9;
            padding: 24px;
            overflow-y: auto;
        }

        /* Hero Banner */
        .welcome-banner {
            border-radius: 12px;
            background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.75)), url('../assets/images/sunset_martial_hero.jpg') center/cover no-repeat, #1e293b;
            padding: 24px 32px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
        }
        .banner-left h2 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .banner-left p {
            color: #cbd5e1;
            font-size: 13px;
        }
        .banner-right {
            text-align: right;
            max-width: 320px;
            font-style: italic;
            font-size: 12.5px;
            color: #f8fafc;
            border-left: 2px solid #dc2626;
            padding-left: 14px;
        }
        .banner-right span {
            display: block;
            margin-top: 4px;
            color: #f59e0b;
            font-size: 11px;
            font-style: normal;
            font-weight: 600;
        }

        /* 5 Stat Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            border-radius: 10px;
            padding: 16px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .kpi-card.blue { background: #1e40af; }
        .kpi-card.green { background: #15803d; }
        .kpi-card.purple { background: #6b21a8; }
        .kpi-card.orange { background: #c2410c; }
        .kpi-card.red { background: #b91c1c; }

        .kpi-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .kpi-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .kpi-label {
            font-size: 11.5px;
            font-weight: 500;
            opacity: 0.9;
        }
        .kpi-value {
            font-size: 26px;
            font-weight: 800;
            margin: 6px 0 2px;
            line-height: 1;
        }
        .kpi-note {
            font-size: 10px;
            opacity: 0.8;
        }

        /* 3-Column Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 340px 280px;
            gap: 20px;
        }

        /* Section Cards */
        .dashboard-panel {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .panel-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .panel-header .view-link {
            font-size: 11.5px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        .panel-header .view-link:hover { text-decoration: underline; }

        /* Tables */
        .koms-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .koms-table th {
            text-align: left;
            padding: 8px 10px;
            color: #64748b;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }
        .koms-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .badge-status {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-status.approved { background: #dcfce7; color: #15803d; }
        .badge-status.pending { background: #fef3c7; color: #b45309; }
        .badge-status.rejected { background: #fee2e2; color: #b91c1c; }

        .btn-table-action {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-approve { background: #16a34a; color: #fff; margin-right: 4px; }
        .btn-reject { background: #dc2626; color: #fff; }

        /* Recent Activity List */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e0f2fe;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .activity-icon-wrap.green { background: #dcfce7; color: #16a34a; }
        .activity-icon-wrap.purple { background: #f3e8ff; color: #9333ea; }
        .activity-icon-wrap.orange { background: #ffedd5; color: #ea580c; }
        .activity-details .act-title {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
        }
        .activity-details .act-desc {
            font-size: 11px;
            color: #64748b;
        }
        .activity-details .act-time {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 1px;
        }

        /* Quick Actions list */
        .quick-action-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            color: #334155;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .quick-action-link:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .quick-action-link i.fa-chevron-right {
            font-size: 10px;
            color: #94a3b8;
        }

        /* Common UI Control */
        .control-badge-top {
            background: #dcfce7;
            color: #15803d;
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
        }
        .control-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .control-row:last-child { border-bottom: none; }
        .control-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-weight: 500;
        }
        .control-left i { color: #16a34a; font-size: 13px; }
        .control-edit-link {
            color: #2563eb;
            font-size: 11px;
            text-decoration: none;
            font-weight: 600;
        }
        .control-edit-link:hover { text-decoration: underline; }
        .btn-manage-common {
            width: 100%;
            background: #1e293b;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            margin-top: 14px;
            transition: background 0.2s;
        }
        .btn-manage-common:hover { background: #0f172a; }

        /* Mini Charts Row */
        .mini-charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }
        .mini-chart-card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 14px;
        }
        .mini-chart-card h4 {
            font-size: 12px;
            color: #475569;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .mini-chart-card h4 span { font-size: 10px; color: #94a3b8; font-weight: normal; }

        /* Footer */
        .bottom-footer {
            background: #080c10;
            color: #94a3b8;
            font-size: 11px;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .bottom-footer span.motto { color: #f59e0b; font-weight: 600; letter-spacing: 0.5px; }

        @media (max-width: 1200px) {
            .content-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .mini-charts-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <header class="top-header">
        <div class="header-brand">
            <div class="brand-crest"><i class="fas fa-dragon"></i></div>
            <div class="brand-text">
                <h1>KOMS</h1>
                <span>KARATE ORGANIZATION MANAGEMENT SYSTEM</span>
            </div>
        </div>
        <div class="header-right">
            <button class="notif-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notif-badge">3</span>
            </button>
            <div class="profile-widget">
                <div class="profile-avatar">GM</div>
                <div class="profile-info">
                    <div class="profile-name">Grand Master</div>
                    <div class="profile-role">Super Admin</div>
                </div>
            </div>
        </div>
    </header>

    <div class="app-container">
        <!-- LEFT SIDEBAR (14 items matching Image 2) -->
        <aside class="sidebar">
            <ul class="nav-list">
                <li class="nav-item active"><a href="#" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li class="nav-item"><a href="dojos.php" class="nav-link"><i class="fas fa-synagogue"></i> Dojo Management</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-user-clock"></i> Master Requests <span class="nav-badge">2</span></a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-user-ninja"></i> Masters</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Students</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-wallet"></i> Fees</a></li>
                <li class="nav-item"><a href="tournaments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-images"></i> Gallery</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-ribbon"></i> Training Levels</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-layer-group"></i> Common UI Content</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-book-open"></i> Tradition Content</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
            <div class="sidebar-kanji-footer">空手道</div>
        </aside>

        <!-- MAIN WORKSPACE -->
        <main class="workspace">
            <!-- HERO BANNER -->
            <div class="welcome-banner">
                <div class="banner-left">
                    <h2>Welcome Back, Grand Master</h2>
                    <p>Manage your organization, dojos and members with complete control.</p>
                </div>
                <div class="banner-right">
                    “Discipline Builds Character and Character Builds a Better World.”
                    <span>— KOMS</span>
                </div>
            </div>

            <!-- 5 STAT CARDS -->
            <div class="stats-row">
                <div class="kpi-card blue">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fas fa-synagogue"></i></div>
                        <div>
                            <div class="kpi-label">Total Dojos</div>
                            <div class="kpi-value" id="kpiDojos">5</div>
                        </div>
                    </div>
                    <div class="kpi-note">4 Approved | 1 Pending</div>
                </div>

                <div class="kpi-card green">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fas fa-user-ninja"></i></div>
                        <div>
                            <div class="kpi-label">Total Masters</div>
                            <div class="kpi-value" id="kpiMasters">8</div>
                        </div>
                    </div>
                    <div class="kpi-note">7 Approved | 1 Pending</div>
                </div>

                <div class="kpi-card purple">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="kpi-label">Total Students</div>
                            <div class="kpi-value" id="kpiStudents">236</div>
                        </div>
                    </div>
                    <div class="kpi-note">218 Active | 18 Inactive</div>
                </div>

                <div class="kpi-card orange">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="kpi-label">Upcoming Events</div>
                            <div class="kpi-value">3</div>
                        </div>
                    </div>
                    <div class="kpi-note">Next Event: Karate Championship (25 Oct 2026)</div>
                </div>

                <div class="kpi-card red">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div>
                            <div class="kpi-label">Pending Fees</div>
                            <div class="kpi-value">₹ 1,48,500</div>
                        </div>
                    </div>
                    <div class="kpi-note">From 32 Students</div>
                </div>
            </div>

            <!-- 3-COLUMN CONTENT GRID -->
            <div class="content-grid">
                <!-- COLUMN 1: Dojo Overview & Master Approval Requests & Mini Charts -->
                <div class="col-main">
                    <!-- DOJO OVERVIEW TABLE -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Dojo Overview</h3>
                            <a href="dojos.php" class="view-link">View All Dojos →</a>
                        </div>
                        <table class="koms-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Dojo Name</th>
                                    <th>Location</th>
                                    <th>Master</th>
                                    <th>Status</th>
                                    <th>Students</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><strong><i class="fas fa-synagogue me-1 text-primary"></i> Ranga Nagar Dojo</strong></td>
                                    <td>Ranga Nagar, Chennai</td>
                                    <td>S. Senthil Kumar</td>
                                    <td><span class="badge-status approved">Approved</span></td>
                                    <td>62</td>
                                    <td><i class="fas fa-chevron-right text-muted"></i></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong><i class="fas fa-synagogue me-1 text-primary"></i> Old Perungalathur Dojo</strong></td>
                                    <td>Old Perungalathur, Chennai</td>
                                    <td>R. Prakash</td>
                                    <td><span class="badge-status approved">Approved</span></td>
                                    <td>48</td>
                                    <td><i class="fas fa-chevron-right text-muted"></i></td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong><i class="fas fa-synagogue me-1 text-primary"></i> Tambaram Dojo</strong></td>
                                    <td>Tambaram, Chennai</td>
                                    <td>V. Arul</td>
                                    <td><span class="badge-status approved">Approved</span></td>
                                    <td>45</td>
                                    <td><i class="fas fa-chevron-right text-muted"></i></td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td><strong><i class="fas fa-synagogue me-1 text-primary"></i> Velachery Dojo</strong></td>
                                    <td>Velachery, Chennai</td>
                                    <td>K. Mani</td>
                                    <td><span class="badge-status approved">Approved</span></td>
                                    <td>36</td>
                                    <td><i class="fas fa-chevron-right text-muted"></i></td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td><strong><i class="fas fa-synagogue me-1 text-primary"></i> Anna Nagar Dojo</strong></td>
                                    <td>Anna Nagar, Chennai</td>
                                    <td>M. Rajesh</td>
                                    <td><span class="badge-status pending">Pending</span></td>
                                    <td>0</td>
                                    <td><i class="fas fa-chevron-right text-muted"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MASTER APPROVAL REQUESTS TABLE -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Master Approval Requests</h3>
                            <a href="#" class="view-link">View All →</a>
                        </div>
                        <table class="koms-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Dojo Name</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><strong>R. Prakash</strong></td>
                                    <td>Old Perungalathur Dojo</td>
                                    <td>18 Oct 2026</td>
                                    <td><span class="badge-status pending">Pending</span></td>
                                    <td>
                                        <button class="btn-table-action btn-approve" onclick="alert('Master R. Prakash approved!')">Approve</button>
                                        <button class="btn-table-action btn-reject" onclick="alert('Master request rejected')">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong>S. Kumar</strong></td>
                                    <td>Tambaram Dojo</td>
                                    <td>17 Oct 2026</td>
                                    <td><span class="badge-status pending">Pending</span></td>
                                    <td>
                                        <button class="btn-table-action btn-approve" onclick="alert('Master S. Kumar approved!')">Approve</button>
                                        <button class="btn-table-action btn-reject" onclick="alert('Master request rejected')">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong>V. Rajesh</strong></td>
                                    <td>Anna Nagar Dojo</td>
                                    <td>15 Oct 2026</td>
                                    <td><span class="badge-status pending">Pending</span></td>
                                    <td>
                                        <button class="btn-table-action btn-approve" onclick="alert('Master V. Rajesh approved!')">Approve</button>
                                        <button class="btn-table-action btn-reject" onclick="alert('Master request rejected')">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 3 MINI CHARTS ROW -->
                    <div class="mini-charts-row">
                        <div class="mini-chart-card">
                            <h4>Student Growth <span>Last 6 Months</span></h4>
                            <canvas id="studentGrowthChart" height="120"></canvas>
                        </div>
                        <div class="mini-chart-card" style="text-align: center;">
                            <h4>Fee Collection <span>This Month</span></h4>
                            <div style="font-size: 24px; font-weight: 800; color: #15803d; margin: 6px 0;">68%</div>
                            <div style="font-size: 11px; color: #64748b;">
                                <span style="color: #15803d; font-weight: 600;">● Collected ₹2,84,000</span><br>
                                <span style="color: #b91c1c; font-weight: 600;">● Pending ₹1,48,500</span>
                            </div>
                        </div>
                        <div class="mini-chart-card">
                            <h4>Attendance Overview <span>This Week</span></h4>
                            <canvas id="attendanceBarChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <!-- COLUMN 2: Dojo Status & Recent Activity -->
                <div class="col-mid">
                    <!-- DOJO STATUS DONUT -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Dojo Status</h3>
                        </div>
                        <div style="position: relative; height: 160px; display: flex; align-items: center; justify-content: center;">
                            <canvas id="dojoStatusChart"></canvas>
                            <div style="position: absolute; text-align: center; pointer-events: none;">
                                <div style="font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1;">5</div>
                                <div style="font-size: 10px; color: #64748b;">Total Dojos</div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px; font-size: 11px; color: #475569;">
                            <div><span style="color: #15803d; font-size: 14px;">●</span> Approved <strong>4</strong></div>
                            <div><span style="color: #b45309; font-size: 14px;">●</span> Pending <strong>1</strong></div>
                            <div><span style="color: #b91c1c; font-size: 14px;">●</span> Rejected <strong>0</strong></div>
                            <div><span style="color: #94a3b8; font-size: 14px;">●</span> Inactive <strong>0</strong></div>
                        </div>
                    </div>

                    <!-- RECENT ACTIVITY -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Recent Activity</h3>
                            <a href="../admin/audit.php" class="view-link">View All →</a>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon-wrap"><i class="fas fa-user-plus"></i></div>
                            <div class="activity-details">
                                <div class="act-title">New master registration request</div>
                                <div class="act-desc">R. Prakash - Old Perungalathur Dojo</div>
                                <div class="act-time">2 hours ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon-wrap green"><i class="fas fa-check-circle"></i></div>
                            <div class="activity-details">
                                <div class="act-title">Dojo approved</div>
                                <div class="act-desc">Velachery Dojo</div>
                                <div class="act-time">5 hours ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon-wrap purple"><i class="fas fa-user-graduate"></i></div>
                            <div class="activity-details">
                                <div class="act-title">New student joining request</div>
                                <div class="act-desc">Arun Kumar - Ranga Nagar Dojo</div>
                                <div class="act-time">6 hours ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon-wrap green"><i class="fas fa-rupee-sign"></i></div>
                            <div class="activity-details">
                                <div class="act-title">Fee payment received</div>
                                <div class="act-desc">₹ 5,000 - S. Karthik</div>
                                <div class="act-time">8 hours ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon-wrap orange"><i class="fas fa-calendar-plus"></i></div>
                            <div class="activity-details">
                                <div class="act-title">Event created</div>
                                <div class="act-desc">Karate Championship 2026</div>
                                <div class="act-time">12 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMN 3: Quick Actions & Common UI Control -->
                <div class="col-right">
                    <!-- QUICK ACTIONS -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-user-check me-2 text-primary"></i> Approve Master Requests</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="dojos.php" class="quick-action-link"><span><i class="fas fa-synagogue me-2 text-primary"></i> Manage Dojos</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-user-ninja me-2 text-primary"></i> View All Masters</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="users.php" class="quick-action-link"><span><i class="fas fa-users me-2 text-primary"></i> View All Students</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="tournaments.php" class="quick-action-link"><span><i class="fas fa-calendar-alt me-2 text-primary"></i> Manage Events</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-images me-2 text-primary"></i> Manage Gallery</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-sliders-h me-2 text-primary"></i> Control Common UI</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-book me-2 text-primary"></i> Manage Tradition Content</span> <i class="fas fa-chevron-right"></i></a>
                        <a href="#" class="quick-action-link"><span><i class="fas fa-ribbon me-2 text-primary"></i> Manage Training Levels</span> <i class="fas fa-chevron-right"></i></a>
                    </div>

                    <!-- COMMON UI CONTROL -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h3>Common UI Control</h3>
                            <span class="control-badge-top">Controlled by Grand Master</span>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Home Content</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('home')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Dojo Content</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('dojo')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Master Profiles</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('masters')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Tradition Content</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('tradition')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Events</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('events')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Gallery</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('gallery')">Edit</a>
                        </div>
                        <div class="control-row">
                            <div class="control-left"><i class="fas fa-check-circle"></i> Training Level Content</div>
                            <a href="#" class="control-edit-link" onclick="openContentModal('training_level')">Edit</a>
                        </div>

                        <button class="btn-manage-common" onclick="alert('Opening Grand Master Content Management...')">
                            <i class="fas fa-cog"></i> Manage Common UI Content
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- BOTTOM FOOTER -->
    <footer class="bottom-footer">
        <div>KOMS | Karate Organization Management System</div>
        <div class="motto">Tradition • Discipline • Excellence</div>
    </footer>

    <!-- Chart Scripts -->
    <script>
        // Dojo Status Donut
        const ctxStatus = document.getElementById('dojoStatusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected', 'Inactive'],
                datasets: [{
                    data: [4, 1, 0, 0],
                    backgroundColor: ['#16a34a', '#f59e0b', '#dc2626', '#94a3b8'],
                    borderWidth: 0,
                    cutout: '72%'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                maintainAspectRatio: false
            }
        });

        // Student Growth Line
        const ctxGrowth = document.getElementById('studentGrowthChart').getContext('2d');
        new Chart(ctxGrowth, {
            type: 'line',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    data: [50, 95, 130, 165, 200, 236],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } }
                }
            }
        });

        // Attendance Bar Chart
        const ctxAtt = document.getElementById('attendanceBarChart').getContext('2d');
        new Chart(ctxAtt, {
            type: 'bar',
            data: {
                labels: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
                datasets: [
                    { data: [180, 195, 210, 205, 190, 225, 230], backgroundColor: '#16a34a', borderRadius: 2 },
                    { data: [20, 15, 12, 18, 25, 10, 6], backgroundColor: '#dc2626', borderRadius: 2 }
                ]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } }
                }
            }
        });

        function openContentModal(section) {
            alert('Editing Grand Master Common UI Section: ' + section);
        }
    </script>
</body>
</html>
