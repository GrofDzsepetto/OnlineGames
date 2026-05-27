<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$data = read_json_body();

$token = trim((string)($data["token"] ?? ""));

if ($token === "") {
    json_error("Missing token", 400);
}

try {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($token),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        app_log("GOOGLE VERIFY ERROR: " . $curlError);
        json_error("Google verification failed", 401);
    }

    $payload = json_decode($response, true);

    if (!is_array($payload) || empty($payload["email"])) {
        json_error("Invalid Google token", 401);
    }

    $email = strtolower(trim((string)$payload["email"]));
    $name = isset($payload["name"]) ? trim((string)$payload["name"]) : null;

    $stmt = $pdo->prepare("
        select id
        from users
        where email = ?
        limit 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare("
            insert into users (email, name)
            values (?, ?)
        ");
        $stmt->execute([$email, $name]);

        $userId = (int)$pdo->lastInsertId();
    } else {
        $userId = (int)$user["id"];
    }

    $_SESSION["user_id"] = $userId;

    app_log("GOOGLE LOGIN SESSION ID: " . session_id());

    json_success([
        "user_id" => $userId
    ]);

} catch (Throwable $e) {
    app_log_exception("GOOGLE LOGIN ERROR", $e);
    json_error("Nem sikerült bejelentkezni Google-fiókkal.", 500);
}