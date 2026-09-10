<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting password security migration...\n";

    // 1. Ensure columns on users table
    ensure_user_column($pdo, 'must_change_password', 'must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
    ensure_user_column($pdo, 'password_change_count', 'password_change_count INT NOT NULL DEFAULT 0 AFTER must_change_password');

    echo "Ensured user columns (must_change_password, password_change_count).\n";

    // 2. Create password_reset_requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            dojo_id INT NULL,
            reason TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            master_notes TEXT NULL,
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            CONSTRAINT fk_pwd_req_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Created table password_reset_requests.\n";

    // 3. For all students that have default DOB passwords, ensure password_change_count = 0 and must_change_password = 1
    $count = $pdo->exec("UPDATE users SET password_change_count = 0, must_change_password = 1 WHERE role = 'student' AND (password_change_count IS NULL OR password_change_count = 0)");
    echo "Updated student accounts with initial password state (Count: $count).\n";

    echo "Migration completed successfully!\n";
} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
