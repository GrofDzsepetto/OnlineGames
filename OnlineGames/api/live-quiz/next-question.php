<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$pin = $_POST['pin'];

$pdo->prepare("
    UPDATE game_sessions
    SET current_question_index = current_question_index + 1,
        state = 'question',
        question_started_at = NOW()
    WHERE id = ?
")->execute([$pin]);