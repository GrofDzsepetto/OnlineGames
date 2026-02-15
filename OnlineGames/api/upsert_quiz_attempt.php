<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON: " . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    exit;
}

$quizSlug = $data["quiz_slug"] ?? null;
$score    = $data["score"] ?? null;
$maxScore = $data["max_score"] ?? null;

if (!$quizSlug || !is_numeric($score) || !is_numeric($maxScore)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing or invalid fields"], JSON_UNESCAPED_UNICODE);
    exit;
}

$score    = (int)$score;
$maxScore = (int)$maxScore;

if ($score < 0 || $maxScore <= 0 || $score > $maxScore) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid score values"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // 🔎 1️⃣ slug → quiz.id lekérés
    $stmt = $pdo->prepare("SELECT id FROM quiz WHERE slug = ?");
    $stmt->execute([$quizSlug]);
    $quizId = $stmt->fetchColumn();

    if (!$quizId) {
        http_response_code(400);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 💾 2️⃣ insert / update már quiz.id-vel
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempt (
            id,
            quiz_id,
            user_id,
            score,
            max_score,
            created_at
        ) VALUES (
            UUID(), ?, ?, ?, ?, NOW()
        )
        ON DUPLICATE KEY UPDATE
            score = IF(VALUES(score) > score, VALUES(score), score),
            max_score = IF(VALUES(score) > score, VALUES(max_score), max_score),
            created_at = IF(VALUES(score) > score, NOW(), created_at)
    ");

    $stmt->execute([
        $quizId,   // ⚠️ FONTOS: itt már ID megy be
        $userId,
        $score,
        $maxScore
    ]);

    echo json_encode([
        "success"   => true,
        "quiz_id"   => $quizId,
        "user_id"   => $userId,
        "score"     => $score,
        "max_score" => $maxScore
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log("==== QUIZ ATTEMPT ERROR ====");
    error_log("Message: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Code: " . $e->getCode());
    error_log("Trace: " . $e->getTraceAsString());

    http_response_code(500);

    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
