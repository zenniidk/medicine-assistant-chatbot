<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])
    || $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !validCsrfToken($_POST['csrf_token'] ?? null)
) {
    header('Location: ../index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
$sex = trim($_POST['sex'] ?? '');
$allergies = trim($_POST['allergies'] ?? 'Ninguna');
$password = $_POST['password'] ?? '';
$allowedSex = ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)
    || $fullName === ''
    || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)
    || !$age
    || $age < 1
    || $age > 120
    || !in_array($sex, $allowedSex, true)
    || ($password !== '' && strlen($password) < 6)
) {
    setMessage('error', 'Hay datos no válidos. Revisa el formulario.');
    header('Location: ../profile.php');
    exit;
}

$duplicate = $pdo->prepare(
    'SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1'
);
$duplicate->execute([$email, $username, $_SESSION['user_id']]);

if ($duplicate->fetch()) {
    setMessage('error', 'Ese correo o usuario ya pertenece a otra cuenta.');
    header('Location: ../profile.php');
    exit;
}

if ($password !== '') {
    $statement = $pdo->prepare(
        'UPDATE users SET email = ?, full_name = ?, username = ?, age = ?, sex = ?,
         allergies = ?, password = ? WHERE id = ?'
    );
    $values = [
        $email, $fullName, $username, $age, $sex,
        $allergies ?: 'Ninguna', password_hash($password, PASSWORD_DEFAULT), $_SESSION['user_id']
    ];
} else {
    $statement = $pdo->prepare(
        'UPDATE users SET email = ?, full_name = ?, username = ?, age = ?, sex = ?,
         allergies = ? WHERE id = ?'
    );
    $values = [
        $email, $fullName, $username, $age, $sex,
        $allergies ?: 'Ninguna', $_SESSION['user_id']
    ];
}

$statement->execute($values);
$_SESSION['full_name'] = $fullName;
$_SESSION['username'] = $username;

setMessage('success', 'Perfil actualizado.');
header('Location: ../profile.php');
exit;
