<?php
require_once __DIR__ . '/includes/session.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | MEDICINESCAN</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="app-title">
    <span>✚</span>
    <div>
        <h1>MEDICINESCAN</h1>
        <p>Escanea. Comprueba. Cuídate.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="auth-alert <?= htmlspecialchars($message['type']) ?>">
        <?= htmlspecialchars($message['text']) ?>
    </div>
<?php endif; ?>

<section class="auth-card">
    <div class="auth-tabs" role="tablist" aria-label="Acceso a MEDICINESCAN">
        <button class="tab-button active" id="loginTab" type="button">Iniciar sesión</button>
        <button class="tab-button" id="signupTab" type="button">Crear cuenta</button>
    </div>

    <form class="auth-form" id="loginForm" action="actions/login.php" method="post">
        <h2>Bienvenido</h2>
        <p>Ingresa con el usuario o correo que registraste.</p>
        <input type="hidden" name="csrf_token" value="<?= createCsrfToken() ?>">

        <label for="loginUser">Usuario o correo</label>
        <input id="loginUser" name="user" type="text" autocomplete="username" required>

        <label for="loginPassword">Contraseña</label>
        <input id="loginPassword" name="password" type="password" autocomplete="current-password" required>

        <button class="submit-button" type="submit">Ingresar</button>
    </form>

    <form class="auth-form" id="signupForm" action="actions/register.php" method="post" hidden>
        <h2>Crear cuenta</h2>
        <p>Completa tu perfil básico para usar el escáner.</p>
        <input type="hidden" name="csrf_token" value="<?= createCsrfToken() ?>">

        <label for="registerEmail">Correo electrónico</label>
        <input id="registerEmail" name="email" type="email" autocomplete="email" required>

        <label for="fullName">Nombre completo</label>
        <input id="fullName" name="full_name" type="text" autocomplete="name" required>

        <label for="registerUsername">Nombre de usuario</label>
        <input id="registerUsername" name="username" type="text" autocomplete="username" required>

        <label for="registerPassword">Contraseña</label>
        <input id="registerPassword" name="password" type="password" minlength="6" autocomplete="new-password" required>
        <small>Mínimo 6 caracteres.</small>

        <div class="form-row">
            <div>
                <label for="age">Edad</label>
                <input id="age" name="age" type="number" min="1" max="120" required>
            </div>
            <div>
                <label for="sex">Sexo</label>
                <select id="sex" name="sex" required>
                    <option value="">Selecciona</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Otro">Otro</option>
                    <option value="Prefiero no decir">Prefiero no decir</option>
                </select>
            </div>
        </div>

        <label for="allergies">Alergias</label>
        <input id="allergies" name="allergies" type="text" placeholder="Ejemplo: aspirina, ibuprofeno o Ninguna">

        <button class="submit-button" type="submit">Registrarme</button>
    </form>
</section>

<script src="assets/js/auth.js"></script>
</body>
</html>
