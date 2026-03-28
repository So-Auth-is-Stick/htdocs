<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "db.php"; 

// Extract the raw JSON payload from the Flutter app
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Validate payload presence
if (empty($data) || !isset($data['session_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or empty payload."]);
    exit();
}

// 1. THE BOUNCER: Check for the token
if (empty($data["auth_token"]) || empty($data["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Missing token."]);
    exit;
}

$authToken = $data["auth_token"];
$userId = $data["user_id"];

try {
    // 2. VERIFY TOKEN AGAINST DATABASE (Using PDO from db.php)
    $verifySql = "SELECT id FROM users WHERE id = :user_id AND auth_token = :auth_token";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([
        ':user_id' => $userId,
        ':auth_token' => $authToken
    ]);

    if ($verifyStmt->rowCount() === 0) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid or expired token."]);
        exit;
    }

    // 3. IF TOKEN IS VALID, PROCEED WITH UPSERT TRANSACTION
    $conn->beginTransaction();

    // UPSERT THE WORKOUT SESSION
    $session_query = "INSERT INTO workout_sessions 
        (id, user_id, routine_id, status, global_score, duration_seconds) 
        VALUES (:id, :user_id, :routine_id, :status, :global_score, :duration_seconds)
        ON DUPLICATE KEY UPDATE 
        status = :status, 
        global_score = :global_score, 
        duration_seconds = :duration_seconds";
        
    $stmt = $conn->prepare($session_query);
    $stmt->execute([
        ':id' => $data['session_id'],
        ':user_id' => $userId,
        ':routine_id' => !empty($data['routine_id']) ? $data['routine_id'] : null,
        ':status' => $data['status'],
        ':global_score' => $data['global_score'],
        ':duration_seconds' => $data['duration_seconds']
    ]);

    // UPSERT THE EXERCISE TELEMETRY
    if (isset($data['exercises']) && is_array($data['exercises'])) {
        $ex_query = "INSERT INTO exercise_telemetry 
            (id, session_id, exercise_name, good_reps, bad_reps, exercise_score, rep_scores_array) 
            VALUES (:id, :session_id, :exercise_name, :good_reps, :bad_reps, :exercise_score, :rep_scores_array)
            ON DUPLICATE KEY UPDATE 
            good_reps = :good_reps, 
            bad_reps = :bad_reps, 
            exercise_score = :exercise_score, 
            rep_scores_array = :rep_scores_array";
            
        $ex_stmt = $conn->prepare($ex_query);
        
        foreach ($data['exercises'] as $ex) {
            $ex_stmt->execute([
                ':id' => $ex['telemetry_id'],
                ':session_id' => $data['session_id'],
                ':exercise_name' => $ex['exercise_name'],
                ':good_reps' => $ex['good_reps'],
                ':bad_reps' => $ex['bad_reps'],
                ':exercise_score' => $ex['exercise_score'],
                ':rep_scores_array' => json_encode($ex['rep_scores']) 
            ]);
        }
    }

    // Commit the changes to the database
    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Telemetry locked into database."]);

} catch (PDOException $e) {
    // If anything blew up, revert the database to its previous state
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Transaction Failed: " . $e->getMessage()]);
}
?>