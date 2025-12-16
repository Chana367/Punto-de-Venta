# 🏪 Sistema de Punto de Venta

Sistema completo de gestión de ventas con control de inventario, clientes y reportes.

## 📦 Instalación

### Primera Instalación

1. **Descargar/Clonar** el proyecto
2. **Instalar dependencias**:
   ```bash
   npm install
   ```
3. **Iniciar la aplicación**:
   ```bash
   npm start
   ```
   O ejecutar: `iniciar_app.bat`

4. La base de datos se creará **automáticamente** en el primer inicio

### Credenciales Iniciales

```
Usuario: admin
Contraseña: ChanaPass
```

**⚠️ IMPORTANTE**: Cambia la contraseña después del primer inicio de sesión.

## ⚙️ Configuración Inicial

Después del primer inicio de sesión, configura los datos del negocio:

1. Ir a **Configuración** (icono de engranaje en el menú)
2. Actualizar:
   - Nombre del negocio
   - Teléfono
   - Email
   - Dirección
   - Logo del negocio
   - Mensaje en los recibos

## 🔄 Reiniciar Base de Datos

Si necesitas reiniciar completamente el sistema:

### Windows:
```bash
reiniciar_bd.bat
```

### Manual:
```bash
php\php.exe inicializar_bd.php
```

**NOTA**: Se crea un backup automático antes de reiniciar.

## 📋 Características

### ✅ Gestión de Usuarios
- Crear usuarios con diferentes permisos
- Control granular de accesos (Crear, Leer, Actualizar, Eliminar)
- Usuario admin protegido

### 👥 Gestión de Clientes
- Registrar clientes con datos de contacto
- Historial de compras por cliente

### 📦 Gestión de Productos
- Control de inventario
- Stock mínimo con alertas
- Código de barras

### 💰 Ventas
- Sistema de punto de venta rápido
- Múltiples métodos de pago (Efectivo, Tarjeta, Transferencia)
- Cálculo automático de vuelto
- Sistema de turnos (Mañana, Tarde, Noche)
- Registro automático del usuario que realiza la venta

### 📊 Reportes
- Filtrado por fechas, usuario, producto, turno y método de pago
- Exportación a Excel
- Exportación a PDF
- Estadísticas de ventas

### 🔐 Sistema de Permisos
- **2**: Configuración del sistema
- **usuarios**: Gestión de usuarios
- **clientes**: Gestión de clientes
- **productos**: Gestión de productos
- **ventas**: Ver historial de ventas
- **nueva_venta**: Realizar ventas
- **reportes**: Generar y exportar reportes

## 🗂️ Estructura de Archivos

```
Punto-de-Venta/
├── src/                  # Archivos PHP del sistema
│   ├── ajax.php         # Procesos AJAX
│   ├── ventas.php       # Módulo de ventas
│   ├── reportes.php     # Módulo de reportes
│   └── ...
├── assets/              # Recursos estáticos
│   ├── css/            # Estilos
│   ├── js/             # Scripts JavaScript
│   └── img/            # Imágenes
├── php/                 # PHP portable
├── node_modules/        # Dependencias Node.js
├── main.js             # Aplicación Electron
├── inicializar_bd.php  # Script de inicialización
├── sistema.db          # Base de datos SQLite (auto-generada)
└── package.json        # Configuración del proyecto
```

## 🛠️ Tecnologías

- **Backend**: PHP 8.x con SQLite
- **Frontend**: HTML5, CSS3, Bootstrap 4, JavaScript/jQuery
- **Desktop**: Electron
- **Base de Datos**: SQLite
- **Reportes**: FPDF para PDF, HTML para Excel

## 📝 Notas Importantes

### Usuario Admin
- ID: 1
- No aparece en la lista de usuarios
- No puede ser editado ni eliminado por otros usuarios
- Tiene acceso completo al sistema

### Base de Datos
- Se crea automáticamente en el primer inicio
- Los backups se guardan como: `sistema_backup_YYYY-MM-DD_HHMMSS.db`
- No se incluye en el repositorio (ver `.gitignore`)

### Permisos
- El usuario admin siempre tiene acceso completo
- Los demás usuarios necesitan permisos específicos
- Los permisos se gestionan en: **Usuarios > Botón de llave**

## 🐛 Solución de Problemas

### La base de datos no se crea
1. Verificar que existe la carpeta `php/`
2. Verificar permisos de escritura
3. Ejecutar manualmente: `php\php.exe inicializar_bd.php`

### No puedo iniciar sesión
- Usuario: `admin`
- Contraseña: `ChanaPass`
- Si olvidaste la contraseña, reinicia la base de datos

### Error al generar PDF
- Verificar que existe la carpeta `src/pdf/fpdf/`
- Verificar permisos de escritura

## 📞 Soporte

Para reportar problemas o sugerencias:
- Email: paredesfrancisco031@gmail.com

## 📄 Licencia

ISC

---

**Desarrollado por Francisco Paredes** 🚀
