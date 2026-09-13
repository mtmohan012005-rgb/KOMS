-- Real-Time Events Engine Schema for KOMS
-- Supports Server-Sent Events (SSE) and fast polling across mobile and web clients

CREATE TABLE IF NOT EXISTS realtime_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(64) NOT NULL,
    payload JSON NOT NULL,
    target_user_id INT UNSIGNED NULL,
    target_role VARCHAR(32) NULL,
    dojo_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_target_user (target_user_id),
    INDEX idx_target_role (target_role),
    INDEX idx_dojo (dojo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
