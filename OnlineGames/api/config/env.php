<?php

$envPath = dirname(__DIR__) . '/.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || substr($line, 0, 1) === '#') continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, -1);
        }
        if (($value[0] ?? '') === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env(string $key, $default = null) {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('ENV', env('APP_ENV', 'production'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

define('COOKIE_SECURE', ENV !== 'local');