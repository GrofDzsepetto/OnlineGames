<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

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

$userId = (string)$_SESSION["user_id"];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $quizStmt = $pdo->prepare("
        select id, is_public, created_by
        from quiz
        where slug = ?
        limit 1
    ");
    $quizStmt->execute([$quizId]);
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isOwner = ((string)$quiz["created_by"] === $userId);
    $isPublic = ((int)$quiz["is_public"] === 1);

    if (!$isPublic && !$isOwner) {
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod az eredményekhez"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        select
            u.id as user_id,
            u.name as user_name,
            qa.score,
            qa.max_score,
            qa.created_at
        from quiz_attempt qa
        join users u on u.id = qa.user_id
        where qa.quiz_id = ?
        order by qa.score desc, qa.created_at asc
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
