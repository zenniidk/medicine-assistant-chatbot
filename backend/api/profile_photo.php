<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$user = requireApiUser($pdo);
requireMethod('POST');

if (empty($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
    apiResponse(422, ['ok' => false, 'message' => 'Selecciona una foto para subir']);
}

$photo = $_FILES['photo'];
if (($photo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    apiResponse(422, ['ok' => false, 'message' => 'No se pudo recibir la foto']);
}

if (($photo['size'] ?? 0) > 2 * 1024 * 1024) {
    apiResponse(422, ['ok' => false, 'message' => 'La foto debe pesar menos de 2 MB']);
}

$imageInfo = @getimagesize($photo['tmp_name']);
if ($imageInfo === false) {
    apiResponse(422, ['ok' => false, 'message' => 'El archivo debe ser una imagen']);
}

$allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
$imageType = $imageInfo[2] ?? null;
if (!isset($allowedTypes[$imageType])) {
    apiResponse(422, ['ok' => false, 'message' => 'Usa una imagen JPG, PNG o WebP']);
}

$uploadDir = dirname(__DIR__) . '/uploads/profile';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    apiResponse(500, ['ok' => false, 'message' => 'No se pudo preparar la carpeta de fotos']);
}

$filename = 'user_' . (int) $user['id'] . '_' . time() . '.' . $allowedTypes[$imageType];
$targetPath = $uploadDir . '/' . $filename;
if (!move_uploaded_file($photo['tmp_name'], $targetPath)) {
    apiResponse(500, ['ok' => false, 'message' => 'No se pudo guardar la foto']);
}

if (!empty($user['profile_photo'])) {
    $previousPath = dirname(__DIR__) . '/' . ltrim((string) $user['profile_photo'], '/');
    if (is_file($previousPath)) {
        @unlink($previousPath);
    }
}

$relativePath = 'uploads/profile/' . $filename;
$statement = $pdo->prepare('UPDATE users SET profile_photo = ? WHERE id = ?');
$statement->execute([$relativePath, $user['id']]);

$updated = $pdo->prepare(
    'SELECT id, email, full_name, username, age, sex, allergies, profile_photo FROM users WHERE id=?'
);
$updated->execute([$user['id']]);

apiResponse(200, ['ok' => true, 'message' => 'Foto actualizada', 'user' => publicUser($updated->fetch())]);
