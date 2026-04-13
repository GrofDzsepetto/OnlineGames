<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

// ========================================
// INPUT
// ========================================
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "error" => "JSON hiba: " . json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !is_array($data) ||
    empty($data["quiz_id"])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Hiányzó quiz_id"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$quizSlug = (string)$data["quiz_id"];

// ========================================
// QUIZ CHECK
// ========================================
$stmt = $pdo->prepare("
    SELECT id, title FROM quiz WHERE slug = ? LIMIT 1
");
$stmt->execute([$quizSlug]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    http_response_code(404);
    echo json_encode([
        "error" => "Quiz nem található"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// PIN GENERÁLÁS
// ========================================
do {
    $pin = rand(100000, 999999);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_sessions WHERE id = ?");
    $stmt->execute([$pin]);
    $exists = $stmt->fetchColumn();

} while ($exists);

// ========================================
// INSERT (csak ami létezik a DB-ben)
// ========================================
$stmt = $pdo->prepare("
    INSERT INTO game_sessions (
        id,
        quiz_id,
        state,
        current_question_index,
        created_at
    )
    VALUES (?, ?, 'lobby', 0, NOW())
");

$stmt->execute([
    $pin,
    $quiz["id"]
]);

// ========================================
// RESPONSE
// ========================================
echo json_encode([
    "ok" => true,
    "pin" => $pin,
    "quiz_title" => $quiz["title"]
], JSON_UNESCAPED_UNICODE);