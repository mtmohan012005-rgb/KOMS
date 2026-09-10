<?php
// seed_students.php - Seeds and verifies 13 student accounts
require_once __DIR__ . '/../config/database.php';

echo "=== Seeding 13 KOMS Student Accounts ===\n";

$students = [
    [
        'name' => 'Şai Rohan L',
        'first_name' => 'Sai',
        'last_name' => 'Rohan L',
        'member_id' => 'sairohan2012.koms',
        'email' => 'sairohan2012@koms.local',
        'password' => '20.10.2012',
        'dob' => '2012-10-20',
        'gender' => 'male',
        'blood_group' => 'A-ve',
        'father_name' => 'Lingadhurai S.',
        'mother_name' => 'Patturani L',
        'phone' => '8939319656',
        'alternate_phone' => '9841882666',
        'address' => 'J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai-63',
        'date_of_joining' => '2026-08-01'
    ],
    [
        'name' => 'D. Guhan',
        'first_name' => 'Guhan',
        'last_name' => 'D',
        'member_id' => 'dguhan2015.koms',
        'email' => 'dguhan2015@koms.local',
        'password' => '25.09.2015',
        'dob' => '2015-09-25',
        'gender' => 'male',
        'blood_group' => 'A+',
        'father_name' => 'Lingadhurai S.',
        'mother_name' => 'Pattusari L.',
        'phone' => '8939319656',
        'alternate_phone' => '9841882666',
        'address' => 'J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai',
        'date_of_joining' => '2026-08-02'
    ],
    [
        'name' => 'Harshini.S',
        'first_name' => 'Harshini',
        'last_name' => 'S',
        'member_id' => 'harshini2012.koms',
        'email' => 'harshini2012@koms.local',
        'password' => '31.07.2012',
        'dob' => '2012-07-31',
        'gender' => 'female',
        'blood_group' => 'O+',
        'father_name' => 'Sathish Kurman D.',
        'mother_name' => 'Jayachithra.S',
        'phone' => '9994318107',
        'alternate_phone' => '9551581505',
        'address' => '4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048',
        'date_of_joining' => '2026-07-31'
    ],
    [
        'name' => 'S. Varshini',
        'first_name' => 'Varshini',
        'last_name' => 'S',
        'member_id' => 'svarshini2012.koms',
        'email' => 'svarshini2012@koms.local',
        'password' => '30.10.2012',
        'dob' => '2012-10-30',
        'gender' => 'female',
        'blood_group' => 'O+',
        'father_name' => 'D. Sathish Kumar',
        'mother_name' => 'S. Jaya Chithra',
        'phone' => '9994318107',
        'alternate_phone' => '9551581505',
        'address' => '4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048',
        'date_of_joining' => '2026-06-16'
    ],
    [
        'name' => 'G.P. Prathyuminan',
        'first_name' => 'Prathyuminan',
        'last_name' => 'G P',
        'member_id' => 'gpprathyuminan2020.koms',
        'email' => 'gpprathyuminan2020@koms.local',
        'password' => '25.09.2020',
        'dob' => '2020-09-25',
        'gender' => 'male',
        'blood_group' => 'B+',
        'father_name' => 'R. Prabu',
        'mother_name' => 'N. Gayathri',
        'phone' => '9952978876',
        'alternate_phone' => '9677283219',
        'address' => 'No. 36/24A, CTO Colony 2nd Street, Lakshmipuram, West Tambaram, Chennai-600045, Chengalpattu District',
        'date_of_joining' => '2026-05-30'
    ],
    [
        'name' => 'R. Harshitha',
        'first_name' => 'Harshitha',
        'last_name' => 'R',
        'member_id' => 'rharshitha2016.koms',
        'email' => 'rharshitha2016@koms.local',
        'password' => '04.08.2016',
        'dob' => '2016-08-04',
        'gender' => 'female',
        'blood_group' => 'O',
        'father_name' => 'R. Rewikanth',
        'mother_name' => 'M. Shanthi Charles Mary',
        'phone' => '9790897178',
        'alternate_phone' => '9840591792',
        'address' => 'No. 2, Arivu Street, MKB Nagar, New Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-07-18'
    ],
    [
        'name' => 'R. Darshitha',
        'first_name' => 'Darshitha',
        'last_name' => 'R',
        'member_id' => 'rdarshitha2016.koms',
        'email' => 'rdarshitha2016@koms.local',
        'password' => '04.08.2016',
        'dob' => '2016-08-04',
        'gender' => 'female',
        'blood_group' => 'O+',
        'father_name' => 'R. Ravikanth',
        'mother_name' => 'M. Shanthi Charles Mary',
        'phone' => '9790897178',
        'alternate_phone' => '9840591792',
        'address' => 'No. 2, Arivu Street, M.K.B. Nagar, New Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-07-18'
    ],
    [
        'name' => 'M.P. Niranjana Sri',
        'first_name' => 'Niranjana Sri',
        'last_name' => 'M P',
        'member_id' => 'mpniranjanasri2019.koms',
        'email' => 'mpniranjanasri2019@koms.local',
        'password' => '30.10.2019',
        'dob' => '2019-10-30',
        'gender' => 'female',
        'blood_group' => 'B Negative',
        'father_name' => 'B. Mathan',
        'mother_name' => 'M. Praveena',
        'phone' => '9600103987',
        'alternate_phone' => '8056507676',
        'address' => 'No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-07-04'
    ],
    [
        'name' => 'M. Krish Charan',
        'first_name' => 'Krish Charan',
        'last_name' => 'M',
        'member_id' => 'mkrishcharan2017.koms',
        'email' => 'mkrishcharan2017@koms.local',
        'password' => '08.08.2017',
        'dob' => '2017-08-08',
        'gender' => 'male',
        'blood_group' => 'B Positive',
        'father_name' => 'B. Mathan',
        'mother_name' => 'M. Praveena',
        'phone' => '9600103987',
        'alternate_phone' => '8056507676',
        'address' => 'No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-07-04'
    ],
    [
        'name' => 'Thejasri A.',
        'first_name' => 'Thejasri',
        'last_name' => 'A',
        'member_id' => 'thejasri2019.koms',
        'email' => 'thejasri2019@koms.local',
        'password' => '02.05.2019',
        'dob' => '2019-05-02',
        'gender' => 'female',
        'blood_group' => 'A+',
        'father_name' => 'Ajith Kumar',
        'mother_name' => 'A. Usha',
        'phone' => '7845033821',
        'alternate_phone' => '9566748689',
        'address' => 'No. 2, Arivu Street, Mahakavi Bharathiyar Nagar, New Perungalathur',
        'date_of_joining' => '2026-07-04'
    ],
    [
        'name' => 'Karunesh M.',
        'first_name' => 'Karunesh',
        'last_name' => 'M',
        'member_id' => 'karunesh2014.koms',
        'email' => 'karunesh2014@koms.local',
        'password' => '20.08.2014',
        'dob' => '2014-08-20',
        'gender' => 'male',
        'blood_group' => 'O+',
        'father_name' => 'Mohan T.',
        'mother_name' => 'Komalavalli M.',
        'phone' => '9176223876',
        'alternate_phone' => '9380056410',
        'address' => '6B, GE Properties, Muthusamy Cross Street, New Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-08-01'
    ],
    [
        'name' => 'V. Pragatheeshwaran',
        'first_name' => 'Pragatheeshwaran',
        'last_name' => 'V',
        'member_id' => 'vpragatheeshwaran2016.koms',
        'email' => 'vpragatheeshwaran2016@koms.local',
        'password' => '25.02.2016',
        'dob' => '2016-02-25',
        'gender' => 'male',
        'blood_group' => 'A+',
        'father_name' => 'K. Vinothkumar',
        'mother_name' => 'K. Chitra',
        'phone' => '9677017473',
        'alternate_phone' => '9841748800',
        'address' => '3/24, Ranganagar, Nehru Street, Old Perungalathur, Chennai-600063',
        'date_of_joining' => '2026-08-01'
    ],
    [
        'name' => 'Advick A.',
        'first_name' => 'Advick',
        'last_name' => 'A',
        'member_id' => 'advick2016.koms',
        'email' => 'advick2016@koms.local',
        'password' => '02.07.2016',
        'dob' => '2016-07-02',
        'gender' => 'male',
        'blood_group' => 'A+',
        'father_name' => 'A.Arun',
        'mother_name' => 'Anitha',
        'phone' => '7418737343',
        'alternate_phone' => '8148783837',
        'address' => 'No. 6 (St), Dhinagar, Old Perungalathur',
        'date_of_joining' => '2026-08-01'
    ]
];

// Ensure columns exist on users
$columns = [
    'blood_group VARCHAR(30) NULL',
    'father_name VARCHAR(150) NULL',
    'mother_name VARCHAR(150) NULL',
    'alternate_phone VARCHAR(20) NULL',
    'date_of_joining DATE NULL',
    'must_change_password TINYINT(1) NOT NULL DEFAULT 0'
];
foreach ($columns as $colDef) {
    $colName = explode(' ', $colDef)[0];
    $check = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = '$colName'");
    if ((int)$check->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN $colDef");
    }
}

// Find active dojo
$dojoStmt = $pdo->query("SELECT id FROM dojos ORDER BY id ASC LIMIT 1");
$defaultDojoId = (int)$dojoStmt->fetchColumn();

// If no dojo exists, create default dojo and master
if (!$defaultDojoId) {
    $masterStmt = $pdo->query("SELECT id FROM users WHERE role IN ('master', 'super_admin') LIMIT 1");
    $masterId = (int)$masterStmt->fetchColumn();
    if (!$masterId) {
        $masterPass = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, status) VALUES ('master.koms', 'Master', 'Sensei', 'master@koms.com', '$masterPass', 'master', 'active')");
        $masterId = (int)$pdo->lastInsertId();
    }
    $pdo->exec("INSERT INTO dojos (name, master_id, location, status) VALUES ('Mass Dragon Dojo', $masterId, 'Perungalathur, Chennai', 'approved')");
    $defaultDojoId = (int)$pdo->lastInsertId();
}

$insertStmt = $pdo->prepare("
    INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
    VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)
    ON DUPLICATE KEY UPDATE
        member_id = VALUES(member_id),
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        password_hash = VALUES(password_hash),
        role = 'student',
        dob = VALUES(dob),
        gender = VALUES(gender),
        blood_group = VALUES(blood_group),
        father_name = VALUES(father_name),
        mother_name = VALUES(mother_name),
        phone = VALUES(phone),
        alternate_phone = VALUES(alternate_phone),
        address = VALUES(address),
        date_of_joining = VALUES(date_of_joining),
        status = 'active',
        must_change_password = 0
");

$membershipStmt = $pdo->prepare("
    INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at)
    VALUES (?, ?, 'approved', CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE status = 'approved'
");

foreach ($students as $s) {
    $hash = password_hash($s['password'], PASSWORD_DEFAULT);
    
    // Check if user exists by member_id or email
    $existCheck = $pdo->prepare("SELECT id FROM users WHERE member_id = ? OR email = ?");
    $existCheck->execute([$s['member_id'], $s['email']]);
    $existing = $existCheck->fetch();

    if ($existing) {
        $updateStmt = $pdo->prepare("
            UPDATE users SET
                member_id = ?, first_name = ?, last_name = ?, password_hash = ?,
                role = 'student', dob = ?, gender = ?, blood_group = ?, father_name = ?,
                mother_name = ?, phone = ?, alternate_phone = ?, address = ?,
                date_of_joining = ?, status = 'active', must_change_password = 0
            WHERE id = ?
        ");
        $updateStmt->execute([
            $s['member_id'], $s['first_name'], $s['last_name'], $hash,
            $s['dob'], $s['gender'], $s['blood_group'], $s['father_name'],
            $s['mother_name'], $s['phone'], $s['alternate_phone'], $s['address'],
            $s['date_of_joining'], $existing['id']
        ]);
        $userId = $existing['id'];
    } else {
        $insertStmt->execute([
            $s['member_id'], $s['first_name'], $s['last_name'], $s['email'], $hash,
            $s['dob'], $s['gender'], $s['blood_group'], $s['father_name'],
            $s['mother_name'], $s['phone'], $s['alternate_phone'], $s['address'],
            $s['date_of_joining']
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    // Link dojo membership
    $membershipStmt->execute([$userId, $defaultDojoId]);
    echo "[OK] Student: {$s['name']} | User ID: {$s['member_id']} | Pass: {$s['password']}\n";
}

echo "\nSeeding complete! All 13 accounts are active in database.\n";
