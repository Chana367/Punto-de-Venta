<?php
/**
 * Script de inicialización de base de datos
 * Crea la estructura completa con datos por defecto
 */

// Recibir ruta de BD como argumento, o usar APPDATA por defecto
if (isset($argv[1]) && !empty($argv[1])) {
    $db_file = $argv[1];
} else {
    // Usar APPDATA si no se proporciona ruta
    $appDataDir = getenv('APPDATA') . '\\PuntoVenta';
    if (!is_dir($appDataDir)) {
        mkdir($appDataDir, 0777, true);
    }
    $db_file = $appDataDir . '\\sistema.db';
}

$backup_dir = dirname($db_file);

// Si existe la BD, hacer backup
if (file_exists($db_file)) {
    $backup = $backup_dir . '\\sistema_backup_' . date('Y-m-d_His') . '.db';
    copy($db_file, $backup);
    echo "✓ Backup creado: $backup\n";
    unlink($db_file);
}

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Base de datos creada\n";
    
    // Crear tablas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuario (
            idusuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            correo TEXT NOT NULL,
            usuario TEXT NOT NULL,
            clave TEXT NOT NULL
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cliente (
            idcliente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            telefono TEXT,
            direccion TEXT
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS producto (
            codproducto INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT NOT NULL,
            descripcion TEXT NOT NULL,
            embalaje TEXT DEFAULT '',
            precio REAL NOT NULL,
            cantidad INTEGER NOT NULL,
            stock_minimo INTEGER DEFAULT 0
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INTEGER NOT NULL,
            total REAL NOT NULL,
            id_usuario INTEGER NOT NULL,
            fecha TEXT DEFAULT CURRENT_TIMESTAMP,
            metodo_pago TEXT,
            monto_pagado REAL,
            vuelto REAL,
            turno TEXT,
            FOREIGN KEY (id_cliente) REFERENCES cliente(idcliente),
            FOREIGN KEY (id_usuario) REFERENCES usuario(idusuario)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS detalle_venta (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_producto INTEGER NOT NULL,
            id_venta INTEGER NOT NULL,
            cantidad INTEGER NOT NULL,
            precio REAL NOT NULL,
            total REAL NOT NULL,
            descuento REAL DEFAULT 0,
            FOREIGN KEY (id_producto) REFERENCES producto(codproducto),
            FOREIGN KEY (id_venta) REFERENCES ventas(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS detalle_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_producto INTEGER NOT NULL,
            id_usuario INTEGER NOT NULL,
            cantidad INTEGER NOT NULL,
            precio_venta REAL NOT NULL,
            total REAL NOT NULL,
            FOREIGN KEY (id_producto) REFERENCES producto(codproducto),
            FOREIGN KEY (id_usuario) REFERENCES usuario(idusuario)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permisos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL UNIQUE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS detalle_permisos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER NOT NULL,
            id_permiso INTEGER NOT NULL,
            puede_crear INTEGER DEFAULT 0,
            puede_leer INTEGER DEFAULT 0,
            puede_actualizar INTEGER DEFAULT 0,
            puede_eliminar INTEGER DEFAULT 0,
            FOREIGN KEY (id_usuario) REFERENCES usuario(idusuario),
            FOREIGN KEY (id_permiso) REFERENCES permisos(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            telefono TEXT,
            email TEXT,
            direccion TEXT,
            mensaje TEXT,
            logo TEXT DEFAULT 'logo.jpg',
            background TEXT DEFAULT 'sidebar-1.jpg'
        )
    ");
    
    echo "✓ Tablas creadas\n";
    
    // Insertar usuario admin por defecto
    $clave_hash = md5('ChanaPass');
    $pdo->exec("
        INSERT INTO usuario (nombre, correo, usuario, clave) 
        VALUES ('Francisco', 'paredesfrancisco031@gmail.com', 'admin', '$clave_hash')
    ");
    echo "✓ Usuario admin creado (usuario: admin, contraseña: ChanaPass)\n";
    
    // Insertar permisos por defecto
    $permisos = ['usuarios', 'clientes', 'productos', 'ventas', 'nueva_venta', 'reportes', 'configuracion'];
    foreach ($permisos as $permiso) {
        $pdo->exec("INSERT INTO permisos (nombre) VALUES ('$permiso')");
    }
    echo "✓ Permisos creados\n";
    
    // Insertar configuración por defecto
    $pdo->exec("
        INSERT INTO configuracion (nombre, telefono, email, direccion, mensaje, logo, background) 
        VALUES (
            'Mi Negocio',
            '000-0000000',
            'contacto@minegocio.com',
            'Dirección de ejemplo',
            'Gracias por su compra',
            'logo.jpg',
            'sidebar-1.jpg'
        )
    ");
    echo "✓ Configuración por defecto creada\n";
    
    // Insertar cliente por defecto (Cliente General)
    $pdo->exec("
        INSERT INTO cliente (nombre, telefono, direccion) 
        VALUES ('Cliente General', 'N/A', 'N/A')
    ");
    echo "✓ Cliente General creado\n";
    
    echo "\n";
    echo "========================================\n";
    echo "✓ Base de datos inicializada correctamente\n";
    echo "========================================\n";
    echo "\n";
    echo "Credenciales de acceso:\n";
    echo "  Usuario: admin\n";
    echo "  Contraseña: ChanaPass\n";
    echo "\n";
    echo "IMPORTANTE: Cambie estos datos después del primer inicio de sesión.\n";
    echo "Configure los datos del negocio en: Configuración > Datos del negocio\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
