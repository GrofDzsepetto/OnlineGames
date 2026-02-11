<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";


$userId = isset($_SESSION["user_id"]) ? (string)$_SESSION["user_id"] : null;
$userEmail = null;

if ($userId) {
    $emailStmt = $pdo->prepare("
        SELECT EMAIL
        FROM USERS
        WHERE ID = ?
        LIMIT 1
    ");
    $emailStmt->execute([$userId]);
    $userEmail = $emailStmt->fetchColumn();
    $userEmail = $userEmail ? strtolower(trim((string)$userEmail)) : null;
}

try {
    if (!$userId || !$userEmail) {
        $sql = "
            SELECT 
                Q.ID, 
                Q.SLUG, 
                Q.TITLE, 
                Q.DESCRIPTION,
                Q.CREATED_BY,
                Q.IS_PUBLIC,
                U.NAME AS CREATOR_NAME
            FROM QUIZ Q
            LEFT JOIN USERS U ON Q.CREATED_BY = U.ID
            WHERE Q.IS_PUBLISHED = 1
              AND Q.IS_PUBLIC = 1
            ORDER BY Q.CREATED_AT DESC
        ";

        $stmt = $pdo->query($sql);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "
        SELECT 
            Q.ID, 
            Q.SLUG, 
            Q.TITLE, 
            Q.DESCRIPTION,
            Q.CREATED_BY,
            Q.IS_PUBLIC,
            U.NAME AS CREATOR_NAME
        FROM QUIZ Q
        LEFT JOIN USERS U ON Q.CREATED_BY = U.ID
        WHERE Q.IS_PUBLISHED = 1
          AND (
                Q.IS_PUBLIC = 1
                OR Q.CREATED_BY = ?
                OR EXISTS (
                    SELECT 1
                    FROM QUIZ_VIEWER_EMAIL V
                    WHERE V.QUIZ_ID = Q.ID
                      AND V.USER_EMAIL = ?
                )
          )
        ORDER BY Q.CREATED_AT DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userEmail]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "SQL hiba: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
