<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api_auth.php';

handle_api_cors();

$caller = authenticate_api_request($pdo, false);

try {
    if ($caller && !empty($caller['dojo_id'])) {
        $stmt = $pdo->prepare("
            SELECT id, title, content, level, dojo_id, publish_date 
            FROM announcements 
            WHERE status = 'active' 
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
              AND (level = 'global' OR (level = 'dojo' AND dojo_id = ?))
            ORDER BY publish_date DESC
            LIMIT 30
        ");
        $stmt->execute([(int)$caller['dojo_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, title, content, level, dojo_id, publish_date 
            FROM announcements 
            WHERE status = 'active' 
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
              AND level = 'global' 
            ORDER BY publish_date DESC
            LIMIT 30
        ");
        $stmt->execute();
    }
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_api_response($announcements, "Announcements fetched successfully");
} catch (Throwable $e) {
    error_log("API Announcements List Error: " . $e->getMessage());
    send_api_error("Unable to fetch announcements.", [], 500);
}

