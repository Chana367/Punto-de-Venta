<?php
session_start();
require_once "../conexion.php";
$id_user = $_SESSION['idUser'];
$permiso = "reportes";

// Verificar permisos (debe tener permiso de leer en reportes)
$sql = $conexion->prepare("SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = :id_user AND p.nombre = :permiso AND d.puede_leer = 1");
$sql->bindParam(':id_user', $id_user, PDO::PARAM_INT);
$sql->bindParam(':permiso', $permiso, PDO::PARAM_STR);
$sql->execute();
$existe = $sql->fetchAll(PDO::FETCH_ASSOC);

if (empty($existe) && $id_user != 1) {
    header('Location: permisos.php');
    exit();
}

include_once "includes/header.php";

// Obtener lista de usuarios para el filtro
$usuarios = $conexion->query("SELECT idusuario, nombre FROM usuario ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de productos para el filtro
$productos = $conexion->query("SELECT codproducto, descripcion FROM producto ORDER BY descripcion LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-lg">
    <div class="card-header bg-primary text-white">
        <h4><i class="fas fa-chart-line"></i> Reportes de Ventas</h4>
    </div>
    <div class="card-body">
        <!-- Formulario de Filtros -->
        <form id="formFiltros" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="fecha_inicio"><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="fecha_fin"><i class="fas fa-calendar-alt"></i> Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
                        <select class="form-control" id="usuario" name="usuario">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['idusuario']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="producto"><i class="fas fa-box"></i> Producto</label>
                        <select class="form-control" id="producto" name="producto">
                            <option value="">Todos los productos</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo $producto['codproducto']; ?>"><?php echo htmlspecialchars($producto['descripcion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="turno"><i class="fas fa-clock"></i> Turno</label>
                        <select class="form-control" id="turno" name="turno">
                            <option value="">Todos los turnos</option>
                            <option value="mañana">Mañana (06:00 - 11:59)</option>
                            <option value="tarde">Tarde (12:00 - 17:59)</option>
                            <option value="noche">Noche (18:00 - 05:59)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="metodo_pago"><i class="fas fa-credit-card"></i> Método de Pago</label>
                        <select class="form-control" id="metodo_pago" name="metodo_pago">
                            <option value="">Todos los métodos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-primary mr-2" onclick="generarReporte()">
                        <i class="fas fa-search"></i> Generar Reporte
                    </button>
                    <button type="button" class="btn btn-success mr-2" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="exportarPDF()">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                </div>
            </div>
        </form>

        <!-- Resumen de Totales -->
        <div class="row mb-4" id="resumenTotales" style="display: none;">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-shopping-cart"></i> Total Ventas</h5>
                        <h3 id="totalVentas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Total Facturado</h5>
                        <h3 id="totalFacturado">$0.00</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-box"></i> Productos Vendidos</h5>
                        <h3 id="cantidadProductos">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i> Promedio por Venta</h5>
                        <h3 id="promedioVenta">$0.00</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="tblReportes">
                <thead class="thead-dark">
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Usuario</th>
                        <!-- <th>Cliente</th> -->
                        <th>Productos</th>
                        <th>Método Pago</th>
                        <th>Total</th>
                        <th>Turno</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="bodyReportes">
                    <tr>
                        <td colspan="9" class="text-center">
                            <p class="text-muted mt-3">
                                <i class="fas fa-info-circle"></i> Seleccione los filtros y haga clic en "Generar Reporte"
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Funciones de formateo de números estilo argentino
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numero);
}

function formatearMoneda(numero) {
    return '$' + formatearNumero(numero);
}

function generarReporte() {
    const fecha_inicio = $('#fecha_inicio').val();
    const fecha_fin = $('#fecha_fin').val();
    const usuario = $('#usuario').val();
    const producto = $('#producto').val();
    const turno = $('#turno').val();
    const metodo_pago = $('#metodo_pago').val();

    $.ajax({
        url: 'ajax_reportes.php',
        type: 'POST',
        data: {
            generarReporte: true,
            fecha_inicio: fecha_inicio,
            fecha_fin: fecha_fin,
            usuario: usuario,
            producto: producto,
            turno: turno,
            metodo_pago: metodo_pago
        },
        success: function(response) {
            const data = JSON.parse(response);
            
            // Actualizar resumen con formato argentino
            $('#resumenTotales').show();
            $('#totalVentas').text(data.resumen.total_ventas);
            $('#totalFacturado').text(formatearMoneda(data.resumen.total_facturado));
            $('#cantidadProductos').text(data.resumen.cantidad_productos);
            $('#promedioVenta').text(formatearMoneda(data.resumen.promedio_venta));
            
            // Actualizar tabla
            let html = '';
            if (data.ventas.length > 0) {
                data.ventas.forEach(function(venta) {
                    html += '<tr>';
                    html += '<td>' + venta.id + '</td>';
                    html += '<td>' + venta.fecha + '</td>';
                    html += '<td>' + venta.hora + '</td>';
                    html += '<td>' + venta.usuario + '</td>';
                    // html += '<td>' + venta.cliente + '</td>';
                    html += '<td>' + venta.productos + '</td>';
                    html += '<td><span class="badge badge-info">' + venta.metodo_pago + '</span></td>';
                    html += '<td>' + formatearMoneda(venta.total) + '</td>';
                    html += '<td>' + venta.turno + '</td>';
                    html += '<td><a href="pdf/generar.php?cl=' + venta.id_cliente + '&v=' + venta.id + '" target="_blank" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i></a></td>';
                    html += '</tr>';
                });
            } else {
                html = '<tr><td colspan="9" class="text-center text-muted">No se encontraron resultados para los filtros seleccionados</td></tr>';
            }
            $('#bodyReportes').html(html);
        },
        error: function(error) {
            console.error(error);
            alert('Error al generar el reporte');
        }
    });
}

function exportarExcel() {
    const fecha_inicio = $('#fecha_inicio').val();
    const fecha_fin = $('#fecha_fin').val();
    const usuario = $('#usuario').val();
    const producto = $('#producto').val();
    const turno = $('#turno').val();
    const metodo_pago = $('#metodo_pago').val();
    
    window.location.href = 'exportar_reporte.php?tipo=excel&fecha_inicio=' + fecha_inicio + 
        '&fecha_fin=' + fecha_fin + '&usuario=' + usuario + '&producto=' + producto + 
        '&turno=' + turno + '&metodo_pago=' + metodo_pago;
}

function exportarPDF() {
    const fecha_inicio = $('#fecha_inicio').val();
    const fecha_fin = $('#fecha_fin').val();
    const usuario = $('#usuario').val();
    const producto = $('#producto').val();
    const turno = $('#turno').val();
    const metodo_pago = $('#metodo_pago').val();
    
    window.open('exportar_reporte.php?tipo=pdf&fecha_inicio=' + fecha_inicio + 
        '&fecha_fin=' + fecha_fin + '&usuario=' + usuario + '&producto=' + producto + 
        '&turno=' + turno + '&metodo_pago=' + metodo_pago, '_blank');
}
</script>

<?php include_once "includes/footer.php"; ?>
