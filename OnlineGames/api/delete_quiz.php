<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Bejelentkezés szükséges!"], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$quizId = isset($data["quiz_id"]) ? (string)$data["quiz_id"] : "";

if ($quizId === "") {
    http_response_code(400);
    echo json_encode(["error" => "Missing quiz_id"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (string)$_SESSION["user_id"];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("select created_by from quiz where id = ? limit 1");
    $stmt->execute([$quizId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Quiz not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((string)$row["created_by"] !== $userId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(["error" => "Nincs jogosultságod törölni ezt a kvízt."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $qStmt = $pdo->prepare("select id, type from question where quiz_id = ?");
    $qStmt->execute([$quizId]);
    $qs = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    $delAnswer = $pdo->prepare("delete from answer_option where question_id = ?");
    $delPair = $pdo->prepare("delete from matching_pair where question_id = ?");
    $delLeft = $pdo->prepare("delete from matching_left_item where question_id = ?");
    $delRight = $pdo->prepare("delete from matching_right_item where question_id = ?");
    $delQuestion = $pdo->prepare("delete from question where id = ?");
    $delQuiz = $pdo->prepare("delete from quiz where id = ?");

    foreach ($qs as $q) {
        $qid = (string)$q["id"];
        $type = (string)$q["type"];

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
