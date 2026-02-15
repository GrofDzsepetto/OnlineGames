<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$slug = $_GET["slug"] ?? null;

if (!$slug) {
    http_response_code(400);
    echo json_encode(["error" => "Missing slug"], JSON_UNESCAPED_UNICODE);
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

    // 1️⃣ Slug alapján quiz lekérés
    $quizStmt = $pdo->prepare("
        SELECT id, is_public, created_by
        FROM quiz
        WHERE slug = ?
        LIMIT 1
    ");
    $quizStmt->execute([$slug]);
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

    // 2️⃣ Leaderboard lekérés SLUG alapján
    $stmt = $pdo->prepare("
        SELECT
            u.id AS USER_ID,
            u.name AS USER_NAME,
            qa.score AS SCORE,
            qa.max_score AS MAX_SCORE,
            qa.created_at AS CREATED_AT
        FROM quiz_attempt qa
        JOIN users u ON u.id = qa.user_id
        WHERE qa.quiz_slug = ?
        ORDER BY qa.score DESC, qa.created_at ASC
    ");
    $stmt->execute([$slug]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "slug" => $slug,
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
