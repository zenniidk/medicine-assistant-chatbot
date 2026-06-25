<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$payload = jsonPayload();
$username = trim((string) ($payload['username'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($username === '' || $password === '') {
    apiResponse(422, ['ok' => false, 'message' => 'Usuario y contrasena son obligatorios']);
}

$statement = $pdo->prepare(
    'SELECT id, email, full_name, username, password, age, sex, allergies, profile_photo
     FROM users WHERE username = ? OR email = ? LIMIT 1'
);
$statement->execute([$username, $username]);
$user = $statement->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    apiResponse(401, ['ok' => false, 'message' => 'Credenciales invalidas']);
}

$token = createApiToken($pdo, (int) $user['id']);
apiResponse(200, [
    'ok' => true,
    'message' => 'Sesion iniciada',
    'token' => $token,
    'token_type' => 'Bearer',
    'expires_in_days' => 30,
    'user' => publicUser($user),
]);
