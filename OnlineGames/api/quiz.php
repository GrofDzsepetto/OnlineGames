<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$key = trim((string)($_GET["slug"] ?? ""));

if ($key === "") {
    json_error("Missing quiz slug/id", 400);
}

try {
    $quizStmt = $pdo->prepare("
        select id, slug, title, description, is_public, created_by
        from quiz
        where slug = ? or id = ?
        limit 1
    ");

    $quizStmt->execute([$key, $key]);
    $quiz = $quizStmt->fetch();

    if (!$quiz) {
        json_error("Quiz not found", 404);
    }

    $quizId = (string)$quiz["id"];
    $isPublic = ((int)$quiz["is_public"] === 1);
    $createdBy = (string)$quiz["created_by"];

    $isCreator = false;
    $viewerEmails = [];

    if (!$isPublic) {
        $userId = require_user_id();
        $isCreator = ((string)$userId === $createdBy);

        if (!$isCreator) {
            $emailStmt = $pdo->prepare("
                select email
                from users
                where id = ?
                limit 1
            ");
            $emailStmt->execute([$userId]);
            $userEmail = $emailStmt->fetchColumn();

            if (!$userEmail) {
                json_error("Nem sikerült azonosítani a felhasználót.", 403);
            }

            $userEmail = strtolower(trim((string)$userEmail));

            $allowStmt = $pdo->prepare("
                select 1
                from quiz_viewer_email
                where quiz_id = ?
                and lower(user_email) = ?
                limit 1
            ");
            $allowStmt->execute([$quizId, $userEmail]);

            if (!$allowStmt->fetchColumn()) {
                json_error("Nincs jogosultságod ehhez a kvízhez.", 403);
            }
        }

        if ($isCreator) {
            $viewersStmt = $pdo->prepare("
                select user_email
                from quiz_viewer_email
                where quiz_id = ?
                order by user_email
            ");
            $viewersStmt->execute([$quizId]);
            $viewerEmails = $viewersStmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    $questionStmt = $pdo->prepare("
        select id, question_text, type
        from question
        where quiz_id = ?
        order by order_index, id
    ");

    $questionStmt->execute([$quizId]);
    $questions = $questionStmt->fetchAll();

    $answerStmt = $pdo->prepare("
        select label as answer_text, is_correct
        from answer_option
        where question_id = ?
        order by order_index, id
    ");

    $pairStmt = $pdo->prepare("
        select
            l.id as left_id,
            l.text as left_text,
            r.text as right_text
        from matching_left_item l
        left join matching_pair p
            on p.left_id = l.id
            and p.question_id = ?
        left join matching_right_item r
            on r.id = p.right_id
        where l.question_id = ?
        order by l.order_index, r.order_index
    ");

    foreach ($questions as &$question) {
        if ((string)$question["type"] === "MATCHING") {
            $pairStmt->execute([
                $question["id"],
                $question["id"]
            ]);

            $rows = $pairStmt->fetchAll();
            $byLeft = [];

            foreach ($rows as $row) {
                $leftId = (string)$row["left_id"];

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

            $question["groups"] = array_values($byLeft);
        } else {
            $answerStmt->execute([$question["id"]]);
            $question["answers"] = $answerStmt->fetchAll();
        }
    }

    unset($question);

    $quiz["questions"] = $questions;
    $quiz["is_public"] = $isPublic ? 1 : 0;

    if (!$isPublic && $isCreator) {
        $quiz["viewer_emails"] = $viewerEmails;
    }

    json_success([
        "quiz" => $quiz
    ]);

} catch (Throwable $e) {
    app_log_exception("QUIZ LOAD ERROR: ", $e);

    json_error("Nem sikerült betölteni a kvízt.", 500);
}