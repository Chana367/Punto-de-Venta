<?php
session_start();
require_once "../conexion.php";
$id_user = $_SESSION['idUser'];
$permiso = "ventas";

// Consulta para verificar permisos del usuario (debe tener permiso de leer)
$sql = $conexion->prepare("SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = :id_user AND p.nombre = :permiso AND d.puede_leer = 1");
$sql->bindParam(':id_user', $id_user, PDO::PARAM_INT);
$sql->bindParam(':permiso', $permiso, PDO::PARAM_STR);
$sql->execute();
$existe = $sql->fetchAll(PDO::FETCH_ASSOC);

if (empty($existe) && $id_user != 1) {
    header('Location: permisos.php');
    exit();
}

// Consulta para obtener ventas con los datos de los clientes y usuarios (LEFT JOIN para mostrar todas las ventas)
$query = $conexion->query("SELECT v.*, c.idcliente, c.nombre as cliente_nombre, u.nombre as usuario_nombre FROM ventas v LEFT JOIN cliente c ON v.id_cliente = c.idcliente LEFT JOIN usuario u ON v.id_usuario = u.idusuario ORDER BY v.id DESC");
$ventas = $query->fetchAll(PDO::FETCH_ASSOC);
include_once "includes/header.php";
?>
<div class="card">
    <div class="card-header">
        Historial de ventas
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-light" id="tbl">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Ver Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($ventas)) {
                        foreach ($ventas as $row) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['cliente_nombre'] ?? '<span class="text-muted">Cliente eliminado</span>'; ?></td>
                            <td><?php echo $row['total']; ?></td>
                            <td><span class="badge badge-info"><?php echo $row['usuario_nombre'] ?? 'Desconocido'; ?></span></td>
                            <td><?php echo $row['fecha']; ?></td>
                            <td>
                                <a href="pdf/generar.php?cl=<?php echo $row['id_cliente'] ?? 0 ?>&v=<?php echo $row['id'] ?>" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i></a>
                            </td>
                        </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once "includes/footer.php"; ?>
