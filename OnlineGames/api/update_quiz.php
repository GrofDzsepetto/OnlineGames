<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$creatorId = require_user_id();
$data = read_json_body();

$language = strtolower(trim((string)($data["language"] ?? "")));
$allowedLanguages = ["hu", "en"];

$quizId = trim((string)($data["quiz_id"] ?? ""));
$title = trim((string)($data["title"] ?? ""));
$questions = $data["questions"] ?? null;

if (
    $quizId === "" ||
    $title === "" ||
    !is_array($questions) ||
    $language === "" ||
    !in_array($language, $allowedLanguages, true)
) {
    json_error("Hiányzó vagy érvénytelen adatok", 400);
}

function db_uuid(PDO $pdo): string {
    return (string)$pdo->query("select uuid()")->fetchColumn();
}

try {
    $pdo->beginTransaction();

    $description = (string)($data["description"] ?? "");
    $isPublic = !empty($data["isPublic"]) ? 1 : 0;
    $viewerEmails = isset($data["viewerEmails"]) && is_array($data["viewerEmails"])
        ? $data["viewerEmails"]
        : [];

    $ownerStmt = $pdo->prepare("select created_by from quiz where id = ? limit 1");
    $ownerStmt->execute([$quizId]);
    $row = $ownerStmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        json_error("Quiz not found", 404);
    }

    if ((string)$row["created_by"] !== (string)$creatorId) {
        $pdo->rollBack();
        json_error("Nincs jogosultságod ehhez a kvízhez.", 403);
    }

    $updateQuiz = $pdo->prepare("
        update quiz
        set title = ?,
            description = ?,
            language_code = ?,
            is_public = ?
        where id = ?
    ");
    $updateQuiz->execute([
        $title,
        $description,
        $language,
        $isPublic,
        $quizId
    ]);

    $pdo->prepare("delete from quiz_viewer_email where quiz_id = ?")
        ->execute([$quizId]);

    $insertViewerEmail = $pdo->prepare("
        insert ignore into quiz_viewer_email (quiz_id, user_email)
        values (?, ?)
    ");

    if ($isPublic === 0) {
        foreach ($viewerEmails as $rawEmail) {
            $email = strtolower(trim((string)$rawEmail));

            if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $insertViewerEmail->execute([$quizId, $email]);
        }

        $creatorEmailStmt = $pdo->prepare("
            select email
            from users
            where id = ?
            limit 1
        ");
        $creatorEmailStmt->execute([$creatorId]);
        $creatorEmail = $creatorEmailStmt->fetchColumn();

        if ($creatorEmail) {
            $insertViewerEmail->execute([
                $quizId,
                strtolower(trim((string)$creatorEmail))
            ]);
        }
    }

    $qidsStmt = $pdo->prepare("select id from question where quiz_id = ?");
    $qidsStmt->execute([$quizId]);
    $questionIds = $qidsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($questionIds)) {
        $placeholders = implode(",", array_fill(0, count($questionIds), "?"));

        $pdo->prepare("delete from matching_pair where question_id in ($placeholders)")
            ->execute($questionIds);

        $pdo->prepare("delete from matching_left_item where question_id in ($placeholders)")
            ->execute($questionIds);

        $pdo->prepare("delete from matching_right_item where question_id in ($placeholders)")
            ->execute($questionIds);

        $pdo->prepare("delete from answer_option where question_id in ($placeholders)")
            ->execute($questionIds);

        $pdo->prepare("delete from question where id in ($placeholders)")
            ->execute($questionIds);
    }

    $insertQuestion = $pdo->prepare("
        insert into question (id, quiz_id, type, question_text, order_index)
        values (?, ?, ?, ?, ?)
    ");

    $insertAnswer = $pdo->prepare("
        insert into answer_option (id, question_id, label, is_correct, order_index)
        values (?, ?, ?, ?, ?)
    ");

    $insertLeft = $pdo->prepare("
        insert into matching_left_item (id, question_id, text, order_index)
        values (?, ?, ?, ?)
    ");

    $insertRight = $pdo->prepare("
        insert into matching_right_item (id, question_id, text, order_index)
        values (?, ?, ?, ?)
    ");

    $insertPair = $pdo->prepare("
        insert into matching_pair (id, question_id, left_id, right_id)
        values (?, ?, ?, ?)
    ");

    $questionOrder = 0;

    foreach ($questions as $question) {
        if (!is_array($question)) {
            continue;
        }

        $type = (string)($question["type"] ?? "");
        $questionText = trim((string)($question["text"] ?? $question["question"] ?? ""));

        if ($type === "" || $questionText === "") {
            continue;
        }

        $questionOrder++;
        $questionId = db_uuid($pdo);

        $insertQuestion->execute([
            $questionId,
            $quizId,
            $type,
            $questionText,
            $questionOrder
        ]);

        if ($type === "MULTIPLE_CHOICE") {
            $answers = isset($question["answers"]) && is_array($question["answers"])
                ? $question["answers"]
                : [];

            $answerOrder = 0;

            foreach ($answers as $answer) {
                if (!is_array($answer)) {
                    continue;
                }

                $label = trim((string)($answer["text"] ?? ""));

                if ($label === "") {
                    continue;
                }

                $answerOrder++;
                $answerId = db_uuid($pdo);
                $isCorrect = !empty($answer["isCorrect"]) ? 1 : 0;

                $insertAnswer->execute([
                    $answerId,
                    $questionId,
                    $label,
                    $isCorrect,
                    $answerOrder
                ]);
            }
        }

        if ($type === "MATCHING") {
            $pairs = isset($question["pairs"]) && is_array($question["pairs"])
                ? $question["pairs"]
                : [];

            $leftIdByText = [];
            $rightIdByText = [];
            $leftOrder = 0;
            $rightOrder = 0;

            foreach ($pairs as $pair) {
                if (!is_array($pair)) {
                    continue;
                }

                $leftText = trim((string)($pair["left"] ?? ""));

                if ($leftText === "") {
                    continue;
                }

                $rights = isset($pair["rights"]) && is_array($pair["rights"])
                    ? $pair["rights"]
                    : [];

                $rights = array_values(array_filter(array_map(
                    fn($right) => trim((string)$right),
                    $rights
                )));

                if (empty($rights)) {
                    continue;
                }

                if (!isset($leftIdByText[$leftText])) {
                    $leftOrder++;
                    $leftId = db_uuid($pdo);
                    $leftIdByText[$leftText] = $leftId;

                    $insertLeft->execute([
                        $leftId,
                        $questionId,
                        $leftText,
                        $leftOrder
                    ]);
                } else {
                    $leftId = $leftIdByText[$leftText];
                }

                foreach ($rights as $rightText) {
                    if (!isset($rightIdByText[$rightText])) {
                        $rightOrder++;
                        $rightId = db_uuid($pdo);
                        $rightIdByText[$rightText] = $rightId;

                        $insertRight->execute([
                            $rightId,
                            $questionId,
                            $rightText,
                            $rightOrder
                        ]);
                    } else {
                        $rightId = $rightIdByText[$rightText];
                    }

                    $insertPair->execute([
                        db_uuid($pdo),
                        $questionId,
                        $leftId,
                        $rightId
                    ]);
                }
            }
        }
    }

    if ($questionOrder === 0) {
        $pdo->rollBack();
        json_error("Nincs egyetlen érvényes kérdés sem.", 400);
    }

    $pdo->commit();

    json_success([
        "quiz_id" => $quizId
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    app_log_exception("UPDATE QUIZ ERROR: ", $e);

    json_error("Nem sikerült frissíteni a kvízt.", 500);
}