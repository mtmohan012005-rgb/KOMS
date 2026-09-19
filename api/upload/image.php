<?php
// api/upload/image.php - Upload and persist profile and dojo photos with strict permission checks
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    $userId = (int)$caller['user_id'];
    $role = $caller['role'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_api_error("Method not allowed. Use POST multipart/form-data.", [], 405);
    }

    $uploadType = trim($_POST['type'] ?? 'student_profile'); // 'student_profile', 'master_profile', 'dojo_photo'

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['image']['error'] ?? 'NO_FILE';
        send_api_error("No file uploaded or upload error occurred ($errorCode).", [], 400);
    }

    $file = $_FILES['image'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        send_api_error("Invalid file format. Allowed formats: JPG, JPEG, PNG, WEBP.", [], 400);
    }

    // Permission check
    if ($uploadType === 'student_profile') {
        // Students can only upload their own photo
        $targetStudentId = (int)($_POST['student_id'] ?? $userId);
        if ($targetStudentId !== $userId && $role !== 'super_admin' && $role !== 'grand_master') {
            send_api_error("Access denied: You cannot edit another student's profile photo.", [], 403);
        }
        $subDir = "profiles/students";
        $fileName = "student_" . $targetStudentId . "_" . time() . "." . $ext;
        $dbUpdateUser = $targetStudentId;
    } elseif ($uploadType === 'master_profile') {
        if ($role !== 'master' && $role !== 'super_admin' && $role !== 'grand_master') {
            send_api_error("Access denied: Master role required.", [], 403);
        }
        $subDir = "profiles/masters";
        $fileName = "master_" . $userId . "_" . time() . "." . $ext;
        $dbUpdateUser = $userId;
    } elseif ($uploadType === 'dojo_photo') {
        $dojoId = (int)($_POST['dojo_id'] ?? $caller['dojo_id'] ?? 0);
        if ($role !== 'master' && $role !== 'super_admin' && $role !== 'grand_master') {
            send_api_error("Access denied: Master role required to upload dojo photos.", [], 403);
        }
        $subDir = "dojos";
        $fileName = "dojo_" . $dojoId . "_" . time() . "." . $ext;
        $dbUpdateUser = null;
    } else {
        send_api_error("Unsupported upload type.", [], 400);
    }

    $targetDir = __DIR__ . '/../../uploads/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . '/' . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        send_api_error("Failed to save uploaded file on server.", [], 500);
    }

    $relativeUrl = "uploads/" . $subDir . "/" . $fileName;

    // Update database
    if ($dbUpdateUser !== null) {
        $stmt = $pdo->prepare("UPDATE users SET profile_photo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$relativeUrl, $dbUpdateUser]);

        // Audit log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'PHOTO_UPDATED', 'users', ?, ?)")
            ->execute([$userId, $dbUpdateUser, "Profile photo updated for user ID $dbUpdateUser"]);
    }

    send_api_response([
        'url' => $relativeUrl,
        'type' => $uploadType,
        'filename' => $fileName,
        'user_id' => $dbUpdateUser
    ], "Photo uploaded and profile updated successfully.");

} catch (Throwable $e) {
    error_log("Image Upload API Error: " . $e->getMessage());
    send_api_error("Error uploading image: " . $e->getMessage(), [], 500);
}
