<?php
// Test HTTP web login with session cookies and API login
function test_http_post($url, $data, &$cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $httpCode, 'redirect' => $redirectUrl, 'body' => $response];
}

$baseUrl = 'http://localhost/koms';

echo "=== REAL HTTP LOGIN TEST ===\n\n";

// 1. Test Master Login with Email
$cookieMasterEmail = tempnam(sys_get_temp_dir(), 'koms_master_em_');
$res1 = test_http_post("$baseUrl/login.php", [
    'login' => 'master@gmail.com',
    'password' => 'password123'
], $cookieMasterEmail);
echo "1. Master by Email ('master@gmail.com'): HTTP {$res1['code']} -> Redirects to: {$res1['redirect']}\n";

// 2. Test Master Login with User ID
$cookieMasterId = tempnam(sys_get_temp_dir(), 'koms_master_id_');
$res2 = test_http_post("$baseUrl/login.php", [
    'login' => 'master.koms',
    'password' => 'password123'
], $cookieMasterId);
echo "2. Master by User ID ('master.koms'): HTTP {$res2['code']} -> Redirects to: {$res2['redirect']}\n";

// 3. Test Student Login with Email
$cookieStudentEmail = tempnam(sys_get_temp_dir(), 'koms_stud_em_');
$res3 = test_http_post("$baseUrl/login.php", [
    'login' => 'sairohan2012@koms.local',
    'password' => '20.10.2012'
], $cookieStudentEmail);
echo "3. Student by Email ('sairohan2012@koms.local'): HTTP {$res3['code']} -> Redirects to: {$res3['redirect']}\n";

// 4. Test Student Login with User ID
$cookieStudentId = tempnam(sys_get_temp_dir(), 'koms_stud_id_');
$res4 = test_http_post("$baseUrl/login.php", [
    'login' => 'sairohan2012.koms',
    'password' => '20.10.2012'
], $cookieStudentId);
echo "4. Student by User ID ('sairohan2012.koms'): HTTP {$res4['code']} -> Redirects to: {$res4['redirect']}\n";

// 5. Test REST API Auth for Mobile App (POST /api/login.php)
$ch = curl_init("$baseUrl/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'dguhan2015.koms', 'password' => '25.09.2015']));
$apiResp = curl_exec($ch);
$apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "\n5. Mobile API Auth with User ID ('dguhan2015.koms'): HTTP $apiCode\n";
echo "API Response: $apiResp\n";
