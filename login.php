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

$sql = "SELECT id, username, email, first_name, last_name, birthday, created_at
        FROM users
        WHERE username = ? AND password = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

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

$stmt->close();
$conn->close();