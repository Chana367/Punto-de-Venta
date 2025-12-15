@echo off

REM Leer la ruta de instalación desde el registro
for /f "usebackq tokens=2*" %%A in (`reg query "HKCU\Software\PuntoVenta" /v InstallDir`) do set InstallDir=%%B

cd "%InstallDir%"
    
REM Configurar Node.js portable si existe
if exist "%InstallDir%\node" (
    set "PATH=%InstallDir%\node;%PATH%"
)

:: Verificar si node_modules existe, si no, instalar dependencias
if not exist "%InstallDir%\node_modules" (
    echo Instalando dependencias por primera vez...
    call npm install
)

:: Iniciar el servidor PHP sin mostrar la consola, especificando el php.ini
start /b "Servidor PHP" "%InstallDir%\php\php.exe" -c "%InstallDir%\php\php.ini" -S localhost:8000 -t "%InstallDir%"

:: Esperar unos segundos para asegurarse de que el servidor PHP se haya iniciado
timeout /t 5 /nobreak >nul

:: Iniciar la aplicación Electron sin mostrar la consola
start /b "" cmd /c "npm start"

exit
