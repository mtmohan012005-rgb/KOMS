<?php
// setup.php - 1-Click Automated Database & System Installer for KOMS
require_once 'config/config.php';

$message = null;
$error = null;
$installed = false;

// Default MySQL configuration matching standard XAMPP / local environments
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'koms';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? 'koms');

    try {
        // Step 1: Connect to MySQL Server (without selecting db)
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Step 2: Create Database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // Step 3: Execute Schema
        $schema_file = __DIR__ . '/database/schema.sql';
        if (file_exists($schema_file)) {
            $sql = file_get_contents($schema_file);
            $pdo->exec($sql);
        }

        // Step 4: Execute Seed Data
        $seed_file = __DIR__ . '/database/seed.sql';
        if (file_exists($seed_file)) {
            $sql = file_get_contents($seed_file);
            $pdo->exec($sql);
        }

        // Step 5: Ensure uploads directory exists
        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0777, true);
        }

        // Step 6: Update config/database.php if custom credentials provided
        $db_config_file = __DIR__ . '/config/database.php';
        $db_code = "<?php\n"
            . "// database.php - Auto-generated connection configuration\n"
            . "require_once __DIR__ . '/config.php';\n\n"
            . "\$host = '$db_host';\n"
            . "\$db_name = '$db_name';\n"
            . "\$username = '$db_user';\n"
            . "\$password = '$db_pass';\n"
            . "\$charset = 'utf8mb4';\n\n"
            . "\$dsn = \"mysql:host=\$host;dbname=\$db_name;charset=\$charset\";\n"
            . "\$options = [\n"
            . "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n"
            . "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
            . "    PDO::ATTR_EMULATE_PREPARES   => false,\n"
            . "];\n\n"
            . "try {\n"
            . "    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);\n"
            . "} catch (\\PDOException \$e) {\n"
            . "    if (APP_ENV === 'development') {\n"
            . "        // Redirect to setup if database not initialized\n"
            . "        header('Location: ' . APP_URL . '/setup.php?error=' . urlencode(\$e->getMessage()));\n"
            . "        exit();\n"
            . "    } else {\n"
            . "        die('Database connection failed. Please run setup.php.');\n"
            . "    }\n"
            . "}\n"
            . "?>\n";
        file_put_contents($db_config_file, $db_code);

        $installed = true;
        $message = "Database initialized and seeded successfully! All tables, DPDP compliance fields, and demo accounts are ready.";
    } catch (Exception $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOMS - 1-Click System Setup & Bootstrapper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #030303;
            --gold: #f4bd17;
            --red: #e50914;
        }
        body {
            background-color: var(--dark-bg);
            color: #e5e7eb;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-card {
            background: rgba(18, 18, 18, 0.95);
            border: 1px solid rgba(244, 189, 23, 0.25);
            border-radius: 16px;
            box-shadow: 0 0 40px rgba(244, 189, 23, 0.15);
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
        }
        .gold-title {
            color: var(--gold);
            font-weight: 800;
            letter-spacing: 1px;
        }
        .btn-gold {
            background: linear-gradient(135deg, #f4bd17 0%, #d49f0a 100%);
            color: #000;
            font-weight: 700;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(244, 189, 23, 0.4);
            color: #000;
        }
        .form-control {
            background: #111;
            border: 1px solid #333;
            color: #fff;
        }
        .form-control:focus {
            background: #181818;
            border-color: var(--gold);
            color: #fff;
            box-shadow: 0 0 10px rgba(244, 189, 23, 0.3);
        }
        .demo-pill {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="setup-card mx-auto">
        <div class="text-center mb-4">
            <i class="fas fa-dragon fa-3x" style="color: var(--gold);"></i>
            <h2 class="gold-title mt-2 mb-1">MASS DRAGON DOJO</h2>
            <p class="text-secondary small text-uppercase tracking-wider">KOMS 1-Click Database & System Bootstrapper</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <strong>Success!</strong> <?= htmlspecialchars($message) ?>
                </div>
            </div>
            
            <div class="card bg-dark border-secondary p-3 mb-4">
                <h6 class="text-warning mb-2"><i class="fas fa-key me-2"></i>Seeded Demo Credentials:</h6>
                <div class="demo-pill"><strong>Grand Master:</strong> admin@gmail.com / password123</div>
                <div class="demo-pill"><strong>Master Sensei:</strong> master@gmail.com / password123</div>
                <div class="demo-pill"><strong>Senior Belt:</strong> senior@gmail.com / password123</div>
                <div class="demo-pill"><strong>Student:</strong> student@gmail.com / password123</div>
            </div>

            <div class="d-grid gap-2">
                <a href="index.html" class="btn btn-gold btn-lg"><i class="fas fa-play me-2"></i>Enter Mass Dragon Dojo (SPA)</a>
                <a href="index.php" class="btn btn-outline-light"><i class="fas fa-globe me-2"></i>Go to PHP Web Portal</a>
            </div>
        <?php else: ?>
            <?php if ($error || isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error ?: $_GET['error']) ?>
                </div>
            <?php endif; ?>

            <p class="text-muted small mb-4">
                Click below to initialize the MySQL database, configure DPDP Act 2023 compliance tables, Shorin Ryu syllabus, SCD Type 2 fee structures, and test accounts.
            </p>

            <form method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-secondary">MySQL Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($db_name) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary">MySQL Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary">MySQL Password</label>
                        <input type="password" name="db_pass" class="form-control" placeholder="(empty by default in XAMPP)">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-gold btn-lg">
                        <i class="fas fa-bolt me-2"></i>Run 1-Click Database Setup
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <a href="index.html" class="text-secondary small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Return to Main Application
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
