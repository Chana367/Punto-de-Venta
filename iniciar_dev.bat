@echo off
echo Iniciando Punto de Venta (Desarrollo)...
echo.

REM Matar procesos anteriores
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM electron.exe >nul 2>&1
del "%TEMP%\punto_venta.lock" >nul 2>&1

REM Iniciar servidor PHP
echo [1/2] Iniciando servidor PHP...
start "PHP Server" /MIN cmd /c "php\php.exe -c php\php.ini -S 127.0.0.1:8000 -t ."

REM Esperar 3 segundos
timeout /t 3 /nobreak >nul

REM Iniciar Electron
echo [2/2] Iniciando aplicacion...
npm start

echo.
echo Aplicacion cerrada.
pause
