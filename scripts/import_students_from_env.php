<?php
/**
 * KOMS one-time student data importer.
 *
 * Reads KOMS_STUDENT_IMPORT_JSON from the runtime environment so personal
 * student data never has to be committed to the public Git repository.
 *
 * No default/demo passwords are created. New students receive a randomly
 * generated, discarded password hash and must have their real password set
 * through the KOMS account workflow.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raw = getenv('KOMS_STUDENT_IMPORT_JSON') ?: '';
if (trim($raw) === '') {
    echo "Student import: no import payload configured; skipping.\n";
    exit(0);
}

function ensure_column(PDO $pdo, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = ?");
    $stmt->execute([$column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN {$definition}");
    }
}

try {
    $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) {
        throw new RuntimeException('Import payload must be a JSON array.');
    }

    $dbName = getenv('DB_NAME') ?: 'koms';
    $dbHost = getenv('DB_HOST') ?: '';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPassword = getenv('DB_PASSWORD') ?: '';
    $dbPort = (int)(getenv('DB_PORT') ?: 3306);

    $databaseUrl = getenv('DATABASE_URL') ?: '';
    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if (is_array($parts)) {
            $dbHost = $parts['host'] ?? $dbHost;
            $dbUser = isset($parts['user']) ? rawurldecode($parts['user']) : $dbUser;
            $dbPassword = isset($parts['pass']) ? rawurldecode($parts['pass']) : $dbPassword;
            $dbPort = isset($parts['port']) ? (int)$parts['port'] : $dbPort;
            if (!empty($parts['path'])) {
                $dbName = ltrim($parts['path'], '/');
            }
        }
    }

    if ($dbHost === '') {
        $dbHost = '127.0.0.1';
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS koms_system_flags (flag_name VARCHAR(100) PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $flag = $pdo->query("SELECT COUNT(*) FROM koms_system_flags WHERE flag_name='excel_students_imported_v1'")->fetchColumn();
    if ((int)$flag === 1) {
        echo "Student import: already completed; skipping.\n";
        exit(0);
    }

    // MariaDB versions differ in support for ALTER TABLE ... ADD COLUMN IF NOT EXISTS.
    ensure_column($pdo, 'blood_group', 'blood_group VARCHAR(30) NULL AFTER gender');
    ensure_column($pdo, 'father_name', 'father_name VARCHAR(150) NULL AFTER blood_group');
    ensure_column($pdo, 'mother_name', 'mother_name VARCHAR(150) NULL AFTER father_name');
    ensure_column($pdo, 'alternate_phone', 'alternate_phone VARCHAR(20) NULL AFTER phone');
    ensure_column($pdo, 'date_of_joining', 'date_of_joining DATE NULL AFTER alternate_phone');
    ensure_column($pdo, 'must_change_password', 'must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status');

    $select = $pdo->prepare("SELECT id FROM users WHERE email=? OR member_id=? LIMIT 1");
    $insert = $pdo->prepare("INSERT INTO users
        (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
        VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)");
    $update = $pdo->prepare("UPDATE users SET
        first_name=?, last_name=?, email=?, dob=?, gender=?, blood_group=?, father_name=?, mother_name=?, phone=?, alternate_phone=?, address=?, date_of_joining=?, status='active'
        WHERE id=?");

    $pdo->beginTransaction();
    $count = 0;

    foreach ($payload as $student) {
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
            throw new RuntimeException('A student record is missing member_id, first_name, email, or dob.');
        }

        $select->execute([$email, $memberId]);
        $existingId = $select->fetchColumn();

        if ($existingId) {
            $update->execute([$firstName, $lastName, $email, $dob, $gender, $bloodGroup, $fatherName, $motherName, $phone, $alternatePhone, $address, $joining, (int)$existingId]);
        } else {
            // Generate and immediately discard a random password. The account
            // cannot be used until an administrator assigns a real password.
            $unusableHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $insert->execute([$memberId, $firstName, $lastName, $email, $unusableHash, $dob, $gender, $bloodGroup, $fatherName, $motherName, $phone, $alternatePhone, $address, $joining]);
        }

        $count++;
    }

    $flagStmt = $pdo->prepare("INSERT IGNORE INTO koms_system_flags(flag_name) VALUES (?)");
    $flagStmt->execute(['excel_students_imported_v1']);

    $pdo->commit();
    echo "Student import: {$count} spreadsheet records processed successfully.\n";
    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('KOMS student import failed: ' . $e->getMessage());
    fwrite(STDERR, "Student import failed: {$e->getMessage()}\n");
    exit(1);
}
