<?php

function json_success(array $data = [], int $status = 200): void{
    http_response_code($status);
    echo json_encode(
        ["success" => true, "data" => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function json_error(string $message, int $status = 400, array $errors = []): void {
    http_response_code($status);

    $response = [
        "success" => false,
        "message" => $message,
    ];

    if (!empty($errors)) {
        $response["errors"] = $errors;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}