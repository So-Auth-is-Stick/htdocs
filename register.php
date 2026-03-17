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

// SECURE: Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, email, first_name, last_name, birthday)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $username, $hashedPassword, $email, $firstName, $lastName, $birthday);

try {
    $stmt->execute();
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully."
    ]);
} catch (mysqli_sql_exception $e) {
    // 1062 is the specific MySQL code for a Duplicate Entry
    if ($e->getCode() == 1062) {
        echo json_encode([
            "status" => "error",
            "message" => "Username is already taken. Please choose another."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Database error occurred."
        ]);
    }
}

$stmt->close();
$conn->close();
?>