<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php"; 

// 1. EXTRACT FORM DATA (Expected from Dart's application/x-www-form-urlencoded)
$userId = $_POST['user_id'] ?? null;
$authToken = $_POST['auth_token'] ?? null;
$prep_time = $_POST['prep_time'] ?? 30;
$rest_time = $_POST['rest_time'] ?? 30;
$voice_enabled = $_POST['voice_enabled'] ?? 1;
$feedback_volume = $_POST['feedback_volume'] ?? 1.0;
$beeps_volume = $_POST['beeps_volume'] ?? 1.0;

// 2. VALIDATE CREDENTIALS
if (!$userId || !$authToken) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Missing credentials."]);
    exit();
}

try {
    // 3. UPSERT THE SETTINGS
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
        // Update bindings for ON DUPLICATE KEY
        ':prep_time_update' => $prep_time,
        ':rest_time_update' => $rest_time,
        ':voice_update' => $voice_enabled,
        ':feedback_update' => $feedback_volume,
        ':beeps_update' => $beeps_volume
    ]);

    echo json_encode(["status" => "success", "message" => "Settings updated successfully."]);
} catch (PDOException $e) {
    error_log("SETTINGS SYNC ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database failure."]);
}
?>