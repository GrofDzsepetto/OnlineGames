<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$data = read_json_body();

$quizSlug = trim((string)($data["quiz_id"] ?? ""));

if ($quizSlug === "") {
    json_error("Hiányzó quiz_id", 400);
}

try {
    $stmt = $pdo->prepare("
        select id, title
        from quiz
        where slug = ?
        limit 1
    ");
    $stmt->execute([$quizSlug]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        json_error("Quiz nem található", 404);
    }

    do {
        $pin = random_int(100000, 999999);

        $stmt = $pdo->prepare("
            select count(*)
            from game_sessions
            where id = ?
        ");
        $stmt->execute([$pin]);
        $exists = (int)$stmt->fetchColumn();
    } while ($exists > 0);

    $stmt = $pdo->prepare("
        insert into game_sessions (
            id,
            quiz_id,
            state,
            current_question_index,
            created_at
        )
        values (?, ?, 'lobby', 0, now())
    ");

    $stmt->execute([
        $pin,
        $quiz["id"]
    ]);

    json_success([
        "pin" => $pin,
        "quiz_title" => $quiz["title"]
    ]);

} catch (Throwable $e) {
    app_log_exception("CREATE GAME ERROR", $e);
    json_error("Nem sikerült létrehozni a játékot.", 500);
}