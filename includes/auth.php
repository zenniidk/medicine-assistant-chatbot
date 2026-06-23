<?php
require_once __DIR__ . '/session.php';

if (empty($_SESSION['user_id'])) {
    setMessage('error', 'Inicia sesión para continuar.');
    header('Location: index.php');
    exit;
}
