<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken($_POST['csrf_token'] ?? null)) {
    setMessage('error', 'Solicitud no válida.');
    header('Location: ../index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
$sex = trim($_POST['sex'] ?? '');
$allergies = trim($_POST['allergies'] ?? 'Ninguna');
$allowedSex = ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)
    || $fullName === ''
    || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)
    || strlen($password) < 6
    || !$age
    || $age < 1
    || $age > 120
    || !in_array($sex, $allowedSex, true)
) {
    setMessage('error', 'Revisa los datos. El usuario debe tener de 3 a 30 caracteres y la contraseña mínimo 6.');
    header('Location: ../index.php?form=signup');
    exit;
}

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$exists->execute([$email, $username]);

if ($exists->fetch()) {
    setMessage('error', 'El correo o el nombre de usuario ya están registrados.');
    header('Location: ../index.php?form=signup');
    exit;
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
    $allergies === '' ? 'Ninguna' : $allergies
]);

setMessage('success', 'Cuenta creada. Ya puedes iniciar sesión.');
header('Location: ../index.php');
exit;
