<?php
// tests/test_dojo_approval_student_flow.php - Integration test for Dojo Approval -> Common UI -> Student Registration -> Master Approval & Field Permissions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';

$baseUrl = "http://127.0.0.1:8080";
$passCount = 0;
$failCount = 0;

function run_step($name, $closure) {
    global $passCount, $failCount;
    echo "\n------------------------------------------------------------\n";
    echo "TEST STEP: $name\n";
    try {
        $res = $closure();
        if ($res !== false) {
            echo "RESULT: PASSED [OK]\n";
            $passCount++;
        } else {
            echo "RESULT: FAILED [X]\n";
            $failCount++;
        }
    } catch (Throwable $e) {
        echo "RESULT: EXCEPTION -> " . $e->getMessage() . "\n";
        $failCount++;
    }
}

function http_call($endpoint, $method = 'GET', $payload = null, $token = null) {
    global $baseUrl;
    $ch = curl_init("$baseUrl/$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($body, true), 'raw' => $body];
}

echo "============================================================\n";
echo "KOMS END-TO-END DOJO APPROVAL & STUDENT WORKFLOW TEST SUITE\n";
echo "============================================================\n";

// Setup Test Master (ID 2 - R.N. Thirukailash)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'master' LIMIT 1");
$stmt->execute();
$masterUser = $stmt->fetch(PDO::FETCH_ASSOC);
$masterToken = create_api_token($masterUser);

// Setup Grand Master (ID 1 - Admin)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('super_admin', 'grand_master') LIMIT 1");
$stmt->execute();
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
$adminToken = create_api_token($adminUser);

// Setup or create a new test student (Student X)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND id != 10 LIMIT 1");
$stmt->execute();
$testStudent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$testStudent) {
    $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, status) VALUES ('test_student', 'Test', 'Student', 'teststudent@koms.local', 'hash', 'student', 'active')")->execute();
    $testStudentId = $pdo->lastInsertId();
    $testStudent = ['id' => $testStudentId, 'role' => 'student', 'name' => 'Test Student', 'email' => 'teststudent@koms.local'];
}
$studentToken = create_api_token($testStudent);

$testDojoName = "Tambaram South Dojo " . time();
$createdDojoId = 0;

// STEP 1: Master registers a new Dojo -> Status = PENDING
run_step("1. Master registers a new Dojo (Tambaram South Dojo)", function() use ($masterToken, $testDojoName, &$createdDojoId) {
    $payload = [
        'name' => $testDojoName,
        'location' => 'Tambaram East, Chennai',
        'contact_number' => '9840123456',
        'email' => 'tambaram.dojo@koms.local',
        'training_days' => 'Tue, Thu, Sat',
        'training_timings' => '05:30 PM - 07:00 PM',
        'description' => 'Classical Shorin-Ryu Karate Training Center'
    ];
    $res = http_call("api/master/register_dojo.php", "POST", $payload, $masterToken);
    if ($res['code'] !== 201 || empty($res['data']['data']['dojo_id'])) {
        echo "Failed to register dojo: " . $res['raw'] . "\n";
        return false;
    }
    $createdDojoId = (int)$res['data']['data']['dojo_id'];
    echo "Dojo registered with ID: $createdDojoId, Status: {$res['data']['data']['status']}\n";
    return $res['data']['data']['status'] === 'pending';
});

// STEP 2: Pending Dojo MUST NOT appear in Common UI
run_step("2. Verify Pending Dojo does NOT appear in Common UI (api/dojos/list.php)", function() use ($testDojoName) {
    $res = http_call("api/dojos/list.php", "GET");
    if ($res['code'] !== 200 || empty($res['data']['data'])) {
        echo "Failed to fetch dojos list: " . $res['raw'] . "\n";
        return false;
    }
    $found = false;
    foreach ($res['data']['data'] as $d) {
        if ($d['name'] === $testDojoName) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "ERROR: Pending dojo appeared in Common UI!\n";
        return false;
    }
    echo "Pending dojo correctly HIDDEN from Common UI.\n";
    return true;
});

// STEP 3: Student CANNOT join pending dojo
run_step("3. Verify Student CANNOT join unapproved/pending dojo", function() use ($studentToken, $createdDojoId) {
    $payload = ['dojo_id' => $createdDojoId];
    $res = http_call("api/students/join_dojo.php", "POST", $payload, $studentToken);
    if ($res['code'] === 400) {
        echo "Correctly rejected join request for pending dojo: {$res['data']['message']}\n";
        return true;
    }
    echo "Unexpected response: " . $res['raw'] . "\n";
    return false;
});

// STEP 4: Grand Master sees pending dojo application
run_step("4. Grand Master views pending dojo applications", function() use ($adminToken, $createdDojoId) {
    $res = http_call("api/admin/master_requests.php", "GET", null, $adminToken);
    if ($res['code'] !== 200 || !is_array($res['data']['data'])) {
        echo "Failed to fetch master requests: " . $res['raw'] . "\n";
        return false;
    }
    $found = false;
    foreach ($res['data']['data'] as $req) {
        if ((int)$req['id'] === $createdDojoId) {
            $found = true;
            break;
        }
    }
    echo "Pending dojo found in Grand Master review queue: " . ($found ? "YES" : "NO") . "\n";
    return $found;
});

// STEP 5: Grand Master approves the Dojo
run_step("5. Grand Master APPROVES the Dojo", function() use ($adminToken, $createdDojoId) {
    $payload = [
        'request_id' => $createdDojoId,
        'action' => 'approve'
    ];
    $res = http_call("api/admin/master_requests.php", "POST", $payload, $adminToken);
    if ($res['code'] !== 200 || empty($res['data']['data']['status'])) {
        echo "Failed to approve dojo: " . $res['raw'] . "\n";
        return false;
    }
    echo "Dojo approved by Grand Master! Status: {$res['data']['data']['status']}\n";
    return $res['data']['data']['status'] === 'approved';
});

// STEP 6: Approved Dojo now APPEARS in Common UI
run_step("6. Verify Approved Dojo is now visible in Common UI (api/dojos/list.php)", function() use ($testDojoName) {
    $res = http_call("api/dojos/list.php", "GET");
    if ($res['code'] !== 200 || empty($res['data']['data'])) {
        return false;
    }
    $found = false;
    foreach ($res['data']['data'] as $d) {
        if ($d['name'] === $testDojoName) {
            $found = true;
            break;
        }
    }
    echo "Approved dojo visible in Common UI: " . ($found ? "YES" : "NO") . "\n";
    return $found;
});

// STEP 7: Student submits join request for newly approved dojo
run_step("7. Student submits Join Request for newly approved Dojo", function() use ($pdo, $studentToken, $testStudent, $createdDojoId) {
    // Clear any previous membership for test student
    $pdo->prepare("DELETE FROM dojo_memberships WHERE student_id = ?")->execute([$testStudent['id']]);

    $payload = ['dojo_id' => $createdDojoId];
    $res = http_call("api/students/join_dojo.php", "POST", $payload, $studentToken);
    if ($res['code'] !== 201 || empty($res['data']['data']['membership_id'])) {
        echo "Failed to join dojo: " . $res['raw'] . "\n";
        return false;
    }
    echo "Join request submitted! Status: {$res['data']['data']['status']}\n";
    return $res['data']['data']['status'] === 'pending';
});

// STEP 8: Master sees pending student request & approves
run_step("8. Master receives student join request & APPROVES student", function() use ($masterToken, $testStudent, $createdDojoId) {
    // Master token needs dojo_id of created dojo or resolved
    $reqPayload = [
        'student_id' => $testStudent['id'],
        'action' => 'approve'
    ];
    $res = http_call("api/master/student_requests.php", "POST", $reqPayload, $masterToken);
    if ($res['code'] !== 200) {
        echo "Master approve response: " . $res['raw'] . "\n";
        return false;
    }
    echo "Master approved student! Member status is now: {$res['data']['data']['status']}\n";
    return $res['data']['data']['status'] === 'Active';
});

// STEP 9: Field-level permissions: Master CANNOT edit student personal details
run_step("9. Field-level permission check: Master editing student personal details is BLOCKED", function() use ($masterToken, $testStudent) {
    $payload = [
        'student_id' => $testStudent['id'],
        'phone' => '9999999999',
        'address' => 'Hacked Address'
    ];
    $res = http_call("api/students/update_profile.php", "POST", $payload, $masterToken);
    if ($res['code'] === 403) {
        echo "Access denied as expected: {$res['data']['message']}\n";
        return true;
    }
    echo "Unexpected status code: {$res['code']}, response: " . $res['raw'] . "\n";
    return false;
});

// STEP 10: Field-level permissions: Student CAN edit their own personal details
run_step("10. Field-level permission check: Student CAN edit their own personal details", function() use ($studentToken) {
    $payload = [
        'father_name' => 'Updated Father S',
        'phone' => '8939319656',
        'address' => 'Valid Student Address, Chennai'
    ];
    $res = http_call("api/students/update_profile.php", "POST", $payload, $studentToken);
    if ($res['code'] === 200 && !empty($res['data']['data']['updated'])) {
        echo "Student successfully updated their own profile.\n";
        return true;
    }
    echo "Student update failed: " . $res['raw'] . "\n";
    return false;
});

echo "\n============================================================\n";
echo "SUMMARY: Total: " . ($passCount + $failCount) . " | Passed: $passCount | Failed: $failCount\n";
echo "============================================================\n";
