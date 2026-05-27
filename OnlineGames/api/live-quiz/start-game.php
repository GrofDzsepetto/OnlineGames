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
        update game_sessions
        set state = 'playing',
            current_question_index = 0,
            question_started_at = now()
        where id = ?
    ");
    $stmt->execute([$pin]);

    if ($stmt->rowCount() === 0) {
        json_error("Game not found", 404);
    }

    json_success();

} catch (Throwable $e) {
    app_log_exception("START GAME ERROR", $e);
    json_error("Nem sikerült elindítani a játékot.", 500);
}