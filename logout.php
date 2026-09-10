<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

logout_user($pdo);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['success_msg'] = "You have been logged out successfully.";
redirect('/login.php');
?>
