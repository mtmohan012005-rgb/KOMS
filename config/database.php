<?php
// database.php - KOMS MySQL connection
require_once __DIR__ . '/config.php';

/*
 * KOMS uses the database named `koms`.
 * We intentionally do NOT read DATABASE_URL / MYSQL_URL here because a
 * platform-provided URL can point to a different default database.
 */
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
$db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$db_name = getenv('DB_NAME') ?: 'koms';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 8,
];

// Aiven/remote MySQL connection.
if ($db_host !== 'localhost' && $db_host !== '127.0.0.1') {
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    error_log('KOMS database connection failed: ' . $e->getMessage());

    // Never silently switch to another database when running setup.
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'setup.php') {
        return;
    }

    http_response_code(503);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>KOMS Database Unavailable</title></head><body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#080808;color:#fff;font-family:Arial,sans-serif">';
    echo '<div style="width:min(92%,620px);padding:28px;border:1px solid #c61a1a;border-radius:16px;background:#111;text-align:center;box-shadow:0 18px 50px rgba(0,0,0,.55)">';
    echo '<h2 style="margin:0 0 12px;color:#ffcc00">Database Connection Required</h2>';
    echo '<p style="margin:0;color:#bbb;line-height:1.6">KOMS could not connect to the <strong style="color:#fff">' . htmlspecialchars($db_name) . '</strong> MySQL database. Please verify the Render/Aiven database credentials.</p>';
    echo '</div></body></html>';
    exit;
}

function ensure_user_column(PDO $pdo, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name=?");
    $stmt->execute([$column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN {$definition}");
    }
}

function ensure_security_tables(PDO $pdo): void {
    try {
        ensure_user_column($pdo, 'must_change_password', 'must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
        ensure_user_column($pdo, 'password_change_count', 'password_change_count INT NOT NULL DEFAULT 0 AFTER must_change_password');
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            dojo_id INT NULL,
            reason TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            master_notes TEXT NULL,
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        error_log('KOMS ensure_security_tables error: ' . $e->getMessage());
    }
}

ensure_security_tables($pdo);

/**
 * Import the user-provided student spreadsheet through a Render secret.
 * The JSON payload is never stored in the public Git repository.
 */
function import_runtime_students(PDO $pdo): void {
    $raw = getenv('KOMS_STUDENT_IMPORT_JSON') ?: '';
    if (trim($raw) === '') {
        return;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS koms_system_flags (flag_name VARCHAR(100) PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $already = $pdo->query("SELECT COUNT(*) FROM koms_system_flags WHERE flag_name='excel_students_imported_v1'")->fetchColumn();
        if ((int)$already === 1) {
            return;
        }

        $students = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($students)) {
            throw new RuntimeException('Student import payload is not an array.');
        }

        ensure_user_column($pdo, 'blood_group', 'blood_group VARCHAR(30) NULL AFTER gender');
        ensure_user_column($pdo, 'father_name', 'father_name VARCHAR(150) NULL AFTER blood_group');
        ensure_user_column($pdo, 'mother_name', 'mother_name VARCHAR(150) NULL AFTER father_name');
        ensure_user_column($pdo, 'alternate_phone', 'alternate_phone VARCHAR(20) NULL AFTER phone');
        ensure_user_column($pdo, 'date_of_joining', 'date_of_joining DATE NULL AFTER alternate_phone');
        ensure_user_column($pdo, 'must_change_password', 'must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status');

        $select = $pdo->prepare("SELECT id FROM users WHERE email=? OR member_id=? LIMIT 1");
        $insert = $pdo->prepare("INSERT INTO users
            (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
            VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)");
        $update = $pdo->prepare("UPDATE users SET
            first_name=?, last_name=?, email=?, dob=?, gender=?, blood_group=?, father_name=?, mother_name=?, phone=?, alternate_phone=?, address=?, date_of_joining=?, status='active'
            WHERE id=?");

        $pdo->beginTransaction();
        $processed = 0;

        foreach ($students as $student) {
            if (!is_array($student)) {
                continue;
            }

            $memberId = trim((string)($student['member_id'] ?? ''));
            $firstName = trim((string)($student['first_name'] ?? ''));
            $lastName = trim((string)($student['last_name'] ?? ''));
            $email = trim((string)($student['email'] ?? ''));
            $dob = trim((string)($student['dob'] ?? '')) ?: null;
            $gender = strtolower(trim((string)($student['gender'] ?? '')));
            $gender = in_array($gender, ['male', 'female', 'other'], true) ? $gender : null;
            $bloodGroup = trim((string)($student['blood_group'] ?? '')) ?: null;
            $fatherName = trim((string)($student['father_name'] ?? '')) ?: null;
            $motherName = trim((string)($student['mother_name'] ?? '')) ?: null;
            $phone = trim((string)($student['phone'] ?? '')) ?: null;
            $alternatePhone = trim((string)($student['alternate_phone'] ?? '')) ?: null;
            $address = trim((string)($student['address'] ?? '')) ?: null;
            $joining = trim((string)($student['date_of_joining'] ?? '')) ?: null;

            if ($memberId === '' || $firstName === '' || $email === '' || $dob === null) {
                throw new RuntimeException('Student record missing member_id, first_name, email, or dob.');
            }

            $select->execute([$email, $memberId]);
            $existingId = $select->fetchColumn();

            if ($existingId) {
                $update->execute([$firstName, $lastName, $email, $dob, $gender, $bloodGroup, $fatherName, $motherName, $phone, $alternatePhone, $address, $joining, (int)$existingId]);
            } else {
                // Generate and immediately discard a random password. The account
                // cannot be used until an administrator sets a real password.
                $unusableHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $insert->execute([$memberId, $firstName, $lastName, $email, $unusableHash, $dob, $gender, $bloodGroup, $fatherName, $motherName, $phone, $alternatePhone, $address, $joining]);
            }

            $processed++;
        }

        $flag = $pdo->prepare("INSERT IGNORE INTO koms_system_flags(flag_name) VALUES (?)");
        $flag->execute(['excel_students_imported_v1']);
        $pdo->commit();

        error_log("KOMS student import completed: {$processed} spreadsheet records processed.");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('KOMS runtime student import failed: ' . $e->getMessage());
    }
}

import_runtime_students($pdo);
?>