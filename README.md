# MEDICINESCAN

Aplicacion academica para escaneo y asistencia sobre medicamentos. Esta version separa el proyecto en:

- `backend/`: API PHP conectada a MySQL.
- `frontend/`: frontend Ionic/Angular.
- `Docs/`: documentacion y manual de usuario.
- `backend/Obsidian/`: carpeta preparada para personalidad/conocimiento de IA.

## Requisitos

- XAMPP con MySQL.
- PHP de XAMPP.
- Node.js y npm.
- Base de datos MySQL `medicinescan`.

## Levantar el proyecto

La forma rapida es ejecutar:

```bat
iniciar_medicinescan.bat
```

Esto revisa/levanta:

- MySQL en `3306`
- Backend en `http://127.0.0.1:8000`
- Frontend en `http://127.0.0.1:8100`

Luego abre:

```text
http://127.0.0.1:8100
```

## Comandos manuales

Backend:

```bat
cd backend
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Frontend:

```bat
cd frontend
npm install
npm start -- --host 127.0.0.1 --port 8100
```

## APIs principales

Base URL:

```text
http://127.0.0.1:8000/api
```

Endpoints:

- `GET /health.php`
- `POST /signup.php`
- `POST /login.php`
- `GET /profile.php`
- `PUT /profile.php`
- `DELETE /profile.php`
- `POST /profile_photo.php`
- `POST /logout.php`

La coleccion de Postman esta en:

```text
MEDICINESCAN.postman_collection.json
```

## Nota medica

Este proyecto es una demostracion academica. No sustituye la valoracion de un profesional de salud.
