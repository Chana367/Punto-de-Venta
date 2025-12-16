@echo off
echo ========================================
echo   LIMPIEZA DE PROCESOS
echo   Punto de Venta
echo ========================================
echo.
echo Este script cerrará todos los procesos PHP
echo relacionados con el Punto de Venta.
echo.
pause

REM Obtener la ruta de instalación
for /f "usebackq tokens=2*" %%A in (`reg query "HKCU\Software\PuntoVenta" /v InstallDir 2^>nul`) do set InstallDir=%%B
if "%InstallDir%"=="" (
    for /f "usebackq tokens=2*" %%A in (`reg query "HKLM\Software\PuntoVenta" /v InstallDir 2^>nul`) do set InstallDir=%%B
)

if "%InstallDir%"=="" (
    echo Error: No se encontró la instalación
    pause
    exit /b 1
)

REM Eliminar archivo de bloqueo si existe (en carpeta temporal)
if exist "%TEMP%\punto_venta.lock" (
    del "%TEMP%\punto_venta.lock"
    echo Archivo de bloqueo eliminado
)

REM Matar procesos PHP que contengan la ruta de instalación
echo Cerrando procesos PHP...
wmic process where "name='php.exe' and CommandLine like '%%%InstallDir%%%'" call terminate >nul 2>&1

REM Matar procesos Electron que contengan la ruta de instalación
echo Cerrando procesos Electron...
wmic process where "name='electron.exe' and CommandLine like '%%%InstallDir%%%'" call terminate >nul 2>&1

echo.
echo ✓ Procesos cerrados correctamente
echo.
pause
