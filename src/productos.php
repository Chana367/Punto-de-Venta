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
    header('Location: permisos.php');
    exit();
}

if (!empty($_POST)) {
    $alert = "";
    $id = $_POST['id'];
    $codigo = $_POST['codigo'];
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $stock_minimo = isset($_POST['stock_minimo']) ? $_POST['stock_minimo'] : 5;

    if (empty($codigo) || empty($producto) || empty($precio) || $precio < 0 || empty($cantidad) || $cantidad < 0 || empty($stock_minimo) || $stock_minimo < 0) {
        $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Atención:</strong> Todos los campos son obligatorios. El precio, cantidad y stock mínimo deben ser mayores o iguales a cero.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
    } else {
        if (empty($id)) {
            $query = $conexion->prepare("SELECT * FROM producto WHERE codigo = :codigo");
            $query->bindParam(':codigo', $codigo, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> El código ya existe en el sistema.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
            } else {
                $query_insert = $conexion->prepare("INSERT INTO producto (codigo, descripcion, precio, cantidad, stock_minimo, activo) VALUES (:codigo, :producto, :precio, :cantidad, :stock_minimo, 1)");
                $query_insert->bindParam(':codigo', $codigo, PDO::PARAM_STR);
                $query_insert->bindParam(':producto', $producto, PDO::PARAM_STR);
                $query_insert->bindParam(':precio', $precio, PDO::PARAM_STR);
                $query_insert->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                $query_insert->bindParam(':stock_minimo', $stock_minimo, PDO::PARAM_INT);
                $query_insert->execute();

                if ($query_insert) {
                    $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Producto registrado
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
                } else {
                    $alert = '<div class="alert alert-danger" role="alert">
                    Error al registrar el producto
                  </div>';
                }
            }
        } else {
            $query_update = $conexion->prepare("UPDATE producto SET codigo = :codigo, descripcion = :producto, precio = :precio, cantidad = :cantidad, stock_minimo = :stock_minimo WHERE codproducto = :id");
            $query_update->bindParam(':codigo', $codigo, PDO::PARAM_STR);
            $query_update->bindParam(':producto', $producto, PDO::PARAM_STR);
            $query_update->bindParam(':precio', $precio, PDO::PARAM_STR);
            $query_update->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $query_update->bindParam(':stock_minimo', $stock_minimo, PDO::PARAM_INT);
            $query_update->bindParam(':id', $id, PDO::PARAM_INT);
            $query_update->execute();

            if ($query_update) {
                $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Producto modificado
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
            } else {
                $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        Error al modificar
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
            }
        }
    }
}
include_once "includes/header.php";

?>

<div class="card shadow-lg">
    <div class="card-body">
        <?php if (!puedeAccion('productos', 'crear') && !puedeAccion('productos', 'actualizar') && puedeAccion('productos', 'leer')): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>Modo Solo Lectura:</strong> Solo puedes ver los productos. No tienes permisos para crear o modificar.
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <?php if (puedeAccion('productos', 'crear') || puedeAccion('productos', 'actualizar')): ?>
                <form action="" method="post" autocomplete="off" id="formulario">
                    <?php echo isset($alert) ? $alert : ''; ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="codigo" class="text-dark font-weight-bold"><i class="fas fa-barcode"></i> Código de Barras</label>
                                <input type="text" placeholder="Ingrese código de barras" name="codigo" id="codigo" class="form-control">
                                <input type="hidden" id="id" name="id">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="producto" class="text-dark font-weight-bold">Producto</label>
                                <input type="text" placeholder="Ingrese nombre del producto" name="producto" id="producto" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="precio" class="text-dark font-weight-bold">Precio</label>
                                <input type="text" placeholder="Ingrese precio" class="form-control" name="precio" id="precio">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="cantidad" class="text-dark font-weight-bold">Cantidad</label>
                                <input type="number" placeholder="Ingrese cantidad" class="form-control" name="cantidad" id="cantidad">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="stock_minimo" class="text-dark font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Stock Mínimo</label>
                                <input type="number" placeholder="Stock mínimo" class="form-control" name="stock_minimo" id="stock_minimo" value="5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if (puedeAccion('productos', 'crear') || puedeAccion('productos', 'actualizar')): ?>
                                <input type="submit" value="Registrar" class="btn btn-primary" id="btnAccion">
                                <input type="button" value="Limpiar Formulario" onclick="limpiar()" class="btn btn-secondary" id="btnNuevo" title="Limpiar campos del formulario">
                            <?php endif; ?>
                            
                            <?php if (puedeAccion('productos', 'crear')): ?>
                                <button type="button" class="btn btn-primary" id="importar-excel" data-toggle="modal" data-target="#excelModal"><i class="fas fa-file-excel"></i> &nbsp; Importar desde Excel</button>
                            <?php endif; ?>
                            
                            <?php if (puedeAccion('productos', 'leer')): ?>
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#lowStockModal">
                                    <i class="fas fa-exclamation-triangle"></i> Productos con Bajo Stock
                                </button>
                                <a href="imprimir_codigos.php" class="btn btn-info" target="_blank">
                                    <i class="fas fa-print"></i> Imprimir Códigos de Barra
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pestañas para productos activos/inactivos -->
        <div class="col-md-12 mt-3 mb-3">
            <div class="btn-group" role="group">
                <a href="?tab=activos" class="btn <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'activos' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-check-circle"></i> PRODUCTOS ACTIVOS
                </a>
                <a href="?tab=inactivos" class="btn <?php echo isset($_GET['tab']) && $_GET['tab'] == 'inactivos' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-ban"></i> PRODUCTOS DESHABILITADOS
                </a>
            </div>
        </div>

        <div class="col-md-12">
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'reactivado'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    <i class="fas fa-check-circle"></i> Producto reactivado correctamente.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="tbl">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Stock Mínimo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include "../conexion.php";

                        // Determinar qué productos mostrar según la pestaña activa
                        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'activos';
                        $activo_value = ($tab == 'inactivos') ? 0 : 1;
                        
                        // Consulta para obtener productos según el estado activo
                        $query = $conexion->query("SELECT * FROM producto WHERE activo = $activo_value");
                        $result = $query->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($result)) {
                            foreach ($result as $data) { 
                                // Determinar el color según el stock
                                $stock_class = '';
                                $stock_minimo = $data['stock_minimo'] ?? 5;
                                if ($data['cantidad'] <= $stock_minimo) {
                                    $stock_class = 'text-danger font-weight-bold';
                                } elseif ($data['cantidad'] <= ($stock_minimo * 2)) {
                                    $stock_class = 'text-warning font-weight-bold';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $data['codproducto']; ?></td>
                                    <td><?php echo $data['codigo']; ?></td>
                                    <td><?php echo $data['descripcion']; ?></td>
                                    <td><?php echo $data['precio']; ?></td>
                                    <td class="<?php echo $stock_class; ?>"><?php echo $data['cantidad']; ?></td>
                                    <td><?php echo $stock_minimo; ?></td>
                                    <td>
                                        <?php if ($tab == 'activos'): ?>
                                            <!-- Botones para productos activos -->
                                            <?php if (puedeAccion('productos', 'actualizar')): ?>
                                                <a href="#" onclick="editarProducto(<?php echo $data['codproducto']; ?>)" class="btn btn-primary" title="Editar"><i class='fas fa-edit'></i></a>
                                            <?php endif; ?>
                                            
                                            <?php if (puedeAccion('productos', 'eliminar')): ?>
                                                <form action="eliminar_producto.php?id=<?php echo $data['codproducto']; ?>" method="post" class="confirmar d-inline">
                                                    <button class="btn btn-warning" type="submit" title="Deshabilitar producto"><i class='fas fa-ban'></i></button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if (!puedeAccion('productos', 'actualizar') && !puedeAccion('productos', 'eliminar') && puedeAccion('productos', 'leer')): ?>
                                                <span class="badge badge-info">Solo lectura</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Botones para productos deshabilitados -->
                                            <?php if (puedeAccion('productos', 'actualizar') || puedeAccion('productos', 'eliminar')): ?>
                                                <a href="reactivar_producto.php?id=<?php echo $data['codproducto']; ?>" 
                                                   class="btn btn-success" 
                                                   title="Reactivar producto"
                                                   onclick="return confirm('¿Está seguro de reactivar este producto?');">
                                                    <i class='fas fa-check-circle'></i> Reactivar
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!puedeAccion('productos', 'actualizar') && !puedeAccion('productos', 'eliminar') && puedeAccion('productos', 'leer')): ?>
                                                <span class="badge badge-secondary">Deshabilitado</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cargar archivo -->
<div class="modal fade" id="excelModal" tabindex="-1" role="dialog" aria-labelledby="excelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excelModalLabel">Importar Productos desde Excel</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importForm" method="POST" enctype="multipart/form-data">
                    <input type="file" id="excelFile" name="archivo" accept=".csv, .xlsx, .xls" required>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="uploadExcel">Subir Archivo</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para productos con bajo stock -->
<div class="modal fade" id="lowStockModal" tabindex="-1" role="dialog" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lowStockModalLabel">Productos con Bajo Stock</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody id="lowStockTableBody">
                            <!-- Aquí se cargarán los datos -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="statusMessage" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    $('#lowStockModal').on('show.bs.modal', function () {
        const tableBody = document.getElementById('lowStockTableBody');
        tableBody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';
        
        fetch('./low_stock.php')
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No hay productos con bajo stock.</td></tr>';
                } else {
                    data.forEach((producto, index) => {
                        tableBody.innerHTML += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${producto.codigo}</td>
                                <td>${producto.descripcion}</td>
                                <td>${producto.precio}</td>
                                <td>${producto.cantidad}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-danger">Error: ${error.message}</td></tr>`;
            });
    });
});
</script>

<script>
document.getElementById("uploadExcel").addEventListener("click", function () {
    const fileInput = document.getElementById("excelFile");
    const formData = new FormData();

    if (fileInput.files.length === 0) { 
        Swal.fire({ 
            position: 'center', 
            icon: 'error', 
            title: 'Por favor, selecciona un archivo para subir.', 
            showConfirmButton: false, timer: 2000 }); 
            return; }

    formData.append("archivo", fileInput.files[0]);

    fetch("./uploadProducts.php", {
        method: "POST",
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) { 
            Swal.fire({
                 position: 'center',
                 icon: 'success', 
                 title: data.message, 
                 showConfirmButton: false, 
                 timer: 2000 }); 
                 setTimeout(() => { location.reload(); }, 2000);
                } else { 
                    Swal.fire({
                         position: 'center', 
                         icon: 'error', 
                         title: `Error: ${data.error}`, 
                         showConfirmButton: false, 
                         timer: 2000 }); }
        $('#excelModal').modal('hide'); // Cerrar el modal
    })
    .catch(error => {
        document.getElementById("statusMessage").innerText = `Error: ${error.message}`;
    });
});
</script>


<?php include_once "includes/footer.php"; ?>