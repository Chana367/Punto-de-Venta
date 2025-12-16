@echo off
setlocal enabledelayedexpansion

REM Leer la ruta de instalación desde el registro
for /f "usebackq tokens=2*" %%A in (`reg query "HKCU\Software\PuntoVenta" /v InstallDir 2^>nul`) do set InstallDir=%%B
if "%InstallDir%"=="" (
    for /f "usebackq tokens=2*" %%A in (`reg query "HKLM\Software\PuntoVenta" /v InstallDir 2^>nul`) do set InstallDir=%%B
)

if "%InstallDir%"=="" (
    echo Error: No se encontró la instalación
    pause
    exit /b 1
)

cd /d "%InstallDir%"

REM Configurar Node.js portable si existe
if exist "%InstallDir%\node" (
    set "PATH=%InstallDir%\node;%PATH%"
)

REM Matar procesos PHP anteriores del proyecto para evitar conflictos
wmic process where "name='php.exe' and CommandLine like '%%127.0.0.1:8000%%'" call terminate >nul 2>&1

REM Esperar un momento
timeout /t 2 /nobreak >nul

REM Iniciar el servidor PHP en una ventana minimizada
start "Servidor PHP" /MIN cmd /c ""%InstallDir%\php\php.exe" -c "%InstallDir%\php\php.ini" -S 127.0.0.1:8000 -t "%InstallDir%""

REM Esperar a que el servidor esté listo (verificar múltiples veces)
echo Esperando al servidor PHP...
timeout /t 3 /nobreak >nul

REM Verificar si el servidor está corriendo
netstat -an | find "127.0.0.1:8000" | find "LISTENING" >nul 2>&1
if errorlevel 1 (
    echo Esperando un poco más...
    timeout /t 3 /nobreak >nul
)

REM Iniciar Electron directamente
if exist "%InstallDir%\node_modules\electron\dist\electron.exe" (
    start "" "%InstallDir%\node_modules\electron\dist\electron.exe" "%InstallDir%\main.js"
) else (
    echo Error: Electron no encontrado. Ejecutando npm start...
    call npm start
)
