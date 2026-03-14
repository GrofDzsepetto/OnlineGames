<?php
require_once __DIR__ . '/config/env.php';

// ========================================
// CORS ORIGINS --> .env alapján
// ========================================
set_exception_handler(function ($e) {

    file_put_contents(
        __DIR__ . '/error.log',
        date('Y-m-d H:i:s') . "\n" . $e . "\n\n",
        FILE_APPEND
    );

    http_response_code(500);

    echo json_encode([
        "error" => true,
        "message" => ENV === 'local'
            ? $e->getMessage()
            : "Internal server error"
    ]);

    exit;
});

$allowedOriginsRaw = env('ALLOWED_ORIGINS', '');
$allowedOrigins = array_values(
    array_filter(
        array_map('trim', explode(',', $allowedOriginsRaw))
    )
);

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}

error_log("ORIGIN: " . ($_SERVER['HTTP_ORIGIN'] ?? 'none'));
error_log("ALLOWED: " . $allowedOriginsRaw);

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json; charset=utf-8");

// ========================================
// SESSION COOKIE
// ========================================

if (ENV === 'local') {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', '.dzsepetto.hu'),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}

session_start();