<?php
$url = 'https://koms-backend.onrender.com/login.php';
$html = @file_get_contents($url);

if ($html === false) {
    echo "Could not reach $url\n";
    exit(1);
}

echo "Render Live check:\n";
echo "Response Length: " . strlen($html) . " bytes\n";
echo "Has '1-CLICK DEMO ACCOUNTS': " . (strpos($html, '1-CLICK DEMO ACCOUNTS') !== false ? "YES (STILL OLD)" : "NO (REMOVED)") . "\n";
echo "Has 'Native App': " . (strpos($html, 'Native App') !== false ? "YES (UPDATED)" : "NO") . "\n";
echo "Has 'Web Portal': " . (strpos($html, 'Web Portal') !== false ? "YES" : "NO") . "\n";
