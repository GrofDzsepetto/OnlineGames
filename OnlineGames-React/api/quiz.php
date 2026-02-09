<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

session_start();
header("Content-Type: application/json; charset=utf-8");

$key = $_GET["slug"] ?? null;

if (!$key) {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz slug/id"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Quiz meta: IS_PUBLIC + CREATED_BY is kell
$quizStmt = $pdo->prepare("
    SELECT ID, TITLE, DESCRIPTION, IS_PUBLIC, CREATED_BY
    FROM QUIZ
    WHERE SLUG = ? OR ID = ?
    LIMIT 1
");
$quizStmt->execute([$key, $key]);
$quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    http_response_code(404);
    echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
    exit;
}

$quizId = (string)$quiz["ID"];
$isPublic = !empty($quiz["IS_PUBLIC"]) ? 1 : 0;
$createdBy = (string)$quiz["CREATED_BY"];

// ✅ Jogosultság ellenőrzés PRIVATE esetén
$isCreator = false;
$viewerEmails = [];

if ($isPublic === 0) {
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode(["error" => "Bejelentkezés szükséges!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = (string)$_SESSION["user_id"];
    $isCreator = ($userId === $createdBy);

    // lekérjük a user emailt
    $emailStmt = $pdo->prepare("
        SELECT EMAIL
        FROM USERS
        WHERE ID = ?
        LIMIT 1
    ");
    $emailStmt->execute([$userId]);
    $userEmail = $emailStmt->fetchColumn();

    if (!$userEmail) {
        http_response_code(403);
        echo json_encode(["error" => "Nem sikerült azonosítani a felhasználót."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userEmail = strtolower(trim((string)$userEmail));

    if (!$isCreator) {
        $allowStmt = $pdo->prepare("
            SELECT 1
            FROM QUIZ_VIEWER_EMAIL
            WHERE QUIZ_ID = ?
            AND USER_EMAIL = ?
            LIMIT 1
        ");
        $allowStmt->execute([$quizId, $userEmail]);
        $allowed = $allowStmt->fetchColumn();

        if (!$allowed) {
            http_response_code(403);
            echo json_encode(["error" => "Nincs jogosultságod ehhez a kvízhez."], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ✅ viewer_emails csak creatornek (edithez)
    if ($isCreator) {
        $viewersStmt = $pdo->prepare("
            SELECT USER_EMAIL
            FROM QUIZ_VIEWER_EMAIL
            WHERE QUIZ_ID = ?
            ORDER BY USER_EMAIL
        ");
        $viewersStmt->execute([$quizId]);
        $viewerEmails = $viewersStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// kérdések
$questionStmt = $pdo->prepare("
    SELECT ID, QUESTION_TEXT, TYPE
    FROM QUESTION
    WHERE QUIZ_ID = ?
    ORDER BY ORDER_INDEX, ID
");
$questionStmt->execute([$quizId]);
$questions = $questionStmt->fetchAll(PDO::FETCH_ASSOC);

$answerStmt = $pdo->prepare("
    SELECT
        LABEL AS ANSWER_TEXT,
        IS_CORRECT
    FROM ANSWER_OPTION
    WHERE QUESTION_ID = ?
    ORDER BY ORDER_INDEX, ID
");

$pairStmt = $pdo->prepare("
    SELECT
        P.LEFT_ID,
        L.TEXT AS LEFT_TEXT,
        P.RIGHT_ID,
        R.TEXT AS RIGHT_TEXT
    FROM MATCHING_PAIR P
    JOIN MATCHING_LEFT_ITEM L ON L.ID = P.LEFT_ID
    JOIN MATCHING_RIGHT_ITEM R ON R.ID = P.RIGHT_ID
    WHERE P.QUESTION_ID = ?
    ORDER BY L.ORDER_INDEX, R.ORDER_INDEX, P.ID
");

foreach ($questions as &$q) {
    if (($q["TYPE"] ?? "") === "MATCHING") {
        $pairStmt->execute([$q["ID"]]);
        $rows = $pairStmt->fetchAll(PDO::FETCH_ASSOC);

        $byLeft = [];

        foreach ($rows as $row) {
            $leftId = $row["LEFT_ID"];
            if (!isset($byLeft[$leftId])) {
                $byLeft[$leftId] = [
                    "ID" => $leftId,
                    "LEFT" => [ (string)$row["LEFT_TEXT"] ],
                    "RIGHT" => [],
                ];
            }
            $byLeft[$leftId]["RIGHT"][] = (string)$row["RIGHT_TEXT"];
        }

        $q["GROUPS"] = array_values($byLeft);
    } else {
        $answerStmt->execute([$q["ID"]]);
        $q["ANSWERS"] = $answerStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$quiz["QUESTIONS"] = $questions;

// ✅ visszaadjuk az IS_PUBLIC-ot is
$quiz["IS_PUBLIC"] = $isPublic;

// ✅ viewer_emails csak creatornek (private esetben)
if ($isPublic === 0 && $isCreator) {
    $quiz["VIEWER_EMAILS"] = $viewerEmails;
}

echo json_encode(["QUIZ" => $quiz], JSON_UNESCAPED_UNICODE);
