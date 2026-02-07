<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

session_start();
header("Content-Type: application/json; charset=utf-8");

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
    return (string)$pdo->query("SELECT UUID()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // ✅ Quiz meta
    $quizStmt = $pdo->prepare("
        SELECT ID, SLUG, TITLE, DESCRIPTION, IS_PUBLIC, CREATED_BY
        FROM QUIZ
        WHERE ID = ? OR SLUG = ?
        LIMIT 1
    ");
    $quizStmt->execute([$key, $key]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((string)$quiz["CREATED_BY"] !== $userId) {
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod ehhez a kvízhez."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $quizId = (string)$quiz["ID"];

    // ✅ biztos bool: csak 1 esetén public
    $isPublic = ((int)$quiz["IS_PUBLIC"] === 1);

    // ✅ Viewer emails (PRIVATE esetén)
    $viewerEmails = [];
    if (!$isPublic) {
        $viewersStmt = $pdo->prepare("
            SELECT USER_EMAIL
            FROM QUIZ_VIEWER_EMAIL
            WHERE QUIZ_ID = ?
            ORDER BY USER_EMAIL
        ");
        $viewersStmt->execute([$quizId]);
        $viewerEmails = $viewersStmt->fetchAll(PDO::FETCH_COLUMN);

        // tisztítás (ha volt whitespace / nagybetű)
        $clean = [];
        foreach ($viewerEmails as $e) {
            $e = strtolower(trim((string)$e));
            if ($e !== "") $clean[] = $e;
        }
        $viewerEmails = array_values(array_unique($clean));
    }

    // ✅ Questions
    $questionStmt = $pdo->prepare("
        SELECT ID, QUESTION_TEXT, TYPE, ORDER_INDEX
        FROM QUESTION
        WHERE QUIZ_ID = ?
        ORDER BY ORDER_INDEX, ID
    ");
    $questionStmt->execute([$quizId]);
    $questionsRows = $questionStmt->fetchAll(PDO::FETCH_ASSOC);

    $answerStmt = $pdo->prepare("
        SELECT LABEL, IS_CORRECT, ORDER_INDEX
        FROM ANSWER_OPTION
        WHERE QUESTION_ID = ?
        ORDER BY ORDER_INDEX, ID
    ");

    $pairStmt = $pdo->prepare("
        SELECT
            L.TEXT AS LEFT_TEXT,
            L.ORDER_INDEX AS LEFT_ORDER,
            R.TEXT AS RIGHT_TEXT,
            R.ORDER_INDEX AS RIGHT_ORDER
        FROM MATCHING_PAIR P
        JOIN MATCHING_LEFT_ITEM L ON L.ID = P.LEFT_ID
        JOIN MATCHING_RIGHT_ITEM R ON R.ID = P.RIGHT_ID
        WHERE P.QUESTION_ID = ?
        ORDER BY L.ORDER_INDEX, R.ORDER_INDEX, P.ID
    ");

    $questions = [];

    foreach ($questionsRows as $qr) {
        $qid = (string)$qr["ID"];
        $type = (string)$qr["TYPE"];
        $qText = (string)$qr["QUESTION_TEXT"];

        if ($type === "MULTIPLE_CHOICE") {
            $answerStmt->execute([$qid]);
            $ansRows = $answerStmt->fetchAll(PDO::FETCH_ASSOC);

            $answers = [];
            foreach ($ansRows as $a) {
                $answers[] = [
                    "text" => (string)$a["LABEL"],
                    "isCorrect" => ((int)$a["IS_CORRECT"] === 1),
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

            // LEFT_TEXT -> rights[]
            $byLeft = [];

            foreach ($rows as $row) {
                $leftText = (string)$row["LEFT_TEXT"];
                $rightText = (string)$row["RIGHT_TEXT"];

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
        "slug" => (string)$quiz["SLUG"],
        "title" => (string)$quiz["TITLE"],
        "description" => (string)($quiz["DESCRIPTION"] ?? ""),
        "isPublic" => $isPublic,
        "viewerEmails" => $viewerEmails,
        "questions" => $questions
    ];

    if ($DEBUG) {
        $out["DEBUG"] = [
            "KEY" => $key,
            "USER_ID" => $userId,
            "IS_PUBLIC_DB_RAW" => $quiz["IS_PUBLIC"],
            "IS_PUBLIC_BOOL" => $isPublic,
            "VIEWER_EMAILS_COUNT" => count($viewerEmails),
            "VIEWER_EMAILS" => $viewerEmails
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
