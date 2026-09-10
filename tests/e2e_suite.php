<?php
// Comprehensive Automated E2E Test Suite for KOMS

$baseUrl = 'http://127.0.0.1:8080';
$passed = 0;
$failed = 0;
$errors = [];

function request($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'code' => $statusCode,
        'headers' => $headers,
        'body' => $body
    ];
}

function extractCsrf($html) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function assertPage($name, $res, $expectedCodes = [200], $mustContain = []) {
    global $passed, $failed, $errors;
    
    $codeOk = in_array($res['code'], $expectedCodes);
    $hasFatal = stripos($res['body'], 'Fatal error') !== false || stripos($res['body'], 'Uncaught Error') !== false || stripos($res['body'], 'PDOException') !== false;
    $hasWarning = stripos($res['body'], '<b>Warning</b>') !== false || stripos($res['body'], 'PHP Warning:') !== false;
    
    $missingContent = [];
    foreach ($mustContain as $term) {
        if (stripos($res['body'], $term) === false) {
            $missingContent[] = $term;
        }
    }
    
    if ($codeOk && !$hasFatal && !$hasWarning && empty($missingContent)) {
        echo "  [PASS] $name (HTTP {$res['code']})\n";
        $passed++;
    } else {
        echo "  [FAIL] $name (HTTP {$res['code']})\n";
        $err = "$name: ";
        if (!$codeOk) $err .= "Expected " . implode('/', $expectedCodes) . " got {$res['code']}. ";
        if ($hasFatal) $err .= "PHP Fatal Error detected. ";
        if ($hasWarning) $err .= "PHP Warning detected. ";
        if (!empty($missingContent)) $err .= "Missing expected content: " . implode(', ', $missingContent) . ". ";
        echo "         -> $err\n";
        $errors[] = $err;
        $failed++;
    }
}

echo "========================================================\n";
echo "       🥋 KOMS FULL APPLICATION AUDIT & TEST SUITE      \n";
echo "========================================================\n\n";

// 1. PUBLIC PAGES
echo "1. Testing Public & Landing Pages...\n";
$cookiePublic = tempnam(sys_get_temp_dir(), 'koms_public_');
$res = request("$baseUrl/index.php", 'GET', [], $cookiePublic);
assertPage("Landing / Entrance Page (index.php)", $res, [200], ['Mass Dragon Dojo', 'Karate Organization Management System']);

$res = request("$baseUrl/index.html", 'GET');
assertPage("Static Presentation Portal (index.html)", $res, [200], ['Mass Dragon Dojo']);

$res = request("$baseUrl/login.php", 'GET', [], $cookiePublic);
assertPage("Login Page (login.php)", $res, [200], ['Mass Dragon Dojo']);

$res = request("$baseUrl/find_dojo.php", 'GET', [], $cookiePublic);
assertPage("Find Dojo Page (find_dojo.php)", $res, [200], ['Find a Dojo']);

$res = request("$baseUrl/register.php", 'GET', [], $cookiePublic);
assertPage("Registration Page (register.php)", $res, [200], ['Register', 'DPDP']);

$res = request("$baseUrl/api/index.php", 'GET');
assertPage("API Status / Discovery (api/index.php)", $res, [200], ['Karate Organization Management System']);

echo "\n";

// 2. AUTHENTICATION & ROLE TEST: GRAND MASTER (super_admin)
echo "2. Testing Role: Grand Master (admin@gmail.com)...\n";
$cookieAdmin = tempnam(sys_get_temp_dir(), 'koms_admin_');
$loginPage = request("$baseUrl/login.php", 'GET', [], $cookieAdmin);
$csrf = extractCsrf($loginPage['body']);

$authRes = request("$baseUrl/login.php", 'POST', [
    'email' => 'admin@gmail.com',
    'password' => 'password123',
    'csrf_token' => $csrf
], $cookieAdmin);
assertPage("Admin Authentication", $authRes, [302]);

$adminPages = [
    'admin/dashboard.php'     => ['Command Center'],
    'admin/dojos.php'         => ['Dojos'],
    'admin/users.php'         => ['Users'],
    'admin/announcements.php' => ['Announcement'],
    'admin/tournaments.php'   => ['Tournament'],
    'admin/reports.php'       => ['Executive Intelligence'],
    'admin/audit.php'         => ['Audit']
];
foreach ($adminPages as $page => $content) {
    $r = request("$baseUrl/$page", 'GET', [], $cookieAdmin);
    assertPage("Admin -> $page", $r, [200], $content);
}

echo "\n";

// 3. AUTHENTICATION & ROLE TEST: DOJO MASTER (master)
echo "3. Testing Role: Dojo Master (master@gmail.com)...\n";
$cookieMaster = tempnam(sys_get_temp_dir(), 'koms_master_');
$loginPage = request("$baseUrl/login.php", 'GET', [], $cookieMaster);
$csrf = extractCsrf($loginPage['body']);

$authRes = request("$baseUrl/login.php", 'POST', [
    'email' => 'master@gmail.com',
    'password' => 'password123',
    'csrf_token' => $csrf
], $cookieMaster);
assertPage("Master Authentication", $authRes, [302]);

$masterPages = [
    'master/dashboard.php'       => ['Mass Dragon Dojo'],
    'master/students.php'        => ['Students'],
    'master/attendance.php'      => ['Attendance'],
    'master/fees.php'            => ['Fee'],
    'master/grading.php'         => ['Grading'],
    'master/tournaments.php'     => ['Tournament'],
    'master/announcements.php'   => ['Announcement'],
    'master/edit_dojo.php'       => ['Dojo']
];
foreach ($masterPages as $page => $content) {
    $r = request("$baseUrl/$page", 'GET', [], $cookieMaster);
    assertPage("Master -> $page", $r, [200], $content);
}

// Check guard redirects on parameter-dependent pages
$r = request("$baseUrl/master/mark_attendance.php", 'GET', [], $cookieMaster);
assertPage("Master -> mark_attendance.php (No Session Guard)", $r, [302]);

$r = request("$baseUrl/master/record_payment.php", 'GET', [], $cookieMaster);
assertPage("Master -> record_payment.php (No Record Guard)", $r, [302]);

// Now test with valid record_id=1
$r = request("$baseUrl/master/record_payment.php?record_id=1", 'GET', [], $cookieMaster);
assertPage("Master -> record_payment.php?record_id=1", $r, [200], ['Payment']);

echo "\n";

// 4. AUTHENTICATION & ROLE TEST: SENIOR STUDENT (senior)
echo "4. Testing Role: Senior Student (senior@gmail.com)...\n";
$cookieSenior = tempnam(sys_get_temp_dir(), 'koms_senior_');
$loginPage = request("$baseUrl/login.php", 'GET', [], $cookieSenior);
$csrf = extractCsrf($loginPage['body']);

$authRes = request("$baseUrl/login.php", 'POST', [
    'email' => 'senior@gmail.com',
    'password' => 'password123',
    'csrf_token' => $csrf
], $cookieSenior);
assertPage("Senior Authentication", $authRes, [302]);

$r = request("$baseUrl/senior/dashboard.php", 'GET', [], $cookieSenior);
assertPage("Senior -> senior/dashboard.php", $r, [200], ['Senior']);

echo "\n";

// 5. AUTHENTICATION & ROLE TEST: STUDENT (student@gmail.com)
echo "5. Testing Role: Student (student@gmail.com)...\n";
$cookieStudent = tempnam(sys_get_temp_dir(), 'koms_student_');
$loginPage = request("$baseUrl/login.php", 'GET', [], $cookieStudent);
$csrf = extractCsrf($loginPage['body']);

$authRes = request("$baseUrl/login.php", 'POST', [
    'email' => 'student@gmail.com',
    'password' => 'password123',
    'csrf_token' => $csrf
], $cookieStudent);
assertPage("Student Authentication", $authRes, [302]);

$studentPages = [
    'student/dashboard.php'     => ['Student Dashboard'],
    'student/my_dojo.php'       => ['Dojo'],
    'student/attendance.php'    => ['Attendance'],
    'student/fees.php'          => ['Fee'],
    'student/grading.php'       => ['Grading'],
    'student/tournaments.php'   => ['Tournament'],
    'student/announcements.php' => ['Announcement'],
    'student/achievements.php'  => ['Achievement']
];
foreach ($studentPages as $page => $content) {
    $r = request("$baseUrl/$page", 'GET', [], $cookieStudent);
    assertPage("Student -> $page", $r, [200], $content);
}

// Check Profile page when logged in
$r = request("$baseUrl/profile.php", 'GET', [], $cookieStudent);
assertPage("Student -> profile.php", $r, [200], ['Profile']);

echo "\n";

// 6. LOGOUT TEST
echo "6. Testing Logout...\n";
$logoutRes = request("$baseUrl/logout.php", 'GET', [], $cookieStudent);
assertPage("Logout Execution", $logoutRes, [302]);

echo "\n========================================================\n";
echo "SUMMARY: Passed: $passed, Failed: $failed\n";
if (!empty($errors)) {
    echo "Errors encountered:\n";
    foreach ($errors as $e) {
        echo " - $e\n";
    }
} else {
    echo "🥋 ALL 33 SUITES PASSED! ZERO ERRORS DETECTED!\n";
}
echo "========================================================\n";
