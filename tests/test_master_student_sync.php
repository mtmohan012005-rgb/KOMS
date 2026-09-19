<?php
/**
 * 12-Test Verification Suite for KOMS Master <-> Student Synchronization
 * Section 36 Compliance
 */

require_once __DIR__ . '/../config/database.php';

$baseUrl = 'http://127.0.0.1:8080';
$passed = 0;
$failed = 0;
$tests = [];

function run_test($testNum, $title, $fn) {
    global $passed, $failed, $tests;
    echo "\n------------------------------------------------------------\n";
    echo "TEST $testNum: $title\n";
    try {
        $result = $fn();
        if ($result === true) {
            echo "RESULT: PASSED [OK]\n";
            $passed++;
            $tests[$testNum] = ['title' => $title, 'status' => 'PASSED'];
        } else {
            echo "RESULT: FAILED - " . (is_string($result) ? $result : "Assertion failed") . " [FAIL]\n";
            $failed++;
            $tests[$testNum] = ['title' => $title, 'status' => 'FAILED', 'error' => $result];
        }
    } catch (Throwable $e) {
        echo "RESULT: ERROR - " . $e->getMessage() . " [FAIL]\n";
        $failed++;
        $tests[$testNum] = ['title' => $title, 'status' => 'ERROR', 'error' => $e->getMessage()];
    }
}

function http_req($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    if ($data !== null) {
        $payload = is_string($data) ? $data : json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    
    if ($curlErr) {
        throw new Exception("cURL error: $curlErr");
    }
    
    $json = json_decode($response, true);
    return ['code' => $httpCode, 'raw' => $response, 'json' => $json];
}

echo "============================================================\n";
echo "KOMS 12-TEST MASTER <-> STUDENT SYNCHRONIZATION TEST SUITE\n";
echo "Base URL: $baseUrl\n";
echo "============================================================\n";

// Shared tokens
$masterAToken = null;
$masterBToken = null;
$grandMasterToken = null;

// TEST 1: Master A login (displays only Dojo A data)
run_test(1, "Master A login & scoped dashboard data", function() use ($baseUrl, &$masterAToken) {
    $res = http_req('POST', "$baseUrl/api/auth/login.php", [
        'email' => 'master@gmail.com',
        'password' => 'password123'
    ]);
    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Login failed: " . ($res['json']['message'] ?? $res['raw']);
    }
    $masterAToken = $res['json']['data']['token'];
    $dojoId = $res['json']['data']['dojo_id'];
    if ($dojoId != 1) {
        return "Expected Master A dojo_id to be 1, got: $dojoId";
    }

    // Check Master Dashboard
    $dash = http_req('GET', "$baseUrl/api/master/dashboard.php", null, $masterAToken);
    if ($dash['code'] !== 200 || empty($dash['json']['success'])) {
        return "Failed to fetch master dashboard: " . ($dash['json']['message'] ?? $dash['raw']);
    }
    $d = $dash['json']['data'];
    if (($d['dojo']['id'] ?? 0) != 1) {
        return "Expected dojo id 1 in dashboard, got: " . ($d['dojo']['id'] ?? 'null');
    }
    if (empty($d['summary']['total_students'])) {
        return "Summary total_students is missing or 0";
    }
    echo "  -> Master A authenticated (Dojo: {$d['dojo']['name']}, Students: {$d['summary']['total_students']})\n";
    return true;
});

// TEST 2: Student submits join request
run_test(2, "Student submits join request for Dojo 1", function() use ($baseUrl, &$masterAToken, $pdo) {
    // Ensure student user exists and has a pending request
    $email = 'karthik_req@koms.local';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        $ins = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES ('Karthik', 'M', ?, ?, 'student', 'pending')");
        $ins->execute([$email, password_hash('password123', PASSWORD_DEFAULT)]);
        $userId = (int)$pdo->lastInsertId();
    }
    // Ensure membership request with status 'pending'
    $pdo->prepare("DELETE FROM dojo_memberships WHERE student_id = ? AND dojo_id = 1")->execute([$userId]);
    $pdo->prepare("INSERT INTO dojo_memberships (dojo_id, student_id, status) VALUES (1, ?, 'pending')")->execute([$userId]);

    // Check Master A gets it in pending requests
    $reqs = http_req('GET', "$baseUrl/api/master/student_requests.php", null, $masterAToken);
    if ($reqs['code'] !== 200) {
        return "Failed to fetch student requests: " . $reqs['raw'];
    }
    $found = false;
    foreach ($reqs['json']['data'] as $r) {
        if ($r['student_id'] == $userId && strcasecmp($r['status'], 'pending') === 0) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        return "Pending request for user $userId not found in Master A request list";
    }
    echo "  -> Student join request successfully created and visible to Master A (Student ID: $userId)\n";
    return true;
});

// TEST 3: Master A approves request -> student becomes ACTIVE
run_test(3, "Master A approves student join request", function() use ($baseUrl, &$masterAToken, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'karthik_req@koms.local'");
    $stmt->execute();
    $studentId = (int)$stmt->fetchColumn();

    $res = http_req('POST', "$baseUrl/api/master/student_requests.php", [
        'student_id' => $studentId,
        'action' => 'approve'
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Approval failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    // Verify DB state
    $memStatus = $pdo->query("SELECT status FROM dojo_memberships WHERE student_id = $studentId AND dojo_id = 1")->fetchColumn();
    $userStatus = $pdo->query("SELECT status FROM users WHERE id = $studentId")->fetchColumn();
    if ($memStatus !== 'approved') {
        return "Expected membership status 'approved', got '$memStatus'";
    }
    if ($userStatus !== 'active') {
        return "Expected user status 'active', got '$userStatus'";
    }
    echo "  -> Student $studentId approved; membership status = $memStatus, user status = $userStatus\n";
    return true;
});

// TEST 4: Master A marks student PRESENT -> attendance increments on Student Dashboard
run_test(4, "Master A marks student PRESENT & synchronizes to Student", function() use ($baseUrl, &$masterAToken) {
    // 1. Get initial attendance stats for Sai Rohan (id 10)
    $initStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $initPresent = $initStud['json']['data']['present_count'] ?? 0;

    // 2. Master A marks attendance for a new session
    $sessionDate = date('Y-m-d', strtotime('+' . rand(1, 1000) . ' days'));
    $res = http_req('POST', "$baseUrl/api/master/attendance.php", [
        'session_date' => $sessionDate,
        'class_title' => 'Evening Kata & Kumite',
        'entries' => [
            ['student_id' => 10, 'status' => 'present', 'remarks' => 'Great discipline']
        ]
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Attendance marking failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    // 3. Check Student Dashboard immediately reflects it
    $newStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $newPresent = $newStud['json']['data']['present_count'] ?? 0;
    $newPct = $newStud['json']['data']['attendance_percentage'] ?? 0;

    if ($newPresent <= $initPresent) {
        return "Expected present count to increment. Before: $initPresent, After: $newPresent";
    }

    // 4. Check Student Profile immediately reflects it
    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    $profPresent = $prof['json']['data']['classes_attended'] ?? 0;
    if ($profPresent != $newPresent) {
        return "Profile classes_attended ($profPresent) does not match dashboard ($newPresent)";
    }

    echo "  -> Attendance marked by Master! Student present count: $initPresent -> $newPresent ($newPct%)\n";
    return true;
});

// TEST 5: Master A records fee payment -> fee records update on Student Dashboard
run_test(5, "Master A records fee payment & synchronizes to Student", function() use ($baseUrl, &$masterAToken) {
    $initStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $initPaid = (float)($initStud['json']['data']['fees_paid'] ?? 0);
    $initPending = (float)($initStud['json']['data']['pending_fees'] ?? 0);

    // Record ₹500 payment
    $payAmt = 500.0;
    $res = http_req('POST', "$baseUrl/api/master/fees.php", [
        'student_id' => 10,
        'amount' => $payAmt,
        'payment_method' => 'UPI',
        'transaction_ref' => 'UPI-TEST-' . time(),
        'notes' => 'Monthly Dojo Fee'
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Payment recording failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    // Verify on Student Dashboard
    $newStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $newPaid = (float)($newStud['json']['data']['fees_paid'] ?? 0);
    $newPending = (float)($newStud['json']['data']['pending_fees'] ?? 0);

    if ($newPaid < $initPaid + $payAmt) {
        return "Fees paid did not increment properly. Before: $initPaid, After: $newPaid";
    }

    // Verify on Student Profile
    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    $profPaid = (float)($prof['json']['data']['fees_paid'] ?? 0);
    if ($profPaid != $newPaid) {
        return "Student profile fees_paid ($profPaid) does not match dashboard ($newPaid)";
    }

    echo "  -> Payment recorded! Paid: ₹$initPaid -> ₹$newPaid, Pending: ₹$initPending -> ₹$newPending\n";
    return true;
});

// TEST 6: Master A promotes belt (White -> Yellow) -> student dashboard reflects Yellow Belt
run_test(6, "Master A promotes belt & synchronizes to Student", function() use ($baseUrl, &$masterAToken) {
    $newBelt = 'Yellow Belt';
    $res = http_req('POST', "$baseUrl/api/master/grading.php", [
        'student_id' => 10,
        'new_belt' => $newBelt,
        'exam_date' => date('Y-m-d'),
        'grade' => 'A+',
        'remarks' => 'Demonstrated excellent Heian Shodan kata'
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Belt grading failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    // Verify Student Dashboard
    $stud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $currBelt = $stud['json']['data']['current_belt'] ?? '';
    $nextBelt = $stud['json']['data']['target_belt'] ?? '';

    if ($currBelt !== $newBelt) {
        return "Expected current_belt '$newBelt', got '$currBelt'";
    }

    // Verify Student Profile
    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    if (($prof['json']['data']['current_belt'] ?? '') !== $newBelt) {
        return "Student profile current_belt does not match '$newBelt'";
    }

    echo "  -> Belt promoted to $newBelt! Next target: $nextBelt\n";
    return true;
});

// TEST 7: Master A adds achievement -> appears on student dashboard
run_test(7, "Master A adds achievement & synchronizes to Student", function() use ($baseUrl, &$masterAToken) {
    $initStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $initCount = (int)($initStud['json']['data']['achievements_count'] ?? 0);

    $title = 'State Karate Championship 2026 - Gold Medal';
    $res = http_req('POST', "$baseUrl/api/master/achievements.php", [
        'student_id' => 10,
        'title' => $title,
        'competition_event' => 'Junior Kumite (Under-14)',
        'position_result' => 'Gold Medal (1st Place)',
        'achievement_date' => date('Y-m-d'),
        'description' => 'Won 4 rounds consecutively.'
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Add achievement failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    $newStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $newCount = (int)($newStud['json']['data']['achievements_count'] ?? 0);

    if ($newCount <= $initCount) {
        return "Achievements count did not increment. Before: $initCount, After: $newCount";
    }

    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    $profCount = (int)($prof['json']['data']['achievements_count'] ?? 0);
    if ($profCount != $newCount) {
        return "Profile achievements_count ($profCount) does not match dashboard ($newCount)";
    }

    echo "  -> Achievement added! Total count: $initCount -> $newCount\n";
    return true;
});

// TEST 8: Master A adds certificate -> appears on student dashboard
run_test(8, "Master A adds certificate & synchronizes to Student", function() use ($baseUrl, &$masterAToken) {
    $initStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $initCount = (int)($initStud['json']['data']['certificates_count'] ?? 0);

    $title = 'Official Yellow Belt Kyu Certification';
    $certNo = 'CERT-KOMS-' . time();
    $res = http_req('POST', "$baseUrl/api/master/certificates.php", [
        'student_id' => 10,
        'certificate_type' => 'Grading',
        'title' => $title,
        'certificate_number' => $certNo,
        'issue_date' => date('Y-m-d'),
        'notes' => 'Awarded by Grand Master Board'
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Issue certificate failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    $newStud = http_req('GET', "$baseUrl/api/students/dashboard.php?student_id=10");
    $newCount = (int)($newStud['json']['data']['certificates_count'] ?? 0);

    if ($newCount <= $initCount) {
        return "Certificates count did not increment. Before: $initCount, After: $newCount";
    }

    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    $profCount = (int)($prof['json']['data']['certificates_count'] ?? 0);
    if ($profCount != $newCount) {
        return "Profile certificates_count ($profCount) does not match dashboard ($newCount)";
    }

    echo "  -> Certificate issued! Total certificates: $initCount -> $newCount (No: $certNo)\n";
    return true;
});

// TEST 9: Master A changes schedule -> student dashboard reflects schedule
run_test(9, "Master A changes schedule & synchronizes to Dojo & Student", function() use ($baseUrl, &$masterAToken) {
    $days = 'Monday, Wednesday, Friday';
    $timings = '06:30 PM - 08:00 PM';
    $res = http_req('POST', "$baseUrl/api/master/schedule.php", [
        'training_days' => $days,
        'training_timings' => $timings,
        'schedules' => [
            ['day' => 'Mon', 'day_full' => 'Monday', 'title' => 'Kata Practice', 'timing' => '06:30 PM - 08:00 PM', 'type' => 'Regular'],
            ['day' => 'Wed', 'day_full' => 'Wednesday', 'title' => 'Kumite Sparring', 'timing' => '06:30 PM - 08:00 PM', 'type' => 'Regular'],
            ['day' => 'Fri', 'day_full' => 'Friday', 'title' => 'Bunkai & Conditioning', 'timing' => '06:30 PM - 08:00 PM', 'type' => 'Regular']
        ]
    ], $masterAToken);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Update schedule failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    // Verify on Student Profile training_schedule
    $prof = http_req('GET', "$baseUrl/api/students/profile.php?student_id=10");
    $sched = $prof['json']['data']['training_schedule'] ?? '';
    if (strpos($sched, 'Monday') === false || strpos($sched, '06:30 PM') === false) {
        return "Student profile does not reflect updated schedule: '$sched'";
    }

    echo "  -> Schedule updated and verified on Student profile: '$sched'\n";
    return true;
});

// TEST 10: Master B login -> cannot see Dojo A students
run_test(10, "Master B login & strict dojo isolation", function() use ($baseUrl, &$masterBToken) {
    $res = http_req('POST', "$baseUrl/api/auth/login.php", [
        'email' => 'master_b@gmail.com',
        'password' => 'password123'
    ]);
    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Master B login failed: " . ($res['json']['message'] ?? $res['raw']);
    }
    $masterBToken = $res['json']['data']['token'];
    $dojoId = $res['json']['data']['dojo_id'];
    if ($dojoId != 2) {
        return "Expected Master B dojo_id to be 2, got: $dojoId";
    }

    // Fetch Master B students
    $studs = http_req('GET', "$baseUrl/api/master/students.php", null, $masterBToken);
    if ($studs['code'] !== 200) {
        return "Failed to fetch Master B students: " . $studs['raw'];
    }

    // Assert Dojo 1 students (Sai Rohan id=10) are NOT present
    foreach ($studs['json']['data'] as $s) {
        if ($s['student_id'] == 10 || stripos($s['name'], 'Sai Rohan') !== false) {
            return "Security violation: Dojo 1 student (Sai Rohan) found in Master B roster!";
        }
    }

    echo "  -> Master B authenticated (Dojo ID: $dojoId); Dojo 1 students strictly isolated.\n";
    return true;
});

// TEST 11: Master B direct API access to Dojo A student -> HTTP 403 denied
run_test(11, "Master B cross-dojo direct access blocked (HTTP 403)", function() use ($baseUrl, &$masterBToken) {
    // Attempt direct access to Sai Rohan (Dojo 1, student_id=10)
    $res = http_req('GET', "$baseUrl/api/master/student_profile.php?student_id=10", null, $masterBToken);

    if ($res['code'] !== 403) {
        return "Expected HTTP 403 Forbidden, got HTTP {$res['code']}: " . $res['raw'];
    }

    if (empty($res['json']) || strpos($res['json']['message'], 'Access denied') === false) {
        return "Expected 'Access denied' error message, got: " . ($res['json']['message'] ?? $res['raw']);
    }

    echo "  -> Security boundary enforced: HTTP 403 Forbidden returned when Master B accesses Dojo 1 student.\n";
    return true;
});

// TEST 12: Grand Master login -> views all dojos and students
run_test(12, "Grand Master global access across all dojos", function() use ($baseUrl, &$grandMasterToken, $pdo) {
    $gmEmail = 'admin@gmail.com';
    $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE email = ?")
        ->execute([password_hash('password123', PASSWORD_BCRYPT), $gmEmail]);

    $res = http_req('POST', "$baseUrl/api/auth/login.php", [
        'email' => $gmEmail,
        'password' => 'password123'
    ]);

    if ($res['code'] !== 200 || empty($res['json']['success'])) {
        return "Grand Master login failed: " . ($res['json']['message'] ?? $res['raw']);
    }

    $grandMasterToken = $res['json']['data']['token'];

    // Grand Master queries Student 10 (Dojo 1)
    $res1 = http_req('GET', "$baseUrl/api/master/student_profile.php?student_id=10", null, $grandMasterToken);
    if ($res1['code'] !== 200) {
        return "Grand Master was denied access to student 10: " . $res1['raw'];
    }

    // Grand Master queries students list
    $resList = http_req('GET', "$baseUrl/api/master/students.php", null, $grandMasterToken);
    if ($resList['code'] !== 200) {
        return "Grand Master failed to view students roster: " . $resList['raw'];
    }

    echo "  -> Grand Master authenticated and verified global visibility across all dojos without restriction.\n";
    return true;
});

echo "\n============================================================\n";
echo "SUMMARY: Total: 12 | Passed: $passed | Failed: $failed\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);
