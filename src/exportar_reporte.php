<?php
session_start();
require_once "../conexion.php";

// Verificar permisos de reportes
$id_user = $_SESSION['idUser'];
$permiso = "reportes";

$sql = $conexion->prepare("SELECT p.*, d.* FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = :id_user AND p.nombre = :permiso AND d.puede_leer = 1");
$sql->bindParam(':id_user', $id_user, PDO::PARAM_INT);
$sql->bindParam(':permiso', $permiso, PDO::PARAM_STR);
$sql->execute();
$existe = $sql->fetchAll(PDO::FETCH_ASSOC);

if (empty($existe) && $id_user != 1) {
    die('No tienes permisos para generar reportes');
}

// Función para formatear números al estilo argentino
function formatearNumeroAR($numero) {
    return number_format($numero, 2, ',', '.');
}

function formatearMonedaAR($numero) {
    return '$' . formatearNumeroAR($numero);
}

// Obtener parámetros
$tipo = $_GET['tipo'] ?? 'excel';
$fecha_inicio = $_GET['fecha_inicio'] . ' 00:00:00';
$fecha_fin = $_GET['fecha_fin'] . ' 23:59:59';
$usuario = $_GET['usuario'] ?? '';
$producto = $_GET['producto'] ?? '';
$turno = $_GET['turno'] ?? '';
$metodo_pago = $_GET['metodo_pago'] ?? '';

// Construir la consulta dinámica
$sql = "SELECT v.id, v.fecha, v.total, v.id_cliente, v.metodo_pago, v.turno, 
        u.nombre as usuario_nombre, c.nombre as cliente_nombre,
        GROUP_CONCAT(p.descripcion || ' (x' || dv.cantidad || ')' , ', ') as productos
        FROM ventas v
        LEFT JOIN usuario u ON v.id_usuario = u.idusuario
        LEFT JOIN cliente c ON v.id_cliente = c.idcliente
        LEFT JOIN detalle_venta dv ON v.id = dv.id_venta
        LEFT JOIN producto p ON dv.id_producto = p.codproducto
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin";

// Agregar filtros opcionales
if (!empty($usuario)) {
    $sql .= " AND v.id_usuario = :usuario";
}
if (!empty($producto)) {
    $sql .= " AND dv.id_producto = :producto";
}
if (!empty($turno)) {
    $sql .= " AND v.turno = :turno";
}
if (!empty($metodo_pago)) {
    $sql .= " AND v.metodo_pago = :metodo_pago";
}

$sql .= " GROUP BY v.id ORDER BY v.fecha DESC";

$query = $conexion->prepare($sql);
$query->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
$query->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);

if (!empty($usuario)) {
    $query->bindParam(':usuario', $usuario, PDO::PARAM_INT);
}
if (!empty($producto)) {
    $query->bindParam(':producto', $producto, PDO::PARAM_INT);
}
if (!empty($turno)) {
    $query->bindParam(':turno', $turno, PDO::PARAM_STR);
}
if (!empty($metodo_pago)) {
    $query->bindParam(':metodo_pago', $metodo_pago, PDO::PARAM_STR);
}

$query->execute();
$ventas = $query->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_ventas = count($ventas);
$total_facturado = 0;
$cantidad_productos = 0;

foreach ($ventas as $venta) {
    $total_facturado += $venta['total'];
    
    $countQuery = $conexion->prepare("SELECT SUM(cantidad) as total FROM detalle_venta WHERE id_venta = ?");
    $countQuery->execute([$venta['id']]);
    $countResult = $countQuery->fetch(PDO::FETCH_ASSOC);
    $cantidad_productos += $countResult['total'] ?: 0;
}

$promedio_venta = $total_ventas > 0 ? $total_facturado / $total_ventas : 0;

if ($tipo == 'excel') {
    // ============================================
    // EXPORTAR A EXCEL (HTML formato Excel)
    // ============================================
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_ventas_' . date('Y-m-d_His') . '.xls');
    
    $desde = $_GET['fecha_inicio'];
    $hasta = $_GET['fecha_fin'];
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    
    // Título
    echo '<table border="0" cellpadding="5" cellspacing="0">';
    echo '<tr><td colspan="8" style="background-color: #4CAF50; color: white; font-size: 18px; font-weight: bold; text-align: center;">REPORTE DE VENTAS</td></tr>';
    echo '<tr><td colspan="8" style="text-align: center; font-size: 12px;">Período: ' . $desde . ' al ' . $hasta . '</td></tr>';
    echo '<tr><td colspan="8">&nbsp;</td></tr>';
    echo '</table>';
    
    // Resumen con formato
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td colspan="2" style="background-color: #2196F3; color: white; font-weight: bold; text-align: center;">RESUMEN</td></tr>';
    echo '<tr><td style="background-color: #E3F2FD; font-weight: bold; width: 200px;">Total de Ventas:</td><td style="text-align: right;">' . $total_ventas . '</td></tr>';
    echo '<tr><td style="background-color: #E3F2FD; font-weight: bold;">Total Facturado:</td><td style="text-align: right; font-weight: bold; color: #1976D2;">' . formatearMonedaAR($total_facturado) . '</td></tr>';
    echo '<tr><td style="background-color: #E3F2FD; font-weight: bold;">Productos Vendidos:</td><td style="text-align: right;">' . $cantidad_productos . '</td></tr>';
    echo '<tr><td style="background-color: #E3F2FD; font-weight: bold;">Promedio por Venta:</td><td style="text-align: right;">' . formatearMonedaAR($promedio_venta) . '</td></tr>';
    echo '</table>';
    
    echo '<br><br>';
    
    // Tabla de ventas con formato
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<thead>';
    echo '<tr style="background-color: #FF9800; color: white; font-weight: bold;">';
    echo '<th style="width: 50px;">ID</th>';
    echo '<th style="width: 100px;">Fecha</th>';
    echo '<th style="width: 80px;">Hora</th>';
    echo '<th style="width: 120px;">Usuario</th>';
    echo '<th style="width: 300px;">Productos</th>';
    echo '<th style="width: 120px;">Método Pago</th>';
    echo '<th style="width: 100px;">Total</th>';
    echo '<th style="width: 80px;">Turno</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $row_color = true;
    foreach ($ventas as $venta) {
        $datetime = new DateTime($venta['fecha']);
        $bg_color = $row_color ? '#FFF3E0' : '#FFFFFF';
        
        echo '<tr style="background-color: ' . $bg_color . ';">';
        echo '<td style="text-align: center;">' . $venta['id'] . '</td>';
        echo '<td style="text-align: center;">' . $datetime->format('Y-m-d') . '</td>';
        echo '<td style="text-align: center;">' . $datetime->format('H:i:s') . '</td>';
        echo '<td>' . ($venta['usuario_nombre'] ?: 'Desconocido') . '</td>';
        echo '<td>' . ($venta['productos'] ?: 'Sin detalles') . '</td>';
        echo '<td style="text-align: center;">' . ($venta['metodo_pago'] ?: 'efectivo') . '</td>';
        echo '<td style="text-align: right; font-weight: bold;">' . formatearMonedaAR($venta['total']) . '</td>';
        echo '<td style="text-align: center;">' . ($venta['turno'] ?: '-') . '</td>';
        echo '</tr>';
        
        $row_color = !$row_color;
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body></html>';

    exit();
    
} else if ($tipo == 'pdf') {
    // ============================================
    // EXPORTAR A PDF
    // ============================================
    
    require_once('pdf/fpdf/fpdf.php');
        $desde = $_GET['fecha_inicio'];
    $hasta = $_GET['fecha_fin'];
        $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    
    // Título
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE DE VENTAS', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Período: ' . $_GET['fecha_inicio'] . ' al ' . $_GET['fecha_fin'], 0, 1, 'C');
    $pdf->Ln(5);
    
    // Resumen en tabla
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'RESUMEN', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell(0, 6, mb_convert_encoding('Período: ' . $desde . ' al ' . $hasta, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Resumen de Estadísticas
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, mb_convert_encoding('ESTADÍSTICAS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell(90, 6, 'Total de Ventas:', 1, 0, 'L', true);
    $pdf->Cell(90, 6, $total_ventas, 1, 1, 'R');
    
    $pdf->Cell(90, 6, 'Total Facturado:', 1, 0, 'L', true);
    $pdf->Cell(90, 6, formatearMonedaAR($total_facturado), 1, 1, 'R');
    
    $pdf->Cell(90, 6, 'Productos Vendidos:', 1, 0, 'L', true);
    $pdf->Cell(90, 6, $cantidad_productos, 1, 1, 'R');
    
    $pdf->Cell(90, 6, 'Promedio por Venta:', 1, 0, 'L', true);
    $pdf->Cell(90, 6, formatearMonedaAR($promedio_venta), 1, 1, 'R');
    
    $pdf->Ln(5);
    
    // Tabla de ventas
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, 'DETALLE DE VENTAS', 0, 1, 'L');
    
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(200, 200, 200);
    
    $pdf->Cell(15, 6, 'ID', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Usuario', 1, 0, 'C', true);
    $pdf->Cell(35, 6, mb_convert_encoding('Método Pago', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Total', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Turno', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 7);
    
    foreach ($ventas as $venta) {
        $datetime = new DateTime($venta['fecha']);
        
        $pdf->Cell(15, 5, $venta['id'], 1, 0, 'C');
        $pdf->Cell(30, 5, $datetime->format('Y-m-d'), 1, 0, 'C');
        $pdf->Cell(40, 5, mb_convert_encoding(substr($venta['usuario_nombre'] ?: 'Desconocido', 0, 25), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $pdf->Cell(35, 5, mb_convert_encoding($venta['metodo_pago'] ?: 'efectivo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(30, 5, formatearMonedaAR($venta['total']), 1, 0, 'R');
        $pdf->Cell(30, 5, mb_convert_encoding($venta['turno'] ?: '-', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
    }
    
    // Salida del PDF - Modo inline para visualizar en el navegador
    $pdf->Output('I', 'reporte_ventas_' . date('Y-m-d_His') . '.pdf');
    exit();
}
?>
