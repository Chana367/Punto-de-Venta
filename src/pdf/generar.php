<?php
session_start();
require_once "../../conexion.php";
require_once 'fpdf/fpdf.php';

// Cambiar el tamaño del documento a A4 (210 x 297 mm)
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetTitle("Ventas");
$pdf->SetFont('Arial', 'B', 16);
$id = $_GET['v'];
$idcliente = $_GET['cl'];

// Consulta de configuración
$config = $conexion->query("SELECT * FROM configuracion");
$datos = $config->fetch(PDO::FETCH_ASSOC);
$logo_pdf = $datos['logo'] ?? 'logo1.jpg';

// Consulta del cliente
$clientes = $conexion->prepare("SELECT * FROM cliente WHERE idcliente = :idcliente");
$clientes->bindParam(':idcliente', $idcliente, PDO::PARAM_INT);
$clientes->execute();
$datosC = $clientes->fetch(PDO::FETCH_ASSOC);

// Valores por defecto si no se encuentra el cliente
if (!$datosC) {
    $datosC = [
        'nombre' => 'Cliente General',
        'telefono' => 'N/A',
        'direccion' => 'N/A'
    ];
}

// Consulta de ventas con información del usuario y descuento
$ventaInfo = $conexion->prepare("SELECT v.*, u.nombre as usuario_nombre FROM ventas v LEFT JOIN usuario u ON v.id_usuario = u.idusuario WHERE v.id = :id");
$ventaInfo->bindParam(':id', $id, PDO::PARAM_INT);
$ventaInfo->execute();
$datosVenta = $ventaInfo->fetch(PDO::FETCH_ASSOC);

$descuento_global = isset($datosVenta['descuento_global']) ? floatval($datosVenta['descuento_global']) : 0;
$usuario_venta = $datosVenta['usuario_nombre'] ?? 'Desconocido';

// Consulta de detalle de ventas con productos
$ventas = $conexion->prepare("SELECT d.*, p.codproducto, p.descripcion FROM detalle_venta d INNER JOIN producto p ON d.id_producto = p.codproducto WHERE d.id_venta = :id");
$ventas->bindParam(':id', $id, PDO::PARAM_INT);
$ventas->execute();

// Recuadro con "X" grande de presupuesto
$pdf->SetFont('Arial', 'B', 50);
$pdf->SetTextColor(0, 0, 0);  // Color negro
$width = 20;
$height = 20;
$x = ($pdf->GetPageWidth() - $width) / 2;
$y = 30;
$pdf->Rect($x, $y, $width, $height, 'D'); // Recuadro centrado
$pdf->SetXY($x, $y + 2);
$pdf->Cell($width, $height, 'X', 0, 0, 'C');
$pdf->SetTextColor(0, 0, 0); // Asegurar que el color del texto se restaure
$pdf->Ln(20);
// Líneas horizontales al costado del recuadro
$pdf->Line($x - 100, $y + $height / 2, $x, $y + $height / 2); // Línea izquierda
$pdf->Line($x + $width, $y + $height / 2, $x + $width + 100, $y + $height / 2); // Línea derecha
$pdf->Ln(20);

// Encabezado
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, mb_convert_encoding($datos['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, mb_convert_encoding("Teléfono: " . $datos['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding("Dirección: " . $datos['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding("Correo: " . $datos['email'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Intentar cargar el logo desde APPDATA, si no existe usar el de assets
$appDataDir = getenv('APPDATA') . '\\PuntoVenta\\imagenes';
$logoPathAppData = $appDataDir . '\\' . $logo_pdf;
$logoPathAssets = "../../assets/img/" . $logo_pdf;

$logoToUse = null;
if (file_exists($logoPathAppData)) {
    $logoToUse = $logoPathAppData;
} elseif (file_exists($logoPathAssets)) {
    $logoToUse = $logoPathAssets;
}

if ($logoToUse && is_file($logoToUse)) {
    $ext = strtolower(pathinfo($logoToUse, PATHINFO_EXTENSION));
    
    // Determinar el tipo correcto para FPDF
    $imageType = '';
    if ($ext === 'png') {
        $imageType = 'PNG';
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $imageType = 'JPG';
    } elseif ($ext === 'gif') {
        $imageType = 'GIF';
    }
    
    if ($imageType) {
        try {
            $pdf->Image($logoToUse, 170, 60, 20, 20, $imageType);
        } catch (Exception $e) {
            // Si falla, simplemente no mostrar logo
            // Error: $e->getMessage()
        }
    }
} 

$pdf->Ln(10);

// Datos del cliente
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "Datos del cliente", 1, 1, 'C', 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, mb_convert_encoding('Nombre: ' . $datosC['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding('Teléfono: ' . $datosC['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding('Dirección: ' . $datosC['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Mostrar el usuario que generó la venta
if ($usuario_venta) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, mb_convert_encoding('Vendedor: ' . $usuario_venta, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
}

$pdf->Ln(10);

// Detalle de Producto
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "Detalle de Producto", 1, 1, 'C', 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(90, 10, mb_convert_encoding('Descripción', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(30, 10, 'Cant.', 1, 0, 'L');
$pdf->Cell(35, 10, 'Precio Unit', 1, 0, 'L');
$pdf->Cell(35, 10, 'Sub Total', 1, 1, 'L');

$ventas_data = $ventas->fetchAll(PDO::FETCH_ASSOC);
$total_sin_descuento = 0.00;
foreach ($ventas_data as $row) {
    $pdf->Cell(90, 10, mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(30, 10, $row['cantidad'], 1, 0, 'L');
    $pdf->Cell(35, 10, '$' . number_format($row['precio'], 2, ',', '.'), 1, 0, 'L');
    
    $sub_total = $row['total'];
    $total_sin_descuento = $total_sin_descuento + $sub_total;
    
    $pdf->Cell(35, 10, '$' . number_format($sub_total, 2, ',', '.'), 1, 1, 'L');
}
$pdf->Ln(10);

// Calcular total con descuento global
$total_con_descuento = $total_sin_descuento * (1 - $descuento_global / 100);

// Totales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Subtotal', 0, 1, 'R');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, '$' . number_format($total_sin_descuento, 2, ',', '.'), 0, 1, 'R');

if ($descuento_global > 0) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Descuento Global (' . number_format($descuento_global, 2) . '%)', 0, 1, 'R');
    $pdf->SetFont('Arial', '', 12);
    $monto_descuento = $total_sin_descuento * ($descuento_global / 100);
    $pdf->Cell(0, 10, '-$' . number_format($monto_descuento, 2, ',', '.'), 0, 1, 'R');
}

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Total a Pagar', 0, 1, 'R');
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, '$' . number_format($total_con_descuento, 2, ',', '.'), 0, 1, 'R');

$pdf->Output("I", "ventas.pdf");
?>
