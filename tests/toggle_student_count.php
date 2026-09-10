<?php
require_once __DIR__ . '/../config/database.php';
$count = isset($argv[1]) ? (int)$argv[1] : 1;
$pdo->prepare("UPDATE users SET password_change_count = ? WHERE member_id = 'sairohan2012.koms'")->execute([$count]);
echo "Updated sairohan2012.koms password_change_count to $count\n";
