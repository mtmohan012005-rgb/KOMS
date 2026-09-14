<?php
/**
 * KOMS Real-Time Fast JSON Polling Endpoint
 * Fallback & native mobile query endpoint for live updates
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api_auth.php';
require_once '../../includes/realtime.php';

handle_api_cors();

// Derive identity strictly from authenticated token or web session
$caller = authenticate_api_request($pdo, false);

$user_id = $caller ? (int)$caller['user_id'] : null;
$user_role = $caller ? $caller['role'] : null;
$dojo_id = $caller ? $caller['dojo_id'] : null;

$since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

$events = fetch_realtime_events($pdo, $since_id, $user_id, $user_role, $dojo_id, 25);

$latest_id = $since_id;
foreach ($events as $ev) {
    if ($ev['id'] > $latest_id) {
        $latest_id = $ev['id'];
    }
}

send_api_response([
    "count" => count($events),
    "latest_id" => $latest_id,
    "events" => $events,
    "server_time" => date('Y-m-d H:i:s')
], "Realtime events fetched successfully");

