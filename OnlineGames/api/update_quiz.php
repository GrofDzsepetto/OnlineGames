<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges!"], JSON_UNESCAPED_UNICODE);
    exit;
}

$creatorId = (string)$_SESSION["user_id"];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "error" => "JSON hiba: " . json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$language = isset($data["language"])
    ? strtolower(trim((string)$data["language"]))
    : null;

$allowedLanguages = ["hu", "en"];

if (
    !is_array($data) ||
    empty($data["quiz_id"]) ||
    empty($data["title"]) ||
    !isset($data["questions"]) ||
    !is_array($data["questions"]) ||
    empty($language) ||
    !in_array($language, $allowedLanguages, true)
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Hiányzó vagy érvénytelen adatok"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function db_uuid(PDO $pdo) {
    return (string)$pdo->query("select uuid()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $quizId = (string)$data["quiz_id"];
    $title = trim((string)$data["title"]);
    $description = isset($data["description"]) ? (string)$data["description"] : "";

    $is_public = !empty($data["isPublic"]) ? 1 : 0;
    $viewer_emails = (isset($data["viewerEmails"]) && is_array($data["viewerEmails"]))
        ? $data["viewerEmails"]
        : [];

    $ownerStmt = $pdo->prepare("select created_by from quiz where id = ? limit 1");
    $ownerStmt->execute([$quizId]);
    $row = $ownerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((string)$row["created_by"] !== $creatorId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod ehhez a kvízhez."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $upd = $pdo->prepare("
        update quiz 
        set title = ?, 
            description = ?, 
            language_code = ?, 
            is_public = ? 
        where id = ?
    ");
    $upd->execute([
        $title,
        $description,
        $language,
        $is_public,
        $quizId
    ]);

    $pdo->prepare("delete from quiz_viewer_email where quiz_id = ?")
        ->execute([$quizId]);

    $insertViewerEmail = $pdo->prepare("
        insert ignore into quiz_viewer_email (quiz_id, user_email)
        values (?, ?)
    ");

    if ($is_public === 0) {
        foreach ($viewer_emails as $rawEmail) {
            $email = strtolower(trim((string)$rawEmail));
            if ($email === "") continue;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $insertViewerEmail->execute([$quizId, $email]);
        }

        $creatorEmailStmt = $pdo->prepare("
            select email from users where id = ? limit 1
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
    $qids = $qidsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($qids)) {
        $in = implode(",", array_fill(0, count($qids), "?"));

        $pdo->prepare("delete from matching_pair where question_id in ($in)")->execute($qids);
        $pdo->prepare("delete from matching_left_item where question_id in ($in)")->execute($qids);
        $pdo->prepare("delete from matching_right_item where question_id in ($in)")->execute($qids);
        $pdo->prepare("delete from answer_option where question_id in ($in)")->execute($qids);
        $pdo->prepare("delete from question where id in ($in)")->execute($qids);
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

    $qOrder = 0;

    foreach ($data["questions"] as $q) {
        if (!is_array($q)) continue;

        $type = isset($q["type"]) ? (string)$q["type"] : "";
        $qText = isset($q["text"]) ? trim((string)$q["text"]) : "";

        if ($type === "" || $qText === "") continue;

        $qOrder++;
        $questionId = db_uuid($pdo);

        $insertQuestion->execute([
            $questionId,
            $quizId,
            $type,
            $qText,
            $qOrder
        ]);

        if ($type === "MULTIPLE_CHOICE") {
            $answers = isset($q["answers"]) && is_array($q["answers"]) ? $q["answers"] : [];
            $aOrder = 0;

            foreach ($answers as $ans) {
                $label = isset($ans["text"]) ? trim((string)$ans["text"]) : "";
                if ($label === "") continue;

                $aOrder++;
                $isCorrect = !empty($ans["isCorrect"]) ? 1 : 0;
                $answerId = db_uuid($pdo);

                $insertAnswer->execute([
                    $answerId,
                    $questionId,
                    $label,
                    $isCorrect,
                    $aOrder
                ]);
            }
        }

        if ($type === "MATCHING") {
            $pairs = isset($q["pairs"]) && is_array($q["pairs"]) ? $q["pairs"] : [];

            $leftIdByText = [];
            $rightIdByText = [];
            $leftOrder = 0;
            $rightOrder = 0;

            foreach ($pairs as $pair) {
                $leftText = trim((string)($pair["left"] ?? ""));
                if ($leftText === "") continue;

                $rights = array_values(array_filter(array_map("trim", $pair["rights"] ?? [])));
                if (empty($rights)) continue;

                if (!isset($leftIdByText[$leftText])) {
                    $leftOrder++;
                    $leftId = db_uuid($pdo);
                    $leftIdByText[$leftText] = $leftId;
                    $insertLeft->execute([$leftId, $questionId, $leftText, $leftOrder]);
                } else {
                    $leftId = $leftIdByText[$leftText];
                }

                foreach ($rights as $rText) {
                    if (!isset($rightIdByText[$rText])) {
                        $rightOrder++;
                        $rightId = db_uuid($pdo);
                        $rightIdByText[$rText] = $rightId;
                        $insertRight->execute([$rightId, $questionId, $rText, $rightOrder]);
                    } else {
                        $rightId = $rightIdByText[$rText];
                    }

                    $pairId = db_uuid($pdo);
                    $insertPair->execute([$pairId, $questionId, $leftId, $rightId]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "quiz_id" => $quizId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
