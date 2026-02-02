<?php
header("Content-Type: application/json");
require __DIR__ . "/db.php";

$slug = $_GET["slug"] ?? null;

if (!$slug) {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz slug"]);
    exit;
}

$quizStmt = $pdo->prepare("
    SELECT ID, TITLE, DESCRIPTION
    FROM QUIZ
    WHERE SLUG = ?
    LIMIT 1
");
$quizStmt->execute([$slug]);
$quiz = $quizStmt->fetch();

if (!$quiz) {
    http_response_code(404);
    echo json_encode(["error" => "Quiz not found"]);
    exit;
}

$questionStmt = $pdo->prepare("
    SELECT ID, QUESTION_TEXT, TYPE
    FROM QUESTION
    WHERE QUIZ_ID = ?
    ORDER BY ORDER_INDEX
");
$questionStmt->execute([$quiz["ID"]]);
$questions = $questionStmt->fetchAll();

foreach ($questions as &$q) {
    if ($q["TYPE"] !== "MATCHING") {
        $answerStmt = $pdo->prepare("
            SELECT LABEL AS ANSWER_TEXT
            FROM ANSWER_OPTION
            WHERE QUESTION_ID = ?
            ORDER BY ORDER_INDEX
        ");
        $answerStmt->execute([$q["ID"]]);
        $q["ANSWERS"] = $answerStmt->fetchAll();
    }
}

$quiz["QUESTIONS"] = $questions;

echo json_encode([
    "QUIZ" => $quiz
]);
