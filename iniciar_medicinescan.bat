@echo off
setlocal

set "PROJECT_DIR=C:\Users\angel\OneDrive\Documentos\MEDICINESCAN_NUEVO"
set "BACKEND_DIR=%PROJECT_DIR%\backend"
set "FRONTEND_DIR=%PROJECT_DIR%\frontend"
set "PHP_EXE=C:\xampp\php\php.exe"
set "MYSQL_EXE=C:\xampp\mysql\bin\mysqld.exe"
set "MYSQL_INI=C:\xampp\mysql\bin\my.ini"

echo ==========================================
echo   Iniciando MEDICINESCAN
echo ==========================================
echo.

echo [1/3] Revisando MySQL...
powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  echo MySQL no esta activo. Abriendo MySQL de XAMPP...
  start "MEDICINESCAN MySQL" /min "%MYSQL_EXE%" --defaults-file="%MYSQL_INI%" --standalone
) else (
  echo MySQL ya esta activo en el puerto 3306.
)

echo.
echo [2/3] Revisando backend PHP en puerto 8000...
powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  echo Backend no esta activo. Abriendo http://127.0.0.1:8000 ...
  start "MEDICINESCAN Backend 8000" cmd /k "cd /d "%BACKEND_DIR%" && "%PHP_EXE%" -S 127.0.0.1:8000 -t ."
) else (
  echo Backend ya esta activo en el puerto 8000.
)

echo.
echo [3/3] Revisando frontend en puerto 8100...
powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Get-NetTCPConnection -LocalPort 8100 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  echo Frontend no esta activo. Abriendo http://127.0.0.1:8100 ...
  start "MEDICINESCAN Frontend 8100" cmd /k "cd /d "%FRONTEND_DIR%" && npm start -- --host 127.0.0.1 --port 8100"
) else (
  echo Frontend ya esta activo en el puerto 8100.
)

echo.
echo Espera unos segundos y abre:
echo   Frontend: http://127.0.0.1:8100
echo   API:      http://127.0.0.1:8000/api/health.php
echo.
echo Puedes cerrar esta ventana. Las ventanas de Backend y Frontend deben quedarse abiertas mientras uses la app.
pause
