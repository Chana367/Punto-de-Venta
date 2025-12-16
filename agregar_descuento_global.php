<?php
require_once "conexion.php";

echo "<h2>Agregar columna descuento_global a tabla ventas</h2>";

try {
    // Verificar si la columna ya existe
    $check = $conexion->query("PRAGMA table_info(ventas)");
    $columns = $check->fetchAll(PDO::FETCH_ASSOC);
    
    $existe = false;
    foreach ($columns as $col) {
        if ($col['name'] == 'descuento_global') {
            $existe = true;
            break;
        }
    }
    
    if ($existe) {
        echo "<p style='color: blue;'>La columna 'descuento_global' ya existe en la tabla ventas.</p>";
    } else {
        // Agregar la columna
        $conexion->exec("ALTER TABLE ventas ADD COLUMN descuento_global REAL DEFAULT 0");
        echo "<p style='color: green;'>✓ Columna 'descuento_global' agregada exitosamente a la tabla ventas.</p>";
    }
    
    // Mostrar estructura actualizada
    echo "<h3>Estructura actualizada de la tabla ventas:</h3>";
    $estructura = $conexion->query("PRAGMA table_info(ventas)");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'><th>ID</th><th>Nombre</th><th>Tipo</th><th>NotNull</th><th>Default</th></tr>";
    while ($col = $estructura->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $col['cid'] . "</td>";
        echo "<td>" . $col['name'] . "</td>";
        echo "<td>" . $col['type'] . "</td>";
        echo "<td>" . ($col['notnull'] ? 'SI' : 'NO') . "</td>";
        echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
