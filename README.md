# MEDICINESCAN — avance 1

MVP académico desarrollado con PHP, MySQL, HTML, CSS y JavaScript para ejecutarse en XAMPP.

## Qué incluye

- Registro de usuario.
- Inicio y cierre de sesión.
- Perfil con nombre, edad, sexo y alergias.
- CRUD básico: crear, consultar, actualizar y eliminar la cuenta.
- Home con acceso al escáner.
- Cámara web con carga alternativa de fotografía.
- OCR con Tesseract.js para reconocer Omeprazol, Paracetamol, Aspirina, Olanzapina e Ibuprofeno.
- Burbuja tipo AR con estado verde, amarillo o rojo.

## Instalación con XAMPP

1. Copia la carpeta `MEDICINESCAN` dentro de `C:\xampp\htdocs\`.
2. Abre XAMPP e inicia **Apache** y **MySQL**.
3. Entra a `http://localhost/phpmyadmin`.
4. Selecciona **Importar** y carga `database/medicinescan.sql`.
5. Abre `http://localhost/MEDICINESCAN/`.

La configuración predeterminada usa:

- Servidor: `localhost`
- Base de datos: `medicinescan`
- Usuario: `root`
- Contraseña: vacía

Si XAMPP tiene otros datos, edita `config/database.php`.

## Nota sobre la cámara

El navegador normalmente permite la cámara en `localhost`. El OCR descarga Tesseract.js desde internet la primera vez. También se puede elegir una fotografía o usar la prueba manual.

## Importante

El resultado del escáner es una demostración, no una recomendación médica real. Para determinar seguridad se necesitan dosis, enfermedades, embarazo, interacciones, presentación y valoración profesional, además de edad, sexo y alergias.

## Documentación

- Consulta `docs/DOCUMENTACION_TECNICA.md` para conocer la arquitectura, los flujos, la base de datos y la función de cada carpeta y archivo.
- Consulta `docs/API.md` para probar login, registro, home, perfil y cámara desde Postman o Ionic.
- Consulta `docs/PLAN_DEL_PROYECTO.md` para ver el reparto sugerido del trabajo y la etapa Ionic/Angular.
