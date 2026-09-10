<?php
require_once __DIR__ . '/../config/database.php';

$hash = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE email IN ('master@gmail.com', 'master@koms.com', 'admin@gmail.com', 'admin@koms.com')")->execute([$hash]);

$users = $pdo->query("SELECT id, member_id, email, role FROM users WHERE role IN ('super_admin', 'master')")->fetchAll();
print_r($users);
