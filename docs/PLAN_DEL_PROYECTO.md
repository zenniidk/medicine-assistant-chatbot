# Plan sencillo del proyecto MEDICINESCAN

## Objetivo del MVP

Demostrar el recorrido principal:

1. El usuario se registra e inicia sesión.
2. Completa su perfil básico.
3. Abre la cámara.
4. El OCR lee el nombre del medicamento.
5. La interfaz muestra una burbuja visual de orientación.

## Estructura actual

```text
MEDICINESCAN/
├── actions/             Procesos PHP de login, registro y CRUD
├── assets/
│   ├── css/             Diseño de login y aplicación
│   └── js/              Cambio de formularios, cámara y OCR
├── config/              Conexión a MySQL
├── database/            Script para crear la base de datos
├── docs/                Documentación del equipo
├── includes/            Sesión, protección y partes reutilizables
├── camera.php           Pantalla de cámara/OCR/AR
├── home.php             Pantalla principal
├── index.php            Login y registro
└── profile.php          Consulta, edición y eliminación del perfil
```

## CRUD explicado

- **Create:** `register.php` crea un usuario.
- **Read:** `home.php` y `profile.php` consultan sus datos.
- **Update:** `update_profile.php` actualiza sus datos.
- **Delete:** `delete_profile.php` elimina la cuenta.

## Reparto sugerido

- Persona 1: login, registro y validaciones.
- Persona 2: home, estilos y experiencia de usuario.
- Persona 3: cámara, OCR y pruebas con empaques.
- Persona 4: base de datos, documentación y exposición.

## Cómo funciona el OCR y la “AR”

Tesseract.js convierte la fotografía en texto. JavaScript busca uno de los cinco nombres permitidos. Después se dibuja una tarjeta sobre el video; esa superposición imita una interfaz AR básica.

Esto no es todavía reconocimiento visual del medicamento ni AR espacial. Para el avance inicial es suficiente para demostrar el concepto sin entrenar un modelo.

## Segunda etapa: Ionic + Angular

Cuando la versión PHP esté estable:

1. Crear el proyecto Ionic Angular.
2. Convertir login, home y cámara en páginas/componentes.
3. Mantener PHP como una API que responda JSON.
4. Consumir esa API desde Angular con `HttpClient`.
5. Usar Capacitor Camera para la cámara del teléfono.
6. Evaluar OCR local o servicio externo.

No conviene copiar PHP dentro de Ionic: PHP permanece en el servidor y Angular/Ionic forma la aplicación cliente.

## Mejoras futuras

- Separar alergias en una tabla propia.
- Añadir dosis, padecimientos, embarazo y medicamentos actuales.
- Guardar historial de escaneos.
- Consultar una fuente farmacológica validada.
- Probar accesibilidad y distintos tamaños de pantalla.
- Solicitar revisión de un profesional de salud.
