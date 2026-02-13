<?php
require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/../db.php";

// ========================================
// Request body
// ========================================

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing token"]);
    exit;
}

$token = $data['token'];

// ========================================
// Google token ellenőrzés
// ========================================

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
$name  = isset($payload['name']) ? $payload['name'] : null;

// ========================================
// User keresés / létrehozás
// ========================================

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, name) VALUES (?, ?)"
    );
    $stmt->execute([$email, $name]);
    $userId = (int)$pdo->lastInsertId();
} else {
    $userId = (int)$user['id'];
}

// ========================================
// SESSION
// ========================================

$_SESSION['user_id'] = $userId;

// DEBUG LOG
error_log("GOOGLE LOGIN SESSION ID: " . session_id());
error_log("GOOGLE LOGIN SESSION DATA: " . print_r($_SESSION, true));

echo json_encode([
    "ok" => true,
    "user_id" => $userId,
]);
exit;
