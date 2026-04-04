<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "db.php";

$userId = $_POST['user_id'] ?? null;
$sessionId = $_POST['session_id'] ?? null;
$exerciseName = $_POST['exercise_name'] ?? null;
$routineId = $_POST['routine_id'] ?? null;
$sessionType = $_POST['session_type'] ?? 'ai';

if (!$userId || !$sessionId || !$exerciseName) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields."
    ]);
    exit();
}

if (!isset($_FILES['video'])) {
    echo json_encode([
        "status" => "error",
        "message" => "No video uploaded."
    ]);
    exit();
}

$file = $_FILES['video'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "Upload failed.",
        "debug" => $file['error']
    ]);
    exit();
}

$safeExerciseName = strtolower($exerciseName);
$safeExerciseName = preg_replace('/[^a-z0-9_ -]/i', '', $safeExerciseName);
$safeExerciseName = str_replace(' ', '_', $safeExerciseName);

$uploadDir = "uploads/ai_videos/user_{$userId}/{$sessionId}/{$safeExerciseName}/";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create upload directory."
        ]);
        exit();
    }
}

$originalName = basename($file['name']);
$fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
$filename = time() . '_' . uniqid() . ($fileExtension ? '.' . $fileExtension : '');
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save video."
    ]);
    exit();
}

try {
    $conn->beginTransaction();

    // Check if session already exists
    $checkSession = $conn->prepare("
        SELECT id
        FROM workout_sessions
        WHERE id = ?
        LIMIT 1
    ");
    $checkSession->execute([$sessionId]);
    $existingSession = $checkSession->fetch(PDO::FETCH_ASSOC);

    // Create session if missing
    if (!$existingSession) {
        $createSession = $conn->prepare("
            INSERT INTO workout_sessions
            (
                id,
                user_id,
                routine_id,
                status,
                global_score,
                duration_seconds,
                created_at,
                session_type
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                ?
            )
        ");

        $createSession->execute([
            $sessionId,
            $userId,
            !empty($routineId) ? $routineId : null,
            'pending',
            null,
            null,
            $sessionType
        ]);
    }

    // Insert uploaded video job
    $stmt = $conn->prepare("
        INSERT INTO exercise_videos
        (
            user_id,
            workout_session_id,
            exercise_name,
            file_name,
            file_path,
            uploaded_at,
            processing_status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            'pending'
        )
    ");

    $stmt->execute([
        $userId,
        $sessionId,
        $exerciseName,
        $filename,
        $targetPath
    ]);

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Video uploaded successfully.",
        "file_name" => $filename,
        "file_path" => $targetPath,
        "session_created" => !$existingSession
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("UPLOAD ERROR: " . $e->getMessage());

    echo json_encode([
        "status" => "error",
        "message" => "Database error.",
        "debug" => $e->getMessage()
    ]);
}
?>