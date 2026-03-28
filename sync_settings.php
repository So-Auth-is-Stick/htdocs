<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php"; 

// 1. THE BOUNCER: Check for the token
if (empty($_POST["auth_token"]) || empty($_POST["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Missing token."]);
    exit;
}

$authToken = $_POST["auth_token"];
$userId = $_POST["user_id"];

try {
    // 2. VERIFY TOKEN AGAINST DATABASE
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

    // 3. EXTRACT SETTINGS PAYLOAD
    $prep_time = $_POST['prep_time'] ?? 30;
    $rest_time = $_POST['rest_time'] ?? 30;
    $voice_enabled = $_POST['voice_enabled'] ?? 1;
    $feedback_volume = $_POST['feedback_volume'] ?? 1.0;
    $beeps_volume = $_POST['beeps_volume'] ?? 1.0;

    // 4. UPSERT THE SETTINGS
    $query = "INSERT INTO user_settings 
        (user_id, prep_time, rest_time, voice_enabled, feedback_volume, beeps_volume) 
        VALUES (:user_id, :prep_time, :rest_time, :voice_enabled, :feedback_volume, :beeps_volume)
        ON DUPLICATE KEY UPDATE 
        prep_time = :prep_time_update, 
        rest_time = :rest_time_update, 
        voice_enabled = :voice_update, 
        feedback_volume = :feedback_update, 
        beeps_volume = :beeps_update";
        
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':prep_time' => $prep_time,
        ':rest_time' => $rest_time,
        ':voice_enabled' => $voice_enabled,
        ':feedback_volume' => $feedback_volume,
        ':beeps_volume' => $beeps_volume,
        // Update bindings
        ':prep_time_update' => $prep_time,
        ':rest_time_update' => $rest_time,
        ':voice_update' => $voice_enabled,
        ':feedback_update' => $feedback_volume,
        ':beeps_update' => $beeps_volume
    ]);

    echo json_encode(["status" => "success", "message" => "Settings synced securely."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>