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
$email = $data["email"] ?? $_POST["email"] ?? "";
$firstName = $data["first_name"] ?? $_POST["first_name"] ?? "";
$lastName = $data["last_name"] ?? $_POST["last_name"] ?? "";
$birthday = $data["birthday"] ?? $_POST["birthday"] ?? "";

if (empty($username) || empty($password) || empty($email)) {
    echo json_encode(["status" => "error", "message" => "Username, password, and email are required."]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (username, password, email, first_name, last_name, birthday)
            VALUES (:username, :password, :email, :first_name, :last_name, :birthday)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword,
        ':email' => $email,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':birthday' => $birthday
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully."
    ]);
} catch (PDOException $e) {
    // 23000 is the PDO SQLSTATE code for integrity constraint violations (like Duplicate Entry)
    if ($e->getCode() == 23000) {
        echo json_encode(["status" => "error", "message" => "Username or email is already taken."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error occurred."]);
    }
}
?>