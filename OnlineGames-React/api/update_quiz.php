<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges!"], JSON_UNESCAPED_UNICODE);
    exit;
}

$creatorId = (string)$_SESSION['user_id'];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "JSON hiba: " . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !$data ||
    empty($data['quiz_id']) ||
    empty($data['title']) ||
    !isset($data['questions']) ||
    !is_array($data['questions'])
) {
    http_response_code(400);
    echo json_encode(["error" => "Hiányzó adatok (quiz_id/title/questions)."], JSON_UNESCAPED_UNICODE);
    exit;
}

function db_uuid(PDO $pdo): string {
    return (string)$pdo->query("SELECT UUID()")->fetchColumn();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $quizId = (string)$data['quiz_id'];
    $title = trim((string)$data['title']);
    $description = isset($data['description']) ? (string)$data['description'] : '';

    // ✅ Láthatóság
    $IS_PUBLIC = !empty($data["isPublic"]) ? 1 : 0;
    $VIEWER_EMAILS = (isset($data["viewerEmails"]) && is_array($data["viewerEmails"])) ? $data["viewerEmails"] : [];

    $ownerStmt = $pdo->prepare("SELECT CREATED_BY FROM QUIZ WHERE ID = ? LIMIT 1");
    $ownerStmt->execute([$quizId]);
    $row = $ownerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((string)$row['CREATED_BY'] !== $creatorId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod ehhez a kvízhez."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ QUIZ meta update (IS_PUBLIC is)
    $upd = $pdo->prepare("UPDATE QUIZ SET TITLE = ?, DESCRIPTION = ?, IS_PUBLIC = ? WHERE ID = ?");
    $upd->execute([$title, $description, $IS_PUBLIC, $quizId]);

    // ✅ Engedélyezett emailek frissítése:
    // - ha PUBLIC: töröljük a listát
    // - ha PRIVATE: újraírjuk a listát (DELETE + INSERT)
    $delViewers = $pdo->prepare("DELETE FROM QUIZ_VIEWER_EMAIL WHERE QUIZ_ID = ?");
    $delViewers->execute([$quizId]);

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

        // creator email automatikusan kapjon hozzáférést
        $creatorEmailStmt = $pdo->prepare("
            SELECT EMAIL
            FROM USERS
            WHERE ID = ?
            LIMIT 1
        ");
        $creatorEmailStmt->execute([$creatorId]);
        $creatorEmail = $creatorEmailStmt->fetchColumn();

        if ($creatorEmail) {
            $insertViewerEmail->execute([$quizId, strtolower(trim((string)$creatorEmail))]);
        }
    }

    $qidsStmt = $pdo->prepare("SELECT ID FROM QUESTION WHERE QUIZ_ID = ?");
    $qidsStmt->execute([$quizId]);
    $qids = $qidsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($qids)) {
        $in = implode(',', array_fill(0, count($qids), '?'));

        $stmt = $pdo->prepare("
            DELETE FROM MATCHING_PAIR
            WHERE QUESTION_ID IN ($in)
        ");
        $stmt->execute($qids);

        $stmt = $pdo->prepare("
            DELETE FROM MATCHING_LEFT_ITEM
            WHERE QUESTION_ID IN ($in)
        ");
        $stmt->execute($qids);

        $stmt = $pdo->prepare("
            DELETE FROM MATCHING_RIGHT_ITEM
            WHERE QUESTION_ID IN ($in)
        ");
        $stmt->execute($qids);

        $stmt = $pdo->prepare("
            DELETE FROM ANSWER_OPTION
            WHERE QUESTION_ID IN ($in)
        ");
        $stmt->execute($qids);

        $stmt = $pdo->prepare("
            DELETE FROM QUESTION
            WHERE ID IN ($in)
        ");
        $stmt->execute($qids);
    }

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

    foreach ($data['questions'] as $index => $q) {
        if (!is_array($q)) continue;

        $type = isset($q['type']) ? (string)$q['type'] : '';
        $qText = isset($q['text']) ? trim((string)$q['text']) : '';

        if ($type === '' || $qText === '') continue;

        $questionId = db_uuid($pdo);
        $insertQuestion->execute([$questionId, $quizId, $type, $qText, (int)$index + 1]);

        if ($type === 'MULTIPLE_CHOICE') {
            $answers = isset($q['answers']) && is_array($q['answers']) ? $q['answers'] : [];

            $aOrder = 0;
            foreach ($answers as $ans) {
                if (!is_array($ans)) continue;

                $label = isset($ans['text']) ? trim((string)$ans['text']) : '';
                if ($label === '') continue;

                $aOrder++;
                $isCorrect = !empty($ans['isCorrect']) ? 1 : 0;
                $answerId = db_uuid($pdo);

                $insertAnswer->execute([$answerId, $questionId, $label, $isCorrect, $aOrder]);
            }
        }

        if ($type === 'MATCHING') {
            $pairs = isset($q['pairs']) && is_array($q['pairs']) ? $q['pairs'] : [];

            $leftIdByText = [];
            $rightIdByText = [];
            $leftOrder = 0;
            $rightOrder = 0;

            foreach ($pairs as $pair) {
                if (!is_array($pair)) continue;

                $leftText = isset($pair['left']) ? trim((string)$pair['left']) : '';
                if ($leftText === '') continue;

                $rights = isset($pair['rights']) && is_array($pair['rights']) ? $pair['rights'] : [];

                $cleanRights = [];
                foreach ($rights as $x) {
                    $t = trim((string)$x);
                    if ($t !== '') $cleanRights[] = $t;
                }
                $rights = array_values($cleanRights);

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

    echo json_encode(["success" => true, "quiz_id" => $quizId], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode(["error" => "DB error"], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(500);
    echo json_encode(["error" => $e->getMessage(), "code" => $e->getCode()], JSON_UNESCAPED_UNICODE);
    exit;
}
