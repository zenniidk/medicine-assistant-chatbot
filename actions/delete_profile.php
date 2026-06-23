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

$statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
$statement->execute([$_SESSION['user_id']]);

$_SESSION = [];
session_destroy();
session_start();
setMessage('success', 'Tu cuenta fue eliminada.');

header('Location: ../index.php');
exit;
