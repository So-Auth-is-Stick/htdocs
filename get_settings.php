<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php"; 

// 1. THE BOUNCER
if (empty($_POST["auth_token"]) || empty($_POST["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Missing token."]);
    exit;
}

$authToken = $_POST["auth_token"];
$userId = $_POST["user_id"];

try {
    // 2. VERIFY TOKEN
    $verifySql = "SELECT id FROM users WHERE id = :user_id AND auth_token = :auth_token";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([
        ':user_id' => $userId,
        ':auth_token' => $authToken
    ]);

    if ($verifyStmt->rowCount() === 0) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid token."]);
        exit;
    }

    // 3. FETCH SETTINGS
    $query = "SELECT prep_time, rest_time, voice_enabled, feedback_volume, beeps_volume 
              FROM user_settings WHERE user_id = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $userId]);

    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($settings) {
        echo json_encode([
            "status" => "success", 
            "data" => $settings
        ]);
    } else {
        // If they have no saved settings, return a specific flag so the app uses its defaults
        echo json_encode([
            "status" => "empty", 
            "message" => "No settings found for this user."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>