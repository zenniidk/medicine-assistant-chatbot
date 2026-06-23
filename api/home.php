<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('GET');
$user = requireApiUser($pdo);
$medicines = $pdo->query(
    'SELECT id, name, aliases, general_warning FROM medicines ORDER BY id'
)->fetchAll();
$ollamaSocket = @fsockopen('127.0.0.1', 11434, $errno, $error, 0.15);
$ollamaAvailable = is_resource($ollamaSocket);
if ($ollamaAvailable) {
    fclose($ollamaSocket);
}

apiResponse(200, [
    'ok' => true,
    'user' => publicUser($user),
    'medicines' => $medicines,
    'modules' => [
        'ocr' => true,
        'rag' => true,
        'ollama_available' => $ollamaAvailable,
    ],
]);
