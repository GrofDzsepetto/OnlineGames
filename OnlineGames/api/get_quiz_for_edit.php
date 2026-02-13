<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$DEBUG = true; // <- ha kész, tedd false-ra

$key = $_GET["id"] ?? $_GET["slug"] ?? null;

if (!$key) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id/slug"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges!"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (string)$_SESSION["user_id"];

function db_uuid(PDO $pdo): string {
    return (string)$pdo->query("select uuid()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $quizStmt = $pdo->prepare("
        select id, slug, title, description, is_public, created_by
        from quiz
        where id = ? or slug = ?
        limit 1
    ");
    $quizStmt->execute([$key, $key]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((string)$quiz["created_by"] !== $userId) {
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod ehhez a kvízhez."], JSON_UNESCAPED_UNICODE);
        exit;
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
        $viewerEmails = $viewersStmt->fetchAll(PDO::FETCH_COLUMN);

        $clean = [];
        foreach ($viewerEmails as $e) {
            $e = strtolower(trim((string)$e));
            if ($e !== "") $clean[] = $e;
        }
        $viewerEmails = array_values(array_unique($clean));
    }

    $questionStmt = $pdo->prepare("
        select id, question_text, type, order_index
        from question
        where quiz_id = ?
        order by order_index, id
    ");
    $questionStmt->execute([$quizId]);
    $questionsRows = $questionStmt->fetchAll(PDO::FETCH_ASSOC);

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

    foreach ($questionsRows as $qr) {
        $qid = (string)$qr["id"];
        $type = (string)$qr["type"];
        $qText = (string)$qr["question_text"];

        if ($type === "MULTIPLE_CHOICE") {
            $answerStmt->execute([$qid]);
            $ansRows = $answerStmt->fetchAll(PDO::FETCH_ASSOC);

            $answers = [];
            foreach ($ansRows as $a) {
                $answers[] = [
                    "text" => (string)$a["label"],
                    "isCorrect" => ((int)$a["is_correct"] === 1),
                ];
            }

            $questions[] = [
                "type" => "MULTIPLE_CHOICE",
                "question" => $qText,
                "answers" => $answers,
                "pairs" => [],
            ];
        }

        if ($type === "MATCHING") {
            $pairStmt->execute([$qid]);
            $rows = $pairStmt->fetchAll(PDO::FETCH_ASSOC);

            $byLeft = [];

            foreach ($rows as $row) {
                $leftText = (string)$row["left_text"];
                $rightText = (string)$row["right_text"];

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
                "question" => $qText,
                "answers" => [],
                "pairs" => $pairs,
            ];
        }
    }

    $out = [
        "quiz_id" => $quizId,
        "slug" => (string)$quiz["slug"],
        "title" => (string)$quiz["title"],
        "description" => (string)($quiz["description"] ?? ""),
        "isPublic" => $isPublic,
        "viewerEmails" => $viewerEmails,
        "questions" => $questions
    ];

    if ($DEBUG) {
        $out["debug"] = [
            "key" => $key,
            "user_id" => $userId,
            "is_public_db_raw" => $quiz["is_public"],
            "is_public_bool" => $isPublic,
            "viewer_emails_count" => count($viewerEmails),
            "viewer_emails" => $viewerEmails
        ];
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB error"], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
