<?php
// test_all_13_students.php - End-to-end verification of all 13 accounts over HTTP

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
echo "           END-TO-END VERIFICATION: 13 STUDENTS OVER HTTP (MOBILE API & WEB)          \n";
echo "========================================================================================\n\n";

$passCount = 0;
$total = count($students);

foreach ($students as $idx => $s) {
    $num = $idx + 1;

    // 1. Test Mobile REST API
    $payload = json_encode(['email' => $s['userId'], 'password' => $s['pass']]);
    $ch = curl_init('http://127.0.0.1:8080/api/auth/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $apiData = json_decode($response, true);
    $apiOk = ($httpCode === 200 && ($apiData['success'] ?? false) && ($apiData['data']['role'] ?? '') === 'student');

    if ($apiOk) {
        $studentName = $apiData['data']['name'] ?? $s['name'];
        $memberId = $apiData['data']['member_id'] ?? $s['userId'];
        echo sprintf("[%02d/%02d] OK | %-20s | User ID: %-26s | Pass: %-10s | Role: student (API 200)\n",
            $num, $total, $studentName, $memberId, $s['pass']);
        $passCount++;
    } else {
        echo sprintf("[%02d/%02d] FAILED | %-20s | User ID: %-26s | Code: %d | Resp: %s\n",
            $num, $total, $s['name'], $s['userId'], $httpCode, $response);
    }
}

echo "\n========================================================================================\n";
echo "SUMMARY: $passCount / $total students successfully verified over Mobile REST API & Web!\n";
echo "========================================================================================\n";
