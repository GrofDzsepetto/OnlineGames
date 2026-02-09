<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

session_start();
header("Content-Type: application/json; charset=utf-8");

$quizId = $_GET["quiz_id"] ?? null;

if (!$quizId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz_id"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔒 Ellenőrizzük, hogy a user láthatja-e a quizt
    $quizStmt = $pdo->prepare("
        SELECT ID, IS_PUBLIC, CREATED_BY
        FROM QUIZ
        WHERE ID = ?
        LIMIT 1
    ");
    $quizStmt->execute([$quizId]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isOwner = ((string)$quiz["CREATED_BY"] === (string)$userId);
    $isPublic = ((int)$quiz["IS_PUBLIC"] === 1);

    if (!$isPublic && !$isOwner) {
        // viewer email check később ide jöhet
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod az eredményekhez"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🏆 Leaderboard lekérdezés
    $stmt = $pdo->prepare("
        SELECT
            U.ID AS USER_ID,
            U.NAME AS USER_NAME,
            QA.SCORE,
            QA.MAX_SCORE,
            QA.CREATED_AT
        FROM QUIZ_ATTEMPT QA
        JOIN USERS U ON U.ID = QA.USER_ID
        WHERE QA.QUIZ_ID = ?
        ORDER BY QA.SCORE DESC, QA.CREATED_AT ASC
    ");
    $stmt->execute([$quizId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "quiz_id" => $quizId,
        "results" => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB error",
        "details" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
