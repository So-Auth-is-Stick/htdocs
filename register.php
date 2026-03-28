<?php
// 1. GAG ORDER: Prevent PHP from spitting out HTML warnings
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php";

// 2. THE CATCHER'S MITT: Grab the raw JSON from Flutter
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// 3. Extract the data (with fallback to $_POST for testing tools like Postman)
$username = $data["username"] ?? $_POST["username"] ?? "";
$password = $data["password"] ?? $_POST["password"] ?? "";
$email = $data["email"] ?? $_POST["email"] ?? "";
$firstName = $data["first_name"] ?? $_POST["first_name"] ?? "";
$lastName = $data["last_name"] ?? $_POST["last_name"] ?? "";
$birthday = $data["birthday"] ?? $_POST["birthday"] ?? "";

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

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database preparation failed."]);
    exit;
}

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
            "message" => "Username or email is already taken."
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