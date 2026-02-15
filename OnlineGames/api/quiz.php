<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$key = $_GET["slug"] ?? null;

if (!$key) {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz slug/id"], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------------------------------------------
   QUIZ LEKÉRÉS (slug VAGY id)
------------------------------------------------------- */

$quizStmt = $pdo->prepare("
    SELECT id, slug, title, description, is_public, created_by
    FROM quiz
    WHERE slug = ? OR id = ?
    LIMIT 1
");

$quizStmt->execute([$key, $key]);
$quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    http_response_code(404);
    echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
    exit;
}

$quizId = $quiz["id"];
$isPublic = ((int)$quiz["is_public"] === 1);
$createdBy = $quiz["created_by"];

/* -------------------------------------------------------
   JOGOSULTSÁG
------------------------------------------------------- */
/* -------------------------------------------------------
   JOGOSULTSÁG
------------------------------------------------------- */

$isCreator = false;
$viewerEmails = [];

if (!$isPublic) {

    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode(
            ["error" => "Bejelentkezés szükséges ehhez a kvízhez."],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $userId = (int)$_SESSION["user_id"];
    $isCreator = ($userId === (int)$createdBy);

    if (!$isCreator) {
        $emailStmt = $pdo->prepare("
            SELECT email
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $emailStmt->execute([$userId]);
        $userEmail = $emailStmt->fetchColumn();

        if (!$userEmail) {
            http_response_code(403);
            echo json_encode(
                ["error" => "Nem sikerült azonosítani a felhasználót."],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        $userEmail = strtolower(trim($userEmail));

        $allowStmt = $pdo->prepare("
            SELECT 1
            FROM quiz_viewer_email
            WHERE quiz_id = ?
            AND LOWER(user_email) = ?
            LIMIT 1
        ");
        $allowStmt->execute([$quizId, $userEmail]);

        if (!$allowStmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(
                ["error" => "Nincs jogosultságod ehhez a kvízhez."],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
    }

    if ($isCreator) {
        $viewersStmt = $pdo->prepare("
            SELECT user_email
            FROM quiz_viewer_email
            WHERE quiz_id = ?
            ORDER BY user_email
        ");
        $viewersStmt->execute([$quizId]);
        $viewerEmails = $viewersStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

/* -------------------------------------------------------
   QUESTIONS
------------------------------------------------------- */

$questionStmt = $pdo->prepare("
    SELECT id, question_text, type
    FROM question
    WHERE quiz_id = ?
    ORDER BY order_index, id
");

$questionStmt->execute([$quizId]);
$questions = $questionStmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------------------
   ANSWERS / MATCHING
------------------------------------------------------- */

$answerStmt = $pdo->prepare("
    SELECT label AS answer_text, is_correct
    FROM answer_option
    WHERE question_id = ?
    ORDER BY order_index, id
");

$pairStmt = $pdo->prepare("
    SELECT
        l.id AS left_id,
        l.text AS left_text,
        r.text AS right_text
    FROM matching_left_item l
    LEFT JOIN matching_pair p 
        ON p.left_id = l.id
        AND p.question_id = ?
    LEFT JOIN matching_right_item r 
        ON r.id = p.right_id
    WHERE l.question_id = ?
    ORDER BY l.order_index, r.order_index
");


foreach ($questions as &$q) {

    if ($q["type"] === "MATCHING") {

        $pairStmt->execute([$q["id"], $q["id"]]);
        $rows = $pairStmt->fetchAll(PDO::FETCH_ASSOC);

        $byLeft = [];

        foreach ($rows as $row) {
            $leftId = $row["left_id"];

            if (!isset($byLeft[$leftId])) {
                $byLeft[$leftId] = [
                    "id" => $leftId,
                    "left" => (string)$row["left_text"],
                    "right" => [],
                ];
            }

            if ($row["right_text"] !== null) {
                $byLeft[$leftId]["right"][] = (string)$row["right_text"];
            }

        }

        $q["groups"] = array_values($byLeft);

    } else {

        $answerStmt->execute([$q["id"]]);
        $q["answers"] = $answerStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* -------------------------------------------------------
   RESPONSE
------------------------------------------------------- */

$quiz["questions"] = $questions;
$quiz["is_public"] = $isPublic ? 1 : 0;

if (!$isPublic && $isCreator) {
    $quiz["viewer_emails"] = $viewerEmails;
}

echo json_encode(["quiz" => $quiz], JSON_UNESCAPED_UNICODE);

error_log("QUIZ ID: " . $quizId);
error_log("QUESTIONS COUNT: " . count($questions));
