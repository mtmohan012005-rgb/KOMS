<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.name, d.location, d.training_days, d.training_timings,
               d.description, d.contact_number, d.email,
               CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as master_name 
        FROM dojos d 
        LEFT JOIN users u ON d.master_id = u.id 
        WHERE d.status = 'approved'
        ORDER BY d.name ASC
    ");
    $stmt->execute();
    $dojos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_api_response($dojos, "Approved dojos fetched successfully");
} catch (Throwable $e) {
    error_log("API Dojos List Error: " . $e->getMessage());
    send_api_error("Unable to fetch dojos.", [], 500);
}
