<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api_auth.php';

handle_api_cors();

try {
    $stmt = $pdo->prepare("
        SELECT id, name, description, event_date, venue, registration_deadline, status 
        FROM tournaments 
        WHERE status IN ('published', 'registration_open') 
        ORDER BY event_date ASC
    ");
    $stmt->execute();
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_api_response($tournaments, "Tournaments fetched successfully");
} catch (Throwable $e) {
    error_log("API Tournaments List Error: " . $e->getMessage());
    send_api_error("Unable to fetch tournaments.", [], 500);
}

