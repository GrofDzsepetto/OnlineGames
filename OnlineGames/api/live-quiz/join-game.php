<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$data = read_json_body();

$pin = trim((string)($data["pin"] ?? ""));
$name = trim((string)($data["name"] ?? ""));

if ($pin === "" || $name === "") {
    json_error("Missing data", 400);
}

try {
    $stmt = $pdo->prepare("
        select id
        from game_sessions
        where id = ?
        limit 1
    ");
    $stmt->execute([$pin]);
    $game = $stmt->fetch();

    if (!$game) {
        json_error("Game not found", 404);
    }

    $stmt = $pdo->prepare("
        insert into game_players (game_id, name, score)
        values (?, ?, 0)
    ");
    $stmt->execute([$pin, $name]);

    json_success([
        "player_id" => $pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    app_log_exception("JOIN GAME ERROR", $e);
    json_error("Nem sikerült csatlakozni a játékhoz.", 500);
}