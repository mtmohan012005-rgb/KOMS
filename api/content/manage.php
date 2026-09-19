<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/api_auth.php';

handle_api_cors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true) ?: $_POST;

    $sectionKey = trim($data['section_key'] ?? '');
    $title = trim($data['title'] ?? '');
    $subtitle = trim($data['subtitle'] ?? '');
    $body = trim($data['body'] ?? '');
    $status = trim($data['status'] ?? 'published');

    if (empty($sectionKey) || empty($title)) {
        send_api_error("Section key and title are required.", [], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content_body = VALUES(content_body), status = VALUES(status)");
        $stmt->execute([$sectionKey, $title, $subtitle, $body, $status]);

        send_api_response([
            'section_key' => $sectionKey,
            'title' => $title,
            'status' => $status
        ], "Content updated successfully");
    } catch (Throwable $e) {
        send_api_error("Failed to update content: " . $e->getMessage(), [], 500);
    }
} else {
    try {
        $stmt = $pdo->query("SELECT * FROM common_ui_content ORDER BY display_order ASC");
        $rows = $stmt->fetchAll();
        send_api_response($rows, "Content sections retrieved");
    } catch (Throwable $e) {
        send_api_error("Error fetching content: " . $e->getMessage(), [], 500);
    }
}
