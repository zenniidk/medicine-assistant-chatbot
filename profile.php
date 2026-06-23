<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$statement = $pdo->prepare(
    'SELECT email, full_name, username, age, sex, allergies FROM users WHERE id = ?'
);
$statement->execute([$_SESSION['user_id']]);
$user = $statement->fetch();

$pageTitle = 'Mi perfil';
require __DIR__ . '/includes/header.php';
?>
<section class="content-card narrow">
    <p class="eyebrow">CRUD de usuario</p>
    <h1>Mi perfil</h1>
    <p>Actualiza los datos que utilizará el prototipo para mostrar sus alertas.</p>

    <form class="profile-form" action="actions/update_profile.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= createCsrfToken() ?>">

        <label>Nombre completo
            <input name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </label>

        <label>Nombre de usuario
            <input name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </label>

        <label>Correo electrónico
            <input name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </label>

        <div class="form-row">
            <label>Edad
                <input name="age" type="number" min="1" max="120" value="<?= (int) $user['age'] ?>" required>
            </label>
            <label>Sexo
                <select name="sex" required>
                    <?php foreach (['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'] as $option): ?>
                        <option value="<?= $option ?>" <?= $user['sex'] === $option ? 'selected' : '' ?>>
                            <?= $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <label>Alergias
            <textarea name="allergies" rows="3" placeholder="Ejemplo: aspirina, ibuprofeno"><?= htmlspecialchars($user['allergies']) ?></textarea>
        </label>

        <label>Nueva contraseña (opcional)
            <input name="password" type="password" minlength="6" placeholder="Déjala vacía para conservar la actual">
        </label>

        <button class="primary-button" type="submit">Guardar cambios</button>
    </form>

    <hr>

    <form action="actions/delete_profile.php" method="post" onsubmit="return confirm('¿Eliminar tu cuenta definitivamente?');">
        <input type="hidden" name="csrf_token" value="<?= createCsrfToken() ?>">
        <button class="danger-button" type="submit">Eliminar mi cuenta</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
