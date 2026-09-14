<?php
require_once __DIR__ . '/../config/database.php';

$ch = curl_init("http://localhost:8080/student_profile.php?id=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $code, Bytes: " . strlen($res) . "\n";
if (strpos($res, "L. Sai Rohan") !== false && strpos($res, "8939319656") !== false && strpos($res, "Lingadhurai. S") !== false) {
    echo "[PASS] Student Profile rendered successfully with exact matching data!\n";
} else {
    echo "[FAIL] Data missing in rendered profile.\n";
}
