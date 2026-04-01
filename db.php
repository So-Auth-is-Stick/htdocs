<?php
// db.php
$host = 'localhost'; // Change if your DB is hosted elsewhere
$db   = 'bettergym_db';
$user = 'root'; // Replace with your actual DB username
$pass = '';     // Replace with your actual DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Forces strict typing and true prepared statements
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a true production environment, log this to a file, do not echo to the client.
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}
?>