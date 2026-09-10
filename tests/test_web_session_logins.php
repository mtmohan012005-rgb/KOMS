<?php
// test_web_session_logins.php - Verifies all 13 student accounts via full HTTP Web session (CSRF + Cookies + 302 Redirect)

$students = [
    ['name' => 'Şai Rohan L', 'userId' => 'sairohan2012.koms', 'pass' => '20.10.2012'],
    ['name' => 'D. Guhan', 'userId' => 'dguhan2015.koms', 'pass' => '25.09.2015'],
    ['name' => 'Harshini.S', 'userId' => 'harshini2012.koms', 'pass' => '31.07.2012'],
    ['name' => 'S. Varshini', 'userId' => 'svarshini2012.koms', 'pass' => '30.10.2012'],
    ['name' => 'G.P. Prathyuminan', 'userId' => 'gpprathyuminan2020.koms', 'pass' => '25.09.2020'],
    ['name' => 'R. Harshitha', 'userId' => 'rharshitha2016.koms', 'pass' => '04.08.2016'],
    ['name' => 'R. Darshitha', 'userId' => 'rdarshitha2016.koms', 'pass' => '04.08.2016'],
    ['name' => 'M.P. Niranjana Sri', 'userId' => 'mpniranjanasri2019.koms', 'pass' => '30.10.2019'],
    ['name' => 'M. Krish Charan', 'userId' => 'mkrishcharan2017.koms', 'pass' => '08.08.2017'],
    ['name' => 'Thejasri A.', 'userId' => 'thejasri2019.koms', 'pass' => '02.05.2019'],
    ['name' => 'Karunesh M.', 'userId' => 'karunesh2014.koms', 'pass' => '20.08.2014'],
    ['name' => 'V. Pragatheeshwaran', 'userId' => 'vpragatheeshwaran2016.koms', 'pass' => '25.02.2016'],
    ['name' => 'Advick A.', 'userId' => 'advick2016.koms', 'pass' => '02.07.2016']
];

echo "========================================================================================\n";
echo "       LINE-BY-LINE VERIFICATION: 13 STUDENTS ON WEBSITE (CSRF & SESSION AUTH)        \n";
echo "========================================================================================\n\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'koms_cookie_');
$passCount = 0;
$total = count($students);

foreach ($students as $idx => $s) {
    $num = $idx + 1;
    @unlink($cookieJar);

    // 1. Fetch login page to get CSRF token & session cookie
    $ch = curl_init('http://127.0.0.1:8080/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
    $csrf = $m[1] ?? '';

    // 2. Submit credentials
    $postFields = http_build_query([
        'email' => $s['userId'],
        'password' => $s['pass'],
        'csrf_token' => $csrf
    ]);

    $ch = curl_init('http://127.0.0.1:8080/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $dashHtml = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $effectiveUrl = $info['url'] ?? '';
    $isStudentDash = (strpos($effectiveUrl, 'student/dashboard.php') !== false) || (strpos($dashHtml, 'Student Dashboard') !== false);

    if ($isStudentDash) {
        echo sprintf("[%02d/%02d] PASS | %-20s | User ID: %-26s | Pass: %-10s -> Web Student Dashboard OK\n",
            $num, $total, $s['name'], $s['userId'], $s['pass']);
        $passCount++;
    } else {
        echo sprintf("[%02d/%02d] FAIL | %-20s | User ID: %-26s | Url: %s\n",
            $num, $total, $s['name'], $s['userId'], $effectiveUrl);
    }
}

@unlink($cookieJar);

echo "\n========================================================================================\n";
echo "WEBSITE RESULTS: $passCount / $total students successfully logged in on Website!\n";
echo "========================================================================================\n";
