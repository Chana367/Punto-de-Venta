<?php
/**
 * Script para agregar la columna 'embalaje' a la tabla producto
 */

require_once "conexion.php";

try {
    // Verificar si la columna ya existe
    $stmt = $conexion->query("PRAGMA table_info(producto)");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existe_embalaje = false;
    foreach ($columnas as $columna) {
        if ($columna['name'] === 'embalaje') {
            $existe_embalaje = true;
            break;
        }
    }
    
    if ($existe_embalaje) {
        echo "✓ La columna 'embalaje' ya existe en la tabla producto.\n";
    } else {
        // Agregar la columna
        $conexion->exec("ALTER TABLE producto ADD COLUMN embalaje TEXT DEFAULT ''");
        echo "✓ Columna 'embalaje' agregada exitosamente a la tabla producto.\n";
    }
    
    echo "\nAhora puedes usar el sistema sin problemas.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
