<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "db.php"; 

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// 1. VALIDATE PAYLOAD
if (empty($data) || !isset($data['session_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or empty payload."]);
    exit();
}

// 2. THE BOUNCER
if (empty($data["auth_token"]) || empty($data["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Missing token."]);
    exit();
}

$authToken = $data["auth_token"];
$userId = $data["user_id"];
    try {
    $conn->beginTransaction();

    // STRICT TOKEN VERIFICATION
    $stmtUser = $conn->prepare("SELECT id FROM users WHERE id = ? AND auth_token = ?");
    $stmtUser->execute([$userId, $authToken]);
    if ($stmtUser->rowCount() === 0) {
        $conn->rollBack();
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Token invalid or expired."]);
        exit();
    }

    // 4. INSERT PARENT SESSION (ON DUPLICATE KEY UPDATE handles retries gracefully)
    $stmtSession = $conn->prepare("
        INSERT INTO workout_sessions (id, user_id, routine_id, status, global_score, duration_seconds) 
        VALUES (?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status), global_score = VALUES(global_score), duration_seconds = VALUES(duration_seconds)
    ");
    
    $routineId = !empty($data['routine_id']) ? $data['routine_id'] : null;
    $stmtSession->execute([
        $data['session_id'],
        $userId,
        $routineId,
        $data['status'] ?? 'IN_PROGRESS',
        $data['global_score'] ?? 0,
        $data['duration_seconds'] ?? 0
    ]);

    // 5. INSERT EXERCISES AND REPS
    if (!empty($data['exercises'])) {
        // Prepare statements once for massive performance gain on loops
        $stmtEx = $conn->prepare("INSERT IGNORE INTO exercise_telemetry (id, session_id, exercise_name, good_reps, bad_reps, exercise_score) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtRep = $conn->prepare("INSERT IGNORE INTO rep_telemetry (id, exercise_telemetry_id, rep_number, score) VALUES (?, ?, ?, ?)");

        foreach ($data['exercises'] as $ex) {
            // Insert Exercise
            $stmtEx->execute([
                $ex['id'], 
                $data['session_id'], 
                $ex['exercise_name'], 
                $ex['good_reps'] ?? 0, 
                $ex['bad_reps'] ?? 0, 
                $ex['exercise_score'] ?? 0
            ]);

            // Insert Individual Reps
            if (!empty($ex['reps'])) {
                foreach ($ex['reps'] as $index => $score) {
                    $repId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                    
                    $stmtRep->execute([
                        $repId,
                        $ex['id'],
                        $index + 1, // rep_number
                        $score
                    ]);
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Session synchronized successfully.']);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("SYNC ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database transaction failed. Check error logs.']);
}
?>