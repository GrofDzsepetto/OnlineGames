<?php

function app_log(string $message): void {
    if (defined("ENV") && ENV === "local") {
        error_log($message);
    }
}

function app_log_exception(string $context, Throwable $e): void {
    if (defined("ENV") && ENV === "local") {
        error_log($context . ": " . $e->getMessage());
        error_log("File: " . $e->getFile());
        error_log("Line: " . $e->getLine());
    }
}