<?php
session_start();
require_once "../conexion.php";
$id_user = $_SESSION['idUser'];
$permiso = "productos";

// Consulta para verificar permisos del usuario
$sql = $conexion->prepare("SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = :id_user AND p.nombre = :permiso");
$sql->bindParam(':id_user', $id_user, PDO::PARAM_INT);
$sql->bindParam(':permiso', $permiso, PDO::PARAM_STR);
$sql->execute();
$existe = $sql->fetchAll(PDO::FETCH_ASSOC);

if (empty($existe) && $id_user != 1) {
    header("Location: permisos.php");
    exit();
}

if (!empty($_GET['id'])) {
    $id = $_GET['id'];

    // Reactivar producto (marcarlo como activo)
    $query_reactivar = $conexion->prepare("UPDATE producto SET activo = 1 WHERE codproducto = :id");
    $query_reactivar->bindParam(':id', $id, PDO::PARAM_INT);
    $query_reactivar->execute();

    header("Location: productos.php?tab=inactivos&msg=reactivado");
}
?>
