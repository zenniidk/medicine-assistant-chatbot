<?php

return [
    'ollama_url' => getenv('OLLAMA_URL') ?: 'http://127.0.0.1:11434',
    'chat_model' => getenv('OLLAMA_CHAT_MODEL') ?: 'llama3.2:3b',
    'embedding_model' => getenv('OLLAMA_EMBED_MODEL') ?: 'embeddinggemma',
    'timeout_seconds' => 20,
];
