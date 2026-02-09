<?php

$allowedOrigins = [
    "https://dzsepetto.hu",
    "https://www.dzsepetto.hu",
    "http://localhost:5173",
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json; charset=utf-8");

$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $isLocal ? null : '.dzsepetto.hu',
    'secure' => !$isLocal,   // localhost → false
    'httponly' => true,
    'samesite' => 'None',    // 🔥 MINDKÉT ESETBEN
]);

session_start();