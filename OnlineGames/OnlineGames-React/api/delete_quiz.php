<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";


if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges!"]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$quizId = isset($data["quiz_id"]) ? (string)$data["quiz_id"] : "";

if ($quizId === "") {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz_id"]);
    exit;
}

$userId = (string)$_SESSION["user_id"];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT CREATED_BY FROM QUIZ WHERE ID = ? LIMIT 1");
    $stmt->execute([$quizId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"]);
        exit;
    }

    if ((string)$row["CREATED_BY"] !== $userId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod törölni ezt a kvízt."]);
        exit;
    }

    $qStmt = $pdo->prepare("SELECT ID, TYPE FROM QUESTION WHERE QUIZ_ID = ?");
    $qStmt->execute([$quizId]);
    $qs = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    $delAnswer = $pdo->prepare("DELETE FROM ANSWER_OPTION WHERE QUESTION_ID = ?");
    $delPair = $pdo->prepare("DELETE FROM MATCHING_PAIR WHERE QUESTION_ID = ?");
    $delLeft = $pdo->prepare("DELETE FROM MATCHING_LEFT_ITEM WHERE QUESTION_ID = ?");
    $delRight = $pdo->prepare("DELETE FROM MATCHING_RIGHT_ITEM WHERE QUESTION_ID = ?");
    $delQuestion = $pdo->prepare("DELETE FROM QUESTION WHERE ID = ?");
    $delQuiz = $pdo->prepare("DELETE FROM QUIZ WHERE ID = ?");

    foreach ($qs as $q) {
        $qid = (string)$q["ID"];
        $type = (string)$q["TYPE"];

        if ($type === "MATCHING") {
            $delPair->execute([$qid]);
            $delLeft->execute([$qid]);
            $delRight->execute([$qid]);
        } else {
            $delAnswer->execute([$qid]);
        }

        $delQuestion->execute([$qid]);
    }

    $delQuiz->execute([$quizId]);

    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
