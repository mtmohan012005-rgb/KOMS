<?php
/**
 * KOMS Real-Time Server-Sent Events (SSE) Stream Endpoint
 * Pushes live updates directly to mobile and web clients
 */

// Set SSE Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Last-Event-ID");
header("Content-Type: text/event-stream; charset=UTF-8");
header("Cache-Control: no-cache, no-transform");
header("Connection: keep-alive");
header("X-Accel-Buffering: no"); // Disables proxy buffering for nginx

// Prevent session lock from blocking parallel requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$session_user_id = $_SESSION['user_id'] ?? null;
$session_user_role = $_SESSION['role'] ?? null;
$session_dojo_id = $_SESSION['dojo_id'] ?? null;
session_write_close(); // Release session file lock immediately

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api_auth.php';
require_once '../../includes/realtime.php';

// Authenticate caller securely via Bearer token, ?token= param, or web session
$caller = authenticate_api_request($pdo, false);

$user_id = $caller ? (int)$caller['user_id'] : null;
$user_role = $caller ? $caller['role'] : null;
$dojo_id = $caller ? $caller['dojo_id'] : null;

// Last event ID from client header or query
$last_event_id = 0;
if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $last_event_id = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
} elseif (isset($_GET['since_id'])) {
    $last_event_id = (int)$_GET['since_id'];
} else {
    // Default to the most recent event ID if initial connection
    ensure_realtime_table_exists($pdo);
    try {
        $stmt = $pdo->query("SELECT MAX(id) FROM realtime_events");
        $last_event_id = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $last_event_id = 0;
    }
}

// Initial Handshake Event
echo "event: handshake\n";
echo "data: " . json_encode([
    "status" => "connected",
    "server_time" => date('Y-m-d H:i:s'),
    "last_id" => $last_event_id,
    "client" => [
        "user_id" => $user_id,
        "role" => $user_role,
        "dojo_id" => $dojo_id
    ]
]) . "\n\n";

if (ob_get_level() > 0) ob_flush();
flush();

// Long-lived loop (max 50 seconds per connection, browser auto-reconnects seamlessly)
$start_time = time();
$last_ping = time();

while (time() - $start_time < 50) {
    if (connection_aborted()) {
        break;
    }

    $events = fetch_realtime_events($pdo, $last_event_id, $user_id, $user_role, $dojo_id, 10);
    
    if (!empty($events)) {
        foreach ($events as $ev) {
            $last_event_id = $ev['id'];
            echo "id: {$ev['id']}\n";
            echo "event: {$ev['type']}\n";
            echo "data: " . json_encode($ev) . "\n\n";
        }
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    // Send heartbeat ping every 15s
    if (time() - $last_ping >= 15) {
        echo ": ping " . time() . "\n\n";
        $last_ping = time();
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    usleep(800000); // 0.8s poll pause
}

// Connection end - browser will reconnect with Last-Event-ID
echo "event: reconnect\n";
echo "data: {\"last_id\": $last_event_id}\n\n";
if (ob_get_level() > 0) ob_flush();
flush();
?>
