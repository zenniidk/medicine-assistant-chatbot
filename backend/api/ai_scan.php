<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$user = requireApiUser($pdo);
$payload = jsonPayload();
$ocrText = trim((string) ($payload['ocr_text'] ?? ''));

if ($ocrText === '' || strlen($ocrText) < 3) {
    apiResponse(422, ['ok' => false, 'message' => 'Escribe o escanea texto del medicamento']);
}

$medicine = detectMedicine($pdo, $ocrText);
$rule = buildRuleResult($user, $medicine, $ocrText);
$obsidian = readObsidianContext();
$prompt = buildScannerPrompt($obsidian['context'], $user, $ocrText, $medicine, $rule);
$ollama = callOllama($prompt);

$aiMessage = $ollama['ok'] ? $ollama['message'] : fallbackAiMessage($medicine, $rule);
$generatedBy = $ollama['ok'] ? 'ollama' : 'rules';
$saved = false;

if ($medicine !== null) {
    $statement = $pdo->prepare(
        'INSERT INTO scan_history
            (user_id, medicine_id, ocr_text, result_level, rule_message, ai_message, generated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $user['id'],
        $medicine['id'],
        $ocrText,
        $rule['level'],
        $rule['message'],
        $aiMessage,
        $generatedBy,
    ]);
    $saved = true;
}

apiResponse(200, [
    'ok' => true,
    'model' => 'qwen3:8b',
    'generated_by' => $generatedBy,
    'ai_available' => $ollama['ok'],
    'ai_error' => $ollama['ok'] ? null : $ollama['error'],
    'obsidian_files' => $obsidian['files'],
    'medicine' => $medicine ? [
        'id' => (int) $medicine['id'],
        'name' => $medicine['name'],
        'aliases' => $medicine['aliases'],
        'general_warning' => $medicine['general_warning'],
    ] : null,
    'result_level' => $rule['level'],
    'rule_message' => $rule['message'],
    'ai_message' => $aiMessage,
    'saved' => $saved,
]);

function detectMedicine(PDO $pdo, string $ocrText): ?array
{
    $statement = $pdo->query('SELECT id, name, aliases, general_warning FROM medicines ORDER BY id');
    $text = normalizeText($ocrText);

    foreach ($statement->fetchAll() as $medicine) {
        $terms = medicineTerms($medicine);
        foreach ($terms as $term) {
            if ($term !== '' && str_contains($text, normalizeText($term))) {
                return $medicine;
            }
        }
    }

    return null;
}

function medicineTerms(array $medicine): array
{
    $terms = [$medicine['name']];
    $aliases = array_filter(array_map('trim', explode(',', (string) ($medicine['aliases'] ?? ''))));
    return array_values(array_unique(array_merge($terms, $aliases)));
}

function buildRuleResult(array $user, ?array $medicine, string $ocrText): array
{
    $allergyText = normalizeText((string) ($user['allergies'] ?? ''));
    $ocr = normalizeText($ocrText);

    if ($medicine === null) {
        return [
            'level' => 'warning',
            'message' => 'No se detecto un medicamento conocido en la base local.',
        ];
    }

    $terms = medicineTerms($medicine);
    foreach ($terms as $term) {
        $normalized = normalizeText($term);
        if ($normalized !== '' && str_contains($allergyText, $normalized)) {
            return [
                'level' => 'danger',
                'message' => 'El medicamento detectado coincide con una alergia registrada.',
            ];
        }
    }

    $allergyTerms = preg_split('/[,;\\n]+/', (string) ($user['allergies'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($allergyTerms as $term) {
        $normalized = normalizeText(trim($term));
        if (strlen($normalized) >= 4 && str_contains($ocr, $normalized)) {
            return [
                'level' => 'danger',
                'message' => 'El texto escaneado menciona una alergia registrada en el perfil.',
            ];
        }
    }

    if (str_contains($allergyText, 'aine') && in_array(normalizeText($medicine['name']), ['ibuprofeno', 'aspirina'], true)) {
        return [
            'level' => 'danger',
            'message' => 'El usuario registro alergia a AINEs y el medicamento pertenece a esa familia.',
        ];
    }

    return [
        'level' => 'safe',
        'message' => 'No se encontro coincidencia directa entre el medicamento y las alergias registradas.',
    ];
}

function readObsidianContext(): array
{
    $base = dirname(__DIR__) . '/Obsidian';
    $relativeFiles = [
        'personalidad/identidad.md',
        'personalidad/tono.md',
        'personalidad/limites.md',
        'prompts/scanner_system_prompt.md',
        'medicina/advertencias.md',
    ];

    $sections = [];
    $loadedFiles = [];
    foreach ($relativeFiles as $relativeFile) {
        $path = $base . '/' . $relativeFile;
        if (is_file($path)) {
            $loadedFiles[] = $relativeFile;
            $sections[] = "### {$relativeFile}\n" . trim((string) file_get_contents($path));
        }
    }

    return [
        'files' => $loadedFiles,
        'context' => implode("\n\n", $sections),
    ];
}

function buildScannerPrompt(string $obsidianContext, array $user, string $ocrText, ?array $medicine, array $rule): string
{
    $medicineText = $medicine
        ? $medicine['name'] . ' | Advertencia local: ' . $medicine['general_warning']
        : 'No detectado';

    return trim("
CONTEXTO DE OBSIDIAN:
{$obsidianContext}

DATOS DEL USUARIO:
Alergias registradas: {$user['allergies']}

TEXTO ESCANEADO:
{$ocrText}

MEDICAMENTO DETECTADO:
{$medicineText}

RESULTADO DE REGLAS:
Nivel: {$rule['level']}
Mensaje: {$rule['message']}

INSTRUCCION:
Responde al usuario en 2 o 3 parrafos cortos. No cambies el nivel de riesgo. No des dosis. No afirmes seguridad absoluta.
");
}

function callOllama(string $prompt): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => '', 'error' => 'La extension cURL de PHP no esta disponible'];
    }

    $payload = json_encode([
        'model' => 'qwen3:8b',
        'prompt' => $prompt,
        'stream' => false,
        'think' => false,
        'options' => [
            'temperature' => 0.25,
            'num_predict' => 260,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('http://127.0.0.1:11434/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 90,
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $status < 200 || $status >= 300) {
        return ['ok' => false, 'message' => '', 'error' => $error ?: "Ollama respondio HTTP {$status}"];
    }

    $data = json_decode((string) $raw, true);
    $message = trim((string) ($data['response'] ?? ''));
    if ($message === '') {
        return ['ok' => false, 'message' => '', 'error' => 'Ollama no devolvio texto'];
    }

    $message = preg_replace('/<think>.*?<\\/think>/is', '', $message) ?? $message;
    return ['ok' => true, 'message' => trim($message), 'error' => null];
}

function fallbackAiMessage(?array $medicine, array $rule): string
{
    $name = $medicine['name'] ?? 'el texto escaneado';
    if ($rule['level'] === 'danger') {
        return "Se detecto una alerta importante relacionada con {$name}. Evita usarlo sin consultar a un profesional de salud, especialmente si coincide con tus alergias registradas.";
    }

    if ($rule['level'] === 'warning') {
        return "No se pudo confirmar completamente el medicamento o la informacion es limitada. Revisa la etiqueta completa y consulta a un profesional si tienes dudas.";
    }

    return "No se encontro una coincidencia directa con tus alergias registradas para {$name}. Aun asi, revisa la etiqueta y consulta a un profesional si presentas sintomas o tienes dudas.";
}

function normalizeText(string $text): string
{
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = strtr($text, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u',
    ]);
    return preg_replace('/\\s+/', ' ', $text) ?? $text;
}
