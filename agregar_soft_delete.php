<?php
require_once "conexion.php";

echo "<h2>Agregar columna 'activo' para eliminación lógica de productos</h2>";

try {
    // Verificar si la columna ya existe
    $check = $conexion->query("PRAGMA table_info(producto)");
    $columns = $check->fetchAll(PDO::FETCH_ASSOC);
    
    $existe = false;
    foreach ($columns as $col) {
        if ($col['name'] == 'activo') {
            $existe = true;
            break;
        }
    }
    
    if ($existe) {
        echo "<p style='color: blue;'>La columna 'activo' ya existe en la tabla producto.</p>";
    } else {
        // Agregar la columna con valor por defecto 1 (activo)
        $conexion->exec("ALTER TABLE producto ADD COLUMN activo INTEGER DEFAULT 1");
        echo "<p style='color: green;'>✓ Columna 'activo' agregada exitosamente.</p>";
        
        // Actualizar todos los productos existentes como activos
        $conexion->exec("UPDATE producto SET activo = 1 WHERE activo IS NULL");
        echo "<p style='color: green;'>✓ Todos los productos existentes marcados como activos.</p>";
    }
    
    // Mostrar estructura actualizada
    echo "<h3>Estructura actualizada de la tabla producto:</h3>";
    $estructura = $conexion->query("PRAGMA table_info(producto)");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'><th>ID</th><th>Nombre</th><th>Tipo</th><th>NotNull</th><th>Default</th></tr>";
    while ($col = $estructura->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $col['cid'] . "</td>";
        echo "<td><strong>" . $col['name'] . "</strong></td>";
        echo "<td>" . $col['type'] . "</td>";
        echo "<td>" . ($col['notnull'] ? 'SI' : 'NO') . "</td>";
        echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p style='color: #666; font-style: italic;'>Ahora los productos se marcarán como inactivos en lugar de eliminarse. Los reportes históricos se preservarán.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
