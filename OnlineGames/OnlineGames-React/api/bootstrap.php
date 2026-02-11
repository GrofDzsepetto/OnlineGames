<?php
require_once __DIR__ . '/config/env.php';

// ========================================
// CORS
// ========================================

$allowedOrigins = [
    "https://dzsepetto.hu",
    "https://www.dzsepetto.hu",
    "http://localhost:5173",
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

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

// ========================================
// SESSION COOKIE BEÁLLÍTÁS
// ========================================

if (ENV === 'local') {
    // LOCAL → HTTP
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    // PROD → HTTPS
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '.dzsepetto.hu',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}

session_start();
