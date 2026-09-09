<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../../config/database.php';

$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE status IN ('published', 'registration_open') ORDER BY event_date ASC");
$stmt->execute();
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "message" => "Tournaments fetched",
    "data" => $tournaments
]);
?>
