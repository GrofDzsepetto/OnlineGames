<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$playerId = $_POST['player_id'];
$questionId = $_POST['question_id'];
$answerId = $_POST['answer_id'];
$pin = $_POST['pin'];

// correct?
$stmt = $pdo->prepare("
    SELECT is_correct FROM ANSWER_OPTION WHERE id = ?
");
$stmt->execute([$answerId]);
$isCorrect = $stmt->fetchColumn();

// save
$pdo->prepare("
    INSERT INTO game_answers 
    (game_id, player_id, question_id, answer_id, is_correct)
    VALUES (?, ?, ?, ?, ?)
")->execute([$pin, $playerId, $questionId, $answerId, $isCorrect]);

// score
if ($isCorrect) {
    $pdo->prepare("
        UPDATE game_players 
        SET score = score + 100
        WHERE id = ?
    ")->execute([$playerId]);
}

echo json_encode(["success" => true]);