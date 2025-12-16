<?php
require 'conexion.php';

// Cambiar extensiones .jpeg a .jpg en la BD
$stmt = $conexion->query("SELECT id, logo, background FROM configuracion LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$logo = $data['logo'];
$background = $data['background'];
$cambios = false;

if (str_ends_with($logo, '.jpeg')) {
    $logo = str_replace('.jpeg', '.jpg', $logo);
    $cambios = true;
    echo "Logo actualizado: $logo\n";
}

if (str_ends_with($background, '.jpeg')) {
    $background = str_replace('.jpeg', '.jpg', $background);
    $cambios = true;
    echo "Background actualizado: $background\n";
}

if ($cambios) {
    $stmt = $conexion->prepare("UPDATE configuracion SET logo = :logo, background = :background WHERE id = :id");
    $stmt->execute([
        ':logo' => $logo,
        ':background' => $background,
        ':id' => $data['id']
    ]);
    echo "\n✓ Base de datos actualizada\n";
} else {
    echo "No hay cambios necesarios\n";
}
?>
