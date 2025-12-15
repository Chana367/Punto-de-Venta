<?php
session_start();
require "../conexion.php";

// Verificar sesión activa
if (!isset($_SESSION['active'])) {
    header("Location: ../");
    exit;
}

// Consultas para los totales usando PDO
$total = [];

// Total de usuarios
$stmt = $conexion->query("SELECT COUNT(*) AS count FROM usuario");
$total['usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total de clientes
// $stmt = $conexion->query("SELECT COUNT(*) AS count FROM cliente");
// $total['clientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total de productos
$stmt = $conexion->query("SELECT COUNT(*) AS count FROM producto");
$total['productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total de ventas de hoy
$stmt = $conexion->query("SELECT COUNT(*) AS count FROM ventas WHERE date(fecha) = date('now')");
$total['ventas'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

include_once "includes/header.php";
?>
<!-- Content Row -->
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
            <div class="card-header card-header-warning card-header-icon">
                <div class="card-icon">
                    <i class="fas fa-user fa-2x"></i>
                </div>
                <a href="usuarios.php" class="card-category text-warning font-weight-bold">
                    Usuarios
                </a>
                <h3 class="card-title"><?php echo $total['usuarios']; ?></h3>
            </div>
            <div class="card-footer bg-warning text-white">
            </div>
        </div>
    </div>

        <!-- <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card card-stats">
                <div class="card-header card-header-success card-header-icon">
                    <div class="card-icon">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <a href="clientes.php" class="card-category text-success font-weight-bold">
                        Clientes
                    </a>
                    <h3 class="card-title"><?php //echo $total['clientes']; ?></h3>
                </div>
                <div class="card-footer bg-secondary text-white">
                </div>
            </div>
        </div> -->
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
            <div class="card-header card-header-danger card-header-icon">
                <div class="card-icon">
                    <i class="fab fa-product-hunt fa-2x"></i>
                </div>
                <a href="productos.php" class="card-category text-danger font-weight-bold">
                    Productos
                </a>
                <h3 class="card-title"><?php echo $total['productos']; ?></h3>
            </div>
            <div class="card-footer bg-primary">
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
            <div class="card-header card-header-info card-header-icon">
                <div class="card-icon">
                    <i class="fas fa-cash-register fa-2x"></i>
                </div>
                <a href="ventas.php" class="card-category text-info font-weight-bold">
                    Ventas
                </a>
                <h3 class="card-title"><?php echo $total['ventas']; ?></h3>
            </div>
            <div class="card-footer bg-danger text-white">
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header card-header-primary">
                <h3 class="title-2 m-b-40">Productos con stock mínimo</h3>
            </div>
            <div class="card-body">
                <canvas id="stockMinimo"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header card-header-primary">
                <h3 class="title-2 m-b-40">Productos más vendidos</h3>
            </div>
            <div class="card-body">
                <canvas id="ProductosVendidos"></canvas>
            </div>
        </div>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>
