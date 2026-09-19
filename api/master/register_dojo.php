<?php
// api/master/register_dojo.php - Register a new Dojo by authenticated Master
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    require_api_role($caller, ['master', 'super_admin', 'grand_master', 'admin']);

    $masterId = (int)$caller['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $name = trim($data['name'] ?? '');
        $location = trim($data['location'] ?? '');
        $phone = trim($data['phone'] ?? $data['contact_number'] ?? '');
        $email = trim($data['email'] ?? '');
        $trainingDays = trim($data['training_days'] ?? 'Mon, Wed, Fri');
        $trainingTimings = trim($data['training_timings'] ?? '06:00 PM - 07:30 PM');
        $description = trim($data['description'] ?? '');
        $experience = trim($data['experience'] ?? '');
        $achievements = trim($data['achievements'] ?? '');

        if (empty($name) || empty($location)) {
            send_api_error("Dojo name and location are required.", [], 400);
        }

        // Check if dojo with same name already exists
        $checkStmt = $pdo->prepare("SELECT id FROM dojos WHERE name = ? LIMIT 1");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetch()) {
            send_api_error("A dojo with this name already exists.", [], 409);
        }

        // Insert new dojo with PENDING status (Grand Master approval required)
        $stmt = $pdo->prepare("
            INSERT INTO dojos (
                name, master_id, location, contact_number, email, 
                experience, achievements, description, 
                training_days, training_timings, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, 'pending', NOW()
            )
        ");
        $stmt->execute([
            $name, $masterId, $location, $phone, $email,
            $experience, $achievements, $description,
            $trainingDays, $trainingTimings
        ]);
        $dojoId = (int)$pdo->lastInsertId();

        // Audit log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'DOJO_REGISTERED', 'dojos', ?, ?)")
            ->execute([$masterId, $dojoId, "Master registered new dojo: $name (Awaiting Grand Master Approval)"]);

        send_api_response([
            'dojo_id' => $dojoId,
            'name' => $name,
            'master_id' => $masterId,
            'status' => 'pending',
            'message' => 'Dojo registered successfully. Status is PENDING Grand Master review.'
        ], "Dojo registration submitted successfully. Awaiting Grand Master approval.", 201);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch Master's dojos or status
        $stmt = $pdo->prepare("SELECT * FROM dojos WHERE master_id = ? ORDER BY id DESC");
        $stmt->execute([$masterId]);
        $dojos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        send_api_response($dojos, "Master dojos fetched successfully");
    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Register Dojo API Error: " . $e->getMessage());
    send_api_error("Error registering dojo: " . $e->getMessage(), [], 500);
}
