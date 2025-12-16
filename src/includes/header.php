<?php
if (empty($_SESSION['active'])) {
    header('Location: ../');
}

// Obtener configuración del sistema (logo y background)
$config_query = $conexion->query("SELECT logo, background FROM configuracion LIMIT 1");
$config_data = $config_query->fetch(PDO::FETCH_ASSOC);
$logo_sistema = $config_data['logo'] ?? 'logo.png';
$background_sistema = $config_data['background'] ?? 'sidebar-1.jpg';

// Obtener permisos del usuario actual con acciones detalladas
$id_user = $_SESSION['idUser'];
$permisos_usuario = [];
$permisos_acciones = [];

// Solo cargar permisos si no es el usuario administrador (id 1)
if ($id_user != 1) {
    $sql_permisos = $conexion->prepare("SELECT p.nombre, d.puede_crear, d.puede_leer, d.puede_actualizar, d.puede_eliminar FROM permisos p INNER JOIN detalle_permisos d ON p.id = d.id_permiso WHERE d.id_usuario = :id_user");
    $sql_permisos->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $sql_permisos->execute();
    $result_permisos = $sql_permisos->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($result_permisos as $permiso) {
        $permisos_usuario[] = $permiso['nombre'];
        $permisos_acciones[$permiso['nombre']] = [
            'crear' => $permiso['puede_crear'],
            'leer' => $permiso['puede_leer'],
            'actualizar' => $permiso['puede_actualizar'],
            'eliminar' => $permiso['puede_eliminar']
        ];
    }
}

// Función helper para verificar si el usuario tiene un permiso
function tienePermiso($permiso) {
    global $permisos_usuario, $id_user;
    return $id_user == 1 || in_array($permiso, $permisos_usuario);
}

// Función helper para verificar si el usuario puede realizar una acción específica
function puedeAccion($permiso, $accion) {
    global $permisos_acciones, $id_user;
    
    // El admin puede todo
    if ($id_user == 1) {
        return true;
    }
    
    // Si no tiene el permiso, no puede hacer nada
    if (!isset($permisos_acciones[$permiso])) {
        return false;
    }
    
    // Retornar si puede realizar la acción específica
    return isset($permisos_acciones[$permiso][$accion]) && $permisos_acciones[$permiso][$accion] == 1;
}
?>
<!DOCTYPE html>
<html lang="es-AR">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <link rel="icon" href="get_image.php?file=<?php echo urlencode($logo_sistema); ?>" type="image/png">
    <meta name="author" content="" />
    <title>Panel de Administración</title>
    <link href="../assets/css/material-dashboard.css" rel="stylesheet" />
    <link href="../assets/css/dataTables.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="../assets/js/jquery-ui/jquery-ui.min.css">
    <script src="../assets/js/all.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="wrapper ">
        <div class="sidebar" data-color="purple" data-background-color="black" data-image="get_image.php?file=<?php echo urlencode($background_sistema); ?>">
            <div class="logo"><a href="./" class="simple-text logo-normal">
                    Punto de Venta
                </a></div>
            <div class="sidebar-wrapper">
                <ul class="nav">
                    <?php if (tienePermiso('usuarios')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="usuarios.php">
                            <i class="fas fa-user mr-2 fa-2x"></i>
                            <p> Usuarios</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('configuracion')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="config.php">
                            <i class="fas fa-cogs mr-2 fa-2x"></i>
                            <p> Configuración</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('productos')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="productos.php">
                            <i class="fab fa-product-hunt mr-2 fa-2x"></i>
                            <p> Productos</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('clientes')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="clientes.php">
                            <i class=" fas fa-users mr-2 fa-2x"></i>
                            <p> Clientes</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('nueva_venta')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="ventas.php">
                            <i class="fas fa-cash-register mr-2 fa-2x"></i>
                            <p> Nueva Venta</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('ventas')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="lista_ventas.php">
                            <i class="fas fa-cart-plus mr-2 fa-2x"></i>
                            <p> Historial Ventas</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (tienePermiso('reportes') || $id_user == 1): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex" href="reportes.php">
                            <i class="fas fa-chart-bar mr-2 fa-2x"></i>
                            <p> Reportes</p>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="main-panel">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-absolute fixed-top bg-dark">
                <div class="container-fluid">
                    <div class="navbar-wrapper">
                        <a class="navbar-brand" href="javascript:;">Punto de Venta</a>
                    </div>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                        <span class="navbar-toggler-icon icon-bar"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end">

                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="javascript:;" id="navbarDropdownProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-user"></i>
                                    <p class="d-lg-none d-md-block">
                                        Cuenta
                                    </p>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownProfile">
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#nuevo_pass">Perfil</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="salir.php">Cerrar Sesión</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- End Navbar -->
            <div class="content">
                <div class="container-fluid">