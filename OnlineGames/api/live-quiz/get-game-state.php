<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$pin = $_GET['pin'];

// game
$stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE id = ?");
$stmt->execute([$pin]);
$game = $stmt->fetch();

// players
$stmt = $pdo->prepare("
    SELECT id, name, score 
    FROM game_players 
    WHERE game_id = ?
    ORDER BY score DESC
");
$stmt->execute([$pin]);
$players = $stmt->fetchAll();

// current question
$stmt = $pdo->prepare("
    SELECT *
    FROM QUESTION
    WHERE quiz_id = ?
    ORDER BY order_index
");
$stmt->execute([$game['quiz_id']]);
$questions = $stmt->fetchAll();

$currentQuestion = $questions[$game['current_question_index']] ?? null;

echo json_encode([
    "game" => $game,
    "players" => $players,
    "question" => $currentQuestion
]);