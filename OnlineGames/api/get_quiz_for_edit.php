<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$key = trim((string)($_GET["id"] ?? $_GET["slug"] ?? ""));

if ($key === "") {
    json_error("Missing id/slug", 400);
}

$userId = require_user_id();

try {
    $quizStmt = $pdo->prepare("
        select id, slug, title, description, is_public, created_by
        from quiz
        where id = ? or slug = ?
        limit 1
    ");
    $quizStmt->execute([$key, $key]);
    $quiz = $quizStmt->fetch();

    if (!$quiz) {
        json_error("Quiz not found", 404);
    }

    if ((string)$quiz["created_by"] !== (string)$userId) {
        json_error("Nincs jogosultságod ehhez a kvízhez.", 403);
    }

    $quizId = (string)$quiz["id"];
    $isPublic = ((int)$quiz["is_public"] === 1);

    $viewerEmails = [];

    if (!$isPublic) {
        $viewersStmt = $pdo->prepare("
            select user_email
            from quiz_viewer_email
            where quiz_id = ?
            order by user_email
        ");
        $viewersStmt->execute([$quizId]);

        $viewerEmails = array_values(array_unique(array_filter(array_map(
            fn($email) => strtolower(trim((string)$email)),
            $viewersStmt->fetchAll(PDO::FETCH_COLUMN)
        ))));
    }

    $questionStmt = $pdo->prepare("
        select id, question_text, type, order_index
        from question
        where quiz_id = ?
        order by order_index, id
    ");
    $questionStmt->execute([$quizId]);
    $questionRows = $questionStmt->fetchAll();

    $answerStmt = $pdo->prepare("
        select label, is_correct, order_index
        from answer_option
        where question_id = ?
        order by order_index, id
    ");

    $pairStmt = $pdo->prepare("
        select
            l.text as left_text,
            l.order_index as left_order,
            r.text as right_text,
            r.order_index as right_order
        from matching_pair p
        join matching_left_item l on l.id = p.left_id
        join matching_right_item r on r.id = p.right_id
        where p.question_id = ?
        order by l.order_index, r.order_index, p.id
    ");

    $questions = [];

    foreach ($questionRows as $questionRow) {
        $questionId = (string)$questionRow["id"];
        $type = (string)$questionRow["type"];
        $questionText = (string)$questionRow["question_text"];

        if ($type === "MULTIPLE_CHOICE") {
            $answerStmt->execute([$questionId]);
            $answerRows = $answerStmt->fetchAll();

            $answers = [];

            foreach ($answerRows as $answerRow) {
                $answers[] = [
                    "text" => (string)$answerRow["label"],
                    "isCorrect" => ((int)$answerRow["is_correct"] === 1),
                ];
            }

            $questions[] = [
                "type" => "MULTIPLE_CHOICE",
                "question" => $questionText,
                "answers" => $answers,
                "pairs" => [],
            ];

            continue;
        }

        if ($type === "MATCHING") {
            $pairStmt->execute([$questionId]);
            $pairRows = $pairStmt->fetchAll();

            $byLeft = [];

            foreach ($pairRows as $pairRow) {
                $leftText = (string)$pairRow["left_text"];
                $rightText = (string)$pairRow["right_text"];

                if (!isset($byLeft[$leftText])) {
                    $byLeft[$leftText] = [];
                }

                $byLeft[$leftText][] = $rightText;
            }

            $pairs = [];

            foreach ($byLeft as $left => $rights) {
                $pairs[] = [
                    "left" => $left,
                    "rights" => array_values($rights),
                ];
            }

            $questions[] = [
                "type" => "MATCHING",
                "question" => $questionText,
                "answers" => [],
                "pairs" => $pairs,
            ];
        }
    }

    json_success([
        "quiz" => [
            "quiz_id" => $quizId,
            "slug" => (string)$quiz["slug"],
            "title" => (string)$quiz["title"],
            "description" => (string)($quiz["description"] ?? ""),
            "isPublic" => $isPublic,
            "viewerEmails" => $viewerEmails,
            "questions" => $questions,
        ]
    ]);

} catch (Throwable $e) {
    app_log_exception("GET QUIZ FOR EDIT ERROR: ", $e);

    json_error("Nem sikerült betölteni a szerkesztendő kvízt.", 500);
}