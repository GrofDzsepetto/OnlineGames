<?php
// ========================================
// Adatbázis kapcsolat (ENV alapú)
// ========================================

require_once __DIR__ . '/config/env.php';

error_log("DB DEBUG ENV=" . ENV . " DB_NAME=" . DB_NAME);


$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
