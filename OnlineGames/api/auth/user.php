<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

// ========================================
// DEBUG KAPCSOLÓ
// ========================================

$DEBUG = false; // ⬅️ true ha debugolni akarsz

if ($DEBUG) {
    error_log("USER CHECK SESSION ID: " . session_id());
    error_log("USER CHECK SESSION DATA: " . print_r($_SESSION, true));
    error_log("USER CHECK COOKIES: " . print_r($_COOKIE, true));

    echo json_encode([
        "debug" => [
            "session_id" => session_id(),
            "session" => $_SESSION,
            "cookies" => $_COOKIE,
            "params" => session_get_cookie_params(),
        ]
    ]);
    exit;
}

// ========================================
// AUTH CHECK
// ========================================

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["user" => null]);
    exit;
}

// ========================================
// USER LEKÉRÉS
// ========================================

$stmt = $pdo->prepare("
    SELECT email, name
    FROM users
    WHERE id = ?
");
$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "user" => [
        "id" => (int)$_SESSION["user_id"],
        "email" => $user["email"],
        "name" => $user["name"],
    ]
]);
exit;
