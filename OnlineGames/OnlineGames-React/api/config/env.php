<?php
// ========================================
// Környezeti változó olvasó (PHP 7 safe)
// ========================================
error_log("ENV DEBUG HTTP_HOST = " . ($_SERVER['HTTP_HOST'] ?? 'N/A'));

function env($key, $default = null) {
    $value = getenv($key);
    return ($value !== false) ? $value : $default;
}

// ========================================
// Környezet felismerése (local vs prod)
// ========================================
$host = $_SERVER['HTTP_HOST'] ?? '';

$IS_LOCAL = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false
);


if ($IS_LOCAL) {
    // ===== LOCAL FEJLESZTŐI KÖRNYEZET =====
    define('ENV', 'local');

    // Local adatbázis
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'dzsepetto_local');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // HTTP → nincs secure cookie
    define('COOKIE_SECURE', false);

} else {
    // ===== ÉLES KÖRNYEZET =====
    define('ENV', 'prod');

    // Prod adatbázis környezeti változókból
    define('DB_HOST', env('DB_HOST'));
    define('DB_NAME', env('DB_NAME'));
    define('DB_USER', env('DB_USER'));
    define('DB_PASS', env('DB_PASS'));

    // HTTPS → kötelező secure cookie
    define('COOKIE_SECURE', true);
}

