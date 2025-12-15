<?php
require_once "conexion.php";

try {
    // Verificar si ya existe el permiso reportes
    $check = $conexion->query("SELECT id FROM permisos WHERE nombre = 'reportes'");
    if ($check->fetch()) {
        echo "El permiso 'reportes' ya existe.\n";
    } else {
        // Crear el permiso reportes
        $conexion->exec("INSERT INTO permisos (nombre) VALUES ('reportes')");
        echo "Permiso 'reportes' creado exitosamente con ID: " . $conexion->lastInsertId() . "\n";
    }
    
    // Mostrar todos los permisos
    echo "\nPermisos disponibles:\n";
    $permisos = $conexion->query("SELECT * FROM permisos");
    foreach ($permisos as $p) {
        echo "  ID: " . $p['id'] . " - " . $p['nombre'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
