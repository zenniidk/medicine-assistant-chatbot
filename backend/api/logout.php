<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$token = bearerToken();
if ($token) {
    $statement = $pdo->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
    $statement->execute([hash('sha256', $token)]);
}

apiResponse(200, ['ok' => true, 'message' => 'Sesion cerrada']);
