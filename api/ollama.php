<?php

declare(strict_types=1);

function ollamaRequest(string $path, array $payload): ?array
{
    $config = require __DIR__ . '/../config/ai.php';
    $curl = curl_init(rtrim($config['ollama_url'], '/') . $path);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => $config['timeout_seconds'],
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if (!is_string($response) || $status < 200 || $status >= 300) {
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function ollamaEmbedding(string $text): ?array
{
    $config = require __DIR__ . '/../config/ai.php';
    $response = ollamaRequest('/api/embed', [
        'model' => $config['embedding_model'],
        'input' => $text,
    ]);
    $embedding = $response['embeddings'][0] ?? null;
    return is_array($embedding) ? $embedding : null;
}

function cosineSimilarity(array $left, array $right): float
{
    if (count($left) !== count($right) || $left === []) {
        return -1.0;
    }
    $dot = $leftNorm = $rightNorm = 0.0;
    foreach ($left as $index => $value) {
        $a = (float) $value;
        $b = (float) $right[$index];
        $dot += $a * $b;
        $leftNorm += $a * $a;
        $rightNorm += $b * $b;
    }
    return ($leftNorm > 0 && $rightNorm > 0)
        ? $dot / (sqrt($leftNorm) * sqrt($rightNorm))
        : -1.0;
}

function retrieveKnowledge(PDO $pdo, int $medicineId, string $query): array
{
    $statement = $pdo->prepare(
        'SELECT id, title, content, source_title, source_url
         FROM medicine_documents
         WHERE medicine_id = ?'
    );
    $statement->execute([$medicineId]);
    $documents = $statement->fetchAll();
    $queryEmbedding = ollamaEmbedding($query);

    if ($queryEmbedding === null) {
        return array_slice($documents, 0, 3);
    }

    $config = require __DIR__ . '/../config/ai.php';
    foreach ($documents as &$document) {
        $embeddingStatement = $pdo->prepare(
            'SELECT vector_json FROM medicine_embeddings
             WHERE document_id = ? AND model = ? LIMIT 1'
        );
        $embeddingStatement->execute([$document['id'], $config['embedding_model']]);
        $stored = $embeddingStatement->fetchColumn();
        $vector = $stored ? json_decode((string) $stored, true) : null;

        if (!is_array($vector)) {
            $vector = ollamaEmbedding($document['title'] . "\n" . $document['content']);
            if ($vector !== null) {
                $save = $pdo->prepare(
                    'INSERT INTO medicine_embeddings (document_id, model, vector_json)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE vector_json = VALUES(vector_json)'
                );
                $save->execute([
                    $document['id'],
                    $config['embedding_model'],
                    json_encode($vector),
                ]);
            }
        }
        $document['score'] = is_array($vector)
            ? cosineSimilarity($queryEmbedding, $vector)
            : -1.0;
    }
    unset($document);

    usort($documents, fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return array_slice($documents, 0, 3);
}

function generateExplanation(array $medicine, array $user, string $level, array $documents): array
{
    $fallback = match ($level) {
        'danger' => 'El medicamento coincide con una alergia registrada. Evita usarlo y consulta a un profesional de salud.',
        'warning' => 'Existe una precaución relevante para tu perfil. Consulta a un médico o farmacéutico antes de usarlo.',
        default => 'No coincide con las alergias registradas, pero aún debes confirmar dosis, enfermedades e interacciones con un profesional.',
    };

    $context = implode("\n\n", array_map(
        fn(array $document): string => $document['title'] . ': ' . $document['content'],
        $documents
    ));
    $config = require __DIR__ . '/../config/ai.php';
    $response = ollamaRequest('/api/chat', [
        'model' => $config['chat_model'],
        'stream' => false,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un asistente de un prototipo académico. Responde en español, máximo 80 palabras. Usa exclusivamente el contexto proporcionado. No indiques dosis, no diagnostiques y no afirmes que un medicamento es seguro. Recomienda consultar a un profesional.',
            ],
            [
                'role' => 'user',
                'content' => "Medicamento: {$medicine['name']}\nEdad: {$user['age']}\nAlergias: {$user['allergies']}\nNivel calculado por reglas: $level\nContexto:\n$context",
            ],
        ],
    ]);
    $text = trim((string) ($response['message']['content'] ?? ''));

    return [
        'text' => $text !== '' ? $text : $fallback,
        'generated_by' => $text !== '' ? 'ollama' : 'rules',
        'model' => $text !== '' ? $config['chat_model'] : null,
    ];
}
