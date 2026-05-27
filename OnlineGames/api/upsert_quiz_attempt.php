<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$userId = (int)require_user_id();
$data = read_json_body();

$quizSlug = trim((string)($data["quiz_slug"] ?? ""));
$score = $data["score"] ?? null;
$maxScore = $data["max_score"] ?? null;

if ($quizSlug === "" || !is_numeric($score) || !is_numeric($maxScore)) {
    json_error("Missing or invalid fields", 400);
}

$score = (int)$score;
$maxScore = (int)$maxScore;

if ($score < 0 || $maxScore <= 0 || $score > $maxScore) {
    json_error("Invalid score values", 400);
}

try {
    $stmt = $pdo->prepare("select id from quiz where slug = ? limit 1");
    $stmt->execute([$quizSlug]);
    $quizId = $stmt->fetchColumn();

    if (!$quizId) {
        json_error("Quiz not found", 404);
    }

    $stmt = $pdo->prepare("
        insert into quiz_attempt (
            id,
            quiz_id,
            user_id,
            score,
            max_score,
            created_at
        ) values (
            uuid(), ?, ?, ?, ?, now()
        )
        on duplicate key update
            score = if(values(score) > score, values(score), score),
            max_score = if(values(score) > score, values(max_score), max_score),
            created_at = if(values(score) > score, now(), created_at)
    ");

    $stmt->execute([
        $quizId,
        $userId,
        $score,
        $maxScore
    ]);

    json_success([
        "quiz_id" => $quizId,
        "user_id" => $userId,
        "score" => $score,
        "max_score" => $maxScore
    ]);

} catch (Throwable $e) {
    error_log("QUIZ ATTEMPT ERROR: " . $e->getMessage());
    app_log_exception("QUIZ ATTEMPT ERROR: ", $e);

    json_error("Nem sikerült menteni az eredményt.", 500);
}