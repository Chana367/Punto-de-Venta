<?php
require_once "conexion.php";

echo "<h2>Verificación de Estructura de Base de Datos</h2>";

// Verificar que la tabla detalle_temp existe
try {
    $query = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='detalle_temp'");
    $tabla = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($tabla) {
        echo "<p style='color: green;'>✓ La tabla detalle_temp existe</p>";
        
        // Obtener estructura de la tabla
        $estructura = $conexion->query("PRAGMA table_info(detalle_temp)");
        echo "<h3>Estructura de detalle_temp:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'><th>ID</th><th>Nombre</th><th>Tipo</th><th>NotNull</th><th>Default</th><th>PK</th></tr>";
        while ($col = $estructura->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $col['cid'] . "</td>";
            echo "<td>" . $col['name'] . "</td>";
            echo "<td>" . $col['type'] . "</td>";
            echo "<td>" . ($col['notnull'] ? 'SI' : 'NO') . "</td>";
            echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
            echo "<td>" . ($col['pk'] ? 'SI' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Intentar insertar un registro de prueba
        echo "<h3>Prueba de inserción:</h3>";
        try {
            $test = $conexion->prepare("INSERT INTO detalle_temp (id_usuario, id_producto, cantidad, precio_venta, total) VALUES (999, 1, 1, 100.00, 100.00)");
            $resultado = $test->execute();
            if ($resultado) {
                echo "<p style='color: green;'>✓ Inserción de prueba exitosa</p>";
                // Eliminar el registro de prueba
                $conexion->exec("DELETE FROM detalle_temp WHERE id_usuario = 999");
                echo "<p style='color: blue;'>ℹ Registro de prueba eliminado</p>";
            } else {
                echo "<p style='color: red;'>✗ Error en inserción de prueba</p>";
                print_r($test->errorInfo());
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Excepción: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ La tabla detalle_temp NO existe</p>";
        echo "<p>Ejecuta inicializar_bd.php para crear la estructura</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Listar todas las tablas
echo "<h3>Tablas en la base de datos:</h3>";
$tablas = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "<ul>";
while ($t = $tablas->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>" . $t['name'] . "</li>";
}
echo "</ul>";
?>
