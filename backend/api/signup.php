<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$payload = jsonPayload();
$email = trim((string) ($payload['email'] ?? ''));
$fullName = trim((string) ($payload['full_name'] ?? ''));
$username = trim((string) ($payload['username'] ?? ''));
$password = (string) ($payload['password'] ?? '');
$age = filter_var($payload['age'] ?? null, FILTER_VALIDATE_INT);
$sex = trim((string) ($payload['sex'] ?? ''));
$allergies = trim((string) ($payload['allergies'] ?? 'Ninguna'));
$allowedSex = ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)
    || $fullName === ''
    || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)
    || strlen($password) < 6
    || $age === false || $age < 1 || $age > 120
    || !in_array($sex, $allowedSex, true)
) {
    apiResponse(422, ['ok' => false, 'message' => 'Datos de registro invalidos']);
}

$duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$duplicate->execute([$email, $username]);
if ($duplicate->fetch()) {
    apiResponse(409, ['ok' => false, 'message' => 'El correo o usuario ya existe']);
}

$statement = $pdo->prepare(
    'INSERT INTO users (email, full_name, username, password, age, sex, allergies)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$statement->execute([
    $email,
    $fullName,
    $username,
    password_hash($password, PASSWORD_DEFAULT),
    $age,
    $sex,
    $allergies ?: 'Ninguna',
]);

$userId = (int) $pdo->lastInsertId();
$token = createApiToken($pdo, $userId);
$userStatement = $pdo->prepare(
    'SELECT id, email, full_name, username, age, sex, allergies, profile_photo FROM users WHERE id = ?'
);
$userStatement->execute([$userId]);

apiResponse(201, [
    'ok' => true,
    'message' => 'Cuenta creada',
    'token' => $token,
    'token_type' => 'Bearer',
    'expires_in_days' => 30,
    'user' => publicUser($userStatement->fetch()),
]);
