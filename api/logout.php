<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');
requireApiUser($pdo);
$pdo->prepare('DELETE FROM api_tokens WHERE token_hash = ?')
    ->execute([hash('sha256', (string) bearerToken())]);
apiResponse(200, ['ok' => true, 'message' => 'Sesión cerrada']);
