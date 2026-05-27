<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$pin = trim((string)($_GET["pin"] ?? ""));

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

    $stmt = $pdo->prepare("
        select 
            gp.id,
            gp.name,
            coalesce(sum(
                case 
                    when ga.is_correct = 1 and ga.started_at is not null then 
                        1000 - least(
                            timestampdiff(second, ga.started_at, ga.answered_at) * 100,
                            800
                        )
                    else 0
                end
            ), 0) as score
        from game_players gp
        left join game_answers ga
            on gp.id = ga.player_id
            and gp.game_id = ga.game_id
        where gp.game_id = ?
        group by gp.id, gp.name
        order by score desc
    ");
    $stmt->execute([$pin]);
    $players = $stmt->fetchAll();

    $question = null;
    $answeredCount = 0;

    if ((string)$game["state"] === "playing") {
        $index = max(0, (int)$game["current_question_index"]);

        $stmt = $pdo->prepare("
            select id, question_text, type
            from question
            where quiz_id = ?
            order by order_index asc
            limit 1 offset $index
        ");
        $stmt->execute([$game["quiz_id"]]);
        $currentQuestion = $stmt->fetch();

        if ($currentQuestion) {
            $stmt = $pdo->prepare("
                select id, label
                from answer_option
                where question_id = ?
                order by order_index asc
            ");
            $stmt->execute([$currentQuestion["id"]]);
            $answerRows = $stmt->fetchAll();

            $answers = array_map(function ($answer) {
                return [
                    "id" => $answer["id"],
                    "text" => $answer["label"],
                ];
            }, $answerRows);

            $question = [
                "id" => $currentQuestion["id"],
                "text" => $currentQuestion["question_text"],
                "type" => $currentQuestion["type"],
                "answers" => $answers,
            ];

            $stmt = $pdo->prepare("
                select count(distinct player_id)
                from game_answers
                where game_id = ?
                and question_id = ?
            ");
            $stmt->execute([
                $pin,
                $currentQuestion["id"]
            ]);

            $answeredCount = (int)$stmt->fetchColumn();
        }
    }

    json_success([
        "game" => [
            "state" => $game["state"],
            "current_question_index" => (int)$game["current_question_index"],
        ],
        "players" => $players,
        "question" => $question,
        "answers_count" => $answeredCount,
    ]);

} catch (Throwable $e) {
    app_log_exception("GET GAME STATE ERROR", $e);
    json_error("Nem sikerült betölteni a játék állapotát.", 500);
}