<?php
/**
 * Script para agregar el permiso 'configuracion' a la base de datos
 */

require_once "conexion.php";

try {
    // Verificar si ya existe el permiso
    $stmt = $conexion->prepare("SELECT id FROM permisos WHERE nombre = 'configuracion'");
    $stmt->execute();
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existe) {
        echo "✓ El permiso 'configuracion' ya existe (ID: " . $existe['id'] . ")\n";
    } else {
        // Insertar el permiso
        $stmt = $conexion->prepare("INSERT INTO permisos (nombre) VALUES ('configuracion')");
        $stmt->execute();
        
        $id_permiso = $conexion->lastInsertId();
        echo "✓ Permiso 'configuracion' creado exitosamente (ID: $id_permiso)\n";
    }
    
    echo "\n";
    echo "Ahora puedes asignar este permiso a los usuarios desde la página de Roles.\n";
    echo "El permiso permite acceder a la configuración del establecimiento.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
