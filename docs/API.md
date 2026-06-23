# API de MEDICINESCAN

Base local:

```text
http://localhost/MEDICINESCAN/api
```

Las rutas protegidas reciben:

```text
Authorization: Bearer TOKEN
Content-Type: application/json
```

## Autenticación

### Registro

`POST /signup.php`

```json
{
  "email": "persona@correo.com",
  "full_name": "Nombre Completo",
  "username": "persona",
  "password": "Secreto123",
  "age": 25,
  "sex": "Femenino",
  "allergies": "Ninguna"
}
```

Devuelve `201`, el usuario y un token válido durante 30 días.

### Login

`POST /login.php`

```json
{
  "username": "persona",
  "password": "Secreto123"
}
```

`username` también acepta el correo electrónico.

### Logout

`POST /logout.php`, con token Bearer.

## Home

`GET /home.php`

Devuelve el usuario, los cinco medicamentos y el estado de los módulos OCR, RAG y Ollama.

## Perfil

- `GET /profile.php`: consulta el perfil.
- `PUT /profile.php`: actualiza el perfil.
- `DELETE /profile.php`: elimina la cuenta.

Ejemplo de actualización parcial:

```json
{
  "age": 26,
  "allergies": "AINEs"
}
```

## Cámara y análisis

`GET /camera.php` devuelve los cinco medicamentos disponibles.

`POST /camera.php` analiza el texto producido por el OCR:

```json
{
  "text": "IBUPROFENO 400 mg"
}
```

La respuesta contiene:

- Medicamento identificado.
- Nivel calculado por reglas: `safe`, `warning` o `danger`.
- Explicación generada por Ollama o por el respaldo determinista.
- Fuentes recuperadas por RAG.
- Identificador del historial.

## Ollama y RAG

La API funciona sin Ollama mediante mensajes controlados por reglas. Para activar Llama y los embeddings:

```powershell
ollama pull llama3.2:3b
ollama pull embeddinggemma
```

Ollama debe estar disponible en `http://127.0.0.1:11434`.

La configuración está en `config/ai.php`. También puede modificarse con:

```text
OLLAMA_URL
OLLAMA_CHAT_MODEL
OLLAMA_EMBED_MODEL
```

Los embeddings se generan y guardan automáticamente la primera vez que se consulta cada documento. Solo existen documentos para Omeprazol, Paracetamol, Aspirina, Olanzapina e Ibuprofeno.

## Uso desde un teléfono

En un teléfono, `localhost` apunta al teléfono. Se debe utilizar la IP local de la computadora:

```text
http://192.168.1.20/MEDICINESCAN/api/login.php
```

Apache debe permitir conexiones desde la red local y ambos dispositivos deben estar en la misma red.
