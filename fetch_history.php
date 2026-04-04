<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "db.php";

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON: " . json_last_error_msg()
    ]);
    exit();
}

if (empty($data["auth_token"]) || empty($data["user_id"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or empty payload."
    ]);
    exit();
}

$authToken = $data["auth_token"];
$userId = (int)$data["user_id"];

try {
    $stmtUser = $conn->prepare("
        SELECT id 
        FROM users 
        WHERE id = ? AND auth_token = ? 
        LIMIT 1
    ");
    $stmtUser->execute([$userId, $authToken]);
    $verifiedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$verifiedUser) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Unauthorized. Token invalid or expired."
        ]);
        exit();
    }

    // workout sessions
    $stmtSessions = $conn->prepare("
        SELECT id, user_id, routine_id, status, global_score, duration_seconds, created_at
        FROM workout_sessions
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmtSessions->execute([$userId]);
    $sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

    // exercise telemetry
    $stmtExercises = $conn->prepare("
        SELECT id, session_id, exercise_name, good_reps, bad_reps, exercise_score, rep_scores_array
        FROM exercise_telemetry
        WHERE session_id = ?
    ");

    // rep telemetry
    $stmtReps = $conn->prepare("
        SELECT id, exercise_telemetry_id, rep_number, score
        FROM rep_telemetry
        WHERE exercise_telemetry_id = ?
        ORDER BY rep_number ASC
    ");

    foreach ($sessions as &$session) {
        $stmtExercises->execute([$session['id']]);
        $exercises = $stmtExercises->fetchAll(PDO::FETCH_ASSOC);

        foreach ($exercises as &$exercise) {
            $stmtReps->execute([$exercise['id']]);
            $reps = $stmtReps->fetchAll(PDO::FETCH_ASSOC);

            $exercise['reps_detail'] = $reps;
            $exercise['reps'] = array_map(function($r) {
                return (float)$r['score'];
            }, $reps);
        }

        $session['exercises'] = $exercises;
    }

    // processed_videos
    $stmtProcessed = $conn->prepare("
        SELECT id, user_id, session_id, exercise_name, result_json, model_status, error_message, created_at, sync_status
        FROM processed_videos
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmtProcessed->execute([$userId]);
    $processedVideos = $stmtProcessed->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "sessions" => $sessions,
        "processed_videos" => $processedVideos
    ]);
    exit();

} catch (Exception $e) {
    error_log("FETCH HISTORY ERROR: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch history.",
        "debug" => $e->getMessage()
    ]);
    exit();
}
?>