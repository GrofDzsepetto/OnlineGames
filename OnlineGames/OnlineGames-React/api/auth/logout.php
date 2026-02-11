<?php
require __DIR__ . "/../bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Session törlés
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        isset($params["domain"]) ? $params["domain"] : '',
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully"
]);
exit;
