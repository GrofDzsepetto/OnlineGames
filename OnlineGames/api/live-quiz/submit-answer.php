<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$data = read_json_body();

$playerId = (int)($data["player_id"] ?? 0);
$answerId = trim((string)($data["answer_id"] ?? ""));
$pin = trim((string)($data["pin"] ?? ""));

if ($playerId <= 0 || $answerId === "" || $pin === "") {
    json_error("Missing data", 400);
}

try {
    $stmt = $pdo->prepare("
        select question_id, is_correct
        from answer_option
        where id = ?
        limit 1
    ");
    $stmt->execute([$answerId]);
    $answer = $stmt->fetch();

    if (!$answer) {
        json_error("Answer not found", 404);
    }

    $questionId = (string)$answer["question_id"];
    $isCorrect = (int)$answer["is_correct"];

    $stmt = $pdo->prepare("
        select id
        from game_answers
        where game_id = ?
        and player_id = ?
        and question_id = ?
        limit 1
    ");
    $stmt->execute([
        $pin,
        $playerId,
        $questionId
    ]);

    if ($stmt->fetch()) {
        json_success([
            "duplicate" => true
        ]);
    }

    $stmt = $pdo->prepare("
        select question_started_at
        from game_sessions
        where id = ?
        limit 1
    ");
    $stmt->execute([$pin]);
    $game = $stmt->fetch();

    if (!$game) {
        json_error("Game not found", 404);
    }

    $startedAt = $game["question_started_at"] ?? null;

    $stmt = $pdo->prepare("
        insert into game_answers
            (game_id, player_id, question_id, answer_id, is_correct, started_at, answered_at)
        values
            (?, ?, ?, ?, ?, ?, now())
    ");
    $stmt->execute([
        $pin,
        $playerId,
        $questionId,
        $answerId,
        $isCorrect,
        $startedAt
    ]);

    json_success([
        "duplicate" => false,
        "correct" => (bool)$isCorrect
    ]);

} catch (Throwable $e) {
    app_log_exception("SUBMIT ANSWER ERROR", $e);
    json_error("Nem sikerült menteni a választ.", 500);
}