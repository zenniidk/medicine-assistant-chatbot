<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken($_POST['csrf_token'] ?? null)) {
    setMessage('error', 'Solicitud no válida.');
    header('Location: ../index.php');
    exit;
}

$user = trim($_POST['user'] ?? '');
$password = $_POST['password'] ?? '';

$statement = $pdo->prepare(
    'SELECT id, full_name, username, password FROM users WHERE username = ? OR email = ? LIMIT 1'
);
$statement->execute([$user, $user]);
$account = $statement->fetch();

if (!$account || !password_verify($password, $account['password'])) {
    setMessage('error', 'Usuario/correo o contraseña incorrectos.');
    header('Location: ../index.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $account['id'];
$_SESSION['full_name'] = $account['full_name'];
$_SESSION['username'] = $account['username'];

setMessage('success', 'Sesión iniciada correctamente.');
header('Location: ../home.php');
exit;
