<?php
require 'conexion.php';
$stmt = $conexion->query('SELECT logo, background FROM configuracion');
$data = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Logo en BD: " . $data['logo'] . "\n";
echo "Background en BD: " . $data['background'] . "\n";
?>
