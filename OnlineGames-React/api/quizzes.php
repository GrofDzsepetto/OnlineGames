<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}



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