<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$userId = require_user_id();
$data = read_json_body();

$quizId = trim((string)($data["quiz_id"] ?? ""));

if ($quizId === "") {
    json_error("Missing quiz_id", 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("select created_by from quiz where id = ? limit 1");
    $stmt->execute([$quizId]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        json_error("Quiz not found", 404);
    }

    if ((string)$row["created_by"] !== $userId) {
        $pdo->rollBack();
        json_error("Nincs jogosultságod törölni ezt a kvízt.", 403);
    }

    $qStmt = $pdo->prepare("select id, type from question where quiz_id = ?");
    $qStmt->execute([$quizId]);
    $questions = $qStmt->fetchAll();

    $delAnswer = $pdo->prepare("delete from answer_option where question_id = ?");
    $delPair = $pdo->prepare("delete from matching_pair where question_id = ?");
    $delLeft = $pdo->prepare("delete from matching_left_item where question_id = ?");
    $delRight = $pdo->prepare("delete from matching_right_item where question_id = ?");
    $delQuestion = $pdo->prepare("delete from question where id = ?");
    $delQuiz = $pdo->prepare("delete from quiz where id = ?");

    foreach ($questions as $question) {
        $questionId = (string)$question["id"];
        $type = (string)$question["type"];

        if ($type === "MATCHING") {
            $delPair->execute([$questionId]);
            $delLeft->execute([$questionId]);
            $delRight->execute([$questionId]);
        } else {
            $delAnswer->execute([$questionId]);
        }

        $delQuestion->execute([$questionId]);
    }

    $delQuiz->execute([$quizId]);

    $pdo->commit();

    json_success();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_log_exception("DELETE QUIZ ERROR:",$e);
    json_error("Nem sikerült törölni a kvízt.", 500);
}