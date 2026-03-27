<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php";

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (empty($data["auth_token"]) || empty($data["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Missing credentials."]);
    exit;
}

$userId = $data["user_id"];
$authToken = $data["auth_token"];

// 1. VERIFY TOKEN
$verifySql = "SELECT id FROM users WHERE id = ? AND auth_token = ?";
$verifyStmt = $conn->prepare($verifySql);
$verifyStmt->bind_param("is", $userId, $authToken);
$verifyStmt->execute();
if ($verifyStmt->get_result()->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid token."]);
    exit;
}
$verifyStmt->close();

// 2. FETCH SESSIONS
$sessionsSql = "SELECT * FROM workout_sessions WHERE user_id = ? ORDER BY created_at ASC";
$sessionsStmt = $conn->prepare($sessionsSql);
$sessionsStmt->bind_param("i", $userId);
$sessionsStmt->execute();
$sessionsResult = $sessionsStmt->get_result();

$payload = [];

while ($session = $sessionsResult->fetch_assoc()) {
    $sessionId = $session['id'];
    
    // 3. FETCH CHILD TELEMETRY
    $exSql = "SELECT * FROM exercise_telemetry WHERE session_id = ?";
    $exStmt = $conn->prepare($exSql);
    $exStmt->bind_param("s", $sessionId);
    $exStmt->execute();
    $exResult = $exStmt->get_result();
    
    $exercises = [];
    while ($ex = $exResult->fetch_assoc()) {
        // Decode the stringified array back into actual JSON so Flutter can read it
        $ex['rep_scores'] = json_decode($ex['rep_scores_array']);
        unset($ex['rep_scores_array']); // Remove the raw string version
        $exercises[] = $ex;
    }
    $exStmt->close();

    $session['exercises'] = $exercises;
    $payload[] = $session;
}
$sessionsStmt->close();

echo json_encode([
    "status" => "success", 
    "message" => "History retrieved.",
    "sessions" => $payload
]);

$conn->close();
?>