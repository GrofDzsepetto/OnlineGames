<?php
header("Content-Type: application/json");
require __DIR__ . "/db.php";

$sql = "
    SELECT 
        ID,
        SLUG,
        TITLE,
        DESCRIPTION
    FROM QUIZ
    WHERE IS_PUBLISHED = 1
    ORDER BY CREATED_AT DESC
";

$stmt = $pdo->query($sql);
$quizzes = $stmt->fetchAll();

echo json_encode($quizzes);