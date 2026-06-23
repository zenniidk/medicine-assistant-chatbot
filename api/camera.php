<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/ollama.php';
$user = requireApiUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $medicines = $pdo->query('SELECT id, name, aliases, general_warning FROM medicines ORDER BY id')
        ->fetchAll();
    apiResponse(200, ['ok' => true, 'medicines' => $medicines]);
}

requireMethod('POST');
$payload = jsonPayload();
$ocrText = trim((string) ($payload['text'] ?? ''));

if ($ocrText === '') {
    apiResponse(422, ['ok' => false, 'message' => 'El texto del OCR es obligatorio']);
}

function normalized(string $text): string
{
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($text, 'UTF-8'));
    return preg_replace('/[^a-z0-9\s]/', '', $converted ?: mb_strtolower($text, 'UTF-8'));
}

$normalizedText = normalized($ocrText);
$medicines = $pdo->query('SELECT id, name, aliases, general_warning FROM medicines ORDER BY id')
    ->fetchAll();
$medicine = null;

foreach ($medicines as $candidate) {
    $names = array_filter(array_map('trim', array_merge(
        [$candidate['name']],
        explode(',', (string) $candidate['aliases'])
    )));
    foreach ($names as $name) {
        if (str_contains($normalizedText, normalized($name))) {
            $medicine = $candidate;
            break 2;
        }
    }
}

if ($medicine === null) {
    apiResponse(404, [
        'ok' => false,
        'identified' => false,
        'message' => 'No se reconoció uno de los cinco medicamentos',
    ]);
}

$allergies = normalized($user['allergies']);
$medicineName = normalized($medicine['name']);
$allergyMatch = str_contains($allergies, $medicineName)
    || ($medicineName === 'aspirina' && str_contains($allergies, 'acido acetilsalicilico'))
    || (in_array($medicineName, ['aspirina', 'ibuprofeno'], true) && str_contains($allergies, 'aines'));

$level = 'safe';
$ruleMessage = 'No coincide con las alergias registradas.';
if ($allergyMatch) {
    $level = 'danger';
    $ruleMessage = 'Coincide con una alergia registrada.';
} elseif ($medicineName === 'aspirina' && (int) $user['age'] < 18) {
    $level = 'warning';
    $ruleMessage = 'Existe una precaución por la edad registrada.';
}

$documents = retrieveKnowledge($pdo, (int) $medicine['id'], $ocrText . ' ' . $ruleMessage);
$explanation = generateExplanation($medicine, $user, $level, $documents);
$sources = array_map(fn(array $document): array => [
    'title' => $document['source_title'],
    'url' => $document['source_url'],
], $documents);

$history = $pdo->prepare(
    'INSERT INTO scan_history
     (user_id, medicine_id, ocr_text, result_level, rule_message, ai_message, generated_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$history->execute([
    $user['id'], $medicine['id'], mb_substr($ocrText, 0, 5000),
    $level, $ruleMessage, $explanation['text'], $explanation['generated_by'],
]);

apiResponse(200, [
    'ok' => true,
    'identified' => true,
    'medicine' => [
        'id' => (int) $medicine['id'],
        'name' => $medicine['name'],
        'general_warning' => $medicine['general_warning'],
    ],
    'result' => [
        'level' => $level,
        'rule_message' => $ruleMessage,
        'explanation' => $explanation['text'],
        'generated_by' => $explanation['generated_by'],
        'model' => $explanation['model'],
    ],
    'sources' => $sources,
    'scan_id' => (int) $pdo->lastInsertId(),
    'disclaimer' => 'Resultado académico. No sustituye una valoración médica o farmacéutica.',
]);
