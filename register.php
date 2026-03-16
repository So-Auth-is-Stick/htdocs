<?php

header("Content-Type: application/json");
require_once "db.php";

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";
$email = $_POST["email"] ?? "";
$firstName = $_POST["first_name"] ?? "";
$lastName = $_POST["last_name"] ?? "";
$birthday = $_POST["birthday"] ?? "";

if (empty($username) || empty($password) || empty($email)) {
    echo json_encode([
        "status" => "error",
        "message" => "Username, password, and email are required."
    ]);
    exit;
}

$sql = "INSERT INTO users (username, password, email, first_name, last_name, birthday)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $username, $password, $email, $firstName, $lastName, $birthday);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();