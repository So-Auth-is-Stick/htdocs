<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php";

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// 1. THE BOUNCER: Check for the token
if (empty($data["auth_token"]) || empty($data["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Missing token."]);
    exit;
}

$authToken = $data["auth_token"];
$userId = $data["user_id"];

// 2. VERIFY TOKEN AGAINST DATABASE
$verifySql = "SELECT id FROM users WHERE id = ? AND auth_token = ?";
$verifyStmt = $conn->prepare($verifySql);
$verifyStmt->bind_param("is", $userId, $authToken);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();

if ($verifyResult->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid or expired token."]);
    exit;
}
$verifyStmt->close();

// 3. IF TOKEN IS VALID, PROCEED WITH SAVING THE WORKOUT
$sessionId = $data["session_id"];
$routineId = $data["routine_id"] ?? null;
$status = $data["status"];
$globalScore = $data["global_score"];
$durationSeconds = $data["duration_seconds"];

// Insert Session
$sessionSql = "INSERT INTO workout_sessions (id, user_id, routine_id, status, global_score, duration_seconds) VALUES (?, ?, ?, ?, ?, ?)";
$sessionStmt = $conn->prepare($sessionSql);
$sessionStmt->bind_param("sissii", $sessionId, $userId, $routineId, $status, $globalScore, $durationSeconds);

try {
    $sessionStmt->execute();
    $sessionStmt->close();

    // Insert Exercises (Child Rows)
    if (!empty($data["exercises"])) {
        $exSql = "INSERT INTO exercise_telemetry (id, session_id, exercise_name, good_reps, bad_reps, exercise_score, rep_scores_array) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $exStmt = $conn->prepare($exSql);

        foreach ($data["exercises"] as $ex) {
            // Re-encode the array to a string for MySQL
            $repScoresStr = json_encode($ex["rep_scores"]);
            $exStmt->bind_param("sssiiis", $ex["telemetry_id"], $sessionId, $ex["exercise_name"], $ex["good_reps"], $ex["bad_reps"], $ex["exercise_score"], $repScoresStr);
            $exStmt->execute();
        }
        $exStmt->close();
    }

    echo json_encode(["status" => "success", "message" => "Session synced securely."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Failed to save session."]);
}

$conn->close();
?>