<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/realtime.php';

echo "1. Dispatching test event...\n";
$eventId = dispatch_realtime_event($pdo, 'ATTENDANCE_MARKED', [
    'student_id' => 4,
    'session_date' => date('Y-m-d'),
    'status' => 'present',
    'remarks' => 'Demonstrated excellent Pinan Shodan',
    'marked_by' => 'Sensei Mass Dragon'
], 4, 'student', 1);

echo "Dispatched event ID: " . $eventId . "\n";

echo "2. Querying event via fetch_realtime_events...\n";
$events = fetch_realtime_events($pdo, $eventId - 1, 4, 'student', 1);
echo "Fetched events count: " . count($events) . "\n";
print_r($events);

if (count($events) > 0 && $events[0]['type'] === 'ATTENDANCE_MARKED') {
    echo "\n>>> REALTIME ENGINE TEST PASSED! <<<\n";
} else {
    echo "\n>>> TEST FAILED <<<\n";
}
