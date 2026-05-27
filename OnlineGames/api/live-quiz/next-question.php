<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$data = read_json_body();

$pin = trim((string)($data["pin"] ?? ""));

if ($pin === "") {
    json_error("Missing pin", 400);
}

try {
    $stmt = $pdo->prepare("
        select *
        from game_sessions
        where id = ?
        limit 1
    ");
    $stmt->execute([$pin]);
    $game = $stmt->fetch();

    if (!$game) {
        json_error("Game not found", 404);
    }

    $nextIndex = (int)$game["current_question_index"] + 1;

    $stmt = $pdo->prepare("
        select count(*)
        from question
        where quiz_id = ?
    ");
    $stmt->execute([$game["quiz_id"]]);
    $totalQuestions = (int)$stmt->fetchColumn();

    if ($nextIndex >= $totalQuestions) {
        $stmt = $pdo->prepare("
            update game_sessions
            set state = 'finished'
            where id = ?
        ");
        $stmt->execute([$pin]);

        json_success([
            "finished" => true
        ]);
    }

    $stmt = $pdo->prepare("
        update game_sessions
        set current_question_index = ?,
            question_started_at = now()
        where id = ?
    ");
    $stmt->execute([
        $nextIndex,
        $pin
    ]);

    json_success([
        "finished" => false,
        "next_index" => $nextIndex
    ]);

} catch (Throwable $e) {
    app_log_exception("NEXT QUESTION ERROR", $e);
    json_error("Nem sikerült a következő kérdésre lépni.", 500);
}