<?php
/**
 * KOMS Real-Time Fast JSON Polling Endpoint
 * Fallback & native mobile query endpoint for live updates
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$session_user_id = $_SESSION['user_id'] ?? null;
$session_user_role = $_SESSION['role'] ?? null;
$session_dojo_id = $_SESSION['dojo_id'] ?? null;
session_write_close();

require_once '../../config/database.php';
require_once '../../includes/realtime.php';

$since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($session_user_id ? (int)$session_user_id : null);
$user_role = isset($_GET['role']) ? trim($_GET['role']) : ($session_user_role ?: null);
$dojo_id = isset($_GET['dojo_id']) ? (int)$_GET['dojo_id'] : ($session_dojo_id ? (int)$session_dojo_id : null);

$events = fetch_realtime_events($pdo, $since_id, $user_id, $user_role, $dojo_id, 25);

$latest_id = $since_id;
foreach ($events as $ev) {
    if ($ev['id'] > $latest_id) {
        $latest_id = $ev['id'];
    }
}

echo json_encode([
    "success" => true,
    "count" => count($events),
    "latest_id" => $latest_id,
    "events" => $events,
    "server_time" => date('Y-m-d H:i:s')
]);
?>
