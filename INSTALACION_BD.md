# Script de Inicialización de Base de Datos

Este documento explica cómo funciona el sistema de inicialización automática de la base de datos.

## 🚀 Funcionamiento Automático

Cuando instalas el programa por primera vez, **automáticamente**:

1. Detecta que no existe `sistema.db`
2. Ejecuta `inicializar_bd.php`
3. Crea todas las tablas necesarias
4. Inserta datos por defecto:
   - **Usuario Admin**: Francisco (usuario: `admin`, contraseña: `ChanaPass`)
   - **Permisos**: Todos los módulos del sistema
   - **Configuración**: Datos de ejemplo del negocio
   - **Cliente General**: Para ventas sin cliente específico

## 📝 Credenciales Iniciales

```
Usuario: admin
Contraseña: ChanaPass
Email: paredesfrancisco031@gmail.com
```

## ⚙️ Configuración Inicial

Después del primer inicio de sesión:

1. Ve a **Configuración** (icono de engranaje)
2. Actualiza los datos del negocio:
   - Nombre del negocio
   - Teléfono
   - Email
   - Dirección
   - Logo
   - Mensaje del recibo

## 🔄 Reiniciar Base de Datos

Si necesitas reiniciar la base de datos manualmente:

### Opción 1: Script BAT (Windows)
```
Ejecutar: reiniciar_bd.bat
```

### Opción 2: PHP directo
```bash
php\php.exe inicializar_bd.php
```

### Opción 3: Eliminar archivo
1. Cierra el programa
2. Elimina el archivo `sistema.db`
3. Inicia el programa nuevamente

**NOTA**: Al reiniciar se crea un backup automático con fecha y hora.

## 📁 Estructura de la Base de Datos

### Tablas creadas:
- `usuario` - Usuarios del sistema
- `cliente` - Clientes del negocio
- `producto` - Inventario de productos
- `ventas` - Registro de ventas
- `detalle_venta` - Detalle de productos por venta
- `detalle_temp` - Carrito temporal de ventas
- `permisos` - Módulos del sistema
- `detalle_permisos` - Permisos asignados a usuarios
- `configuracion` - Datos del negocio

## 🔒 Usuario Admin

El usuario **admin** (ID: 1) está protegido:
- No aparece en la lista de usuarios
- No puede ser editado por otros usuarios
- No puede ser eliminado
- Tiene acceso completo al sistema

## ⚠️ Importante

- **Cambia la contraseña** después del primer inicio de sesión
- **Configura los datos del negocio** antes de usarlo
- Los backups se guardan automáticamente con el formato: `sistema_backup_YYYY-MM-DD_HHMMSS.db`
- El usuario admin siempre tendrá acceso, incluso sin permisos explícitos

## 🛠️ Para Desarrolladores

El script `inicializar_bd.php` puede modificarse para:
- Agregar más datos de ejemplo
- Cambiar credenciales por defecto
- Personalizar la estructura de tablas
- Agregar más permisos

## 📞 Soporte

Si tienes problemas con la inicialización:
1. Verifica que existe la carpeta `php/`
2. Verifica permisos de escritura en la carpeta
3. Revisa los logs de la consola
4. Ejecuta manualmente `reiniciar_bd.bat`
