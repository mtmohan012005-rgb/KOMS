<?php
// api/master/certificates.php - Issue and Manage Student Certificates for Master
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    require_api_role($caller, ['master', 'super_admin', 'grand_master', 'admin']);

    $masterId = (int)$caller['user_id'];
    $dojoId = $caller['dojo_id'] ?? resolve_user_dojo_id($pdo, $masterId, $caller['role']);

    if (!$dojoId && $caller['role'] !== 'super_admin') {
        send_api_error("No active dojo found associated with this Master account.", [], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $studentId = (int)($data['student_id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $certificateType = trim($data['certificate_type'] ?? 'Participation');
        $certificateNumber = trim($data['certificate_number'] ?? ('CERT-' . date('Y') . '-' . rand(100, 999)));
        $issueDate = trim($data['issue_date'] ?? date('Y-m-d'));
        $notes = trim($data['notes'] ?? '');

        if ($studentId <= 0 || empty($title)) {
            send_api_error("student_id and title are required.", [], 400);
        }

        // Verify student belongs to this dojo
        assert_dojo_access($pdo, $caller, $studentId);

        // Ensure certificates table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            dojo_id INT NOT NULL,
            certificate_type VARCHAR(100) NOT NULL,
            title VARCHAR(180) NOT NULL,
            certificate_number VARCHAR(100) NOT NULL,
            issue_date DATE NOT NULL,
            notes TEXT NULL,
            issued_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cert_student (student_id),
            INDEX idx_cert_dojo (dojo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->prepare("INSERT INTO certificates (student_id, dojo_id, certificate_type, title, certificate_number, issue_date, notes, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$studentId, $dojoId, $certificateType, $title, $certificateNumber, $issueDate, $notes, $masterId]);
        $certificateId = $pdo->lastInsertId();

        // Student name
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = ?");
        $stmt->execute([$studentId]);
        $studentName = $stmt->fetchColumn() ?: "Student ID $studentId";

        // Audit Log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'CERTIFICATE_ISSUED', 'certificates', ?, ?)")
            ->execute([$masterId, $studentId, "Certificate added for $studentName ($title)"]);

        // Total count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $totalCount = (int)$stmt->fetchColumn();

        send_api_response([
            'certificate_id' => (int)$certificateId,
            'student_id' => $studentId,
            'student_name' => $studentName,
            'title' => $title,
            'certificate_number' => $certificateNumber,
            'total_certificates' => $totalCount
        ], "Certificate issued successfully");

    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Certificates API Error: " . $e->getMessage());
    send_api_error("Error issuing certificate: " . $e->getMessage(), [], 500);
}
