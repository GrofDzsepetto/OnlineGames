<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (
    !is_array($data) ||
    empty($data["pin"]) ||
    empty($data["name"])
) {
    http_response_code(400);
    echo json_encode(["error" => "Missing data"]);
    exit;
}

$pin = (string)$data["pin"];
$name = trim($data["name"]);

// game check
$stmt = $pdo->prepare("
    SELECT id FROM game_sessions WHERE id = ? LIMIT 1
");
$stmt->execute([$pin]);
$game = $stmt->fetch();

if (!$game) {
    http_response_code(404);
    echo json_encode(["error" => "Game not found"]);
    exit;
}

// player insert
$stmt = $pdo->prepare("
    INSERT INTO game_players (game_id, name, score)
    VALUES (?, ?, 0)
");

$stmt->execute([$pin, $name]);

$playerId = $pdo->lastInsertId();

echo json_encode([
    "ok" => true,
    "player_id" => $playerId
]);