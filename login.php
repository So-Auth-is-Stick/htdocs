<?php
header("Content-Type: application/json");
require_once "db.php";

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";

if (empty($username) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Username and password are required."
    ]);
    exit;
}

// BINARY forces case-sensitivity on the username search
$sql = "SELECT id, username, password, email, first_name, last_name, birthday, created_at
        FROM users
        WHERE BINARY username = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // SECURE: Compare the typed password against the hashed password in the DB
    if (password_verify($password, $user['password'])) {
        
        // Remove the password hash from the array before sending it to the app
        unset($user['password']);

        echo json_encode([
            "status" => "success",
            "message" => "Login successful.",
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid username or password."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password."
    ]);
}

$stmt->close();
$conn->close();
?>