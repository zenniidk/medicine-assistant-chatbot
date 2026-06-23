<?php
require_once __DIR__ . '/session.php';
$pageTitle = $pageTitle ?? 'MEDICINESCAN';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | MEDICINESCAN</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="home.php">
        <span class="brand-icon">✚</span>
        MEDICINESCAN
    </a>
    <nav>
        <a class="<?= $currentPage === 'home.php' ? 'selected' : '' ?>" href="home.php">Inicio</a>
        <a class="<?= $currentPage === 'camera.php' ? 'selected' : '' ?>" href="camera.php">Escanear</a>
        <a class="<?= $currentPage === 'profile.php' ? 'selected' : '' ?>" href="profile.php">Mi perfil</a>
        <a href="actions/logout.php">Salir</a>
    </nav>
</header>
<main class="page">
<?php if ($message = getMessage()): ?>
    <div class="alert <?= htmlspecialchars($message['type']) ?>">
        <?= htmlspecialchars($message['text']) ?>
    </div>
<?php endif; ?>
