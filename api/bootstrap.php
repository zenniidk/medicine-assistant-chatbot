<?php

declare(strict_types=1);

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

function apiResponse(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonPayload(): array
{
    $raw = file_get_contents('php://input');

    try {
        $payload = json_decode($raw ?: '', true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        apiResponse(400, ['ok' => false, 'message' => 'JSON inválido']);
    }

    if (!is_array($payload)) {
        apiResponse(400, ['ok' => false, 'message' => 'JSON inválido']);
    }

    return $payload;
}

function requireMethod(string ...$methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        apiResponse(405, ['ok' => false, 'message' => 'Método no permitido']);
    }
}

function publicUser(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'age' => (int) $user['age'],
        'sex' => $user['sex'],
        'allergies' => $user['allergies'],
    ];
}

function createApiToken(PDO $pdo, int $userId): string
{
    $plainToken = bin2hex(random_bytes(32));
    $statement = $pdo->prepare(
        'INSERT INTO api_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))'
    );
    $statement->execute([$userId, hash('sha256', $plainToken)]);
    return $plainToken;
}

function bearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/^Bearer\s+(.+)$/i', $header, $matches)
        ? trim($matches[1])
        : null;
}

function requireApiUser(PDO $pdo): array
{
    $token = bearerToken();

    if ($token === null || $token === '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['user_id'])) {
            $statement = $pdo->prepare(
                'SELECT id, email, full_name, username, age, sex, allergies
                 FROM users WHERE id = ? LIMIT 1'
            );
            $statement->execute([$_SESSION['user_id']]);
            $sessionUser = $statement->fetch();
            if ($sessionUser) {
                return $sessionUser;
            }
        }
        apiResponse(401, ['ok' => false, 'message' => 'Token de acceso requerido']);
    }

    $statement = $pdo->prepare(
        'SELECT u.id, u.email, u.full_name, u.username, u.age, u.sex, u.allergies
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW()
         LIMIT 1'
    );
    $statement->execute([hash('sha256', $token)]);
    $user = $statement->fetch();

    if (!$user) {
        apiResponse(401, ['ok' => false, 'message' => 'Token inválido o vencido']);
    }

    return $user;
}
