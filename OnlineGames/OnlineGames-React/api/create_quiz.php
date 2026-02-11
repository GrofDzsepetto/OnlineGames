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
        "debug" => ["raw" => mb_substr($raw, 0, 500)]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------------
   LANGUAGE feldolgozás
-------------------------- */
$language = isset($data["language"])
    ? strtolower(trim((string)$data["language"]))
    : null;

$allowedLanguages = ["hu", "en"];

/* -------------------------
   Alap validáció
-------------------------- */
if (
    !is_array($data) ||
    empty($data["title"]) ||
    !isset($data["questions"]) ||
    !is_array($data["questions"]) ||
    empty($language) ||
    !in_array($language, $allowedLanguages, true)
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Hiányzó vagy érvénytelen adatok",
        "debug" => [
            "title" => $data["title"] ?? null,
            "questions_is_array" => isset($data["questions"]) && is_array($data["questions"]),
            "language" => $language,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------------
   UUID helper
-------------------------- */
function db_uuid(PDO $pdo) {
    return (string)$pdo->query("SELECT UUID()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $quizId = db_uuid($pdo);

    $title = trim((string)$data["title"]);
    $description = isset($data["description"]) ? (string)$data["description"] : "";

    $IS_PUBLIC = !empty($data["isPublic"]) ? 1 : 0;
    $VIEWER_EMAILS = (isset($data["viewerEmails"]) && is_array($data["viewerEmails"]))
        ? $data["viewerEmails"]
        : [];

    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    if ($baseSlug === "") $baseSlug = "quiz";
    $slug = $baseSlug . "-" . substr(bin2hex(random_bytes(4)), 0, 6);

    /* -------------------------
       QUIZ insert (LANGUAGE!)
    -------------------------- */
    $stmt = $pdo->prepare("
        INSERT INTO QUIZ (
            ID,
            SLUG,
            TITLE,
            DESCRIPTION,
            LANGUAGE_CODE,
            IS_PUBLISHED,
            IS_PUBLIC,
            CREATED_BY
        )
        VALUES (?, ?, ?, ?, ?, 1, ?, ?)
    ");
    $stmt->execute([
        $quizId,
        $slug,
        $title,
        $description,
        $language,
        $IS_PUBLIC,
        $creatorId
    ]);

    /* -------------------------
       Viewer e-mailek
    -------------------------- */
    $insertViewerEmail = $pdo->prepare("
        INSERT IGNORE INTO QUIZ_VIEWER_EMAIL (QUIZ_ID, USER_EMAIL)
        VALUES (?, ?)
    ");

    if ($IS_PUBLIC === 0) {
        foreach ($VIEWER_EMAILS as $rawEmail) {
            $email = strtolower(trim((string)$rawEmail));
            if ($email === "") continue;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $insertViewerEmail->execute([$quizId, $email]);
        }

        $creatorEmailStmt = $pdo->prepare("
            SELECT EMAIL
            FROM USERS
            WHERE ID = ?
            LIMIT 1
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

    /* -------------------------
       Prepared statements
    -------------------------- */
    $insertQuestion = $pdo->prepare("
        INSERT INTO QUESTION (ID, QUIZ_ID, TYPE, QUESTION_TEXT, ORDER_INDEX)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insertAnswer = $pdo->prepare("
        INSERT INTO ANSWER_OPTION (ID, QUESTION_ID, LABEL, IS_CORRECT, ORDER_INDEX)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insertLeft = $pdo->prepare("
        INSERT INTO MATCHING_LEFT_ITEM (ID, QUESTION_ID, TEXT, ORDER_INDEX)
        VALUES (?, ?, ?, ?)
    ");

    $insertRight = $pdo->prepare("
        INSERT INTO MATCHING_RIGHT_ITEM (ID, QUESTION_ID, TEXT, ORDER_INDEX)
        VALUES (?, ?, ?, ?)
    ");

    $insertPair = $pdo->prepare("
        INSERT INTO MATCHING_PAIR (ID, QUESTION_ID, LEFT_ID, RIGHT_ID)
        VALUES (?, ?, ?, ?)
    ");

    /* -------------------------
       Questions feldolgozás
    -------------------------- */
    $qOrder = 0;
    foreach ($data["questions"] as $q) {
        if (!is_array($q)) continue;

        $type = isset($q["type"]) ? (string)$q["type"] : "";
        $qText = isset($q["text"]) ? trim((string)$q["text"]) : "";

        if ($type === "" || $qText === "") continue;

        $qOrder++;
        $questionId = db_uuid($pdo);
        $insertQuestion->execute([$questionId, $quizId, $type, $qText, $qOrder]);

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
        echo json_encode([
            "error" => "Nincs egyetlen érvényes kérdés sem."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "quiz_id" => $quizId,
        "slug" => $slug
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode(["error" => "DB error"], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "code" => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
