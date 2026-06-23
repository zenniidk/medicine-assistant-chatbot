<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$statement = $pdo->prepare('SELECT age, allergies FROM users WHERE id = ?');
$statement->execute([$_SESSION['user_id']]);
$user = $statement->fetch();

$pageTitle = 'Escáner';
require __DIR__ . '/includes/header.php';
?>
<section class="scanner-heading">
    <div>
        <p class="eyebrow">Prototipo OCR + interfaz AR</p>
        <h1>Escanea el nombre del medicamento</h1>
        <p>Coloca el texto del empaque dentro del recuadro y toma una captura.</p>
    </div>
    <span class="status-chip" id="cameraStatus">Cámara apagada</span>
</section>

<section
    class="scanner"
    id="scanner"
    data-age="<?= (int) $user['age'] ?>"
    data-allergies="<?= htmlspecialchars($user['allergies'], ENT_QUOTES) ?>"
>
    <video id="camera" autoplay playsinline muted></video>
    <canvas id="captureCanvas" hidden></canvas>
    <div class="focus-frame">
        <span></span><span></span><span></span><span></span>
    </div>
    <div class="ar-result" id="arResult" hidden>
        <div class="ar-icon" id="arIcon">?</div>
        <div>
            <strong id="medicineName">Medicamento</strong>
            <p id="safetyMessage"></p>
            <div class="result-sources" id="resultSources"></div>
        </div>
    </div>
    <div class="scan-loading" id="scanLoading" hidden>Analizando… <b id="progress">0%</b></div>
</section>

<div class="scanner-controls">
    <button class="primary-button" id="startCamera" type="button">Encender cámara</button>
    <button class="secondary-button" id="scanButton" type="button" disabled>Escanear texto</button>
    <label class="secondary-button upload-button">
        Elegir fotografía
        <input id="imageUpload" type="file" accept="image/*" capture="environment">
    </label>
</div>

<details class="test-panel">
    <summary>Prueba rápida sin cámara</summary>
    <p>Escribe uno de los cinco nombres para probar la burbuja AR.</p>
    <div class="inline-test">
        <input id="manualMedicine" placeholder="Ejemplo: Paracetamol">
        <button class="secondary-button" id="manualTest" type="button">Probar</button>
    </div>
</details>

<section class="notice">
    <strong>Importante:</strong>
    Esta clasificación es una demostración académica. Una alergia no es el único factor necesario para decidir si un medicamento es seguro.
</section>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="assets/js/camera.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
