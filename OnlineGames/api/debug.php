<?php
require __DIR__ . "/bootstrap.php";

if (ENV !== "local") {
    json_error("Not found", 404);
}

json_success([
    "cookies" => $_COOKIE,
    "session" => $_SESSION ?? null,
]);