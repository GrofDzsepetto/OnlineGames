<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"], JSON_UNESCAPED_UNICODE);
    exit;
}

$quizId = $data["quiz_id"] ?? null;
$score = $data["score"] ?? null;
$maxScore = $data["max_score"] ?? null;

if (!$quizId || !is_numeric($score) || !is_numeric($maxScore)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing or invalid fields"], JSON_UNESCAPED_UNICODE);
    exit;
}

$score = (int)$score;
$maxScore = (int)$maxScore;

if ($score < 0 || $maxScore <= 0 || $score > $maxScore) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid score values"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $attemptId = $pdo->query("SELECT UUID()")->fetchColumn();

    /*
      Upsert logika:
      - új rekord mindig bekerül
      - update csak akkor, ha az új SCORE nagyobb
    */
    $stmt = $pdo->prepare("
        INSERT INTO QUIZ_ATTEMPT (
            ID,
            QUIZ_ID,
            USER_ID,
            SCORE,
            MAX_SCORE,
            CREATED_AT
        ) VALUES (
            ?, ?, ?, ?, ?, NOW()
        )
        ON DUPLICATE KEY UPDATE
            SCORE = IF(VALUES(SCORE) > SCORE, VALUES(SCORE), SCORE),
            MAX_SCORE = IF(VALUES(SCORE) > SCORE, VALUES(MAX_SCORE), MAX_SCORE),
            CREATED_AT = IF(VALUES(SCORE) > SCORE, NOW(), CREATED_AT)
    ");

    $stmt->execute([
        $attemptId,
        $quizId,
        $userId,
        $score,
        $maxScore
    ]);

    echo json_encode([
        "success" => true,
        "quiz_id" => $quizId,
        "user_id" => $userId,
        "score" => $score,
        "max_score" => $maxScore
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB error",
        "details" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
