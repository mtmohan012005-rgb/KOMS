<?php
// api/students/update_profile.php - Live Student Profile Update API with field-level permissions
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_api_error("Only POST method allowed", [], 405);
}

try {
    $authUser = authenticate_api_request($pdo, true);
    
    // Strict field-level permission: Masters cannot edit student personal details
    if ($authUser['role'] === 'master') {
        send_api_error("Access denied: Masters cannot modify student personal information.", [], 403);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    // Students can only update their own profile unless super_admin
    $studentId = (int)$authUser['user_id'];
    if ($authUser['role'] === 'super_admin' || $authUser['role'] === 'grand_master') {
        if (!empty($data['student_id'])) {
            $studentId = (int)$data['student_id'];
        }
    }

    $father = trim($data['father_name'] ?? '');
    $mother = trim($data['mother_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $altPhone = trim($data['alternate_phone'] ?? '');
    $bloodGroup = trim($data['blood_group'] ?? '');
    $address = trim($data['address'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE users SET 
            father_name = COALESCE(NULLIF(?, ''), father_name),
            mother_name = COALESCE(NULLIF(?, ''), mother_name),
            phone = COALESCE(NULLIF(?, ''), phone),
            alternate_phone = COALESCE(NULLIF(?, ''), alternate_phone),
            blood_group = COALESCE(NULLIF(?, ''), blood_group),
            address = COALESCE(NULLIF(?, ''), address),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$father, $mother, $phone, $altPhone, $bloodGroup, $address, $studentId]);

    // Audit log
    $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'STUDENT_PROFILE_UPDATED', 'users', ?, ?)")
        ->execute([$studentId, $studentId, "Student updated own personal profile information"]);

    send_api_response([
        'student_id' => $studentId,
        'updated' => true
    ], "Profile updated successfully");
} catch (Throwable $e) {
    error_log("Student Profile Update API Error: " . $e->getMessage());
    send_api_error("Unable to update profile: " . $e->getMessage(), [], 500);
}
