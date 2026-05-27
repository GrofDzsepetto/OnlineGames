<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

try {
    app_log("USER CHECK SESSION ID: " . session_id());

    if (!isset($_SESSION["user_id"])) {
        json_success([
            "user" => null
        ]);
    }

    $userId = (int)$_SESSION["user_id"];

    $stmt = $pdo->prepare("
        select id, email, name
        from users
        where id = ?
        limit 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION["user_id"]);

        json_success([
            "user" => null
        ]);
    }

    json_success([
        "user" => [
            "id" => (string)$user["id"],
            "email" => (string)$user["email"],
            "name" => (string)($user["name"] ?? ""),
        ]
    ]);

} catch (Throwable $e) {
    app_log_exception("USER CHECK ERROR", $e);

    json_error(
        ENV === "local"
            ? $e->getMessage()
            : "Nem sikerült lekérni a felhasználót.",
        500
    );
}