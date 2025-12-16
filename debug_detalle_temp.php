<?php
session_start();
require_once "conexion.php";

echo "<h2>Debug: Detalle Temp</h2>";
echo "<p><strong>Usuario en sesión:</strong> " . (isset($_SESSION['idUser']) ? $_SESSION['idUser'] : 'NO HAY SESIÓN') . "</p>";
echo "<p><strong>Nombre usuario:</strong> " . (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'N/A') . "</p>";

// Mostrar toda la sesión
echo "<h3>Contenido completo de la sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$query = $conexion->query("SELECT * FROM detalle_temp");
$productos = $query->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Productos en detalle_temp:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr style='background: #333; color: white;'><th>ID</th><th>ID Usuario</th><th>ID Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr>";

if (count($productos) > 0) {
    foreach ($productos as $prod) {
        echo "<tr>";
        echo "<td>" . $prod['id'] . "</td>";
        echo "<td>" . $prod['id_usuario'] . "</td>";
        echo "<td>" . $prod['id_producto'] . "</td>";
        echo "<td>" . $prod['cantidad'] . "</td>";
        echo "<td>$" . number_format($prod['precio_venta'], 2) . "</td>";
        echo "<td>$" . number_format($prod['total'], 2) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align: center; color: red; padding: 10px;'>¡No hay productos en detalle_temp!</td></tr>";
}

echo "</table>";

// Verificar todos los usuarios
echo "<h3>Usuarios en el sistema:</h3>";
$usuarios = $conexion->query("SELECT idusuario, nombre, usuario FROM usuario")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr style='background: #333; color: white;'><th>ID</th><th>Nombre</th><th>Usuario</th></tr>";
foreach ($usuarios as $u) {
    echo "<tr>";
    echo "<td>" . $u['idusuario'] . "</td>";
    echo "<td>" . $u['nombre'] . "</td>";
    echo "<td>" . $u['usuario'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

