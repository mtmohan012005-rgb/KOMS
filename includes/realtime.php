<?php
/**
 * KOMS Real-Time Event Dispatcher and Telemetry Engine
 * Powers live attendance, instant alerts, belt grading, and financial sync.
 */

if (!function_exists('ensure_realtime_table_exists')) {
    function ensure_realtime_table_exists(PDO $pdo) {
        static $checked = false;
        if ($checked) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS realtime_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(64) NOT NULL,
            payload LONGTEXT NOT NULL,
            target_user_id INT UNSIGNED NULL,
            target_role VARCHAR(32) NULL,
            dojo_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_target_user (target_user_id),
            INDEX idx_target_role (target_role),
            INDEX idx_dojo (dojo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $pdo->exec($sql);
            $checked = true;
        } catch (Exception $e) {
            error_log("Failed to ensure realtime_events table: " . $e->getMessage());
        }
    }
}

if (!function_exists('dispatch_realtime_event')) {
    /**
     * Dispatch an event to connected clients
     *
     * @param PDO $pdo
     * @param string $event_type (e.g. ATTENDANCE_MARKED, ANNOUNCEMENT_NEW, BELT_PROMOTED, PAYMENT_RECEIVED)
     * @param array $payload Key-value payload
     * @param int|null $target_user_id If set, targets only this user
     * @param string|null $target_role If set, targets all users with this role (e.g. 'student', 'master')
     * @param int|null $dojo_id If set, targets users in this dojo
     * @return int|bool Inserted event ID or false on failure
     */
    function dispatch_realtime_event(PDO $pdo, string $event_type, array $payload, ?int $target_user_id = null, ?string $target_role = null, ?int $dojo_id = null) {
        ensure_realtime_table_exists($pdo);
        
        $payload['timestamp'] = time();
        $payload['time_str'] = date('H:i:s');
        $json = json_encode($payload);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO realtime_events (event_type, payload, target_user_id, target_role, dojo_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$event_type, $json, $target_user_id, $target_role, $dojo_id]);
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error dispatching realtime event: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('fetch_realtime_events')) {
    /**
     * Fetch events since a specific ID matching recipient criteria
     */
    function fetch_realtime_events(PDO $pdo, int $since_id = 0, ?int $user_id = null, ?string $user_role = null, ?int $dojo_id = null, int $limit = 25): array {
        ensure_realtime_table_exists($pdo);
        
        $where = ["id > ?"];
        $params = [$since_id];
        
        // Match conditions: broadcast events OR targeted to specific user/role/dojo
        $targets = ["(target_user_id IS NULL AND target_role IS NULL AND dojo_id IS NULL)"];
        
        if ($user_id !== null) {
            $targets[] = "(target_user_id = ?)";
            $params[] = $user_id;
        }
        
        if (!empty($user_role)) {
            $targets[] = "(target_role = ?)";
            $params[] = $user_role;
        }
        
        if ($dojo_id !== null && $dojo_id > 0) {
            $targets[] = "(dojo_id = ?)";
            $params[] = $dojo_id;
        }
        
        $where[] = "(" . implode(" OR ", $targets) . ")";
        
        $sql = "SELECT id, event_type, payload, target_user_id, target_role, dojo_id, created_at 
                FROM realtime_events 
                WHERE " . implode(" AND ", $where) . " 
                ORDER BY id ASC 
                LIMIT " . (int)$limit;
                
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $events = [];
            foreach ($rows as $row) {
                $decoded = json_decode($row['payload'], true) ?: [];
                $events[] = [
                    'id' => (int)$row['id'],
                    'type' => $row['event_type'],
                    'payload' => $decoded,
                    'target_user_id' => $row['target_user_id'] ? (int)$row['target_user_id'] : null,
                    'target_role' => $row['target_role'],
                    'dojo_id' => $row['dojo_id'] ? (int)$row['dojo_id'] : null,
                    'created_at' => $row['created_at']
                ];
            }
            return $events;
        } catch (Exception $e) {
            error_log("Error fetching realtime events: " . $e->getMessage());
            return [];
        }
    }
}
