<?php
require_once __DIR__ . '/../config/database.php';

$pdo->query("DELETE FROM dojos WHERE name LIKE 'Tambaram South Dojo%' OR name = 'Old Perungalathur Dojo'");
$stmt = $pdo->prepare("
    INSERT INTO dojos (
        name, master_id, location, contact_number, email, 
        training_days, training_timings, description, status, created_at
    ) VALUES (
        'Old Perungalathur Dojo', 2, 'Old Perungalathur, Chennai', '9841882666', 'perungalathur@koms.local',
        'Mon, Wed, Fri', '06:00 PM - 07:30 PM', 'Authentic Matsubayashi Shorin-Ryu Karate Dojo', 'pending', NOW()
    )
");
$stmt->execute();
echo "Seeded pending dojo: Old Perungalathur Dojo (ID: " . $pdo->lastInsertId() . ")\n";
