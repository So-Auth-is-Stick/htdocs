<?php
header('Content-Type: application/json');
require_once 'db.php'; // uses PDO $conn

$response = [
    "status" => "error",
    "message" => "Unknown error"
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    if (
        !isset($_POST['user_id']) ||
        !isset($_POST['session_id']) ||
        !isset($_POST['exercise_name']) ||
        !isset($_FILES['video'])
    ) {
        throw new Exception("Missing required fields.");
    }

    $userId = intval($_POST['user_id']);
    $sessionId = trim($_POST['session_id']);
    $exerciseName = trim($_POST['exercise_name']);
    $videoFile = $_FILES['video'];

    if ($userId <= 0) {
        throw new Exception("Invalid user_id.");
    }

    if ($sessionId === '') {
        throw new Exception("Invalid session_id.");
    }

    if ($exerciseName === '') {
        throw new Exception("Invalid exercise_name.");
    }

    if ($videoFile['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload failed with error code: " . $videoFile['error']);
    }

    $originalName = $videoFile['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($extension !== 'mp4') {
        throw new Exception("Only MP4 files are allowed.");
    }

    $safeSessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sessionId);
    $safeExerciseName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($exerciseName));

    $baseUploadDir = __DIR__ . '/uploads/ai_videos';
    $userDir = $baseUploadDir . '/user_' . $userId;
    $sessionDir = $userDir . '/session_' . $safeSessionId;
    $exerciseDir = $sessionDir . '/' . $safeExerciseName;

    if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0777, true)) {
        throw new Exception("Failed to create base upload directory.");
    }

    if (!is_dir($userDir) && !mkdir($userDir, 0777, true)) {
        throw new Exception("Failed to create user directory.");
    }

    if (!is_dir($sessionDir) && !mkdir($sessionDir, 0777, true)) {
        throw new Exception("Failed to create session directory.");
    }

    if (!is_dir($exerciseDir) && !mkdir($exerciseDir, 0777, true)) {
        throw new Exception("Failed to create exercise directory.");
    }

    $timestamp = date('Ymd_His');
    $finalFileName = $timestamp . '_' . $safeExerciseName . '.mp4';
    $targetPath = $exerciseDir . '/' . $finalFileName;

    if (!move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file.");
    }

    $relativePath = 'uploads/ai_videos/user_' . $userId . '/session_' . $safeSessionId . '/' . $safeExerciseName . '/' . $finalFileName;

    $conn->beginTransaction();

    $checkSessionSql = "SELECT id FROM workout_sessions WHERE id = :session_id LIMIT 1";
    $checkSessionStmt = $conn->prepare($checkSessionSql);
    $checkSessionStmt->execute([
        ':session_id' => $sessionId
    ]);
    $sessionExists = $checkSessionStmt->fetch();

    if (!$sessionExists) {
        $insertSessionSql = "
            INSERT INTO workout_sessions (id, user_id, status, created_at)
            VALUES (:id, :user_id, :status, NOW())
        ";
        $insertSessionStmt = $conn->prepare($insertSessionSql);
        $insertSessionStmt->execute([
            ':id' => $sessionId,
            ':user_id' => $userId,
            ':status' => 'pending'
        ]);
    }

    $insertVideoSql = "
        INSERT INTO exercise_videos (
            user_id,
            workout_session_id,
            exercise_name,
            file_name,
            file_path,
            uploaded_at
        ) VALUES (
            :user_id,
            :workout_session_id,
            :exercise_name,
            :file_name,
            :file_path,
            NOW()
        )
    ";
    $insertVideoStmt = $conn->prepare($insertVideoSql);
    $insertVideoStmt->execute([
        ':user_id' => $userId,
        ':workout_session_id' => $sessionId,
        ':exercise_name' => $exerciseName,
        ':file_name' => $finalFileName,
        ':file_path' => $relativePath
    ]);

    $exerciseVideoId = $conn->lastInsertId();

    $conn->commit();

    $response = [
        "status" => "success",
        "message" => "Video uploaded and saved to database successfully.",
        "exercise_video_id" => $exerciseVideoId,
        "file_name" => $finalFileName,
        "file_path" => $relativePath,
        "user_id" => $userId,
        "session_id" => $sessionId,
        "exercise_name" => $exerciseName
    ];
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(400);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
?>