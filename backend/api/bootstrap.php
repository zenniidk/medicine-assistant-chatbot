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
    $payload = json_decode($raw ?: '{}', true);
    return is_array($payload) ? $payload : [];
}

function requireMethod(string ...$methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        apiResponse(405, ['ok' => false, 'message' => 'Metodo no permitido']);
    }
}

function publicAssetUrl(string $relativePath): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    return $scheme . '://' . $host . '/' . ltrim($relativePath, '/');
}

function publicUser(array $user): array
{
    $photo = $user['profile_photo'] ?? null;
    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'age' => (int) $user['age'],
        'sex' => $user['sex'],
        'allergies' => $user['allergies'],
        'profile_photo_url' => $photo ? publicAssetUrl($photo) : null,
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
    return preg_match('/^Bearer\s+(.+)$/i', $header, $matches) ? trim($matches[1]) : null;
}

function requireApiUser(PDO $pdo): array
{
    $token = bearerToken();
    if (!$token) {
        apiResponse(401, ['ok' => false, 'message' => 'Token de acceso requerido']);
    }

    $statement = $pdo->prepare(
        'SELECT u.id, u.email, u.full_name, u.username, u.age, u.sex, u.allergies, u.profile_photo
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW()
         LIMIT 1'
    );
    $statement->execute([hash('sha256', $token)]);
    $user = $statement->fetch();

    if (!$user) {
        apiResponse(401, ['ok' => false, 'message' => 'Token invalido o vencido']);
    }

    return $user;
}
