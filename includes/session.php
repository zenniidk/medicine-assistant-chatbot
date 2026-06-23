<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function createCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token'], $token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function setMessage(string $type, string $text): void
{
    $_SESSION['message'] = ['type' => $type, 'text' => $text];
}

function getMessage(): ?array
{
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    return $message;
}
