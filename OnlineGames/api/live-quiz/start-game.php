<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$pin = $_POST['pin'];

$pdo->prepare("
    UPDATE game_sessions
    SET state = 'question',
        current_question_index = 0,
        question_started_at = NOW()
    WHERE id = ?
")->execute([$pin]);

echo json_encode(["success" => true]);