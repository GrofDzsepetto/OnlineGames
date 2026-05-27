<?php

function read_json_body(): array {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        json_error("Érvénytelen JSON adat.", 400);
    }

    return $data;
}