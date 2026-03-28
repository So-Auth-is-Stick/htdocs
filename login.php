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

$sql = "SELECT id, username, password, email, first_name, last_name, birthday, created_at
        FROM users
        WHERE BINARY username = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database preparation failed."]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        unset($user['password']); // Hide password hash

        // --- NEW: GENERATE AND SAVE AUTH TOKEN ---
        $token = bin2hex(random_bytes(32)); // Creates a secure 64-character string
        
        $updateTokenSql = "UPDATE users SET auth_token = ? WHERE id = ?";
        $tokenStmt = $conn->prepare($updateTokenSql);
        $tokenStmt->bind_param("si", $token, $user['id']);
        $tokenStmt->execute();
        $tokenStmt->close();

        $user['auth_token'] = $token; // Send token back to Flutter
        // -----------------------------------------

        echo json_encode([
            "status" => "success",
            "message" => "Login successful.",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
}

$stmt->close();
$conn->close();
?>