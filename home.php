<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$statement = $pdo->prepare('SELECT full_name, age, sex, allergies FROM users WHERE id = ?');
$statement->execute([$_SESSION['user_id']]);
$user = $statement->fetch();

$pageTitle = 'Inicio';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow">Hola, <?= htmlspecialchars($user['full_name']) ?></p>
        <h1>Revisa un medicamento con tu cámara</h1>
        <p>El prototipo reconoce cinco medicamentos y muestra una orientación visual basada en tu perfil.</p>
        <a class="primary-button" href="camera.php">Abrir cámara</a>
    </div>
    <div class="hero-symbol" aria-hidden="true">⌁</div>
</section>

<section>
    <h2>Tu perfil de seguridad</h2>
    <div class="profile-summary">
        <div><span>Edad</span><strong><?= (int) $user['age'] ?> años</strong></div>
        <div><span>Sexo</span><strong><?= htmlspecialchars($user['sex']) ?></strong></div>
        <div><span>Alergias registradas</span><strong><?= htmlspecialchars($user['allergies']) ?></strong></div>
        <a href="profile.php">Editar perfil</a>
    </div>
</section>

<section>
    <h2>Medicamentos disponibles en el MVP</h2>
    <div class="medicine-grid">
        <?php foreach (['Omeprazol', 'Paracetamol', 'Aspirina', 'Olanzapina', 'Ibuprofeno'] as $medicine): ?>
            <article class="medicine-card">
                <span class="pill">●</span>
                <h3><?= $medicine ?></h3>
                <p>Reconocimiento por texto del empaque.</p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
