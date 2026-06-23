<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');
$payload = jsonPayload();
$login = trim((string) ($payload['username'] ?? $payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($login === '' || $password === '') {
    apiResponse(422, ['ok' => false, 'message' => 'Usuario y contraseña son obligatorios']);
}

$statement = $pdo->prepare(
    'SELECT id, email, full_name, username, password, age, sex, allergies
     FROM users WHERE username = ? OR email = ? LIMIT 1'
);
$statement->execute([$login, $login]);
$user = $statement->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    apiResponse(401, ['ok' => false, 'message' => 'Credenciales incorrectas']);
}

apiResponse(200, [
    'ok' => true,
    'message' => 'Sesión iniciada',
    'token' => createApiToken($pdo, (int) $user['id']),
    'token_type' => 'Bearer',
    'expires_in_days' => 30,
    'user' => publicUser($user),
]);
