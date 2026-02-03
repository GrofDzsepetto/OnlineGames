<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["user" => null]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT email, name
    FROM users
    WHERE id = ?
");
$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["user" => $user]);
