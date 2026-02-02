<?php

$pdo = new PDO(
    "mysql:host=localhost;dbname=dzsepetto_online_quiz;charset=utf8mb4",
    "dzsepetto_admin",
    "ALMA1234!",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
