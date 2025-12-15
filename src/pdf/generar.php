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

// Consulta de ventas con información del usuario
$ventas = $conexion->prepare("SELECT d.*, p.codproducto, p.descripcion, v.id_usuario, u.nombre as usuario_nombre FROM detalle_venta d INNER JOIN producto p ON d.id_producto = p.codproducto INNER JOIN ventas v ON d.id_venta = v.id LEFT JOIN usuario u ON v.id_usuario = u.idusuario WHERE d.id_venta = :id");
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

// Intentar cargar el logo desde configuración
$logoPath = "../../assets/img/" . $logo_pdf;
if (file_exists($logoPath)) {
    $ext = strtolower(pathinfo($logo_pdf, PATHINFO_EXTENSION));
    $imageType = ($ext === 'png') ? 'PNG' : 'JPG';
    $pdf->Image($logoPath, 170, 60, 20, 20, $imageType);
} 

$pdf->Ln(10);

// Datos del cliente
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "Datos del cliente", 1, 1, 'C', 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, mb_convert_encoding('Nombre: ' . $datosC['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding('Teléfono: ' . $datosC['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Cell(0, 10, mb_convert_encoding('Dirección: ' . $datosC['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Obtener información del usuario (de la primera fila del detalle)
$usuario_venta = null;
$ventas_data = $ventas->fetchAll(PDO::FETCH_ASSOC);
if (!empty($ventas_data)) {
    $usuario_venta = $ventas_data[0]['usuario_nombre'] ?? 'Desconocido';
}

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
$total = 0.00;
$desc = 0.00;
foreach ($ventas_data as $row) {
    $pdf->Cell(90, 10, mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(30, 10, $row['cantidad'], 1, 0, 'L');
    $pdf->Cell(35, 10, '$' . number_format($row['precio'], 2, ',', '.'), 1, 0, 'L');
    
    $sub_total = $row['total'];
    $total = $total + $sub_total;
    $desc = $desc + $row['descuento'];
    
    $pdf->Cell(35, 10, '$' . number_format($sub_total, 2, ',', '.'), 1, 1, 'L');
}
$pdf->Ln(10);

// Totales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Descuento Total', 0, 1, 'R');
$pdf->SetFont('Arial', '', 12);
$desc_formatted = number_format($desc, 2, ',', '.') . " %";
$pdf->Cell(0, 10, $desc_formatted, 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Total a Pagar', 0, 1, 'R');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, '$' . number_format($total, 2, '.', ','), 0, 1, 'R');

$pdf->Output("I", "ventas.pdf");
?>
