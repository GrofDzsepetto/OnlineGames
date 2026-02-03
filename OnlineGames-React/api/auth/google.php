<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

$isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $isLocal ? null : '.dzsepetto.hu',
    'secure' => !$isLocal,
    'httponly' => true,
    'samesite' => $isLocal ? 'Lax' : 'None',
]);

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing token"]);
    exit;
}

$token = $data['token'];

/**
 * 🔐 Google token ellenőrzés (cURL)
 */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($token),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(401);
    echo json_encode(["error" => "Google verification failed"]);
    exit;
}

$payload = json_decode($response, true);

if (!isset($payload['email'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid Google token"]);
    exit;
}

$email = $payload['email'];
$name  = $payload['name'] ?? "";

/**
 * 👤 User keresés / létrehozás
 */
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, name) VALUES (?, ?)"
    );
    $stmt->execute([$email, $name]);
    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

$_SESSION['user_id'] = $userId;

echo json_encode([
    "ok" => true,
    "user_id" => $userId,
]);
