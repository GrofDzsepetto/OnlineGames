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
        "error" => "JSON hiba: " . json_last_error_msg(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* language */
$language = isset($data["language"])
    ? strtolower(trim((string)$data["language"]))
    : null;

$allowedLanguages = ["hu", "en"];

if (
    !is_array($data) ||
    empty($data["title"]) ||
    !isset($data["questions"]) ||
    !is_array($data["questions"]) ||
    empty($language) ||
    !in_array($language, $allowedLanguages, true)
) {
    http_response_code(400);
    echo json_encode(["error" => "Hiányzó vagy érvénytelen adatok"], JSON_UNESCAPED_UNICODE);
    exit;
}

function db_uuid(PDO $pdo) {
    return (string)$pdo->query("select uuid()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $quizId = db_uuid($pdo);

    $title = trim((string)$data["title"]);
    $description = isset($data["description"]) ? (string)$data["description"] : "";

    $isPublic = !empty($data["isPublic"]) ? 1 : 0;
    $viewerEmails = (isset($data["viewerEmails"]) && is_array($data["viewerEmails"]))
        ? $data["viewerEmails"]
        : [];

    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    if ($baseSlug === "") $baseSlug = "quiz";
    $slug = $baseSlug . "-" . substr(bin2hex(random_bytes(4)), 0, 6);

    /* quiz insert */
    $stmt = $pdo->prepare("
        insert into quiz (
            id,
            slug,
            title,
            description,
            language_code,
            is_published,
            is_public,
            created_by
        )
        values (?, ?, ?, ?, ?, 1, ?, ?)
    ");
    $stmt->execute([
        $quizId,
        $slug,
        $title,
        $description,
        $language,
        $isPublic,
        $creatorId
    ]);

    /* viewer emails */
    $insertViewerEmail = $pdo->prepare("
        insert ignore into quiz_viewer_email (quiz_id, user_email)
        values (?, ?)
    ");

    if ($isPublic === 0) {

        foreach ($viewerEmails as $rawEmail) {
            $email = strtolower(trim((string)$rawEmail));
            if ($email === "") continue;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

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

    /* prepared statements */

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

    /* questions */

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

            $answers = (isset($q["answers"]) && is_array($q["answers"])) ? $q["answers"] : [];
            $aOrder = 0;

            foreach ($answers as $ans) {
                if (!is_array($ans)) continue;

                $label = isset($ans["text"]) ? trim((string)$ans["text"]) : "";
                if ($label === "") continue;

                $aOrder++;
                $isCorrect = (!empty($ans["isCorrect"])) ? 1 : 0;
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

            $pairs = (isset($q["pairs"]) && is_array($q["pairs"])) ? $q["pairs"] : [];

            $leftIdByText = [];
            $rightIdByText = [];
            $leftOrder = 0;
            $rightOrder = 0;

            foreach ($pairs as $pair) {
                if (!is_array($pair)) continue;

                $leftText = isset($pair["left"]) ? trim((string)$pair["left"]) : "";
                if ($leftText === "") continue;

                $rights = (isset($pair["rights"]) && is_array($pair["rights"])) ? $pair["rights"] : [];
                $rights = array_values(array_filter(array_map("trim", $rights)));

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

    if ($qOrder === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "Nincs egyetlen érvényes kérdés sem."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "quiz_id" => $quizId,
        "slug" => $slug
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
