<?php
// Script para actualizar todos los productos a activo = 1
require_once "conexion.php";

echo "<h2>Actualizar productos a activo = 1</h2>";

try {
    // Verificar cuántos productos hay en total
    $total = $conexion->query("SELECT COUNT(*) as total FROM producto")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de productos en la base de datos: <strong>" . $total['total'] . "</strong></p>";
    
    // Verificar cuántos tienen activo NULL o 0
    $inactivos = $conexion->query("SELECT COUNT(*) as total FROM producto WHERE activo IS NULL OR activo = 0")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Productos con activo NULL o 0: <strong>" . $inactivos['total'] . "</strong></p>";
    
    // Actualizar todos los productos a activo = 1
    $update = $conexion->prepare("UPDATE producto SET activo = 1 WHERE activo IS NULL OR activo = 0");
    $update->execute();
    $actualizados = $update->rowCount();
    
    echo "<p style='color: green; font-weight: bold;'>✓ Se actualizaron $actualizados productos a activo = 1</p>";
    
    // Mostrar algunos productos como ejemplo
    echo "<h3>Primeros 10 productos:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Código</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Activo</th></tr>";
    
    $productos = $conexion->query("SELECT * FROM producto LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos as $p) {
        echo "<tr>";
        echo "<td>" . $p['codproducto'] . "</td>";
        echo "<td>" . $p['codigo'] . "</td>";
        echo "<td>" . $p['descripcion'] . "</td>";
        echo "<td>" . $p['precio'] . "</td>";
        echo "<td>" . $p['cantidad'] . "</td>";
        echo "<td style='color: " . ($p['activo'] == 1 ? 'green' : 'red') . ";'><strong>" . $p['activo'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='margin-top: 20px;'><a href='src/productos.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir a Productos</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
