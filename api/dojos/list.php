<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../../config/database.php';

$stmt = $pdo->prepare("SELECT d.id, d.name, d.location, d.training_days, d.training_timings, u.first_name, u.last_name as master_name FROM dojos d JOIN users u ON d.master_id = u.id WHERE d.status = 'approved'");
$stmt->execute();
$dojos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "message" => "Dojos fetched successfully",
    "data" => $dojos
]);
?>
