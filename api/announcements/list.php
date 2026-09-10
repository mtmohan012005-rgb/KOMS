<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../../config/database.php';

// Only fetching global announcements for simplicity in API
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE level = 'global' AND status = 'active' ORDER BY publish_date DESC");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "message" => "Announcements fetched",
    "data" => $announcements
]);
?>
