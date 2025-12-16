@echo off
echo Renombrando imagenes jpeg a jpg...

set "imgDir=%APPDATA%\PuntoVenta\imagenes"

if exist "%imgDir%\logo.jpeg" (
    move /Y "%imgDir%\logo.jpeg" "%imgDir%\logo.jpg"
    echo Logo renombrado de .jpeg a .jpg
)

if exist "%imgDir%\background.jpeg" (
    move /Y "%imgDir%\background.jpeg" "%imgDir%\background.jpg"
    echo Background renombrado de .jpeg a .jpg
)

echo.
echo Listo! Ahora actualiza la base de datos con el script PHP...
pause
