<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

header("Content-Type: application/json; charset=utf-8");

require __DIR__ . "/db.php";

$key = $_GET["slug"] ?? null;

if (!$key) {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz slug/id"]);
    exit;
}

$quizStmt = $pdo->prepare("
    SELECT ID, TITLE, DESCRIPTION
    FROM QUIZ
    WHERE SLUG = ? OR ID = ?
    LIMIT 1
");
$quizStmt->execute([$key, $key]);
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
    ORDER BY ID
");
$questionStmt->execute([$quiz["ID"]]);
$questions = $questionStmt->fetchAll();

foreach ($questions as &$q) {
    if ($q["TYPE"] === "MATCHING") {

        $groupStmt = $pdo->prepare("
            SELECT ID
            FROM MATCHING_GROUP
            WHERE QUESTION_ID = ?
            ORDER BY COALESCE(ORDER_INDEX, 2147483647), ID
        ");
        $groupStmt->execute([$q["ID"]]);
        $groups = $groupStmt->fetchAll();

        $itemStmt = $pdo->prepare("
            SELECT SIDE, TEXT
            FROM MATCHING_ITEM
            WHERE GROUP_ID = ?
            ORDER BY ORDER_INDEX, ID
        ");

        $q["GROUPS"] = [];

        foreach ($groups as $g) {
            $groupId = $g["ID"];

            $itemStmt->execute([$groupId]);
            $items = $itemStmt->fetchAll();

            $left = [];
            $right = [];

            foreach ($items as $it) {
                if ($it["SIDE"] === "LEFT") {
                    $left[] = $it["TEXT"];
                } else if ($it["SIDE"] === "RIGHT") {
                    $right[] = $it["TEXT"];
                }
            }

            $q["GROUPS"][] = [
                "ID" => $groupId,
                "LEFT" => $left,
                "RIGHT" => $right
            ];
        }

    } else {
        $answerStmt = $pdo->prepare("
            SELECT 
                LABEL AS ANSWER_TEXT,
                IS_CORRECT
            FROM ANSWER_OPTION
            WHERE QUESTION_ID = ?
            ORDER BY ID
        ");
        $answerStmt->execute([$q["ID"]]);
        $q["ANSWERS"] = $answerStmt->fetchAll();
    }
}

$quiz["QUESTIONS"] = $questions;

echo json_encode([
    "QUIZ" => $quiz
]);
