<?php

function require_user_id(): string {
    if (!isset($_SESSION["user_id"])) {
        json_error("Bejelentkezés szükséges!", 401);
    }

    return (string)$_SESSION["user_id"];
}