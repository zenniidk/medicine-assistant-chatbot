<?php
require_once __DIR__ . '/../includes/session.php';

$_SESSION = [];
session_destroy();
session_start();
setMessage('success', 'Sesión cerrada.');

header('Location: ../index.php');
exit;
