@echo off
echo ========================================
echo   INICIALIZADOR DE BASE DE DATOS
echo   Punto de Venta
echo ========================================
echo.
echo ADVERTENCIA: Este script reiniciara la base de datos.
echo Se creara un backup de la base de datos actual.
echo.
pause

php\php.exe inicializar_bd.php

echo.
pause
