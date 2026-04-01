<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php"; 

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

$username = $data["username"] ?? $_POST["username"] ?? "";
$password = $data["password"] ?? $_POST["password"] ?? "";

if (empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Username and password are required."]);
    exit;
}

try {
    // Note: Column is 'password', matching your schema exactly
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(32));

        $updateStmt = $conn->prepare("UPDATE users SET auth_token = :token WHERE id = :id");
        $updateStmt->execute([
            ':token' => $token,
            ':id' => $user['id']
        ]);

        // Payload perfectly matches what Flutter's _saveLogin expects
        echo json_encode([
            "status" => "success",
            "message" => "Login successful.",
            "user" => [
                "id" => (int)$user['id'],
                "username" => $user['username'],
                "auth_token" => $token
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error."]);
}
?>