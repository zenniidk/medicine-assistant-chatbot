<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$user = requireApiUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    apiResponse(200, ['ok' => true, 'user' => publicUser($user)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!empty($user['profile_photo'])) {
        $photoPath = dirname(__DIR__) . '/' . ltrim((string) $user['profile_photo'], '/');
        if (is_file($photoPath)) {
            @unlink($photoPath);
        }
    }

    $statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $statement->execute([$user['id']]);

    apiResponse(200, ['ok' => true, 'message' => 'Cuenta eliminada']);
}

requireMethod('PUT', 'PATCH');
$payload = jsonPayload();
$email = trim((string) ($payload['email'] ?? $user['email']));
$fullName = trim((string) ($payload['full_name'] ?? $user['full_name']));
$username = trim((string) ($payload['username'] ?? $user['username']));
$age = filter_var($payload['age'] ?? $user['age'], FILTER_VALIDATE_INT);
$sex = trim((string) ($payload['sex'] ?? $user['sex']));
$allergies = trim((string) ($payload['allergies'] ?? $user['allergies']));
$password = (string) ($payload['password'] ?? '');
$allowedSex = ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)
    || $fullName === ''
    || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)
    || $age === false || $age < 1 || $age > 120
    || !in_array($sex, $allowedSex, true)
    || ($password !== '' && strlen($password) < 6)
) {
    apiResponse(422, ['ok' => false, 'message' => 'Datos de perfil invalidos']);
}

$duplicate = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1');
$duplicate->execute([$email, $username, $user['id']]);
if ($duplicate->fetch()) {
    apiResponse(409, ['ok' => false, 'message' => 'El correo o usuario pertenece a otra cuenta']);
}

if ($password !== '') {
    $statement = $pdo->prepare(
        'UPDATE users SET email=?, full_name=?, username=?, age=?, sex=?, allergies=?, password=? WHERE id=?'
    );
    $statement->execute([
        $email, $fullName, $username, $age, $sex, $allergies ?: 'Ninguna',
        password_hash($password, PASSWORD_DEFAULT), $user['id'],
    ]);
} else {
    $statement = $pdo->prepare(
        'UPDATE users SET email=?, full_name=?, username=?, age=?, sex=?, allergies=? WHERE id=?'
    );
    $statement->execute([$email, $fullName, $username, $age, $sex, $allergies ?: 'Ninguna', $user['id']]);
}

$updated = $pdo->prepare(
    'SELECT id, email, full_name, username, age, sex, allergies, profile_photo FROM users WHERE id=?'
);
$updated->execute([$user['id']]);
apiResponse(200, ['ok' => true, 'message' => 'Perfil actualizado', 'user' => publicUser($updated->fetch())]);
