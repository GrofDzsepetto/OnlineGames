<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$slug = trim((string)($_GET["slug"] ?? ""));

if ($slug === "") {
    json_error("Missing slug", 400);
}

$userId = require_user_id();

try {
    $quizStmt = $pdo->prepare("
        select id, is_public, created_by
        from quiz
        where slug = ?
        limit 1
    ");
    $quizStmt->execute([$slug]);
    $quiz = $quizStmt->fetch();

    if (!$quiz) {
        json_error("Quiz not found", 404);
    }

    $quizId = (string)$quiz["id"];
    $isOwner = ((string)$quiz["created_by"] === (string)$userId);
    $isPublic = ((int)$quiz["is_public"] === 1);

    if (!$isPublic && !$isOwner) {
        json_error("Nincs jogosultságod az eredményekhez.", 403);
    }

    $stmt = $pdo->prepare("
        select
            u.id as user_id,
            u.name as user_name,
            qa.score as score,
            qa.max_score as max_score,
            qa.created_at as created_at
        from quiz_attempt qa
        join users u on u.id = qa.user_id
        where qa.quiz_id = ?
        order by qa.score desc, qa.created_at asc
    ");
    $stmt->execute([$quizId]);
    $results = $stmt->fetchAll();

    json_success([
        "slug" => $slug,
        "results" => $results
    ]);

} catch (Throwable $e) {
    app_log_exception("GET RESULTS ERROR: ", $e);

    json_error("Nem sikerült betölteni az eredményeket.", 500);
}